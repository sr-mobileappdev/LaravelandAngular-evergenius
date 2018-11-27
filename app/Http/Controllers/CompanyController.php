<?php

namespace App\Http\Controllers;
use App\AdminView;
use App\Classes\CompanyHelper;
use App\Classes\CompanySettingsHelper;
use App\Classes\NotificationHelper;
use App\Classes\UserHelper;
use App\Company;
use App\CompanySetting;
use App\NotificationSetting;
use App\Service;
use App\Stage;
use App\User;
use Auth;
use DateTime;
use Hash;
use Illuminate\Http\Request;
use Input;
//use App\Traits\companySettings;
use Mail;
use App\Classes\HonestdoctorHelper;
use App\Classes\TwillioHelper;

class CompanyController extends Controller
{
    public $required_settings = array('facebook_url',
        'twilio_number',
        'twilio_sid',
        'twilio_auth_id',
        'twilio_enable',
        'analytics_profile_id',
        'timezone', 'yext_location_id',
        'mailchimp_api_key',
        'ltv_value',
        'mailchimp_dc',
        'pa_site_id');

    public function getSetting($company_id, $name)
    {
        $data = CompanySetting::
        where('company_id', $company_id)
            ->where('name', $name)
            ->first()->toArray();
        if (count($data) > 0) {
            return $data->value;
        } else {
            return false;
        }
    }


