<?php

namespace Accio\App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class Translate{
    /**
     * This middleware is used to enable multilanguage urls
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next){
        return $next($request);
    }
}
