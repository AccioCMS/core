<?php

namespace Accio\App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;

class CategoryRelationModel extends Model
{
    use Cachable;

    /**
     * The table associated with the model.
     *
     * @var string $table
     */
    protected $table = "categories_relations";

    /**
     * The primary key of the table.
     *
     * @var string $primaryKey
     */
    protected $primaryKey = "categoryRelationID";

    /**
     * Lang key that points to the multi language label in translate file.
     *
     * @var string
     */
    public static $label = "categories.relations.label";

    /**
     * Disable timestamp
     *
     * @var bool
     */
    public $timestamps = false;
}
