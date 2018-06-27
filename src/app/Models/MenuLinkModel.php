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

use App\Models\Menu;
use App\Models\MenuLink;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use App\Models\Language;
use Illuminate\Support\Facades\Event;
use Accio\App\Traits;
use Spatie\Activitylog\Traits\LogsActivity;

class MenuLinkModel extends Model{

    use
      Traits\MenuLinkTrait,
      LogsActivity,
      Traits\CacheTrait,
      Traits\TranslatableTrait,
      Traits\BootEventsTrait;

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
     * @var bool
     */
    protected static $logOnlyDirty = true;

    /**
     * @inheritdoc
     * */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        Event::fire('menuLink:construct', [$this]);
    }

    /**
     * Delete Menulink caches
     */
    public static function deleteCache(){
        Cache::forget('menuLinks');
    }

    /**
     * Destruct model instance
     */
    public function __destruct()
    {
        Event::fire('menuLink:destruct', [$this]);
    }
}


