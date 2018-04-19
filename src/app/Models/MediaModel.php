<?php
/**
 * Media Model
 *
 * It handles Media management
 * @author Jetmir Haxhisefa <jetmir.haxhisefa@manaferra.com>
 * @author Faton Sopa <faton.sopa@manaferra.com>
 * @version 1.0
 */
namespace Accio\App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Event;
use Image;
use Auth;
use Accio\App\Traits;

class MediaModel extends Model{

    use Traits\MediaTrait;

    /**
     * Fields that can be filled in CRUD
     *
     * @var array $fillable
     */
    protected $fillable = ['title', 'description', 'credit', 'type', 'extension', 'url', 'filename', 'fileDirectory', 'filesize', 'dimensions'];

    /**
     * The primary key of the table
     *
     * @var string $primaryKey
     */
    protected $primaryKey = "mediaID";

    /**
     * The table associated with the model.
     *
     * @var string $table
     */
    public $table = "media";

    /**
     * The path to back end view directory
     *
     * @var string $backendPathToView
     */
    public static $backendPathToView = "backend.media.";

    /**
     * Lang key that points to the multi language label in translate file
     * @var string
     */
    public static $label = "Media.label";

    /**
     * Default permissions that will be listed in settings of permissions
     *
     * @var array $defaultPermissions
     */
    public static $defaultPermissions = ['create','read', 'update', 'delete'];

    /**
     * File extensions that are allowed to be uploaded from admin panel
     *
     * @var array $extensions
     */
    public static $extensions = array('jpg','png','jpeg','pdf','txt');

    /**
     * Image extensions that are allowed to be uploaded
     *
     * @var array $imageExtensions
     */
    public static $imageExtensions = array('jpg','png','jpeg');

    /**
     * Documents extensions that are allowed to be uploaded.
     *
     * @var array $documentExtensions
     */
    public static $documentExtensions = array('pdf','doc','docs','xls','xlsx','csv','txt');

    /**
     * Document icon path that should be shown in admin interface
     *
     * @var string $documentIconUrl
     */
    public static $documentIconUrl = "/images/document.png";

    /**
     * Video extensions that are allowed to be uploaded.
     *
     * @var array $videoExtensions
     */
    public static $videoExtensions = array('mp4','flv','mov');

    /**
     * Video icon path that should be shown in admin interface
     *
     * @var string $videoIconUrl
     */
    public static $videoIconUrl = "/images/video.png";

    /**
     * Audio extensions that are allowed to be uploaded.
     *
     * @var array $videoExtensions
     */
    public static $audioExtensions = array('mp3','ogg');

    /**
     * Audio icon path that should be shown in admin interface
     *
     * @var string $audioIconUrl
     */
    public static $audioIconUrl = "/images/audio.png";

    /**
     * The number of media files to show while scrolling in the library list
     *
     * @var int $infinitPaginationShow
     */
    public static $infinitPaginationShow = 100;

    /** @var array $thumbSizes Default thumb sizes of the app*/
    //@TODO should be defined in each model respectively
    //@TODO handle thumbs that have only a fixed width,
    //@TODO handle thumbs that have only a fixed height
    public static $thumbSizes = [
        'default' => [
            [200,200]
        ],
        'users' => [
            [100,100],
        ],
    ];

    /**
     * @inheritdoc
     * */
    public function __construct(array $attributes = []){
        parent::__construct($attributes);
        Event::fire('media:construct', [$this]);
    }

    /**
     * Get all relations of a media
     * @return HasMany
     */
    public function relations()
    {
        return $this->hasMany('App\Models\MediaRelation','mediaID','mediaID');
    }

    /**
     * Handle callback of insert, update, delete
     * */
    protected static function boot(){
        parent::boot();

        self::saving(function($media){
            Event::fire('media:saving', [$media]);
        });

        self::saved(function($media){
            Event::fire('media:saved', [$media]);
        });

        self::creating(function($media){
            Event::fire('media:creating', [$media]);
        });

        self::created(function($media){
            Event::fire('media:created', [$media]);
        });

        self::updating(function($media){
            Event::fire('media:updating', [$media]);
        });

        self::updated(function($media){
            Event::fire('media:updated', [$media]);
        });

        self::deleting(function($media){
            Event::fire('media:deleting', [$media]);
        });

        self::deleted(function($media){
            Event::fire('media:deleted', [$media]);
        });
    }

    /**
     * @return array all allowed extensions
     */
    public static function allowedExtensions(){
        return array_merge(self::$audioExtensions, self::$documentExtensions, self::$imageExtensions, self::$videoExtensions);
    }

    /**
     * Scope a query to only include images
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeImages($query){
        return $query->where('type','image');
    }


    /**
     * Scope a query to only include videos
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVideos($query){
        return $query->where('type','video');
    }

    /**
     * Scope a query to only include audio
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAudio($query){
        return $query->where('type','audio');
    }

    /**
     * Scope a query to only include documents
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDocuments($query){
        return $query->where('type','documents');
    }

    /**
     * Destruct model instance
     */
    public function __destruct()
    {
        Event::fire('media:destruct', [$this]);
    }
}
