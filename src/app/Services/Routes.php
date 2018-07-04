<?php

/**
 * Routes
 *
 * Handle Front-end and Backend Route
 * @author Faton Sopa <faton.sopa@manaferra.com>
 * @version 1.0
 */
namespace Accio\App\Services;

use App\Models\Plugin;
use App\Models\Theme;
use Doctrine\Common\Cache\Cache;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use App\Models\Language;

class Routes{

    /** @var array $getRoute The List of current routes.*/
    public $routes;

    public function __construct()
    {
        //get active theme and theme routes
        new Theme();
    }

    /**
     * Define middleware that routes should pass through.
     *
     * @var array
     */
    private $middleware = [
      'backend' => [
        'application',
        'backend'
      ],
      'frontend' => [
        'application',
        'frontend'
      ]
    ];

    /**
     * Define Package Backend routes
     *
     * These routes are taken from package/routes/backend
     *
     * @return void
     */
    public function mapBackendRoutes(){
        $directory = __DIR__.'/../../routes/backend';
        if(is_dir($directory)) {
            $routeFiles = File::files($directory);
            \Route::group([
              'middleware' => $this->middleware['backend'],
            ], function () use ($routeFiles) {
                foreach ($routeFiles as $file) {
                    require $file;
                }
            });
        }
    }

    /**
     * Define front end routes
     *
     * These routes are taken from /routes and are applied across all themes
     *
     * @return void
     */
    public function mapFrontendRoutes(){
        $directory = base_path('routes');

        if(is_dir($directory)) {
            $routeFiles = File::files($directory);

            \Route::group([
              'middleware' => $this->middleware['frontend'],
            ], function () use ($routeFiles) {
                foreach ($routeFiles as $file) {
                    // Base.php is loaded from FrontendBaseRoutes method
                    if ($file->getFilename() !== 'base.php') {
                        require $file;
                    }
                }
            });
        }
    }

    /**
     * Define "Current Theme" routes for the application.
     *
     * These routes are taken from current Theme
     *
     * @return void
     */
    public function mapThemeRoutes(){
        $this->getRoutsFromTheme(Theme::getActiveTheme());
    }

    /**
     * Get routes from a specific theme
     *
     * @param string $themeName Directoy Name of theme
     */
    public function getRoutsFromTheme($themeName){
        if(Theme::ifExists($themeName)) {
            $routeDir = base_path('themes/' . $themeName . '/routes');
            if (is_dir($routeDir)) {
                $routeFiles = File::files($routeDir);

//                \Route::group([
//                    'middleware' => 'frontend', // TODO add theme middleware
//                    'namespace' => Theme::getNamespaceOf(Theme::getActiveTheme()),
//                ], function () use ($routeFiles) {
//                    foreach ($routeFiles as $file) {
//                        require_once $file;
//                    }
//                });
            }
        }
    }

    /**
     * Define "Plugins" backend routes for the application.
     *
     * It only takes in consideration active plugins
     *
     * @return void
     */
    public function mapPluginsBackendRoutes(){
        foreach(Plugin::activePlugins() as $plugin){
            $backendRoutes = $plugin->backendRoutes();
            if($backendRoutes){
                \Route::group([
                  'middleware' => $this->middleware['backend'],
                  'as' => 'Backend.'.$plugin->namespaceWithDot().".",
                  'namespace' => $plugin->parseNamespace().'\Controllers',
                  'prefix' => Config::get('project')['adminPrefix']."/".$plugin->backendURLPrefix()
                ], function () use($backendRoutes) {
                    foreach($backendRoutes as $file){
                        require $file->getPathname();
                    }
                });
            }
        }
        return;
    }

    /**
     * Define "Plugins" frontend routes for the application.
     *
     * It only takes in consideration active plugins
     *
     * @return void
     */
    public function mapPluginsFrontendRoutes(){
        foreach(Plugin::activePlugins() as $plugin){
            // Frontend routes
            $frontendRoutes = $plugin->frontendRoutes();
            if($frontendRoutes){
                \Route::group([
                  'middleware' => $this->middleware['frontend'],
                  'as' => $plugin->namespaceWithDot().".",
                  'namespace' => $plugin->parseNamespace().'\Controllers',
                ], function () use($frontendRoutes) {
                    foreach($frontendRoutes as $file){
                        require $file->getPathname();
                    }
                });
            }
        }
        return;
    }

    /**
     * Generate an full http/s link to easily create links in front-end whether the project is multilanguage or not
     *
     * @param  string $link The suffix of a link after the domain  (ex. "/about-us/")
     * @param  string $baseURL The link of the domain to be added before the $link (ex. http://www.mywebsite.com). If empty, current project URL will be used
     *
     * @return string
     * */
    public function url($link = '',$baseURL = ''){
        $projectConfig = Config::get('project');

        $language = '';
        //don't show language slug current url belongs to default language
        if ($projectConfig['multilanguage'] && App::getLocale() != Language::getDefault("slug")) {
            $language .= App::getLocale().'/';
        }

        if($baseURL){
            return $baseURL.$language.$link.'/';
        }else{
            return url($language.$link);
        }
    }


