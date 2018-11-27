<?php

namespace App\Http\Controllers;

use App\Activity;
use App\Classes\ActivityHelper;
use App\Classes\CompanyHelper;
use App\Classes\HonestdoctorHelper;
use App\Classes\NotificationHelper;
use App\Classes\CompanySettingsHelper;
use App\Classes\UserHelper;
use App\Company;
use App\User;
use Auth;
use Bican\Roles\Models\Permission;
use Bican\Roles\Models\Role;
use Datatables;
use Hash;
use Illuminate\Http\Request;
use Image;
use Input;
use Landlord;
use Mail;
use Validator;

class UserController extends Controller
{
    /**
     * Get user current context.
     *
     * @return JSON
     */
    public function getMe()
    {
        $user = Auth::user();
        if (isset($user)) {
            $company_id = $user->company_id;
            $Company = Company::find($company_id);
            if ($Company) {
                $user->website_url = $Company->site_url;
                return response()->success($user);
            } else {
                return response()->success($user);
            }
        }
    }

    /**
     * Update user current context.
     *
     * @return JSON success message
     */
    public function putMe(Request $request)
    {
        $user = Auth::user();

        $this->validate(
            $request,
            [
                'data.name' => 'required|min:3',
                'data.email' => 'required|email|unique:users,email,' . $user->id,
            ]
        );

        $userForm = app('request')
            ->only(
                'data.current_password',
                'data.new_password',
                'data.new_password_confirmation',
                'data.name',
                'data.email'
            );

        $userForm = $userForm['data'];
        $user->name = $userForm['name'];
        $user->email = $userForm['email'];

        if ($request->has('data.current_password')) {
            Validator::extend(
                'hashmatch',
                function ($attribute, $value, $parameters) {
                    return Hash::check($value, Auth::user()->password);
                }
            );

            $rules = [
                'data.current_password' => 'required|hashmatch:data.current_password',
                'data.new_password' => 'required|min:8|confirmed',
                'data.new_password_confirmation' => 'required|min:8',
            ];

            $payload = app('request')->only('data.current_password', 'data.new_password', 'data.new_password_confirmation');

            $messages = [
                'hashmatch' => 'Invalid Password',
            ];

            $validator = app('validator')->make($payload, $rules, $messages);

            if ($validator->fails()) {
                return response()->error($validator->errors());
            } else {
                $user->password = Hash::make($userForm['new_password']);
            }
        }
        $user->save();
        return response()->success('success');
    }

