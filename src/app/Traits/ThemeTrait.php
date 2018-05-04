<?php
/**
 * Created by PhpStorm.
 * User: sopa
 * Date: 18/08/2017
 * Time: 10:44 PM
 */

namespace Accio\App\Traits;

use App\Models\Settings;
use App\Models\Theme;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Accio\App\Models\SettingsModel;
use Mockery\Exception;
use Riverskies\Laravel\MobileDetect\Facades\MobileDetect;
use Illuminate\Support\Facades\Request;
use Accio\Support\Facades\Meta;
use Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;

trait ThemeTrait
{
    /**
     * Stores Theme configurations for each config file found in current theme
     *
     * @var array $config
     */

    private  static $config = array();

    /**
     * Active theme name
     *
     * @var string self::getActiveTheme()
     */
    protected static $activeTheme;

    /**
     * Get active theme
     *
     * @param string Name of theme directory
     * @return string Returns name of the active theme
     * */
    public function setActiveTheme($themeName = ''){
        Event::fire('theme.before_is_set', [$this]);

        if($themeName){
            if(!self::ifExists($themeName)){
                throw new Exception($themeName . ' Theme  could not be found in file directory.');
            }
        }else{
            // return mobile theme if site is accessed from mobile or tablet
            if(settings('activateMobileTheme') && (MobileDetect::isMobile() || MobileDetect::isTablet()) ){
                $themeName = settings('mobileActiveTheme');
            }else {
                // Theme from settings
                $themeName = settings('activeTheme');

                // Or return default theme of no theme is selected in settings
                if (!$themeName) {
                    $themeName = config('project.defaultTheme');
                }
            }
        }

        // Ensure theme exist
        if(!self::ifExists($themeName)){
            throw new Exception("Theme '$themeName' does not exist in Themes directory!");
        }

        self::$activeTheme = $themeName;

        $this->setConfig();

        Event::fire('theme.after_is_set', [$this]);
    }

    public static function getActiveTheme(){
        return self::$activeTheme;
    }

    /**
     * Get theme configuration
     *
     * @param string $themeDirectory
     *
     * @return array
     */
    public static function getConfig($themeDirectory){
        $getConfigValues = [];

        // make absolute path work
        if(is_dir($themeDirectory)){
            $path = $themeDirectory.'/config.json';
        }else{
            $path = base_path()."/themes/".$themeDirectory.'/config.json';
        }

        if(file_exists($path)){
            $getConfigValues = json_decode(file_get_contents($path), true);
            if(!is_array($getConfigValues)){
                throw new Exception("It seems like your theme ".$path." is not well formatted!");
            }
        }

        return $getConfigValues;
    }

    /**
     * Get theme configuration
     */
    public function setConfig(){
        self::$config = self::getConfig(self::getActiveTheme());
    }

    /**
     * Get a config value from theme
     * @param  string $name Config key
     *
     * @return mixed Returns value of requested config if found, and null if its fine.
     */
    public static function config($name){
        if(isset(self::$config[$name])){
            return self::$config[$name];
        }
    }

    /**
     * Get base path of active theme
     *
     * @return string
     */
    public static function getPath(){
        return base_path()."/themes/".self::getActiveTheme();
    }

    /**
     * Get base path of a specifc theme
     *
     * @param string $themeNamespace
     * @return string
     */
    public static function getPathOf($themeNamespace){
        return base_path()."/themes/".$themeNamespace;
    }

    /**
     *
     */
    public static function getViewsPath(){
        return self::getPath().'/views/';
    }

    /**
     * Get HTTP url of theme
     *
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public static function getUrl(){
        return url('themes/'.self::getActiveTheme());
    }

    /**
     * Get namespace of active theme
     *
     * @return string
     */
    public static function getNamespace(){
        return self::getNamespaceOf(self::config('namespace'));
    }

    /**
     * Get namespace of active theme
     *
     * @return string
     */
    public static function controllersNameSpace(){
        return self::getNamespace().'\\Controllers\\';
    }

    /**
     * Get namespace of a specific theme
     *
     * @param string $themeDirectoryName Theme directory Name
     * @return string|null
     */
    public static function getNamespaceOf($themeDirectoryName){
        if(self::ifExists($themeDirectoryName)) {
            $themeConfig = self::getConfig($themeDirectoryName);
            return "Themes\\" . $themeConfig['namespace'];
        }
        return null;
    }

