<?php

namespace Accio\App\Http\Controllers\Backend;

use App;
use Illuminate\Support\Facades\Auth;
use Request;
use App\Models\Language;
use App\Models\MenuLink;
use App\Models\Settings;
use App\Models\Plugin;
use App\Models\PostType;
use App\Http\Controllers\Controller as Controller;

class BaseGeneralController extends MainController {

    /**
     * BaseGeneralController constructor.
     */
    public function __construct(){
        parent::__construct();
    }

    /**
     *  Dashboard
     * */
    public function index($lang = '', $view = ''){
        if($lang == ""){
            return redirect(route('backend.base.index.lang',['lang' => Language::getDefault()->slug])."?mode=menu");
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

    /**
     * Get Base data for Vue start
     *
     * @return array
     */
    public function getBaseData(){
        // menu links for the application part
        $applicationMenuLinks = MenuLink::applicationMenuLinks();

        // menu links for the cms part
        $cmsMenus = MenuLink::cmsMenus();

        // user data
        $user = \Illuminate\Support\Facades\Auth::user();
        $user->avatar = $user->avatar(200,200,true);

        // Get Labels
        $labels = Language::getlabels();
        $pluginsConfigs = Plugin::configs();

        // logout link
        $logoutLink = route('backend.base.logoutUser');

        // Logo
        $projectLogo =  Settings::logo();
        if($projectLogo){
            $projectLogoURL = asset($projectLogo->thumb(200,200));
        }else{
            $projectLogoURL = asset('public/images/logo-mana.png');
        }

        $settings  =  Settings::getAllSettings();
        $settings['logo'] = $projectLogoURL;

        // User data object
        $postTypeSlugs = PostType::getFromCache()->keys();
        $globalData = [
            'post_type_slugs' => $postTypeSlugs,
            'permissions' => $user->getPermissions(),
            'settings' => $settings,
            'ini_upload_max_filesize' => ini_get('upload_max_filesize'),
            'ini_post_max_size' => ini_get('post_max_size')
        ];

        // all languages
        $languages = Language::getFromCache();

        return [
            'languages' => $languages,
            'applicationMenuLinks' => $applicationMenuLinks,
            'cmsMenus' => $cmsMenus,
            'auth' => $user,
            'labels' => $labels,
            'pluginsConfigs' => $pluginsConfigs,
            'logoutLink' => $logoutLink,
            'global_data' => $globalData,
        ];
    }

}
