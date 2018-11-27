<?php

namespace App\Http\Controllers;

use App\AdminView;
use App\Classes\AppOptionsHelper;
use App\Classes\CompanyHelper;
use App\Classes\UserHelper;
use App\Company;
use App\User;
use Bican\Roles\Models\Role;
use Curl;
use Datatables;
use Illuminate\Http\Request;
use Infusionsoft;
use Input;
use JWTAuth;
use Auth;
use App\PasswordReset;
use App\LoginActivities;
use DB;

class SuperAdminController extends Controller
{
    private function getRolesAbilities()
    {
        $abilities = [];
        $roles = Role::all();
        foreach ($roles as $role) {
            if (!empty($role->slug)) {
                $abilities[$role->slug] = [];
                $rolePermission = $role->permissions()->get();

                foreach ($rolePermission as $permission) {
                    if (!empty($permission->slug)) {
                        array_push($abilities[$role->slug], $permission->slug);
                    }
                }
            }
        }

        return $abilities;
    }

    private function getAllDoctors($company_id)
    {
        $users = User::with('roles')->where('company_id', $company_id)->whereHas(
            'roles',
            function ($q) {
                $q->where('role_id', 5);
            }
        )->select(['name', 'id'])->get();

        return $users;
    }

    /**
     * @return mixed
     */
    public function postIndex()
    {
        $input = Input::get();

        $c_user = Auth::user();
        $c_user_id = $c_user->id;
        $c_user_role = $c_user->roles->toArray()[0]['slug'];
        $compay_user_roles = restrictedCompaniesRoles();
        $role_id = UserHelper::getRoleIdBySlug('super.admin.agent');
        $agency_search = null;
        $user_companies = [];

        if (isset($input['customFilter']['agent_user'])) {
            $agency_search = [$input['customFilter']['agent_user']][0];
        }
        /* If user is Agency */
        if ($c_user_role=='super.admin.agent') {
            $agency_search  = $c_user_id;
        }
        /* If user is Call Center get companies */
        if ($c_user_role=='super.call.center') {
            $user_companies = UserHelper::getUserCompanies($c_user_id);
        }

        $companies = Company::select("companies.*", "users.agency_name as agency_name")
        ->leftJoin("users", "companies.owner_id", '=', 'users.id');

        if ($agency_search!=null) {
            $companies->where('companies.owner_id', $agency_search);
        }
        /* If user is Call ceter */
        if (count($user_companies)>0) {
            $companies->whereIn('companies.id', $user_companies);
        }

        $companies = $companies
            ->orderBy('companies.created_at', 'desc')
            ->get();
        return Datatables::of($companies)->make(true);
    }

    public function postImpersonate()
    {
        $data = Input::get();
        $company_id = current($data);
        $company_details = [];
        $adminObj = AdminView::select('user_id')->where('company_id', $company_id)->first();
        if ($adminObj) {
            $user_id = $adminObj->user_id;
            $user = User::find($user_id);
            $token = JWTAuth::fromUser($user);
            $abilities = $this->getRolesAbilities();
            $userRole = [];

            foreach ($user->Roles as $role) {
                $userRole[] = $role->slug;
            }

            if (in_array("admin.user", $userRole)) {
                $company_details = CompanyHelper::getCompanyDetais($company_id);
                $user = $user->toArray();
                $user['company_api_key'] = $company_details['api_key'];
            }

            $calendar_doctors = $this->getAllDoctors($company_id);
            $application_url = url('/');
            return response()->success(compact('application_url', 'user', 'token', 'abilities', 'userRole', 'calendar_doctors', 'company_details'));
        } else {
            return response()->error('No user found');
        }
    }

