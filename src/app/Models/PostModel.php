<?php

/**
 * Posts
 *
 * Due to its nature, Posts model are managed dynamically by UI, via PostTypes
 *
 * @author Jetmir Haxhisefa <jetmir.haxhisefa@manaferra.com>
 * @author Faton Sopa <faton.sopa@manaferra.com>
 * @version 1.0
 */
namespace Accio\App\Models;

use App\Models\Category;
use App\Models\CategoryRelation;
use App\Models\Language;
use App\Models\Tag;
use App\Models\TagRelation;
use App\Models\Task;
use App\Models\Theme;
use App\Models\User;
use DateTime;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Accio\Support\Facades\Meta;
use Mockery\Exception;
use Spatie\Activitylog\Traits\LogsActivity;
use Validator;
use Illuminate\Database\Eloquent\Model;
use App\Models\PostType;
use App\Models\Post;
use Accio\App\Traits;

class PostModel extends Model{
    use Traits\PostTrait, Traits\TranslatableTrait, Traits\CustomFieldsValuesTrait, LogsActivity;

    /**
     * The primary table associated with the model.
     *
     * @var string $table
     */
    public $table = 'post_articles';

    /**
     * The temporary table is associated with the "with" method of laravel
     * as it creates a new instance of model, and as a result the $table property has its default value
     *
     * @var string $table
     */
    public static $_tmptable;

    /**
     * Media field to select with media relations
     * @var string $mediaField
     */
    protected $mediaField;


    /**
     * Should we always use tmp table?
     *
     * @var bool
     */
    public static $useTmpTable = false;

