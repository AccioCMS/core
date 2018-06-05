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
    public static function setCacheCollection(array $data, $class, $isPostType = false, $table = null){

        // model may have its own collection method
        if(method_exists($class,'newCollection')){
            $collection = (new $class())->newCollection($data);
        }else{
            $collection = new Collection($data);
        }

        $collection->transform(function ($row) use($class,$isPostType,$table) {

            // because cache saves json values are object, we need to encode them so
            // we laravel does not try to cast tham again
            $attributes = [];
            foreach($row as $key => $value){
                $getType = gettype($value);

                switch ($getType){
                    case 'object';
                        $value = json_encode($value);
                    break;

                    case 'array';
//                        $value = $obj = json_decode(json_encode($value));
                        break;

                }

                $attributes[$key] = $value;
            }

            // initialize model
            $modelObj = new $class();

            // change table
            if($isPostType){
                $modelObj->setTable($table);
            }

            $modelObj->setRawAttributes($attributes);

            return $modelObj;
        });

        return $collection;
    }

    /**
     * Fill cache attributes
     *
     * @param $class
     * @param array $attributes
     * @return mixed
     */
    public function fillCacheAttributes($class, array $items){
        return collect($items)->transform(function ($attributes) use($class) {
            $modelObj = new $class();
            foreach($attributes as $key => $value){
                $modelObj->setAttribute($key, $value);
            }
            return $modelObj;
        });

    }
}