    public function postSuspendCompany(Request $request)
    {
        $c_user  = Auth::user();
        $c_user_role = $c_user->roles->toArray()[0]['slug'];
        $company_id = current($request->all());
        CompanyHelper::updateCompanyStatus($company_id, 0);

        /*Update Agency Status */
        if ($c_user_role == 'super.admin.agent') {
            CompanyHelper::updateCompanyAgencyStatus($company_id, 0);
        }
        $adminObj = AdminView::where('company_id', $company_id)->select('user_id')->first();
        if ($adminObj) {
            $userId = $adminObj->user_id;
        }
        $admin_compnies = CompanyHelper::getAllCompanies();
        $company_detail = CompanyHelper::getCompanyDetais($company_id);
        UserHelper::updateUserStatusNotification(0, $userId);
        return response()->success(['status' => 'success', 'admin_compnies' => $admin_compnies, 'company_detail' => $company_detail]);
    }

    public function postActivateCompany(Request $request)
    {
        $c_user  = Auth::user();
        $c_user_role = $c_user->roles->toArray()[0]['slug'];
        $company_id = current($request->all());
        CompanyHelper::updateCompanyStatus($company_id, 1);
        /*Update Agency Status */
        if ($c_user_role == 'super.admin.agent') {
            CompanyHelper::updateCompanyAgencyStatus($company_id, 1);
        }
        $adminObj = AdminView::where('company_id', $company_id)->select('user_id')->first();
        if ($adminObj) {
            $userId = $adminObj->user_id;
        }
        $admin_compnies = CompanyHelper::getAllCompanies();
        $company_detail = CompanyHelper::getCompanyDetais($company_id);
        UserHelper::updateUserStatusNotification(1, $userId);
        return response()->success(['status' => 'success', 'admin_compnies' => $admin_compnies, 'company_detail' => $company_detail]);
    }

    public function getCompanyDetails($companyId)
    {
        $company_details = Company::where('id', $companyId)->select('id', 'name', 'site_url', 'email')->first();
        if ($company_details) {
            return response()->success($company_details);
        } else {
            return response()->error('No such company found');
        }
    }

    public function deleteAccount($company_id)
    {
        if (Auth::user()) {
            $user = Auth::user();
            $userId = $user->id;
            UserHelper::dettachUserCompany($userId, $company_id);
        }
        CompanyHelper::deleteCompany($company_id);
        CompanyHelper::deleteCompanyContacts($company_id);
        CompanyHelper::deleteCompanyAppointments($company_id);
        CompanyHelper::deleteCompanyNotificationMails($company_id);
        CompanyHelper::deleteCompanySettings($company_id);
        CompanyHelper::deleteCompanyCalls($company_id);
        CompanyHelper::deleteCompanySms($company_id);
        CompanyHelper::deleteCompanyUsers($company_id);
        return response()->success(['status' => 'success']);
    }

    public function getInfusionsoftConnected()
    {
        $connected = true;
        $url = '/api/superadmin/infusionsoft-connect-callback';
        $infusionsoft_token = AppOptionsHelper::getOptionValue('infusionsoft_token');
        if (is_null($infusionsoft_token) || empty($infusionsoft_token)) {
            $connected = false;
            $infusionsoft = Infusionsoft::getAuthorizationUrl();
            $url = $infusionsoft;
        }
        $status = array('login' => $connected, 'url' => $url);
        return response()->success(compact('status'));
    }

    public function CallBackInfusionsoft(Request $request)
    {
        if ($request->get('code')) {
            $code = $request->get('code');
            $token = Infusionsoft::requestAccessToken($code);
            $ac_token = $token->accessToken;
            $refresh_token = $token->refreshToken;
            AppOptionsHelper::updateOptionValue('infusionsoft_refresh_token', $refresh_token);
            $update = AppOptionsHelper::updateOptionValue('infusionsoft_token', $ac_token);
            if ($update) {
                $link = url('/') . '/#/admin/infusionsoft';

                /*  Add Resk hook add contact event */
                $resthooks = Infusionsoft::resthooks();
                // first, create a new task
                $resthook = $resthooks->create([
                    'eventKey' => 'contact.add',
                    'hookUrl' => url('/') . '/rest-hook/infusionsoft/add-contact',
                ]);
                AppOptionsHelper::updateOptionValue('infusionsoft_add_contact_key', $resthook->key);
                $resthook = $resthooks->find($resthook->key)->verify();
                return \Redirect::to($link);
            }
        }
    }

