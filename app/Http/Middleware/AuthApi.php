<?php

namespace App\Http\Middleware;

use Closure;
use App\Classes\CompanyHelper;
use App\Classes\AppOptionsHelper;
use Illuminate\Http\Request;
class AuthApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
       
        if ($request->header('api-key')) {
            $api_key = $request->header('api_key');
            $is_company_exists = CompanyHelper::isApiExists($api_key);
            if ($is_company_exists!=false) {
                $request['company_id'] = $is_company_exists;
                return $next($request);
            } else {
                return response()->error('Wrong/Disabled API key',401);
            }

        } else if ($request->header('app-key')) {
            $input_app_id = $request->header('app-key'); 
            $eg_app_id = AppOptionsHelper::getOptionValue('app_key');
            if ($input_app_id==$eg_app_id)
            {
                return $next($request);
            } else {
                return response()->error('Wrong/Disabled API key',401);
            }
        }
        
        
        else {
            return response()->error('API key Not Found');
        }
    }
}
