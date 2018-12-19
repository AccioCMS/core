<?php
namespace Accio\Support\Facades;

use Illuminate\Support\Facades\Facade;

class Pagination extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Accio\App\Services\Pagination::class;
    }
}
