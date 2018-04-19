<?php
namespace Accio\Support\Facades;

use Illuminate\Support\Facades\Facade;

class Search extends Facade
{
    protected static function getFacadeAccessor(){
        return \Accio\App\Services\Search::class;
    }
}
