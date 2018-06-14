<?php

namespace Accio\App\Traits;

use App\Models\MenuLink;
use App\Models\Plugin;
use App\Models\Post;
use App\Models\Settings;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Language;
use App\Models\PostType;
use Accio\Support\Facades\Meta;
use Accio\Support\Facades\Routes;
use App\Models\User;

use Mockery\Exception;


trait MenuLinkTrait{

    /**
     * Current MenuLink data
     *
     * @var array|null $currentMenuLink
     * */
    private static $currentMenuLink;

    /**
     * Active MenuLinks IDs, including their parents
     *
     * @var array $activeIDs
     */
    private  static $activeIDs = [];

    /**
     * The MenuLink to which the HomePage is linked
     *
     * @var array $homepage .*/
    private static $homepage;

    /**
     * Set current MenuLink IDs.
     *
     * We use routeName and params to match active MenuLinks.
     * While the same MenuLink can be linked to different Menus and they have the same params and routeName,
     * there is currently no way to identify which of them is active, therefore all of their IDs are returned
     *
     * @param boolean $reset If active ids should be reset
     * @return array
     */
    public static function setActiveIDs($reset = false){
        if(!self::$activeIDs || $reset){
            // Get current Menu Link
            $currentRoute = \Request::route();

            // Get active MenuLinks by routeName and params
            $currentMenuLinkIDs = [];
            $isDefaultLanguage = (App::getLocale() == Language::getDefault('slug') ? '.default' : false);
            foreach (\App\Models\MenuLink::getFromCache() as $menuLink) {
                $menuLinkRoute = Route::getRoutes()->getByName($menuLink->routeName . $isDefaultLanguage);
                // maybe route doesn't have .default suffix
                if (!$menuLinkRoute) {
                    $menuLinkRoute = Route::getRoutes()->getByName($menuLink->routeName);
                }

                if($menuLinkRoute){                //route is found|
                    self::matchRoute($currentMenuLinkIDs, $menuLink, $currentRoute, $menuLinkRoute);
                    self::checkCategoryMatch($currentMenuLinkIDs, $menuLink);
                }
            }

            if($currentMenuLinkIDs) {
                self::$activeIDs = array_unique($currentMenuLinkIDs);
            }
        }
    }

    /**
     * Match route based on current menulink and route
     *
     * @param array $currentMenuLinkIDs
     * @param object $menuLink
     * @param object $currentRoute
     * @param object $menuLinkRoute
     */
    private static function matchRoute(&$currentMenuLinkIDs, $menuLink,$currentRoute, $menuLinkRoute){
        $menuLinksParams = self::decodeParams($menuLink->params);

        //add language parameter on non-default languages
        if (App::getLocale() != Language::getDefault('slug')) {
            $menuLinksParams['lang'] = App::getLocale();
        }

        //validate params
        $paramsMatch = true;

        if ($menuLinkRoute->parameterNames()) {
            $paramsMatch = false;
            foreach ($menuLinkRoute->parameterNames() as $parameter) {
                if (
                    // Current route parameter is present in MenuLink route
                  isset($menuLinksParams[$parameter]) &&

                  // Route uri match
                  $currentRoute->uri() == $menuLinkRoute->uri() &&

                  // Current route Parameter match the parameter from Menu Link
                  \Request::route()->parameter($parameter) == $menuLinksParams[$parameter]
                ) {
                    $paramsMatch = true;
                } else {
                    $paramsMatch = false;
                    continue;
                }
            }
        }

        // There may be cases where there is no parameters in routes but both route uri matches
        if (!$menuLinkRoute->parameterNames() && $currentRoute->uri() == $menuLinkRoute->uri()) {
            $paramsMatch = true;
        }

        //set MenuLink as active if route parameters's number match
        $uriWithoutParams = trim(preg_replace('@\{.*?\}@', '*', $menuLinkRoute->uri()));

        if ($paramsMatch && (Request::is($uriWithoutParams) || Request::is($uriWithoutParams."/*")) ) {
            array_push($currentMenuLinkIDs, $menuLink->menuLinkID);

            // append its parents too
            foreach (self::parentID($menuLink->menuLinkID) as $parentID) {
                array_push($currentMenuLinkIDs, $parentID);
            }
        }
    }