    public function isSettingsExists($company_id, $name)
    {
        $data = CompanySetting::
        where('company_id', $company_id)
            ->where('name', $name)
            ->first();
        if (count($data) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function setSetting($company_id, $name, $value)
    {
        /*Check if not Exists*/
        $is_exists = $this->isSettingsExists($company_id, $name);
        /* Update data */
        if ($is_exists != false) {
            $data = CompanySetting::where('company_id', $company_id)
                ->where('name', $name);
            $data->update(array('value' => $value));
            return true;
        } else {
            $company_setting = new CompanySetting;
            $company_setting->company_id = $company_id;
            $company_setting->name = $name;
            $company_setting->value = $value;
            $company_setting->save();
            return $company_setting->id;
        }
    }

    public function getShowSettings()
    {
        $this->create_requred_settings();
        $company_settings = array();
        $user = Auth::user();
        $company_id = $company_id = $user->company_id;
        $company_details = Company::where('id', $company_id)
            ->first()
            ->toArray();
        $social_urls = [];
        $social_data = CompanyHelper::getSocialUrlsTerm($company_id, $company_id, 'company');
        if ($social_data) {
            $social_urls = CompanyHelper::bindUrls($social_data);
        }

        /* Get All Settings of Company*/
        $settings = CompanySetting::select(array('name', 'value'))
            ->where('company_id', $company_id)
            ->get()
            ->toArray();
        foreach ($settings as $key => $setting) {
            $company_settings[$setting['name']] = $setting['value'];
        }
        $stngs = array('companysettings' => $company_settings);
        $company_details = array_merge($company_details, $stngs);
        $company_details['specialities'] = HonestdoctorHelper::getTermSpecializations('company', $company_id);
        $company_details['social_urls'] = $social_urls;
        return response()->success($company_details);
    }


    public function putShowSettings()
    {
        $user = Auth::user();
        $company_id = $company_id = $user->company_id;
        $input_data = Input::get();
        $social_urls = [];
        if (isset($input_data['data']['social_urls'])) {
            $social_urls = $input_data['data']['social_urls'];
            unset($input_data['data']['social_urls']);
        }

        $company_settings = $input_data['data']['companysettings'];
        unset($input_data['data']['companysettings']);
        $hd_spcl_ids = [];
        /* Attach Company specialities */
        if (isset($input_data['data']['specialities'])) {
            $hd_spcl_ids = HonestdoctorHelper::attachSpecializations($input_data['data']['specialities'], 'company', $company_id);
            unset($input_data['data']['specialities']);
        }

        $affectedRows = Company::where('id', '=', intval($company_id))->update($input_data['data']);
        $up_data = $input_data['data'];
        $up_data['claim_status'] = 'Approved';
        /* Update company*/
        if (count($social_urls) > 0) {
            foreach ($social_urls as $media => $url) {
                $up_data[$media] = $url;
                if (!CompanyHelper::isSocialUrlExists($company_id, $company_id, 'company', $media)) {
                    CompanyHelper::addSocialUrls($company_id, $company_id, 'company', $url, $media);
                } else {
                    CompanyHelper::SocialUrlUpdate($company_id, $company_id, 'company', $media, $url);
                }
            }
        }
        //print_r($up_data); die;
        if ($input_data['data']['hd_post_id'] != null) {
            HonestdoctorHelper::updateCompanyHD($up_data, $hd_spcl_ids, $company_id);
        } else {
            $up_data = $input_data['data'];
            HonestdoctorHelper::addCompanyHD($up_data, $hd_spcl_ids, $company_id);
            unset($input_data['data']['hd_post_id']);
        }

        foreach ($company_settings as $key => $settings) {
            $name = $key;
            $value = $settings;

            $this->setSetting($company_id, $name, $value);
        }


        return response()->success('Company Data Update');
    }


    public function postUpdateCompanyLogo(Request $request)
    {
        $user = Auth::user();
        $company_id = $company_id = $user->company_id;
        $image = $request->file('company_logo');
        if ($image) {
            $this->validate($request, [
                'company_logo' => 'image|mimes:jpeg,png,jpg,gif,svg|max:5121',
            ]);
            $input['company_logo'] = time() . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path('/images/company_images');
            $image->move($destinationPath, $input['company_logo']);
            $image_path = "images/company_images/" . $input['company_logo'];
            $company_info = CompanyHelper::getCompanyDetais($company_id);
            if ($company_info['hd_post_id'] != null) {
                $logo_link = url($image_path);
                $media_Id = HonestdoctorHelper::postMediaHD($logo_link);
                HonestdoctorHelper::updateCompanyMediaHD($media_Id, $company_id);
            }
            $update_date = array('logo' => $image_path);
            $affectedRows = Company::where('id', '=', intval($company_id))->update($update_date);
            return response()->success('logo Updated');
        }
        return response()->success('file not found');
    }


    public function create_requred_settings($company_id = null)
    {
        if ($company_id == null) {
            $user = Auth::user();
            $company_id = $user->company_id;
        }

        $rs = $this->required_settings;
        foreach ($rs as $setting) {
            $is_created = $this->isSettingsExists($company_id, $setting);
            if ($is_created == false) {
                $company_setting = new CompanySetting;
                $company_setting->company_id = $company_id;
                $company_setting->name = $setting;
                $company_setting->value = "";
                $company_setting->save();
                $company_setting->id;
            }
        }
    }

    public function getNotificationSettings()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $company_notifications = NotificationHelper::getcompanyNotifications($company_id);

        /* get notification for incoming sms calls etc*/
        $notification_controls = CompanySettingsHelper::getNotificationControls($company_id);
        $notification_useremails = CompanySettingsHelper::getNotifyUserEmails($company_id);
        $notification_userphones = CompanySettingsHelper::getNotifyUserPhones($company_id);

        return response()->success(array('company_notifications' => $company_notifications,
            'notification_controls' => $notification_controls, 'notification_useremails' => $notification_useremails,
            'notification_userphones' => $notification_userphones
        ));
    }

    public function putNotificationSettings()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $input_data = Input::get();
        $notifications = $input_data['data']['company_notifications'];
        $controls = $input_data['data']['notification_controls'];

        if (isset($input_data['data']['companynotifyuseremails'])) {
            $companynotifyuseremails = $input_data['data']['companynotifyuseremails'];
            CompanySettingsHelper::updatetNotifyUserEmails($companynotifyuseremails, $company_id);
        } else {
            CompanySettingsHelper::deleteNotifyByType($company_id, 'email');
        }

        if (isset($input_data['data']['companynotifyuserphones'])) {
            $companynotifyuserphones = $input_data['data']['companynotifyuserphones'];
            CompanySettingsHelper::updatetNotifyUserPhones($companynotifyuserphones, $company_id);
        } else {
            CompanySettingsHelper::deleteNotifyByType($company_id, 'phone');
        }

        NotificationHelper::updateNotifications($notifications);
        /* update notification for incoming sms calls etc*/
        CompanySettingsHelper::updatetNotificationControls($controls, $company_id);
        CompanyHelper::changeIntigrationStatus($company_id, 'notification_setup');
        return response()->success($input_data);
    }