    /**
     * Check if a theme exist
     *
     * It checks file structure
     *
     * @param string $directoryName Name of directory
     * @return boolean
     */
    public static function ifExists($directoryName){
        return is_dir(self::getPathOf($directoryName));
    }

    /**
     * Get url of a css.
     * If no $filename is given, css url will be returned
     *
     * @param string $fileName
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public static function cssUrl($fileName = ''){
        return url('themes/'.self::getActiveTheme().'/assets/css').($fileName ? '/'.$fileName : "");
    }

    /**
     * Get path of a css.
     * If no $filename is given, css url will be returned
     *
     * @param string $fileName
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public static function cssPath($fileName = ''){
        return base_path('themes/'.self::getActiveTheme().'/assets/css').($fileName ? '/'.$fileName : "");
    }

    /**
     * Get url of a js.
     * If no $filename is given, js url will be returned
     *
     * @param string $fileName
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public static function jsUrl($fileName = ''){
        return url('themes/'.self::getActiveTheme().'/assets/js').($fileName ? '/'.$fileName : "");
    }

    /**
     * Get path of a js.
     * If no $filename is given, js url will be returned
     *
     * @param string $fileName
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public static function jsPath($fileName = ''){
        return base_path('themes/'.self::getActiveTheme().'/assets/js').($fileName ? '/'.$fileName : "");
    }

    /**
     * Get url of a image.
     * If no $filename is given, images url will be returned
     *
     * @param string $fileName
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public static function imageUrl($fileName = ''){
        return url('themes/'.self::getActiveTheme().'/assets/images').($fileName ? '/'.$fileName : "");
    }

    /**
     * Get url of a font.
     * If no $filename is given, fonts url will be returned
     *
     * @param string $fileName
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public static function fontUrl($fileName = ''){
        return url('themes/'.self::getActiveTheme().'/assets/fonts').($fileName ? '/'.$fileName : "");
    }

    /**
     * Check if a view exist in current theme
     * @param $view
     * @return boolean
     */
    public static function viewExists($view){
        return (File::exists(self::getViewsPath() . $view . '.blade.php'));
    }

    /**
     * Get template view
     *
     * @param string $view The name of view file
     * @param object $itemID Extra ID to perform additional check of view. ex {template}-{ID}
     *
     * @return string Returns the template path of active theme
     * */
    public static function view($view, $itemID = null){
        $baseViewsPath = self::getActiveTheme().'/views/'.$view;

        // General By ID: {template}-{ID}.blade.php
        $newView =  '-' . $itemID;
        if ($itemID && self::viewExists($view.$newView)) {
            return $baseViewsPath.$newView;
        }

        // Posts: {template}-{postSlug}.blade.php
        $newView =  '-' . Request::route('postSlug');
        if (Request::route('postSlug') && self::viewExists($view.$newView)) {
            return $baseViewsPath.$newView;
        }

        // Posts By Post Type: {template}-{postTypeSlug}-{postSlug}.blade.php
        $newView = Request::route('postTypeSlug').'-'.Request::route('postSlug');
        if (Request::route('postTypeSlug') && Request::route('postSlug') && self::viewExists($view.$newView)) {
            return $baseViewsPath.$newView;
        }

        // Authors: {template}-{authorSlug}.blade.php
        $newView = '-' . Request::route('authorSlug');
        if(Request::route('authorSlug') && self::viewExists($view.$newView)) {
            return $baseViewsPath.$newView;
        }

        // Tags:  {template}-{tagSlug}.blade.php
        $newView = Request::route('tagSlug');
        if (Request::route('tagSlug') && self::viewExists($view.$newView)) {
            return $baseViewsPath.$newView;
        }

        // Post Type: {template}-{postTypeSlug}.blade.php
        $newView = '-' . Request::route('postTypeSlug');
        if (Request::route('postTypeSlug') && self::viewExists($view.$newView)) {
            return $baseViewsPath.$newView;
        }

        // Category: {template}-{categorySlug}.blade.php
        $viewName = '-' . Request::route('categorySlug');
        if (Request::route('categorySlug') && self::viewExists($view.$viewName)) {
            return $baseViewsPath.$viewName;
        }

        return $baseViewsPath;
    }

