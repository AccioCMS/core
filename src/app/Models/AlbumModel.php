<?php

/**
 * Album Model
 *
 * It handles Albums management
 * @author Jetmir Haxhisefa <jetmir.haxhisefa@manaferra.com>
 * @version 1.0
 */

namespace Accio\App\Models;

use App\Models\Album;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Spatie\Activitylog\Traits\LogsActivity;

class AlbumModel extends Model{

    use LogsActivity;

    /**
     * Fields that can be filled
     *
     * @var array $fillable
     */
    protected $fillable = ['albumID','createdByUserID', 'title','description','visible'];

    /**
     * The primary key of the table
     *
     * @var string $primaryKey
     */
    public $primaryKey = "albumID";

    /**
     * Default number of rows per page to be shown in admin panel
     *
     * @var integer $rowsPerPage
     */
    public static $rowsPerPage = 25;

    /**
     * The table associated with the model.
     *
     * @var string $table
     */
    public $table = "albums";

    /**
     * Lang key that points to the multi language label in translate file
     * @var string
     */
    public static $label = "album.label";

    /**
     * Default permission that will be listed in settings of permissions
     *
     * @var array $defaultPermissions
     */
    public static $defaultPermissions = ['create','read', 'update', 'delete'];

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
        Event::fire('album:construct', [$this]);
    }

    /**
     * Declare columns that should be saved in MenuLinks table as 'attributes', to enable navigation in front-end
     *
     * @return array
     */
    public  function menuLinkParameters()
    {
        return [
            'albumID'   => $this->albumID,
            'albumSlug' => $this->slug,
            'date'      => date('Y-m-d',strtotime($this->created_at)),
        ];
    }

    /**
     * Listen to crud events
     * */
    protected static function boot(){
        parent::boot();

        self::saving(function($album){
            Event::fire('album:saving', [$album]);
        });

        self::saved(function($album){
            Event::fire('album:saved', [$album]);
        });

        self::creating(function($album){
            Event::fire('album:creating', [$album]);
        });

        self::created(function($album){
            Event::fire('album:created', [$album]);
        });

        self::updating(function($album){
            Event::fire('album:updating', [$album]);
        });

        self::updated(function($album){
            Event::fire('album:updated', [$album]);
        });

        self::deleting(function($album){
            Event::fire('album:deleting', [$album]);
        });

        self::deleted(function($album){
            Event::fire('album:deleted', [$album]);
        });
    }

    /**
     * Destruct model instance
     */
    public function __destruct()
    {
        Event::fire('album:destruct', [$this]);
    }
}