    /**
     * Generate the URL to a controller action, depending if url should contain language slug or not.
     *
     * It uses Laravel's action method
     *
     * @param  string  $action ex. PostController@single
     * @param  array   $parameters
     * @param  bool    $absolute
     * @return string
     */
    public function action($action, $parameters = [], $absolute = true){
        $getProjectConfig = Config::get('project');
        //add language if current language
        if($getProjectConfig['multilanguage'] && App::getLocale() != Language::getDefault('slug')){
            $langParameter = array('lang'=> App::getLocale() );
        }else{
            $langParameter = array('lang'=>"");
        }

        $parameters = array_merge($langParameter, $parameters);
        $method = Theme::getNamespace().$action;
        $url = action($method,$parameters);
        return $url;
    }

    /**
     *  Add Frontend base routes
     *
     * @return void
     */
    public function mapFrontendBaseRoutes(){
        $projectConfig = Config::get('project');
        $baseFile = base_path('routes/base.php');

        \Route::group([
          'middleware' => $this->middleware['frontend'],
        ], function () use($baseFile) {
            require $baseFile;
        });


        // Find base route
        // getByName() method is currently not working for any strange reason
        $baseRoute = null;
        foreach(Route::getRoutes() as $route){
            if($route->getName() == 'base.homepage'){
                $baseRoute = $route;
                break;
            }
        }

        if($baseRoute && $projectConfig['multilanguage']){
            // Only proced base route that has translate as a middleware
            // base.homepage routes is handled in a separate method, for language purposes
            if($baseRoute->getName() == 'base.homepage' && in_array("translate",$baseRoute->middleware())){
                foreach(Language::cache()->getItems() as $language){
                    // Create default language routes
                    $newRoute = Route::get($baseRoute->uri().'/'.$language->slug,$baseRoute->getActionName());

                    //set name
                    $newRoute->name($baseRoute->getName().'.'.$language->slug);

                    //set wheres
                    foreach($baseRoute->wheres as $key=>$value){
                        $newRoute->where($key, $value);
                    }

                    //set middleware
                    $newRoute->middleware($baseRoute->middleware());

                }
            }
        }

        return;
    }
    /**
     * Add {lang} param to all Frontend routes, if the site is multilanguage
     *
     * Backend routes are excluded
     */
    public  function addLanguagePrefix(){
        $projectConfig = Config::get('project');
        if($projectConfig['multilanguage']) {
            foreach (Route::getRoutes() as $route) {
                foreach($route->methods() as $method){

                    // Only proceed routes that have translate as a middleware
                    // base.homepage routes is handled in a separate method, for language purposes
                    if($method !== 'HEAD' && in_array("translate",$route->middleware()) && strpos($route->getName(),'base.homepage') === false){
                        $method = strtolower($method);

                        /**
                         * Declare language route
                         **/
                        // Create default language routes
                        $newRoute = Route::$method('{lang}/'. $route->uri(), $route->getActionName());

                        //set name
                        $newRoute->name($route->getName());

                        //set wheres
                        foreach($route->wheres as $key=>$value){
                            $newRoute->where($key, $value);
                        }

                        //set middleware
                        $newRoute->middleware($route->middleware());

                        //change original route name so they don't mess with new ones :)
                        $route->name(".default");
                    }
                }
            }
        }
    }

    /**
     * Get a method of backend
     *
     * @param string $method
     * @return string
     */
    public function backend($method){
        return '\App\Http\Controllers\Backend\\'.$method;
    }


    /**
     *  Give hardcoded routes higher priority
     *  It goes through each route, calculate their length and parameters and gives
     *  hardcoded routes higher priority than wild card routes
     *
     * @return void
     */
    public function sortRoutes(){
        $originalRoutes = \Route::getRoutes();
        $routesByMethod = $originalRoutes->getRoutesByMethod();

        //remove all previous routes
        \Route::setRoutes(new RouteCollection());

        $sortedRoutes = [];
        foreach($routesByMethod as $method => $methodRoutes){
            foreach($methodRoutes as $uri => $route){
                $sort = (substr_count($uri, '/'));

                //front page has the higher priority
                if($uri == '/'){
                    $sort = 0;
                }
                else {
                    $explodeURI = explode('/', $uri);

                    //increase weight based on number of slashes
                    if ($sort > 1) {
                        $sort = $sort * 2;
                    }

                    foreach ($explodeURI as $param) {
                        if ($param) {
                            // Uri with one parameter should be sorted upper
                            if ($sort == 0) {
                                $sort = (strpos($param, '{') === 0 ? 2 : 1);
                            }
                            else {
                                // Custom uri parameters should have a lower priority
                                // than hard coded parameters
                                $sort .= (strpos($param, '{') === 0 ? 1 : 0);
                            }
                        }
                    }
                }
                $sortedRoutes[$method][$uri] = intval($sort);
            }

            //sort routes ASC
            asort($sortedRoutes[$method]);

            foreach($sortedRoutes[$method] as $uri => $route){
                \Route::getRoutes()->add($methodRoutes[$uri]);
            }
        }
    }
}
