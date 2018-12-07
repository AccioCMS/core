<?php

/**
 * Post Type model
 *
 * Handle permissions of user groups
 * @author Jetmir Haxhisefa <jetmir.haxhisefa@manaferra.com>
 * @author Faton Sopa <faton.sopa@manaferra.com>
 * @version 1.0
 */
namespace Accio\App\Models;

use Accio\App\Services\AccioQuery;
use App\Models\Language;
use App\Models\Post;
use App\Models\PostType;
use DB;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Accio\App\Traits;
use Illuminate\Support\Facades\File;
use Spatie\Activitylog\Traits\LogsActivity;

class PostTypeModel extends Model{

    use
      Cachable,
      Traits\PostTypeTrait,
      LogsActivity,
      Traits\BootEventsTrait,
      Traits\CollectionTrait;

    /**
     * Fields that can be filled in CRUD.
     *
     * @var array $fillable
     */
    protected $fillable = [
      'createdByUserID', 'name', 'slug', 'isVisible', 'fields', 'hasCategories', 'isCategoryRequired', 'hasTags', 'isTagRequired'
    ];

    /**
     * The table associated with the model.
     *
     * @var string $table
     */
    public $table = "post_type";

    /**
     * The primary key of the table.
     *
     * @var string $primaryKey
     */
    public $primaryKey = "postTypeID";

    /**
     * The path to back-end view directory.
     *
     * @var string $backendPathToView
     */
    public static $backendPathToView = "backend.post_types.";

    /**
     * Lang key that points to the multi language label in translate file.
     *
     * @var string
     */
    public static $label = "PostTypes.label";

    /**
     * Default permissions that will be listed in settings of permissions.
     *
     * @var array $defaultPermissions
     */
    public static $defaultPermissions = ['create','read', 'update', 'delete'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
      'fields' => 'object',
    ];

    //TODO document it and remove it from here
//    public static $customPermissions = [
//        'service' => [
//            'title' => [
//                'type' => 'checkbox',
//                'label' => 'Title',
//            ],
//        ]
//    ];


    /**
     * Default number of rows per page to be shown in admin panel.
     *
     * @var integer $rowsPerPage
     */
    public static $rowsPerPage = 100; // how many rows to appear in the pagination

    /**
     * @var array
     */
    private static $customFieldsArray = [];

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
    public function __construct(array $attributes = []){
        parent::__construct($attributes);
        Event::fire('postType:construct', [$this]);
    }

    /**
     * Get a field by using it's slug.
     *
     * @param string $fieldSlug
     * @return mixed
     */
    public function field(string $fieldSlug){
        foreach ($this->fields as $field){
            if($field->slug == $fieldSlug){
                return $field;
            }
        }
        return null;
    }

    /**
     * Get Value of a multioptions field by using it's key.
     *
     * @param string $fieldSlug
     * @param string $key
     * @return mixed
     * @throws \Exception
     */
    public function getMultioptionFieldValue(string $fieldSlug, $key){
        if(is_null($key)){
            return null;
        }

        $field = $this->field($fieldSlug);
        if(!$field){
            throw new \Exception("No field with slug ".$fieldSlug);
        }
        $options = explode(",", $field->multioptionValues);

        foreach ($options as $option){
            $optionArr = explode(":", $option);
            if($optionArr[0] == $key){
                $value  = $optionArr[1];

                // remove comma
                if(substr($value, -1) == ','){
                    $value = substr($value, 0, -1);
                }
                return $value;
            }
        }
    }

    /**
     * Define menu panel.
     *
     * @return array
     */
    protected static function menuLinkPanel(){
        return [
          'label' => 'Post Types',
          'controller' => 'PostController',
          'belongsTo' => 'post_type',
          'search' => [
            'label' => trans('base.search'),
            'placeholder' => trans('base.searchPlaceholder'),
            'url' => route('backend.postType.menuPanelItems', ['keyword' => ""])
          ],
          'items' => [
            'label' => trans('base.latest'),
            'url' => route('backend.postType.menuPanelItems')
          ],
        ];
    }

