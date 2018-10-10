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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Accio\App\Traits;
use Spatie\Activitylog\Traits\LogsActivity;

class MenuModel extends Model{

    use
      Traits\MenuTrait,
      LogsActivity,
      Traits\CacheTrait,
      Traits\BootEventsTrait,
      Traits\CollectionTrait;

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
     * The primary key of the table.
     *
     * @var string $primaryKey
     */
    protected $primaryKey = "menuID";

    /**
     * The path to back end view directory.
     *
     * @var string $backendPathToView
     */
    public static $backendPathToView = "backend.menu.";

    /**
     * Show how many rows to show in the pagination.
     *
     * @var integer $paginationTo
     */
    public static $rowsPerPage = 100;

    /**
     * Lang key that points to the multi language label in translate file.
     *
     * @var string
     */
    public static $label = "Menu.label";

    /**
     * Default permissions that will be listed in settings of permissions.
     *
     * @var array $defaultPermissions
     */
    public static $defaultPermissions = ['create', 'read', 'update', 'delete'];

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
    public function __construct(array $attributes = []){
        parent::__construct($attributes);
        Event::fire('menu:construct', [$this]);
    }

    /**
     * Create primary menu (if it doesn't exist.
     *
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
     * Destruct model instance.
     */
    public function __destruct(){
        Event::fire('menu:destruct', [$this]);
    }
}
