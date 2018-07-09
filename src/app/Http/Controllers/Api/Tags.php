<?php
namespace Accio\App\Http\Controllers\Api;

use App\Models\Post;

class Tags extends MainController
{

    /**
     * @var int
     */
    protected $limit = 4;

    /**
     * Get posts by of a tag.
     * Accepts query parameters: limit, belongsTo
     *
     * @param string $tagsID
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function posts(string $tagsID)
    {
        $validateQuery = $this->validateQuery();
        if($validateQuery->fails()){
            return $this->error($validateQuery->errors());
        }

        // Validate post type
        $postType = getPostType(request('belongsTo'));
        if(!$postType){
            return $this->error('Post type not found');
        }

        // Validate IDs
        $tags = explode(',', $tagsID);
        foreach($tags as $tagID){
            if(!is_numeric($tagID)){
                return $this->error('Invalid Tag IDs');
            }
        }

        // Get query
        $this->queryValues();

        $postsObj = new Post();
        $postsObj->setTable($postType->slug);
        $postsObj->select('postID', 'title', 'featuredImageID');
        $posts = $postsObj
          ->select('postID', 'title', 'featuredImageID')
          ->join('tags_relations','tags_relations.belongsToID',$postType->slug.'.postID')
          ->where('belongsTo',$postType->slug)
          ->with('featuredImage')
          ->published()
          ->whereIn('tagID',$tags)
          ->orderBy('published_at','DESC')
          ->limit($this->limit)
          ->get()
          ->toArray();

        return response()->json(['data' => $posts], 200);
    }
}