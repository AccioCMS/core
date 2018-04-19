<?php

namespace Themes\DummyTheme\Controllers;

use App\Http\Controllers\Frontend\MainController;
use App\Models\Post;
use Accio\Support\Facades\Search;
use App\Models\Theme;
use DateTime;
use Illuminate\Support\Facades\App;

class SearchController extends MainController{

    public function index(){
        if(Search::getKeyword()) {
            $postsObj = new \App\Models\Post();
            $posts = $postsObj->setTable('post_articles')
                ->published()
                ->where('title','LIKE',"%".Search::getKeyword()."%")
                ->orderBy('published_at', 'DESC')
                ->paginate(4);

            return view(Theme::view('search/search_results'),compact('posts'));
        }else{
            return view(Theme::view('search/search_results'));
        }
    }
}
