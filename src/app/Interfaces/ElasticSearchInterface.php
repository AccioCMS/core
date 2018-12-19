<?php
namespace Accio\App\Interfaces;

interface ElasticSearchInterface
{
    /**
     * Create index and set mapping
     *
     * to execute this function use php artisan elastic:migrate -modelName
     */
    public function setMapping() : void;
}