<?php

if (! function_exists('assetUrl')) {

    /**
     * Prints url to the assets directory, if filename is not empty it will be added to the result string
     * @param string $fileName
     * @return string
     */
    function assetUrl(string $fileName = ''){
        $assets = url('themes/'.\App\Models\Theme::getActiveTheme().'/assets');
        if($fileName){
            $assets .= "/".$fileName;
        }
        return $assets;
    }
}

if (! function_exists('css')) {

    /**
     * Print Theme css as configured on /public/{YOUR THEME NAME}/config/theme.php
     *
     * @param true $header where we are printing header or footer css
     * @param array $files List of css files to be printed
     * @param array $defaultAttributes Default attribute to be assigned to all js files.
     * @param bool $noScript True if css should be appended within a <noscript> tag
     * @return string
     */
    function css($header = true, $defaultAttributes = [], $files = [], $noScript = false){
        return \App\Models\Theme::css($header, $defaultAttributes, $files, $noScript);
    }
}

if (! function_exists('cssUrl')) {

    /**
     * Prints url to the css file with the providen filename
     * If no file name is provided prints the directory of the css
     * @param string $fileName
     * @return string
     */
    function cssUrl(string $fileName = ''){
        return \App\Models\Theme::cssUrl($fileName);
    }
}


if (! function_exists('imageUrl')) {

    /**
     * Prints url to the image file with the providen filename
     * If no file name is provided prints the directory of the image
     * @param string $fileName
     * @return string
     */
    function imageUrl(string $fileName = ''){
        return \App\Models\Theme::imageUrl($fileName);
    }
}

if (! function_exists('jsUrl')) {

    /**
     * Prints url to the js file with the providen filename
     * If no file name is provided prints the directory of the js
     * @param string $fileName
     * @return string
     */
    function jsUrl(string $fileName = ''){
        return \App\Models\Theme::jsUrl($fileName);
    }
}

if (! function_exists('fontUrl')) {

    /**
     * Prints url to the font file with the providen filename
     * If no file name is provided prints the directory of the font
     * @param string $fileName
     * @return string
     */
    function fontUrl(string $fileName = ''){
        return \App\Models\Theme::fontUrl($fileName);
    }
}


if (! function_exists('js')) {

    /**
     * Print Theme javascripts as configured on /public/{YOUR THEME NAME}/config/theme.php
     *
     * @param true $header where we are printing header or footer js
     * @param array $defaultAttributes Default attribute to be assigned to all js files.
     * @param array $files List of js files to be printed
     * @return string
     */
    function js($header = true, $defaultAttributes = [], $files = []){
        return \App\Models\Theme::js($header, $defaultAttributes, $files);
    }
}

if(!function_exists('menu')){
    /**
     * Prints MenuLinks of a menu
     *
     * @param string $menuSlug Slug of Menu
     * @param string $customView Name of a custom blade.php file to render the template
     * @param string $ulClass Class of ul
     * @return \Illuminate\Support\HtmlString Returns an html navigation of a particular menu
     */
    function menu($menuSlug = "primary", $customView = '', $ulClass = ''){
        return \App\Models\Menu::printMenu($menuSlug, $customView, $ulClass);
    }
}

if(!function_exists('languages')){
    /**
     * Print list of languages
     *
     * @param string $customView Name of a custom blade file to render the template
     * @param string $ulClass Class of ul
     *
     * @return \Illuminate\Support\HtmlString
     */
    function languages($customView= '', $ulClass=''){
        return \App\Models\Language::printLanguages($customView, $ulClass);
    }
}

if(!function_exists('searchForm')){
    /**
     * Get a search form
     * @param string $customView Name of a custom blade.php file to render the template
     * @param string $formClass Serch form class
     *
     * @return \Illuminate\Support\HtmlString|string
     */
    function searchForm($customView ='', $formClass=""){
        return \Accio\Support\Facades\Search::printSearchForm($customView, $formClass);
    }
}

if(!function_exists('getLocale')) {
    /**
     * Get locale
     * @return mixed
     */
    function getLocale(){
        return \Illuminate\Support\Facades\App::getLocale();
    }
}
if(!function_exists('noImage')) {
    /**
     * Get no image.
     * Used in cases where there is not featured image set
     *
     * @param string $imagePath
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    function noImage($imagePath = 'no-image-default.png'){
        return \App\Models\Theme::imageUrl($imagePath);
    }
}

if(!function_exists('searchKeyword')) {
    /**
     * Get search keyword
     * @return string
     */
    function searchKeyword(){
        return \Accio\Support\Facades\Search::getKeyword();
    }
}