    /**
     * Print Theme css as configured on /public/{YOUR THEME NAME}/config/theme.php
     * @param array $files List of css files to be printed
     * @param array $defaultAttributes Default attribute to be assigned to all js files.
     * @return string
     */
    public static function css($defaultAttributes = [], $files = []){
        $html = '';
        if($files){
            $cssFiles = $files;
        }else{
            $cssFiles = self::config('css');
        }

        // mix css
        if(isset(self::config('mix')['styles']) && self::config('mix')['styles']){
            $url =  self::cssUrl(self::config('mix')['styles']) ;
            // Add timestamp to the end of the file, for caching purposes
            if(file_exists(self::cssPath(self::config('mix')['styles']))) {
                $url .= "?".File::lastModified(self::cssPath(self::config('mix')['styles']));
            }
            $html .= '<link href="' . $url .'" ' . Meta::parseAttributes($defaultAttributes) . ' rel="stylesheet" type="text/css">' . "\n";
        }
        // Normal css
        else if ($cssFiles) {
            foreach ($cssFiles as $cssFile) {

                // Attributes
                $attributes = $defaultAttributes;
                if (isset($cssFile['attributes'])) {
                    $attributes = array_merge($defaultAttributes, $cssFile['attributes']);
                }

                // Handle absolute url file name
                if (strpos($cssFile['path'], 'http') || strpos($cssFile['path'], '/')) {
                    $url = $cssFile['path'];
                } else {
                    $url = self::cssUrl($cssFile['path']);

                    // Add timestamp to the end of the file, for caching purposes
                    if(file_exists(self::cssPath($cssFile['path']))) {
                        $url .= "?".File::lastModified(self::cssPath($cssFile['path']));
                    }
                }

                $html .= '<link href="' . $url . '" ' . Meta::parseAttributes($attributes) . ' rel="stylesheet" type="text/css">' . "\n";
            }
        }
        return $html;
    }

    /**
     * Print Theme javascripts as configured on /public/{YOUR THEME NAME}/config/theme.php
     *
     * @param array $defaultAttributes Default attribute to be assigned to all js files.
     * @param array $files List of js files to be printed
     * @return string
     */
    public static function js($defaultAttributes = [], $files = []){
        $html = '';
        if($files){
            $jsFiles = $files;
        }else{
            $jsFiles = self::config('js');
        }

        // Mix js
        if(isset(self::config('mix')['scripts']) && self::config('mix')['scripts']){
            $url = self::jsUrl(self::config('mix')['scripts']);
            // Add timestamp to the end of the file, for caching purposes
            if(file_exists(self::jsPath(self::config('mix')['scripts']))) {
                $url .= "?".File::lastModified(self::jsPath(self::config('mix')['scripts']));
            }
            $html .= '<script src="'. $url .'" '.Meta::parseAttributes($defaultAttributes).' type="text/javascript"></script>'."\n";
        }
        // Normal js
        else if ($jsFiles) {
            foreach ($jsFiles as $jsFile) {
                // Attributes
                $attributes = $defaultAttributes;
                if (isset($cssFile['attributes'])) {
                    $attributes = array_merge($defaultAttributes, $jsFile['attributes']);
                }

                // Handle absolute url file name
                if (strpos($jsFile['path'], 'http') || strpos($jsFile['path'], '/')) {
                    $url = $jsFile['path'];
                } else {
                    $url = self::jsUrl($jsFile['path']);

                    // Add timestamp to the end of the file, for caching purposes
                    if(file_exists(self::jsPath($jsFile['path']))) {
                        $url .= "?".File::lastModified(self::jsPath($jsFile['path']));
                    }
                }

                $html .= '<script src="'.$url .'" '.Meta::parseAttributes($attributes).' type="text/javascript"></script>'."\n";
            }
        }
        return $html;
    }


    /**
     * Gets configs of all themes
     *
     * @return array
     */
    public static function configs(){
        $files = File::allFiles(base_path().'/themes');
        $result = [];
        foreach ($files as $file){
            if($file->getBasename() == "config.json"){
                $result[] = self::getConfig($file->getPath());
            }
        }
        return $result;
    }

}