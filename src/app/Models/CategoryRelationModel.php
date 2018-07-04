<?php

namespace Accio\App\Models;

use Accio\App\Traits\CacheTrait;
use App\Models\CategoryRelation;
use App\Models\Post;
use App\Models\PostType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Mockery\Exception;

class CategoryRelationModel extends Model
{
    use CacheTrait;

    /**
     * The table associated with the model.
     *
     * @var string $table
     */
    protected $table = "categories_relations";

    /**
     * The primary key of the table
     *
     * @var string $primaryKey
     */
    protected $primaryKey = "categoryRelationID";

    /**
     * Lang key that points to the multi language label in translate file
     * @var string
     */
    public static $label = "categories.relations.label";
}
