<?php

namespace Accio\App\Traits;


use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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
        self::created(function($post){
            self::manageCacheState();
        });

        self::updated(function($post){

        });

        self::deleted(function($post){

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
     * Get cache name.
     *
     * @return mixed
     */
    private function cacheName(){
        return $this->cacheName;
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
    private static function initializeCache($model, string $cacheName, array $attributes){
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
        return $default;
    }


    /**
     * @param string $cacheName
     * @param string $class
     * @return mixed
     *
     * @throws \Exception
     */
    private function handleCustomCache(string $class){
        $methodName = 'cache'.ucfirst($this->cacheName());

        if(method_exists($class,$methodName)){
            return $class->$methodName($this);
        }else{
            throw new \Exception("Cache method $methodName does not exists!");
        }
    }
    /**
     * Add, update or remove an item from cache.
     *
     * @param string $cacheName
     * @param object $item
     * @param integer $limit
     * @param bool $delete
     *
     * @return Collection
     */
    private static function manageCacheState($cacheName, $item, $limit, $delete = false){
        $cachedItems = Cache::get($cacheName);

        if(!$cachedItems){
            $cachedItems = collect();
        }

        // DELETE
        if($delete){
            if($cachedItems->has($post->postID)){
                $cachedItems->forget($post->postID);
            }
        }else {
            // UPDATE
            if ($cachedItems->has($post->postID)) {
                $cachedItems[$post->postID] = $post;
            } else { // ADD
                $cachedItems->put($post->postID, $post);

                // Limit results
                $countItems = $cachedItems->count();
                if ($countItems > $limit) {
                    $cachedItems = $cachedItems->slice(($countItems - $limit));
                }
            }
        }

        Cache::forever($cacheName,$cachedItems);

        return $cachedItems;
    }

    /**
     * Set cache collection
     *
     * @param array $data
     * @return Collection
     */
    public function setCacheCollection(array $data){
        $model = $this->getModel();
        $table = $this->cacheAttribute('table');

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
     * @return string
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
    public static function getFromCache($attributes = []){
        $modelName = self::getModelFromParent();
        $classPath = '\\App\\Models\\'.$modelName;

        $cacheInstance = self::initializeCache($classPath, $modelName, $attributes);
        $data = Cache::get($cacheInstance->cacheName);

        if(!$data){
            $data  = $cacheInstance->cache();
        }

        return $cacheInstance->setCacheCollection($data);
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
     * Delete default cache
     *
     * @return array
     */
    public function deleteCache(){
        Cache::forget(self::getModelFromParent());
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