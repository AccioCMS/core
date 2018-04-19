<?php

namespace Accio\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Accio\App\Traits;

class PluginModel extends Model{

    use Traits\PluginTrait;

    /**
     * The table associated with the model.
     *
     * @var string $table
     */
    protected $table = "plugins";

    /**
     * Fields that can be filled in CRUD
     *
     * @var array $fillable
     */
    protected $fillable = [
        'title', 'namespace', 'organization', 'version', 'isActive'
    ];

    /**
     * Lang key that points to the multi language label in translate file
     * @var string
     */
    public static $label = "Plugin.label";

    /**
     * Default permissions that will be listed in settings of permissions
     *
     * @var array $defaultPermissions
     */
    public static $defaultPermissions = ['create','read', 'update', 'delete'];

    /**
     * @inheritdoc
     * */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        Event::fire('plugin:construct', [$this]);
    }

    /**
     * Get settings from cache. Cache is generated if not found
     *
     * @return object|null  Returns requested cache if found, null instead
     */
    public static function getFromCache(){
        if(!Cache::has('plugins')){
            $getData = self::all();
            Cache::forever('plugins', $getData);

            return $getData;
        }

        return Cache::get('plugins');
    }

    /**
     * Handle callback of insert, update, delete
     * */
    protected static function boot(){
        parent::boot();

        self::saving(function($plugin){
            Event::fire('plugin:saving', [$plugin]);
        });

        self::saved(function($plugin){
            Event::fire('plugin:saved', [$plugin]);
            Cache::forget('plugins');
        });

        self::creating(function($plugin){
            Event::fire('plugin:creating', [$plugin]);
        });

        self::created(function($plugin){
            Event::fire('plugin:created', [$plugin]);
        });

        self::updating(function($plugin){
            Event::fire('plugin:updating', [$plugin]);
        });

        self::updated(function($plugin){
            Event::fire('plugin:updated', [$plugin]);
        });

        self::deleting(function($plugin){
            Event::fire('plugin:deleting', [$plugin]);
        });

        self::deleted(function($plugin){
            Event::fire('plugin:deleted', [$plugin]);
            Cache::forget('plugins');
        });
    }

    /**
     * Destruct model instance
     */
    public function __destruct(){
        Event::fire('plugin:destruct', [$this]);
    }
}
