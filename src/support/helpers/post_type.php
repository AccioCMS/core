<?php

if(!function_exists('cleanPostTypeSlug')) {
    /**
     * Remove 'post_' from post type slug
     *
     * @param  $postTypeSlug
     * @param  string       $replaceWith
     * @return mixed
     */
    function cleanPostTypeSlug($postTypeSlug, $replaceWith = '')
    {
        return str_replace('post_', $replaceWith, $postTypeSlug);
    }
}

if (! function_exists('getPostType')) {
    /**
     * Get post type object
     *
     * @param  mixed $postTypeSlug without the post_ prefix
     * @return mixed
     */
    function getPostType($postTypeSlug = '')
    {
        if(!$postTypeSlug) {
            $postTypeSlug = config('project.default_post_type');
        }

        if(is_int($postTypeSlug)){
            $postType  = \App\Models\PostType::findByID($postTypeSlug);
        }else{
            $postType  = \App\Models\PostType::findBySlug($postTypeSlug);
        }

        if($postType) {
            return $postType;
        }
        return null;
    }
}


if (! function_exists('isPostType')) {
    /**
     * Get post type object
     *
     * @param  string $postType without the post_ prefix
     * @return mixed
     */
    function isPostType($postTypeSlug)
    {
        if(getPostType($postTypeSlug) !== null){
            return true;
        }
        return false;
    }
}