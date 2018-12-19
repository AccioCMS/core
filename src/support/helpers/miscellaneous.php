<?php


if (! function_exists('css')) {

    /**
     * Print Theme css as configured on /public/{YOUR THEME NAME}/config/theme.php
     *
     * @param  true  $header            where we are printing header or footer css
     * @param  array $files             List of css files to be printed
     * @param  array $defaultAttributes Default attribute to be assigned to all js files.
     * @param  bool  $noScript          True if css should be appended within a <noscript> tag
     * @return string
     */
    function css($header = true, $defaultAttributes = [], $files = [], $noScript = false)
    {
        return \App\Models\Theme::css($header, $defaultAttributes, $files, $noScript);
    }
}


if (! function_exists('js')) {

    /**
     * Print Theme javascripts as configured on /public/{YOUR THEME NAME}/config/theme.php
     *
     * @param  true  $header            where we are printing header or footer js
     * @param  array $defaultAttributes Default attribute to be assigned to all js files.
     * @param  array $files             List of js files to be printed
     * @return string
     */
    function js($header = true, $defaultAttributes = [], $files = [])
    {
        return \App\Models\Theme::js($header, $defaultAttributes, $files);
    }
}

if(!function_exists('menu')) {
    /**
     * Prints MenuLinks of a menu
     *
     * @param  string $menuSlug   Slug of Menu
     * @param  string $customView Name of a custom blade.php file to render the template
     * @param  string $ulClass    Class of ul
     * @return \Illuminate\Support\HtmlString Returns an html navigation of a particular menu
     */
    function menu($menuSlug = "primary", $customView = '', $ulClass = '')
    {
        return \App\Models\Menu::printMenu($menuSlug, $customView, $ulClass);
    }
}

if(!function_exists('languages')) {
    /**
     * Print list of languages
     *
     * @param string $customView Name of a custom blade file to render the template
     * @param string $ulClass    Class of ul
     *
     * @return \Illuminate\Support\HtmlString
     */
    function languages($customView= '', $ulClass='')
    {
        return \App\Models\Language::printLanguages($customView, $ulClass);
    }
}

if(!function_exists('searchForm')) {
    /**
     * Get a search form
     *
     * @param string $customView Name of a custom blade.php file to render the template
     * @param string $formClass  Serch form class
     *
     * @return \Illuminate\Support\HtmlString|string
     */
    function searchForm($customView ='', $formClass="")
    {
        return \Accio\Support\Facades\Search::printSearchForm($customView, $formClass);
    }
}

if(!function_exists('getLocale')) {
    /**
     * Get locale
     *
     * @return mixed
     */
    function getLocale()
    {
        return \Illuminate\Support\Facades\App::getLocale();
    }
}
if(!function_exists('noImage')) {
    /**
     * Get no image.
     * Used in cases where there is not featured image set
     *
     * @param  string $imagePath
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    function noImage($imagePath = 'no-image-default.png')
    {
        return \App\Models\Theme::imageUrl($imagePath);
    }
}

if(!function_exists('searchKeyword')) {
    /**
     * Get search keyword
     *
     * @return string
     */
    function searchKeyword()
    {
        return \Accio\Support\Facades\Search::getKeyword();
    }
}


if(!function_exists('settings')) {
    /**
     * Get a project settings
     *
     * @param  string $key
     * @return string
     */
    function settings($key)
    {
        return \App\Models\Settings::getSetting($key);
    }
}

if(!function_exists('googleAnalytics')) {
    /**
     * Get a project settings
     *
     * @param  string $trackingID
     * @return string
     */
    function googleAnalytics($trackingID = '')
    {
        $trackingID = ($trackingID ? $trackingID : settings('trackingCode'));
        if($trackingID) {
            return new \Illuminate\Support\HtmlString(
              view()->make(
                "vendor.general.googleAnalytics", [
                  'trackingID' => $trackingID
                ]
              )->render()
            );
        }
    }
}

if(!function_exists('googleTagManager')) {
    /**
     * Get a project settings
     *
     * @param  string $containerID
     * @return string
     */
    function googleTagManager($containerID = '')
    {
        $containerID = ($containerID ? $containerID : settings('tagManager'));
        if($containerID) {
            return new \Illuminate\Support\HtmlString(
              view()->make(
                "vendor.general.googleTagManagerHead", [
                  'containerID' => $containerID
                ]
              )->render()
            );
        }
    }
}

if(!function_exists('googleTagManagerBody')) {
    /**
     * Get a project settings
     *
     * @param  string $containerID
     * @return string
     */
    function googleTagManagerBody($containerID = '')
    {
        $containerID = ($containerID ? $containerID : settings('tagManager'));
        if($containerID) {
            return new \Illuminate\Support\HtmlString(
              view()->make(
                "vendor.general.googleTagManagerBody", [
                  'containerID' => $containerID
                ]
              )->render()
            );
        }
    }
}

