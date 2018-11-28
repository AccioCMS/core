<?php

namespace Accio\App\Http\Middleware;

use App\Models\MenuLink;
use App\Models\User;
use App\Models\Language;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class Backend{
    /**
     * @param $request
     * @param Closure $next
     * @return \Illuminate\Http\Response|mixed
     * @throws \Exception
     */
    public  function handle($request, Closure $next){
        if (isInAdmin()) {
            if (Auth::guard("admin")->check()) {
                // get all permissions
                Auth::user()->getPermissions();

                // check permission language
                if (!User::isDefaultGroup() && !User::hasAccess('Language', 'id', Language::current('languageID'))) {
                    return response()->view('errors.404', ['message' => "You don't have permissions on this language!"], 404);
                }
            }

            // Validate language
            if (\Request::route('lang')) {
                if (!Language::checkBySlug(\Request::route('lang'))) {
                    return response()->view('errors.404', ['message' => "This language does not exists!"], 404);
                }
            }
        }
        return $next($request);
    }
}
