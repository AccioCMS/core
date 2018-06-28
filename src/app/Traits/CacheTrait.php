<?php

namespace Accio\App\Traits;


use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

trait CacheTrait
{

    /**
     * @var
     */
    protected $cacheModel;

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
     * Boot Cache Trait Events.
     *
     * @return void
     */
    protected static function bootCacheTrait(){
        self::saved(function($item){
            $item->handleUpdateCache($item, "saved");
        });

        self::created(function($item){
            $item->handleUpdateCache($item, "created");
        });


        self::deleting(function($item){
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
     * Set model.
     *
     * @param string $model
     */
    private function setModel(string $model){
        $this->cacheModel = $model;
    }

    /**
     * Get model.
     *
     * @return mixed
     */
    private function getModel(){
        return $this->cacheModel;
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
     * Set cache attributes.
     *
     * @param string $class
     * @param string $cacheName
     * @param array $attributes
     *
     * @return $this
     * @return void
     */
    public static function initializeCache($model, string $cacheName, array $attributes){
        $instance = (new self());
        $instance->setModel($model);
        $instance->setCacheName($cacheName);
        $instance->setCacheAttributes($attributes);
        $instance->setCacheInstance($instance);
        return $instance;
    }

    /**
     * Set cache attributes.
     *
     * @param array $attributes
     * @return void
     */
    private function setCacheAttributes(array $attributes){
        $this->cacheAttributes = $attributes;
        return;
    }

    /**
     * Get cache attribute.
     *
     * @param string $key
     * @param mixed $default
     */
    private function cacheAttribute($key, $default = null){
        if(isset($this->cacheAttributes[$key])){
            return $this->cacheAttributes[$key];
        }

        $this->cacheAttributes[$key] = $default;

        return $this->cacheAttributes[$key];
    }

    private function cacheWhere($key, $default = null){
        $whereList = $this->cacheAttribute('where');
        if($whereList){
            if(isset($whereList[$key])){
                return $whereList[$key];
            }
        }

        return $default;
    }

    /**
     * @param string $cacheName
     * @param string $class
     * @return mixed
     *
     * @throws \Exception
     */
    private function handleCustomCache(){
        if($this->cacheAttribute('method')){
            $methodName = $this->cacheAttribute('method');
        }else{
            $methodName = 'cache'.ucfirst($this->cacheName);
        }

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
        $cachedItems = $classPath::getFromCache($cacheName, $attributes, false);

        $currentItem = $item->hasCacheItem($cachedItems,  $item->getKeyName(), $item->getKey());

        if(!$cachedItems){
            $cachedItems = [];
        }

        // DELETE
        if($mode == 'delete'){
            if ($currentItem) {
                $cachedItems = array_pull($cachedItems, key($currentItem));
            }
        }else {
            // UPDATE
            if ($currentItem) {
                $cachedItems[key($currentItem)] = $item->toArray();
            } else { // ADD
                $cachedItems = array_add($cachedItems, $item->getKey(), $item->toArray());

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

    private function hasCacheItem($array, $keyName, $keyValue){
        return array_where($array, function ($item) use($keyName, $keyValue) {
            return ($keyValue == $item[$keyName]);
        });

    }

    public function cacheLimit(){
        $classPath = '\\App\\Models\\'.self::getModelFromParent();

        // Set cache limit
        $limit = null;
        if(property_exists($classPath, 'defaultCacheLimit')){
            $limit = $classPath::$defaultCacheLimit;
        }

        return  $this->cacheAttribute('limit', $limit);
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
        $model = $this->getModel();
        $table = $this->cacheAttribute('table', $table);


        // model may have its own collection method
        if(method_exists($model,'newCollection')){
            $collection = (new $model())->newCollection($data);
        }else{
            $collection = new Collection($data);
        }

        $collection->transform(function ($row) use($model,$table) {

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
            $modelObj = new $model();

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
     * Get items from cache.
     * Cache is generated if not found.
     *
     * @return Collection
     */
    public static function getFromCache(string $cacheName = '', $attributes = [], $returnCollection = true){
        if(!$cacheName){
            $cacheName = self::getModelFromParent();
        }
        $classPath = '\\App\\Models\\'.self::getModelFromParent();

        $cacheInstance = self::initializeCache($classPath, $cacheName, $attributes);
        $data = Cache::get($cacheInstance->cacheName);

        if(!$data){
            $data  = $cacheInstance->cache();
        }

        if($returnCollection){
            return $cacheInstance->setCacheCollection($data);
        }

        return $data;
    }

    /**
     * Default method to handle cache query.
     *
     * @return array
     */
    public function cache(){
        $classPath = '\\App\\Models\\'.self::getModelFromParent();
        $data  = $classPath::all()->toArray();

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