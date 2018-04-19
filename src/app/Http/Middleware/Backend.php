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
        if ($request->is(Config::get('project')['adminPrefix'].'*')) {
            // Set default language
            Language::setDefault();

            // Add lang parameter to every route/action request if user is accessing a language that's different than default
            if (config('project.multilanguage')) {
                if(\Request::route('lang')) {
                    // set current language
                    Language::setCurrent(\Request::route('lang'));
                }
                Language::setLangAttribute($request);
            }

            if (Auth::check()) {
                //set all permissions
                User::setPermissions();

                // check permission language
                if (!User::isDefaultGroup() && !User::hasAccess('Language', 'id', Language::current('languageID'))) {
                    return response()->view('errors.404', ['message' => "You don't have permissions on this language!"], 404);
                }
            }

            //check if language exists
            if (\Request::route('lang')) {
                if (!Language::checkBySlug(\Request::route('lang'))) {
                    return response()->view('errors.404', ['message' => "This language does not exists!"], 404);
                }
            }

            // Initialize MenuLinks
            MenuLink::initialize($request);

        }
        return $next($request);
    }
}
