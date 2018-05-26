<?php
/**
 * Menu Model
 *
 * It handles site's Menus
 * @author Jetmir Haxhisefa <jetmir.haxhisefa@manaferra.com>
 * @version 1.0
 */

namespace Accio\App\Models;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Accio\App\Traits;

class MenuModel extends Model{

    use Traits\MenuTrait;

    /**
     * Fields that can be filled in CRUD
     *
     * @var array $fillable
     */
    protected $fillable = ['title','slug','isPrimary'];

    /**
     * The table associated with the model.
     *
     * @var string $table
     */
    public $table = "menus";

    /**
     * The primary key of the table
     *
     * @var string $primaryKey
     */
    protected $primaryKey = "menuID";

    /**
     * The path to back end view directory
     *
     * @var string $backendPathToView
     */
    public static $backendPathToView = "backend.menu.";

    /**
     * Show how many rows to show in the pagination
     *
     * @var integer $paginationTo
     */
    public static $rowsPerPage = 100;

    /**
     * Lang key that points to the multi language label in translate file
     * @var string
     */
    public static $label = "Menu.label";

    /**
     * Default permissions that will be listed in settings of permissions
     *
     * @var array $defaultPermissions
     */
    public static $defaultPermissions = ['create', 'read', 'update', 'delete'];

    /**
     * @inheritdoc
     * */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        Event::fire('menu:construct', [$this]);
    }

    /**
     * Get menu from cache. Cache is generated if not found
     *
     * @return object|null
     * */
    public static function getFromCache(){
        if(!Cache::has('menu')){
            $getMenu = Menu::all()->keyBy('slug');
            Cache::forever('menu',$getMenu);

            return $getMenu;
        }
        return Cache::get('menu');
    }

    /**
     * Create primary menu (if it doesn't exist
     * @return bool
     */
    public static function createPrimaryMenu(){
        $check = Menu::where('slug', 'primary')->get()->first();
        if(!$check){

            $create = factory(Menu::class)->create([
                'title' => 'Primary',
                'slug' => 'primary',
                'isPrimary' => true,
            ]);

            if($create){
                return true;
            }
        }
        return false;
    }

    /**
     * Handle callback of insert, update, delete
     * */
    protected static function boot(){
        parent::boot();

        self::saving(function($menu){
            Event::fire('menu:saving', [$menu]);
        });

        self::saved(function($menu){
            Event::fire('menu:saved', [$menu]);
            Menu::_saved($menu);
        });

        self::creating(function($menu){
            Event::fire('menu:creating', [$menu]);
        });

        self::created(function($menu){
            Event::fire('menu:created', [$menu]);
        });

        self::updating(function($menu){
            Event::fire('menu:updating', [$menu]);
        });

        self::updated(function($menu){
            Event::fire('menu:updated', [$menu]);
        });

        self::deleting(function($menu){
            Event::fire('menu:deleting', [$menu]);
        });

        self::deleted(function($menu){
            Event::fire('menu:deleted', [$menu]);
            Menu::_deleted($menu);
        });
    }

    /**
     * Perform certain actions after the menu is saved
     *
     * @param object $menu Saved menu
     * */
    private static function _saved($menu){
        //delete existing cache
        Cache::forget('menu');
    }

    /**
     * Perform certain actions after the menu is deleted
     *
     * @param object $menu Deleted menu
     **/
    private static function _deleted($menu){
        //delete existing cache
        Cache::forget('menu');
    }

    /**
     * Destruct model instance
     */
    public function __destruct()
    {
        Event::fire('menu:destruct', [$this]);
    }
}
