<?php

namespace Accio\App\Http\Controllers\Backend;


use App\Models\Post;
use \Accio\App\Services\Archive;

class BaseTaskController extends MainController {

    /**
     * Used to store a new cache for the most read posts
     */
    public function mostReadArticles(){
        Post::setMostReadCache();
    }

}