<?php

namespace Accio\App\Http\Middleware;

use App\Models\MenuLink;
use App\Models\Theme;
use App\Models\User;
use App\Models\Language;
use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class Application
{
    /**
     * @param  $request
     * @param  Closure $next
     * @return \Illuminate\Http\Response|mixed
     * @throws \Exception
     */
    public  function handle($request, Closure $next)
    {
        // Set default language
        Language::setDefault();

        // language may be present in url without {param} defined
        Language::setFromURL($request);

        // Add lang parameter to every route/action request if user is accessing a language that's different than default
        if (config('project.multilanguage')) {
            if(\Request::route('lang')) {
                // set current language
                Language::setCurrent(\Request::route('lang'));
            }
            Language::setLangAttribute($request);
        }

        // Initialize MenuLinks
        MenuLink::initialize($request);

        // Initialize Theme
        // When routes are not cached, theme is initialized in Routes
        if(App::routesAreCached()) {
            new Theme();
        }

        return $next($request);
    }
}
