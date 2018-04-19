<?php

namespace Accio\App\Http\Controllers\Backend;

use App\Models\Category;
use App\Models\MenuLinkConfig;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuLink;
use App\Models\Post;
use App\Models\PostType;
use App\Models\Theme;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Accio\Support\Facades\Routes;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Frontend\MainController as FrontEndMainController;


class BaseMenuController extends MainController{
    private $newMenuLinks = [];
    private $existingMenuLinks = [];
    private $tmpMenuLinkInfo = [];
    private $order = 0;
    private $allMenuLinkRoutes = [];

    // Check authentification in the constructor
    public function __construct(){
        parent::__construct();
    }

    /**
     * Stores or updates the menu and his menu links.
     *
     * @param Request $request
     * @return array response for frontend
     * @throws \Exception
     */
    public function store(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('Menu','create')){
            return $this->noPermission();
        }

        // delete menu links from database
        if ($request->deletedMenuLinks){
            $ids = [];
            foreach ($request->deletedMenuLinks as $menuLinkID){
                if (!strstr($menuLinkID, 'NEW')){
                    $ids[] = $menuLinkID;
                }
            }
            MenuLink::whereIn('menuLinkID', $ids)->delete();
        }

        // create menu if it doesn't exist
        if (!$request->selectedMenuID){
            $slug = self::generateSlug($request->selectedMenu['title'], 'menus', 'menuID');

            $menu = new Menu();
            $menu->title = $request->selectedMenu['title'];
            $menu->slug = $slug;
            $menu->isPrimary = 0;

            if($menu->save()){
                $menuID = $menu->menuID;
            }else{
                throw new \Exception("New MenuLinks could not be saved. Please contact your administrator!");
            }
        }else{
            // update menu if it exists
            $menuID = $request->selectedMenuID;

            $menu = Menu::findOrFail($menuID);
            $menu->title = $request->selectedMenu['title'];

            if(!$menu->save()){
                throw new \Exception("New MenuLinks could not be saved. Please contact your administrator!");
            }
        }

        // convert menu links from multidimension to one dimension array
        $this->convertTo1Dimension($request->menuLinkList);
        // prepare array for storing
        $this->prepareArrayForStore($request->menuLinkListAfterOrder, $this->tmpMenuLinkInfo, $menuID);

        $newMenuLinksID = [];
        // create the new menu links for the selected menu
        foreach($this->newMenuLinks as $menuLinkKey=>$menuLink){

            //if parent is a new link get his id from $newMenuLinksID
            $isParentNewLink = strstr($menuLink['parent'], 'NEW');
            if($isParentNewLink){
                $menuLink['parent'] = $newMenuLinksID[$menuLink['parent']];
            }

            //we don't need these anymore
            if(isset($menuLink['menuLinkID'])){ unset($menuLink['menuLinkID']); }
            if(isset($menuLink['routeList'])){ unset($menuLink['routeList']); }
            if(isset($menuLink['href'])){ unset($menuLink['href']); }
            if(isset($menuLink['belongsToData'])){ unset($menuLink['belongsToData']); }

            $this->newMenuLinks[$menuLinkKey] = self::objectsToJson($menuLink);
        }

        // Fire event
        Event::fire('menuLink:creating', [$this->newMenuLinks, $request]);

        // insert menulinks
        $insertMenuLinks = DB::table('menu_links')->insert($this->newMenuLinks);

        if($insertMenuLinks){
            // Fire event
            Event::fire('menuLink:created', [$insertMenuLinks, $request]);
        }else{
            throw new \Exception("New MenuLinks could not be saved. Please contact your administrator!");
        }

        // update links
        $existingMenuLinks = $this->setParentID($newMenuLinksID);
        foreach($existingMenuLinks as $menuLink){
            $menuLink = self::objectsToJson($menuLink);
            $menuLinkID = $menuLink['menuLinkID'];
            if(isset($menuLink['menuLinkID'])){ unset($menuLink['menuLinkID']); }
            if(isset($menuLink['routeList'])){ unset($menuLink['routeList']); }
            if(isset($menuLink['href'])){ unset($menuLink['href']); }
            if(isset($menuLink['routes'])){ unset($menuLink['routes']); }

            // Fire event
            Event::fire('menuLink:updating', [$menuLink, $request]);

            MenuLink::where('menuLinkID', $menuLinkID)->update($menuLink);

            // Fire event
            Event::fire('menuLink:updated', [$menuLink, $request]);
        }

