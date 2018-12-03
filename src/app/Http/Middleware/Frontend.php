<?php

namespace Accio\App\Http\Middleware;

use App\Models\Language;
use Closure;
use App\Models\Theme;
use App\Models\MenuLink;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;

class Frontend
{
    /**
     * @param $request
     * @param Closure $next
     * @return \Illuminate\Http\Response|mixed
     * @throws \Exception
     */
    public  function handle($request, Closure $next){
        // http to https redirect
        if (env('HTTP_TO_HTTPS_REDIRECT') && !isHttps()) {
            return Redirect::to(Request::fullUrl(), 301,[],true);
        }


        //redirect menulink to default language if default language slug is given
        if(config('project.multilanguage') && \Request::route('lang')) {
            if (config('project.hideDefaultLanguageInURL')) {
                MenuLink::redirectToDefaultLanguage();
            }

            // Validate language
            if (!Language::checkBySlug(\Request::route('lang'))) {
                return response()->view(Theme::view('errors/404'), ['message' => "This language does not exists!"], 404);
            }
        }

        return $next($request);
    }
}
