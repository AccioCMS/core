<?php

namespace Accio\App\Http\Controllers\Backend;

use App;
use Illuminate\Support\Facades\Auth;
use Request;
use App\Models\Language;
use App\Http\Controllers\Controller as Controller;


class BaseGeneralController extends MainController {

    public function __construct(){
        parent::__construct();
        $this->middleware('auth');
    }

    /**
     *  Dashboard
     * */
    public function index($lang = '', $view = ''){
        if($lang == ""){
            return redirect(route('backend.base.index.lang',['lang' => Language::getDefault()->slug])."?mode=cms");
        }
        return view('content', compact('lang','postTypes'));
    }

    /**
     * Route to logout user
     */
    public function logoutUser(){
        Auth::logout();
        return redirect(route('backend.auth.login'));
    }
}
