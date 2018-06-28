<?php

namespace Accio\App\Models;

use Accio\App\Traits\CacheTrait;
use App\Models\PostType;
use App\Models\TagRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Mockery\Exception;

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
     * The primary key of the table
     *
     * @var string $primaryKey
     */
    protected $primaryKey = "tagRelationID";

    /**
     * Default method to handle cache query.
     *
     * @return array
     */
    public function cache(){
        $postTypeSlug = $this->cacheAttribute('belongsTo', $this->cacheName);
        if(!isPostType($postTypeSlug)){
            throw new \Exception('Post type \''.$postTypeSlug.'\' not found in Cetegory relation\'s cache method!');
        }

        $data = TagRelation::where('belongsTo',$postTypeSlug)->get()->toArray();
        Cache::forever('tags_relations_'.$postTypeSlug,$data);
        return $data;
    }


    /**
     * Delete post cache by categories
     *
     * @param string $postTypeSlug
     */
    public static function updateCache($item, $mode){
        self::manageCacheState('tags_relations_'.$item->belongsTo, ['belongsTo' => $item->belongsTo], $item, $mode);
    }

}