<?php

namespace Accio\App\Traits;


use Accio\Support\PostCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

trait CacheTrait
{

    /**
     * @var
     */
    protected $cacheName;

    /*
     *
     */
    protected $cacheTable;

    /**
     * @var array
     */
    protected $cacheAttributes = [];



    /**
     * @var object
     */
    protected static $deletingItem;

    /**
     * @var object
     */
    protected static $updatingItem;

    /**
     *
     * @var array =
     */
    protected $cachedItems;

    protected $cacheCallback;

    /**
     * @var integer|null
     */
    protected $limitCache;

    /**
     * Shall we disable casts.
     * @var bool
     */
    public $disableCasts = false;
    public static $tmpDisableCasts = false;

    /**
     * Boot Cache Trait Events.
     * TODO : spe shoh ku po perdoret
     *
     * @return void
     */
    protected static function bootCacheTrait(){
        self::created(function($item){
            $item->handleUpdateCache($item, "created");
        });

        self::updating(function($item){
            $item->handleUpdateCache($item, "updating");
        });

        self::updated(function($item){
            $item->handleUpdateCache($item, "updated");
        });

        $test = self::deleting(function($item){
            $item->handleUpdateCache($item, "deleting");
        });

        self::deleted(function($item) use($test){
            $item->handleUpdateCache($item, "deleted");
        });
    }

    /**
     * Cache name.
     *
     * @param string $cacheName
     */
    private function setCacheName(string $cacheName){
        $this->cacheName = $cacheName;
    }


    /**
     * @param string $methodName Method name
     *
     * @return mixed
     *
     * @throws \Exception
     */
    private function handleCustomCache($methodName){
        if(method_exists($this,$methodName)){
            return $this->$methodName();
        }else{
            throw new \Exception("Cache method $methodName does not exists!");
        }
    }

   


    /**
     * Check if a key/value is in cache.
     *
     * @param $array
     * @param $keyName
     * @param $keyValue
     *
     * @return array
     */
    private function hasCacheItem($array, $keyName, $keyValue){
        $items = $array->where($keyName, $keyValue);
        if($items){
            foreach ($items as $key=>$val){
                return $key;
            }
        }
        return null;
    }

    /**
     * @return $this
     */
    public function limitCache($limit = null){
        if($limit){
            $this->limitCache = $limit;
        }
        return $this;
    }

    /**
     * Default method to update cache.
     *
     * @param $item
     * @param $mode
     */
    public function updateCache($item, $mode){
        $model =  self::getModel();
        $cacheName = self::getAutoCacheName();

        // Fire cache updated event
        Event::fire(lcfirst($model).':cacheUpdated', [$item, $mode]);

        // Manage cache state
        $limit = (property_exists($this, 'defaultLimitCache') ? $this->defaultLimitCache : $this->limitCache);
        $model::cache($cacheName, null, false)->refreshState($cacheName, $item, $mode, $limit);
    }

    /**
     * Handle custom cache update in models.
     *
     * @param $item
     * @param $mode
     */
    private function handleUpdateCache($item, $mode){
        $classPath = self::getModel();
        $modelClass = new $classPath();

        //delete existing cache
        $updateCacheMethods = preg_grep('/^updateCache/', get_class_methods($classPath));

        foreach($updateCacheMethods as $method){
            $modelClass->$method($item, $mode);
        }
    }

    /**
     * Get parent class.
     * It removes Accio namespace in order to use project's own model
     *
     * @return string
     */
    private static function getModel(){
        $className = get_class();

        // Remove "Model" form class so project's models are called
        if(strstr(get_class(), 'Accio\\App\\')){
            $explode = explode('\\',$className);
            $className = '\\App\\Models\\'.str_replace('Model','',end($explode));
        }

        return $className;
    }

    /**
     * Get cache name automatically based on model's name.
     *
     * @return string
     */
    private function getAutoCacheName(){
        $explode = explode('\\',get_class());
        return str_replace('Model','',end($explode));
    }

    /**
     * Set cache attributes.
     *
     * @param string $cacheName
     * @return mixed
     */
    public function initializeCache(string $cacheName = ''){
        if(!$cacheName){
            $cacheName = $this->getAutoCacheName();
        }
        $this->setCacheName($cacheName);
        $this->cachedItems = Cache::get($this->cacheName);
        return $this;
    }

