<?php

namespace Accio\App\Models;

use App\Models\PostType;
use App\Models\TagRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Mockery\Exception;

class RoleRelationsModel extends Model{
    /**
     * The table associated with the model.
     *
     * @var string $table
     */
    protected $table = "roles_relations";

    /**
     * The primary key of the table.
     *
     * @var string $primaryKey
     */
    protected $primaryKey = "roleRelationID";
}
