<?php

namespace Accio;

use Accio\App\Commands\Deploy\ActivateNewReleaseAfter;
use Accio\App\Commands\Deploy\ActivateNewReleaseBefore;
use Accio\App\Commands\Deploy\PurgeOldReleaseAfter;
use Accio\App\Commands\Deploy\PurgeOldReleaseBefore;
use Accio\App\Commands\Deploy\CopyUploads;
use Accio\App\Commands\Deploy\CreateSymlinks;
use Accio\App\Commands\Deploy\Cronjobs;
use Accio\App\Commands\Deploy\Database;
use Accio\App\Commands\Deploy\EnvFile;
use Accio\App\Commands\Deploy\SetPermissions;
use Accio\App\Commands\DeployCron;
use Accio\App\Commands\PluginUpdate;
use Accio\App\Commands\PostCreateProject;
use Accio\App\Commands\SetWritePermissions;
use Accio\App\Services\Routes;
use App\Models\Plugin;
use App\Models\Theme;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\File;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Accio\App\Commands\CheckRequirements;
use Accio\App\Commands\DBDumper;
use Accio\App\Commands\MakeDummy;
use Accio\App\Commands\AccioInstall;
use Accio\App\Commands\AccioUninstall;
use Accio\App\Commands\MakeTheme;
use Accio\App\Commands\MakeUser;
use Accio\App\Commands\PluginInstall;

class PackageServiceProvider extends ServiceProvider
{

    /**
     * List Package Service Providers
     * Example: 'Accio\App\Providers\ClassNameServiceProvider',
     *
     * @var array
     */
    protected $providers = [];

    /**
     * List Package bindings
     * Example: 'ClassName' => 'Accio\App\Services\ClassName',
     *
     * @var array
     */
    public $bindings = [];

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
      MakeDummy::class,
      MakeUser::class,
      DBDumper::class,
      MakeTheme::class,
      CheckRequirements::class,
      AccioInstall::class,
      AccioUninstall::class,
      PluginInstall::class,
      PluginUpdate::class,
      SetWritePermissions::class,
      PostCreateProject::class,
      CopyUploads::class,
      Cronjobs::class,
      Database::class,
      EnvFile::class,
      CreateSymlinks::class,
      ActivateNewReleaseAfter::class,
      ActivateNewReleaseBefore::class,
      PurgeOldReleaseBefore::class,
      PurgeOldReleaseAfter::class
    ];

    /**
     * List Package Aliases
     * Example: 'ClassName' => 'Accio\App\Services\ClassName',
     *
     * @var array
     */
    protected $aliases = [
      'AccioQuery' => 'Accio\App\Services\AccioQuery',
      'Pagination' => 'Accio\App\Services\Pagination',
      'Routes' => 'Accio\App\Services\Routes',
      'Search' => 'Accio\App\Services\Search',
      'Meta' => 'Accio\App\Services\Meta',
    ];

    /**
     * Define the "web" routes for the application..
     *
     * @throws \Exception
     */
    protected function mapRoutes()
    {
        if (!$this->app->routesAreCached()) {
            Route::group(
                ['middleware' => ['web']], function () {
                    $routes = new Routes();

                    // Backend Routes
                    $routes->mapBackendRoutes()
                        ->mapPluginsBackendRoutes();

                    // Frontend Routes
                    $routes->mapFrontendBaseRoutes()
                        ->mapFrontendRoutes()
                        ->mapThemeRoutes()
                        ->mapPluginsFrontendRoutes();

                    // Add Language {lang} prefix
                    $routes->addLanguagePrefix()
                        ->sortRoutes();
                }
            );
        }
    }

    /**
     * Format https scheme
     *
     * @param $url
     */
    private function forceHTTPSScheme($url)
    {
        if(env('FORCE_HTTPS_SCHEME')) {
            $url->formatScheme('https');
            $this->app['request']->server->set('HTTPS', true);
        }
    }

    /**
     * Boot Accio.
     *
     * @param  UrlGenerator $url
     * @throws \Exception
     */
    public function boot(UrlGenerator $url)
    {
        /**
         * Register commands, so you may execute them using the Artisan CLI.
         */
        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }

        /*
         * redirect http to https
         */
        $this->forceHTTPSScheme($url);

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

            /**
             * Register Service Providers
             */
            foreach ($this->providers as $namespace) {
                $this->app->register($namespace);
            }

            // Load Library translations
            $this->loadTranslationsFrom(accioPath('resources/lang'), 'accio');

            // Load Library views
            $this->loadViewsFrom(accioPath('resources/views'), 'accio');


            // Map routes
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

            // Load Theme views
            $this->loadViewsFrom(Theme::getPath().'/'.'views', Theme::config('namespace'));

            Event::fire('system:boot', [$this]);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {

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
            $this->app->bind(
                $name, function ($namespace) {
                    return $this->app->make($namespace);
                }
            );
        }

        Event::fire('system:register', [$this]);
    }


    /**
     * Check if app is installed
     * It currently only checks if permalinks table exist
     *
     * @TODO find a better way to detect if app is installed
     */
    public static function isInstalled()
    {
        if(!File::exists(app()->environmentFilePath()) || !config('app.key') || config('app.key') == 'SomeRandomString') {
            return false;
        }
        return true;
    }
}
