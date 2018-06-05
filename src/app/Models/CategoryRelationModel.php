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
     * Get categories relations by Post Type
     * If items are found in cache they are served from it, otherwise it gets them from database
     *
     * @param  string $postTypeSlug  Slug of post type
     * @return Collection
     * */
    public static function getFromCache($postTypeSlug){
        if(!isPostType($postTypeSlug)){
            throw new \Exception('No post type found');
        }

        $cacheName = 'categories_relations_'.$postTypeSlug;

        $data = Cache::get($cacheName);
        if(!$data) {
            $data = CategoryRelation::where('belongsTo',$postTypeSlug)->get()->toArray();
            Cache::forever($cacheName, $data);
        }

        return self::setCacheCollection($data, self::class);
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
