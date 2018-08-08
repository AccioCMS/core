<?php

namespace Accio\App\Traits;

use App\Models\Menu;
use Illuminate\Support\HtmlString;
use App\Models\MenuLink;

trait MenuTrait
{

    /**
     * Stores the ID of primary menu.
     *
     * @var int $primaryMenuID
     */
    private  static $primaryMenuID;

    /**
     * Get menu links of a particular menu.
     *
     * @param $menuSlug
     * @return array|\Illuminate\Support\Collection|mixed
     * @throws \Exception
     */
    public static function getMenuLinks($menuSlug){
        // Set active MenuLinks
        MenuLink::setActiveIDs(true);

        $menuData = self::findBySlug($menuSlug);
        $menuLinks = MenuLink::cache()->where('menuID', $menuData->menuID)->sortBy('order');

        if($menuLinks){
            return MenuLink::children($menuLinks);
        }
    }

    /**
     * Set the ID of primary menu.
     *
     * @throws \Exception
     */
    public static function setPrimaryMenuID(){
        if(Menu::cache()) {
            $primaryMenu = Menu::cache()->where('isPrimary', 1);

            //if no primary menu is found, get the first one from the list
            if (!$primaryMenu) {
                $primaryMenu = Menu::cache()->first();
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
     * @return HtmlString
     * @throws \Exception
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
     * Get Menu by slug.
     * 
     * @param $slug
     * @return mixed|null
     * @throws \Exception
     */
    public static function findBySlug($slug){
        $menu = Menu::cache();
        if($menu) {
            $getMenuLink = $menu->where('slug', $slug);

            if ($getMenuLink) {
                return $getMenuLink->first();
            }
        }
        return null;
    }

    /**
     * Get Menu by ID.
     *
     * @param $menuID
     * @return mixed|null
     * @throws \Exception
     */
    public static function findByID($menuID){
        $menu = Menu::cache();
        if($menu) {
            $getMenuLink = $menu->where('id', $menuID);

            if ($getMenuLink) {
                return $getMenuLink->first();
            }
        }
        return null;
    }

}