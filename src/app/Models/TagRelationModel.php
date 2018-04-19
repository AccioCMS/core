<?php

namespace Accio\App\Models;

use App\Models\PostType;
use App\Models\TagRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Mockery\Exception;

class TagRelationModel extends Model
{
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
     * Get tags relations by Post Type
     * If items are found in cache they are served from it, otherwise it gets them from database
     *
     * @param  string $postTypeSlug  Slug of post type
     * @return object|null
     * */
    public static function getFromCache($postTypeSlug){
        $findPostType = PostType::findBySlug($postTypeSlug);
        if(!$findPostType){
            throw new Exception('No post type given');
        }

        $cacheName = 'tags_relations_'.$postTypeSlug;

        //generate cache if it doesn't exist
        if(!Cache::has($cacheName)) {
            $relations = TagRelation::where('belongsTo',$postTypeSlug)->get();
            Cache::forever($cacheName, $relations);

            return $relations;
        }

        // return posts of current language
        return Cache::get($cacheName);
    }
}
