<?php

namespace Accio\App\Models;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Mockery\Exception;

class UserGroupModel extends Model{

    /** @var array $fillable fields that can be filled in CRUD*/
    protected $fillable = [
        'name', 'slug', 'isDefault'
    ];

    /** @var string $primaryKey the primary key */
    protected $primaryKey = "groupID";

    /** @var string $table The name of the table in database */
    protected $table = "users_groups";

    /**
     * Get admin group
     * @return object
     * @throws Exception
     */
    public static function getAdminGroup(){
        return self::where('slug', "admin")->get()->first();
    }

    /**
     * Get an editor role
     * @return object
     * @throws Exception
     */
    public static function getEditorGroup(){
        return self::where('slug', "editor")->get()->first();
    }

    /**
     * Get an editor role
     * @return object
     * @throws Exception
     */
    public static function getAuthorGroup(){
        return self::where('slug', "author")->get()->first();
    }

    /**
     * Create default roles
     * @return void
     */
    public static function createDefaultRoles(){
        self::createAdminRole();
        self::createEditorRole();
        self::createAuthorRole();

        return;
    }

    /**
     * Create an Admin role
     * @param bool $force Create admin role even if it exist
     * @return bool
     */
    public static function createAdminRole($force = false){
        if(!$force){
            if(Permission::exists('global', 'admin')) {
                return false;
            }
        }

        return self::createRole('Admin', true, [[
            'app' => 'global',
            'key' => 'admin'
        ]]);
    }

    /**
     * Create an Editor role
     * @param bool $force Create editor role even if it exist
     * @return bool
     */
    public static function createEditorRole($force = false){
        if(!$force){
            if(Permission::exists('global', 'editor')) {
                return false;
            }
        }

        return self::createRole('Editor', true, [[
            'app' => 'global',
            'key' => 'editor'
        ]]);
    }

    /**
     * Create an Author role
     * @param bool $force Create author role even if it exist
     * @return bool
     */
    public static function createAuthorRole($force = false){
        if(!$force){
            if(Permission::exists('global', 'author')) {
                return false;
            }
        }

        return self::createRole('Author', true, [[
            'app' => 'global',
            'key' => 'author'
        ]]);
    }

    /**
     * Create an admin role
     *
     * @param string $name
     * @param bool $isDefault
     * @param array $permissions ["app" => "lorem", "key" => ipsum]
     * @return boolean
     */
    public static function createRole($name, $isDefault, $permissions = []){

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
     * Handle callback of insert, update, delete
     * */
    protected static function boot(){
        parent::boot();

        self::saving(function($userGroup){
            Event::fire('userGroup:saving', [$userGroup]);
        });

        self::saved(function($userGroup){
            Event::fire('userGroup:saved', [$userGroup]);
        });

        self::creating(function($userGroup){
            Event::fire('userGroup:creating', [$userGroup]);
        });

        self::created(function($userGroup){
            Event::fire('userGroup:created', [$userGroup]);
        });

        self::updating(function($userGroup){
            Event::fire('userGroup:updating', [$userGroup]);
        });

        self::updated(function($userGroup){
            Event::fire('userGroup:updated', [$userGroup]);
        });

        self::deleting(function($userGroup){
            Event::fire('userGroup:deleting', [$userGroup]);
        });

        self::deleted(function($userGroup){
            Event::fire('userGroup:deleted', [$userGroup]);
        });
    }

    /**
     * Destruct model instance
     */
    public function __destruct()
    {
        Event::fire('userGroup:destruct', [$this]);
    }
}
