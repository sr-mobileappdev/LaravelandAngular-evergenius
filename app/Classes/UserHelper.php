<?php

namespace App\Classes;

use App\Company;
use App\User;
use App\UserCompanies;
use App\LoginActivities;
use Curl;
use Bican\Roles\Models\Role as Role;
use App\PasswordReset;
use Url;
use Mail;
use App\UserPermission;
use DB;
use \App\Classes\CompanyHelper;
use DateTime;

class UserHelper
{
    public static function getUserDetails($user_id)
    {
        $record = User::where('id', $user_id)->first();
        //dd($record);
        if ($record != null) {
            $record = $record->toArray();
            return $record;
        } else {
            return false;
        }
    }

    /* Push Doctor to Website api */
    public static function createDoctorFrontWebsite($company_details, $user_data)
    {
        if (isset($user_data['bio'])) {
            $user_data['details'] = $user_data['bio'];
            unset($user_data['bio']);
        }

        $web_api_url = $company_details['api_url'];
        $web_access_token = $company_details['api_access_token'];

        if ($web_api_url && $web_access_token) {
            $web_api_url = $web_api_url . "/doctors/create";
            $user_data = $user_data->toArray();
            $user_data['profile_id'] = $user_data['id'];
            $user_data['mobile'] = $user_data['phone'];
            if (isset($user_data['avatar'])) {
                $user_data['featured_image_src'] =  $user_data['avatar'];
                unset($user_data['avatar']);
            }
            if (isset($user_data['specialities'])) {
                $specialities = [];
                foreach ($user_data['specialities'] as $spcl) {
                    $specialities[] = str_replace("-", " ", $spcl['text']);
                }
                $user_data['specialities'] = implode(", ", $specialities);
            }

            unset($user_data['id']);
            unset($user_data['company_id']);
            unset($user_data['updated_at']);
            unset($user_data['created_at']);
            unset($user_data['email_verified']);
            unset($user_data['phone']);
            $response = Curl::to($web_api_url)
                ->withHeader('access_token:' . $web_access_token)
                ->withData($user_data)
                ->asJson()
                ->post();

            if (isset($response) && $response->error == false) {
                return true;
            } else {
                return isset($response) ? $response->message : "User not added to front website";
            }
        }
        return false;
    }

    /* Update Doctor to Website api */
    public static function updateDoctorFrontWebsite($company_details, $user_data)
    {
        if (isset($user_data['bio'])) {
            $user_data['details'] = $user_data['bio'];
            unset($user_data['bio']);
        }

        $web_api_url = $company_details['api_url'];
        $web_access_token = $company_details['api_access_token'];

        if ($web_api_url && $web_access_token) {
            $web_api_url = $web_api_url . "/doctors/update_doctor";
            //$user_data = $user_data->toArray();
            $user_data['profile_id'] = $user_data['id'];
            $user_data['mobile'] = $user_data['phone'];
            $user_data['featured_image_src'] = '';

            if (isset($user_data['specialities'])) {
                $specialities = [];
                foreach ($user_data['specialities'] as $spcl) {
                    $specialities[] = str_replace("-", " ", $spcl['text']);
                }
                $user_data['specialities'] = implode(", ", $specialities);
            } else {
                $user_data['specialities'] = '';
            }

            unset($user_data['id']);
            unset($user_data['company_id']);
            unset($user_data['updated_at']);
            unset($user_data['created_at']);
            unset($user_data['email_verified']);
            unset($user_data['phone']);
            if (isset($user_data['avatar'])) {
                $user_data['featured_image_src'] =  $user_data['avatar'];
                unset($user_data['avatar']);
            }
            if (!empty($web_access_token)) {
                $response = Curl::to($web_api_url)
                    ->withHeader('access_token:' . $web_access_token)
                    ->withData($user_data)
                    ->asJson()
                    ->put();
                if (isset($response) && $response->error == false) {
                    return true;
                } else {
                    return isset($response) ? $response->message : "User not updated to front website";
                }
            }
        } else {
            return true;
        }
        //return false;
    }

    public static function deleteDoctorFrontWebsite($company_details, $user_id)
    {
        $web_api_url = $company_details['api_url'];
        $web_access_token = $company_details['api_access_token'];

        if ($web_api_url && $web_access_token) {
            $web_api_url = $web_api_url . "/doctors/delete_doctor";
            $user_data['profile_id'] = $user_id;
            $response = Curl::to($web_api_url)
                ->withHeader('access_token:' . $web_access_token)
                ->withData($user_data)
                ->delete();
            $response = json_decode($response);
            if ($response->error == false) {
                return true;
            } else {
                return $response->message;
            }
        }
        return false;
    }

