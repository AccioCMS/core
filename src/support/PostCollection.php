<?php
namespace Accio\Support;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class PostCollection extends Collection
{

    /**
     * Scope a query to only include published posts.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function published(){

        return $this
          ->where('published_at', '<=', date('Y-m-d H:i:s'))
          ->whereJson('status->'.App::getLocale(),'published');
    }

    /**
     * Scope a query to only include unpublished posts.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function unpublished(){
        return $this
          ->where('published_at', '>', date('Y-m-d H:i:s'))
          ->whereJson('status->'.App::getLocale(),'published');
    }

    /**
     * Create a pagination of items from array or collection.
     *
     * @param array|Collection      $items
     * @param int  $perPage
     * @param int  $page
     * @param array $options
     *
     * @return LengthAwarePaginator
     */
    public function paginate($perPage = 15, $page = null, $options = [], $setPath = null)
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = ($this instanceof Collection || $this instanceof PostCollection)? $this : Collection::make($items);
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

            $retrieved = data_get($item, $column);

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
}