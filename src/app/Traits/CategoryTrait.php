<?php
namespace Accio\App\Traits;

use App\Models\Language;
use App\Models\MenuLink;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use \App\Models\Menu;

trait CategoryTrait{

    /**
     * @var array
     */
    private $categoriesToBeDeleted = [];

    /**
     * @var array used for parent child relation
     */
    public $categoryList = [];

    /**
     * Find by ID
     *
     * @param int $categoryID ID of Category
     *
     * @return array|null Category data if found, false if not found
     *
     */
    public static function findByID($categoryID){
        $categories = \App\Models\Category::cache();
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
        $categories = \App\Models\Category::cache();
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
            $categories = self::cache();
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

    /**
     * Make parent child tree
     * (every category has a children array that contains his children)
     * @param $list
     * @return array
     */
    public function makeChildrenTree($categories){
        $tmp = [];
        foreach ($categories as $key => $category){
            if(!key_exists('children', $category)){
                $category->children = [];
            }

            // get children
            $children = $this->getChildren($category->categoryID);

            // call self to get a loop until every category has his children
            $category->children = $this->makeChildrenTree($children);

            $tmp[] = $category;
        }

        return Language::filterRows($tmp, false);
    }

    /**
     * Get children of a category
     *
     * @param $parentID
     * @return array
     */
    public function getChildren($parentID){
        $tmp = [];
        foreach($this->categoryList as $key => $item){
            if($item->parentID == $parentID){
                $tmp[] = $item;
            }
        }

        return $tmp;
    }

    /**
     * Get all children tree of a parent
     * @param $parentID
     */
    public function getAllChildren($parentID){
        $children = $this->getChildren($parentID);
        foreach ($children as $key => $child){
            $this->categoriesToBeDeleted[$child->categoryID] = $child;
            $this->getAllChildren($child->categoryID);
        }
    }

    /**
     * Delete List of children
     * @param int $parentID
     * @param int $postTypeID
     * @return mixed
     */
    public function deleteChildren(int $parentID, int $postTypeID){
        $this->categoryList = self::where("postTypeID", $postTypeID)->get();
        $this->getAllChildren($parentID);
        foreach($this->categoriesToBeDeleted as $cat){
            // Post type should not be able to be deleted if it has posts
            if(Category::isInMenuLinks($cat->categoryID)){
                return $this->response("You can't delete this Category because it is part of a menu.", 403);
            }

            // delete relations
            DB::table('categories_relations')->where("categoryID", $cat->categoryID)->delete();
            // delete children
            $cat->delete();
        }
    }

}