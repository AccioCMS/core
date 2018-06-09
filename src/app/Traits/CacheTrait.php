<?php

namespace Accio\App\Traits;


use Illuminate\Support\Collection;

trait CacheTrait
{

    /**
     * Set cache collection
     *
     * @param array $data
     * @param object $class
     * @param bool $isPostType
     * @param null $table
     * @return Collection
     */
    public static function setCacheCollection(array $data, $class, $table = null){

        // model may have its own collection method
        if(method_exists($class,'newCollection')){
            $collection = (new $class())->newCollection($data);
        }else{
            $collection = new Collection($data);
        }

        $collection->transform(function ($row) use($class,$table) {

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
            $modelObj = new $class();

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