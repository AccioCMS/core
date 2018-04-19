<?php

namespace Themes\DummyTheme\Controllers;

use App\Http\Controllers\Frontend\MainController;
use App\Models\Post;
use App\Models\PostType;
use App\Models\Tag;
use App\Models\Theme;
use Datetime;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TagController extends MainController{

    public function single(){
        $tag = Tag::findBySlug(request('tagSlug'));
        if(!$tag){
            return error404();
        }

         //get posts by tag
        $postsObj = new Post();
        $postsObj->setTable(PostType::getSlug());
        $posts = $postsObj->join('tags_relations','tags_relations.belongsToID',PostType::getSlug().'.postID')
            ->where('belongsTo',PostType::getSlug())
            ->published()
            ->where('tagID',$tag['tagID'])
            ->orderBy('published_at','DESC')
            ->paginate(10);

        return view(Theme::view('tags/single'),compact(['posts','tag']));
    }
}