    public static function getLeadAssignes($company_id)
    {
        $where = [];
        /*if(Auth::user()){
        $user = Auth::user();
        $user_role = $user->roles->toArray()[0]['slug'];
        if($user_role!='admin.user'){
        $assinee = $user->id;
        $d_s = array('id','=',$assinee);
        array_push($where,$d_s);
        }
        }*/
        $all_users = User::select('id', 'name', 'email')
            ->where('company_id', $company_id)
            ->whereHas(
                'roles',
                function ($q) {
                    $q->where('role_id', '!=', 6);
                }
            )
            ->where($where)
            ->orderBy('name', 'asc')->get();
        if (count($all_users) > 0) {
            return $all_users->toArray();
        }
        return [];
    }

    public static function findUsersByEmail($search_name, $company_id = '')
    {
        $term = User::select('id', 'email', 'name')
            ->where(
                function ($query) use ($search_name) {
                    $query->where('email', 'like', '%' . $search_name . '%')
                        ->orWhere('name', 'like', '%' . $search_name . '%');
                }
            )
            ->where('company_id', '=', $company_id)
            ->where('status', '!=', '0')
            ->get()->toArray();
        return $term;
    }

    public static function findUsersByNumber($search_name, $company_id = '')
    {
        $term = User::select('id', 'phone', 'name')
            ->where(
                function ($query) use ($search_name) {
                    $query->where('phone', 'like', '%' . $search_name . '%')
                        ->orWhere('name', 'like', '%' . $search_name . '%');
                }
            )
            ->where('company_id', '=', $company_id)
            ->where('status', '!=', '0')
            ->get()->toArray();
        return $term;
    }

    public static function getUserDetailsAuth($user_id)
    {
        $user = User::find($user_id);
        $user['role'] = $user
            ->roles()
            ->select(['slug', 'roles.id', 'roles.name'])
            ->get();

        return $user;
    }

    public static function RoleUsersCountCompany($company_id, $role_id)
    {
        $countUserRole = User::select('users.id')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->where('users.company_id', $company_id)
            ->where('role_user.role_id', $role_id)
            ->count();
        return $countUserRole;
    }

    /**Send Notification Mail to User**/

    public static function SendUserNotification($input, $message, $subject, $company_id)
    {
        $app_from_email = app_from_email();
        $companyInfo = Company::find($company_id)->toArray();
        if ($message != false && $subject != false) {
            $data['company_information'] = $companyInfo;
            $message = nl2br($message);
            if (isset($input['list_name'])) {
                $message = str_replace('{$subscription_list}', $input['list_name'], $message);
            }
            if (isset($input['first_name'])) {
                $message = str_replace('{$first_name}', $input['first_name'], $message);
            }
            if (isset($input['last_name'])) {
                $message = str_replace('{$last_name}', $input['last_name'], $message);
            }
            if (isset($input['email'])) {
                $message = str_replace('{$email}', $input['email'], $message);
            }
            if (isset($input['insurance_provider'])) {
                $message = str_replace('{$insurance_provider}', $input['insurance_provider'], $message);
            }
            if (isset($input['duration'])) {
                $message = str_replace('{$duration}', $duration, $message);
            }
            if (isset($input['notes'])) {
                $message = str_replace('{$notes}', $input['notes'], $message);
            }
            if (isset($data['company_information']['name'])) {
                $message = str_replace('{$company_name}', $data['company_information']['name'], $message);
            }
            if (isset($data['company_information']['name'])) {
                $message = str_replace('{$client_name}', $data['company_information']['name'], $message);
            }
            if (isset($data['company_information']['address'])) {
                $message = str_replace('{$company_address}', $data['company_information']['address'], $message);
            }
            if (isset($provider_name)) {
                $message = str_replace('{$provider}', $provider_name, $message);
            }
            if (isset($input['start_datetime'])) {
                $message = str_replace('{$date}', date('F dS, Y', strtotime($input['start_datetime'])), $message);
            }
            if (isset($input['start_datetime'])) {
                $message = str_replace('{$time}', date('h:i A', strtotime($input['start_datetime'])), $message);
            }
            if (isset($input_phone_number)) {
                $message = str_replace('{$phone}', $input_phone_number, $message);
            }

            $data['content_data'] = $message;

            $bcc_email = getenv('BCC_EMAIL');

            \Mail::send(
                'emails.user_booking_notification',
                compact('input', 'companyInfo', 'data'),
                function ($mail) use ($input, $app_from_email, $subject, $bcc_email) {
                    $mail->to($input['email'])
                        ->from($app_from_email)
                        ->subject($subject);

                    /********** Bcc Email ********/
                    if ($bcc_email != false) {
                        $mail->bcc($bcc_email, "EverGenius");
                    }
                }
            );
        }
    }


