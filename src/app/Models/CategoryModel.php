<?php

/**
 * Categories Model
 *
 * It handles Categories management
 * @author Jetmir Haxhisefa <jetmir.haxhisefa@manaferra.com>
 * @author Faton Sopa <faton.sopa@manaferra.com>
 * @version 1.0
 */

namespace Accio\App\Models;

use App\Models\PostType;
use App\Models\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Accio\App\Traits;
use Accio\Support\Facades\Meta;

class CategoryModel extends Model{

    use Traits\CategoryTrait, Traits\TranslatableTrait;

    /**
     * Fields that can be filled in CRUD
     *
     * @var array $fillable
     */
    protected $fillable = ['postTypeID','customFieldID','featuredImageID','title','slug','description','order','createdByUserID','isVisible', 'customFields'];

    /**
     * The primary key of the table
     *
     * @var string $primaryKey
     */
    public $primaryKey = "categoryID";

    /**
     * The table associated with the model.
     *
     * @var string $table
     */
    public $table = "categories";

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'title' => 'object',
        'description' => 'object',
        'slug' => 'object',
        'isVisible' => 'object',
        'customFields' => 'object',
    ];

    /**
     * Default number of rows per page to be shown in admin panel
     *
     * @var integer $rowsPerPage
     */
    public static $rowsPerPage = 50;

    /**
     * Lang key that points to the multi language label in translate file
     * @var string
     */
    public static $label = "categories.label";

    /**
     * Default permissions that will be listed in settings of permissions
     *
     * @var array $defaultPermissions
     */
    public static $defaultPermissions = ['create','read', 'update', 'delete'];

    /**
     * @inheritdoc
     * */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        Event::fire('category:construct', [$this]);
    }

    /**
     * Define menu panel
     * @return array
     */
    protected static function menuLinkPanel(){
        return [
            'label' => 'Category',
            'controller' => 'CategoryController',
            'search' => [
                'label' => trans('base.search'),
                'placeholder' => trans('base.searchPlaceholder'),
                'url' => route('backend.category.menuPanelItems', ['keyword' => ""])
            ],
            'items' => [
                'label' => trans('base.latest'),
                'url' => route('backend.category.menuPanelItems')
            ],
        ];
    }

    /**
     * Declare columns that should be saved in MenuLinks table as 'attributes', to enable navigation in front-end
     *
     * @return array
     */
    public function menuLinkParameters()
    {
        $previousAutoTranslate = $this->getAutoTranslate();
        $this->setAutoTranslate(false);

        $postType = PostType::findByID($this->postTypeID);

        $data = [
            'categoryID'    => $this->categoryID,
            'categorySlug'  => $this->slug,
            'postTypeSlug'  => cleanPostTypeSlug($postType->slug),
        ];

        $this->setAutoTranslate($previousAutoTranslate);
        return $data;
    }

    /**
     * Featured image of a category
     * @return HasOne
     */
    public function featuredImage()
    {
        return $this->hasOne('App\Models\Media','mediaID','featuredImageID');
    }

    /**
     * Generate the URL to a category
     *
     *
     * @return string
     */
    public function getHrefAttribute(){
        return $this->href();
    }

    /**
     * Generate a custom URL to a category
     *
     * @param string $routeName
     * @param array $customAttributes
     *
     * @return string
     */
    public function href($routeName = '', $customAttributes = []){
        if(!$routeName){
            $routeName = 'category.posts';
        }
        $getRoute = Route::getRoutes()->getByName($routeName);
        if($getRoute) {
            $routeParams = Route::getRoutes()->getByName($routeName)->parameterNames();

            // translating language
            if($this->getTranslateLanguage()){
                $languageSlug = $this->getTranslateLanguage();
            }
            else{
                $languageSlug = App::getLocale();
            }

            //set only requested params
            $params = [];
            foreach($routeParams as $name){

                switch ($name){
                    case 'postTypeSlug';

                        $postType = PostType::findByID($this->postTypeID);
                        $params['postTypeSlug'] = cleanPostTypeSlug($postType->slug);
                        break;

                    case 'categorySlug';
                        $params['categorySlug'] = $this->slug;
                        break;

                    case 'categoryID';
                        $params['categoryID'] = $this->categoryID;
                        break;

                    case 'lang';
                        // don't show language slug on default language
                        if(config('project.hideDefaultLanguageInURL') && $languageSlug !=  Language::getDefault('slug')) {
                            $params['lang'] = $languageSlug;
                        }
                        break;
                }
            }

            return  route($routeName,$params);
        }else{
            throw new Exception("Route $routeName not found");
        }
    }


    /**
     * Define single user's SEO Meta data
     *
     * @return array
     */
    public function metaData(){
        Meta::setTitle($this->title)
            ->set("description", $this->description)
            ->set("og:type", "profile", "property")
            ->set("og:title", $this->title, "property")
            ->set("og:description", $this->description, "property")
            ->set("og:url",$this->href, "property")
            ->setCanonical($this->href)
            ->setHrefLangData($this)
            ->setWildcards([
                '{title}' => $this->title,
                '{siteTitle}' => settings('siteTitle')
            ]);
    }

     /**
     * Get categories from cache. Cache is generated if not found
     *
     * @param  string $cacheName  Name of the cache ex "post_services". Prefix "categories_" is added automatically on cache name. Default: categories
     * @param  string $languageSlug Language slug
     * @return object|null  Returns requested cache if found, null instead
     * */
    public static function getFromCache(string $cacheName = 'categories', string $languageSlug = ''){
        if(!$languageSlug){
            $languageSlug = App::getLocale();
        }

        //we need an empty cache to fill it later
        if(!Cache::has($cacheName)){
            $cachedPosts = new \stdClass();
            Cache::forever($cacheName,$cachedPosts);
        }else{
            $cachedPosts = Cache::get($cacheName);
        }

        //set cache in this language
        if(!isset($cachedItems->$languageSlug)){

            //handle post type cache
            $postTypeData = PostType::findBySlug($cacheName);
            if($postTypeData) {
                return self::setCacheByPostType($postTypeData, $languageSlug);
            }
            //or a custom cache
            else{
                $functionName = 'setCache'.$cacheName;
                if(method_exists(__CLASS__,$functionName)){
                    return self::$functionName($cacheName,$languageSlug);
                }
            }
        }

        if(isset($cachedItems->$languageSlug)){
            return $cachedItems->$languageSlug;
        }
    }

    /**
     * Get categories cache
     *
     * @param  array  $cacheName
     * @param  string $languageSlug
     * @return object Categories of requested language
     */
    private  static function setCacheCategories($cacheName, $languageSlug){
        $getCategories = self::all()->keyBy('categoryID');

        //setup cache data
        $cachedItems = new \stdClass();
        $cachedItems->$languageSlug = Language::translateList($getCategories, $languageSlug);

        //save cache
        Cache::forever($cacheName,$cachedItems);

        return $cachedItems->$languageSlug;
    }

    /**
     * Delete categories cache
     *
     * @param  object $category
     * @param  string $mode
     */
    private  static function deleteCacheCategories($category, $mode){
        Cache::forget('categories');
    }

    /**
     * Get categories by post type
     *
     * @param  array  $postTypeData Data of post type
     * @param  string $languageSlug Language slug
     * @return object Categories of requested language
     */
    private  static function setCacheByPostType($postTypeData, $languageSlug){
        $cacheName = "categories_".$postTypeData->slug;
        $getCategories = self::where('postTypeID', $postTypeData->postTypeID)->get();

        //setup cache data
        $cachedItems = new \stdClass();
        $cachedItems->$languageSlug = Language::translate($getCategories, $languageSlug);

        //save cache
        Cache::forever($cacheName,$cachedItems);

        return $getCategories;
    }


    /**
     * Delete cached categories by post type
     *
     * @param object $category A single category object
     * @param string $mode Set "saved" or 'deleted" mode
     */
    private static function deleteCacheByPostType($category, $mode){
        $getPostType = PostType::findByID($category->postTypeID);
        Cache::forget('categories_'.$getPostType->slug);
    }


    /**
     * Scope a query to only include visible categories.
     *
     * @param string $languageSlug
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisible($query, $languageSlug = ''){
        if(!$languageSlug){
            $languageSlug = App::getLocale();
        }
        return $query->where('isVisible->'.$languageSlug, true);
    }


    /**
     * Handle callback of insert, update, delete
     * */
    protected static function boot(){
        parent::boot();

        self::saving(function($category){
            Event::fire('category:saving', [$category]);
        });

        self::saved(function($category){
            Event::fire('category:saved', [$category]);
            self::_saved($category);
        });

        self::creating(function($category){
            Event::fire('category:creating', [$category]);
        });

        self::created(function($category){
            Event::fire('category:created', [$category]);
        });

        self::updating(function($category){
            Event::fire('category:updating', [$category]);
        });

        self::updated(function($category){
            Event::fire('category:updated', [$category]);
        });

        self::deleting(function($category){
            Event::fire('category:deleting', [$category]);
        });

        self::deleted(function($category){
            Event::fire('category:deleted', [$category]);
            self::_deleted($category);
        });
    }

    /**
     * Delete Category caches
     * @param object $category
     */
    public static function deleteCache($category){
        $deleteCacheMethods = preg_grep('/^deleteCache/', get_class_methods(__CLASS__));
        foreach($deleteCacheMethods as $method){
            if($method !== 'deleteCache') {
                self::$method($category, "saved");
            }
        }
    }

    /**
     * Perform certain actions after a category is saved
     *
     * @param object $category
     * */
    private static function _saved($category){
        self::updateMenulink($category);
        self::deleteCache($category);
    }

    /**
     * Perform certain actions after a category is deleted
     *
     * @param object $category Deleted category
     * */
    private static function _deleted($category){
        self::deleteCache($category);
    }

    /**
     * Destruct model instance
     */
    public function __destruct()
    {
        Event::fire('category:destruct', [$this]);
    }
}