if(!function_exists('projectDirectory')) {
    /**
     * Get directory of the project
     * @return string
     */
    function projectDirectory(){
        $splitRoot = explode(request()->getHost(), Request::root());
        if(isset($splitRoot[1])){
            return $splitRoot[1];
        }

        return "/";
    }
}

if(!function_exists('settings')) {
    /**
     * Get a project settings
     * @param string $key
     * @return string
     */
    function settings($key){
        return \App\Models\Settings::getSetting($key);
    }
}

if(!function_exists('googleAnalytics')) {
    /**
     * Get a project settings
     * @param string $trackingID
     * @return string
     */
    function googleAnalytics($trackingID = ''){
        $trackingID = ($trackingID ? $trackingID : settings('trackingCode'));
        if($trackingID){
            return new \Illuminate\Support\HtmlString(view()->make("vendor.general.googleAnalytics", [
                'trackingID' => $trackingID
            ])->render());
        }
    }
}

if(!function_exists('googleTagManager')) {
    /**
     * Get a project settings
     * @param string $containerID
     * @return string
     */
    function googleTagManager($containerID = ''){
        $containerID = ($containerID ? $containerID : settings('tagManager'));
        if($containerID){
            return new \Illuminate\Support\HtmlString(view()->make("vendor.general.googleTagManagerHead", [
                'containerID' => $containerID
            ])->render());
        }
    }
}

if(!function_exists('googleTagManagerBody')) {
    /**
     * Get a project settings
     * @param string $containerID
     * @return string
     */
    function googleTagManagerBody($containerID = ''){
        $containerID = ($containerID ? $containerID : settings('tagManager'));
        if($containerID){
            return new \Illuminate\Support\HtmlString(view()->make("vendor.general.googleTagManagerBody", [
                'containerID' => $containerID
            ])->render());
        }
    }
}

if(!function_exists('metaTags')) {
    /**
     * Get meta tags and prints them
     * @param object $post
     * @param array $customData
     * @return string
     */
    function metaTags($modelData = null, $customData = []){
        if(\Accio\Support\Facades\Meta::getMetaIsPrinted()){
            return;
        }

        $currentMenuLink =  \App\Models\MenuLink::getActive();

        // Set model data
        if($modelData) {
            \Accio\Support\Facades\Meta::setModelData($modelData);

            // Get model's own meta data
            if(method_exists($modelData, 'metaData')){
                $modelData->metaData();
            }
        }else{
            $modelData = $currentMenuLink;
        }

        // Set title
        $title = null;
        if(isset($customData['title'])){
            $title = $customData['title'];
        }elseif($modelData && property_exists($modelData, 'title')){
            $title = $modelData->title;
        }elseif($currentMenuLink && $currentMenuLink->label){
            $title = $currentMenuLink->label;
        }

        if($title){
            \Accio\Support\Facades\Meta::setTitle($title);
        }

        // Set description
        $description = null;
        if(isset($customData['description'])){
            $description = $customData['description'];
        }elseif($modelData && property_exists($modelData, 'description')){
            $description = $modelData->description;
        }

        if($description){
            $this->set('description', $description);
        }

        // Get meta data from events
        event('meta:add', [$modelData]);

        \Accio\Support\Facades\Meta::printMetaTags();
    }
}

if(!function_exists('homepage')) {

    /**
     * Get the data of Homepage
     *
     * @param string $columnName Column of page to be returned
     *
     * @return array|null Returns the data of the primary Menu if found, null instead
     */
    function homepage($columnName = ''){
        return \App\Models\Post::getHomepage($columnName);
    }
}

function uploadsPath($extraPath = null){
    return config('filesystems.disks.public.path').($extraPath ? "/".$extraPath : '');
}
function uploadsURL($filePath){
    return Storage::disk('public')->url($filePath);
}

if(!function_exists('noPostTypeSlug')) {
    /**
     * Remove 'post_' from post type slug
     * @param $postTypeSlug
     * @param string $replaceWith
     * @return mixed
     */
    function cleanPostTypeSlug($postTypeSlug, $replaceWith = ''){
        return str_replace('post_',$replaceWith,$postTypeSlug);
    }
}

