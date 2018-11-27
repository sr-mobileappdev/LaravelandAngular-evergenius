<?php

namespace App\Http\Middleware;

use Closure;
use App\Classes\CompanyHelper;

class CheckApi
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

        if($request->api_key) {

            $api_key=$request->api_key;
            $is_company_exists = CompanyHelper::isApiExists($api_key);
            if($is_company_exists!=false) {
                $request['company_id'] = $is_company_exists;
                return $next($request);
            } else {
                 return response()->error('Wrong/Disabled API key', 401);
            }

        } else {
            return response()->error('API key Not Found');
        }

        //return $next($request);
    }
}
