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
     * @var array
     */
    protected $whereCache = [];

    /**
     * @var object
     */
    protected static $deletingItem;

    /**
     *
     * @var array =
     */
    protected $cachedItems;

    /**
     * @var integer|null
     */
    protected $limitCache;

    /**
     * @var array
     */
    protected $joinCache;


    /**
     * @var array
     */
    protected $withCache;

    /**
     * @var array
     */
    protected $orderByCache;

    /**
     * Boot Cache Trait Events.
     *
     * @return void
     */
    protected static function bootCacheTrait(){
        self::created(function($item){
            $item->handleUpdateCache($item, "created");
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
     * @param string $cacheName
     * @param string $class
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
     * @param string $cacheName
     * @param object $item
     * @param string $mode
     * @param integer $limit
     *
     * @return Collection
     */
    private static function manageCacheState($cacheName, array $attributes = [], $item, $mode = false, $limit = null){
        $classPath = '\\App\\Models\\'.self::getModelFromParent();
        $cachedItems = $classPath::cache($cacheName, $attributes, false);

        $currentItem = $item->hasCacheItem($cachedItems,  $item->getKeyName(), $item->getKey(), $cacheName);

        if(!$cachedItems){
            $cachedItems = [];
        }

        // DELETE
        if($mode == 'deleted'){
            if ($currentItem) {
                unset($cachedItems[key($currentItem)]);
            }
        }else {
            // UPDATE
            if ($currentItem) {
                $cachedItems[key($currentItem)] = $item->toArray();
            } else { // ADD
                // push new item to cache
                array_push($cachedItems, $item->toArray());

                // Let's make sure the latest item is sorted at the end of cachedItems
                $cachedItems = array_values($cachedItems);

                // Limit results
                if($limit) {
                    $countItems = count($cachedItems);
                    if ($countItems > $limit) {
                        $cachedItems = array_slice($cachedItems, ($countItems - $limit));
                    }
                }
            }
        }

        // Save cache
        Cache::forever($cacheName,$cachedItems);

        return $cachedItems;
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
        foreach ($array as $index => $row){
            if($row[$keyName] ==  $keyValue){
                return [ $index => $row];
            }
        }

        return [];
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
     * @param bool $delete
     */
    public function updateCache($item, string $mode){
        $model = $cacheName = self::getModelFromParent();

        // Fire cache updated event
        Event::fire(lcfirst($model).':cacheUpdated', [$item, $mode]);

        // Manage cache state
        self::manageCacheState($cacheName, [], $item, $mode, $this->cacheLimit());
    }

    /**
     * Handle custom cache update in models.
     *
     * @param $item
     * @param $mode
     */
    private function handleUpdateCache($item, $mode){
        $classPath = '\\App\\Models\\'.self::getModelFromParent();
        $modelClass = new $classPath();

        //delete existing cache
        $updateCacheMethods = preg_grep('/^updateCache/', get_class_methods($classPath));

        foreach($updateCacheMethods as $method){
            $modelClass->$method($item, $mode);
        }
    }

    /**
     * Set cache collection
     *
     * @param array $data
     * @return Collection
     */
    public function setCacheCollection(array $data, string $table = ''){
        $modelClass = '\\App\\Models\\'.self::getModelFromParent();
        $table = $this->getTable();

        // model may have its own collection method
        if(method_exists($this,'newCollection')){
            $collection = $this->newCollection($data);
        }else{
            $collection = new Collection($data);
        }

        $collection->transform(function ($row) use($modelClass,$table) {

            // because cache saves json values are object, we need to encode them so
            // we laravel does not try to cast tham again
            $attributes = [];
            foreach($row as $key => $value){
                $getType = gettype($value);

                switch ($getType){
                    case 'object':
                    case 'array':
                        $value = json_encode($value);
                        break;
                }

                $attributes[$key] = $value;
            }

            // initialize model
            $modelObj = new $modelClass();

            // change table
            if($table){
                $modelObj->setTable($table);
            }

            $modelObj->setRawAttributes($attributes);

            return $modelObj;
        });

        return $collection;
    }

    /**
     * Automatically find model which is calling this method
     *
     * @return Collection|array
     */
    private static function getModelFromParent(){
        $explode = explode('\\',get_class());
        $modelName = str_replace('Model','',end($explode));
        return $modelName;
    }


    /**
     * Set cache attributes.
     *
     * @param string $class
     * @param string $cacheName
     * @param array $attributes
     *
     * @return $this
     * @return void
     */
    public static function initializeCache(string $cacheName){
        $model = '\\App\\Models\\'.self::getModelFromParent();
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
     * @return $this
     */
    public static function cache(string $cacheName = ''){
        if(!$cacheName){
            $cacheName = self::getModelFromParent();
        }
        $cacheInstance = self::initializeCache($cacheName);
        $cacheInstance->cachedItems = Cache::get($cacheInstance->cacheName);

        return $cacheInstance;
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
     * Where cache.
     * User to generate cache query.
     *
     * @param $key
     * @param $operator
     * @param null $value
     * @return PostCollection
     */
    public function whereCache($key, $operator, $value = null){
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->whereCache[] = [
          'key' => $key,
          'operator' => $operator,
          'value' => $value,
        ];

        return $this;
    }

    /**
     * Gete where cache value.
     *
     * @param string|null $key
     */
    protected function whereCacheValue($key){
        foreach($this->whereCache as $where){
            if($where['key'] === $key){
                return $where['value'];
            }
        }

        return null;
    }

    /**
     * Join cache query.
     *
     * @param $table
     * @param $first
     * @param null $operator
     * @param null $second
     * @param string $type
     * @param bool $where
     * @return $this
     */
    public function joinCache($table, $first, $operator = null, $second = null, $type = 'inner', $where = false){
        $this->jsonCache[] = [
          'table' => $table,
          'fill' => $first,
          'operator' => $operator,
          'second' => $second,
          'type' => $type,
          'where' => $where
        ];
        return $this;
    }


    /**
     * Set the relationships that should be eager loaded to cache.
     *
     * @param  mixed  $relations
     * @return $this
     */
    public function withCache($relations){
        $this->withCache = $relations;
        return $this;
    }

    /**
     * Get items from cache.
     *
     * @param bool $returnCollection
     * @return PostCollection|array Returns array on [TRUE], array on false
     */
    public function getItems($customCacheMethod = '', $returnCollection = true){
        // Generate cache if it doesn't exist
        if(!$this->cachedItems){
            if($customCacheMethod){
                $this->cachedItems = $this->handleCustomCache($customCacheMethod);
            }else{
                $this->cachedItems = $this->generateCache();
            }
        }

        // Return collection
        if($returnCollection){
            return $this->setCacheCollection($this->cachedItems);
        }

        return $this->cachedItems;
    }

    /**
     * Default method to handle cache query.
     *
     * @return array
     */
    public function generateCache(){
        $queryObject = $this;

        // Join
        if($this->joinCache){
            foreach($this->joinCache as $join){
                $queryObject = $queryObject->join($join['table'], $join['first'], $join['operator'], $join['second'], $join['type'], $join['where']);
            }
        }

        // With relations
        $withRelations = $this->withCache;
        if($withRelations){
            $queryObject->with($withRelations);
        }

        // Where conditions
        if($this->whereCache){
            foreach($this->whereCache as $where){
                $queryObject = $queryObject->where($where['key'], $where['operator'], $where['value']);
            }
        }

        // Limit
        $limit = (property_exists($this,'defaultLimitCache') ? $this->defaultLimitCache : $this->limitCache);
        if($limit){
            $queryObject = $queryObject->limit($limit);
        }

        // Order
        $orderBy = $this->orderByCache;
        if($orderBy){
            $queryObject = $queryObject->orderBy($orderBy['key'],$orderBy['type']);
        }

        // Execute query
        $data = $queryObject->get()->toArray();

        // Save in cache
        Cache::forever($this->cacheName,$data);

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
     * Fill cache attributes
     *
     * @param $class
     * @param array $attributes
     * @return Collection
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
                $modelObj = new $class();
                foreach ($attributes as $key => $value) {
                    $modelObj->setAttribute($key, $value);
                }
                return $modelObj;
            });
        }

    }
}