    /**
     *
     * Helper function for create suepradmin user
     *
     * @param $object
     * @return bool
     */
    public static function createSuperAdminUser($user)
    {
        $newUser = new User();
        $newUser->name = $user->name;
        $newUser->email = $user->email;
        $newUser->phone_country_code = $user->phone_country_code;
        $newUser->phone = format_phone_number($user->phone);
        if (isset($user->website_url)) {
            $newUser->website_url = $user->website_url;
        }
        if (isset($user->address)) {
            $newUser->address = $user->address;
        }
        if (isset($user->city)) {
            $newUser->city = $user->city;
        }
        if (isset($user->state)) {
            $newUser->state = $user->state;
        }
        if (isset($user->zip)) {
            $newUser->zip = $user->zip;
        }
        if (isset($user->country)) {
            $newUser->country = $user->country;
        }
        if (isset($user->avatar)) {
            $newUser->avatar = $user->avatar;
        }
        $newUser->status = 1;
        $newUser->email_verified = 1;
        $newUser->save();
        $userId = $newUser->id;
        if ($userId) {
            $newUser->detachAllRoles();
            $role_attach = self::getRoleIdBySlug($user->role);
            if ($role_attach) {
                $newUser->attachRole($role_attach);
            }
            self::setSuperAdmin($userId, $user->role, $user);

            self::createUserNotification($user->email, $user);
            return $userId;
        }
        return false;
    }

    /**
     * @param $slug : slug of role id
     * @return bool/role ID
     */
    public static function getRoleIdBySlug($slug)
    {
        $role_info = Role::select('id')->where('slug', $slug)->first();
        if (count($role_info) > 0) {
            return $role_info->id;
        }
        return false;
    }

    public static function getSuperAdminRoles()
    {

        $role_info = Role::select(DB::raw('slug'))
            ->where('type', 'super_admin_user')
            ->get();
        if (count($role_info) > 0) {
            $sulgs = [];
            foreach ($role_info->toArray() as $role) {
                $slugs[] = $role['slug'];
            }
            //print_r($slugs); die;
            return $slugs;
        }
        return false;
    }

    public static function setSuperAdmin($userId, $role, $user)
    {
        // Update Licence

        if ($role == 'super.admin.agent') {
            if (isset($user->num_license)) {
                $usr = User::find($userId);
                $usr->num_license = $user->num_license;
                $usr->agency_name = $user->agency_name;
                $usr->save();
            }
        }
        if ($role == 'super.call.center') {
            if (isset($user->call_center_companies)) {
                $call_center_companies = $user->call_center_companies;
                if (is_array($call_center_companies)) {
                    self::attachUserCompanies($userId, $call_center_companies);
                }
            }
            /* if permissions */
            if (isset($user->permissions)) {
                if (is_array($user->permissions)) {
                    self::attachUserPermissions($userId, $user->permissions);
                }
            }
        }
    }

    /**
     * @param $email : email id of user
     * @param $user : all user details
     * @return boolean
     */

    public static function renderUserMessage($message, $user)
    {

        if (strpos($message, '{{password_link}}') !== false) {
            $reset_link = self::getUserResetPasswordLink($user->email);
            $message = str_replace('{{password_link}}', '<a href="' . $reset_link . '" > Set Password </a>', $message);
            $message = str_replace('{{password_link_copy}}', $reset_link, $message);
        }
        $message = str_replace('{{name}}', $user->name, $message);
        $message = str_replace('{{website_url}}', url('/'), $message);
        $message = str_replace('{{username}}', $user->email, $message);
        $message = str_replace('{{agency_name}}', $user->name, $message);
        $bob_s = '<img src="' . url('/') . '/img/bob_sign.png" alt="Bob Signature">';
        $message = str_replace("{{bob_signature}}", $bob_s, $message);
        return $message;
    }

