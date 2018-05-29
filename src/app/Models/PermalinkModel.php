<?php

namespace Accio\App\Models;

use App\Models\Permalink;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Accio\App\Traits;
use Spatie\Activitylog\Traits\LogsActivity;

class PermalinkModel extends Model
{

    use Traits\PermalinkTrait, LogsActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'permalinks';

    /**
     * The primary key of the table
     *
     * @var string $primaryKey
     */
    public $primaryKey = "permalinkID";

    /**
     * Fields that can be filled in CRUD
     *
     * @var array $fillable
     */
    protected $fillable = ['permalinkID','url','controller','method','http_method'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var bool
     */
    protected static $logFillable = true;

    /**
     * @var bool
     */
    protected static $logOnlyDirty = true;

    /**
     * @inheritdoc
     * */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        Event::fire('permalink:construct', [$this]);
    }

    /**
     * Get Permalinks
     * If Permalinks  are available in cache, they are stored from cache, otherwise cache is generated
     *
     * @return array
     */
    public static function getFromCache(){
        if(!Cache::has('permalinks')){
            $getPermalinks = Permalink::all();
            Cache::forever('permalinks',$getPermalinks);

            return $getPermalinks;
        }
        return Cache::get('permalinks');
    }

    /**
     * Handle callback of insert, update, delete
     * */
    protected static function boot(){
        parent::boot();

        self::saving(function($permalink){
            Event::fire('permalink:saving', [$permalink]);
        });

        self::saved(function($permalink){
            Event::fire('permalink:saved', [$permalink]);
            Permalink::_saved($permalink);
        });

        self::creating(function($permalink){
            Event::fire('permalink:creating', [$permalink]);
        });

        self::created(function($permalink){
            Event::fire('permalink:created', [$permalink]);
        });

        self::updating(function($permalink){
            Event::fire('permalink:updating', [$permalink]);
        });

        self::updated(function($permalink){
            Event::fire('permalink:updated', [$permalink]);
        });

        self::deleting(function($permalink){
            Event::fire('permalink:deleting', [$permalink]);
        });

        self::deleted(function($permalink){
            Event::fire('permalink:deleted', [$permalink]);
            Permalink::_deleted($permalink);
        });
    }

    /**
     * Perform certain actions after a permalink is saved
     *
     * @param object $permalink Saved Permalink
     * */
    private static function _saved($permalink){
        //delete existing cache
        Cache::forget('permalinks');
    }

    /**
     * Perform certain actions after a permalink is deleted
     *
     * @param object $permalink Deleted permalink
     * */
    private static function _deleted($permalink){
        //delete existing cache
        Cache::forget('permalinks');
    }

    /**
     * Destruct model instance
     */
    public function __destruct()
    {
        Event::fire('permalink:destruct', [$this]);
    }
}
