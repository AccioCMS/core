<?php

namespace Accio\App\Models;

use App\Models\Plugin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Accio\App\Traits;
use Spatie\Activitylog\Traits\LogsActivity;

class PluginModel extends Model{

    use
      Traits\PluginTrait,
      LogsActivity,
      Traits\CacheTrait,
      Traits\BootEventsTrait,
      Traits\CollectionTrait;

    /**
     * The table associated with the model.
     *
     * @var string $table
     */
    protected $table = "plugins";

    /**
     * The primary key of the table
     *
     * @var string $primaryKey
     */
    protected $primaryKey = "pluginID";

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
        Event::fire('plugin:construct', [$this]);
    }

    /**
     * Destruct model instance
     */
    public function __destruct(){
        Event::fire('plugin:destruct', [$this]);
    }
}
