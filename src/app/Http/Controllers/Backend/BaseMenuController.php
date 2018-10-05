<?php

namespace Accio\App\Http\Controllers\Backend;

use App\Models\Category;
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
     * menu link and but sets their parent ID. Stores array in $this->tmpMenuLinkInfo class instance.
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
                $this->tmpMenuLinkInfo[] = $menuLink;
                $this->convertTo1Dimension($children, $menuLink['parent']);
            }else{
                $this->tmpMenuLinkInfo[] = $menuLink;
            }
        }
    }

    /**
     * Prepares two arrays of new menu links to store in database ($this->newMenuLinks class instance)
     * and existing menu links to be updated ($this->existingMenuLinks class instance).
     *
     * @param $orderedLinkList
     * @param $unOrderedLinkList
     * @param $menuID
     * @param int $parent
     * @throws \Exception
     */
    private function prepareArrayForStore($orderedLinkList, $unOrderedLinkList, $menuID, $parent = 0){
        foreach ($orderedLinkList as $menuLink){
            if (isset($menuLink['id'])){
                $id = $menuLink['id'];
            }else{
                $id = $menuLink['menuLinkID'];
            }
            $isNew = strstr($id, 'NEW');
            $tmp = $this->searchMenuLinkUsingID($unOrderedLinkList, $id);
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
     * Search menu link using it's ID.
     *
     * @param $list
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    private function searchMenuLinkUsingID($list, $id){
        foreach ($list as $menuLink){
            if($menuLink['menuLinkID'] == $id){
                return $menuLink;
            }
        }
        throw new \Exception("Menu link doesn't exit, something is wrong");
    }

    /**
     * Used to replace the parent of menu links that exists in DB but have parent a new menu link.
     * So after inserting the new menu links in database we get their ID and use them if their are parents of existing menu links
     *
     * @param array $newMenuLinksID ids of new menu links inserted in database
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
     * Get all controllers' menuLinkRoutes.
     *
     * @return array
     * @throws \Exception
     */
    protected function getAllMenuLinkRoutes(){
        if(!$this->allMenuLinkRoutes) {
            $menuLinkRoutes = [];
            if(file_exists(Theme::getPath() . '/controllers')) {
                $controllers = File::files(Theme::getPath() . '/controllers');
                foreach ($controllers as $file) {
                    $controllerName = str_replace('.php', '', $file->getFileName());
                    $controllerClass = Theme::getNamespace() . '\\Controllers\\' . $controllerName;
                    $routes = $controllerClass::getMenuLinkRoutes($controllerName);
                    if ($routes) {
                        $menuLinkRoutes[$controllerName] = $routes;
                    }
                }
            }
            $this->allMenuLinkRoutes = $menuLinkRoutes;
        }


        // Check post types have routes
        $postTypes = PostType::cache()->getItems()->where('isVisible', true);
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
                    $this->allMenuLinkRoutes[$postTypeControllerName][$postType->slug] = Post::getDefaultPostRoutes($postType);
                }
            }else{ // Define postType route if not defined in PostController

                // Default Post Type routes
                if (!isset($this->allMenuLinkRoutes['PostController']['post_type'][$postType->slug])) {
                    $this->allMenuLinkRoutes['PostController']['post_type'][$postType->slug] = Post::getDefaultPostTypeRoutes($postType);
                }
                // default post routes
                if(!isset($this->allMenuLinkRoutes['PostController'][$postType->slug])){
                    $this->allMenuLinkRoutes['PostController'][$postType->slug] = Post::getDefaultPostRoutes($postType);
                }
            }
        }

        return $this->allMenuLinkRoutes;
    }

    /**
     * Get all panels from controllers' menuLinkRoutes.
     * Used to create link panels options for adding in the menu.
     *
     * @return array
     * @throws \Exception
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
     * Get menu Link Routes by post type or return default ones.
     *
     * @param $controller
     * @param string $belongsTo
     * @return array|mixed
     * @throws \Exception
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
     * Create a menu link parent child relation array.
     *
     * @param $lang
     * @param $id
     * @return array
     * @throws \Exception
     */
    public function detailsJSON($lang, $id){
        // check if user has permissions to access this link
        if(!User::hasAccess('Menu','read')){
            return $this->noPermission();
        }
        // get selected menu
        $menu = Menu::findByID($id);

        // TODO me i marr te dhanat e menu linkave prej cachit
        $this->existingMenuLinks = MenuLink::where('menuID', $id)->orderBy("order", "ASC")->get()->toArray();
        $convertedMenuLinks = $this->convertToParentChild($this->existingMenuLinks);
        $convertedMenuLinks = $this->filterParents($convertedMenuLinks);

        $final = array(
            'list' => $convertedMenuLinks,
            'menu' => $menu,
            'languages' => Language::cache()->getItems()
        );

        // Fire event
        $final['events'] = Event::fire('menu:pre_update', [$final]);

        return $final;
    }

    /**
     * Converts the one dimensional array of menu links to multidimensional array where each parent link has a object with
     * key 'children' where his children are stored.
     *
     * @param $menuLinks
     * @return array
     * @throws \Exception
     */
    private function convertToParentChild($menuLinks){
        $tmp = [];
        $count = 0;
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

            $tmp[$count] = $menuLink;
            $children = $this->getChildren($menuLink['menuLinkID']);
            if(count($children)){
                $tmp[$count]['children'] = $this->convertToParentChild($children);
            }
            $count++;
        }
        return $tmp;
    }

    /**
     * Get all children of a menu link.
     *
     * @param $parentID
     * @return array
     */
    private function getChildren($parentID){
        $tmp = [];
        $count = 0;
        foreach($this->existingMenuLinks as $key => $menuLink){
            if ($menuLink['parent'] == $parentID){
                $tmp[$count] = $menuLink;
                $count++;
            }
        }
        return $tmp;
    }

    /**
     * Remove children links from first dimension of array
     *
     * @param $menuLinks
     * @return array
     */
    private function filterParents($menuLinks){
        $tmp = [];
        foreach($menuLinks as $key => $menuLink){
            if(isset($menuLink['parent']) && !$menuLink['parent']){
                $tmp[$key] = $menuLink;
            }
        }
        return $tmp;
    }


    /**
     * Delete menu with his menu links
     *
     * @param $lang
     * @param $menuID
     * @return array
     */
    public function deleteMenu($lang, $menuID){
        // check if user has permissions to access this link
        if(!User::hasAccess('Menu','delete')){
            return $this->noPermission();
        }

        $menu = Menu::find($menuID);
        if(!$menu){
            return $this->response("Menu doesn't exist", 400);
        }

        if(!$menu->isPrimary){
            $menuLinks = MenuLink::where('menuID', $menuID);
            $menuLinks->delete();
            $menu->delete();
            return $this->response("Menu deleted", 200, Menu::getPrimaryMenuID());
        }else{
            return $this->response("Primary menu can not be deleted", 400);
        }
    }
}