    public function getLogoutInfusion()
    {
        $inf_token = AppOptionsHelper::getOptionValue('infusionsoft_token');
        $inf_key = AppOptionsHelper::getOptionValue('infusionsoft_add_contact_key');
        AppOptionsHelper::updateOptionValue('infusionsoft_header', null);
        AppOptionsHelper::updateOptionValue('infusionsoft_add_contact_key', null);
        AppOptionsHelper::updateOptionValue('infusionsoft_token', null);
        $api_url = 'https://api.infusionsoft.com/crm/rest/v1/hooks/' . $inf_key . '?access_token=' . $inf_token;
        Curl::to($api_url)
            ->delete();
        return response()->success('success');
    }

    /**
     * Add New Super admin users
     *
     * @return void
     */
    public function postUser(Request $request)
    {
        $this->validate(
            $request,
            [
                'name' => 'required|min:3',
                'email' => 'required|email|unique:users,email,' . $request->email,
                'phone_country_code' => 'required|min:2',
                'phone' => 'required|min:3',
                'role' => 'required|min:3'
            ]
        );
        $userId = UserHelper::createSuperAdminUser($request);
        if ($userId) {
            return response()->success(['satus' => 'success', 'user_id' => $userId]);
        }
        return response()->error('Error!!! Something went Wrong');
    }


    /**
     * @param $id
     * @return mixed
     */
    public function getUser($id)
    {
        $user = UserHelper::getSuperAdminUser($id);
        if ($user) {
            return response()->success($user);
        }
        return response()->error('user not found');
    }

    /**
     * @param $userId
     * @return Mixed
     */
    public function putUser($userId = null)
    {
        $isUser = false;
        /* Is user data is available */
        if ($userId != null) {
            $user = Input::get('data');
            if (isset($user['email'])) {
                $iseEmailExists = UserHelper::isEmailExists($user['email'], $userId);
                if ($iseEmailExists) {
                    return response()->error('Email Already in use');
                }
            }
            $isUser = UserHelper::isSuperAdminUserExists($userId);
        }
        if ($isUser) {
            $updated = UserHelper::updateSuperAdminUser($user, $userId);
            if ($updated) {
                $user = UserHelper::getSuperAdminUser($userId);
                return response()->success($user);
            }
        }
        return response()->error('user not found');
    }

    /**
     * @param $userId
     * @return mixed
     */
    public static function putSuspendAccount($userId)
    {
        $user = UserHelper::getSuperAdminUser($userId);
        if ($user) {
            UserHelper::changeUserStatus($userId, 0);
            $user_get =User::find($userId);
            $c_user_role = $user_get->roles->toArray()[0]['slug'];
            /*Suspend Allcomapnies */
            if ($c_user_role == 'super.admin.agent') {
                UserHelper::updateAgentCompaniesStatus($userId, 0);
            }
            UserHelper::updateUserStatusNotification(0, $userId);
            return response()->success($user);
        }
        return response()->error('user not found');
    }

    /**
     * @param $userId
     * @return mixed
     */
    public static function deleteSuperadminAccount($userId)
    {
        $user = UserHelper::getSuperAdminUser($userId);
        if ($user) {
            $user_get =User::find($userId);
            $c_user_role = $user_get->roles->toArray()[0]['slug'];
            /*Suspend Allcomapnies */
            if ($c_user_role == 'super.admin.agent') {
                UserHelper::deleteAgentCompanies($userId);
            }

            UserHelper::DeleteSuperAdminUser($userId);
            return response()->success($user);
        }
        return response()->error('user not found');
    }