    /**show settings of all companies**/
    public function getCompanySettings($company_id)
    {
        $company_id = $company_id;
        //$this->create_requred_settings();
        $company_settings = array();
        $company_details = Company::where('id', $company_id)->first()->toArray();

        /* Get All Settings of Company*/
        $adminView = AdminView::where('company_id', $company_id)->select('user_id')->first();
        if ($adminView) {
            $user = User::find($adminView->user_id);
            $user_email = $user->email;
            $user_info = User::find($adminView->user_id);
        }

        $settings = CompanySetting::select(array('name', 'value'))
            ->where('company_id', $company_id)
            ->get()
            ->toArray();

        foreach ($settings as $key => $setting) {
            $company_settings[$setting['name']] = $setting['value'];
        }


        if (count($settings) < 1) {
            $this->create_requred_settings($company_id);
            $settings = CompanySetting::select(array('name', 'value'))
                ->where('company_id', $company_id)
                ->get()
                ->toArray();
            foreach ($settings as $key => $setting) {
                $company_settings[$setting['name']] = $setting['value'];
            }
        }

        $stngs = array('companysettings' => $company_settings);
        if ($user_email) {
            $userEmail = array('user_email' => $user_email,
                'user_name' => $user_info->name,
                'user_id' => $user_info->id,
                'user_phone' => $user_info->phone,
                'user_country_code' => $user_info->phone_country_code
            );
            $company_details = array_merge($company_details, $userEmail);
        }
        /* Social Urls Get*/
        $company_details = array_merge($company_details, $stngs);
        $social_urls = [];
        $social_data = CompanyHelper::getSocialUrlsTerm($company_id, $company_id, 'company');
        if ($social_data) {
            $social_urls = CompanyHelper::bindUrls($social_data);
        }
        $company_details = array_merge($company_details, ['social_urls' => $social_urls]);
        /* /Social Urls Get*/
        $company_details['specialities'] = HonestdoctorHelper::getTermSpecializations('company', $company_id);
        return response()->success($company_details);
    }

    public function putCompanySettings(Request $request)
    {
        $input_data = Input::get();
        //dd($input_data['data']['social_urls']);
        if (isset($input_data['data']['social_urls'])) {
            $social_urls = $input_data['data']['social_urls'];
            unset($input_data['data']['social_urls']);
        }


        $company_id = $input_data['data']['id'];

        $company_info = CompanyHelper::getCompanyDetais($company_id);
        $company_settings = $input_data['data']['companysettings'];
        $new_password = isset($input_data['data']['user_password']) ? $input_data['data']['user_password'] : "";
        unset($input_data['data']['companysettings']);
        unset($input_data['data']['user_country_code']);

        unset($input_data['data']['user_password']);
        /* Edit User Info */

        $userinfo = User::where('id', $input_data['data']['user_id'])->first();
        unset($input_data['data']['user_id']);
        if (isset($input_data['data']['user_name'])) {
            $userinfo->name = trim($input_data['data']['user_name']);
            unset($input_data['data']['user_name']);
        }

        if (isset($input_data['data']['user_email'])) {
            /*If User email changed and user laready exists then throw error*/
            if (strtolower($input_data['data']['user_email']) != strtolower($userinfo->email)) {
                $mail_exists = UserHelper::isEmailExists($input_data['data']['user_email']);
                if ($mail_exists) {
                    return response()->error('please change user email user already exists.');
                }
            }

            $userinfo->email = trim($input_data['data']['user_email']);
            unset($input_data['data']['user_email']);
        }

        if (array_key_exists('user_phone', $input_data['data'])) {
            $userinfo->phone = trim($input_data['data']['user_phone']);
            unset($input_data['data']['user_phone']);
        }
        if (isset($input_data['data']['phone_country_code'])) {
            $userinfo->phone_country_code = trim($input_data['data']['phone_country_code']);
            unset($input_data['data']['phone_country_code']);
        } else {
            $userinfo->phone_country_code = '+1';
        }
        if ($new_password != "") {
            $admin_profile = AdminView::where('company_id', $company_id)->first();
            $userinfo->password = Hash::make($new_password);
        }
        $userinfo->save();
        /* /Edit User Info */
        /* Attach Company specialities */
        if (isset($input_data['data']['specialities'])) {
            $hd_spcl_ids = HonestdoctorHelper::attachSpecializations($input_data['data']['specialities'], 'company', $company_id);
            unset($input_data['data']['specialities']);
        }


        if ($input_data['data']['hd_post_id'] != null) {
            $up_data = $input_data['data'];
            //print_r($up_data); die;
            HonestdoctorHelper::updateCompanyHD($up_data, $hd_spcl_ids, $company_id);
        } else {
            $up_data = $input_data['data'];
            HonestdoctorHelper::addCompanyHD($up_data, $hd_spcl_ids, $company_id);
            unset($input_data['data']['hd_post_id']);
        }
        $affectedRows = Company::where('id', '=', intval($company_id))->update($input_data['data']);
        foreach ($company_settings as $key => $settings) {
            $name = $key;
            $value = $settings;
            $this->setSetting($company_id, $name, $value);
        }
        if (count($social_urls) > 0) {
            foreach ($social_urls as $media => $url) {
                $up_data[$media] = $url;
                if (!CompanyHelper::isSocialUrlExists($company_id, $company_id, 'company', $media)) {
                    CompanyHelper::addSocialUrls($company_id, $company_id, 'company', $url, $media);
                } else {
                    CompanyHelper::SocialUrlUpdate($company_id, $company_id, 'company', $media, $url);
                }
            }
        }

        return response()->success('Company Data Update');
    }