if(!function_exists('metaTags')) {
    /**
     * Get meta tags and prints them
     *
     * @param  null  $modelData
     * @param  array $customData
     * @throws Exception
     */
    function metaTags($modelData = null, $customData = [])
    {
        if(\Accio\Support\Facades\Meta::getMetaIsPrinted()) {
            return;
        }

        $currentMenuLink = \App\Models\MenuLink::getActive();
        if(!$modelData) {
            $modelData = $currentMenuLink;
        }

        // Set model data
        if($modelData) {
            \Accio\Support\Facades\Meta::setModelData($modelData);

            // Get model's own meta data
            if(method_exists($modelData, 'metaData')) {
                $modelData->metaData();
            }
        }

        // Set title
        $title = null;
        if(isset($customData['title'])) {
            $title = $customData['title'];
        }elseif($modelData && property_exists($modelData, 'title')) {
            $title = $modelData->title;
        }elseif($currentMenuLink && $currentMenuLink->label) {
            $title = $currentMenuLink->label;
        }

        if($title) {
            \Accio\Support\Facades\Meta::setTitle($title);
        }

        // Set description
        $description = null;
        if(isset($customData['description'])) {
            $description = $customData['description'];
        }elseif($modelData && property_exists($modelData, 'description')) {
            $description = $modelData->description;
        }

        if($description) {
            $this->set('description', $description);
        }

        // Get meta data from events
        event('meta:add', [$modelData, $customData]);

        \Accio\Support\Facades\Meta::printMetaTags();
    }
}

if(!function_exists('permalink')) {
    /**
     * Get a permalink by name
     *
     * @param  $belongsTo
     * @param  $name
     * @param  string    $defaultURL
     * @return string
     */
    function permalink($belongsTo, $name, $defaultURL = '')
    {
        return \App\Models\Permalink::getByName($belongsTo, $name, $defaultURL);
    }
}

if(!function_exists('routeIsActive')) {
    /**
     * Check if a route is active
     *
     * @param  $routeName
     * @param  string    $className
     * @return bool|string
     */
    function routeIsActive($routeName, $className = "")
    {
        $currentRouteName = str_replace('.default', '', \Request::route()->getName());
        if($currentRouteName == $routeName) {
            if($className) {
                return $className;
            }
            return false;
        }else{
            if($className) {
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
     *
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
     *
     * @return \Illuminate\Http\Response
     */
    function error404()
    {
        return response()->view(\App\Models\Theme::view('errors/404'), [], 404);
    }
}


if (! function_exists('isSecure')) {
    /**
     * check whether the site is opened via https or no
     *
     * @param  $request
     * @return bool
     */
    function isHttps()
    {
        return request()->headers->get('x-forwarded-proto') == 'https' ? true: false;
    }
}
if (! function_exists('adminURL')) {
    /**
     * Get project's admin URL
     *
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    function adminURL()
    {
        return url(config('project.adminPrefix'));
    }
}

if (! function_exists('isInAdmin')) {
    /**
     * Check if current request belong to admin interface.
     *
     * @return bool
     */
    function isInAdmin()
    {
        if (request()->is((string) Config::get('project')['adminPrefix'].'*')) {
            return true;
        }
        return false;
    }
}

if (! function_exists('currentMenuLink')) {
    /**
     * Current MenuLink
     *
     * @param  string $columnName
     * @return int|null
     */
    function currentMenuLink($columnName = '')
    {
        return \App\Models\MenuLink::getCurrent($columnName);
    }
}

if (! function_exists('isMobile')) {
    /**
     * Check if site is access via mobile.
     *
     * @return mixed
     */
    function isMobile()
    {
        // temporary
        return \Riverskies\Laravel\MobileDetect\Facades\MobileDetect::isMobile();
    }
}

if (! function_exists('isTablet')) {
    /**
     * Check if site is access via tablet.
     *
     * @return mixed
     */
    function isTablet()
    {
        // temporary
        return \Riverskies\Laravel\MobileDetect\Facades\MobileDetect::isTablet();
    }
}
if (! function_exists('themeNamespace')) {
    /**
     * Get current theme namespace.
     *
     * @return mixed
     */
    function themeNamespace()
    {
        return \App\Models\Theme::controllersNameSpace();
    }
}

if (! function_exists('categoriesRelationTable')) {
    function categoriesRelationTable($postTypeSlug)
    {
        return $postTypeSlug."_categories";
    }
}

if (! function_exists('tagsRelationTable')) {
    function tagsRelationTable($postTypeSlug)
    {
        return $postTypeSlug."_tags";
    }
}

if (! function_exists('mediaRelationTable')) {
    function mediaRelationTable($postTypeSlug)
    {
        return $postTypeSlug."_media";
    }
}