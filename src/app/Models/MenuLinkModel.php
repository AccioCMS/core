<?php
/**
 * Menu Links Model
 *
 * It handles MenuLinks of the site
 * @author Jetmir Haxhisefa <jetmir.haxhisefa@manaferra.com>
 * @author Faton Sopa <faton.sopa@manaferra.com>
 * @version 1.0
 */
namespace Accio\App\Models;

use App\Models\MenuLink;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Models\Language;
use Illuminate\Support\Facades\Event;
use Accio\App\Traits;
use Spatie\Activitylog\Traits\LogsActivity;

class MenuLinkModel extends Model{

    use Traits\MenuLinkTrait, LogsActivity;

    /**
     * Fields that can be filled in CRUD
     *
     * @var array $fillable
     */
    protected $fillable = ['menuID', 'belongsTo', 'belongsToID', 'label', 'slug', 'parent', 'cssClass', 'order', 'customLink', 'controller', 'method', 'routeName','params'];

    /**
     * The attributes that should be cast to native types.
     * @var array
     */
    protected $casts = [
        'label' => 'object',
        'params' => 'object',
        'slug' => 'object',
    ];

    /**
     * The primary key of the table
     *
     * @var string $primaryKey
     */
    protected $primaryKey = "menuLinkID";

    /**
     * The table associated with the model.
     *
     * @var string $table
     */
    public $table = "menu_links";

    /**
     * Lang key that points to the multi language label in translate file
     * @var string
     */
    public static $label = "MenuLink.label";

    /**
     * Default permissions that will be listed in settings of permissions
     *
     * @var array $defaultPermissions
     */
    public static $defaultPermissions = ['create','read', 'update', 'delete'];

    /**
     * @var bool
     */
    protected static $logFillable = true;

    /**
     * @inheritdoc
     * */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        Event::fire('menuLink:construct', [$this]);
    }

    /**
     * Get MenuLinks from cache. Cache is generated if not found
     *
     * @param string $languageSlug Slug of language to get the data from
     * @return object|null  Returns requested cache if found, null instead
     * */

    public static function getFromCache($languageSlug = ""){
        if(!$languageSlug){
            $languageSlug = \App::getLocale();
        }

        //we need an empty cache to fill it later
        if(!Cache::has("menuLinks")) {
            $cachedItems = new \stdClass();
            Cache::forever("menuLinks",$cachedItems);
        }else{
            //get requested cache
            $cachedItems = Cache::get("menuLinks");
        }

        //set cache in this language
        if(!isset($cachedItems->$languageSlug)){
            $menuLinks = MenuLink::all()->keyBy('menuLinkID');
            $cachedItems->$languageSlug = Language::translateList($menuLinks, $languageSlug);
            Cache::forever('menuLinks',$cachedItems);

            return $menuLinks;
        }

        // return posts of current language
        if(isset($cachedItems->$languageSlug)){
            return $cachedItems->$languageSlug;
        }
    }

    /**
     * Handle callback of insert, update, delete
     * */
    protected static function boot(){
        parent::boot();

        self::saving(function($menuLink){
            Event::fire('menuLink:saving', [$menuLink]);
        });

        self::saved(function($menuLink){
            MenuLink::_saved($menuLink);
            Event::fire('menuLink:saved', [$menuLink]);
        });

        self::creating(function($menuLink){
            Event::fire('menuLink:creating', [$menuLink]);
        });

        self::created(function($menuLink){
            Event::fire('menuLink:created', [$menuLink]);
        });

        self::updating(function($menuLink){
            Event::fire('menuLink:updating', [$menuLink]);
        });

        self::updated(function($menuLink){
            Event::fire('menuLink:updated', [$menuLink]);
        });

        self::deleting(function($menuLink){
            Event::fire('menuLink:deleting', [$menuLink]);
        });

        self::deleted(function($menuLink){
            MenuLink::_deleted($menuLink);
            Event::fire('menuLink:deleted', [$menuLink]);
        });
    }

    /**
     * Delete Menulink caches
     */
    public static function deleteCache(){
        Cache::forget('menuLinks');
    }

    /**
     * Perform certain actions after a menulink is saved
     *
     * @param object $menulink Saved menulink
     * */
    private static function _saved($menulink){
        self::deleteCache();
    }

    /**
     * Perform certain actions after a menulink is deleted
     *
     * @param object $menulink Deleted menulink
     * */
    private static function _deleted($menulink){
        self::deleteCache();
    }

    /**
     * Destruct model instance
     */
    public function __destruct()
    {
        Event::fire('menuLink:destruct', [$this]);
    }
}