    /**SUPER ADMIN: Create a company profile**/
    public function postSaveCompanySettings(Request $request)
    {
        $user = Auth::user();
        $cur_user = $user;
        $cur_user_id = $cur_user->id;
        $c_user_role = $user->roles->toArray()[0]['slug'];
        if ($c_user_role == 'super.admin.agent') {
            $userCompanieCount = \App\Classes\UserHelper::getAgencyComapnieCount($user->id);
            if ($userCompanieCount >= $user->num_license) {
                return response()->error('You have exceed maximum number of license.');
            }
        }

        $media_Id = null;
        $hd_spcl_ids = [];
        $user = Auth::user();
        $input_data = Input::get();

        $d = $input_data['data'];
        $social_urls = [];

        if (isset($input_data['data']['social_urls'])) {
            $social_urls = $input_data['data']['social_urls'];
            unset($input_data['data']['social_urls']);
        }
        $count = Company::where('email', $d['email'])->orWhere('name', $d['name'])->count();
        $Ucount = User::where('email', $d['user_email'])->count();
        if (!$count && !$Ucount) {
            $eg_api_key = CompanyHelper::generateUuid();
            $comp = new Company;
            $comp->name = $d['name'];
            $comp->email = $d['email'];
            $comp->phone = $d['phone'];
            $comp->site_url = $d['site_url'];
            if (isset($d['description'])) {
                $comp->description = $d['description'];
            }
            $comp->country = $d['country'];
            $comp->state = $d['state'];
            $comp->city = $d['city'];

            if ($c_user_role == 'super.admin.agent') {
                $comp->agency_status = 1;
            }
            if (isset($d['zip_code'])) {
                $comp->zip_code = $d['zip_code'];
            }
            /* Phone  Add country code */
            if (isset($d['phone_country_code'])) {
                $comp->phone_country_code = $d['phone_country_code'];
            }
            $comp->api_url = $d['site_url'] . "/wp-json/evergenius/v1";
            $comp->api_key = $eg_api_key;
            $comp->api_access_token = "";
            $comp->address = $d['address'];
            if (isset($request->logo)) {
                $image = base64_decode($request->logo['base64']);
                $image_ext = explode('.', $request->logo['filename']);
                $imagename = time() . '.' . $image_ext[1];
                $destinationPath = public_path('/images/company_images');
                file_put_contents($destinationPath . '/' . $imagename, $image);
                $comp->logo = "images/company_images/" . $imagename;
            }
            if ($c_user_role == 'super.admin.agent') {
                $comp->owner_id = $cur_user_id;
            }
            $status = $comp->save();
            if ($status) {
                /*Attach Company to User*/
                if ($c_user_role == 'super.call.center' || $c_user_role == 'super.admin.agent') {
                    \App\Classes\UserHelper::attachUserCompany($user->id, $comp->id);
                }

                /* Save Company Settings */
                $arraydata = array();
                $stages = \App\Stage::where('company_id', '0')->get();
                foreach ($stages as $stage) {
                    $arraydata[] = array('title' => $stage->title, 'slug' => $stage->slug, 'company_id' => $comp->id, 'created_at' => new DateTime);
                }
                \App\Stage::insert($arraydata);
                \App\Classes\ReviewHelper::createNewCompanySettings($comp->id);
                CompanySettingsHelper::addSetting($comp->id, 'lead_medium_time', '30');
                CompanySettingsHelper::addSetting($comp->id, 'lead_high_time', '30');
                CompanySettingsHelper::addSetting($comp->id, 'pending_email_notification_time', '30');
                if(isset($input_data['data']['ltv_value'])){
                    CompanySettingsHelper::addSetting($comp->id, 'ltv_value', trim($input_data['data']['ltv_value']));
                }
                CompanySettingsHelper::createNotificationControls($comp->id);

                /* Update timezone */
                CompanySettingsHelper::setSetting($comp->id, 'timezone', 'America/Chicago');
                /*Add Newletter to company*/
                CompanySettingsHelper::addSubscriptionList($comp->id);
                $company_id = $comp->id;
                $user = new User;
                $user->company_id = $company_id;
                $user->email_verified = 1;
                $user->status = 1;
                $user->email = $d['user_email'];
                if (isset($d['user_name'])) {
                    $user->name = trim($d['user_name']);
                }
                if (isset($d['user_name'])) {
                    $user->name = trim($d['user_name']);
                }
                if (isset($d['user_phone'])) {
                    $user->phone = trim($d['user_phone']);
                }
                if (isset($d['phone_country_code'])) {
                    $user->phone_country_code = trim($d['phone_country_code']);
                } else {
                    $user->phone_country_code = '+1';
                }
                $user->email = strtolower($d['user_email']);

                $user->password = Hash::make($d['user_password']);
                $userstatus = $user->save();
                if (count($social_urls) > 0) {
                    foreach ($social_urls as $media => $url) {
                        $up_data[$media] = $url;
                        if (!CompanyHelper::isSocialUrlExists($company_id, $company_id, 'company', $media)) {
                            CompanyHelper::addSocialUrls($company_id, $company_id, 'company', $url, $media);
                        } else {
                            CompanyHelper::SocialUrlUpdate($company_id, $company_id, 'company', $media, $url);
                        }
                    }
                }

                if ($userstatus) {
                    $user->attachRole(2); //attach role admin
                    $order_items = NotificationSetting::where('company_id', '=', '0')->
                    where('clone_to_companies', '=', '1')
                        ->get()
                        ->toArray();
                    foreach ($order_items as $item) {
                        unset($item['id']);
                        $item['company_id'] = $company_id;
                        NotificationSetting::insert($item);
                    }

                    /* Send Mail To Company owner */
                    $data['company_information'] = CompanyHelper::getCompanyDetais($company_id);
                    $data['company_information']['logo'] = '/img/mail_image_preview.png';

                    $admin_email_id = app_from_email();
                    $message = NotificationHelper::getNotificationMethod(0, 'evergenius_admin_notifications', 'ADD_COMPANY');
                    $subject = NotificationHelper::getNotificationSubject(0, 'evergenius_admin_notifications', 'ADD_COMPANY');
                    $d['website_url'] = url('/');
                    /* Attach Company specialities */
                    if (isset($d['specialities'])) {
                        $hd_spcl_ids = HonestdoctorHelper::attachSpecializations($d['specialities'], 'company', $company_id);
                    }

                    if (isset($request->logo)) {
                        $logo_link = url('/') . '/' . $comp->logo;
                        $media_Id = HonestdoctorHelper::postMediaHD($logo_link);
                    }
                    /*post status published */
                    //$d['hd_publish_status'] = 1;
                    HonestdoctorHelper::addCompanyHD($d, $hd_spcl_ids, $company_id, $media_Id);
                    if ($message != false && $subject != false) {
                        $message = nl2br($message);
                        $message = str_replace('{{$name}}', $d['name'], $message);
                        $message = str_replace('{{$website_url}}', $d['website_url'], $message);
                        $message = str_replace('{{$username}}', $d['user_email'], $message);
                        $message = str_replace('{{$password}}', $d['user_password'], $message);
                        if (strpos($message, '{{password_link}}') !== false) {
                            $reset_link = self::getUserResetPasswordLink($d['user_email']);
                            $message = str_replace('{{password_link}}', '<a href="' . $reset_link . '" > Set Password </a>', $message);
                            $message = str_replace('{{password_link_copy}}', $reset_link, $message);
                        }
                        $data['content_data'] = $message;
                        $bcc_email = getenv('BCC_EMAIL');

                        Mail::send('emails.add_new_doctor', compact('data'), function ($mail) use ($admin_email_id, $d, $subject, $bcc_email) {
                            $mail->to(trim(strtolower($d['user_email'])))
                                ->from($admin_email_id)
                                ->subject($subject);

                            if ($bcc_email != false) {
                                $mail->bcc($bcc_email, "EverGenius");
                            }
                        });
                    }
                    /*/Add Provider in In HD*/
                    $u_cpmpany_roles = restrictedCompaniesRoles();
                    if (in_array($c_user_role, $u_cpmpany_roles)) {
                        $admin_compnies = CompanyHelper::getAllUserCompanies($cur_user->id, $c_user_role);
                    } else {
                        $admin_compnies = CompanyHelper::getAllCompanies();
                    }
                    $out = array('status' => 'success', 'company_id' => $company_id, 'api_key' => $eg_api_key, 'admin_compnies' => $admin_compnies);
                    return response()->success($out);
                } else {
                    return response()->error('Error in creating company profile');
                }
            } else {
                return response()->error('Error in creating company profile');
            }
        } else {
            return response()->error('Company already exists with name or email address or user email');
        }
    }

