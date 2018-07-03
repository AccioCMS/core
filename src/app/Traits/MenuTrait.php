<?php

namespace Accio\App\Traits;

use App\Models\Menu;
use Illuminate\Support\HtmlString;
use App\Models\MenuLink;

trait MenuTrait
{
    /**
     * Store MenuLinks by menu
     *
     * @var array $menuLinksByMenu
     * */
    private static $menuLinksByMenu = [];

    /**
     * Stores the ID of primary menu.
     *
     * @var int $primaryMenuID
     */
    private  static $primaryMenuID;

    /**
     * Set MenuLinks by menu
     *
     * @return object|null;
     */
    public static function setMenuLinksByMenu(){
        //firstly, list them by MenuID
        $menuLinksByMenu = [];
        foreach (\App\Models\MenuLink::cache()->getItems() as $menuLink) {
            $menuLinksByMenu[$menuLink->menuID][$menuLink->menuLinkID] = $menuLink;
        }

        // than go through each Menu and sort their MenuLinks
        foreach ($menuLinksByMenu as $menuID => $menuList){
            $sortedMenuLinks = collect($menuList)->sortBy('order');
            self::$menuLinksByMenu[$menuID] = MenuLink::children($sortedMenuLinks);
        }
        return;
    }


    /**
     * Get menu links of a particular menu
     *
     * @param  string $menuSlug The slug of the menu we want to get MenuLinks from
     * @return object|array  Returns MenuLinks of requested menu if found
     * */
    public static function getMenuLinks($menuSlug){
        // Set active MenuLinks
        MenuLink::setActiveIDs(true);

        $menuData = self::findBySlug($menuSlug);
        if($menuData && isset(self::$menuLinksByMenu[$menuData['menuID']])){
            return self::$menuLinksByMenu[$menuData['menuID']];
        }

        return [];
    }

    /**
     * Set the ID of primary menu
     *
     * @return int|void Returns the ID of primary Menu if found, null instead
     **/
    public static function setPrimaryMenuID(){
        if(Menu::cache()) {
            $primaryMenu = Menu::cache()->getItems()->where('isPrimary', 1);

            //if no primary menu is found, get the first one from the list
            if (!$primaryMenu) {
                $primaryMenu = Menu::cache()->getItems()->first();
            } else {
                $primaryMenu = $primaryMenu->first();
            }

            if (isset($primaryMenu->menuID)) {
                self::$primaryMenuID = $primaryMenu->menuID;
            }
        }
        return;
    }

    /**
     * Get the ID of primary menu
     *
     * @return int|null Returns the ID of primary Menu if found, null instead
     **/
    public static function getPrimaryMenuID(){
        return self::$primaryMenuID;
    }

    /**
     * Prints MenuLinks of a menu
     *
     * @param string $menuSlug Slug of Menu
     * @param string $customView Name of a custom blade.php file to render the template
     * @param string $ulClass Class of ul
     * @return HtmlString Returns an html navigation of a particular menu
     */
    public static function printMenu($menuSlug = "primary", $customView = '', $ulClass = ''){
        $menuLinks = self::getMenuLinks($menuSlug);
        if($menuLinks) {
            return new HtmlString(view()->make(($customView ? $customView : "vendor.menulinks.bootstrap-4"), [
              'menuLinks' => $menuLinks,
              'ulClass' => $ulClass,
              'menuSlug'=> $menuSlug

            ])->render());
        }
    }



    /**
     * Get Menu by slug
     *
     * @param  string $slug  The slug of Menu ex. "primary"
     * @return object|boolean Menu data if found, null instead
     *
     */
    public static function findBySlug($slug){
        if(\App\Models\Menu::cache()->getItems()) {
            $getMenuLink = \App\Models\Menu::cache()->getItems()->where('slug', $slug);

            if ($getMenuLink) {
                return $getMenuLink->first();
            }
        }
        return null;
    }

    /**
     * Get Menu by ID
     *
     * @param  int $menuID The ID of Menu.
     * @return object|null Menu data if found, null instead
     *
     */
    public static function findByID($menuID){
        if(\App\Models\Menu::cache()->getItems()) {
            $getMenuLink = \App\Models\Menu::cache()->getItems()->where('id', $menuID);

            if ($getMenuLink) {
                return $getMenuLink->first();
            }
        }
        return null;
    }

}