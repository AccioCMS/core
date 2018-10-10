<?php

namespace Accio\App\Models;

use Accio\App\Traits\CacheTrait;
use Illuminate\Database\Eloquent\Model;

class TagRelationModel extends Model
{

    use CacheTrait;

    /**
     * The table associated with the model.
     *
     * @var string $table
     */
    protected $table = "tags_relations";

    /**
     * The primary key of the table.
     *
     * @var string $primaryKey
     */
    protected $primaryKey = "tagRelationID";
}