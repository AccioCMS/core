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

use App\Models\Post;
use App\Models\PostType;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Accio\App\Traits;
use Illuminate\Support\Facades\File;
use Spatie\Activitylog\Traits\LogsActivity;

class PostTypeModel extends Model{

    use
      Traits\PostTypeTrait,
      LogsActivity,
      Traits\CacheTrait,
      Traits\BootEventsTrait,
      Traits\CollectionTrait;

    /**
     * Fields that can be filled in CRUD
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
     * The primary key of the table
     *
     * @var string $primaryKey
     */
    public $primaryKey = "postTypeID";

    /**
     * The path to back-end view directory
     *
     * @var string $backendPathToView
     */
    public static $backendPathToView = "backend.post_types.";

    /**
     * Lang key that points to the multi language label in translate file
     * @var string
     */
    public static $label = "PostTypes.label";

    /**
     * Default permissions that will be listed in settings of permissions
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
     * Default number of rows per page to be shown in admin panel
     *
     * @var integer $rowsPerPage
     */
    public static $rowsPerPage = 100; // how many rows to appear in the pagination


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
     * Get a field by using it's slug
     * @param string $fieldSlug
     * @return mixed
     */
    public function field(string $fieldSlug){
        // TODO me hek json decode me bo me cast
        foreach ($this->fields as $field){
            if($field->slug == $fieldSlug){
                return $field;
            }
        }
    }

    /**
     * Get Value of a multioptions field by using it's key
     *
     * @param string $fieldSlug
     * @param string $key
     * @return mixed
     * @throws \Exception
     */
    public function getMultioptionFieldValue(string $fieldSlug, $key){
        if(is_null($key)){
            return;
        }

        $field = $this->field($fieldSlug);
        if(!$field){
            throw new \Exception("No field with slug ".$fieldSlug);
        }
        $options = explode("\n", $field->multioptionValues);

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
     * Define menu panel
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
     * Declare columns that should be saved in MenuLinks table as 'attributes', to enable navigation in front-end
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

    /** Validate a post type from url
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
     * Destruct model instance
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
                $slug = camel_case($slug."_".$count, '_');
                if (!in_array($slug, $usedSlugs)){
                    return $slug;
                }
                $count++;
            }
        }

        return $slug;
    }


    /**
     * @param $postTypeSlug
     * @param $fields
     * @return array
     */
    public static function createTable($postTypeSlug, $fields = [], $connection = 'mysql', $createRoutes = true){
        self::$customFieldsArray = [];

        if(!Schema::connection($connection)->hasTable($postTypeSlug)) {
            // create new table for the posts of the post type
            Schema::connection($connection)->create($postTypeSlug, function ($table) use ($fields) {
                $table->bigIncrements("postID");
                $table->integer("createdByUserID")->nullable();
                $table->json("title")->nullable();
                $table->json("content")->nullable();
                $table->json("customFields")->nullable();
                $table->integer("featuredImageID")->nullable();
                $table->integer("featuredVideoID")->nullable();
                $table->json("status")->nullable();
                $table->datetime("published_at")->nullable()->index();
                $table->json("slug")->nullable();

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

                    // if field is translatable make field json
                    if($field['translatable']){
                        $table->json($slug)->nullable();
                        continue;
                    }

                    // for non translatable field create for each type
                    if($field['type']['inputType'] == "text" || $field['type']['inputType'] == "email" || $field['type']['inputType'] == "checkbox" || $field['type']['inputType'] == "radio") {
                        $table->string($slug)->nullable();
                    }else if ($field['type']['inputType'] == "textarea" || $field['type']['inputType'] == "dropdown" || $field['type']['inputType'] == "editor") {
                        $table->text($slug)->nullable();
                    }else if ($field['type']['inputType'] == "number") {
                        $table->integer($slug)->nullable();
                    }else if ($field['type']['inputType'] == "date") {
                        $table->dateTime($slug)->nullable();
                    }else if ($field['type']['inputType'] == "boolean") {
                        $table->tinyInteger($slug)->nullable();
                    }else if ($field['type']['inputType'] == "db") {
                        if($field['isMultiple']){
                            $table->json($field['dbTable']['name'])->nullable();
                        }else {
                            $table->integer($field['dbTable']['name'])->nullable();
                        }
                    }
                }
                $table->timestamps();
            });

            if($createRoutes) {
                self::createRouteFile($postTypeSlug);
            }
        }

        return self::$customFieldsArray;
    }

    /**
     * @param $postTypeSlug
     * @param $fields
     * @return array
     */
    public static function updateTable($postTypeSlug, $fields){
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

                // insert column only if it is not already in the DB
                if (isset($field['canBeRemoved']) && $field['canBeRemoved'] == true){
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

                // unset the canBeRemoved from array
                if(isset($field['canBeRemoved']) && $field['canBeRemoved'] == true){
                    unset($field['canBeRemoved']);
                }

                array_push(self::$customFieldsArray, $field);
            }
        });

        return self::$customFieldsArray;
    }

    /**
     * Creates rout file for new post types
     * @param string $slug
     * @return mixed
     */
    public static function createRouteFile(string $slug){
        $stub =  File::get(stubPath('PostType'));
        $route = str_replace('DummySlug',cleanPostTypeSlug($slug),$stub);
        $bytes_written = File::put(base_path().'/routes/'.$slug.'.php', $route);
        if ($bytes_written === false){
            die("Error writing to new route file");
        }
        return $route;
    }
}
