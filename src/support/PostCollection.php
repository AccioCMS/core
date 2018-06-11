<?php
namespace Accio\Support;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class PostCollection extends Collection
{

    /**
     * Scope a query to only include published posts.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function published(){
        return $this->where('published_at', '<=', date('Y-m-d H:i:s'));
    }

    /**
     * Scope a query to only include unpublished posts.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function unpublished(){
        return $this->where('published_at', '>', date('Y-m-d H:i:s'));
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

}