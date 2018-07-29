<?php

namespace Accio\App\Traits;

use Accio\Support\AccioCollection;

trait CollectionTrait
{
    /**
     * Create a custom Eloquent Collection instance.
     *
     * @param  array  $models
     * @return PostCollection
     */
    public function newCollection(array $models = [])
    {
        return new AccioCollection($models);
    }
}