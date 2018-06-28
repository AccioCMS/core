<?php

namespace Accio\App\Http\Controllers\Backend;

use App\Models\Language;
use App\Models\Media;
use App\Models\Permalink;
use App\Models\Settings;
use App\Models\Theme;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BaseSettingsController extends MainController{

    /**
     * Get all settings
     *
     * @return array settings
     */
    public function getSettings(){
        $settings = Settings::all()->keyBy('settingsKey');
        $settings['defaultLanguage'] = (object) array('value' => Language::getDefault()->languageID);

        if(isset($settings['logo'])){
            $media = Media::where('mediaID',$settings['logo']->value)->first();
            if ($media){
                $settings['logo']['media'] = $media;
            }
            $watermark = Media::where('mediaID',$settings['watermark']->value)->first();
            if ($watermark){
                $settings['watermark']['media'] = $watermark;
            }
        }
        // user groups
        $userGroups = UserGroup::all();
        // get all posts
        $posts = DB::table('post_pages')->get();
        $posts = Language::filterRows($posts, false);
        // theme configs
        $themeConfigs = $this->getThemeConfigs();

        return [
            'settings' => $settings,
            'userGroups' => $userGroups,
            'pages' => $posts,
            'themeConfigs' => $themeConfigs,
        ];
    }

    /**
     * @return array configs of all themes
     */
    public function getThemeConfigs(){
        return Theme::configs();
    }

    /**
     * Store all settings in the database
     *
     * @param Request $request
     * @return array
     */
    public function store(Request $request){
        // insert key and value in settings table
        foreach ($request->form as $key => $value){
            $settings = Settings::where('settingsKey',$key)->first();
            if(!$settings){
                $settings = new Settings();
                $settings->settingsKey = $key;
            }
            $settings->value = ($value ? $value : null);
            $settings->save();
        }

        // if request is being made from general settings
        if($request->settingsType == 'general'){

            // remove the current default language ( set it to non-default ) if this new one is the default
            Language::where('isDefault',1)->update(['isDefault' => 0]);

            // set the new default language
            $defaultLanguage = $request->form['defaultLanguage'];
            Language::find($defaultLanguage)->update(['isDefault' => 1]);
        }

        return $this->response( 'Settings are saved' , 200);
    }


    /**
     * Get list of all permalinks
     *
     * @param string $lang language slug
     * @return array all permalinks
     */
    public function getPermalinks($lang){
        return Permalink::all();
    }

    /**
     * @param Request $request
     * @return array
     */
    public function storePermalinks(Request $request){
        $data = $request->all();

        $tmp = [];
        foreach ($data as $permalinks){
            $tmp = array_merge($tmp, $permalinks);
        }

        if(Permalink::truncate()){
            if(Permalink::insert($tmp)){
                return $this->response( 'Permalinks are saved' , 200);
            }
        }

        return $this->response( 'Permalinks could not be saved. Please try again later.', 500);
    }


}