    /**
     * @param $userId
     * @return mixed
     */
    public static function putActiveAccount($userId)
    {
        $user = UserHelper::getSuperAdminUser($userId);
        if ($user) {
            UserHelper::changeUserStatus($userId, 1);
            $user_get =User::find($userId);
            $c_user_role = $user_get->roles->toArray()[0]['slug'];
            /*Suspend Allcomapnies */
            if ($c_user_role == 'super.admin.agent') {
                UserHelper::updateAgentCompaniesStatus($userId, 1);
            }
            UserHelper::updateUserStatusNotification(1, $userId);
            return response()->success($user);
        }
        return response()->error('user not found');
    }

    public function postUsers()
    {
        $slug_find = ['super.call.center', 'super.admin.agent'];
        $input = Input::get();

        /* Filter for Role of users */
        if (isset($input['customFilter']['role'])) {
            $slug_find = [$input['customFilter']['role']];
        }

        $users = User::select(
            'users.id',
            'users.email',
            'users.status',
            'users.name',
            'users.phone_country_code',
            'users.phone',
            'users.num_license',
            'users.agency_name',
            'roles.name as role',
            DB::raw("(SELECT COUNT(id) from companies WHERE companies.owner_id = users.id and companies.deleted_at IS NULL ) as count_companies")
        )->whereHas(
            'roles',
            function ($q) use ($slug_find) {
                $q->whereIn('roles.slug', $slug_find);
            }
        )
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->orderBy('users.created_at', 'desc')
            ->get();
        return Datatables::of($users)->make(true);
        ;
    }

    public function postAdminNotifications()
    {
        $c_user = Auth::user();
        $c_user_role = $c_user->roles->toArray()[0]['slug'];
        if ($c_user_role == 'admin.super') {
            $notifiations =
                \App\NotificationSetting::select(['id', 'title', 'email_subject', 'status'])
                ->where('type', 'evergenius_admin_notifications')
                ->orderBy('created_at', 'desc')
                ->get();
            return Datatables::of($notifiations)->make(true);
        }
        return response()->error('you are not able to fetch notifications');
    }

    public function getNotification($notificationId = null)
    {
        $c_user = Auth::user();
        $c_user_role = $c_user->roles->toArray()[0]['slug'];
        if ($notificationId==null || $c_user_role != 'admin.super') {
            return response()->error('you are not able to fetch notifications');
        }
        $notification = \App\Classes\NotificationHelper::getNotification($notificationId);
        return response()->success($notification);
    }

    public function putNotification($notificationId)
    {
        $c_user = Auth::user();
        $c_user_role = $c_user->roles->toArray()[0]['slug'];
        if ($notificationId==null || $c_user_role != 'admin.super') {
            return response()->error('you are not able to fetch notifications');
        }
        /* update data */
        $inputData = Input::get('data');
        \App\Classes\NotificationHelper::updateNotificationEmail($notificationId, $inputData);
        /* update data */
        $notification = \App\Classes\NotificationHelper::getNotification($notificationId);
        return response()->success($notification);
    }

    public function getAgentUsers()
    {
        $c_user = Auth::user();
        $c_user_role = $c_user->roles->toArray()[0]['slug'];
        if ($c_user_role == 'admin.super') {
            $agent_users = UserHelper::getAgencyUsers();
            return response()->success($agent_users);
        }
    }

