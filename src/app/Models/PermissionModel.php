<?php
/**
 * User permissions
 *
 * Handle permissions of user groups
 *
 * @author  Jetmir Haxhisefa <jetmir.haxhisefa@manaferra.com>
 * @author  Faton Sopa <faton.sopa@manaferra.com>
 * @version 1.0
 */
namespace Accio\App\Models;

use Illuminate\Database\Eloquent\Model;
use Accio\App\Traits;

class PermissionModel extends Model
{

    use Traits\PermissionTrait;

    /**
     * Fields that can be filled in CRUD.
     *
     * @var array $fillable
     */
    protected $fillable = ['permissionID','groupID', 'app', 'key','value', 'ids','hasAll'];

    /**
     * The table associated with the model.
     *
     * @var string $table
     */
    public $table = "permissions";

    /**
     * The primary key of the table.
     *
     * @var string $primaryKey
     */
    protected $primaryKey = "permissionID";

    /**
     * The path to back-end view directory.
     *
     * @var string $backendPathToView
     */
    public static $backendPathToView = "backend.permission.";

    /**
     * Sets permission to a role (User Group).
     *
     * @param  int   $groupID
     * @param  array $permission
     * @return boolean
     */
    public function assertPermission($groupID, $permission)
    {
        $this->groupID = $groupID;
        $this->app = $permission['app'];
        $this->key = $permission['key'];
        $this->value = (isset($permission['value']) ? $permission['value'] : true);
        $this->ids = (isset($permission['ids']) ? $permission['ids'] : null);
        $this->hasAll = (isset($permission['hasAll']) ? $permission['hasAll'] : false);
        return $this->save();
    }
}