    public function postSaveUpdateCompanyLogo(Request $request)
    {
        $media_Id = null;
        $data = $request->all();
        if ($data) {
            $company_info = CompanyHelper::getCompanyDetais($data['company_id']);
            $comp = Company::where('id',$data['company_id'])->first();
            $image = base64_decode($data['company_logo']['base64']);
            $image_ext = explode('.', $data['company_logo']['filename']);
            $imagename = time() . '.' . $image_ext[1];
            $destinationPath = public_path('/images/company_images');
            file_put_contents($destinationPath . '/' . $imagename, $image);
            $comp->logo = "images/company_images/" . $imagename;
            $comp->save();

            if ($company_info['logo'] != $comp->logo) {
                $logo_link = url('/') . '/' . $comp->logo;
                $media_Id = HonestdoctorHelper::postMediaHD($logo_link);
                HonestdoctorHelper::updateCompanyMediaHD($media_Id, $data['company_id']);
            }


            return response()->success('Company logo saved successfully');
        }
        return response()->success('file not found');
    }

    public function getOpportunityServices(Request $request)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $services = Service::where('company_id', $company_id)->get();
        $stages = Stage::where('company_id', $company_id)->get();
        $services = !empty($services) ? $services->toArray() : '';
        $stages = !empty($stages) ? $stages->toArray() : '';
        $lead_medium_time = [];
        $lead_high_time = [];
        $pending_email_notification_time = [];