    /**
     * Check if the menulink has a category linked to currrent Meta model data
     *
     * @param array $currentMenuLinkIDs
     * @param object $menuLink
     */
    private static function checkCategoryMatch(&$currentMenuLinkIDs, $menuLink){
        // check for category match
        if (Meta::getModelData()
          && isset($menuLink->params->categoryID)
          && isset(Meta::getModelData()->categories)
          && count(Meta::getModelData()->categories)
        ) {
            foreach(Meta::getModelData()->categories as $category){
                if($category->categoryID === $menuLink->params->categoryID){
                    array_push($currentMenuLinkIDs, $menuLink->menuLinkID);

                    // append its parents too
                    foreach (self::parentID($menuLink->menuLinkID) as $parentID) {
                        array_push($currentMenuLinkIDs, $parentID);
                    }
                }
            }
        }

    }

    /**
     * Get first active menulink
     */
    public static function getActive(){
        self::setActiveIDs();
        if(self::getActiveIDs()){
            return MenuLink::findByID(current(self::getActiveIDs()));
        }
        return;
    }

    /**
     * Get parent IDs of current MenuLink
     */
    public static function getActiveIDs(){
        return self::$activeIDs;
    }

    /**
     * Set current MenuLink ID
     *
     * @param array $menuLinkData MenuLink data
     */
    public static function setCurrent($menuLinkData){
        self::$currentMenuLink = $menuLinkData;
    }

    /**
     * Get current MenuLink ID
     *
     * @param string $columnName Column of page to be returned
     *
     * @return int|null Returns MenuLinkID if found, null instead
     */
    public static function getCurrent($columnName = ''){
        if ($columnName) {
            if (isset(self::$currentMenuLink->$columnName)) {
                return self::$currentMenuLink->$columnName;
            }
            return;
        }
        return self::$currentMenuLink;
    }

    /**
     * Get a param from current MenuLink
     *
     * @param string $paramKey
     * @return string|null
     */
    public static  function getCurrentParam($paramKey){
        if(self::$currentMenuLink) {
            if (isset(self::$currentMenuLink->params->$paramKey)) {
                return self::$currentMenuLink->params->$paramKey;
            }
        }
        return null;
    }

    /**
     * Find MenuLink that is defined HomePage
     *
     * @return array|string|null Returns column value if column found, null if not found. If column is not given all MenuLink data will be returned
     */
    public static function sethomepage(){
        if(Post::gethomepage('postID')) {
            $menuLinkHomePage = \App\Models\MenuLink::getFromCache()
              ->where('belongsToID', Post::gethomepage('postID'))
              ->where('belongsTo', 'post_pages');

            // The first MenuLink is returned if no HomePage is defined
            if (!$menuLinkHomePage) {
                $menuLinkHomePage = $menuLinkHomePage->first();
            } else {
                $menuLinkHomePage = Language::translate($menuLinkHomePage->first());
            }

            self::$homepage = $menuLinkHomePage;
        }
    }

    /**
     * Get MenuLink that is set as Homepage
     *
     * @return array|string|null Returns column value if column found, null if not found. If column is not given all MenuLink data will be returned
     */
    public static function gethomepage(){
        return self::$homepage;
    }

    /**
     * Get MenuLink by slug
     *
     * @param string $slug The slug of MenuLink ex. "about-us"
     *
     * @return array|boolean MenuLink data if found, false if not found
     *
     */
    public static function findBySlug($slug,$languageSlug = ""){
        $getMenuLink = array_where(\App\Models\MenuLink::getFromCache($languageSlug), function ($value)  use($slug){
            return ($value['slug'] == $slug);
        });

        if($getMenuLink){
            return array_first($getMenuLink);
        }
        return false;
    }

    /**
     * Get MenuLink by ID
     *
     * @param int $menuLinkID The ID of MenuLink.
     * @param string $languageSlug Slug of lanaguage
     *
     * @return object|null MenuLink data if found, null if not found
     *
     */
    public static function findByID($menuLinkID,$languageSlug=""){
        $menuLinks = MenuLink::getFromCache($languageSlug);
        if($menuLinks){
            return $menuLinks->where('menuLinkID',$menuLinkID)->first();
        }
    }