    public static function createUserNotification($email, $user)
    {
        if ($user->role=='super.admin.agent') {
            $message = NotificationHelper::getNotificationMethod(0, 'evergenius_admin_notifications', 'ADD_SUPER_ADMIN_AGENCY_ACCOUNT');
            $subject = NotificationHelper::getNotificationSubject(0, 'evergenius_admin_notifications', 'ADD_SUPER_ADMIN_AGENCY_ACCOUNT');
        } else {
            $message = NotificationHelper::getNotificationMethod(0, 'evergenius_admin_notifications', 'ADD_SUPER_ADMIN_USER');
            $subject = NotificationHelper::getNotificationSubject(0, 'evergenius_admin_notifications', 'ADD_SUPER_ADMIN_USER');
        }
        if ($message && $subject) {
            $message = self::renderUserMessage($message, $user);
            $data['company_information']['logo'] = '/img/mail_image_preview.png';
            $data['content_data'] = $message;
            $admin_email_id = app_from_email();
            NotificationHelper::sendEmailNotificationUser($email, $data, $subject, $admin_email_id);
            \App\Classes\LeadHelper::Lead_email_notification_log(null, 'ADD_SUPER_ADMIN_USER', $message, $subject, null, null, null, 'user', $user->email, 'mail');
        }
    }

    public static function updateUserStatusNotification($status, $userId)
    {
        $user = User::find($userId);
        $email_temp_key = '';
        if ($status == 0) {
            $email_temp_key = 'ADD_SUPER_ADMIN_ACCOUNT_SUSPEND';
        } elseif ($status == 1) {
            $email_temp_key = 'ADD_SUPER_ADMIN_ACCOUNT_REACTIVE';
        }

        $message = NotificationHelper::getNotificationMethod(0, 'evergenius_admin_notifications', $email_temp_key);
        $subject = NotificationHelper::getNotificationSubject(0, 'evergenius_admin_notifications', $email_temp_key);
        if ($message && $subject) {
            $message = self::renderUserMessage($message, $user);
            $data['company_information']['logo'] = '/img/mail_image_preview.png';
            $data['content_data'] = $message;
            $admin_email_id = app_from_email();
            $email = $user->email;
            NotificationHelper::sendEmailNotificationUser($email, $data, $subject, $admin_email_id);
            \App\Classes\LeadHelper::Lead_email_notification_log(null, $email_temp_key, $message, $subject, null, null, $userId, 'user', $user->email, 'mail');
        }
    }

    /* @return mixed
     */
    public static function getUserResetPasswordLink($email)
    {
        PasswordReset::whereEmail($email)->delete();
        $reset = PasswordReset::create([
            'email' => $email,
            'token' => str_random(10),
        ]);
        $token = $reset->token;
        $reset_link = url('/#/reset-password/' . $email . '/' . $token);
        return $reset_link;
    }

    /**
     * @param $user_id
     * @param array $companies
     */
    public static function attachUserCompanies($user_id, array $companies)
    {
        UserCompanies::where('user_id', $user_id)->delete();
        $ins_data = [];
        foreach ($companies as $company) {
            $ins_data[] = array(
                'user_id' => $user_id,
                'company_id' => $company
            );
        }
        UserCompanies::insert($ins_data);
    }

    public static function getuserComapnieCount($userId)
    {
        $count = UserCompanies::where('user_id', $userId)->count();
        return $count;
    }
    public static function getAgencyComapnieCount($userId)
    {
        $count = Company::where('owner_id', $userId)->count();
        return $count;
    }


    public static function attachUserCompany($user_id, $company)
    {
        $ins_data = array(
            'user_id' => $user_id,
            'company_id' => $company
        );
        UserCompanies::insert($ins_data);
        return true;
    }

    public static function dettachUserCompany($user_id, $company)
    {
        UserCompanies::where(['company_id' => $company, 'user_id' => $user_id])->delete();
        return true;
    }

    /**
     * @param int $user_id
     * @param array $permissions
     */
    public static function attachUserPermissions($user_id, array $permissions)
    {
        UserPermission::where('user_id', $user_id)->delete();
        $ins_data = [];
        foreach ($permissions as $permission) {
            $ins_data[] = array(
                'user_id' => $user_id,
                'slug' => $permission
            );
        }
        UserPermission::insert($ins_data);
    }

