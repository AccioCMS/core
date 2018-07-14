<?php

namespace Accio\App\Traits;

use App\Models\MenuLink;
use App\Models\PostType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use \App\Models\Theme;
use Illuminate\Support\Facades\Request;

trait PostTypeTrait{
    /**
     * Check if a post type has posts
     *
     * @param  string  $postTypeSlug  The name of the post type. ex. service
     * @return boolean Returns true if there is any post
     */
    public static function hasPosts($postTypeSlug){
        $getPostType = self::findBySlug($postTypeSlug);
        if($getPostType){
            if(DB::table($getPostType['slug'])->count() > 0){
                return true;
            }
        }
        return false;
    }

    /**
     * Get post type by slug
     *
     * @param  string $postTypeSlug  The slug of Post Type
     *
     * @return object Returns requested post type if found, null instead
     * */
    public static function findBySlug($postTypeSlug){
        if(self::cache()->getItems()) {
            $postTypeSlug = 'post_' . cleanPostTypeSlug($postTypeSlug);
            $postTypes = self::cache()->getItems();

            if(!$postTypes){
                return;
            }
            $getPosType = $postTypes->where('slug', $postTypeSlug);

            if ($getPosType) {
                return $getPosType->first();
            }
        }
        return;
    }

    /**
     * @param integer $postTypeID ID of the post type
     * @return bool is this post type being used in menu links
     */
    public static function isInMenuLinks($postTypeID){
        if(MenuLink::cache()->getItems()) {
            $menuLinks = MenuLink::cache()->getItems()->getItems()->where('belongsToID', $postTypeID)->where('belongsTo', 'post_type');
            if ($menuLinks->count()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get post type by ID
     *
     * @param  string $postTypeID  ID of Post Type
     *
     * @return object Returns requested post type if found, null instead
     * */
    public static function findByID($postTypeID){
        if(\App\Models\PostType::cache()) {
            $getPosType = \App\Models\PostType::cache()->getItems()->where('postTypeID', $postTypeID);
            if ($getPosType) {
                return $getPosType->first();
            }
        }
        return;
    }

    /**
     * Get current post type slug
     *
     * @param bool $removePrefix It revmoes "post_" from slug
     * @return \Illuminate\Config\Repository|mixed
     */
    public static function getSlug($removePrefix = false){
        // get it from route
        $postTypeSlug = \Request::route('postTypeSlug');

        if(!$postTypeSlug){

            // get it from url
            $url = explode('/',Request::route()->uri());

            if(isset($url[0]) && $url[0]){
                $postType = PostType::findBySlug($url[0]);
                if($postType){
                    $postTypeSlug = $postType->slug;
                }
            }
            if(!$postTypeSlug) {
                // get it from default post type
                $postTypeSlug = config('project.default_post_type');
            }
        }

        if($removePrefix){
            return str_replace('post_','',$postTypeSlug);
        }

        return $postTypeSlug;
    }

    /**
     * Get fields of a post type
     * @param $post_type
     * @return array
     */
    public static function getFields($post_type){
        $postType = PostType::cache()->getItems()->where('slug', $post_type)->first();

        if($postType){
            return $postType->fields;
        }
        
        return [];
    }


    /**
     * Check if a post type has a custom controller
     * @return bool
     */
    public function hasCustomController(){
        $controllerName = ucfirst(str_replace('post_','',$this->slug));
        if(File::exists(Theme::getPath().'/controllers/'.$controllerName.'Controller.php')){
            return true;
        }
        return false;
    }

    /**
     * Get Post type's custom controller
     * @return string
     */
    public function getCustomController(){
        $controllerName = ucfirst(str_replace('post_','',$this->slug));
        return $controllerName.'Controller';
    }
}