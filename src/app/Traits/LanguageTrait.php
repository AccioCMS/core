<?php

/**
 * Languages Library
 *
 * Language library contains helpers that helps developers to create certain features of sites by ready-to-use functions
 *
 * @author Faton Sopa <faton.sopa@manaferra.com>
 * @version 1.0
 */
namespace Accio\App\Traits;


use App\Models\Language;
use App\Models\Plugin;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\App as AppFacade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Input;
use Accio\Support\Facades\Routes;
use Mockery\Exception;


trait LanguageTrait
{

    /**
     * Stores current language data
     *
     * @var array $current
     */
    private  static $current;

    /**
     * Stores default language data.
     *
     * @var array $default
     */
    private  static $default;


    /**
     * Get data of the default language
     *
     * @param string $column Column Name of language
     * @return object|string|null
     * @throws Exception If unable to find default language data
     * */
    public static function getDefault(string $column = ''){
        if(isset(self::$default->$column)){
            return self::$default->$column;
        }
        return self::$default;
    }

    /**
     * Get information of current language.
     *
     * @param string $column Column Name of language
     * @return string|string
     * @throws Exception If unable to find current language's data
     * */
    public static function current(string $column =''){
        if(isset(self::$current->$column)){
            return self::$current->$column;
        }
        return self::$current;
    }

    /**
     * Set default language.
     * It fills out the $route property of MenuLinks
     *
     * @throws \Exception
     */
    public static function setDefault(){
        if(!self::$default){
            $languages = Language::cache()->getItems();
            if($languages) {
                $getDefault = $languages->where('isDefault',true);
//                dd($getDefault, $languages);

                if ($getDefault) {
                    self::$default = $getDefault->first();
                    self::$current = self::$default; //current language is the same as default one in this phase

                    App::setLocale(self::$default->slug);
                    Carbon::setLocale(self::$default->slug);
                    return;
                }
            }

            throw new \Exception("Default language could not be found. Please check if a default language is defined in Administration");
        }
        return;
    }


    /**
     * Set current language across all the environment
     *
     * @param string $languageSlug
     * @throws Exception if language does not exist
     */
    public static function setCurrent(string $languageSlug){
        $languageData  = self::findBySlug($languageSlug);
        if ($languageData) {
            App::setLocale($languageSlug);
            Carbon::setLocale($languageSlug);
            self::$current = $languageData;
        } else {
            self::$current = self::$default;
            \Request::route()->setParameter('lang', self::$current->slug);
        }
    }

    /**
     * Detect language form url and set it as route parameter
     *
     * @param  \Illuminate\Http\Request $request
     * @return string|null
     */
    public static function setFromURL(Request $request){
        if(!\Request::route('lang')) {
            // language may be present in url without {param} defined
            $detectLanguageFromRequest = \App\Models\Language::detectLanguageFromRequest($request);
            if ($detectLanguageFromRequest) {
                $request->route()->setParameter('lang', $detectLanguageFromRequest);
                return $detectLanguageFromRequest;
            }
        }
        return null;
    }

    /**
     * Print list of languages
     *
     * @param string $customView Name of a custom blade file to render the template
     * @param string $ulClass Class of ul
     *
     * @return HtmlString
     */
    public static function printLanguages($customView = '', $ulClass=''){
        return new HtmlString(view()->make(($customView ? $customView : "vendor.languages.default"), [
          'languages' => Language::cache()->getItems(),
          'ulClass' => $ulClass,

        ])->render());
    }

