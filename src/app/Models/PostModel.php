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
use Illuminate\Database\Query\Builder;
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
    use
        Traits\PostTrait,
        Traits\TranslatableTrait,
        Traits\CustomFieldsValuesTrait,
        LogsActivity,
        Traits\CacheTrait,
        Traits\BootEventsTrait;

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

    // Carbon instance fields
    protected $dates = ['created_at', 'updated_at', 'published_at'];

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
     * @var array
     */
    public $autoCacheRelations = [];

    /**
     * Define default cachelimit
     * @var int
     */
    public $defaultLimitCache = 1000;

    /**
     * List of default table columns
     *
     * NOTE: translations shall be referred via __ prefix
     *
     * @var array
     */
    public static $defaultListColumns = [
        'postID' => '#ID',
        'title' => '__accio::base.title',
        'category' => '__accio::categories.labelSingle',
        'published_at' => '__accio::post.publishedAt',
        'author' => '__accio::user.author'
    ];

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
        foreach(PostType::cache()->getItems() as $postType){
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
     * Initialize cache instance.
     * Cache is generated if not found.
     *
     * @param string $cacheName
     * @return mixed
     * @throws \Exception
     */
    public static function cache($cacheName = ''){
        if(!$cacheName){
            $cacheName = self::getAutoCacheName();
        }
        $cacheInstance = self::initializeCache($cacheName);
        $cacheInstance->cachedItems = Cache::get($cacheInstance->cacheName);

        // Validate post type by cache name
        $postType = getPostType($cacheInstance->cacheName);
        if(!$postType){
            // or from table
            $postType = getPostType($cacheInstance->getTable());

            if(!$postType) {
                throw new \Exception($cacheInstance->cacheName . ' doest\'t seem like a post type slug.');
            }
        }

        // Set table to this post type
        $cacheInstance->setTable($postType->slug);

        return $cacheInstance;
    }

    /**
     * Cache posts by post type.
     *
     * @return Collection
     *
     * @throws \Exception
     **/
    private function generateCache(){
        $postType = getPostType($this->getTable());

        $postObj = (new Post())->setTable($this->getTable());

        $queryObject = $postObj;

        // Category Inner selection
        if($this->whereCacheValue('categories_relations.categoryID')) {
            $queryObject = $queryObject->join('categories_relations', 'categories_relations.belongsToID', $postType->slug . '.postID');
        }

        // Join
        if($this->joinCache){
            foreach($this->joinCache as $join){
                $queryObject = $queryObject->join($join['table'], $join['first'], $join['operator'], $join['second'], $join['type'], $join['where']);
            }
        }

        // With relations
        $withRelations = ($this->withCache ? $this->withCache : $this->getDefaultRelations($postType));
        if($withRelations){
            $queryObject = $queryObject->with($withRelations);
        }

        // Where conditions
        if($this->whereCache){
            foreach($this->whereCache as $where){
                $queryObject = $queryObject->where($where['key'], $where['operator'], $where['value']);
            }
        }

        // Limit
        $limit = ($this->defaultLimitCache ? $this->defaultLimitCache : $this->limitCache);
        if($limit){
            $queryObject = $queryObject->limit($limit);
        }

        // Order
        $orderBy = $this->orderByCache;
        if($orderBy){
            $queryObject = $queryObject->orderBy($orderBy['key'],$orderBy['type']);
        }else{
            $queryObject = $queryObject->orderBy('published_at','DESC');
        }

        // Execute query
        $data = $queryObject->get();

        // Save in cache
        Cache::forever($this->cacheName,$data);

        return $data;
    }

    /**
     * Update cache in post types.
     *
     * @param object $postObj
     * @param string $mode created, updating, updated, deleting, deleted
     * @throws \Exception
     */
    private function updateCache($postObj, string $mode){
        // Get post data
        switch ($mode) {
            case 'deleting':
                self::$deletingItem = Post::findByID($postObj->postID, $postObj->getTable());
                break;

            case 'updating':
                self::$updatingItem = Post::findByID($postObj->postID, $postObj->getTable());
                break;

            case 'deleted':
                $postObj = self::$deletingItem;
                break;

            default:
                // Select post with all of its relations
                $postObj = Post::findByID($postObj->postID, $postObj->getTable());
                break;
        }

        $this->updatePostTypeCache($postObj, $mode);
        $this->updateCategoriesPostsCache($postObj, $mode);
    }

    /**
     * Update cache in post types.
     *
     * @param $postObj
     * @param string $mode
     * @throws \Exception
     */
    private function updatePostTypeCache($postObj, string $mode){
        switch ($mode){
            case 'deleted':
                $this->refreshPostInPostTypeCache($postObj, $mode);
                break;

            case 'created':
            case 'updated':
                // Post has relations that are saved after a post is saved
                // therefore we need to fire cache refresh after all relations are saved.
                Event::listen('post:stored', function ($data, $postObj) use($mode){
                    $this->refreshPostInPostTypeCache($postObj, $mode);
                });
                break;
        }
    }

    /**
     * Update post in post type cache.
     *
     * @param $postObj
     * @param string $mode
     * @throws \Exception
     */
    private function refreshPostInPostTypeCache($postObj, string $mode){
        Post::cache($postObj->getTable())
            ->setTable($postObj->getTable())
            ->refreshState($postObj, $mode);
    }

    /**
     * Update cache in categories.
     *
     * @param object $postObj
     * @param string $mode created, updating, updated, deleting, deleted
     * @throws \Exception
     * @throws \Exception
     */
    private function updateCategoriesPostsCache($postObj, string $mode){
        switch ($mode){
            case 'deleted':
                if ($postObj->hasCategory()) {
                    foreach ($postObj->categories as $category) {
                        $this->refreshPostInCacheCategory($postObj, $mode, $category);
                    }
                }
                break;

            case 'created':
            case 'updated':

                // Post has relations that are saved after a post is saved
                // therefore we need to fire cache refresh after all relations are saved.
                Event::listen('post:stored', function ($data, $postObj) use($mode){
                    if ($postObj->hasCategory()) {
                        foreach ($postObj->categories as $category) {

                            // Remove cache of previous selected categories, if there is any change
                            if (self::$updatingItem && self::$updatingItem->hasCategory()) {
                                foreach (self::$updatingItem->categories as $prevCategory) {
                                    // only update if previous category is currently not selected
                                    if ($postObj->categories->where('categoryID', $prevCategory->categoryID)->isEmpty()) {
                                        $this->refreshPostInCacheCategory($postObj, "deleted", $prevCategory);
                                    }
                                }
                            }

                            // update from current changes
                            $this->refreshPostInCacheCategory($postObj, $mode, $category);
                        }
                    }else{
                        // Remove post from previous category if there is no category selected
                        if (self::$updatingItem && self::$updatingItem->hasCategory()) {
                            foreach (self::$updatingItem->categories as $prevCategory) {
                                $this->refreshPostInCacheCategory($postObj, "deleted", $prevCategory);
                            }
                        }
                    }
                });
                break;
        }
    }

    /**
     * Update post in cache category.
     *
     * @param $postObj
     * @param $mode
     * @param $category
     *
     * @throws \Exception
     */
    private function refreshPostInCacheCategory($postObj,$mode, $category){
        Post::cache("category_posts_".$category->categoryID)
            ->setTable($postObj->getTable())
            ->whereCache('categories_relations.categoryID', $category->categoryID)
            ->refreshState($postObj, $mode);
    }

    /**
     * Sets up the cache for the most read articles
     */
    public static function setMostReadCache(){
        // check if a article has been read
        if(Cache::has("most_read_articles_ids")){
            // current date and time
            $currentDate = new DateTime();

            // use to refresh the cache "most_read_articles_ids", removes posts that are older then 2 days
            $mostReadIDs = [];

            // used to make a new array with postID as key end the count (how many times is the post read) as value
            $postByCount = [];

            // get the cached post
            $currentMostReadIDs = Cache::get("most_read_articles_ids");
            foreach($currentMostReadIDs as $key => $post){
                $diff = (int) $currentDate->diff($post['date'])->format("%d");
                if($diff <= 2){
                    $mostReadIDs[$key] = $post;
                    $postByCount[$key] = $post['count'];
                }
            }

            // save most read id
            Cache::forever("most_read_articles_ids",$mostReadIDs);

            // @TODO cache duhet em u gjeneru prej cron-it, jo çdo here sa te hapet nje artikull me ba kete query
            if(false && $postByCount) {
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
        $mostRead = Cache::get("most_read_articles");
        if(!$mostRead || $mostRead == null || $mostRead->isEmpty()){
            return [];
        }
        return $mostRead;
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
     * Get cache relations.
     *
     * @param $postType
     * @return array
     */
    public function getDefaultRelations($postType){
        $relations = [];

        if(count($this->autoCacheRelations)){
            return $this->autoCacheRelations;
        }


        // Tags
        if($postType->hasTags){
            $relations[] = 'tags';
        }

        // Categories
        if($postType->hasCategories){
            $relations[] = 'categories';
        }

        // FeaturedImage
        $relations[] = 'featuredimage';

        return $relations;
    }

    /**
     * Handle callback of insert, update, delete
     * */
    protected static function boot(){
        parent::boot();

        self::saved(function($post){
            Post::_saved($post);
        });
    }

    /**
     * Perform certain actions after a post is saved.
     *
     * @param array $post Saved post
     * */
    public static function _saved($post){
        self::updateMenulink($post);
    }

    /**
     * Switch between connection @if archive is active switches to it otherwise uses primary connection.
     *
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
     *
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
     *
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
     * Scope a query to include posts by a specific date.
     *
     * @param Builder $query
     * @param string $year
     * @param string $month
     * @param string $day
     *
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
     * Get Home Page's data.
     * Home is supposed to be served form post_pages post type.
     *
     * @return object
     * @throws \Exception
     */
    public static function setHomepage(){
        if(!self::$homepage) {
            $findHomePage = null;
            if (settings('homepageID')) {
                $findHomePage = Post::cache('post_pages')->getItems()->where('postID', settings('homepageID'))->first();
            }

            // get the first found page if no homepage is defined
            if (!$findHomePage) {
                $findHomePage = Post::cache('post_pages')->getItems()->first();
            }

            if(!$findHomePage){
                throw new \Exception("No homepage found. Please add a page post!");
            }
            self::$homepage = $findHomePage;
        }

        return self::$homepage;
    }

    /**
     * Get the data of Homepage.
     *
     * @param string $columnName Column of page to be returned
     *
     * @return array|string|null Returns the data of the primary Menu if found, null instead
     */
    public static function getHomepage($columnName = ''){
        if($columnName){
            if(isset(self::$homepage->$columnName)){
                return self::$homepage->$columnName;
            }
            return null;
        }
        return self::$homepage;
    }

    /**
     * Generate default URL to a post.
     *
     * @return string
     * @throws \Exception
     */
    public function getHrefAttribute(){
        return $this->href();
    }

    /**
     * Generate a custom URL to a post.
     *
     * @param string $routeName
     * @param array $customAttributes
     * @return string
     * @throws \Exception
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
                        $params['postTypeSlug'] = cleanPostTypeSlug($this->getTable());
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
            throw new \Exception("Route $routeName not found");
        }
    }

    /**
     * Define single post's SEO Meta data.
     *
     * @return void;
     */
    public function metaData(){
        Meta::setTitle($this->title)
            ->set("description", $this->content())
            ->set("author", ($this->user ? $this->user->firstName." ".$this->user->lastName : null) )
            ->set("og:type", "article", "property")
            ->set("og:title", $this->title, "property")
            ->set("og:description", $this->content(), "property")
            ->set("og:url",$this->href, "property")
            ->setImageOG(($this->hasFeaturedImage() ? $this->featuredImage : null))
            ->setArticleOG($this)
            ->setHrefLangData($this)
            ->setCanonical($this->href)
            ->setWildcards([
                '{categoryTitle}'=>($this->hasCategory() ? $this->category->title :  null),
                '{title}' => $this->title,
                '{siteTitle}' => settings('siteTitle')
            ]);

        return;
    }


    /**
     * Get media list of a post.
     *
     * @return Collection|mixed
     * @throws \Exception
     */
    public function getMediaAttribute(){
        // when attribute is available, weo don't ned to re-run relation
        if ($this->attributeExists('media')) {
            $items = $this->getAttributeFromArray('media');
            // when Collection is available, we already have the data for this attribute
            if(!$items instanceof Collection) {
                $items = $this->fillCacheAttributes(Media::class, $items);
            }
        }
        // or search tags in relations
        else{
            $items = $this->getRelationValue('media');
        }

        if(is_null($items)){
            return collect([]);
        }

        return $items;
    }


    /**
     * Get media of a specific field.
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
     * Set Media field.
     *
     * @param string $mediaField
     * @return $this
     */
    public function withMediaField($mediaField){
        $this->mediaField = $mediaField;
        return $this;
    }

    /**
     * Media that belong to a Post.
     *
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
     * Generate default URL to a post.
     *
     * @return Collection|mixed
     * @throws \Exception
     */
    public function getTagsAttribute()
    {
        // request caregory only if post type use categories
        $getPostType = getPostType($this->getTable());
        if($getPostType->hasTags) {
            // when attribute is available, weo don't ned to re-run relation
            if ($this->attributeExists('tags')) {
                $items = $this->getAttributeFromArray('tags');
                // when Collection is available, we already have the data for this attribute
                if (!$items instanceof Collection) {
                    $items = $this->fillCacheAttributes(Tag::class, $items);
                }
                return $items;
            } // or search tags in relations
            else {
                return $this->getRelationValue('tags');
            }
        }
    }

    /**
     * Cached categories that belong to a post.
     *
     * @return array|Collection|mixed
     * @throws \Exception
     */
    public function getCategoriesAttribute()
    {
        // Request category only if post type uses categories
        $getPostType = getPostType($this->getTable());

        if($getPostType->hasCategories) {
            if ($this->attributeExists('categories')) {
                $items = $this->getAttributeFromArray('categories');
                // when Collection is available, we already have the data for this attribute
                if (!$items instanceof Collection) {
                    $items = $this->fillCacheAttributes(Category::class, $items);
                }
                return $items;
            } else {
                // or search in relations
                return $this->getRelationValue('categories');
            }
        }
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
     * Get user that belongs to a post.
     *
     * @return mixed|null
     * @throws \Exception
     */
    public function getUserAttribute()
    {

        if($this->createdByUserID){
            // when attribute is available, weo don't ned to re-run relation
            if ($this->attributeExists('user')) {
                $items = $this->getAttributeFromArray('user');
                // when Collection is available, we already have the data for this attribute
                if(!$items instanceof Collection) {
                    if($this->createdByUserID){
                        $items =  User::cache()->getItems()->where('userID', $this->createdByUserID)->first();
                    }
                }

                return $items;
            }else{
                // search in cache
                $user = User::cache()->getItems()->where('userID', $this->createdByUserID)->first();

                // search in database
                if (!$user) {
                    $user = $this->getRelationValue('user');
                }
                return $user;
            }
        }
        return null;
    }

    /**
     * Get Featured Image that belong to a post.
     *
     * @return mixed
     * @throws \Exception
     */
    public function getFeaturedImageAttribute()
    {
        if($this->featuredImageID) {
            // when attribute is available, weo don't ned to re-run relation
            if ($this->attributeExists('featuredimage')) {
                $items = $this->getAttributeFromArray('featuredimage');
                // when Collection is available, we already have the data for this attribute
                if (!$items instanceof Collection) {
                    $items = $this->fillCacheAttributes(Media::class, $items)->first();
                }

                return $items;
            } // or search tags in relations
            else {
                return $this->getRelationValue('featuredimage');
            }
        }
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
    public function featuredVideo(){
        $this->setConnection("mysql"); //@todo temporary, se po i thirr prje arkives kur posti eshte i arkives
        return $this->hasOne('App\Models\Media','mediaID','featuredVideoID');
    }


    /**
     * The users that belong to the role.
     */
    public function users(){
        return $this->belongsTo('App\Models\User', 'createdByUserID','userID');
    }

    /**
     * Categories that belong to a Post.
     *
     * @return HasManyThrough
     * @throws \Exception
     */
    public function categories(){
        $findPostType = PostType::findBySlug($this->getTable());
        if(!$findPostType){
            throw new \Exception("Categories  relations could not be made because the post type of the post #".$this->postID." could ont be found!");
        }
        return $this->hasManyThrough('App\Models\Category', 'App\Models\CategoryRelation','belongsToID','categoryID','postID','categoryID')->where('postTypeID',$findPostType->postTypeID);
    }

    /**
     * Tags that belong to a Post.
     *
     * @return HasManyThrough
     * @throws \Exception
     */
    public function tags(){
        $findPostType = PostType::findBySlug($this->getTable());
        if(!$findPostType){
            throw new \Exception("Tag relations could not be made because the post type of the post #".$this->postID." could ont be found!");
        }
        return $this->hasManyThrough('App\Models\Tag', 'App\Models\TagRelation','belongsToID','tagID','postID','tagID')->where('postTypeID',$findPostType->postTypeID);
    }


    /**
     * Get data relation for fields (Dropdown from DB).
     *
     * @param string $fieldSlug
     * @return array
     * @throws \Exception
     */
    public function getFieldRelations(string $fieldSlug){
        $postType = PostType::cache()->getItems()->where("slug", $this->getTable())->first();
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