    /**
     * @param $userId
     * @return mixed
     */
    public static function getSuperAdminUser($userId)
    {
        $user_find = User::select(['id', 'name', 'email', 'phone_country_code', 'phone', 'website_url', 'address', 'city', 'state', 'zip', 'country', 'avatar', 'num_license','agency_name'])
            ->where('id', $userId)
            ->whereHas(
                'roles',
                function ($q) {
                    $q->whereIn('slug', ['super.call.center', 'super.admin.agent', 'admin.super']);
                }
            )
            ->first();
        if (count($user_find) > 0) {
            $role = $user_find
                ->roles()
                ->select(['slug'])
                ->first();
            $user = $user_find->toArray();
            $user['role'] = $role->slug;
            $user = self::getSuperAdminInfo($user);
            return $user;
        }
        return false;
    }

    /**
     * @param $user
     * @return mixed
     */
    public static function getSuperAdminInfo($user)
    {
        if ($user['role'] == 'super.call.center') {
            $user['call_center_companies'] = self::getUserCompanies($user['id']);
            $user['permissions'] = self::getUserPermissions($user['id']);
        }
        return $user;
    }


    /**
     * @param $userId
     * @return array
     */
    public static function getUserCompanies($userId)
    {
        $out = [];
        $company_ids = UserCompanies::select('company_id')->where('user_id', $userId)->get();
        if (count($company_ids) > 0) {
            foreach ($company_ids->toArray() as $comp_id) {
                $out[] = $comp_id['company_id'];
            }
        }
        return $out;
    }

    /**
     * @param $userId
     * @return array
     */
    public static function getUserPermissions($userId)
    {
        $out = [];
        $UserPermission = UserPermission::select('slug')->where('user_id', $userId)->get();
        if (count($UserPermission) > 0) {
            foreach ($UserPermission->toArray() as $user_permision) {
                $out[] = $user_permision['slug'];
            }
        }
        return $out;
    }

    /**
     * @param $userId :  user Id
     * @return bool
     */
    public static function isSuperAdminUserExists($userId)
    {
        $user_find = User::select(['id', 'name', 'email', 'phone_country_code', 'phone', 'website_url', 'address', 'city', 'state', 'zip', 'country', 'avatar', 'num_license'])
            ->where('id', $userId)
            ->whereHas(
                'roles',
                function ($q) {
                    $q->whereIn('slug', ['super.call.center', 'super.admin.agent', 'admin.super']);
                }
            )
            ->first();
        if (count($user_find) > 0) {
            return true;
        }
        return false;
    }

    /**
     * @param $user
     * @param $userId
     * @return bool
     */
    public static function updateSuperAdminUser($user, $userId)
    {
        if (isset($user['call_center_companies'])) {
            $call_center_companies = $user['call_center_companies'];
            unset($user['call_center_companies']);
        }
        if (isset($user['permissions'])) {
            $permissions = $user['permissions'];
            unset($user['permissions']);
        }
        if (isset($user['role'])) {
            $role = $user['role'];
            unset($user['role']);
        }
        /* update user */
        $userInfo = User::find($userId);
        if (isset($user['name'])) {
            $userInfo->name = $user['name'];
        }
        if (isset($user['phone_country_code'])) {
            $userInfo->phone_country_code = $user['phone_country_code'];
        }
        if (isset($user['email'])) {
            $userInfo->email = $user['email'];
        }
        if (isset($user['phone'])) {
            $userInfo->phone = format_phone_number($user['phone']);
        }
        if (isset($user['website_url'])) {
            $userInfo->website_url = $user['website_url'];
        }
        if (isset($user['address'])) {
            $userInfo->address = $user['address'];
        }
        if (isset($user['city'])) {
            $userInfo->city = $user['city'];
        }
        if (isset($user['state'])) {
            $userInfo->state = $user['state'];
        }
        if (isset($user['zip'])) {
            $userInfo->zip = $user['zip'];
        }
        if (isset($user['email'])) {
            $userInfo->email = $user['email'];
        }
        if (isset($user['country'])) {
            $userInfo->country = $user['country'];
        }
        if (isset($user['avatar'])) {
            $userInfo->avatar = $user['avatar'];
        }

        $userInfo->save();
        //$userInfo->update($user);
        /*Set user Role */
        if (isset($role)) {
            $userInfo->detachAllRoles();
            $role_attach = self::getRoleIdBySlug($role);
            if ($role_attach) {
                $userInfo->attachRole($role_attach);
            }
        }
        $user = (object)$user;
        if (isset($permissions)) {
            $user->permissions = $permissions;
        }
        if (isset($call_center_companies)) {
            $user->call_center_companies = $call_center_companies;
        }
        if (isset($role)) {
            self::setSuperAdmin($userId, $role, $user);
        }
        return true;
    }

