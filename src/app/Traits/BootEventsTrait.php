<?php

namespace Accio\App\Traits;


use Illuminate\Support\Facades\Event;

trait BootEventsTrait{

    /**
     * Default boot events.
     * */
    protected static function bootBootEventsTrait(){
        $explode = explode('\\',get_class());
        $modelName = lcfirst(str_replace('Model','',end($explode)));

        self::saving(function($album) use($modelName){
            Event::fire($modelName.':saving', [$album]);
        });

        self::saved(function($album) use($modelName){
            Event::fire($modelName.':saved', [$album]);
        });

        self::creating(function($album) use($modelName){
            Event::fire($modelName.':creating', [$album]);
        });

        self::created(function($album) use($modelName){
            Event::fire($modelName.':created', [$album]);
        });

        self::updating(function($album) use($modelName){
            Event::fire($modelName.':updating', [$album]);
        });

        self::updated(function($album) use($modelName){
            Event::fire($modelName.':updated', [$album]);
        });

        self::deleting(function($album) use($modelName){
            Event::fire($modelName.':eleting', [$album]);
        });

        self::deleted(function($album) use($modelName){
            Event::fire($modelName.':deleted', [$album]);
        });
    }
}