    public function postLoginActivities()
    {
        $where = [];
        $find_roles = ["super.call.center", "super.admin.agent", "admin.super", "admin.user", "sales", "doctors"];
        $input = Input::get();
        if ((isset($input['customFilter']['start_date']) && empty($input['customFilter']['start_date'])==false) && (isset($input['customFilter']['end_date']) && empty($input['customFilter']['end_date'])==false)) {
            $start_date = $input['customFilter']['start_date'];
            $end_date = $input['customFilter']['end_date'];
            $d_s = array('login_activities.time', '>=', date('y-m-d 00:00:00', strtotime($start_date)));
            $d_e = array('login_activities.time', '<=', date('y-m-d 23:59:59', strtotime($end_date)));
            array_push($where, $d_s, $d_e);
        }
        if (isset($input['customFilter']['user_id']) && empty($input['customFilter']['user_id'])==false) {
            $where_user = $input['customFilter']['user_id'];
            $where_user =  array('login_activities.user_id', '=',$where_user);
            array_push($where, $where_user);
        }
        if (isset($input['customFilter']['role']) && empty($input['customFilter']['role'])==false) {
            $where_role = $input['customFilter']['role'];
            $find_roles = [$where_role];
        }
        $loginActivites = LoginActivities::select(['roles.name as role','users.name','login_activities.email','device_type','device_name','ip_address','event','time','login_activities.id as login_id'])
            ->join('roles', 'roles.slug', '=', 'login_activities.role')
            ->join('users', 'users.id', '=', 'login_activities.user_id')
            ->whereIn('role', $find_roles)
            ->where($where)
            ->orderBy('login_activities.id', 'DESC')
            ->get();
			//$dat = $loginActivites->toArray();
			//dd($dat);
        return Datatables::of($loginActivites)->make(true);
    }
    public function getSuperAdminUsers()
    {
        $agent_users = UserHelper::getSuperadminUsers();
        return response()->success($agent_users);
    }

    public function getLicenceInformation()
    {
        $total_companies = 0;
        $num_license = 0;
        $c_user = Auth::user();
        $c_user_role = $c_user->roles->toArray()[0]['slug'];
        if ($c_user_role == 'super.admin.agent') {
            $total_companies = UserHelper::getAgencyComapnieCount($c_user->id);
            $num_license = $c_user->num_license;
            return response()->success(compact(['total_companies','num_license']));
        }
        return response()->success(compact(['total_companies','num_license']));
    }

    public function getDashboardStats()
    {
        $c_user = Auth::user();
        $c_user_role = $c_user->roles->toArray()[0]['slug'];
        if ($c_user_role =='admin.super') {
            $input_data = Input::get();
            if (isset($input_data['start_date'])==false && isset($input_data['end_date'])==false) {
                return response()->error(['message'=>'start_date and end_date is required']);
            }
            $start_date = $input_data['start_date'];
            $end_date = $input_data['end_date'];
            $dashborad_data = UserHelper::superadminDashboardStats($start_date, $end_date);
            return response()->success(compact('dashborad_data'));
        }
        return response()->error(['message'=>'Please login with valid user role.']);
    }

    public function getImpersonateCompanies($userId=null){

        $abilities  = [];
        if($userId==null){
            $user = Auth::user();
            $userId = $user->id;
        }
        if($userId!=null){
        $user = User::with('roles')->where('id', $userId)->first();
        if(!count($user)){
            return response()->error('please request with valid user id');
        }
        $userRole = $user->roles->toArray()[0]['slug'];
        $superAdminRoles = UserHelper::getSuperAdminRoles();
        if (in_array($userRole, $superAdminRoles)) {
            $u_cpmpany_roles = restrictedCompaniesRoles();
            if (in_array($userRole, $u_cpmpany_roles)) {
                $admin_compnies  = CompanyHelper::getAllUserCompanies($user->id, $userRole);
                $total_companies = UserHelper::getuserComapnieCount($user->id);
                if ($userRole=='super.call.center') {
                    //$user_permissions = UserHelper::getUserPermissions($user->id);
                    //$abilities['super.call.center']=array_merge($abilities['super.call.center'], $user_permissions);
                }
            } else {
                $admin_compnies = CompanyHelper::getAllCompaniesSuperadmin();
            }
            return response()->success(compact('admin_compnies'));
        }
      }
        return response()->error('please request with valid user id');
    }
}
