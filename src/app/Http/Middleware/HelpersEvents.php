<?php
namespace Accio\App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Event;

class HelpersEvents
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
        Event::listen('theme:body_end', function(){
            print googleAnalytics();
            print googleTagManager();
        });

        Event::listen('theme:body_end', function(){
            print googleTagManagerBody();
        });

        return $next($request);
    }
}