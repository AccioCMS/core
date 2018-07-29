<?php
/**
 * Created by PhpStorm.
 * User: sopa
 * Date: 18/09/2017
 * Time: 10:32 AM
 */

namespace Accio\App\Traits;

use App\Models\Plugin;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Directory;
use Mockery\Exception;

trait PluginTrait
{

    /**
     * Registered Plugins
     * @var array
     */
    protected $registeredPlugins = [];

    /**
     * Booted Plugins
     * @var array
     */
    protected $bootedPlugins = [];

    /**
     * Plugin data
     * @var object $plugin
     */
    protected static $pluginData;

    /**
     * Get plugin data
     * @param null $namespace
     * @return PluginTrait|null|object
     * @throws Exception
     */
    public function getData($namespace = null){
        if(!self::$pluginData) {
            // try to find the model via namespace
            if (!$namespace) {
                // if we are in a model
                if (property_exists($this, 'namespace')) {
                    self::$pluginData = $this;
                } /// When calling the method from a parent class that uses this class as a parent
                else {
                    // try to find plugin's model via reflection class
                    try{
                        $reflector = new \ReflectionClass(self::class);
                        $fn = $reflector->getFileName();
                        $explode = explode('plugins/', str_replace('\\','/', dirname($fn)));
                        if (isset($explode[1])) {
                            $pluginNamespace = str_replace('/Controllers', '', $explode[1]);
                            self::$pluginData = Plugin::cache()->where('namespace', $pluginNamespace)->collect()->first();
                        }

                    }catch(\Exception $e){
                        return null;
                    }
                }
            } // Return a requested plugin
            else {
                self::$pluginData = $this->getByNamespace($namespace);
            }
        }

        if (!self::$pluginData) {
            return null;
        }

        return self::$pluginData;
    }

    /**
     * Get base path of the plugin
     *
     * @return string
     */
    public function basePath(){
        return pluginsPath(str_replace("\\","/",$this->namespace));
    }

    /**
     * Get resources path of the plugin
     *
     * @return string
     */
    public function resourcesPath(){
        return $this->basePath().'/resources';
    }

    /**
     * Get translation path of the plugin
     *
     * @return string
     */
    public function translationsPath(){
        return $this->resourcesPath().'/lang';
    }

    /**
     * Get resources path of the plugin
     *
     * @param string $path
     * @return string
     */
    public function viewsPath($path = ''){
        //'plugins.'.str_replace("\\","/",$this->namespace).'.resources.views'.($path ? '/'.$path : '');
        return $this->resourcesPath().'/views'.($path ? '/'.$path : '');
    }

    /**
     * Get assets path of the plugin
     *
     * @return string
     */
    public function assetsPath(){
        return $this->resourcesPath().'/assets';
    }

    /**
     * Get config of a plugin
     *
     * @param string $namespace
     * @return array
     */
    public static function config($namespace){
        $configPath = pluginsPath($namespace.'/config.json');
        if(file_exists($configPath)){
            return json_decode(File::get($configPath));
        }
        throw  new \Exception("No config.json file found for plugin ".$configPath);
    }

    public function parseNamespace(){
        return "Plugins\\".str_replace("/","\\",$this->namespace);
    }

    /**
     * Get All plugins from plugins directory
     *
     * Plugins are listed by author
     *
     * @return array
     */
    public static function activePlugins(){
        $plugins = self::cache();
        // Plugin table must exist first
        if($plugins){
            return $plugins->where('isActive', true)->collect();
        }
        return [];
    }

    /**
     * Get Route name prefix of a plugin
     * @return string
     */
    public function namespaceWithDot(){
        return str_replace(["\\",'/'],".",$this->namespace);
    }

    /**
     * Get Plugin's names pace and replace slashes with underlnes
     * @return string
     */
    public function namespaceWithUnderline(){
        return str_replace(["\\",'/'],"_",$this->namespace);
    }

    /**
     * Full backend URL
     * @return string
     */
    public function fullBackendUrl(){
        return url(Config::get('project')['adminPrefix'].'/'.App::getLocale()."/plugins/".str_replace("\\","/",self::config($this->namespace)->baseURL));
    }

    /**
     * Get backend url prefix
     * @return string
     */
    public function backendURLPrefix(){
        return "{lang}/plugins/".str_replace("\\","/",self::config($this->namespace)->baseURL);
    }

    /**
     * Get routes of a plugin
     * @return array
     */
    public function backendRoutes(){
        $path = $this->basePath() . '/routes/backend';
        if($this->isActive && file_exists($path)){
            return File::files($path);
        }
        return [];
    }

    /**
     * Get routes of a plugin
     * @return array
     */
    public function frontendRoutes(){
        $path = $this->basePath() . '/routes/frontend';
        if($this->isActive() && file_exists($path)) {
            return File::files($path);
        }
        return [];
    }

