<?php

namespace Accio\App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;

class TagRelationModel extends Model
{
    use Cachable;

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

    /**
     * Disable timestamp
     *
     * @var bool
     */
    public $timestamps = false;
}