    /**
     * Checks if a given MenuLinkID is currently active in navigation
     *
     * @params tring $class
     * @return bool|string Returns true, false or the given class name
     */
    public function isActive($class = ''){
        $isActive = false;
        if(in_array($this->menuLinkID, self::getActiveIDs())){
            $isActive = true;
        }
        if($isActive && $class){
            return $class;
        }

        return $isActive;
    }

    /**
     * Get all parents IDs of a MenuLink
     *
     * @param $menuLinkID
     * @return array
     */
    public static function parentID($menuLinkID){
        $parentIDs = [];
        while($menuLinkID != NULL){
            if(MenuLink::getFromCache()) {
                $parentObj = MenuLink::getFromCache()->where('menuLinkID', $menuLinkID)->first();
                if ($parentObj) {
                    $menuLinkID = $parentObj->parent;
                    if ($menuLinkID) {
                        $parentIDs[] = $menuLinkID;
                    }
                }
                if ($parentObj || !$menuLinkID) {
                    $menuLinkID = NULL;
                }
            }
        }
        return $parentIDs;
    }

    /**
     * Redirect url to default language if default language slug is given
     * */
    public static function redirectToDefaultLanguage(){
        $getCurrentPath = Request::path();
        $explodePath = explode('/',$getCurrentPath);

        //only proceed if there is a language found in first path and that's default language
        if(App::getLocale() == Language::getDefault("slug")){
            $countPaths = count($explodePath);
            $getLanguage = Language::findBySlug($explodePath[0]);

            if($getLanguage) {
                if($getLanguage->isDefault){
                    if($countPaths >= 2 && $explodePath[0] && $explodePath[1]){
                        $urlAfterLanguage = substr($getCurrentPath, 3);
                        Redirect::to($urlAfterLanguage, 301)->send();
                    }
                    //that's shall be default language
                    else if($countPaths == 1 && $explodePath[0]){
                        Redirect::to('/', 301)->send();
                    }
                }

                //set language
                if(!Request::route('lang')){
                    \Request::route()->setParameter('lang', $getLanguage->slug);
                }
            }
        }
    }

    /**
     * Generate action of a link and its params, as defined in Routes
     *
     * @param object $menuLink A single specific Menu Link object
     *
     * @return array Returns controller and params of a method
     * */
    public static function getActionOfLink($menuLink){
        //post type
        if($menuLink->belongsTo == "post_type"){
            return route('backend.post.index', [
              'post_type' => "post_".$menuLink->params->postTypeSlug,
              'view' => 'list'
            ]);
        }
        // posts
        else if(substr($menuLink->belongsTo, 0, 5) == "post_" && $menuLink->belongsTo != "post_type"){

            //handle single post
            if(isset($menuLink->params->postSlug)){
                return route('backend.post.single', [
                  'post_type' => $menuLink->belongsTo,
                  'view' => 'update',
                  'id' => $menuLink->belongsToID
                ]);
            }
            //posts list
            else{
                $postType = PostType::findByID($menuLink->belongsToID);
                if($postType) {
                    return route('backend.post.index', [
                      'post_type' => $postType->slug,
                      'view' => 'list'
                    ]);
                }
            }

        }

        //categories
        else if($menuLink->belongsTo == "category" || $menuLink->belongsTo == "categories"){
            $category = Category::findByID($menuLink->belongsToID);
            if($category) {
                $postType = PostType::findByID($category->postTypeID);
                if ($postType) {
                    return route('backend.post.index', [
                      'post_type' => $postType->slug,
                      'view' => 'list',
                      'category' => $menuLink->belongsToID
                    ]);
                }
            }
        }
        return;
    }

    /**
     * Use to get all menus and the menu links of each of them
     *
     * @return array of menus and menu links
     */
    public static function cmsMenus(){
        $result = [];
        foreach(\App\Models\Menu::getFromCache() as $menu){
            $result[$menu->slug] = [
              'menuID' => $menu->menuID,
              'title' => $menu->title,
              'menuLinks' => self::cmsMenuLinks(Menu::getMenuLinks($menu->slug)),
            ];
        }
        return $result;
    }