    /**
     * Declare columns that should be saved in MenuLinks table as 'attributes', to enable navigation in front-end.
     *
     * @return array
     */
    public  function menuLinkParameters(){
        return [
          'postTypeID'    => $this->postTypeID,
          'postTypeSlug'  => cleanPostTypeSlug($this->slug)
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function categories(){
        return $this->hasMany('App\Models\Category', 'postTypeID');
    }

    /** Validate a post type from url.
     *
     * @param string $postTypeSlug
     * @return bool
     */
    public static function validatePostType($postTypeSlug = ''){
        if(!$postTypeSlug){
            $postTypeSlug = \Request::route('postTypeSlug');
        }
        return ($postTypeSlug && !PostType::findBySlug($postTypeSlug));
    }

    /**
     * Destruct model instance.
     */
    public function __destruct(){
        Event::fire('postType:destruct', [$this]);
    }

    /**
     * Generate slug of a field. Uses camel case to create the slug from a string.
     *
     * @param string $slug
     * @param array $usedSlugs
     * @return string
     */
    public static function generateSlug(string $slug, array $usedSlugs){
        $slug = camel_case($slug);
        $count = 1;

        // adds a number in the slug string if the specific slug allready exists
        if (in_array($slug, $usedSlugs)){
            while(true){
                $slug = camel_case($slug."_".$count);
                if (!in_array($slug, $usedSlugs)){
                    return $slug;
                }
                $count++;
            }
        }

        return $slug;
    }


    /**
     * Create table and his columns when post type is created.
     *
     * @param $postTypeSlug
     * @param $fields
     * @return array
     */
    public static function createTable($postTypeSlug, $fields = [], $hasCategories = false, $hasTags = false, $createRoutes = true, $controller = null){
        self::$customFieldsArray = [];

        // create new table for the posts of the post type
        $isTableCreated = Schema::create($postTypeSlug, function ($table) use ($fields) {
            // create general fields
            $table->bigIncrements("postID");
            $table->unsignedInteger("createdByUserID")->nullable();
            $table->json("title")->nullable();
            $table->json("content")->nullable();
            $table->json("customFields")->nullable();
            $table->unsignedInteger("featuredImageID")->nullable();
            $table->unsignedInteger("featuredVideoID")->nullable();
            $table->json("status")->nullable();
            $table->datetime("published_at")->nullable()->index();
            $table->json("slug")->nullable();

            $table->foreign('createdByUserID')
              ->references('userID')->on('users')
              ->onDelete('cascade');

            $table->foreign('featuredImageID')
              ->references('mediaID')->on('media')
              ->onDelete('set null');

            $table->foreign('featuredVideoID')
              ->references('mediaID')->on('media')
              ->onDelete('set null');

            $post = new PostType();
            $usedSlugs = [];
            $usedSlugs = array_merge($usedSlugs, $post->fillable);

            foreach($fields as $field){
                // generate unique slugs for every field
                if(!isset($field['slug']) || $field['slug'] == ""){
                    $slug = self::generateSlug($field['name'], $usedSlugs);
                }else{
                    $slug = self::generateSlug($field['slug'], $usedSlugs);
                }
                $usedSlugs[] = $slug;

                $field['slug'] = $slug;
                array_push(self::$customFieldsArray, $field);

                // create other fields
                self::createDatabaseFields($table, $field, $slug);
            }
            $table->timestamps();
        });

        self::createMediaRelationsTable($postTypeSlug);

        if($hasCategories){
            self::createCategoryRelationsTable($postTypeSlug);
        }

        if($hasTags){
            self::createTagRelationsTable($postTypeSlug);
        }

        if($createRoutes) {
            self::createRouteFile($postTypeSlug, $controller);
        }

        self::createVirtualColumnsForSlug($postTypeSlug);

        return self::$customFieldsArray;
    }

    /**
     * Create virtual columns for slug in every language
     *
     * @param string $postTypeSlug
     * @param array|string $languageSlugs
     */
    public static function createVirtualColumnsForSlug(string $postTypeSlug, $languageSlugs = []){
        $languageSlugs = ($languageSlugs && !is_array($languageSlugs) ? explode(" ", $languageSlugs): $languageSlugs);
        $languageSlugs = ($languageSlugs ? $languageSlugs : Language::all()->pluck(['slug'])->toArray());

        $virtualColumns = [];
        foreach ($languageSlugs as $languageSlug){
            $virtualColumns[] = [
              "name" => $languageSlug,
              "type" => "string",
              "index" => true,
              "length" => 200,
            ];
        }
        AccioQuery::createVirtualColumns($postTypeSlug, "slug", $virtualColumns);
    }

    /**
     * Creates media relations table for the selected Post Type
     *
     * @param $postTypeSlug
     */
    private static function createMediaRelationsTable($postTypeSlug){
        Schema::create($postTypeSlug."_media", function ($table) use($postTypeSlug){
            // create general fields
            $table->bigIncrements("mediaRelationID");
            $table->bigInteger("postID")->unsigned()->nullable();
            $table->unsignedInteger("mediaID")->nullable();
            $table->json("language")->nullable();
            $table->string("field", 60)->nullable();

            $table->foreign('postID')
              ->references('postID')->on($postTypeSlug)
              ->onDelete('cascade');

            $table->foreign('mediaID')
              ->references('mediaID')->on("media")
              ->onDelete('cascade');
        });
    }

    /**
     * Creates category relations table for the selected Post Type
     *
     * @param $postTypeSlug
     */
    private static function createCategoryRelationsTable($postTypeSlug){
        if(!Schema::hasTable($postTypeSlug."_categories")) {
            Schema::create($postTypeSlug."_categories", function ($table)  use($postTypeSlug){
                // create general fields
                $table->bigIncrements("categoryRelationID");
                $table->bigInteger("postID")->unsigned()->nullable();
                $table->unsignedInteger("categoryID")->nullable();

                $table->foreign('postID')
                  ->references('postID')->on($postTypeSlug)
                  ->onDelete('cascade');

                $table->foreign('categoryID')
                  ->references('categoryID')->on("categories")
                  ->onDelete('cascade');

                $table->unique(array('postID', 'categoryID'));
            });
        }
    }

    /**
     * Creates media relations table for the selected Post Type
     *
     * @param $postTypeSlug
     */
    private static function createTagRelationsTable($postTypeSlug){
        if(!Schema::hasTable($postTypeSlug."_tags")) {
            Schema::create($postTypeSlug."_tags", function ($table)  use($postTypeSlug){
                // create general fields
                $table->bigIncrements("tagRelationID");
                $table->bigInteger("postID")->unsigned()->nullable();
                $table->unsignedInteger("tagID")->nullable();
                $table->string("language", 5)->nullable();

                $table->foreign('postID')
                  ->references('postID')->on($postTypeSlug)
                  ->onDelete('cascade');

                $table->foreign('tagID')
                  ->references('tagID')->on("tags")
                  ->onDelete('cascade');

                $table->unique(array('postID', 'tagID', 'language'));
            });
        }
    }

    /**
     * Update post type table and add new columns.
     *
     * @param $postTypeSlug
     * @param $fields
     * @return array
     */
    public static function updateTable($postTypeSlug, $fields, $hasTags = false, $hasCategories = false){
        self::$customFieldsArray = [];

        Schema::table($postTypeSlug, function($table) use($fields){
            $post = new Post();
            $usedSlugs = [];
            $usedSlugs = array_merge($usedSlugs, $post->fillable);

            foreach($fields as $field){
                // generate unique slugs for every field
                if(!isset($field['slug']) || $field['slug'] == ""){
                    $slug = self::generateSlug($field['name'], $usedSlugs);
                }else{
                    $slug = self::generateSlug($field['slug'], $usedSlugs);
                }
                $usedSlugs[] = $slug;

                $field['slug'] = $slug;

                self::createDatabaseFields($table, $field, $slug, false);

                // unset the canBeRemoved from array
                if(isset($field['canBeRemoved']) && $field['canBeRemoved'] == true){
                    unset($field['canBeRemoved']);
                }

                array_push(self::$customFieldsArray, $field);
            }
        });

        if($hasCategories){
            self::createCategoryRelationsTable($postTypeSlug);
        }

        if($hasTags){
            self::createTagRelationsTable($postTypeSlug);
        }

        return self::$customFieldsArray;
    }

    /**
     * Create fields of post type table.
     *
     * @param object $table
     * @param object $field
     * @param string $slug
     * @param string $isCreate
     */
    private static function createDatabaseFields($table, $field, $slug, $isCreate = true){
        // insert column only if it is not already in the DB
        if ($isCreate || (isset($field['canBeRemoved']) && $field['canBeRemoved'])){
            // if field is translatable
            if($field['translatable']){
                $table->json($slug)->nullable();
            }else{
                if($field['type']['inputType'] == "text" || $field['type']['inputType'] == "email" || $field['type']['inputType'] == "checkbox" || $field['type']['inputType'] == "radio"){
                    $table->string($slug)->nullable();
                }else if ($field['type']['inputType'] == "textarea" || $field['type']['inputType'] == "dropdown" || $field['type']['inputType'] == "editor"){
                    $table->text($slug)->nullable();
                }else if ($field['type']['inputType'] == "number"){
                    $table->integer($slug)->nullable();
                }else if ($field['type']['inputType'] == "date"){
                    $table->dateTime($slug)->nullable();
                }else if ($field['type']['inputType'] == "boolean"){
                    $table->tinyInteger($slug)->nullable();
                }else if ($field['type']['inputType'] == "db"){
                    if($field['isMultiple']){
                        $table->json($slug)->nullable();
                    }else{
                        $table->integer($slug)->nullable();
                    }
                }
            }
        }
    }

    /**
     * Creates route file for new post types.
     *
     * @param string $slug
     * @return mixed
     */
    public static function createRouteFile(string $slug, $controller = 'PostController'){
        $stub =  File::get(stubPath('PostType'));
        $route = str_replace('DummySlug',cleanPostTypeSlug($slug),$stub);
        $route = str_replace('DummyController',$controller,$route);
        $bytes_written = File::put(base_path().'/routes/'.$slug.'.php', $route);
        if ($bytes_written === false){
            die("Error writing to new route file");
        }
        return $route;
    }

}
