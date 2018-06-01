<?php
namespace Accio\Support;

use Illuminate\Database\Eloquent\Collection;

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

}