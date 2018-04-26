<?php
namespace Accio\App\Traits;

use App\Models\Language;
use App\Models\MenuLink;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use \App\Models\Menu;

trait CategoryTrait{
    /**
     * Find by ID
     *
     * @param int $categoryID ID of Category
     *
     * @return array|null Category data if found, false if not found
     *
     */
    public static function findByID($categoryID){
        $categories = \App\Models\Category::getFromCache();
        if($categories){
            return $categories->where('categoryID',$categoryID)->first();
        }
        return;
    }

    /**
     * Find by ID
     *
     * @param string $categorySlug ID of Category
     *
     * @return object|boolean Category data if found, false if not found
     *
     */
    public static function findBySlug($categorySlug){
        $categories = \App\Models\Category::getFromCache();
        if($categories){
            return $categories->where('slug',$categorySlug)->first();
        }
        return;
    }

    /**
     * Find by Post Type
     *
     * @param string $postTypeSlug
     *
     * @return object|boolean Category data if found, false if not found
     *
     */
    public static function findByPostType($postTypeSlug){
        $postType = PostType::findBySlug($postTypeSlug);
        if($postType){
            $categories = self::getFromCache();
            if($categories){
                return $categories->where('postTypeID',$postType->postTypeID);
            }
        }
        return;
    }


    /**
     * Check if a category has posts
     *
     * @param string $postType slug of post type
     * @param string $categoriesID selected category ID
     *
     * @return bool
     *
     */
    public static function hasPosts($postType, $categoriesID){
        if(DB::table('categories_relations')
            ->where('categoryID', $categoriesID)
            ->where('belongsTo', $postType)
            ->count()){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Checks if a category is part of a menulink
     *
     * @param integer $categoriesID ID of the post type
     * @return bool True is the category is being used in menu links, false instead
     */
    public static function isInMenuLinks($categoriesID){
        $isInMenulinks = MenuLink::where('belongsToID', $categoriesID)->where('belongsTo', 'category')->count();
        if ($isInMenulinks){
            return true;
        }
        return false;
    }

    /**
     * Add a category to a Menu
     * @param Menu $menu
     * @return object
     */
    public function addToMenu(Menu $menu){
        if($this->postTypeID) {
            $data = [
                'menuID' => $menu->menuID,
                'belongsToID' => $this->categoryID,
                'belongsTo' => 'category',
                'params' => $this->menuLinkParameters(),
                'routeName' => 'category.posts'
            ];

            foreach (Language::all() as $language) {
                $data['slug'][$language->slug] = $this->translate($language->slug)->slug;
                $data['label'][$language->slug] = $this->title;
            }

            factory(MenuLink::class)->create($data);
        }

        return $this;
    }

    /**
     * Update category paremeters in MenuLink
     * @param object $category
     * @return void
     */
    public static function updateMenulink($category){
        if(self::isInMenuLinks($category->categoryID)){
            $menuLinks = MenuLink::where('belongsToID', $category->categoryID)->where('belongsTo', 'category')->get();
            foreach($menuLinks as $menuLink){
                $menuLink->params = $category->menuLinkParameters();
                $menuLink->save();
            }
        }
    }
}