    /**
     * Get MenuLinks to be shown in Admin Panel
     *
     * @param array $menuLinks list of menu links
     * @return array all menu links in parent child relation
     */
    public static function cmsMenuLinks($menuLinks = []){
        $links = [];
        $count = 0;

        foreach($menuLinks as $menuLink){
            if($menuLink->belongsTo == "post_type"){
                $permission = User::hasAccess($menuLink->slug, "read");
            }elseif($menuLink->belongsTo == "category"){
                $permission = User::hasAccess("Category", "read");
            }else{
                $permission = User::hasAccess($menuLink->slug, "update");
            }

            $routeURL = self::getActionOfLink($menuLink);
            if($routeURL){
                $links[$count] = [
                  'label' => $menuLink->label,
                  'menuLinkID' => $menuLink->menuLinkID,
                  'link' => self::removeDomainFromLink($routeURL),
                  'icon' => '',
                  'access' => $permission,
                  'children' => self::cmsMenuLinks($menuLink->children),
                ];
                $count++;
            }
        }
        if(!count($links)){
            return [];
        }
        return $links;
    }

    /**
     * Translate menulinks params to a single dimensional array and returns only current language params
     * @param string $params
     * @return array
     */
    public static function decodeParams($params){
        if(!$params){
            return [];
        }
        $paramsArrayList = json_decode(json_encode($params), true);
        $decodedParams = [];
        foreach($paramsArrayList as $paramKey=>$paramValue){
            if(is_array($paramValue)){
                foreach($paramValue as $languageSlug=>$langParamValue){
                    if($languageSlug == App::getLocale()){
                        $decodedParams[$paramKey] = $langParamValue;
                        break;
                    }else{
                        $decodedParams[$paramKey] = "";
                    }
                }
            }else{
                $decodedParams[$paramKey] = $paramValue;
            }
        }
        return $decodedParams;
    }


    /**
     * Find which params belongs to an action and gets their values from Menu Links Params
     *
     * @param object $menuLink
     * @return array Returns final params with values
     */
    private static function setParams($menuLink){
        $menuLinkParams = self::decodeParams($menuLink->params);
        $routeParams = Route::getRoutes()->getByName($menuLink->routeName)->parameterNames();
        $params = [];
        foreach($routeParams as $key){
            if(isset($menuLinkParams[$key])) {
                $params[$key] = $menuLinkParams[$key];
            }
        }
        return $params;
    }

    public static function removeDomainFromLink($link){
        return str_replace(Request::getSchemeAndHttpHost(),"", $link);
    }

