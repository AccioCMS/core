<?php

namespace Accio\App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleRelationsModel extends Model
{
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
