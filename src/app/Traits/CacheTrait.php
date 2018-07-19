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
     * @var
     */
    protected $cacheInstance;


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
     * Boot Cache Trait Events.
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
     * Set cache instance.
     *
     * @param $cacheInstance
     */
    private function setCacheInstance($cacheInstance){
        $this->cacheInstance = $cacheInstance;
    }

    /**
     * Get cache instance.
     *
     * @return $this Returns cache instance
     */
    private function cacheInstance(){
        return $this->cacheInstance;
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
     * Add, update or remove an item from cache.
     *
     * @param $item
     * @param $mode
     * @param null $callback
     * @return CacheTrait
     * @throws \Exception
     */
    public function refreshState($item, $mode, $callback = null){
        $cachedItems = $this->cachedItems;

        // check if this item exists
        $currentItemKey = null;
        if($cachedItems) {
            $keyName = $item->getKeyName();
            $keyValue = $item->getKey();
            foreach ($cachedItems as $index => $row) {
                if ($row[$keyName] == $keyValue) {
                    $currentItemKey = [$index => $row];
                    break;
                }
            }
        }

        // DELETE
        if($mode == 'deleted'){
            if ($currentItemKey) {
                unset($cachedItems[key($currentItemKey)]);
            }
        }else {
            // UPDATE
            if ($currentItemKey) {
                $cachedItems[key($currentItemKey)] = $item->toArray();

            } else { // ADD
                // push new item to cache
                $newCachedItems = [];
                array_push($newCachedItems, $item->toArray());

                $cachedItems = array_merge($newCachedItems, $cachedItems);

                // Limit results
                $limit = (property_exists($this,'defaultLimitCache') ? $this->defaultLimitCache : $this->limitCache);
                if($limit) {
                    $countItems = count($cachedItems);
                    if ($countItems > $limit) {
                        $cachedItems = array_slice($cachedItems, 0, ($limit - $countItems));
                    }
                }

            }
        }

        // Save cache
        Cache::forever($this->cacheName,$cachedItems);

        return $this;
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
     * @return void
     */
    public function updateCache($item, string $mode){
        $model =  self::getModel();
        $cacheName = self::getAutoCacheName();

        // Fire cache updated event
        Event::fire(lcfirst($model).':cacheUpdated', [$item, $mode]);

        // Manage cache state
        $model::cache($cacheName)->refreshState($item, $mode);
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
    private static function getAutoCacheName(){
        $explode = explode('\\',get_class());
        return str_replace('Model','',end($explode));
    }

    /**
     * Set cache attributes.
     *
     * @param string $cacheName
     * @return mixed
     */
    public static function initializeCache(string $cacheName){
        $model = get_class();
        $instance = new $model();
        $instance->setCacheName($cacheName);
        $instance->setCacheInstance($instance);
        return $instance;
    }

    /**
     * Initialize cache instance.
     * Cache is generated if not found.
     *
     * @param string $cacheName
     * @param \Closure $callback Callback must always return an executed query with collection output
     *
     * @return mixed Returns collection or instance
     */
    public static function cache($cacheName = '', $callback = null, $returnCollection = true){
        if(!$cacheName){
            $cacheName = self::getAutoCacheName();
        }
        $instance = self::initializeCache($cacheName);
        $instance->cachedItems = Cache::get($instance->cacheName);

        if(!$instance->cachedItems){
            if ($callback) {
                if (is_callable($callback)) {
                    $instance->cachedItems = $cacheCallback($instance);
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

        // Return collection
        if($returnCollection){
            return $instance->newCollection($instance->cachedItems)->setModel(self::getModel(), $instance->getTable());
        }

        return $instance;
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
                if(!is_Array($attributes)){
                    dd($attributes);
                }
                $modelObj = new $class($attributes);
//                foreach ($attributes as $key => $value) {
//                    $modelObj->setAttribute($key, $value);
//                }
                return $modelObj;
            });
        }

    }

}