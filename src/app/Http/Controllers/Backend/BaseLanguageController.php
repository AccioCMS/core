<?php

namespace Accio\App\Http\Controllers\Backend;

use App;
use App\Models\User;
use App\Models\Language;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Validator;
use Response;

use Illuminate\Http\Request;

class BaseLanguageController extends MainController{
    // Check authentification in the constructor
    public function __construct(){
        parent::__construct();
        $this->middleware('auth');
    }

    /**
     * Delete a language
     * */
    public function delete($lang, $id){
        if(!User::hasAccess('Language','delete')){
            return $this->noPermission();
        }
        $language = Language::find($id);

        if($language) {
            if ($language->isDefault) {
                return $this->response("You can't delete the default language!", 403);
            }

            if($language->delete()){
                if(is_dir(libraryPath("resources/lang/".$language->slug)))
                    File::deleteDirectory(libraryPath("resources/lang/".$language->slug));
                if(is_dir(base_path("resources/lang/".$language->slug)))
                    File::deleteDirectory(base_path("resources/lang/".$language->slug));
                return $this->response('The Language was deleted');
            }
        }

        return $this->response('The Language was not deleted. Please try again later', 500);
    }

    /**
     *  Bulk Delete languages
     *  Delete many languages
     *  @params array Language IDs
     * */
    public function bulkDelete(Request $request){
        if(!User::hasAccess('Language','delete')){
            return $this->noPermission();
        }

        // Ensure a selection has taken place
        if (count($request->all()) <= 0) {
            return $this->response( 'Please select some languages to be deleted', 500);
        }

        // loop through items
        foreach ($request->all() as $id) {
            $language = Language::find($id);

            if ($language->isDefault) {
                return $this->response( "You can't delete the default language!", 403);
            }

            if(!$language->delete()){
                return $this->response( 'The language '.$language->languageID.' was not deleted so the deleting process has stopped. Please try again later', 500);
            }
        }
        return $this->response( 'Languages are deleted');
    }

    /**
     *  Store a new language in database
     *  @param Request $request all language data comming from the form
     *  @return array
     * */
    public function store(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('Language','read')){
            return $this->noPermission();
        }
        // custom messages for validation
        $messages = array(
            'name.required'=>'Language name is required',
        );

        $data = $request->all();
        $data['name'] = (isset($data['language']) && isset($data['language']['name'])) ? $data['language']['name'] : '';
        $data['nativeName'] = (isset($data['language']) && isset($data['language']['nativeName'])) ? $data['language']['nativeName'] : '';
        $data['slug'] = (isset($data['language']) && isset($data['language']['slug'])) ? $data['language']['slug'] : '';
        unset($data['language']);

        // validation
        $validator = Validator::make($data, [
            'name' => 'required',
            'isDefault' => 'required',
            'isVisible' => 'required',
            'slug' => 'required|max:500|unique:languages',
        ], $messages);
        // if validation fails return json response
        if ($validator->fails()) {
            return $this->response( "Bad request", 400, null, false, false, true, $validator->errors());
        }

        // Make data a object
        $data = (object) $data;

        if($data->isDefault == true){
            // remove the current default language ( set it to non-default ) if this new one is the default
            Language::where('isDefault',1)->update([
                'isDefault'    => 0,
            ]);
        }

        $language = new Language();
        $language->createdByUserID  = Auth::user()->userID;
        $language->name = $data->name;
        $language->nativeName = $data->nativeName;
        $language->isDefault = $data->isDefault;
        $language->isVisible = $data->isVisible;
        $language->slug      = $data->slug;

        if($language->save()){
            // create labels
            $this->createNewLanguageLabels($language->slug);

            $redirectParams = parent::redirectParams($data->redirect, 'language', $language->languageID);
            $result = $this->response( 'Language is created', 200, $language->languageID, $redirectParams['view'], $redirectParams['redirectUrl']);
        }else{
            $result = $this->response( 'Internal server error. Please try again later', 500);
        }
        return $result;

    }

    /**
     * Copies labels from the default languages to the new language
     *
     * @param string $slug
     */
    private function createNewLanguageLabels(string $slug){
        $defaultLangPathLibrary = libraryPath("resources/lang/".Language::getDefault()->slug);
        $defaultLangPath = base_path("resources/lang/".Language::getDefault()->slug);

        if(is_dir($defaultLangPathLibrary)){
            File::copyDirectory($defaultLangPathLibrary, libraryPath("resources/lang/".$slug));
        }

        if(is_dir($defaultLangPath)){
            File::copyDirectory($defaultLangPath, base_path("resources/lang/".$slug));
        }
    }

    /**
     * @return array with details for a specific language
     * @params language ID
     * */
    public function detailsJSON($lang,$id){
        if(!User::hasAccess('Language','read')){
            return $this->noPermission();
        }

        $language = Language::find($id);

        $final = array(
            'details' => $language
        );

        // Fire event
        $final['events'] = Event::fire('language:pre_update', [$final]);

        return $final;
    }

    /**
     * Update the language data
     * @params object Request $request all language data comming from the form
     * @return array
     * */
    public function storeUpdate(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('Language','update', $request->id, true)){
            return $this->noPermission();
        }
        // custom messages for validation
        $messages = array();
        // validation
        $validator = Validator::make($request->all(), [
            'isDefault' => 'required',
            'isVisible' => 'required',
        ], $messages);

        // if validation fails return json response
        if ($validator->fails()) {
            return $this->response("Bad request", 400, null, false, false, true, $validator->errors());
        }
        if($request->isDefault == true){
            // remove the current default language ( set it to non-default ) if this new one is the default
            Language::where('isDefault',1)->update([
                'isDefault' => 0,
            ]);
        }

        // update language query
        $language = Language::findOrFail($request->id);
        $language->createdByUserID  = Auth::user()->userID;
        $language->isDefault = $request->isDefault;
        $language->isVisible = $request->isVisible;

        // Fire event
        if ($language->save()){
            $redirectParams = parent::redirectParams($request->redirect, 'language', $request->id);
            $result = $this->response( 'Language is updated', 200, $request->id, $redirectParams['view'], $redirectParams['redirectUrl']);
        }else{
            $result = $this->response( 'Internal server error. Please try again later', 500);
        }
        return $result;
    }


    public function getDefaultLanguage($lang){
        return Language::getDefault();
    }

}
