<?php

namespace App\Http\Middleware;

use App\Classes\CompanyHelper;
use Closure;
use Illuminate\Http\Request;
use JWTAuth;
use Landlord;
use Auth;

class MultiTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $userRole = [];
        if (Auth::user()) {
            $user = Auth::user();
            $user_d = \App\Classes\UserHelper::getUserDetailsAuth($user->id);
            foreach ($user_d->Roles as $role) {
                $userRole = $role->slug;
            }

            /* Throw message if account is not active */
            if (!in_array($userRole, ["admin.super","super.admin.agent","super.call.center"] )) {

                $company_details = CompanyHelper::getCompanyDetais($user->company_id);
                if ($company_details['is_active'] == 0) {
                    return response()->error('Your account has been suspended by the admin, please contact admin', 401);
                }
            }
            Landlord::addTenant('company_id', $user->company_id);
        }
        return $next($request);
    }
}
