<?php
namespace Accio\Support\Facades;

use Illuminate\Support\Facades\Facade;

class Meta extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Accio\App\Services\Meta::class;
    }
}
