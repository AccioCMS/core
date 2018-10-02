<?php
namespace Accio\Support;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class AccioCollection extends Collection {

    /**
     * @var
     */
    private static $_pathToClass;

    /**
     * @var
     */
    private static  $_modelTable;

    /**
     * Scope a query to only include published posts..
     *
     * @return PostCollection
     */
    public function published(){
        return $this
          ->where('published_at', '<=', date('Y-m-d H:i:s'))
          ->whereJson('status->'.App::getLocale(),'published');
    }

    /**
     * Scope a query to only include unpublished posts..
     * 
     * @return PostCollection
     */
    public function unpublished(){
        return $this
          ->where('published_at', '>', date('Y-m-d H:i:s'))
          ->whereJson('status->'.App::getLocale(),'published');
    }

    /**
     * Create a pagination of items from array or collection.
     *
     * @param int $perPage
     * @param null $page
     * @param array $options
     * @param null $setPath
     * @return LengthAwarePaginator
     */
    public function paginate($perPage = 15, $page = null, $options = [], $setPath = null){
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = ($this instanceof Collection || $this instanceof PostCollection) ? $this : Collection::make($this);
        $paginator = new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);

        // Set Path
        if(is_null($setPath)) {
            $paginator->setPath(\Request::url());
        }else{
            $paginator->setPath($setPath);
        }
        return $paginator;
    }

    /**
     * Perform order by.
     * It performs similar to sortBy method of collection but simplifies parameter order.
     *
     * @param $key
     * @param string $mode
     * @param int $options
     * @return PostCollection
     */
    public function orderBy($key, $mode = 'ASC', $options = SORT_REGULAR){
        return $this->sortBy($key, $options, ($mode === 'DESC' ? true : false));
    }

    /**
     * Where json operator.
     *
     * @param $key
     * @param $operator
     * @param null $value
     * @return PostCollection
     */
    public function whereJson($key, $operator, $value = null){
        return $this->filter($this->jsonOperator(...func_get_args()));
    }

    /**
     * Where json operator.
     * It currently supports only level of json depth.
     *
     * @param $key
     * @param $operator
     * @param null $value
     * @return \Closure
     */
    public function jsonOperator($key, $operator, $value = null){
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return function ($item) use ($key, $operator, $value) {
            $column = $key;
            if(Str::contains($key, '->')){
                $explodeKey = explode('->', $key);
                $column = $explodeKey[0];
                $key = $explodeKey[1];
            }

            $retrieved = data_get($item, $column.'.'.$key, null, true);

            $strings = array_filter([$retrieved, $value], function ($value) {
                return is_string($value) || (is_object($value) && method_exists($value, '__toString'));
            });

            if (count($strings) < 2 && count(array_filter([$retrieved, $value], 'is_object')) == 1) {
                return in_array($operator, ['!=', '<>', '!==']);
            }

            switch ($operator) {
                default:
                case '=':
                case '==':  return $retrieved == $value;
                case '!=':
                case '<>':  return $retrieved != $value;
                case '<':   return $retrieved < $value;
                case '>':   return $retrieved > $value;
                case '<=':  return $retrieved <= $value;
                case '>=':  return $retrieved >= $value;
                case '===': return $retrieved === $value;
                case '!==': return $retrieved !== $value;
            }
        };
    }

    /**
     * Get an item from an array or object using "dot" notation.
     * It is similar to laravel's get_data helper method,
     * with a bug fix on missing return statems on two conditions
     *
     * @param  mixed   $target
     * @param  string|array  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function dataGet($target, $key, $default = null){
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        while (! is_null($segment = array_shift($key))) {
            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->all();
                } elseif (! is_array($target)) {
                    return value($default);
                }

                $result = Arr::pluck($target, $key);

                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }

            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                return $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                return $target->{$segment};
            } else {
                return value($default);
            }
        }

        return $target;
    }

    /**
     * Set model path and table to be appended later on cache items.
     *
     * @param $pathToClass
     * @param $table
     * @return $this
     */
    public function setModel($eloquent, $table){
        self::$_pathToClass = $eloquent;
        self::$_modelTable = $table;

        return $this;
    }

    /**
     * Appends a model to each of cache items.
     *
     * @param null $pathToClass
     * @param null $table
     * @return AccioCollection|\Illuminate\Support\Collection
     */
    public function getItems($pathToClass = null, $table = null){
        if(!$pathToClass){
            $pathToClass = self::$_pathToClass;
        }
        if(!$table){
            $table = self::$_modelTable;
        }
        // TODO nese ne nje collection query e thirr nje collection tjeter, proprties
        // nuk barten se where-at e ri-inicializojne klasen me new static
        // keshtu qe duhet me ja gjet nje zgjidhje. Rasti i select categories me postTypeID

        $backtrace = debug_backtrace();
        return $this->map(function ($item) use($pathToClass,$table) {
            $modelInstance = (new $pathToClass())->setTable($table);
            $modelInstance->disableCasts = true;
            return $modelInstance->newFromBuilder($item);
        });
    }

    /**
     * Add, update or remove an item from cache.
     *
     * @param $cacheName
     * @param $item
     * @param $mode
     * @param $limitCache
     * @return $this
     */
    public function refreshState($cacheName, $item, $mode, $limitCache){
        $cachedItems = $this->all();

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
                if($limitCache) {
                    $countItems = count($cachedItems);
                    if ($countItems > $limitCache) {
                        $cachedItems = array_slice($cachedItems, 0, ($limitCache - $countItems));
                    }
                }

            }
        }

        // Save cache
        Cache::forever($cacheName,$cachedItems);

        return $this;
    }
}