    /**
     * Get all users.
     *
     * @return JSON
     */
    public function postIndex()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $all_users = User::select('id', 'name', 'email', 'phone', 'phone_country_code', 'city')
            ->where('company_id', $company_id)
            ->whereHas(
                'roles',
                function ($q) {
                    $q->where('role_id', 5);
                }
            )
            ->orderBy('id', 'desc')->get();
        return Datatables::of($all_users)->make(true);
    }

    /**
     * Get user details referenced by id.
     *
     * @param int User ID
     *
     * @return JSON
     */
    public function getShow($id)
    {
        $company_id = 0;
        if (Auth::user()) {
            $user = Auth::user();
            $company_id = $user->company_id;
        }

        $user = User::find($id);
        if ($user->company_id != $company_id) {
            return response()->error('Wrong user Id');
        }

        $user['role'] = $user
            ->roles()
            ->select(['slug', 'roles.id', 'roles.name'])
            ->get();
        $user['specializations'] = HonestdoctorHelper::getTermSpecializations('user', $id);
        $social_urls = [];
        $social_data = CompanyHelper::getSocialUrlsTerm($user->company_id, $id, 'provider');
        if ($social_data) {
            $social_urls = CompanyHelper::bindUrls($social_data);
        }
        $user['social_urls'] = $social_urls;

        return response()->success($user);
    }

    /**
     * Update user data.
     *
     * @return JSON success message
     */
    public function putShow(Request $request)
    {
        $media_Id = null;
        $send_lead = null;

        $input = Input::get();
        $userForm = array_dot(
            app('request')->only(
                'data.name',
                'data.email',
                'data.phone',
                'data.job_title',
                'data.website_url',
                'data.address',
                'data.city',
                'data.state',
                'data.zip',
                'data.country',
                'data.bio',
                'data.education',
                'data.languages_spoken',
                'data.phone_country_code',
                'data.specialities',
                'data.id',
                'date.send_lead',
                'data.specializations',
                'data.gender',
                'data.hd_publish_status',
                'data.avatar',
                'data.social_urls'
            )
        );

        $userId = intval($userForm['data.id']);

        $user = User::find($userId);
        $validations =  [
            'data.id' => 'required|integer',
            'data.name' => 'required|min:3',
        ];

        if ($input['data']['email']!=$user->email) {
            $exists=UserHelper::isEmailExists($input['data']['email']);
            if ($exists) {
                return response()->error('user already exists');
            }
        }

        $this->validate(
            $request,
            $validations
        );

        if (isset($userForm['data.send_lead'])) {
            $send_lead = $userForm['data.send_lead'];
        }

        //  print_r($userForm); die;
        $userData = [
            'name' => $userForm['data.name'],
            'email' => $userForm['data.email'],
            'phone' => $userForm['data.phone'],
            'job_title' => $userForm['data.job_title'],
            'website_url' => $userForm['data.website_url'],
            'phone_country_code' => $userForm['data.phone_country_code'],
            //'address' => $userForm['data.address'],
            'address' => $userForm['data.address'],
            'gender' => $userForm['data.gender'],
            'avatar' => $userForm['data.avatar'],
            'hd_publish_status' => $userForm['data.hd_publish_status'],
            'city' => $userForm['data.city'],
            'state' => $userForm['data.state'],
            'zip' => $userForm['data.zip'],
            'country' => $userForm['data.country'],
            'bio' => $userForm['data.bio'],
            'education' => $userForm['data.education'],
            'languages_spoken' => $userForm['data.languages_spoken'],
            'specialities' => $userForm['data.specialities'],
            'send_lead' => $send_lead
        ];

        if (isset($input['data']['specializations'])) {
            $hd_spcl_ids = HonestdoctorHelper::attachSpecializations($input['data']['specializations'], 'user', $userId);
        }

        if ($user->avatar != $userForm['data.avatar']) {
            $media_Id = HonestdoctorHelper::postMediaHD($userForm['data.avatar']);
        }

        User::where('id', '=', $userId)->update($userData);
        $user->detachAllRoles();

        foreach (Input::get('data.role') as $setRole) {
            $user->attachRole($setRole);
        }

        $userData['id'] = $userId;

        $cuser = Auth::user();
        $company_id = $cuser->company_id;

        $company_details = CompanyHelper::getCompanyDetais($company_id);
        $socials=[];
        //print_r($userForm); die;
        foreach ($userForm as $user_key => $user_fields) {
            if (strpos($user_key, 'social_urls.') !== false) {
                $userForm[str_replace("social_urls.", '', $user_key)] = $user_fields;
                //print_r(str_replace("social_urls.",'',$user_key));
                $socials[str_replace("social_urls.", '', $user_key)] = $user_fields;
                $media = str_replace("data.social_urls.", '', $user_key);
                if (!CompanyHelper::isSocialUrlExists($company_id, $userId, 'provider', $media)) {
                    CompanyHelper::addSocialUrls($company_id, $userId, 'provider', $user_fields, $media);
                } else {
                    CompanyHelper::SocialUrlUpdate($company_id, $userId, 'provider', $media, $user_fields);
                }
            }
        }
        //print_r($userForm); die;


        /* update users in HD */
        $hd_provider_id = UserHelper::getUserHdId($userId);
        if ($hd_provider_id != null && $hd_provider_id != false) {
            $userForm['data.province'] = $userForm['data.state'];
            $twilio_number = CompanySettingsHelper::getSetting($company_id, 'twilio_number');
            if ($twilio_number!==false && empty($twilio_number)===false) {
                $userForm['data.phone'] = $twilio_number;
            } else {
                $userForm['data.phone'] = $company_details['phone'];
            }
            $update_HD_provider = HonestdoctorHelper::updateHdProvider($userForm, $hd_spcl_ids, $company_id, $hd_provider_id, $userId, $media_Id);
        } else {
            $uf = [];
            foreach ($userForm as $key => $value) {
                $key = str_replace("data.", "", $key);
                $uf[$key] = $value;
            }
            HonestdoctorHelper::createProvider($uf, $hd_spcl_ids, $userId, $company_id, $uf['website_url'], $media_Id);
        }
        if (isset($input['data']['specializations'])) {
            $userData['specialities'] = $input['data']['specializations'];
        }

        $doctor_update_front_website = UserHelper::updateDoctorFrontWebsite($company_details, $userData);

        if ($doctor_update_front_website == true) {
            return response()->success('success');
        } else {
            return response()->error($doctor_update_front_website);
        }

        return response()->success('success');
    }

    /**
     * Delete User Data.
     *
     * @return JSON success message
     */
    public function deleteUser($id)
    {
        $user = User::find($id);
        $user_role = $user->roles()->select(['slug'])->first();

        /* Check is user Admin*/
        if (count($user_role) > 0 && $user_role->slug == 'admin.user') {
            $countCompanyAdmins = UserHelper::RoleUsersCountCompany($user->company_id, $user_role->pivot['role_id']);
            /* restrict user to delete last admin */
            if ($countCompanyAdmins < 2) {
                return response()->error('Last admin can not be deleted.');
            }
        }

        $user->delete();
        $c_usr = Auth::user();
        $company_id = $c_usr->company_id;
        $company_doctors = CompanyHelper::getAllDoctors($company_id);
        $company_details = CompanyHelper::getCompanyDetais($company_id);
        $doctor_delete_front_website = UserHelper::deleteDoctorFrontWebsite($company_details, $id);

        if ($doctor_delete_front_website == true) {
            return response()->success(compact('company_doctors'));
        } else {
            return response()->success(compact('company_doctors'));
        }
        return response()->success(compact('company_doctors'));
    }

    /**
     * Get all user roles.
     *
     * @return JSON
     */
    public function getRoles()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $roles = Role::whereNotIn('slug', ['admin.super', 'doctor'])
            ->where('type', 'company_users')
            ->whereIn('company_id', [0, $company_id])
            ->get();
        return response()->success(compact('roles'));
    }

    /**
     * Get role details referenced by id.
     *
     * @param int Role ID
     *
     * @return JSON
     */
    public function getRolesShow($id)
    {
        $company_id = 0;
        if (Auth::user()) {
            $user = Auth::user();
            $company_id = $user->company_id;
        }
        $role = Role::find($id);
        if ($role->company_id != $company_id) {
            return response()->error('Wrong Role Id');
        }

        $role['permissions'] = $role
            ->permissions()
            ->select(['permissions.name', 'permissions.id'])
            ->get();

        return response()->success($role);
    }

    /**
     * Update role data and assign permission.
     *
     * @return JSON success message
     */
    public function putRolesShow()
    {
        $roleForm = Input::get('data');
        $roleData = [
            'name' => $roleForm['name'],
            'slug' => $roleForm['slug'],
            'description' => $roleForm['description'],
        ];

        $roleForm['slug'] = str_slug($roleForm['slug'], '.');
        Role::where('id', '=', intval($roleForm['id']))->update($roleData);
        $role = Role::find($roleForm['id']);
        $role->detachAllPermissions();
        foreach (Input::get('data.permissions') as $setPermission) {
            $role->attachPermission($setPermission);
        }

        return response()->success('success');
    }

    /**
     * Create new user role.
     *
     * @return JSON
     */
    public function postRoles()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $role = new Role();
        $role->name = Input::get('role');
        $role->company_id = $company_id;
        $role->slug = str_slug(Input::get('slug'), '.');
        $role->description = Input::get('description');
        $role->type = 'company_users';
        $role->save();
        if (Input::get('permissions') && count(Input::get('permissions')) > 0) {
            foreach (Input::get('permissions') as $setPermission) {
                $role->attachPermission($setPermission);
            }
        }
        return response()->success(compact('role'));
    }

    /**
     * Delete user role referenced by id.
     *
     * @param int Role ID
     *
     * @return JSON
     */
    public function deleteRoles($id)
    {
        Role::destroy($id);

        return response()->success('success');
    }

    /**
     * Get all system permissions.
     *
     * @return JSON
     */
    public function getPermissions()
    {
        $permissions = Permission::all();
        return response()->success(compact('permissions'));
    }

    /**
     * Create new system permission.
     *
     * @return JSON
     */
    public function postPermissions()
    {
        $permission = Permission::create(
            [
                'name' => Input::get('name'),
                'slug' => str_slug(Input::get('slug'), '.'),
                'description' => Input::get('description'),
            ]
        );

        return response()->success(compact('permission'));
    }

    /**
     * Get system permission referenced by id.
     *
     * @param int Permission ID
     *
     * @return JSON
     */
    public function getPermissionsShow($id)
    {
        $permission = Permission::find($id);

        return response()->success($permission);
    }

    /**
     * Update system permission.
     *
     * @return JSON
     */
    public function putPermissionsShow()
    {
        $permissionForm = Input::get('data');
        $permissionForm['slug'] = str_slug($permissionForm['slug'], '.');
        $affectedRows = Permission::where('id', '=', intval($permissionForm['id']))->update($permissionForm);

        return response()->success($permissionForm);
    }

    /**
     * Delete system permission referenced by id.
     *
     * @param int Permission ID
     *
     * @return JSON
     */
    public function deletePermissions($id)
    {
        Permission::destroy($id);

        return response()->success('success');
    }

    public function postDoctors()
    {
        $hd_spcl_ids = [];
        /* Current User Company Id*/
        $user = Auth::user();
        $c_user_id = $user->id;
        $company_id = $user->company_id;
        $company_information = CompanyHelper::getCompanyDetais($company_id);

        $users_data = Input::get();
        $social_urls = [];
        if (isset($users_data['social_urls'])) {
            $social_urls = $users_data['social_urls'];
            unset($users_data['social_urls']);
        }

        $user_ex_data = User::where('email', strtolower($users_data['email']))->where('company_id', $company_id)->first();
        $website_url = null;
        $city = null;
        $state = null;
        $zip = null;
        $country = null;
        $media_Id = null;

        /* Store New User */
        if (count($user_ex_data) == 0) {
            if (Input::get('website_url')) {
                $website_url = trim(Input::get('website_url'));
            }

            if (Input::get('city')) {
                $city = trim(Input::get('city'));
            }
            if (Input::get('state')) {
                $state = trim(Input::get('state'));
            }
            if (Input::get('zip')) {
                $zip = trim(Input::get('zip'));
            }
            if (Input::get('country')) {
                $country = trim(Input::get('country'));
            }
            $user = new User();
            $user->company_id = $company_id;
            $user->name = trim($users_data['name']);
            $user->email = trim(strtolower($users_data['email']));
            $user->job_title = trim($users_data['job_title']);
            $user->phone = trim($users_data['phone']);
            $user->website_url = trim($website_url);

            /* Add Country code */
            if (Input::get('phone_country_code')) {
                $user->phone_country_code = $users_data['phone_country_code'];
            }

            $user->city = $city;
            $user->state = $state;
            $user->zip = $zip;
            $user->country = $country;
            $user->hd_publish_status = $users_data['hd_publish_status'];
            $user->password = bcrypt($users_data['password']);
            $user->email_verified = 1;

            /* Additiona Settings */
            if (isset($users_data['bio'])) {
                $user->bio = $users_data['bio'];
            }
            if (isset($users_data['education'])) {
                $user->education = $users_data['education'];
            }
            if (isset($users_data['languages_spoken'])) {
                $user->languages_spoken = $users_data['languages_spoken'];
            }

            if (isset($users_data['address'])) {
                $user->address = $users_data['address'];
            }
            if (isset($users_data['gender'])) {
                $user->gender = $users_data['gender'];
            }
            if (isset($users_data['profile_pic'])) {
                $user->avatar = $users_data['profile_pic'];
                /* Post Image to HD */
                HonestdoctorHelper::postMediaHD($users_data['profile_pic']);
            }
            $user->save();
            $user_id = $user->id;

            /*Save Provider Setup Status*/
            CompanyHelper::changeIntigrationStatus($company_id, 'providers_setup');

            if (isset($users_data['specialities'])) {
                $hd_spcl_ids = HonestdoctorHelper::attachSpecializations($users_data['specialities'], 'user', $user_id);
                $user['specialities'] = $users_data['specialities'];
            }

            if (count($social_urls)>0) {
                foreach ($social_urls as $media => $url) {
                    $users_data[$media] = $url;
                    if (!CompanyHelper::isSocialUrlExists($company_id, $user_id, 'provider', $media)) {
                        CompanyHelper::addSocialUrls($company_id, $user_id, 'provider', $url, $media);
                    } else {
                        CompanyHelper::SocialUrlUpdate($company_id, $user_id, 'provider', $media, $url);
                    }
                }
            }
            $data['company_information'] = CompanyHelper::getCompanyDetais($company_id);
            if (isset($users_data['hospital_affiliations'])) {
                $users_data['hospital_affiliations'] = $users_data['hospital_affiliations'];
            } else {
                $users_data['hospital_affiliations'] = '';
            }
            if (isset($users_data['education'])) {
                $users_data['education'] = $users_data['education'];
            } else {
                $users_data['education'] = '';
            }
            /* Create Prodider On HD */
            if (isset($users_data['profile_pic'])) {
                /* Post Image to HD */
                $media_Id = HonestdoctorHelper::postMediaHD($users_data['profile_pic']);
            }
            $twilio_number = CompanySettingsHelper::getSetting($company_id, 'twilio_number');
            if ($twilio_number!==false && empty($twilio_number)===false) {
                $users_data['phone'] = $twilio_number;
            } else {
                $users_data['phone'] = $company_information['phone'];
            }
            //print_r($users_data); die;
            HonestdoctorHelper::createProvider($users_data, $hd_spcl_ids, $user_id, $company_id, $website_url, $media_Id);
            /* Create Prodider On HD */

            /* Insert in Role Table */
            $user->attachRole(5);

            $data = array();
            $data['name'] = $users_data['name'];
            $data['username'] = trim(strtolower($users_data['email']));
            $data['password'] = $users_data['password'];
            $data['website_url'] = url('/');
            $data['company_information'] = CompanyHelper::getCompanyDetais($company_id);
            $admin_email_id = app_from_email();

            $message = NotificationHelper::getNotificationMethod($company_id, 'general_settings', 'ADD_DOCTOR');
            $subject = NotificationHelper::getNotificationSubject($company_id, 'general_settings', 'ADD_DOCTOR');

            if ($message != false && $subject != false) {
                $message = nl2br($message);
                $message = str_replace('{{$name}}', $data['name'], $message);
                $message = str_replace('{{$website_url}}', $data['website_url'], $message);
                $message = str_replace('{{$username}}', $data['username'], $message);
                $message = str_replace('{{$password}}', $data['password'], $message);


                $message = str_replace('{$name}', $data['name'], $message);
                $message = str_replace('{$website_url}', $data['website_url'], $message);
                $message = str_replace('{$username}', $data['username'], $message);
                $message = str_replace('{$password}', $data['password'], $message);

                $data['content_data'] = $message;

                $bcc_email = getenv('BCC_EMAIL');

                Mail::send(
                    'emails.add_new_doctor',
                    compact('data'),
                    function ($mail) use ($admin_email_id, $users_data, $subject, $bcc_email) {
                        $mail->to(trim(strtolower($users_data['email'])))
                            ->from($admin_email_id)
                            ->subject($subject);
                        /********** Bcc Email ********/
                        if ($bcc_email != false) {
                            $mail->bcc($bcc_email, "EverGenius");
                        }
                    }
                );
                CompanyHelper::recordNotificationLog($user_id, 'user', 'ADD_DOCTOR', 'mail', $company_id, $users_data['email'], $message, $subject, null, null);
            }
            $company_doctors = CompanyHelper::getAllDoctors($company_id);

            /* *********************** Add Activity *********************** */
            ActivityHelper::createActivity($company_id, 'NEW_DOCTOR_ADDED', 'user', $user_id, null, $user_id, $c_user_id);
            /* *********************** / Add Activity *********************** */

            /* Add Doctor On front website */
            $docot_add_front_website = UserHelper::createDoctorFrontWebsite($data['company_information'], $user);
            if ($docot_add_front_website == true) {
                return response()->success(compact('company_doctors'));
            } else {
                return response()->success(compact('company_doctors'));
            }
        } else {
            return response()->error('User already exist.');
        }
    }

    public function getNotificationLastSeen()
    {
        $user_id = Auth::user()->id;
        $user = User::find($user_id);
        $user->notification_last_seen = date('Y-m-d H:i:s');
        $status = $user->save();

        if ($status) {
            return response()->success('Notifications have been seen');
        } else {
            return response()->error('Notifications have not been seen');
        }
    }

    /**User Notification list**/
    public function getUserNotifications()
    {
        $user = Auth::user();
        $user_id = $user->id;
        $user_last_seen = $user->notification_last_seen;
        $user_role = $user->roles()->select(['slug'])->first()->toArray();
        if (count($user_role) < 1) {
            return [];
        }
        $activites = "";
        $out = array();
        //Fetch data for doctor
        if ($user_role['slug'] == 'doctor') {
            $activites = Activity::where('user_id', $user_id);
        } elseif ($user_role['slug'] == 'admin.user') {
            $activites = Activity::select('*')->where('company_id', $user->company_id);
        }
        if ($activites) {
            if ($user_last_seen) {
                $activites->where('created_at', '>=', $user_last_seen);
            }
            $activites->orderBy('id', 'desc');
            $activites = $activites->get();
            if (count($activites) < 1) {
                return [];
            }
            $activites = $activites->toArray();
            $out = ActivityHelper::translateActivites($activites);
        }
        return $out;
    }
    public function postUpdatePlayerId()
    {
        $data = Input::get();
        $user = Auth::user();
        $user_id = $user->id;
        if ($data) {
            $status = User::where('id', $user_id)->update(['player_id' => $data['player_id']]);
            return response()->success('User added for the notification');
        } else {
            return response()->success('User information updated');
        }
    }

    public function getCompanyUsers()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $users = User::select('users.id', 'users.name', 'users.phone_country_code', 'users.email', 'users.phone', 'roles.name as role_name', 'roles.slug')
            ->leftJoin('role_user', 'users.id', '=', 'role_user.user_id')
            ->leftJoin('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('users.company_id', $company_id)
            ->orderBy('users.id', 'desc')
            ->where('roles.slug', '!=', 'doctor')
            ->get();

        if (count($users) > 0) {
            return response()->success(compact('users'));
        }
        return response()->error('user not found');
    }

    public function getShowCompanyUser($user_id)
    {
        $company_id = 0;
        if (Auth::user()) {
            $user_c = Auth::user();
            $company_id = $user_c->company_id;
        }
        $user = User::find($user_id);
        $user->is_last_admin = false;
        $user_role = $user->roles()->select(['slug'])->first();
        if (count($user_role) > 0 && $user_role->slug == 'admin.user') {
            $countCompanyAdmins = UserHelper::RoleUsersCountCompany($user->company_id, $user_role->pivot['role_id']);
            /* restrict user to delete last admin */
            if ($countCompanyAdmins < 2) {
                $user->is_last_admin = true;
            }
        }
        $user['role'] = $user
            ->roles()
            ->select(['slug', 'roles.id', 'roles.name'])
            ->get();
        if ($user->company_id != $company_id) {
            return response()->error('user not found');
        }
        return response()->success($user);
    }

    public function postShowCompanyUser($user_id)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $user = User::find($user_id);
        $avatar = null;
        $user_info = Input::get();
        if (isset($user_info['avatar']) && $user_info['avatar'] != '' && $user_info['avatar'] != null) {
            $avatar = $user_info['avatar'];
        }
        $user_ex_data = User::where('id', $user_id)->where('company_id', $company_id)->first();
        if (count($user_ex_data) > 0) {
            $update_data = array(
                'name' => trim($user_info['name']),
                'email' => trim(strtolower($user_info['email'])),
                'phone' => trim(strtolower($user_info['phone'])),
                'phone_country_code' => trim($user_info['phone_country_code']),
                'send_lead' => ((isset($user_info['send_lead']) && $user_info['send_lead'] == true) ? 1 : 0),
                'avatar' => trim($avatar),
            );
            if (isset($user_info['password'])) {
                $update_data['password'] = bcrypt($user_info['password']);
            }

            $user_up = User::find($user_id);
            $user_up->detachAllRoles();
            User::where('id', $user_id)->update($update_data);
            $user_up->attachRole($user_info['role_id']);
            return response()->success(['status' => 'success']);
        }
        return response()->error('user not found');
    }

    public function postCompanyUser()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $users_data = Input::get();
        $users_data['website_url'] = url('/');
        $user_ex_data = User::where('email', $users_data['email'])->first();
        if (count($user_ex_data) == 0) {
            $user = new User();
            $user->company_id = $company_id;
            $user->name = trim($users_data['name']);
            $user->email = trim(strtolower($users_data['email']));
            $user->phone = trim($users_data['phone']);
            $user->password = bcrypt($users_data['password']);

            if (isset($users_data['phone_country_code'])) {
                $user->phone_country_code = $users_data['phone_country_code'];
            }
            $user->send_lead = (isset($users_data['send_lead']) && $users_data['send_lead'] == true) ? 1 : 0;
            /* update profile picture */
            if (isset($users_data['avatar'])) {
                $user->avatar = trim($users_data['avatar']);
            }
            $user->email_verified = 1;
            $user->save();
            $user_id = $user->id;
            $user->detachAllRoles();
            $user->attachRole($users_data['role_id']);
            CompanyHelper::changeIntigrationStatus($company_id, 'user_setup');
            $message = NotificationHelper::getNotificationMethod($company_id, 'general_settings', 'ADD_STAFF');
            $subject = NotificationHelper::getNotificationSubject($company_id, 'general_settings', 'ADD_STAFF');
            $data = $users_data;
            if ($message != false && $subject != false) {
                $message = nl2br($message);
                $message = str_replace('{{$name}}', $data['name'], $message);
                if (isset($data['website_url'])) {
                    $message = str_replace('{{$website_url}}', $data['website_url'], $message);
                    $message = str_replace('{$website_url}', $data['website_url'], $message);
                }
                // $message = str_replace('{{$username}}', $data['email'], $message);
                //$message = str_replace('{{$password}}', $data['password'], $message);

                // print_r( $message); die;
                /* if name existes*/
                if (isset($users_data['name'])) {
                    $message = str_replace('{{$name}}', $users_data['name'], $message);
                    $message = str_replace('{$name}', $users_data['name'], $message);
                } else {
                    $message = str_replace('{{$name}}', '', $message);
                    $message = str_replace('{$name}', "", $message);
                }
                /* if website url*/
                if (isset($users_data['website_url'])) {
                    $message = str_replace('{{$website_url}}', $users_data['website_url'], $message);
                    $message = str_replace('{$website_url}', $users_data['website_url'], $message);
                } else {
                    $message = str_replace('{{$website_url}}', '', $message);
                    $message = str_replace('{$website_url}', '', $message);
                }
                /*if username*/
                if (isset($users_data['email'])) {
                    $message = str_replace('{{$username}}', $users_data['email'], $message);
                    $message = str_replace('{$username}', $users_data['email'], $message);
                } else {
                    $message = str_replace('{{$username}}', '', $message);
                    $message = str_replace('{$username}', '', $message);
                }
                /*If Password */
                if (isset($users_data['password'])) {
                    $message = str_replace('{{$password}}', $users_data['password'], $message);
                    $message = str_replace('{$password}', $users_data['password'], $message);
                } else {
                    $message = str_replace('{{$password}}', '', $message);
                    $message = str_replace('{$password}', '', $message);
                }

                $data['content_data'] = $message;
                $data['data'] = $message;
                $bcc_email = getenv('BCC_EMAIL');
                $admin_email_id = getenv('MAIL_FROM');
                $data['company_information'] = CompanyHelper::getCompanyDetais($company_id);
                Mail::send(
                    'emails.add_new_doctor',
                    compact('data'),
                    function ($mail) use ($admin_email_id, $users_data, $subject, $bcc_email) {
                        $mail->to(trim(strtolower($users_data['email'])))
                            ->from($admin_email_id)
                            ->subject($subject);
                        if ($bcc_email != false) {
                            $mail->bcc($bcc_email, "EverGenius");
                        }
                    }
                );
                CompanyHelper::recordNotificationLog($user_id, 'user', 'ADD_STAFF', 'mail', $company_id, $users_data['email'], $message, $subject, null, null);
            }
            return response()->success(['status' => 'success']);
        } else {
            return response()->error('User already exist.');
        }
    }

    public function getFindTags()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $search = Input::get('e');
        $out = array();
        if ($search) {
            $sources = UserHelper::findUsersByEmail($search, $company_id);
            $out = $sources;
        }
        $search = Input::get('n');
        if ($search) {
            $sources = UserHelper::findUsersByNumber($search, $company_id);
            $out = $sources;
        }

        return $out;
    }

    public function postUploadProfileImage(Request $request)
    {
        $user = Auth::user();
        $company_id = $company_id = $user->company_id;
        $image = $request->file('profile_pic');
        $network = $request->network;
        if ($image) {
            $this->validate(
                $request,
                [
                    'profile_pic' => 'image|mimes:jpeg,png,jpg,gif,svg|max:10024',
                ]
            );
            $input['profile_pic'] = time() . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path('/images/profile_pics');
            $image->move($destinationPath, $input['profile_pic']);
            $image_path = "/images/profile_pics/" . $input['profile_pic'];
            // Image::make(
            //     "/images/profile_pics/" . $input['profile_pic'],
            //     array(
            //         'width' => 250,
            //         'height' => 250,
            //     )
            // )->save("images/profile_pics/250x250-" . $input['profile_pic']);

            $path = url('/') . "/images/profile_pics/" . $input['profile_pic'];
            $out = array(
                'path' => $path,
                'network' => $network,
            );
            return response()->success($out);
        }
        return response()->error('file not found');
    }
}
