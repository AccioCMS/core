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
     * Get tags from cache. Cache is generated if not found
     *
     * @param  string $postTypeSlug Name of the cache ex "post_services". Prefix "tags_" is added automatically on cache name.
     * @return object|null  Returns requested cache if found, null instead
     */
    public static function getFromCache($postTypeSlug =''){
        if(!isPostType($postTypeSlug)){
            throw new Exception('No post type given');
        }
        $cacheName = 'tags_relations_'.$postTypeSlug;

        $data = Cache::get($cacheName);
        if(!$data){
            $functionName = 'setCache_'.$cacheName;
            if(method_exists(TagRelation::class,$functionName)){
                $data = TagRelation::$functionName($cacheName);
            }else{
                $data = TagRelation::where('belongsTo',$postTypeSlug)->get()->toArray();
                Cache::forever($cacheName,$data);
            }
        }

        return self::setCacheCollection($data, TagRelation::class);
    }

}
