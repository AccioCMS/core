<?php

namespace Accio\App\Http\Middleware;

use App\Models\Language;
use Closure;
use App\Models\Theme;
use App\Models\MenuLink;

class Frontend
{
    /**
     * @param $request
     * @param Closure $next
     * @return \Illuminate\Http\Response|mixed
     * @throws \Exception
     */
    public  function handle($request, Closure $next)
    {
        // Set default language
        Language::setDefault();

        // language may be present in url without {param} defined
        Language::setFromURL($request);

        if(config('project.multilanguage') ) {
            if(\Request::route('lang')) {

                //set current language
                Language::setCurrent(\Request::route('lang'));

                //redirect menulink to default language if default language slug is given
                if (config('project.hideDefaultLanguageInURL')) {
                    MenuLink::redirectToDefaultLanguage();
                }

                //check if language exists
                if (!Language::checkBySlug(\Request::route('lang'))) {
                    return response()->view(Theme::view('errors/404'), ['message' => "This language does not exists!"], 404);
                }
            }

            // Add lang parameter to every route/action request if user is accessing a language that's different than default
            Language::setLangAttribute($request);
        }

        // Initialize MenuLinks
        MenuLink::initialize($request);

        // Initialize Theme
        new Theme();

        return $next($request);
    }
}
