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

        // When there are not enough related post, we need to fill the space with latest posts from category
        if(count($relatedPosts) < $limitTags){
            if($post->hasCategory()){
                $latestPosts = \App\Models\Post::cache('category_posts_'.$post->category->categoryID)
                  ->whereCache('categories_relations.categoryID',$post->category->categoryID)
                  ->published()
                  ->orderBy('published_at','DESC')
                  ->getItems();
            }else{
                $latestPosts = \App\Models\Post::cache()

                  ->whereNotIn("postID", array_keys($relatedPosts))
                  ->published()
                  ->orderBy('published_at','DESC')
                  ->getItems();
            }

            if($latestPosts->count() >= $limitTags) {
                $randomPosts = $latestPosts->random(($limitTags - count($relatedPosts)));
                foreach($randomPosts as $post){
                    $relatedPosts->push($post);
                }
            }
        }


        $posts = [];
        $l = 0;
        foreach($relatedPosts as $key => $row){
            $posts[$l]['postID'] = $row->postID;
            $posts[$l]['title'] = $row->title;
            $posts[$l]['href'] = $row->href;
            $posts[$l]['featuredImage'] = $row->featuredImageURL($thumbWidth, $thumbHeight);
            $l++;
        }

        return response()->json(['data' => $posts], 200);
    }
}