    /**
     * @param $userEmail
     * @param null $userId
     * @return bool
     */
    public static function isEmailExists($userEmail, $userId = null)
    {
        $user = User::where('email', $userEmail);
        if ($userId != null) {
            $user->where('id', '!=', $userId);
        }
        $userCount = $user->count();
        if ($userCount > 0) {
            return true;
        }
        return false;
    }

    /**
     * @param $userId
     * @param $status
     */
    public static function changeUserStatus($userId, $status)
    {
        User::find($userId)->update(['status' => $status]);
    }

    /**
     * @param $userId
     * @return bool
     */
    public static function DeleteSuperAdminUser($userId)
    {
        User::find($userId)->delete();
        return true;
    }

    /**
     * @param $userId
     * @param $status
     */
    public static function updateAgentCompaniesStatus($userId, $status)
    {
        $userCompanies = self::getUserCompanies($userId);
        foreach ($userCompanies as $companyId) {
            if ($status == 0) {
                CompanyHelper::updateCompanyStatus($companyId, 0);
            } elseif ($status == 1) {
                $agency_status = CompanyHelper::getCompanyAgencyStatus($companyId);
                CompanyHelper::updateCompanyStatus($companyId, $agency_status);
            }
        }
    }

    public static function deleteAgentCompanies($userId)
    {
        $userCompanies = self::getUserCompanies($userId);
        foreach ($userCompanies as $companyId) {
            self::dettachUserCompany($userId, $companyId);
            CompanyHelper::deleteCompany($companyId);
            CompanyHelper::deleteCompanyContacts($companyId);
            CompanyHelper::deleteCompanyAppointments($companyId);
            CompanyHelper::deleteCompanyNotificationMails($companyId);
            CompanyHelper::deleteCompanySettings($companyId);
            CompanyHelper::deleteCompanyCalls($companyId);
            CompanyHelper::deleteCompanySms($companyId);
            CompanyHelper::deleteCompanyUsers($companyId);
        }
    }

    public static function getAgencyUsers()
    {
        $agentusers = User::select(['users.id', 'users.name'])
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->where('role_user.role_id', function ($query) {
                $query->select('id')
                    ->from('roles')
                    ->where('slug', 'super.admin.agent');
            })
            ->get();
        if (count($agentusers) > 0) {
            return $agentusers->toArray();
        }
        return [];
    }
    public static function getSuperadminUsers()
    {
        $agentusers = User::select(['users.id', 'users.name'])
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->whereIn('role_user.role_id', function ($query) {
                $query->select('id')
                    ->from('roles')
                    ->whereIn('slug', ['super.admin.agent', 'super.call.center', 'admin.super']);
            })
            ->get();
        if (count($agentusers) > 0) {
            return $agentusers->toArray();
        }
        return [];
    }

    public static function updateUser($userId, $updateData)
    {
        User::where('id', $userId)->update($updateData);
        return true;
    }
    public static function getUserHdId($user_id)
    {
        $hd_id = User::select('hd_provider_id')->where('id', $user_id)->first();
        if (count($hd_id) > 0) {
            return $hd_id->hd_provider_id;
        }
        return false;
    }
    public static function GetNonHdUsers()
    {
        $users = User::whereNull('hd_provider_id')
            ->whereHas('roles', function ($q) {
                $q->where('role_id', '=', 5);
            })->get();
        if (count($users) > 0) {
            return $users->toArray();
        }
    }

    public static function addLoginActivity($user_id, $role, $email, $device_type, $device_name = null, $ip_address = null, $event = null)
    {
        $login_activity = new LoginActivities();
        $login_activity->user_id = $user_id;
        $login_activity->role = $role;
        $login_activity->email = $email;
        $login_activity->device_type = $device_type;
        $login_activity->device_name = $device_name;
        $login_activity->ip_address= $ip_address;
        $login_activity->event = $event;
        $login_activity->time = new dateTime();
        ;
        $login_activity->save();
        return $login_activity->id;
    }

    public static function superadminDashboardStats($start_date = null, $end_date = null)
    {
        if ($start_date==null || $end_date==null) {
            $start_date = '2016-05-06';
            $end_date = date('Y-m-d', time());
        }
        $arv = DB::select('call superadmin_dashboard_stat(?,?)', array($start_date, $end_date));
        $data =  collect($arv);
        return $data;
    }
}