    /**
     * homepage's data gotten from post_page.
     *
     * @var array $homepage
     */
    private  static $homepage;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
      'title' => 'object',
      'slug' => 'object',
      'content' => 'object',
      'status' => 'object',
      'customFields' => 'object',
    ];

    /**
     * The primary key of the table
     *
     * @var string $primaryKey
     */
    public $primaryKey = "postID";

    /**
     * Lang key that points to the multi language label in translate file
     * @var string
     */
    public static $label = "posts.label";

    /**
     * The path to back end view directory
     *
     * @var string $backendPathToView
     */
    public static $postsAllowedInTable = 12;

    /**
     * Default number of rows per page to be shown in admin panel
     *
     * @var integer $rowsPerPage
     */
    public static $rowsPerPage = 25;

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
        Event::fire('post:construct', [$this]);
        if(self::$useTmpTable && self::$_tmptable){
            $this->table = self::$_tmptable;
        }
    }


    /**
     * Set the table associated with the model.
     *
     * @param  string  $table
     * @return $this
     */
    public function setTable($table)
    {
        $this->table = 'post_'.cleanPostTypeSlug($table);
        self::$_tmptable = $this->table;
        return $this;
    }

    /**
     * Begin querying a model with eager loading.
     *
     * @param  array|string  $relations
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function with($relations)
    {
        $model = (new static);

        if(self::$_tmptable){
            $model->setTable(self::$_tmptable);
        }

        return $model->newQuery()->with(
          is_string($relations) ? func_get_args() : $relations
        );
    }

    public function newInstance($attributes = [], $exists = false)
    {
        // Overridden in order to allow for late table binding.
        $model = parent::newInstance($attributes, $exists);
        $model->setTable($this->table);
        return $model;
    }

    /**
     * Define menu panel
     * @return array
     */
    protected static function menuLinkPanel(){
        // List one menu panel for each post type
        $panels =[];
        foreach(PostType::getFromCache() as $postType){
            $panels[] = [
              'label' => $postType->name,
              'belongsTo' => $postType->slug,
              'controller' => ($postType->hasCustomController() ? $postType->getCustomController() : 'PostController'),
              'search' => [
                'label' => trans('base.search'),
                'placeholder' => trans('base.searchPlaceholder'),
                'url' => route('backend.post.menuPanelItems', ['keyword' => "", "postTypeSlug" => $postType->slug])
              ],
              'items' => [
                'label' => trans('base.latest'),
                'url' => route('backend.post.menuPanelItems', ["postTypeSlug" => $postType->slug])
              ],
            ];
        }
        return $panels;
    }

    /**
     * Declare columns that should be saved in MenuLinks table as 'attributes', to enable navigation in front-end
     *
     * @return array
     */
    public function menuLinkParameters(){
        $previousAutoTranslate = $this->getAutoTranslate();
        $this->setAutoTranslate(false);

        $data = [
          'postID'        => $this->postID,
          'postSlug'      => $this->slug,
          'date'          => date('Y-m-d',strtotime($this->created_at)),
          'postTypeSlug'  => cleanPostTypeSlug($this->getTable())
        ];

        $this->setAutoTranslate($previousAutoTranslate);
        return $data;
    }

    /**
     * Get posts by Post Type, by a Category or by a custom function
     * If posts are found in cache they are served from it, otherwise it gets them from database
     *
     * @param  string $cacheName  The name of the cache to request
     * @param  int    $categoryID ID of the category to get the posts from
     * @param  string $languageSlug Slug of the language to get the posts from
     * @return object|null  Returns all posts found as requested
     * */
    public  static function getFromCache($cacheName = '', $categoryID = 0, $languageSlug = ""){

        // Default Language
        if(!$languageSlug){
            $languageSlug = \App::getLocale();
        }

        // Default cache name
        if(!$cacheName){
            $cacheName = PostType::getSlug();
        }

        // Handle cache by post type and category
        if(PostType::findBySlug($cacheName)){
            // ensure post type name is right
            $cacheName = 'post_'.cleanPostTypeSlug($cacheName);

            //set post type table name
            if($categoryID){
                return Post::setCacheCategory($cacheName, $categoryID, $languageSlug);
            }else{
                return Post::setCachePostType($cacheName, $languageSlug);
            }
        }else{
            // Handle custom cache methods

            // We need an empty cache to fill it later
            if(!Cache::has($cacheName)){
                $cachedItems = new \stdClass();
                Cache::forever($cacheName, $cachedItems);
            }else{
                //get requested cache
                $cachedItems = Cache::get($cacheName);
            }

            // Execute cache' method if cache doesn't exist
            if(!isset($cachedItems->$languageSlug)){
                $functionName = 'setCache'.$cacheName;
                if(method_exists(__CLASS__,$functionName)){
                    return Post::$functionName($languageSlug);
                }

                return null;
            }
        }

        // return posts of current language
        if(isset($cachedItems->$languageSlug)){
            return $cachedItems->$languageSlug;
        }
    }


    /**
     * Sets up the cache for the most read articles
     */
    public static function setMostReadCache(){
        // check if a article has been read
        if(Cache::has("most_read_articles_ids")){
            // get the cached post
            $postCount = Cache::get("most_read_articles_ids");
            // current date and time
            $currentDate = new DateTime();

            // use to refresh the cache "most_read_articles_ids", removes posts that are older then 2 days
            $tmpIDsToRefresh = [];
            // used to make a new array with postID as key end the count (how many times is the post read) as value
            $tmpArrayToSort = [];
            foreach($postCount as $key => $post){
                $diff = (int) $currentDate->diff($post['date'])->format("%d");
                if($diff <= 2){
                    $tmpIDsToRefresh[$key] = $post;
                    $tmpArrayToSort[$key] = $post['count'];
                }
            }
            Cache::forever("most_read_articles_ids",$tmpIDsToRefresh);

            // sort the posts by value
            arsort($tmpArrayToSort);
            // get only the 10 most read posts IDs
            $ids = array_slice(array_keys($postCount), 0, 10);

            // get post from db
            $post = new Post();
            $post->setTable("post_articles");
            $posts = $post->whereIn("postID", $ids)->get()->keyBy("postID");

            // make array for cache and arrange the posts with the most read
            $tmpPost = [];
            foreach($ids as $id){
                $tmpPost[$id] = $posts[$id];
            }

            Cache::forever("most_read_articles",$tmpPost);
        }
    }

    /**
     * Get most read posts from cache
     * @return array
     */
    public static function getMostReadCache(){
        return Cache::get("most_read_articles");
    }

    /**
     * @param PostModel $post
     */
    public static function mostReadArticlesIDs(PostModel $post){
        if(!Cache::has("most_read_articles_ids")){
            Cache::forever("most_read_articles_ids", []);
        }

        // push in cache only the post that are not older than 2 days
        $currentDate = new DateTime();
        $publishedDate = new DateTime($post->published_at);
        $diff = (int) $currentDate->diff($publishedDate)->format("%d");
        if($diff <= 2){
            $posts = Cache::get("most_read_articles_ids");
            if(!isset($posts[$post->postID])){
                $posts[$post->postID] = [
                  'date' => $publishedDate,
                  'count' => 0
                ];
            }
            $posts[$post->postID]['count'] += 1;
            Cache::forever("most_read_articles_ids", $posts);
        }
    }

    /**
     * Cache posts by post type
     *
     * @param string $postTypeSlug
     * @param string $languageSlug
     * @return object
     **/
    private static function setCachePostType($postTypeSlug, $languageSlug){
        if(!Cache::has($postTypeSlug)){
            $cachedPosts = new \stdClass();
            Cache::forever($postTypeSlug,$cachedPosts);
        }else{
            $cachedPosts = Cache::get($postTypeSlug);
        }

        // if posts doesn't not exist in this language, query them
        if(!isset($cachedPosts->$languageSlug)){
            $posts = (new Post())->setTable($postTypeSlug)
              ->with('featuredImage')
              ->with('categories')
              ->with('media')
              ->published($languageSlug)
              ->limit(1000)
              ->orderBy('published_at','DESC')
              ->get();

            $cachedPosts->$languageSlug = $posts;

            //save it to cache
            Cache::forever($postTypeSlug,$cachedPosts);

            return $posts;
        }

        if(isset($cachedPosts->$languageSlug)){
            return $cachedPosts->$languageSlug;
        }
    }


    /**
     * Gets collection of a tag ($post->tags) and returns other posts with the same tags
     *
     * @return array
     */
    public function getPostsByTagCollection(int $numberOfPosts = 1000){
        $tmpTagIDs = [];
        $postResult = [];

        $tags = $this->tags;
        foreach($tags as $tag){
            $tmpTagIDs[] = $tag->tagID;
        }

        $count = 0;
        $posts = Post::getFromCache($this->getTable());
        foreach($posts as $post){
            foreach($tags as $tag){
                if(in_array($tag->tagID, $tmpTagIDs) && $post->postID != $this->postID && $count < $numberOfPosts){
                    $postResult[$post->postID] = $post;
                    $count++;
                }
            }
            if($count == $numberOfPosts){
                break;
            }
        }
        return $postResult;
    }


    /**
     * Delete caches of posts by post type
     *
     * @param object $postData Post data
     */
    private static function deleteCachePostType($postData){
        Cache::forget($postData->getTable());
    }

    /**
     * Delete caches of posts by its categories
     *
     * @param  string $postTypeSlug
     * @param  int $categoryID
     * @param  string $languageSlug
     * @return object
     **/
    private  static function setCacheCategory($postTypeSlug, $categoryID, $languageSlug){
        $cacheName = 'category_posts_'.$categoryID;

        if(!Cache::has($cacheName)){
            $cachedPosts = new \stdClass();
            Cache::forever($cacheName,$cachedPosts);
        }else{
            $cachedPosts = Cache::get($cacheName);
        }

        //if posts doesn't  exists in this language, query them
        if(!isset($cachedPosts->$languageSlug)){
            $posts = (new Post())->setTable($postTypeSlug)
              ->join('categories_relations','categories_relations.belongsToID',$postTypeSlug.'.postID')
              ->where('categories_relations.categoryID', '=', $categoryID)
              ->with('featuredImage')
              ->with('media')
              ->published($languageSlug)
              ->limit(1000)
              ->orderBy($postTypeSlug.'.published_at','DESC')
              ->get()
              ->keyBy('postID');

            $cachedPosts->$languageSlug = $posts;

            Cache::forever($cacheName,$cachedPosts);

            return $posts;
        }

        // return posts of current language
        if(isset($cachedPosts->$languageSlug)){
            return $cachedPosts->$languageSlug;
        }
    }

    /**
     * Delete post cache by categories
     *
     * @param object $postData Post by language
     */
    private static function deleteCacheCategories($postData){
        if(isset($postData->categories)){
            foreach($postData->categories as $category){
                Cache::forget('category_posts_'.$category->categoryID);
            }
        }
    }

    /**
     * Handle callback of insert, update, delete
     * */
    protected static function boot(){
        parent::boot();

        self::retrieved(function($post){
            Event::fire('post:retrieved', [$post]);
        });

        self::saving(function($post){
            Event::fire('post:saving', [$post]);
        });

        self::saved(function($post){
            Post::_saved($post);
            Event::fire('post:saved', [$post]);
        });

        self::creating(function($post){
            Event::fire('post:creating', [$post]);
        });

        self::created(function($post){
            // create task
            Task::create('post','create', $post, ['postType' => $post->getTable()]);

            Event::fire('post:created', [$post]);
        });

        self::updating(function($post){
            Event::fire('post:updating', [$post]);
        });

        self::updated(function($post){
            // create task
            Task::create('post', 'update', $post, ['postType' =>  $post->getTable()]);

            Event::fire('post:updated', [$post]);
        });

        self::deleting(function($post){
            Event::fire('post:deleting', [$post]);
        });

        self::deleted(function($post){
            Post::_deleted($post, $post->getTable());
            Event::fire('post:deleted', [$post]);

            // create delete task
            Task::create('post','delete', $post, ['postType' => $post->getTable()]);
        });
    }

    /**
     * Perform certain actions after a post is saved
     *
     * @param array $post Saved post
     * */
    public static function _saved($post){
        self::updateMenulink($post);
        self::deleteCache($post);
    }

    /**
     * Perform certain actions after a post is deleted
     *
     * @param array $post deleted post
     * */
    public static function _deleted($post){
        self::deleteCache($post);
    }

    /**
     * Switch between connection @if archive is active switches to it otherwise uses primary connection
     * @return $this
     */
    public function checkConnection(){
        if(env("DB_ARCHIVE")){
            $this->setConnection("mysql_archive");
        }else{
            $this->setConnection("mysql");
        }
        return $this;
    }

    /**

     * Scope a query to only include published posts.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query, $languageSlug = ''){
        if(!$languageSlug){
            $languageSlug = App::getLocale();
        }

        return $query->where('status->'.$languageSlug, 'published')->where('published_at', '<=', new DateTime());
    }

    /**
     * Scope a query to only include published posts.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnpublished($query, $languageSlug = '')
    {
        if (!$languageSlug) {
            $languageSlug = App::getLocale();
        }
        return $query
          ->where('status->' . $languageSlug, '!=', 'published')
          ->where('published_at', '>=', new DateTime());
    }


    /**
     * Scope a query to include posts by a specific date
     *
     * @param $query
     * @param $filters
     * @return mixed
     */
    public function scopeDate($query, $year = '', $month ='', $day =''){
        if($year){
            $query->whereMonth($this->getTable().'.created_at', $year);
        }

        if($month){
            $query->whereYear($this->getTable().'.created_at', $month);
        }

        if($day){
            $query->whereDay($this->getTable().'.created_at', $day);
        }
        return $query;
    }

    /**
     *
     * Get Home Page's data
     * Home is supposed to be served form post_pages post type
     *
     * @return object
     * @throws \Exception
     */
    public static function sethomepage(){
        if(!self::$homepage) {
            $findHomePage = null;
            if (settings('homepageID')) {
                $findHomePage = Post::getFromCache('post_pages')->where('postID', settings('homepageID'))->first();
            }

            // get the first found page if no homepage is defined
            if (!$findHomePage) {
                $findHomePage = Post::getFromCache('post_pages')->first();
            }

            if(!$findHomePage){
                throw new \Exception("No homepage found. Please add a page post!");
            }
            self::$homepage = $findHomePage;
        }

        return self::$homepage;
    }

    /**
     * Get the data of Homepage
     *
     * @param string $columnName Column of page to be returned
     *
     * @return array|null Returns the data of the primary Menu if found, null instead
     */
    public static function getHomepage($columnName = ''){
        if($columnName){
            if(isset(self::$homepage->$columnName)){
                return self::$homepage->$columnName;
            }
            return;
        }
        return self::$homepage;
    }

    /**
     * Generate default URL to a post
     *
     * @return string
     */
    public function getHrefAttribute(){
        return $this->href();
    }

    /**
     * Generate a custom URL to a post
     *
     * @param string $routeName
     * @param array $customAttributes
     *
     * @return string
     */
    public function href($routeName = '', $customAttributes = []){
        if(!$routeName) {
            $routeName = cleanPostTypeSlug($this->getTable(),'post.') . '.single';
        }

        $getRoute = Route::getRoutes()->getByName($routeName);
        if($getRoute) {
            $routeParams = Route::getRoutes()->getByName($routeName)->parameterNames();

            // translating language
            if($this->getTranslateLanguage()){
                $languageSlug = $this->getTranslateLanguage();
            }else{
                $languageSlug = App::getLocale();
            }

            //set only requsted params
            $params = [];
            foreach ($routeParams as $name) {

                switch ($name) {
                    case 'postTypeSlug';
                        $params['postTypeSlug'] = cleanPostTypeSlug($item->getTable());
                        break;

                    case 'postSlug';
                        $params['postSlug'] = $this->slug;
                        break;

                    case 'date';
                        $params['date'] = $this->created_at;
                        break;

                    case 'postID';
                        $params['postID'] = $this->postID;
                        break;

                    case 'lang';
                        //don't show language slug on default language
                        if(config('project.hideDefaultLanguageInURL') && $languageSlug !=  Language::getDefault('slug')) {
                            $params['lang'] = $languageSlug;
                        }
                        break;
                }
            }

            if($customAttributes){
                $params = array_merge($customAttributes, $params);
            }
            return route($routeName, $params);
        }else{
            throw new Exception("Route $routeName not found");
        }
    }

    /**
     * Define single post's SEO Meta data
     */
    public function metaData(){
        Meta::setTitle($this->title)
            ->set("description", $this->content())
            ->set("author", $this->cachedUser()->firstName." ".$this->cachedUser()->lastName)
            ->set("og:type", "article", "property")
            ->set("og:title", $this->title, "property")
            ->set("og:description", $this->content(), "property")
            ->set("og:url",$this->href, "property")
            ->setImageOG($this->featuredImage)
            ->setArticleOG($this)
            ->setHrefLangData($this)
            ->setCanonical($this->href)
            ->setWildcards([
                '{categoryTitle}'=>(isset($this->cachedCategory->title) ? $this->cachedCategory->title :  null),
                '{title}' => $this->title,
                '{siteTitle}' => settings('siteTitle')
            ]);
    }

    /**
     * Featured image of a post
     * @return HasOne
     */
    public function featuredImage()
    {
        $this->setConnection("mysql"); //@todo temporary, se po i thirr prje arkives kur posti eshte i arkives
        return $this->hasOne('App\Models\Media','mediaID','featuredImageID');
    }

    /**
     * Featured video of a post
     * @return HasOne
     */
    public function featuredVideo()
    {
        $this->setConnection("mysql"); //@todo temporary, se po i thirr prje arkives kur posti eshte i arkives
        return $this->hasOne('App\Models\Media','mediaID','featuredVideoID');
    }

    /**
     * Media that belong to a Post
     * @return HasManyThrough
     */
    public function media()
    {
        $this->setConnection("mysql"); //@todo temporary, se po i thirr prje arkives kur posti eshte i arkives
        $query =  $this->hasManyThrough('App\Models\Media', 'App\Models\MediaRelation','belongsToID','mediaID','postID', 'mediaID');

        if($this->mediaField){
            $query->where('media_relations.field',$this->mediaField);
        }

        // We also need 'field' from media relations, so we can select media based on field name
        $query->select(['media.*','media_relations.field']);

        return $query;
    }

    /**
     * Get media of a specific field
     *
     * @param $field
     * @return object|null
     */
    public function mediaField($field){
        if(count($this->media)){
            $results = $this->media->where('field', $field);
            if(count($results)) {
                return $results;
            }
        }

        return null;
    }

    /**
     * Set Media field
     *
     * @param string $mediaField
     * @return $this
     */
    public function withMediaField($mediaField){
        $this->mediaField = $mediaField;
        return $this;
    }

    /**
     * Get Cached Tags
     *
     * @return string
     */
    public function getCachedTagsAttribute()
    {
        // Find cached relations from this post type
        $relations = TagRelation::getFromCache($this->getTable())->where('belongsToID',$this->postID);
        $tagsID = $relations->pluck(['tagID'])->all();

        // Find cached tags from relations
        return \App\Models\Tag::getFromCache($this->getTable())->whereIn('tagID',$tagsID);
    }

    /**
     * Cached categories that belong to a post
     *
     * @return string
     */
    public function getCachedCategoriesAttribute()
    {
        // Find cached relations from this post type
        $relations = CategoryRelation::getFromCache($this->getTable())->where('belongsToID',$this->postID);
        $categoriesID = $relations->pluck(['categoryID'])->all();

        // Find cached categories from relations
        if(Category::getFromCache()){
            return Category::getFromCache()->whereIn('categoryID',$categoriesID);
        }
        return;
    }

    /**
     * Get Primary Cached category
     *
     * @return string
     */
    public function getCachedCategoryAttribute()
    {
        // Find cached relations from this post type
        $relations = CategoryRelation::getFromCache($this->getTable())->where('belongsToID',$this->postID );
        $categoryID = $relations->pluck(['categoryID'])->first();

        // Find cached categories from relations
        if(Category::getFromCache()){
            return Category::getFromCache()->where('categoryID',$categoryID)->first();
        }

        return;
    }

    /**
     * Get Cached user that belongs to a post
     * @return HasOne
     */
    public function cachedUser()
    {
        if(User::getFromCache()) {
            $user = User::getFromCache()->where('userID', $this->createdByUserID);
            return $user->first();
        }
        return;
    }
    /**
     * The users that belong to the role.
     */
    public function users()
    {
        return $this->belongsToMany('App\Models\User');
    }

    /**
     * Categories that belong to a Post
     * @return HasManyThrough
     */
    public function categories()
    {
        $findPostType = PostType::findBySlug($this->getTable());
        return $this->hasManyThrough('App\Models\Category', 'App\Models\CategoryRelation','belongsToID','categoryID','postID','categoryID')->where('postTypeID',$findPostType->postTypeID);
    }

    /**
     * Tags that belong to a Post
     * @return HasManyThrough
     */
    public function tags()
    {
        $findPostType = PostType::findBySlug($this->getTable());
        return $this->hasManyThrough('App\Models\Tag', 'App\Models\TagRelation','belongsToID','tagID','postID','tagID')->where('postTypeID',$findPostType->postTypeID);
    }


    /**
     * Get data relation for fields (Dropdown from DB)
     *
     * @param string $fieldSlug
     * @return array
     */
    public function getFieldRelations(string $fieldSlug){
        $postType = PostType::getFromCache()->where("slug", $this->getTable())->first();
        if($postType){
            // get the specific field
            $field = $postType->field($fieldSlug);
            if($field->type->inputType == "db"){
                // get table of the field (table for relation)
                $table = $field->dbTable->name;
                $belongsTo = $field->dbTable->belongsTo;

                // get the model and the primary key
                if($belongsTo == "PostType"){
                    $id = "postID";
                    $obj = new Post();
                    $obj->setTable($table);
                }elseif($belongsTo == "User"){
                    $id = "userID";
                    $obj = new User();
                }

                $relationData = [];

                // if multiple use whereIn if not multiple use where
                if($field->isMultiple){
                    if(gettype($this->$fieldSlug) == "string"){
                        $ids = json_decode($this->$fieldSlug);
                    }else{
                        $ids = $this->$fieldSlug;
                    }
                    $relationData = $obj->whereIn($id, $ids)->get();
                }else{
                    $relationData = $obj->where($id, json_decode($this->$fieldSlug))->get();
                }

                return $relationData;
            }
        }
    }

    /**
     * Destruct model instance
     */
    public function __destruct()
    {
        Event::fire('post:destruct', [$this]);
    }
}