    /**
     * Gets current URL and gives the respective url in a given language
     * TODO: me e ndreq translate url me route te reja
     * @param string $languageSlug Slug of a language
     * @return string
     */
    public static function translateURL($languageSlug){
//        $currentRoute = \Request::route();
//        $routeName = str_replace('.default','',$currentRoute->getName());
//        route($routeName);

        return url($languageSlug);
        $domain = Request::root();
        $requestURL = Request::getRequestUri();

        //remove language
        $explodeRequest = explode('/',$requestURL);

        //get menu link in case of default language
        if(App::getLocale() == self::getDefault("slug")){
            $menuLinkToChange = (isset($explodeRequest[1]) ? $explodeRequest[1] : false);
        }else{
            $menuLinkToChange = (isset($explodeRequest[2]) ? $explodeRequest[2] : false);
        }

        $newMenuLinkSlug = '';
        if($menuLinkToChange){
            $getMenuLink = MenuLink::findBySlug($menuLinkToChange);
            if($getMenuLink){
                //add requested menu link slug
                $getNewMenuLink = MenuLink::findByID($getMenuLink['menuLinkID'],$languageSlug);
                $newMenuLinkSlug = "/".$getNewMenuLink['slug'];
            }
        }

        //remove section
        return $domain.($languageSlug !== self::getDefault("slug") ? "/".$languageSlug : "").$newMenuLinkSlug;
    }
    /**
     * Get all the labels of the current language and store them in array by directory name
     *
     * @return array Returns all labels from current language
     * */
    public static function getLabels(){
        if(!file_exists(accioPath('resources/lang/'.App::getLocale()))){
            return json_encode([]);
        }

        // Load Project translation files
        $translationFiles = File::files(accioPath('resources/lang/'.App::getLocale())); //@TODO check if it produces errors, Faton

        $labels = [];
        foreach ($translationFiles as $file) {
            $fileName = str_replace('.php','',$file->getFilename());
            $labels[$fileName] = File::getRequire($file->getPathName());
        }

        // Load plugin translations
        $labels += Plugin::getAllLabels();

        return $labels;
    }

    /**
     * @param  string $slug  The slug of language (ex. en)
     *
     * @return array|null Returns an array with language's data if found, or null if not found
     * @throws \Exception
     * */
    public static function findBySlug(string $slug){
        $langauge = \App\Models\Language::cache()->whereJson('slug->'.App::getLocale(), $slug);
        if(!$langauge->isEmpty()){
            return $langauge->getItems()->first();
        }
        return null;
    }

    /**
     *
     * @param string $slug The slug of the language (ex. en)
     *
     * @return boolean Returns true if found, false instead
     * @throws \Exception
     * */
    public static function checkBySlug(string $slug){
        if(!\App\Models\Language::cache()->where('slug', $slug)->isEmpty()){
            return true;
        }
        return false;
    }

    /**
     *
     * Filter json arrays by current language.
     * Due to the fact that language values are stored in JSON using mysql 5.7 (ex. {"en":"Name","fr":"Prénom"}), this function filters rows and returns only values of the current language
     * This function is only supposed to be used in Administration area, as it offers a wide list of options for different scenarios
     *
     * @param  array   $rows The result of the query, the rows from database
     * @param  boolean $justForInTable If it should filters columns for use in tables
     * @param  array   $filterColumns Show only certain columns
     * @param  boolean $withPagination If rows are generated from the pagination class, it gets rows from pagination's array
     *
     * @return array   Return filtered array
     * */
    public static function filterRows($rows, $withPagination = true, $justForInTable = false, $filterColumns = array()){
        $filteredList = array();
        $temporaryList = array();
        $language = App::getLocale();

        if($withPagination) {
            if(!is_array($rows)){
                $rows = $rows->toArray();
            }
            $list = $rows['data'];
        }else{
            if(is_a($rows, 'Illuminate\Support\Collection')){
                $rows = $rows->toArray();
            }
            $list = $rows;
        }

        // loop throw the list
        foreach ($list as $rowKey => $row){
            if(gettype($row) == "object" && in_array('Illuminate\Database\Eloquent\Model', class_parents($row))){
                $row = $row->getAttributes();
            }
            foreach ($row as $key => $value){
                $jsonValue = true;
                // Casts columns
                if(is_object($value)){
                    $jsonValue = $value;
                }elseif(is_string($value) ){
                    // non-casts columns
                    $jsonValue = json_decode($value);
                }

                if($justForInTable){
                    //fields like ID, postID, title and createdByUserID should always be shown in the list
                    array_push($filterColumns,'postID','belongsToID','title','createdByUserID');

                    if (in_array($key, $filterColumns)){
                        if ($jsonValue && isset($jsonValue->$language)){
                            $temporaryList[$key] = $jsonValue->$language;
                            $temporaryList["original_object_".$key] = $value;
                        }else{
                            // empty if there is no translation in the selected language
                            $temporaryList[$key] = $value;
                        }
                    }
                }else{
                    if ($jsonValue && isset($jsonValue->$language)){
                        $temporaryList[$key] = $jsonValue->$language;
                        //store original object because we need it in vue js!
                        $temporaryList["original_object_".$key] = $value;
                    }else{
                        $temporaryList[$key] = $value;
                    }
                }
            }

            $filteredList[$rowKey] = $temporaryList;
        }

        if ($withPagination) {
            $rows['data'] = $filteredList;
        }else{
            $rows = $filteredList;
        }
        return $rows;
    }