if(!function_exists('permalink')) {
    /**
     * Get a permalink by name
     *
     * @param $belongsTo
     * @param $name
     * @param string $defaultURL
     * @return string
     */
    function permalink($belongsTo, $name, $defaultURL = '')
    {
        return \App\Models\Permalink::getByName($belongsTo, $name, $defaultURL);
    }
}

if(!function_exists('stubPath')) {
    /**
     * Get stub Path-
     * @param string $path
     * @param bool $stubExtension True if .stub extension should be added in the given path
     * @return string
     */
    function stubPath($path, $stubExtension = true)
    {
        return base_path() . '/vendor/acciocms/core/src/support/stubs/' . $path .($stubExtension ? '.stub' : '');
    }
}

if(!function_exists('routeIsActive')) {
    /**
     * Check if a route is active
     *
     * @param $routeName
     * @param string $className
     * @return bool|string
     */
    function routeIsActive($routeName, $className = ""){
        $currentRouteName = str_replace('.default', '' ,\Request::route()->getName());
        if($currentRouteName == $routeName){
            if($className){
                return $className;
            }
            return false;
        }else{
            if($className){
                return '';
            }
            return true;
        }
    }
}

if(!function_exists('authControllerExist')) {
    /**
     * Checks if auth controller exist
     * This is used to check if user account routes shall be defined and if login/register links should be shown in Default Theme
     * @return bool
     */
    function authControllerExist()
    {
        return (\Illuminate\Support\Facades\File::exists(\App\Models\Theme::getPath() . '/controllers/Auth/'));
    }
}

if (! function_exists('error404')) {

    /**
     * Error 404
     * @return \Illuminate\Http\Response
     */
    function error404(){
        return response()->view(\App\Models\Theme::view('errors/404'), [], 404);
    }
}


if (! function_exists('pluginsPath')) {
    /**
     * Error 404
     * @return \Illuminate\Http\Response
     */
    function pluginsPath($path = ''){
        $path = str_replace('\\','/',$path);
        return base_path().'/plugins'.($path ? '/'.$path : "");
    }
}


if (! function_exists('tmpPath')) {
    /**
     * tmp path 404
     * @param string $path
     * @return \Illuminate\Http\Response
     */
    function tmpPath($path = ''){
        return storage_path('tmp'.($path ? '/'.$path : ""));
    }
}

if (! function_exists('accioPath')) {
    /**
     * Manafarra CMS path
     *
     * @param string $path
     * @return string
     */
    function accioPath($path = ''){
        return base_path('vendor/acciocms/core/src'.($path ? '/'.$path : ""));
    }
}

if (! function_exists('findPostByID')) {
    /**
     * Find a post by its ID
     *
     * @param int $postID
     * @param string $postTypeSlug
     * @return object
     */
    function findPostByID(int $postID, string $postTypeSlug = ''){
        return \App\Models\Post::findByID($postID, $postTypeSlug);
    }
}

if (! function_exists('findPostBySlug')) {
    /**
     * Find a post by its slug
     *
     * @param string $slug
     * @param string $postTypeSlug
     * @return object
     */
    function findPostBySlug(string $slug, string $postTypeSlug = ''){
        return \App\Models\Post::findBySlug($slug, $postTypeSlug);
    }
}

if (! function_exists('shareUrl')) {
    /**
     * Get all links to share content in social media
     *
     * @param string $url to be shared
     * @param string $title (optional) if you want to append text to share
     * @return array all social links
     */
    function shareUrl(string $url, string $title = ''){
        $links = [];
        $links['facebook'] = "https://www.facebook.com/sharer.php?u=" . $url;
        $links['twitter'] = "https://twitter.com/intent/tweet?url={$url}&text={$title}";
        $links['google'] = "https://plus.google.com/share?url={$url}&text={$title}";
        $links['linkedin'] = "https://www.linkedin.com/shareArticle?mini=true&url={$url}&title={$title}";
        $links['viber'] = "viber://forward?text={$url}";
        $links['whatsapp'] = "whatsapp://send?text={$url}";
        return $links;
    }
}


if (! function_exists('getPostType')) {
    /**
     * Get post type object
     *
     * @param string $postType without the post_ prefix
     * @return mixed
     */
    function getPostType(string $postType){
        // post type of pages
        return \App\Models\PostType::findBySlug($postType);
    }
}
if (! function_exists('isSecure')) {
    /**
     * check whether the site is opened via https or no
     *
     * @param $request
     * @return bool
     */
    function isHttps(){
        return request()->headers->get('x-forwarded-proto') == 'https' ? true: false;
    }
}


