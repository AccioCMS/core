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
     * Insert all cache relations for the posts the are in chache
     *
     * @return array
     */
    public function cache(){
        $postTypeSlug = $this->cacheAttribute('belongsTo', $this->cacheName);
        if(!isPostType($postTypeSlug)){
            throw new \Exception('Post type \''.$postTypeSlug.'\' not found in Cetegory relation\'s cache method!');
        }

        $postIDs = Post::getFromCache($postTypeSlug)->pluck("postID")->toArray();
        $data = CategoryRelation::where('belongsTo',$postTypeSlug)->whereIn('belongsToID', $postIDs)->get()->toArray();
        Cache::forever('categories_relations_'.$postTypeSlug,$data);
        return $data;
    }

    /**
     * Delete post cache by categories
     *
     * @param string $postTypeSlug
     */
    public static function updateCache($item, $mode){
        self::manageCacheState('categories_relations_'.$item->belongsTo, ['belongsTo' => $item->belongsTo], $item, $mode);
    }
}