    /**
     * Translate language fields from a list of objects
     *
     * @param  object $items The rows to be translated
     * @param  string $languageSlug slug of language
     *
     * @return object Returns the list of translated items
     * */
    public static function translateList($items, $languageSlug = ''){
        if($items) {
            if(is_a($items, 'Illuminate\Database\Eloquent\Collection')) {
                $translatedItems = $items->map(function ($post) use($languageSlug) {
                    foreach ($post->getAttributes() as $key => $value) {
                        $post->$key = self::translate($value,$languageSlug);
                    }
                    return $post;
                });
            } else {
                $translatedItems = $items;
                foreach($items as $key=>$value){
                    $translatedItems->$key = self::translate($value,$languageSlug);
                }
            }

            //if pagination
            if(method_exists($items,"setCollection")){
                $collection = new Collection($translatedItems);
                return $items->setCollection($collection);
            }

            return $translatedItems;
        }
    }

    /**
     * Gets current language value
     *
     * @param  array|object|string $value The rows to be translated
     * @param  string $languageSlug   The slug of the language to be translated to. Default is taken from current menu link
     *
     * @return mixed  Returns the translated value
     * */
    public static function translate($value, $languageSlug = ''){

        if($value) {
            if (!$languageSlug) {
                $languageSlug = App::getLocale();
            }

            //is an array
            if (is_array($value)) {
                if (isset($value[$languageSlug])) {
                    return $value[$languageSlug];
                }else{
                    return $value;
                }
            }
            //is an object
            else if (is_object($value)) {
                if (isset($value->$languageSlug)) {
                    return $value->$languageSlug;
                }else{
                    return $value;
                }
            }
            //is an object written in json
            else if (is_object(json_decode($value))) {
                $valueJson = json_decode($value);
                if (isset($valueJson->$languageSlug)) {
                    return $valueJson->$languageSlug;
                }else{
                    return $valueJson;
                }
            }
            //is an array written in json
            else if (is_array(json_decode($value))) {
                $valueJson = json_decode($value);
                if (isset($valueJson[$languageSlug])) {
                    return $valueJson[$languageSlug];
                }else{
                    return $valueJson;
                }
            }
            //or return it as it was given, clean as hell :)
            else {
                return $value;
            }
        }
    }

    /**
     * Detect language slug from a request
     * @param $request Request
     * @return string|boolean
     */
    public static function detectLanguageFromRequest(Request $request){
        $splitURL = explode("/",$request->path());
        if(isset($splitURL[0])){
            // validate language
            if(self::findBySlug($splitURL[0])){
                return $splitURL[0];
            }
        }
        return false;
    }

    /**
     * Generate the URL to a Language
     *
     * @return string
     */
    public function getHrefAttribute(){
        return url($this->slug);
    }

    /**
     * Set route lang attribute to all route request
     * @param Request $request
     * @return void
     */
    public static function setLangAttribute($request){
        // language may be present in url without {param} defined
        $languageSlug= self::setFromURL($request);
        if(!$languageSlug){
            $languageSlug = App::getLocale();
        }

        //add lang parameter to every route/action request if user is accessing a language that's different than default
        if(config('project.multilanguage')) {
            // hide slug on default language in frontend
            //backend
            if (!isInAdmin()) {
                if (config('project.hideDefaultLanguageInURL') && $languageSlug ==  Language::getDefault('slug')) {
                    $languageSlug = '';
                }
            }

            URL::defaults(['lang' => $languageSlug]);
        }

        return;
    }

    /**
     * Get list of languages based on ISO 639.1 standard
     * @return object
     */
    public static function ISOlist(){
        $path = accioPath('resources/assets/json/languages.json');
        return json_decode(file_get_contents($path));
    }

    /**
     * Get a ISO language by it slug
     *
     * @param string $slug
     * @return object|null
     */
    public static function getISOBySlug($slug){
        $data = collect(self::ISOlist());
        return $data->where('slug', $slug)->first();
    }

    /**
     * Get a ISO language by it name
     *
     * @param string $name
     * @return object|null
     */
    public static function getISOByName($name){
        $data = collect(self::ISOlist());
        return $data->where('name', $name)->first();
    }
}