        $lead_medium_time = CompanySetting::select('value')->where('company_id', $company_id)->where('name', 'lead_medium_time')->first();
        if (count($lead_medium_time) > 0) {
            $lead_medium_time = $lead_medium_time->toArray();
        }
        $lead_high_time = CompanySetting::select('value')->where('company_id', $company_id)->where('name', 'lead_high_time')->first();

        if (count($lead_high_time) > 0) {
            $lead_high_time = $lead_high_time->toArray();
        }

        $pending_email_notification_time = CompanySetting::select('value')->where('company_id', $company_id)->where('name', 'pending_email_notification_time')->first();

        if (count($pending_email_notification_time) > 0) {
            $pending_email_notification_time = $pending_email_notification_time->toArray();
        }

        return response()->success(array('services' => $services, 'stages' => $stages, 'lead_high_time' => $lead_high_time['value'], 'lead_medium_time' => $lead_medium_time['value'], 'pending_email_notification_time' => $pending_email_notification_time['value']));
    }

    public function postUpdateOpportunitySettings(Request $request)
    {
        $data = $request->all();
        $user = Auth::user();
        $company_id = $user->company_id;

        if ($data && !empty($data['services'])) {
            foreach ($data['services'] as $item) {
                if (is_numeric($item['id'])) {
                    $serve = Service::find($item['id']);
                    if ($serve) {
                        $serve->name = $item['name'];
                        $serve->save();
                    }
                } else {
                    $serve = new Service;
                    $serve->name = $item['name'];
                    $serve->company_id = $company_id;
                    $serve->created_at = date('Y-m-d H:i:s');
                    $serve->updated_at = date('Y-m-d H:i:s');
                    $serve->save();
                }
            }
        }

        if ($data && $data['stages']) {
            foreach ($data['stages'] as $item) {
                if (is_numeric($item['id'])) {
                    $Stages = Stage::find($item['id']);
                    if ($Stages) {
                        $Stages->title = $item['title'];
                        $Stages->save();
                    }
                } else {
                    Stage::insert(
                        array('title' => $item['title'],
                            'company_id' => $company_id,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at ' => date('Y-m-d H:i:s')
                        )
                    );
                }
            }
        }
        if ($data && $data['lead_medium_time']) {
            CompanySetting::where('company_id', $company_id)->where('name', 'lead_medium_time')->update(['value' => $data['lead_medium_time']]);
        }
        if ($data && $data['lead_high_time']) {
            CompanySetting::where('company_id', $company_id)->where('name', 'lead_high_time')->update(['value' => $data['lead_high_time']]);
        }
        if ($data && $data['pending_email_notification_time']) {
            CompanySetting::where('company_id', $company_id)->where('name', 'pending_email_notification_time')->update(['value' => $data['pending_email_notification_time']]);
        }
        return response()->success('Opportunity settings have been updated successfully !!');
    }

    public function postDeleteOpportunitySettings(Request $request)
    {
        $data = $request->all();
        if ($data['type'] == 'service') {
            Service::where('id', $data['id'])->delete();
        }
        if ($data['type'] == 'stage') {
            Stage::where('id', $data['id'])->delete();
        }
        return response()->success($data['type'] . ' Opportunity settings have been deleted successfully !!');
    }

    public function isCompanyActive()
    {
        if (Input::get('api_key')) {
            $api_key = Input::get('api_key');
            $is_active = CompanyHelper::is_api_active($api_key);
            if ($is_active) {
                return 1;
            }
            return 0;
        }
        return response()->error('Wrong/Disabled API key', 401);
    }

    public function postSmtpSettings(Request $request)
    {
        $data = Input::get();
        $user = Auth::user();
        $company_id = $user->company_id;

        if ($data) {
            foreach ($data as $key => $value) {
                if (array_key_exists('sendgrid_api_key', $data) && empty($data['sendgrid_api_key'])) {
                    return response()->json(array('status' => '500', 'message' => 'Sendgrid Api key required !!'), 500);
                } else {
                    $find_setting = CompanySetting::where('company_id', '=', $company_id)->where('name', '=', $key)->first();

                    if ($find_setting == null) {
                        $cmp_setting = new CompanySetting;
                        $cmp_setting->name = $key;
                        $cmp_setting->value = $data[$key];
                        $cmp_setting->company_id = $company_id;
                        $cmp_setting->created_at = date('Y-m-d H:i:s');
                        $cmp_setting->save();
                    } elseif ($find_setting != null) {
                        $cmp_setting = CompanySetting::find($find_setting->id);
                        $cmp_setting->name = $key;
                        $cmp_setting->value = $data[$key];
                        $cmp_setting->company_id = $company_id;
                        $cmp_setting->created_at = date('Y-m-d H:i:s');
                        $cmp_setting->save();
                    }
                }
            }
            CompanyHelper::changeIntigrationStatus($company_id, 'sendgrid_setup');
            return response()->success('Api key has been update successfully');
        } else {
            return response()->json(array('status' => '500', 'message' => 'Something went wrong, Please refresh page and try again'), 500);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getSmtpSettings(Request $request)
    {
        $output_array = array();
        $user = Auth::user();
        $company_id = $user->company_id;
        $smtp_data = CompanySetting::select('name', 'value')->whereIn('name', ['sendgrid_api_key', 'em_from_name', 'em_from_email'])->where('company_id', $company_id)->get();
        if ($smtp_data->count() > 0) {
            $smtp_data = $smtp_data->toArray();
            if ($smtp_data) {
                foreach ($smtp_data as $item) {
                    $output_array[$item['name']] = $item['value'];
                }
                return response()->success($output_array);
            }
        }
        $output_array = array();
        return response()->success($output_array);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function postNewTwilioNumber()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $input = Input::get();
        $area_code = '';
        $company_name = '';
        $company_info = CompanyHelper::getCompanyDetais($company_id);
        if ($company_info != false) {
            $company_name = $company_info['name'];
        }

        if (isset($input['area_code']) && empty(isset($input['area_code'])) == false) {
            $area_code = $input['area_code'];
            if (strlen($area_code) != 3) {
                return response()->error('Area Code min 3 char required');
            }
        } else {
            return response()->error('Area Code is required');
        }
        if (isset($input['company_name']) && empty(isset($input['company_name'])) == false) {
            $company_name = $input['company_name'];
        }
        $number = TwillioHelper::createSubAccountGetPhone($area_code, $company_name, $company_id);
        CompanyHelper::changeIntigrationStatus($company_id, 'twilio_setup');
        return response()->success(compact('number'));
    }

    public function postTwillioForwarding()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $input = Input::get();
        $recording_status = 0;
        $company_info = CompanyHelper::getCompanyDetais($company_id);
        $forward_to = $company_info['phone'];
        if (isset($input['forwarding_to']) && empty($input['forwarding_to']) === false) {
            $forward_to = $input['forwarding_to'];
        }
        if (isset($input['recording_status']) && empty($input['recording_status']) === false) {
            $recording_status = $input['recording_status'];
        }
        TwillioHelper::twilioforwaringStatusChange($company_id, $forward_to, $recording_status);
        return response()->success(['status' => 'success']);
    }

    public function getCompanyResponseTwiml($api_key)
    {
        $companyID = CompanyHelper::getCompanyIdByApi($api_key);
        if ($companyID) {
            $twilio = TwillioHelper::getTwimlCallHandle($companyID);
            return response($twilio, 200)
                ->header('Content-Type', 'text/xml');
        }
        return response()->error(['message' => 'wrong API key/ company not exists']);
    }

    public function getCompanyConfigurations()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $configs = CompanyHelper::getcompnayConfigStatus($company_id);
        if ($configs['google_analytics_setup'] == 1) {
            $configs['google_profile_id'] = CompanySettingsHelper::getSetting($company_id, 'analytics_profile_id');
        }
        if ($configs['twilio_setup'] == 1) {
            $configs['twillio_forwaring_to'] = CompanySettingsHelper::getSetting($company_id, 'twillio_forwaring_to');
            $configs['twilio_number'] = CompanySettingsHelper::getSetting($company_id, 'twilio_number');
        }
        return response()->success(compact('configs'));
    }

    public function getSkipIntegration()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        CompanyHelper::changeIntigrationStatus($company_id, 'skip_integration', 1);
        return response()->success(['status' => 'success']);
    }

    public function getRemoveIntegration($intigration = null)
    {
        $company_id = 0;
        if (Auth::user()) {
            $user = Auth::user();
            $company_id = $user->company_id;
        }
        if ($intigration == null) {
            return response()->error(['please provide intigration key']);
        }
        $keys = intigrationsKeys();
        if (isset($keys[$intigration])) {
            $interal_keys = $keys[$intigration];
            foreach ($interal_keys as $inti_key) {
                CompanySettingsHelper::deleteSetting($company_id, $inti_key);
            }
            return response()->success(['status' => 'success']);
        }
        return response()->error(['please provide valid intigration key']);
    }

    public function getTwilioNumber()
    {
        if (Auth::user()) {
            $user = Auth::user();
            $company_id = $user->company_id;
            $twilio_number = CompanySettingsHelper::getSetting($company_id, 'twilio_number');
            if ($twilio_number !== false) {
                return response()->success(compact('twilio_number'));
            }
            return response()->error(['Twillio number is not setup Yet.']);
        }
    }
}