    /**
     * Used to get the array of menu links for the application sites, and the list of post types
     *
     * @return array of application menu links
     */
    public static function applicationMenuLinks(){
        $applicationMenuLinks = [
          [
            'label' => 'Menu',
            'link' => self::removeDomainFromLink(action(Routes::backend("MenuController@single"),['lang' =>  App::getLocale(), 'view' => 'list', 'menuID' => Menu::getPrimaryMenuID()])),
            'module' =>  'menu',
            'icon' => 'fa fa-bars',
            'access' => User::hasAccess("Menu", "read"),
            'children' => '',
          ],
          [
            'label' => 'Users',
            'link' =>  '',
            'module' =>  'user',
            'icon' => 'fa fa-users',
            'access' => User::hasAccess("User", "read"),
            'children' => [
              [
                'label' => 'List',
                'link' => self::removeDomainFromLink(action(Routes::backend("UserController@index"),['lang' =>  App::getLocale(), 'view' => 'list'])),
                'icon' => '',
                'access' => User::hasAccess("User", "read"),
                'children' => '',
              ],
              [
                'label' => 'Add new',
                'link' => self::removeDomainFromLink(action(Routes::backend("UserController@index"),['lang' =>  App::getLocale(), 'view' => 'create'])),
                'icon' => '',
                'access' => User::hasAccess("User", "create"),
                'children' => '',
              ],
              [
                'label' => 'Permissions',
                'link' => self::removeDomainFromLink(action(Routes::backend("PermissionController@index"),['lang' =>  App::getLocale(), 'view' => 'list'])),
                'icon' => '',
                'access' => User::hasAccess('Permission', "read"),
                'children' => '',
              ]
            ],
          ],
          [
            'label' => 'Post types',
            'link' =>  '',
            'module' =>  'post-type',
            'icon' => 'fa fa-plus-square-o',
            'access' => User::hasAccess("PostType", "read"),
            'children' => [
              [
                'label' => 'List',
                'link' => self::removeDomainFromLink(action(Routes::backend("PostTypeController@index"),['lang' =>  App::getLocale(), 'view' => 'list'])),
                'icon' => '',
                'access' => User::hasAccess("PostType", "read"),
                'children' => '',
              ],
              [
                'label' => 'Add new',
                'link' =>  self::removeDomainFromLink(action(Routes::backend("PostTypeController@index"),['lang' =>  App::getLocale(), 'view' => 'create'])),
                'icon' => '',
                'access' => User::hasAccess("PostType", "create"),
                'children' => '',
              ]
            ],
          ],
          [
            'label' => 'Custom fields',
            'link' =>  '',
            'module' => 'custom-fields',
            'icon' => 'fa fa-plus-square-o',
            'access' => User::hasAccess("CustomField", "read"),
            'children' => [
              [
                'label' => 'List',
                'link' => self::removeDomainFromLink(action(Routes::backend("CustomFieldController@index"),['lang' =>  App::getLocale(), 'view' => 'list'])),
                'icon' => '',
                'access' => User::hasAccess("CustomField", "read"),
                'children' => '',
              ],
              [
                'label' => 'Add new',
                'link' =>  self::removeDomainFromLink(action(Routes::backend("CustomFieldController@index"),['lang' =>  App::getLocale(), 'view' => 'create'])),
                'icon' => '',
                'access' => User::hasAccess("CustomField", "create"),
                'children' => '',
              ]
            ],
          ],
          [
            'label' => 'Media',
            'link' => self::removeDomainFromLink(action(Routes::backend("MediaController@index"), ['lang' =>  App::getLocale(), 'view' => 'library'])),
            'module' => 'media',
            'icon' => 'fa fa-camera',
            'access' => true,
            'children' => '',
          ],
          [
            'label' => 'Settings',
            'link' => self::removeDomainFromLink(action(Routes::backend("SettingsController@index"),['lang' =>  App::getLocale(), 'view' => 'general'])),
            'module' => 'project-settings',
            'icon' => 'fa fa-cogs',
            'access' => User::hasAccess('Settings', "read"),
            'children' => '',
          ],
          [
            'label' => 'Language',
            'link' => self::removeDomainFromLink(action(Routes::backend("LanguageController@index"),['lang' =>  App::getLocale(), 'view' => 'list'])),
            'icon' => 'fa fa-language',
            'module' => 'language',
            'access' => User::hasAccess('Language', "read"),
            'children' => '',
          ]
        ];

        foreach (PostType::getFromCache() as $postType){
            if(!$postType->isVisible){
                continue;
            }

            $tmp = [
              'label' => $postType->name,
              'link' =>  '',
              'module' => $postType['slug'],
              'icon' => 'fa fa-thumb-tack',
              'access' => User::hasAccess($postType['slug'],"read"),
              'children' => [
                [
                  'label' => 'List',
                  'link' =>  self::removeDomainFromLink(action(Routes::backend("PostController@postsIndex"),['lang' =>  App::getLocale(), 'post_type' => $postType['slug'], 'view' => 'list' ])),
                  'icon' => '',
                  'access' => User::hasAccess($postType['slug'], "read"),
                  'children' => '',
                ],
                [
                  'label' => 'Add new',
                  'link' => self::removeDomainFromLink(action(Routes::backend("PostController@postsIndex"),['lang' =>  App::getLocale(), 'post_type' => $postType['slug'], 'view' => 'create' ])),
                  'icon' => '',
                  'access' => User::hasAccess($postType['slug'], "create"),
                  'children' => '',
                ],
                [
                  'label' => 'Categories',
                  'link' => self::removeDomainFromLink(action(Routes::backend("PostTypeController@single"),['lang' =>  App::getLocale(), 'view' => 'categorylist', 'id' => $postType['postTypeID'] ])),
                  'icon' => '',
                  'access' => (User::hasAccess('Category', "read") && $postType['hasCategories']),
                  'children' => '',
                ],
                [
                  'label' => 'Tags',
                  'link' => self::removeDomainFromLink(action(Routes::backend("PostTypeController@single"),['lang' =>  App::getLocale(), 'view' => 'taglist', 'id' => $postType['postTypeID'] ])),
                  'icon' => '',
                  'access' => (User::hasAccess('Tag', "read") && $postType['hasTags']),
                  'children' => '',
                ],
              ]
            ];
            array_unshift($applicationMenuLinks, $tmp);
        }

        $plugins = Plugin::activePlugins();
        if(count($plugins)) {
            // get plugins links
            $tmpPluginLinks = [
              'label' => "Plugins",
              'link' =>  '',
              'module' => "plugins",
              'icon' => 'fa fa-thumb-tack',
              'access' => User::hasAccess("Plugin", "read"),
              'children' => [],
            ];

            foreach ($plugins as $plugin) {
                $title = str_replace('/', "_", $plugin->namespace);
                $app = str_slug($title, '_');

                $tmpPluginLinks['children'][] = [
                  'label' => $plugin->title,
                  'link' => self::removeDomainFromLink($plugin->fullBackendUrl()),
                  'icon' => '',
                  'access' => User::hasAccess($app, "read"),
                  'children' => '',
                  'module' => "plugins",
                ];

            }
            $applicationMenuLinks[] = $tmpPluginLinks;
        }


        return $applicationMenuLinks;
    }

