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

use App\Models\Category;
use App\Models\PostType;
use App\Models\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Accio\App\Traits;
use Accio\Support\Facades\Meta;
use Spatie\Activitylog\Traits\LogsActivity;

class CategoryModel extends Model{

    use
      Traits\CategoryTrait,
      Traits\TranslatableTrait,
      LogsActivity,
      Traits\CacheTrait,
      Traits\BootEventsTrait;

    /**
     * Fields that can be filled in CRUD
     *
     * @var array $fillable
     */
    protected $fillable = ['postTypeID','parentID','customFieldID','featuredImageID','title','slug','description','order','createdByUserID','isVisible', 'customFields'];

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
     * @var bool
     */
    protected static $logFillable = true;

    /**
     * @var bool
     */
    protected static $logOnlyDirty = true;

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
     * Parent of a category
     * @return HasOne
     */
    public function parent(){
        return $this->hasOne('App\Models\Category','categoryID','parentID');
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
            throw new \Exception("Route $routeName not found");
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
        
        self::saved(function($category){
            Category::_saved($category);
        });
    }

    /**
     * Perform certain actions after a category is saved
     *
     * @param object $category
     * */
    private static function _saved($category){
        self::updateMenulink($category);
    }

    /**
     * Destruct model instance
     */
    public function __destruct()
    {
        Event::fire('category:destruct', [$this]);
    }
}
