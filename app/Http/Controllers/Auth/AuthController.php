<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Auth;
use Bican\Roles\Models\Role;
use Illuminate\Http\Request;
use JWTAuth;
use Mail;
use Socialite;
use App\Classes\CompanyHelper;
use App\Classes\UserHelper;
use Jenssegers\Agent\Agent;

class AuthController extends Controller
{
    /**
     * Get all roles and their corresponding permissions.
     *
     * @return array
     */
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
        /* Get users who have role_id 5 */
        $users = User::select()
        ->where('company_id', $company_id)
        ->whereHas('roles', function ($q) {
            $q->where('role_id', 5);
        })->select(['name','id'])->get();
        return $users;
    }

    /**
     * Get authenticated user details and auth credentials.
     *
     * @return JSON
     */
    public function getAuthenticatedUser()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $token = JWTAuth::fromUser($user);
            $abilities = $this->getRolesAbilities();
            $userRole = [];

            foreach ($user->Roles as $role) {
                $userRole [] = $role->slug;
            }
            $calendar_doctors = $this->getAllDoctors($user->company_id);
            return response()->success(compact('user', 'token', 'abilities', 'userRole', 'calendar_doctors'));
        } else {
            return response()->error('unauthorized', 401);
        }
    }

    /**
     * Redirect the user to the Oauth Provider authentication page.
     *
     * @param string oauth provider
     *
     * @return Response
     */
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Obtain the user information from Oauth Provider.
     *
     * @param string oauth provider
     *
     * @return Response
     */
    public function handleProviderCallback($provider)
    {
        try {
            $user = Socialite::driver($provider)->user();
        } catch (Exception $e) {
            return Redirect::to('auth/'.$provider);
        }

        $authUser = $this->findOrCreateUser($user, $provider);

        \Auth::login($authUser, true);

        return \Redirect::to('/#/login-loader');
    }

    /**
     * Create user based from details provided by oauth providers.
     *
     * @param object user data provided by provider
     * @param object oauth provider instance
     *
     * @return Response
     */
    private function findOrCreateUser($oauthUser, $provider)
    {
        if ($authUser = User::where('oauth_provider_id', $oauthUser->getId())->where('oauth_provider', '=', $provider)->first()) {
            return $authUser;
        }

        return User::create([
            'name' => $oauthUser->name,
            'email' => $oauthUser->email,
            'oauth_provider' => $provider,
            'oauth_provider_id' => $oauthUser->getId(),
            'avatar' => $oauthUser->avatar,
        ]);
    }

    /**
     * Authenticate user.
     *
     * @param Instance Request instance
     *
     * @return JSON user details and auth credentials
     */
    public function postLogin(Request $request)
    {
        $this->validate($request, [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user_ip = $request->ip();
        $total_companies = 0;
        $credentials = $request->only('email', 'password');
        $company_config_status = 0;
        $user = User::whereEmail($credentials['email'])->first();

        if (isset($user->email_verified) && $user->email_verified == 0) {
            return response()->error('Email Unverified');
        }
        if (isset($user->status) && $user->status == 0) {
            return response()->error('Your account has been suspended by the admin, please contact admin');
        }

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->error('Invalid credentials', 401);
            }
        } catch (\JWTException $e) {
            return response()->error('Could not create token', 500);
        }

        $user       = Auth::user();
        $application_url = url('/');
        $token      = JWTAuth::fromUser($user);
        $abilities  = $this->getRolesAbilities();
        $userRole   = [];
        $company_details = [];
        $admin_compnies = [];

        foreach ($user->Roles as $role) {
            $userRole = $role->slug;
        }
        $superAdminRoles = UserHelper::getSuperAdminRoles();

        if (in_array($userRole, $superAdminRoles)) {
            $u_cpmpany_roles = restrictedCompaniesRoles();
            if (in_array($userRole, $u_cpmpany_roles)) {
                $admin_compnies  = CompanyHelper::getAllUserCompanies($user->id, $userRole);
                $total_companies = UserHelper::getuserComapnieCount($user->id);
                if ($userRole=='super.call.center') {
                    $user_permissions = UserHelper::getUserPermissions($user->id);
                    $abilities['super.call.center']=array_merge($abilities['super.call.center'], $user_permissions);
                }
            } else {
                $admin_compnies = CompanyHelper::getAllCompaniesSuperadmin();
            }
        }

        if (!in_array($userRole, $superAdminRoles)) {
            $company_details = CompanyHelper::getCompanyDetailsFields($user->company_id, ['id','name','api_key','is_active']);
           
            /* If Account is suspended throw error */
            if ($company_details['is_active']==0) {
                return response()->error('Your account has been suspended by the admin, please contact admin');
            }
        }
        $com_id = $user->company_id;
        $calendar_doctors = $this->getAllDoctors($user->company_id);
        if (!in_array($userRole, $superAdminRoles)) {
            $u_compny_d = CompanyHelper::getCompanyDetailsFields($user->company_id);
            $user = $user->toArray();
            $user['company_api_key'] = $u_compny_d['api_key'];
        }
        $agent = new Agent();

        $device_type = 'Web';
        $is_mob = $agent->isMobile();
        $is_tab = $agent->isTablet();
        if ($is_mob) {
            $device_type = 'Mobile';
        } elseif ($is_tab) {
            $device_type = 'Tablet';
        }
        $device = $agent->device();
        if (!is_array($user)) {
            $user = $user->toArray();
        }
        $company_config_status = CompanyHelper::checkConfig_status($com_id);
        UserHelper::addLoginActivity($user['id'], $userRole, $credentials['email'], $device_type, $device, $user_ip, 'login');
        /* Refactoring */
        $user  = getAuthUserArray($user);
        $abilities = filterAbilities($abilities, $userRole);

        /* Refactoring */
        return response()->success(compact('application_url', 'user', 'token', 'abilities', 'userRole', 'calendar_doctors', 'admin_compnies', 'company_details', 'total_companies', 'company_config_status'));
    }

    public function verifyUserEmail($verificationCode)
    {
        $user = User::whereEmailVerificationCode($verificationCode)->first();

        if (!$user) {
            return redirect('/#/userverification/failed');
        }

        $user->email_verified = 1;
        $user->save();

        return redirect('/#/userverification/success');
    }

    /**
     * Create new user.
     *
     * @param Instance Request instance
     *
     * @return JSON user details and auth credentials
     */
    public function postRegister(Request $request)
    {
        $this->validate($request, [
            'name'       => 'required|min:3',
            'email'      => 'required|email|unique:users',
            'password'   => 'required|min:8|confirmed',
        ]);

        $verificationCode = str_random(40);
        $user = new User();
        $user->name = trim($request->name);
        $user->email = trim(strtolower($request->email));
        $user->password = bcrypt($request->password);
        $user->email_verification_code = $verificationCode;
        $user->save();

        $token = JWTAuth::fromUser($user);
        $bcc_email = getenv('BCC_EMAIL');

        Mail::send('emails.userverification', ['verificationCode' => $verificationCode], function ($m) use ($request, $bcc_email) {
            $m->to($request->email, 'test')->subject('Email Confirmation');
            if ($bcc_email!=false) {
                $m->bcc($bcc_email, "EverGenius");
            }
        });

        return response()->success(compact('user', 'token'));
    }
}
