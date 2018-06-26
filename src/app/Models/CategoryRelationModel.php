<?php

namespace Accio\App\Models;

use Accio\App\Traits\CacheTrait;
use App\Models\CategoryRelation;
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


    /**
     * Default method to handle cache query.
     *
     * @return array
     */
    public function cache(){
        if(!isPostType($this->cacheName)){
            throw new \Exception('Post type not found in Cetegory relation\'s cache method!');
        }

        $data = CategoryRelation::where('belongsTo',$this->cacheName)->get()->toArray();
        Cache::forever('categories_relations_'.$this->cacheName,$data);
        return $data;
    }

    /**
     * Delete post cache by categories
     *
     * @param string $postTypeSlug
     */
    public static function updateCache($item, $mode){
        self::manageCacheState('categories_relations_'.$item->belongsTo, [], $item, $mode);
    }
}
