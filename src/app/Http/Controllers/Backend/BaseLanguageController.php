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
    public function __construct(){
        parent::__construct();
    }

    /**
     * Returns language list from cache.
     * Overwrites getAll method form MainController.
     *
     * @param string $lang
     * @return array|\Illuminate\Contracts\Pagination\Paginator
     * @throws \Exception
     */
    public function getAll($lang = ""){
        if(!User::hasAccess('Language','read')){
            return $this->noPermission();
        }
        return ['data' => Language::cache()->getItems()];
    }

    /**
     * Used te delete language label directories.
     *
     * @param $slug
     */
    private function deleteDirectory($slug){
        if(is_dir(accioPath("resources/lang/".$slug)))
            File::deleteDirectory(accioPath("resources/lang/".$slug));
        if(is_dir(base_path("resources/lang/".$slug)))
            File::deleteDirectory(base_path("resources/lang/".$slug));
    }

    /**
     * Delete language.
     * Removes language directory used for labels.
     *
     * @param string $lang
     * @param int $id
     * @return array
     */
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
                $this->deleteDirectory($language->slug);
                return $this->response('The Language was deleted');
            }
        }

        return $this->response('The Language was not deleted. Please try again later', 500);
    }

    /**
     * Bulk Delete languages.
     *
     * Delete many languages
     * @params array Language IDs
     * */
    public function bulkDelete(Request $request){
        if(!User::hasAccess('Language','delete')){
            return $this->noPermission();
        }

        // Ensure a selection has taken place
        if (count($request->all()) <= 0) {
            return $this->response( 'Please select some languages to be deleted', 500);
        }

        // loop through language IDs
        foreach ($request->all() as $id) {
            $language = Language::find($id);

            if ($language->isDefault) {
                return $this->response( "You can't delete the default language!", 403);
            }

            if(!$language->delete()){
                return $this->response( 'The language '.$language->languageID.' was not deleted so the deleting process has stopped. Please try again later', 500);
            }else{
                $this->deleteDirectory($language->slug);
            }
        }
        return $this->response( 'Languages are deleted');
    }

    /**
     * Save (Create or Update) language in database.
     * If the language is set to be default, the previous one is updated and set to non-default.
     *
     * @param Request $request all language data
     * @return array response
     * */
    public function store(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('Language',(isset($request->id)) ? 'update' : 'create')){
            return $this->noPermission();
        }
        // custom messages for validation
        $messages = array(
            'name.required'=>'Language name is required',
        );

        $validatorValues = [
            'name' => 'required',
            'isDefault' => 'required',
            'isVisible' => 'required',
            'slug' => 'required|max:500|unique:languages',
        ];
        if(isset($request->id))
            unset($validatorValues['slug']);

        // validation
        $validator = Validator::make($request->all(), $validatorValues , $messages);
        // if validation fails return json response
        if ($validator->fails()) {
            return $this->response( "Please check all required fields!", 400, null, false, false, true, $validator->errors());
        }

        if($request->isDefault == true){
            // remove the current default language ( set it to non-default ) if this new one is the default
            Language::where('isDefault',1)->update([
                'isDefault'    => 0,
            ]);
        }

        // createdByUserID, slug and nativeName don't need to be updated
        if(isset($request->id)){
            $language = Language::findOrFail($request->id);
        }else{
            $language = new Language();
            $language->createdByUserID = Auth::user()->userID;
            $language->slug = $request->slug;
            $language->nativeName = $request->nativeName;
        }
        $language->name = $request->name;
        $language->isDefault = $request->isDefault;
        $language->isVisible = $request->isVisible;

        if($language->save()){
            // create labels
            if(!isset($request->id))
                $this->createNewLanguageLabels($language->slug);

            $redirectParams = parent::redirectParams($request->redirect, 'language', $language->languageID);
            $result = $this->response( 'Language is stored', 200, $language->languageID, $redirectParams['view'], $redirectParams['redirectUrl']);
        }else{
            $result = $this->response( 'Language could not be stored. Internal server error. Please try again later', 500);
        }
        return $result;

    }

    /**
     * Copies labels from the default languages to the new language.
     * So creates labels for new language.
     *
     * @param string $slug
     */
    private function createNewLanguageLabels(string $slug){
        $defaultLangPathLibrary = accioPath("resources/lang/".Language::getDefault()->slug);
        $defaultLangPath = base_path("resources/lang/".Language::getDefault()->slug);

        if(is_dir($defaultLangPathLibrary)){
            File::copyDirectory($defaultLangPathLibrary, accioPath("resources/lang/".$slug));
        }

        if(is_dir($defaultLangPath)){
            File::copyDirectory($defaultLangPath, base_path("resources/lang/".$slug));
        }
    }

    /**
     * Returns all data of a single language (used in update form).
     *
     * @return array with details for a specific language
     * @params language ID
     * */
    public function detailsJSON($lang, $id){
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

}