    /**
     * Initialize cache instance.
     * Cache is generated if not found.
     *
     * @param string $cacheName
     * @param null $callback Callback must always return an executed query with collection output
     * @param bool $appendModelToCollection
     * @return mixed
     * @throws \Exception
     *
     * @return mixed Returns collection or instance
     */
    public static function cache($cacheName = '', $callback = null, $appendModelToCollection = true){
        $instance = (new static)->initializeCache($cacheName);

        if(!Cache::has($instance->cacheName)){
            if ($callback) {
                if (is_callable($callback)) {
                    $instance->cachedItems = $callback($instance);
                } else {
                    throw new \Exception("Cache callback must be callable!");
                }
            } else {
                $instance->cachedItems = $instance->generateCache();
            }

            // convert collection to array if configured so
            $instance->cachedItems = $instance->cachedItems->toArray();

            // Save in cache
            Cache::forever($instance->cacheName,$instance->cachedItems);
        }

        return $instance->newCollection($instance->cachedItems,get_class(), $instance->getTable());
    }

    public static function removeCache($cacheName = ''){
        $instance = (new static)->initializeCache($cacheName);
        if(Cache::has($instance->cacheName)){
            Cache::forget($instance->cacheName);
        }
    }

    /**
     * Specifies an ordering for the cache query results.
     * Replaces any previously specified orderings, if any.
     *
     * @param string $sort  The ordering expression.
     * @param string $order The ordering direction.
     *
     * @return $this This QueryBuilder instance.
     */
    public function orderByCache($sort, $order = null)
    {
        $this->orderByCache = [
          'sort' => $sort,
          'order' => (! $order ? 'ASC' : $order)
        ];

        return $this;;
    }


    /**
     * Default method to handle cache query..
     *
     * @return mixed
     * @throws \Exception
     */
    public function generateCache(){
        // Generte cache
        $queryObject = $this;

        if($this->cacheCallback){
            $cacheCallback = $this->cacheCallback;
            if (is_callable($cacheCallback)) {
                $data = $cacheCallback($this);
            }else{
                throw new \Exception("Cache callback must be callable!");
            }
        }else {
            // Limit
            $limit = (property_exists($this, 'defaultLimitCache') ? $this->defaultLimitCache : $this->limitCache);
            if ($limit) {
                $queryObject = $queryObject->limit($limit);
            }

            // Execute query
            $data = $queryObject->get();
        }

        return $data;
    }
    
    public function newInstance($attributes = [], $exists = false)
    {
        // Overridden in order to allow for late table binding.
        $model = parent::newInstance($attributes, $exists);
        $model->setTable($this->table);

        // casts are disabled when cache is being generated, this is done to simulate database data structure
        $model->disableCasts = $this->disableCasts;
        $model::$tmpDisableCasts = $this->disableCasts;
        return $model;
    }

    /**
     * Get the casts array.
     * When generating cache, we need to save cache data as they currently are in database, without casts.
     * This is done to allow casts to work just as when they receives data from database.
     *
     * @return array
     */
    public function getCasts()
    {
        if($this->disableCasts){
            return [];
        }

        if ($this->getIncrementing()) {
            return array_merge([$this->getKeyName() => $this->getKeyType()], $this->casts);
        }

        return $this->casts;
    }

    /**
     * Check if an attribute is listed in model
     * @param $key
     * @return null
     */
    public function attributeExists($key){
        $keyExists = array_key_exists($key, $this->attributes);
        if ($keyExists || ($keyExists && is_null($this->attributes[$key])) ){
            return true;
        }
        return false;
    }

    /**
     * Fill cache attributes.
     *
     * @param $class
     * @param $items
     * @return Collection
     * @throws \Exception
     */
    public function fillCacheAttributes($class,  $items){
        if(is_null($items) || $items == '[]' || is_array($items) && !count($items)){
            return collect([]);
        }else {
            // we may receive object as string
            if (!is_array($items)) {
                $items = @json_decode($items, true);
                if (!is_array($items)) {
                    throw new \Exception("Items filled in cache attributes must be json or array");
                }
            }

            // items should be wrapped in an array so we can add them in collection
            if (!isset($items[0])) {
                $items = [$items];
            }

            return collect($items)->transform(function ($attributes) use ($class) {
                $modelObj = new $class($attributes);
//                foreach ($attributes as $key => $value) {
//                    $modelObj->setAttribute($key, $value);
//                }
                return $modelObj;
            });
        }

    }

}