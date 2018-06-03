<?php

namespace Accio\App\Models;

use App\Models\CategoryRelation;
use App\Models\PostType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Mockery\Exception;

class CategoryRelationModel extends Model
{
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
     * Get categories relations by Post Type
     * If items are found in cache they are served from it, otherwise it gets them from database
     *
     * @param  string $postTypeSlug  Slug of post type
     * @return object|null
     * */
    public static function getFromCache($postTypeSlug){
        $findPostType = PostType::findBySlug($postTypeSlug);
        if(!$findPostType){
            throw new \Exception('No post type given');
        }

        $cacheName = 'categories_relations_'.$postTypeSlug;

        //generate cache if it doesn't exist
        if(!Cache::has($cacheName)) {
            $relations = CategoryRelation::where('belongsTo',$postTypeSlug)->get();
            Cache::forever($cacheName, $relations);

            return $relations;
        }

        // return posts of current language
        return Cache::get($cacheName);
    }


    /**
     * Handle callback of insert, update, delete
     * */
    protected static function boot(){
        parent::boot();

        self::saved(function($categoryRelation){
            self::deleteCache($categoryRelation->belongsTo);
        });

        self::deleted(function($categoryRelation){
            self::deleteCache($categoryRelation->belongsTo);
        });
    }

    /**
     * Delete post cache by categories
     *
     * @param string $postTypeSlug
     */
    public static function deleteCache($postTypeSlug){
        Cache::forget('categories_relations_'.$postTypeSlug);
    }
}