        // Remove Caches
        MenuLink::deleteCache();

        return $this->response( 'Menu is successfully saved', 200, $menuID);

    }

    /**
     * encode a list of object or arrays as json
     *
     * @param array $objectArray list of data
     * @return array encoded data
     */
    public static function objectsToJson($objectArray){
        $tmp = [];
        foreach ($objectArray as $key => $item){
            if (is_object($item) || is_array($item)){
                $item = json_encode($item);
            }
            $tmp[$key] = $item;
        }
        return $tmp;
    }

    /**
     * Converts multidimensional menu link array to one dimension. Removes children of each
     * menu link and but sets their parent ID. Stores array in $this->tmpMenuLinkInfo class instance
     *
     * @param array $unOrderedLinkList array of menu links
     * @param integer $parent id of parent
     * */
    private function convertTo1Dimension($unOrderedLinkList, $parent = 0){
        foreach ($unOrderedLinkList as $key => $menuLink){
            if(isset($menuLink['children'])){
                $children = $menuLink['children'];
                unset($menuLink['children']);
                unset($menuLink['routeList']);
                unset($menuLink['routes']);
                $this->tmpMenuLinkInfo[$key] = $menuLink;
                $this->convertTo1Dimension($children, $menuLink['parent']);
            }else{
                $this->tmpMenuLinkInfo[$key] = $menuLink;
            }
        }
    }

    /**
     * Prepares two arrays of new menu links to store in database ($this->newMenuLinks class instance)
     * and existing menu links to be updated ($this->existingMenuLinks class instance)
     *
     * @param array $orderedLinkList list of menu links generated by the nestable plugin in frontend
     * @param array $unOrderedLinkList list of menu links where we get all link information
     * @param integer $menuID ID of menu
     * @param integer $parent ID of parent of the menu link
     * */
    private function prepareArrayForStore($orderedLinkList, $unOrderedLinkList, $menuID, $parent = 0){
        foreach ($orderedLinkList as $menuLink){
            if (isset($menuLink['id'])){
                $id = $menuLink['id'];
            }else if(isset($menuLink['menuLinkID'])){
                $id = $menuLink['menuLinkID'];
            }else if(isset($menuLink['menuLinkID'])){
                $id = $menuLink['menuLinkID'];
            }
            $isNew = strstr($id, 'NEW');
            $tmp = $unOrderedLinkList[$id];
            $tmp['order'] = $this->order;
            $tmp['parent'] = $parent;
            $tmp['menuID'] = $menuID;

            if ($isNew){
                $this->newMenuLinks[] = $tmp;
            }else{
                $this->existingMenuLinks[] = $tmp;
            }
            $this->order++;

            if (isset($menuLink['children'])){
                $this->prepareArrayForStore($menuLink['children'], $unOrderedLinkList, $menuID, $id);
            }
        }
    }

    /**
     * Used to replace the parent of menu links that exists in DB but have parent a new menu link.
     * So after inserting the new menu links in database we get their ID and use them if their are parents of existing menu links
     *
     * @param array $newMenuLinksID ids of new menu links inserted in database
     *
     * @return array Returns menu links that exist in db with updated parents
     * */
    private function setParentID($newMenuLinksID){
        $tmp = [];
        foreach ($this->existingMenuLinks as $menuLink){
            $isParentNewLink = strstr($menuLink['parent'], 'NEW');
            if($isParentNewLink){
                $menuLink['parent'] = $newMenuLinksID[$menuLink['parent']];
            }
            $tmp[] = $menuLink;
        }
        return $tmp;
    }

    /**
     * Get all controllers' menuLinkRoutes
     * @return array
     */
    protected function getAllMenuLinkRoutes(){
        if(!$this->allMenuLinkRoutes) {
            $controllers = File::files(Theme::getPath() . '/controllers');
            $menuLinkRoutes = [];
            foreach ($controllers as $file) {
                $controllerName = str_replace('.php', '', $file->getFileName());
                $controllerClass = Theme::getNamespace() . '\\Controllers\\' . $controllerName;
                $routes = $controllerClass::getMenuLinkRoutes($controllerName);
                if ($routes) {
                    $menuLinkRoutes[$controllerName] = $routes;
                }
            }
            $this->allMenuLinkRoutes = $menuLinkRoutes;
        }


        // Check post types have routes
        $postTypes = PostType::where('isVisible', true)->get();
        foreach($postTypes as $postType){
            $postTypeControllerName = ucfirst(camel_case($postType->slug)).'Controller';

            // add default post_pages routes if they are not defined
            if($postType->slug == 'post_pages'){
                // ad single post route route
                if(!isset($this->allMenuLinkRoutes['PagesController'])) {
                    $this->allMenuLinkRoutes['PagesController'] = Post::getDefaultPostRoutes($postType);
                }
                continue;
            }

            // Define postType route if not defined in its own Controller
            if(Post::haveItsOwnController($postType->slug)) {
                if (!isset($this->allMenuLinkRoutes[$postTypeControllerName]['post_type'][$postType->slug])) {
                    $this->allMenuLinkRoutes[$postTypeControllerName]['post_type'][$postType->slug] = Post::getDefaultPostTypeRoutes($postType);
                }
                // default post routes
                if(!isset($this->allMenuLinkRoutes[$postTypeControllerName][$postType->slug])){
                    $this->allMenuLinkRoutes[$postTypeControllerName][$postType->slug] =  Post::getDefaultPostRoutes($postType);
                }
            }else{ // Define postType route if not defined in PostController

                // Default Post Type routes
                if (!isset($this->allMenuLinkRoutes['PostController']['post_type'][$postType->slug])) {
                    $this->allMenuLinkRoutes['PostController']['post_type'][$postType->slug] = Post::getDefaultPostTypeRoutes($postType);
                }
                // default post routes
                if(!isset($this->allMenuLinkRoutes['PostController'][$postType->slug])){
                    $this->allMenuLinkRoutes['PostController'][$postType->slug] =  Post::getDefaultPostRoutes($postType);
                }
            }
        }

        return $this->allMenuLinkRoutes;
    }
    /**
     * Get all controllers' menuLinkRoutes
     * @return array
     */
    public function menuLinkPanels(){
        $this->getAllMenuLinkRoutes();
        $models = File::files(base_path('app/Models'));
        $panels = [];
        foreach($models as $file){
            $modelName = str_replace('.php', '', $file->getFileName()); // extract model name
            $modelClass ='App\\Models\\'.$modelName;

            if(method_exists($modelClass, 'menuLinkPanel' )){
                $menuPanels = $modelClass::menuLinkPanel();
                // handle one dimension menuLinkRoutes
                if(isset($menuPanels['items'])){
                    $menuPanels['routes'] = $this->getMenuLinkRoutes($menuPanels['controller'], (isset($menuPanels['belongsTo']) ? $menuPanels['belongsTo'] : ''));
                    $panels[] = $menuPanels;
                }else{
                    foreach($menuPanels as $panelKey=>$panel){
                        $panel['routes'] = $this->getMenuLinkRoutes($panel['controller'], (isset($panel['belongsTo']) ? $panel['belongsTo'] : ''));
                        $panels[] = $panel;
                    }
                }
            }
        }
        return $panels;
    }

    /**
     * Get menu Link Routes by post type or return default ones
     *
     * @param string $controller
     * @param string $belongsTo
     * @return array
     */
    public function getMenuLinkRoutes($controller, $belongsTo = ''){
        $this->getAllMenuLinkRoutes();

        $routes = [];
        // Get routes of this post type
        if(isset($this->allMenuLinkRoutes[$controller][$belongsTo])){
            $routes = $this->allMenuLinkRoutes[$controller][$belongsTo];
        }else if(isset($this->allMenuLinkRoutes[$controller])){
            $routes = $this->allMenuLinkRoutes[$controller];
        }
        return $routes;
    }

    /**
     * takes the menu links from the database and creates a array for each of them with their children
     *
     * $param string $lang language slug
     * $param integer $id selected menu ID
     * @return array multidimensional array of menu links, selected menu and all languages
     * */
    public function detailsJSON($lang, $id){
        // check if user has permissions to access this link
        if(!User::hasAccess('Menu','read')){
            return $this->noPermission();
        }
        // get selected menu
        $menu = Menu::findByID($id);

        // TODO me i marr te dhanat e menu linkave prej cachit
        $this->existingMenuLinks = MenuLink::where('menuID', $id)->orderBy("order", "ASC")->get()->toArray();
        $convertedMenuLinks = $this->convertLinksToParentChild($this->existingMenuLinks);
        $convertedMenuLinks = $this->removeFirstDimensionNoneParents($convertedMenuLinks);

        $final = array(
            'list' => $convertedMenuLinks,
            'menu' => $menu,
            'languages' => Language::getFromCache()
        );

        // Fire event
        $final['events'] = Event::fire('menu:pre_update', [$final]);

        return $final;
    }

    /**
     * Converts the one dimensional array of menu links to multidimensional array where each parent link has a object with
     * key 'children' where his children are stored.
     *
     * @param array $menuLinks list of menu links
     * @return array multidimensional array of menu links with children
     * */
    private function convertLinksToParentChild($menuLinks){
        $tmp = [];
        foreach($menuLinks as $key => $menuLink){

            // Append menuLink Routes to each menu link
            if($menuLink['routeName']) {
                $route = Route::getRoutes()->getByName($menuLink['routeName']);
                if($route){
                    $controller = Arr::last(explode('\\', get_class($route->getController())));
                    $routes = $this->getMenuLinkRoutes($controller, $menuLink['belongsTo']);

                    // Category
                    if($menuLink['belongsTo'] == 'category'){
                        $routeName = 'backend.postType.single';
                        $menuLink['href'] = route($routeName, ['view' => 'categoryupdate', 'id' => $menuLink['belongsToID']]);
                        $menuLink['routeList'] = $routes['list'];
                    }else if($menuLink['belongsTo'] == 'post_type'){
                        // Post Types
                        $routeName = 'backend.postType.single';
                        $menuLink['href'] = route($routeName, ['view' => 'update', 'id' => $menuLink['belongsToID']]);

                        $slugArr = array_first((array)$menuLink['slug']);
                        if($slugArr){
                            $menuLink['routeList'] = $routes[$slugArr]['list'];
                        }
                    }else{
                        // Single post
                        $routeName = 'backend.post.single';
                        $menuLink['href'] = route($routeName, ['post_type' => $menuLink['belongsTo'],'view' => 'update', 'id' => $menuLink['belongsToID']]);
                        $menuLink['routeList'] = $routes['list'];
                    }

                }
            }

            $tmp[$menuLink['menuLinkID']] = $menuLink;
            $children = $this->getChildren($menuLink['menuLinkID']);
            if(count($children)){
                $tmp[$menuLink['menuLinkID']]['children'] = $this->convertLinksToParentChild($children);
            }
        }
        return $tmp;
    }

    /**
     * gets all children of a menu link
     *
     * @param integer id of pare menu link
     * @return array children of menu link
     * */
    private function getChildren($parentID){
        $tmp = [];
        foreach($this->existingMenuLinks as $key => $menuLink){
            if ($menuLink['parent'] == $parentID){
                $tmp[$menuLink['menuLinkID']] = $menuLink;
            }
        }
        return $tmp;
    }

    private function removeFirstDimensionNoneParents($menuLinks){
        $tmp = [];
        foreach($menuLinks as $key => $menuLink){
            if(isset($menuLink['parent']) && !$menuLink['parent']){
                $tmp[$menuLink['menuLinkID']] = $menuLink;
            }
        }
        return $tmp;
    }


    /**
     * @return array list of the related apps for a specific menu link
     * */
    public function getRelatedApps($lang, $menuLinksID){
        $postTypes = PostType::getFromCache();
        $categories = Category::all();
        $menuLinks = MenuLinkConfig::where('menuLinkID', $menuLinksID)->get();

        $langSlug = App::getLocale();

        $postTypesArr = array();
        $categoriesArr = array();
        $albumsArr = array();
        $postArr = array();

        $albumsIDs = array();

        // loop throw the menulinks ows
        foreach ($menuLinks as $menuLink){
            // if row post type
            if($menuLink->belongsTo == "post_type"){
                // get title from post type table if the config (relation) row is a post type
                foreach ($postTypes as $postType){
                    if($menuLink->belongsToID == $postType->postTypeID){
                        $actionLink = [
                            'controller' => Routes::backend('PostController@postsIndex'),
                            'params' => [
                                'post_type' => $postType->slug,
                                'view' => 'list'
                            ]
                        ];

                        if($menuLink->postIDs === NULL){ // if it is a post type list
                            $menuLink->title = $postType->name;
                            // generate url
                            $menuLink->url = MenuLink::removeDomainFromLink(action($actionLink['controller'], $actionLink['params']));
                            array_push($postTypesArr, $menuLink);
                            break;
                        }else{ // if it is a post type list of specific post ids
                            $postIDsOBJ = json_decode($menuLink->postIDs);
                            $idList = array();
                            $c = 0;
                            $IDs = array();
                            foreach ($postIDsOBJ as $id){
                                array_push($idList,$id);
                                $IDs[$c] = $id;
                                $c++;
                            }

                            $posts = DB::table($postType->slug)->whereIn('postID',$idList)->get();
                            $postsTranslated = Language::filterRows($posts,false);

                            // generate url
                            $menuLink->url = MenuLink::removeDomainFromLink(action($actionLink['controller'], $actionLink['params']));

                            $postArr[$postType->slug] = array(
                                'title'         =>  $postType->name,
                                'belongsToID'   =>  $menuLink->belongsToID,
                                'belongsTo'     =>  $menuLink->belongsTo,
                                'postIDs'       =>  $IDs,
                                'url'           =>  $menuLink->url,
                                'list'          =>  $postsTranslated,
                            );

                        }
                    }
                }
            // if row is category
            }else if($menuLink->belongsTo == "categories"){
                foreach ($categories as $category){
                    if($menuLink->belongsToID == $category->categoryID){
                        $catTitleObject = $category->title;
                        $catTitle = "";
                        // if the title exist in the current language
                        if(isset($catTitleObject)){
                            $catTitle = $catTitleObject;
                        }
                        $menuLink->title = $catTitle;
                        // generate url
                        $actionLink = MenuLink::getActionOfLink($menuLink);
                        $menuLink->url = MenuLink::removeDomainFromLink($actionLink);
                        array_push($categoriesArr, $menuLink);
                        break;
                    }
                }
            }else if($menuLink->belongsTo == "albums"){
                $albumsIDs[] = $menuLink->belongsToID;
            }
        }
        // get related albums
        if (count($albumsIDs) > 0){
            $albums = \App\Models\Album::whereIn('albumID',$albumsIDs)->get();
            foreach ($albums as $album){
                $albumsArr[] = [
                    'belongsTo' => 'albums',
                    'belongsToID' => $album->albumID,
                    'title' => json_decode($album->title)->$langSlug,
                    'postIDs' => NULL,
                    'url' => NULL,
                    'menuLinkID' => $menuLinksID,
                ];
            }
        }

        return [
            'post_types'=> $postTypesArr,
            'categories' => [
                'title'=>'Categories',
                'list'=> $categoriesArr
            ],
            'albums' => [
                'title'=>'Albums',
                'list'=> $albumsArr,
                'url' => MenuLink::removeDomainFromLink(action(Routes::backend("MediaController@index"), ['lang' => $langSlug, 'view' => 'albums'])),
            ],
            'posts' => $postArr
        ];
    }

    /**
     *  Used to store the relation in the database
     *  Table menu_link_config
     *  @params array relatedList the list of the relation rows
     *  @params integer menu_link_id ID of the menu
     * */
    public function storeRelated(Request $request){
        DB::table('menu_link_config')->where("menuLinkID", $request->menu_link_id)->delete();
        foreach ($request->relatedList as $related){
            // make json object for the postIDs
            if(isset($related['postIDs']) && $related['postIDs'] != ""){
                if(is_array($related['postIDs']) && !is_string($related['postIDs']) && count($related['postIDs']) != 0){
                    $postIDs = array();
                    foreach ($related['postIDs'] as $key=>$val){
                        if($val != NULL && $val != ''){
                            $postIDs['k_'.$key] = $val;
                        }
                    }
                    $postIDs = json_encode($postIDs);
                }else{
                    $postIDs = NULL;
                }
            }else{
                $postIDs = NULL;
            }
            $isStored = MenuLinkConfig::create([
                'menuLinkID' => $request->menu_link_id,
                'belongsTo' => $related['belongsTo'],
                'belongsToID' => $related['belongsToID'],
                'postIDs' => $postIDs,
            ]);
            if(!$isStored){
                return $this->response( 'Menu relations could not be stored successfully', 403);
            }
        }
        return $this->response('Menu relations are stored successfully');
    }

}
