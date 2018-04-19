<?php

namespace Themes\DummyTheme\Controllers;

use App\Http\Controllers\Frontend\MainController;
use App\Models\Post;
use App\Models\Theme;

class PagesController extends MainController {

    /**
     * Define Route names that can be chosen as template from MenuLink
     */
    protected static function menuLinkRoutes(){
        $routes = [
            'defaultRoute' => 'post.pages.single',
            'list' => [
                'post.pages.single' => 'Single Post Page'
            ]
        ];

        return $routes;
    }

    /**
     * The default home method
     * Used to load the main path "www.mydomain.com"
     * Change the return view() if you want to change the template file
     * */
    public function homepage(){
        return view(Theme::view('pages/home'));
    }


    /**
     * This function is usually used to load a page
     * */
    public function single(){
        $post = Post::findBySlug(\Request::route('postSlug'), "post_pages");
        if(!$post){
            return error404();
        }

        return view(Theme::view('pages/single'),compact('post'));
    }
}