    /**
     * Get children of a menu link
     *
     * @param  array $menuLinks the list of menuLinks of a Menu
     * @param  INT   $parentID the ID of menuLinkID that serves as a parentID to its children
     * @param  INT   $level What is the level of the menu. It automatically increases +1 for each child
     *
     * @return object|array Returns all childrens of a given list of MenuLinks
     * */
    public static function children($menuLinks, $parentID = 0, $level = 1){

        if($level == 1){
            $fetchMenuList = $menuLinks->where('parent',null);
        }else{
            $fetchMenuList = $menuLinks->where('parent',$parentID);
        }

        //order List
        $fetchMenuList = $fetchMenuList->sortBy('order');

        if($fetchMenuList->count()) {
            $menuLinksList = new \stdClass();
            foreach ($fetchMenuList as $menuLink) {
                $menuLinksID = $menuLink->menuLinkID;
                $menuLinksList->$menuLinksID = $menuLink;
                $menuLinksList->$menuLinksID->children = self::children($menuLinks, $menuLink->menuLinkID, ($level + 1));

                //set active or no
                if (in_array($menuLink->menuLinkID, self::getActiveIDs())) {
                    $menuLinksList->$menuLinksID->isActive = true;
                } else {
                    $menuLinksList->$menuLinksID->isActive = false;
                }
            }
            return $menuLinksList;
        }
        return [];
    }


    /**
     * Generate the URL to a MenuLink
     *
     * @return string
     * @throws Exception
     */
    public function getHrefAttribute(){
        if(Route::getRoutes()->getByName($this->routeName)){
            if($this->params){
                $params = self::setParams($this);
                $url = route($this->routeName,$params);
            }else{
                $url = route($this->routeName);
            }
            return $url;

        }else{
            throw new Exception("No route found for '".$this->routeName."' MenuLink: ".$this->label);
        }
    }

    /**
     * Initialize menulinks
     * @param $request
     * @throws \Exception
     */
    public static function initialize($request){
        Menu::setPrimaryMenuID();
        Menu::setMenuLinksByMenu();

        //Backend
        if ($request->is(Config::get('project')['adminPrefix'].'*')){
            $menuLinkID = (is_numeric(Input::get('menu_link_id')) ? Input::get('menu_link_id') : FALSE);
            if($menuLinkID){
                self::setCurrent(self::findByID($menuLinkID));
            }
        }else{ // frontend
            Post::sethomepage();
            self::sethomepage();

            self::setActiveIDs();

            // Set home page MenuLink if home page is requested
            $getCurrentPath = Request::path();
            if($getCurrentPath == '/' || $getCurrentPath == App::getLocale()){
                self::setCurrent(MenuLink::gethomepage());
            }
            //or if any other menulink is active
            else if(self::getActiveIDs()){
                self::setCurrent(self::findByID(array_first(self::getActiveIDs())));
            }

            // Front page should not have duplicate urls
            if(Request::route('postSlug') == Post::gethomepage()->slug){
                Redirect::to('/', 301)->send();
            }
        }
    }
}