<?php

namespace Accio\App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class RedirectIfAuthenticated{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null){

        // backend url
        if (isInAdmin()()) {
            $redirectURL = route("backend.base.index");
        }else{ //frontend login
            $redirectURL = route("account.dashboard");
        }

        if (Auth::guard($guard)->check()) {
            return redirect($redirectURL);
        }

        return $next($request);
    }
}
