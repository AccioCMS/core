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
                throw new \Exception($themeName . ' Theme  could not be found in file directory.');
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
            throw new \Exception("Theme '$themeName' does not exist in Themes directory!");
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
                throw new \Exception("It seems like your theme ".$path." is not well formatted!");
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
            if(isset($themeConfig['namespace'])) {
                return "Themes\\" . $themeConfig['namespace'];
            }
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
        return ($directoryName && is_dir(self::getPathOf($directoryName)));
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
     * @param true $header where we are printing header or footer css
     * @param array $files List of css files to be printed
     * @param array $defaultAttributes Default attribute to be assigned to all js files.
     * @param bool $noScript True if css should be appended within a <noscript> tag
     * @return string
     */
    public static function css($header = true, $defaultAttributes = [], $files = [], $noScript = false){
        $html = '';
        if($files){
            $files = $files;
        }else{
            $files = self::config('css');
        }

        if ($files) {

            foreach ($files as $file) {
                // exclude merge files
                if (isset($file['merge']) && $file['merge']) {
                    continue;
                }

                // exclude header
                if ($header) {
                    if(isset($file['showInHeader']) && !$file['showInHeader']) {
                        continue;
                    }
                }else{ // exclude footer css
                    if(!isset($file['showInHeader']) || (isset($file['showInHeader']) && $file['showInHeader'])) {
                        continue;
                    }
                }

                // Attributes
                $attributes = $defaultAttributes;
                if (isset($file['attributes'])) {
                    $attributes = array_merge($defaultAttributes, $file['attributes']);
                }

                if(self::isInline($file)) {
                    if (file_exists(self::cssPath($file['path']))) {
                        $html .= '<style type="text/css">' . Meta::parseAttributes($attributes) . ''.File::get(self::cssPath($file['path'])).'</style>' . "\n";
                    }
                }else{
                    // Handle absolute url file name
                    if (strpos($file['path'], 'http') || strpos($file['path'], '/')) {
                        $url = $file['path'];
                    } else {
                        $url = self::cssUrl($file['path']);

                        // Add timestamp to the end of the file, for caching purposes
                        if (file_exists(self::cssPath($file['path']))) {
                            $url .= "?" . File::lastModified(self::cssPath($file['path']));
                        }
                    }

                    $html .= '<link href="' . $url . '" ' . Meta::parseAttributes($attributes) . ' rel="stylesheet" type="text/css">' . "\n";
                }
            }

            // append <noscript>
            if($html && $noScript){
                $html = '<noscript id="deferred-styles">'.$html.'</noscript>';
            }
        }
        return $html;
    }

    /**
     * Check whether a css or js should be printed as inline
     *
     * @param array $file
     * @return boolean
     */
    private static function isInline($file){
        if(isset($file['inline']) && $file['inline']){
            return true;
        }
        return false;
    }

    /**
     * Print Theme javascripts as configured on /public/{YOUR THEME NAME}/config/theme.php
     *
     * @param true $header where we are printing header or footer js
     * @param array $defaultAttributes Default attribute to be assigned to all js files.
     * @param array $files List of js files to be printed
     * @return string
     */
    public static function js($header = true, $defaultAttributes = [], $files = []){
        $html = '';
        if($files){
            $files = $files;
        }else{
            $files = self::config('js');
        }
        if ($files) {
            foreach ($files as $file) {
                // exclude merge files
                if (isset($file['merge']) && $file['merge']) {
                    continue;
                }

                // exclude header
                if ($header) {
                    if(isset($file['showInHeader']) && !$file['showInHeader']) {
                        continue;
                    }
                }else{ // exclude footer css
                    if(!isset($file['showInHeader']) || (isset($file['showInHeader']) && $file['showInHeader'])) {
                        continue;
                    }
                }

                // Attributes
                $attributes = $defaultAttributes;
                if (isset($file['attributes'])) {
                    $attributes = array_merge($defaultAttributes, $file['attributes']);
                }

                if(self::isInline($file)) {
                    if (file_exists(self::jsPath($file['path']))) {
                        $html .= '<script type="text/javascript" ' . Meta::parseAttributes($attributes) . '>'.File::get(self::jsPath($file['path'])).'</script>' . "\n";
                    }
                }else {
                    // Handle absolute url file name
                    if (strpos($file['path'], 'http') || strpos($file['path'], '/')) {
                        $url = $file['path'];
                    } else {
                        $url = self::jsUrl($file['path']);

                        // Add timestamp to the end of the file, for caching purposes
                        if (file_exists(self::jsPath($file['path']))) {
                            $url .= "?" . File::lastModified(self::jsPath($file['path']));
                        }
                    }

                    $html .= '<script src="' . $url . '" ' . Meta::parseAttributes($attributes) . ' type="text/javascript"></script>' . "\n";
                }
            }
        }
        return $html;
    }


    /**
     * Gets configs of all themes
     *
     * @return array
     * @throws \Exception
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