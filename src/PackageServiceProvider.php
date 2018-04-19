<?php

namespace Manaferra;
use App\Models\Plugin;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\File;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Accio\App\Commands\AppUninstall;
use Accio\App\Commands\CheckRequirements;
use Accio\App\Commands\DBExport;
use Accio\App\Commands\DeployClean;
use Accio\App\Commands\DeployDB;
use Accio\App\Commands\DeployEnv;
use Accio\App\Commands\DeployRelease;
use Accio\App\Commands\DeploySetPermissions;
use Accio\App\Commands\DeployUploads;
use Accio\App\Commands\MakeArchive;
use Accio\App\Commands\MakeDummy;
use Accio\App\Commands\AppInstall;
use Accio\App\Commands\MakeTheme;
use Accio\App\Commands\MakeUser;
use Illuminate\Config\Repository as ConfigRepository;
use Accio\App\Commands\PluginInstall;

class PackageServiceProvider extends ServiceProvider{

    /**
     * List Package Service Providers
     * Example: 'Accio\App\Providers\ClassNameServiceProvider',
     * @var array
     */
    protected $providers = [];

    /**
     * List Package bindings
     * Example: 'ClassName' => 'Accio\App\Services\ClassName',
     * @var array
     */
    protected $bindings = [];

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        MakeDummy::class,
        MakeUser::class,
        AppInstall::class,
        MakeArchive::class,
        DBExport::class,
        DeployDB::class,
        DeployUploads::class,
        DeployEnv::class,
        DeploySetPermissions::class,
        DeployClean::class,
        DeployRelease::class,
        MakeTheme::class,
        CheckRequirements::class,
        AppUninstall::class,
        PluginInstall::class
    ];

    /**
     * List Package Aliases
     * Example: 'ClassName' => 'Accio\App\Services\ClassName',
     * @var array
     */
    protected $aliases = [
        'Pagination' => 'Accio\App\Services\Pagination',
        'Routes' => 'Accio\App\Services\Routes',
        'Search' => 'Accio\App\Services\Search',
        'Meta' => 'Accio\App\Services\Meta',
    ];

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapRoutes(){
        if (!$this->app->routesAreCached()) {
            Route::group([
                'middleware' => 'web',
            ], function ($router) {
                require __DIR__.'/routes/web.php';
            });
        }
    }

    public function boot(){
        /**
         * Register commands, so you may execute them using the Artisan CLI.
         */
        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }


        /**
         * Register migrations, so they will be automatically run when the php artisan migrate command is executed.
         */
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        if(self::isInstalled()) {
            /**
             * Register Middleware
             */
            $kernel = $this->app['Illuminate\Contracts\Http\Kernel'];
            $kernel->pushMiddleware('Accio\App\Http\Middleware\HelpersEvents');
            //$kernel->pushMiddleware('Accio\App\Http\Middleware\MenuLink');
            //$kernel->pushMiddleware('Accio\App\Http\Middleware\Backend');

            /**
             * Register Service Providers
             */
            foreach ($this->providers as $namespace) {
                $this->app->register($namespace);
            }

            // Load Library translations
            $this->loadTranslationsFrom(libraryPath('resources/lang'), 'accio');

            // Load Library views
            $this->loadViewsFrom(libraryPath('resources/views'), 'accio');


            $this->mapRoutes();

            /**
             * Register & Boot Plugins
             */
            $plugins = new Plugin();
            $plugins->autoloadPlugins();
            $plugins->registerPlugins();
            $plugins->bootPlugins();
            $plugins->addViewsPaths();

            // Load Plugin translations
            foreach($plugins->activePlugins() as $plugin){
                $this->loadTranslationsFrom($plugin->translationsPath(), $plugin->namespaceWithDot());
            }

            Event::fire('system:boot', [$this]);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register(){

        /**
         * Merge configurations
         * Config::get('accio.test')
         */
        $this->mergeConfigFrom(
            __DIR__.'/config/app.php', 'accio'
        );

        /**
         * Register aliases
         */
        $aliasLoader = AliasLoader::getInstance();
        foreach ($this->aliases as $name=>$namespace){
            $aliasLoader->alias($name, $namespace);
        }

        /**
         * Bind classes
         */
        foreach ($this->bindings as $name=>$namespace){
            $this->app->bind($name, function($namespace){
                return $this->app->make($namespace);
            });
        }

        Event::fire('system:register', [$this]);
    }

    /**
     * Check if app is installed
     * It currently only checks if permalinks table exist
     * @TODO find a better way to detect if app is installed
     */
    public static function isInstalled(){
        if(!File::exists(app()->environmentFilePath()) || !config('app.key') || config('app.key') == 'SomeRandomString'){
            return false;
        }
        return true;
    }
}