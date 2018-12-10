<?php

namespace Accio\App\Models;

use Accio\App\Traits\BootEventsTrait;
use Accio\App\Traits\CollectionTrait;
use App\Models\Permission;
use App\Models\UserGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Mockery\Exception;
use Spatie\Activitylog\Traits\LogsActivity;

class UserGroupModel extends Model
{

    use
      LogsActivity,
      BootEventsTrait,
      CollectionTrait;

    /**
     * fields that can be filled in CRUD
     *
     * @var array
     */
    protected $fillable = [
        'name', 'slug', 'isDefault'
    ];

    /**
     * Name of the primary key
     *
     * @var string
     */
    protected $primaryKey = "groupID";

    /**
     * Table name
     *
     * @var string
     */
    protected $table = "users_groups";

    /**
     * @var bool
     */
    protected static $logFillable = true;

    /**
     * @var bool
     */
    protected static $logOnlyDirty = true;

    /**
     * Get admin group.
     *
     * @return object
     * @throws Exception
     */
    public static function getAdminGroup()
    {
        return UserGroup::where('slug', "admin")->get()->first();
    }

    /**
     * Get an editor role.
     *
     * @return object
     * @throws Exception
     */
    public static function getEditorGroup()
    {
        return UserGroup::where('slug', "editor")->get()->first();
    }

    /**
     * Get an editor role.
     *
     * @return object
     * @throws Exception
     */
    public static function getAuthorGroup()
    {
        return UserGroup::where('slug', "author")->get()->first();
    }

    /**
     * Create default roles.
     *
     * @return void
     */
    public static function createDefaultRoles()
    {
        self::createAdminRole();
        self::createEditorRole();
        self::createAuthorRole();

        return;
    }

    /**
     * Create an Admin role.
     *
     * @param  bool $force Create admin role even if it exist
     * @return bool
     */
    public static function createAdminRole($force = false)
    {
        if(!$force) {
            if(Permission::exists('global', 'admin')) {
                return false;
            }
        }

        return self::createRole(
            'Admin', true, [[
            'app' => 'global',
            'key' => 'admin'
            ]]
        );
    }

    /**
     * Create an Editor role.
     *
     * @param  bool $force Create editor role even if it exist
     * @return bool
     */
    public static function createEditorRole($force = false)
    {
        if(!$force) {
            if(Permission::exists('global', 'editor')) {
                return false;
            }
        }

        return self::createRole(
            'Editor', true, [[
            'app' => 'global',
            'key' => 'editor'
            ]]
        );
    }

    /**
     * Create an Author role.
     *
     * @param  bool $force Create author role even if it exist
     * @return bool
     */
    public static function createAuthorRole($force = false)
    {
        if(!$force) {
            if(Permission::exists('global', 'author')) {
                return false;
            }
        }

        return self::createRole(
            'Author', true, [[
            'app' => 'global',
            'key' => 'author'
            ]]
        );
    }

    /**
     * Create an admin role.
     *
     * @param  string $name
     * @param  bool   $isDefault
     * @param  array  $permissions ["app" => "lorem", "key" => ipsum]
     * @return boolean
     */
    public static function createRole($name, $isDefault, $permissions = [])
    {

        // Create admin role
        $role = (new static());
        $role->name = $name;
        $role->isDefault = $isDefault;
        $role->slug = str_slug($name);

        if($createdRole = $role->save()) {
            // Assert Permissions
            if($permissions) {
                $permissionObj = new Permission();
                foreach ($permissions as $permission) {
                    $permissionObj->assertPermission($role->groupID, $permission);
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     * */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        Event::fire('userGroup:construct', [$this]);
    }

    /**
     * Destruct model instance
     */
    public function __destruct()
    {
        Event::fire('userGroup:destruct', [$this]);
    }
}
