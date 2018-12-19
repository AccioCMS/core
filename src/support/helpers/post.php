<?php

if (! function_exists('findPostByID')) {
    /**
     * Find a post by its ID
     *
     * @param  int    $postID
     * @param  string $postTypeSlug
     * @return object
     */
    function findPostByID(int $postID, string $postTypeSlug = '')
    {
        return \App\Models\Post::findByID($postID, $postTypeSlug);
    }
}

if (! function_exists('findPostBySlug')) {
    /**
     * Find a post by its slug
     *
     * @param  string $slug
     * @param  string $postTypeSlug
     * @return object
     */
    function findPostBySlug(string $slug, string $postTypeSlug = '')
    {
        return \App\Models\Post::findBySlug($slug, $postTypeSlug);
    }
}

if (! function_exists('findPost')) {
    /**
     * Find a post by its ID
     *
     * @param  int|string  $postIdentification
     * @param  string $postTypeSlug
     * @return object
     */
    function findPost($postID, string $postTypeSlug = '')
    {
        if(!$postID){
            throw new \Exception("No Post ID or Slug given!");
        }

        if(is_int($postIdentification)){
            return findPostByID($postIdentification, $postTypeSlug);
        }else{
            return findBySlug($postIdentification, $postTypeSlug);
        }
    }
}


if(!function_exists('homepage')) {

    /**
     * Get the data of Homepage
     *
     * @param string $columnName Column of page to be returned
     *
     * @return array|null Returns the data of the primary Menu if found, null instead
     */
    function homepage($columnName = '')
    {
        return \App\Models\Post::getHomepage($columnName);
    }
}