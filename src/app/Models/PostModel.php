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
use App\Models\Media;
use App\Models\Tag;
use App\Models\TagRelation;
use App\Models\Task;
use App\Models\Theme;
use App\Models\User;
use DateTime;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
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
use Accio\Support\PostCollection;

class PostModel extends Model{
    use Traits\PostTrait, Traits\TranslatableTrait, Traits\CustomFieldsValuesTrait, LogsActivity, Traits\CacheTrait;

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
     * @var int 
     */
    public static $defaultCacheLimit = 1000;

    public static $defaultCacheWith = ['featuredimage', 'categories', 'media', 'tags'];

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
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return PostCollection
     */
    public function newCollection(array $models = [])
    {
        return new PostCollection($models);
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

    public static function getFromCache($cacheName = '', $categoryID = 0, $languageSlug = ""){
        // Default Language
        if(!$languageSlug){
            $languageSlug = \App::getLocale();
        }

        // Default cache name
        if(!$cacheName){
            $cacheName = PostType::getSlug();
        }

        // Handle cache by post type and category
        $isPostType = isPostType($cacheName);
        if($isPostType){
            // ensure post type name is right
            $cacheName = 'post_'.cleanPostTypeSlug($cacheName);

            if($categoryID){
                $data = Post::setCacheCategory($cacheName, $categoryID, $languageSlug);
            }else{
                $data = Post::setCachePostType($cacheName, $languageSlug);
            }

        }else{ // Handle custom cache methods
            $data = Cache::get($cacheName);

            // Execute cache' method if cache doesn't exist
            if(!isset($data[$languageSlug])){
                $functionName = 'setCache'.$cacheName;

                if(method_exists(__CLASS__,$functionName)){
                    $data = Post::$functionName($languageSlug);
                }else{
                    throw new \Exception("Cache method $functionName does not exists!");
                }
            }else{
                $data = $data[$languageSlug];
            }
        }

        return self::setCacheCollection($data, self::class, $isPostType, $cacheName);
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
            $mostReadIDs = [];

            // used to make a new array with postID as key end the count (how many times is the post read) as value
            $postByCount = [];
            foreach($postCount as $key => $post){
                $diff = (int) $currentDate->diff($post['date'])->format("%d");
                if($diff <= 2){
                    $mostReadIDs[$key] = $post;
                    $postByCount[$key] = $post['count'];
                }
            }

            Cache::forever("most_read_articles_ids",$mostReadIDs);

            if($postByCount) {
                // sort posts by count DESC
                arsort($postByCount);

                // get only the 10 most read posts IDs
                $mostReadPostIDs = array_slice(array_keys($postByCount), 0, 10);

                // get post from db
                $post = new Post();
                $post->setTable("post_articles");
                $posts = $post->whereIn("postID", $mostReadPostIDs)->get()->keyBy("postID");

                // make array for cache and arrange the posts with the most read
                $mosReadPosts = [];
                foreach ($mostReadPostIDs as $id) {
                    if (isset($posts[$id])) {
                        $mosReadPosts[$id] = $posts[$id];
                    }
                }

                Cache::forever("most_read_articles", $mosReadPosts);
            }
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
    public function markAsReadCache($days = 2){
        if(!Cache::has("most_read_articles_ids")){
            Cache::forever("most_read_articles_ids", []);
        }

        // push in cache only the post that are not older than 2 days
        $currentDate = new DateTime();
        $publishedDate = new DateTime($this->published_at);
        $diff = (int) $currentDate->diff($publishedDate)->format("%d");

        // only save article if is newser than 2 days
        if($diff <= $days){
            $posts = Cache::get("most_read_articles_ids");

            // add post in list
            if(!isset($posts[$this->postID])){
                $posts[$this->postID] = [
                  'date' => $publishedDate,
                  'count' => 0
                ];
            }

            $posts[$this->postID]['count'] += 1;

            Cache::forever("most_read_articles_ids", $posts);
        }
    }

    /**
     * Cache posts by post type
     *
     * @param string $postTypeSlug
     * @param string $languageSlug
     * @return Collection
     **/
    private static function setCachePostType($postTypeSlug, $languageSlug){
        $cachedPosts = Cache::get($postTypeSlug);

        // if posts doesn't not exist in this language, query them
        if(!isset($cachedPosts[$languageSlug])){
            $data = (new Post())->setTable($postTypeSlug)
              ->with(self::$defaultCacheWith)
              ->limit(self::$defaultCacheLimit)
              ->orderBy('published_at','DESC')
              ->get()
              ->toArray();

            // merge with other langauges
            $dataToCache = [$languageSlug => $data];
            if($cachedPosts){
                $dataToCache = array_merge($cachedPosts,$dataToCache);
            }

            Cache::forever($postTypeSlug,$dataToCache);
        }else{
            $data = $cachedPosts[$languageSlug];
        }

        return $data;
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
     * @return Collection
     **/
    private  static function setCacheCategory($postTypeSlug, $categoryID, $languageSlug){
        $cacheName = 'category_posts_'.$categoryID;
        $cachedItems = Cache::get($cacheName);

        if(!isset($cachedItems[$languageSlug])){
            $data = (new Post())->setTable($postTypeSlug)
              ->where('categories_relations.categoryID', '=', $categoryID)
              ->with(self::$defaultCacheWith)
              ->limit(self::$defaultCacheLimit)
              ->orderBy($postTypeSlug.'.published_at','DESC')
              ->get()
              ->toArray();

            // merge with other langauges
            $dataToCache = [$languageSlug => $data];
            if($cachedItems){
                $dataToCache = array_merge($cachedItems,$dataToCache);
            }
            Cache::forever($cacheName,$dataToCache);

        }else{
            $data = $cachedItems[$languageSlug];
        }

        return $data;
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
     * Gets collection of a tag ($post->tags) and returns other posts with the same tags
     *
     * @param int $numberOfPosts
     * @return array
     */
    public function getPostsByTags(int $numberOfPosts){
        $tmpTagIDs = [];
        $postsByTags = [];

        foreach($this->tags as $tag){
            $tmpTagIDs[] = $tag->tagID;
        }

        return [];

        $count = 0;
        $posts = Post::getFromCache($this->getTable())->published();
        foreach($posts as $post){
            if($post->postID != $this->postID) {
                foreach ($post->tags as $tag) {
                    if (in_array($tag->tagID, $tmpTagIDs) && $count <= $numberOfPosts) {
                        $postsByTags[] = $post;
                        $count++;
                    }
                }
            }

            if($count == $numberOfPosts){
                break;
            }
        }

        return $postsByTags;
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

        return $query
          ->where('status->'.$languageSlug, 'published')
          ->where('published_at', '<=', new DateTime());
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
          ->set("author", $this->user->firstName." ".$this->user->lastName)
          ->set("og:type", "article", "property")
          ->set("og:title", $this->title, "property")
          ->set("og:description", $this->content(), "property")
          ->set("og:url",$this->href, "property")
          ->setImageOG($this->featuredImage)
          ->setArticleOG($this)
          ->setHrefLangData($this)
          ->setCanonical($this->href)
          ->setWildcards([
            '{categoryTitle}'=>($this->hasCategory() ? $this->category->title :  null),
            '{title}' => $this->title,
            '{siteTitle}' => settings('siteTitle')
          ]);
    }


    public function getMediaAttribute(){
        $media = $this->getAttributeFromArray('media');

        // dont recreate attribute
        if($media instanceof Collection) {
            return $media;
        }

        // instance of collection when called from cache
        if(is_array($media) && !$media instanceof Collection){
            return $this->fillCacheAttributes(Media::class, $media);
        }

        // or search in relations
        return $this->getRelationValue('media');
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
     * Generate default URL to a post
     *
     * @return Collection
     */
    public function getTagsAttribute()
    {
        $tags = $this->getAttributeFromArray('tags');

        // dont recreate attribute
        if($tags instanceof Collection) {
            return $tags;
        }

        // tags may not be instance of collection when called from cache
        if(is_array($tags) && !$tags instanceof Collection){
            return $this->fillCacheAttributes(Tag::class, $tags);
        }

        // or search tags in relations
        return $this->getRelationValue('tags');
    }

    /**
     * Cached categories that belong to a post
     *
     * @return string
     */
    public function getCategoriesAttribute()
    {
        $categories = $this->getAttributeFromArray('categories');

        // dont recreate attribute
        if($categories instanceof Collection) {
            return $categories;
        }

        // instance of collection when called from cache
        if(is_array($categories) && !$categories instanceof Collection){
            return $this->fillCacheAttributes(Category::class, $categories);
        }

        // Try to find categories in cache
        $categoriesID = CategoryRelation::getFromCache($this->getTable())
          ->where('belongsToID', $this->postID)
          ->pluck(['categoryID'])
          ->all();

        if ($categoriesID) {
            return Category::getFromCache()->whereIn('categoryID', $categoriesID);
        }

        // or search in relations
        return $this->getRelationValue('categories');
    }

    /**
     * Get Primary Cached category
     *
     * @return string
     */
    public function getCategoryAttribute()
    {
        if(!is_null($this->categories)){
            return $this->categories->first();
        }

        return;
    }

    /**
     * Get user that belongs to a post
     * @return HasOne
     */
    public function getUserAttribute()
    {
        $user = $this->getAttributeFromArray('user');

        // dont recreate attribute
        if(!is_null($user)) {
            return $user;
        }

        if (!$this->createdByUserID) {
            return;
        }

        // search in cache
        $user = User::getFromCache()->where('userID', $this->createdByUserID)->first();

        // search in database
        if (!$user) {
            $user = User::find($this->createdByUserID);
        }

        return $user;
    }

    /**
     * Get Featured Image that belong to a post
     * @return HasOne
     */
    public function getFeaturedImageAttribute()
    {
        if($this->featuredImageID) {
            $featuredImage = $this->getAttributeFromArray('featuredimage');

            // dont recreate attribute
            if ($featuredImage instanceof Collection) {
                return $featuredImage;
            }

            // instance of collection when called from cache
            if (is_array($featuredImage) && !$featuredImage instanceof Collection) {
                return $this->fillCacheAttributes(Media::class, [$featuredImage])->first();
            }

            // dont recreate attribute
            return $this->getRelationValue('featuredimage');
        }
        return null;

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
        if(!$findPostType){
            throw new \Exception("TaCategories  relations could not be made because the post type of the post #".$this->postID." could ont be found!");
        }
        return $this->hasManyThrough('App\Models\Category', 'App\Models\CategoryRelation','belongsToID','categoryID','postID','categoryID')->where('postTypeID',$findPostType->postTypeID);
    }

    /**
     * Tags that belong to a Post
     * @return HasManyThrough
     */
    public function tags()
    {
        $findPostType = PostType::findBySlug($this->getTable());
        if(!$findPostType){
            throw new \Exception("Tag relations could not be made because the post type of the post #".$this->postID." could ont be found!");
        }
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
