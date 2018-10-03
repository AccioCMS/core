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
     * Get list of all settings and corresponding data, languages for language images etc.
     *
     * @return array
     * @throws \Exception
     */
    public function getSettings(){
        $settings = Settings::cache()->getItems()->keyBy('settingsKey');

        $settings['defaultLanguage'] = (object) array('value' => Language::getDefault()->languageID);

        if(isset($settings['logo'])){
            $media = Media::where('mediaID',$settings['logo']->value)->select("mediaID", "type", "filename", "fileDirectory")->first();
            if ($media){
                $settings['logo']['media'] = $media;
            }
            $watermark = Media::where('mediaID',$settings['watermark']->value)->select("mediaID", "type", "filename", "fileDirectory")->first();
            if ($watermark){
                $settings['watermark']['media'] = $watermark;
            }
        }
        // user groups
        $userGroups = UserGroup::all();
        // get all posts
        $posts = DB::table('post_pages')->select("postID", "title", "slug")->get();
        $posts = Language::filterRows($posts, false);
        // theme configs
        $themeConfigs = Theme::configs();

        return [
            'settings' => $settings,
            'userGroups' => $userGroups,
            'pages' => $posts,
            'themeConfigs' => $themeConfigs,
        ];
    }

    /**
     * API -- Used to take theme configs
     *
     * @param $lang
     * @return array
     * @throws \Exception
     */
    public function getThemeConfigs($lang){
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
        $settings = Settings::all()->keyBy('settingsKey')->toArray();

        foreach ($request->form as $key => $value){
            if(!isset($settings[$key])){
                $settings[$key] = ["settingsKey" => $key, "value" => $value];
            }
            $settings[$key]['value'] = ($value ? $value : null);
        }

        if(Settings::truncate()){
            Settings::insert($settings);
            Settings::removeCache();
        }

        // if request is being made from general settings
        if($request->settingsType == 'general'){
            if(isset($settings['defaultLanguage']) &&
                ((int) $settings['defaultLanguage']['value'] !== Language::getDefault()->languageID)){
                    // remove the current default language ( set it to non-default ) if this new one is the default
                    Language::where('isDefault',1)->update(['isDefault' => 0]);
                    // set the new default language
                    $defaultLanguage = $request->form['defaultLanguage'];
                    Language::find($defaultLanguage)->update(['isDefault' => 1]);
            }

        }

        return $this->response( 'Settings are saved' , 200);
    }


    /**
     * Get list of all permalinks
     *
     * @param $lang
     * @return mixed
     * @throws \Exception
     */
    public function getPermalinks($lang){
        return Permalink::cache()->getItems();
    }

    /**
     * Store permalinks in database
     *
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
                Permalink::removeCache();
                return $this->response( 'Permalinks are saved' , 200);
            }
        }

        return $this->response( 'Permalinks could not be saved. Please try again later.', 500);
    }
}
