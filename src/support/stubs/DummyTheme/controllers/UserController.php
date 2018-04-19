<?php

namespace Themes\DummyTheme\Controllers;

use App\Http\Controllers\Frontend\MainController;
use App\Models\Post;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Datetime;

class UserController extends MainController{

    public function single(){
        $author = User::findBySlug(\Request::route('authorSlug'));

        if(!$author){
            return error404();
        }
        //get posts by author
        $postsObj = new Post();
        $postsObj->setTable('post_articles');
        $posts = $postsObj
            ->published()
            ->with('featuredImage')
            ->where('createdByUserID', $author['userID'])
            ->orderBy('published_at','DESC')
            ->paginate(10);

        return view(Theme::view('user/single'),compact(['author','posts']));
    }

}
