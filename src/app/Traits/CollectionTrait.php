<?php

namespace Accio\App\Traits;

use Accio\Support\AccioCollection;

trait CollectionTrait
{
    /**
     * Create a custom Eloquent Collection instance.
     *
     * @param  array       $models
     * @param  string|null $eloquent
     * @param  string|null $table
     * @return AccioCollection
     */
    public function newCollection(array $models = [], string $eloquent = null, string $table = null)
    {
        return (new AccioCollection($models))->setModel($eloquent, $table);
    }
}