    /**
     * Autoload all active plugins
     * @return void
     */
    public function autoloadPlugins(){
        foreach(self::activePlugins() as $plugin){
            // plugin composer autoload
            if(File::exists($plugin->basePath().'/vendor/autoload.php')) {
                require $plugin->basePath() . '/vendor/autoload.php';
            }
            // plugin helpers
            if(File::exists($plugin->basePath().'/support/helpers.php')) {
                require $plugin->basePath() . '/support/helpers.php';
            }
        }
        return;
    }

    /**
     * Register all active plugins
     *
     * @return void
     */
    public function registerPlugins(){
        foreach($this->activePlugins() as $plugin){
            $className = $plugin->parseNamespace()."\\Plugin";
            if(class_exists($className)) {
                $pluginInstance = new $className();
                if ($plugin->isActive() && method_exists($pluginInstance, 'register')) {
                    $pluginInstance->register();
                    $this->registeredPlugins[] = $plugin;
                }
            }
        }
        return;
    }

    /**
     * Boot all active plugins
     *
     * @return void
     */
    public function bootPlugins(){
        foreach($this->activePlugins() as $plugin){
            $className = $plugin->parseNamespace()."\\Plugin";
            if(class_exists($className)) {
                $pluginInstance = new $className();
                if ($plugin->isActive() && method_exists($pluginInstance, 'boot')) {
                    $pluginInstance->boot();
                    $this->bootedPlugins[] = $plugin;
                }
            }
        }
        return;
    }

    /**
     * Get all registered plugins
     *
     * @return array
     */
    public function getRegisteredPlugins(){
        return $this->registeredPlugins;
    }

    /**
     * Get all registered plugins
     *
     * @return array
     */
    public function getBootedPlugins(){
        return $this->bootedPlugins;
    }

    /**
     * Gets the config.php file of each plugins and returns their values as multidimensional array
     *
     * @return array of file config
     */
    public static function configs(){
        $files = File::allFiles(base_path().'/plugins');
        $result = [];
        foreach ($files as $file){
            if($file->getBasename() == "config.json"){
                $result[] = json_decode(File::get($file->getPathname()));
            }
        }
        return $result;
    }

    /**
     * Get plugin's panel data from Request/Form
     * @param string $panelKey Name of the panel where dhe data should be requested from
     * @param string $field
     * @return mixed
     */
    public function getPanelData($panelKey, $field = ''){
        $request = Request::instance();

        if(isset($request->pluginsData[$panelKey])) {
            $panel = $request->pluginsData[$panelKey];
            if($field){
                if (isset($panel[$field])) {
                    return $panel[$field];
                }
            }else{
                return $panel;
            }
        }

        return;
    }

    /**
     * Get all plugins'
     * @return array
     */
    public static function getAllLabels(){
        // Load Plugin translations
        $labels = [];
        foreach(self::activePlugins() as $plugin){
            if(File::isDirectory($plugin->translationsPath().'/'.App::getLocale())) {
                $translationFiles = File::files($plugin->translationsPath() . '/' . App::getLocale());
                foreach ($translationFiles as $file) {
                    $fileName = str_replace('.php', '', $file->getFilename());

                    $pluginNamespace = explode(".", $plugin->namespaceWithDot());

                    // set a array key for each plugin author
                    if(!isset($labels[$pluginNamespace[0]])){
                        $labels[$pluginNamespace[0]] = [];
                    }
                    // set a array key for each plugin
                    if(!isset($labels[$pluginNamespace[0]][$pluginNamespace[1]])){
                        $labels[$pluginNamespace[0]][$pluginNamespace[1]] = [];
                    }

                    $labels[$pluginNamespace[0]][$pluginNamespace[1]][$fileName] = File::getRequire($file->getPathName());
                }
            }
        }

        return $labels;
    }

    /**
     * Get a plugin namespace
     * @param $namespace
     * @return object|null
     */
    public static function getByNamespace($namespace){
        $cachedPlugin = Plugin::cache()->where('namespace', $namespace)->collect()->first();
        if($cachedPlugin){
            return $cachedPlugin;
        }
        return null;
    }
    /**
     * Check if a plugin is installed
     *
     * @param $namespace
     * @return bool
     */
    public static function isInstalled($namespace){

        // Exists as a directory
        if(!File::isDirectory(pluginsPath($namespace))){
            return false;
        }

        // Exists as a table plugin
        if(!self::getByNamespace($namespace)){
            return false;
        }

        return true;
    }

    /**
     * Check if a plugin is active
     *
     * @return bool
     */
    public function isActive(){
        // wee need a namespace first
        if(!isset($this->namespace)){
            return false;
        }

        // Plugin is not active is it's not installed
        if(!self::isInstalled($this->namespace)){
            return false;
        }

        if(!$this->isActive){
            return false;
        }
        return true;
    }

    /**
     * Add plugins views paths so they are accessible via views("Author.PluginName::viewName")
     * return void
     */
    public function addViewsPaths(){
        foreach($this->activePlugins() as $plugin){
            view()->addNamespace($plugin->namespaceWithDot(), $plugin->viewsPath());
        }
        return;
    }
}