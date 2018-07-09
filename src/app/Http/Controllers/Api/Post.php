<?php
/**
 * Created by PhpStorm.
 * User: sopa
 * Date: 05/07/2018
 * Time: 12:13 AM
 */

namespace Accio\App\Http\Controllers\Api;


class Post extends MainController
{

    /**
     * Get data for "you may like "component.
     * Accepts query parameters: limit, width, height
     *
     * @param $postID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Support\Collection
     * @throws \Exception
     */
    public function related($postID){
        $post = \App\Models\Post::findByID($postID);
        if(!$post){
            return $this->error('Post not found!');
        }

        // get related
        $limitTags = (is_numeric(request('limit')) ? request('limit') : 6);
        $thumbWidth = (is_numeric(request('width')) ? request('width') : 200);
        $thumbHeight = (is_numeric(request('height')) ? request('height') : 200);


        // get posts by tags
        $relatedPosts = $post->getPostsByTags($limitTags);

        // get random posts if there are less posts than required limit
        if(count($relatedPosts) < $limitTags){
            if($post->hasCategory()){
                $getPosts = \App\Models\Post::cache('category_posts_'.$post->category->categoryID)
                  ->whereCache('categories_relations.categoryID',$post->category->categoryID)
                  ->getItems()
                  ->published()
                  ->orderBy('published_at','DESC');
            }else{
                $getPosts = \App\Models\Post::cache()
                  ->getItems()
                  ->whereNotIn("postID", array_keys($relatedPosts))
                  ->published()
                  ->orderBy('published_at','DESC');
            }

            if($getPosts->count() >= $limitTags) {
                $relatedPosts = $getPosts->random(($limitTags - count($relatedPosts)));
            }
        }

        $posts = [];
        foreach($relatedPosts as $key => $row){
            $posts[$row->postID]['postID'] = $row->postID;
            $posts[$row->postID]['title'] = $row->title;
            $posts[$row->postID]['href'] = $row->href;
            $posts[$row->postID]['featuredImage'] = $row->featuredImageURL($thumbWidth, $thumbHeight);
        }

        return response()->json(['data' => $posts], 200);
    }
}