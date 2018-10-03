<?php

namespace Accio\App\Http\Controllers\Backend;

use Illuminate\Routing\Controller;
use App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Accio\Support\Facades\Search;
use App\Models\User;
use App\Models\Language;
use ImageOptimizer;

class MainController extends Controller{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct(){
        if(App::routesAreCached()) {
            $this->middleware('application');
            $this->middleware('backend');
        }
    }

    /**
     * Returns view with no ID param, like create etc.
     *
     * @param string $lang
     * @param string $view
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index($lang = "", $view = ""){
        $classNameArr = explode("\\", get_class($this));
        $className = str_replace("Controller","",$classNameArr[4]);
        // check if user has permissions to access this link
        $key = ($view == 'list' || $view == '') ? 'read' : $view;
        if(!User::hasAccess($className,$key)){
            return view('errors.permissions', compact('lang','view'));
        }
        return view('content');
    }

    /**
     * Returns the list of specific model
     *
     * @param string $lang
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function getAll($lang = ""){
        $classNameArr = explode("\\", get_class($this));
        $className = "App\\Models\\".str_replace("Controller","",$classNameArr[4]);
        $rowsPerPage = $className::$rowsPerPage;
        $class = new $className();

        $obj = DB::table($class->table);
        if(isset($_GET['order']) && isset($_GET['type'])){
            $obj = $obj->orderBy($_GET['order'], $_GET['type']);
        }
        return $obj->simplePaginate($rowsPerPage);
    }

    /**
     * Return views for a specific model LIKE update, reset password etc
     *
     * @params view and ID
     * */
    public function single($lang, $view, $id){
        $classNameArr = explode("\\", get_class($this));
        $className = str_replace("Controller","",$classNameArr[4]);
        // if we are accessing Post Type check if we are are in category or tags and repair the class name and key for them
        $key = $view;
        if($className === "PostType"){
            if(strpos($key, 'category') !== false){
                $className = 'Categories';
                // make categorylist to list, categoryupdate to update etc
                $key = str_replace('category','',$key);
            }else if(strpos($key, 'tag') !== false){
                // make taglist to list, tagupdate to update etc
                $className = 'Tags';
                $key = str_replace('tag','',$key);
            }
        }

        $key = ($key == "list" || $key == "posts") ? 'read' : $key;
        // check if user has permissions to access this link
        if($key == 'read'){
            if(!User::hasAccess($className,$key)){
                return view('errors.permissions', compact('lang','view','id'));
            }
        }else{
            if(!User::hasAccess($className,$key, $id, true)){
                return view('errors.permissions', compact('lang','view','id'));
            }
        }

        return view('content');
    }

    /**
     * Handles sort ( updates order column )
     *
     * @param Request $request
     * @return array
     */
    public function sort(Request $request){
        $classNameArr = explode("\\", get_class($this));
        $className = str_replace("Controller","",$classNameArr[4]);
        $class = "App\\".$className;

        $count = 1;
        $req = $request->all();
        unset($req['postTypes']);
        foreach($req as $key => $id){
            $id = (integer) str_replace("ID","", $id);
            $object = $class::find($id);
            $object->order = $count;
            $object->save();
            $count++;
        }
        return $this->response( 'Items are re-ordered successfully');
    }

    /**
     * Used as API to get list of all rows without using pagination
     * returns array of all rows in database
     *
     * Uses the Controller name to get his model
     * Controller name has to start with the name as the model
     *
     * Exmp. (ModelName)Controller -- UserController
     *
     * @param string $lang
     * @return mixed
     */
    public function getAllWithoutPagination($lang = ""){
        $classNameArr = explode("\\", get_class($this));
        $className = "App\\Models\\".str_replace("Controller","",$classNameArr[4]);
        $class = new $className();
        return $class->all();
    }


    /**
     * This function creates the slug for a row of a model and makes sure that -
     * slugs it is not being used from a other post
     *
     * @param $title
     * @param $tableName
     * @param $primaryKey
     * @param string $languageSlug
     * @param int $id
     * @param bool $translatable
     * @param string $delimiter
     * @return string
     */
    public function generateSlug($title, $tableName, $primaryKey, $languageSlug = '', $id = 0, $translatable = false, $delimiter = "-"){
        $count = 0;
        $found = true;
        $originalSlug = str_slug($title, $delimiter);

        while($found){
            if($count != 0){
                $slug = $originalSlug.$delimiter.$count;
            }else{
                $slug = $originalSlug;
            }

            $countObj = DB::table($tableName);
            if ($translatable){
                $countObj->where('slug->'.$languageSlug, $slug);
            }else{
                $countObj->where('slug', $slug);
            }
            if($id){
                $countObj->where($primaryKey, '!=' ,$id);
            }
            $countPosts = $countObj->count();

            if(!$countPosts){
                return $slug;
            }
            $count++;
        }
        return $originalSlug;
    }


    /**
     * Make simple search with a search term
     * Gets query params and performs where clauses
     * TODO: implement ElasticSearch
     *
     * @param $lang
     * @param $term
     * @return array
     */
    public function makeSearchParent($lang, $term){
        // get the current model of the request
        $classNameArr = explode("\\", get_class($this));
        $className = str_replace("Controller","",$classNameArr[4]);
        $class = "App\\Models\\".$className;
        $object = new $class();
        $rowsPerPage = $class::$rowsPerPage;
        $table =  $object->table;

        $orderBy    = '';
        $orderType  = '';
        $pagination = '';
        if(isset($_GET['pagination'])){
            $pagination = $_GET['pagination'];
        }
        if(isset($_GET['order'])){
            $orderBy = $_GET['order'];
        }
        if(isset($_GET['type'])){
            $orderType = $_GET['type'];
        }

        $excludeColumns = array('remember_token', 'created_at', 'updated_at');
        $results = Search::searchByTerm($table, $term, $rowsPerPage, true, array(), $excludeColumns, $pagination, $orderBy, $orderType);
        return Language::filterRows($results);
    }

    /**
     * @param string $redirectChoice what kind of redirection to make
     * @param string $belongsTo in which app to redirect
     * @param integer $id if link has a id
     * @param array $customViews (optional) if vuejs part needs custom views
     *
     * @return array redirectUrl the url where it will be redirected to, view and the view in which it will be redirected to (used in vuejs)
     * */
    public function redirectParams($redirectChoice, $belongsTo = '', $id = 0, $customViews = []){
        $adminPrefix = Config::get('project')['adminPrefix'];

        // default views
        $updateView = 'update';
        $listView = 'list';
        $createView = 'create';
        // if custom views are set overwrite the default ones
        if(count($customViews)){
            if(isset($customViews['update'])){
                $updateView = $customViews['update'];
            }
            if(isset($customViews['list'])){
                $listView = $customViews['list'];
            }
            if(isset($customViews['create'])){
                $createView = $customViews['create'];
            }
        }

        //get base path
        $splitRoot = explode('/',\Request::root());
        $basePath = (end($splitRoot) ? "/".end($splitRoot) : "");

        if($redirectChoice == 'save'){
            $redirectUrl = $basePath."/".$adminPrefix."/".App::getLocale()."/$belongsTo/$updateView/".$id;
            $view = $updateView;
        }else if($redirectChoice == 'close'){
            $redirectUrl = $basePath."/".$adminPrefix."/".App::getLocale()."/$belongsTo/$listView";
            $view = $listView;
        }else{
            $redirectUrl = $basePath."/".$adminPrefix."/".App::getLocale()."/$belongsTo/$createView";
            $view = $createView;
        }

        return array('redirectUrl' => $redirectUrl, 'view' => $view);
    }

    /**
     * Handle ajax responses (mainly used by view vue js
     *
     * @param  integer $code HTTP response code
     * @param  string  $message The message to be shown along the error
     * @param  int     $itemID The ID of the editing item
     * @param  string  $redirectToView The view to be redirected to if ajax response is successful
     * @param  string  $redirectUrl The URL to be redirected to if ajax response is successful
     * @param  boolean $returnInputErrors If true, it handle input errors
     * @param  array   $errorsList The list of errors in array, ex. ["field_name" => array("Error 1","Error 2")]
     * @param  array   $noty Custom notifications list
     * @return array
     * */
    protected  function response($message, $code = 200, $itemID = null, $redirectToView = '', $redirectUrl = '', $returnInputErrors = false, $errorsList = [], $noty = []){
        // if we have input errors in the validator
        if($returnInputErrors){
            return response()->json(array(
                    'code' =>           400,
                    'id'                => $itemID,
                    'errors'            => $errorsList,
                    'message'           => $message,
                    'redirectToView'    => $redirectToView,
                    'plugins'           => [],
                    'noty'              => $noty
                )
            );
        }
        // if there are no validation errors return normal errors or no errors if there are non
        return  [
            'code'          => $code,
            'id'            => $itemID,
            'errors'        => $errorsList,
            'message'       => $message,
            'redirectToView'=> $redirectToView,
            'redirectUrl'   => $redirectUrl,
            'plugins'       => [],
            'noty'          => $noty
        ];
    }

    /**
     * General response if user has no permission
     *
     * @return array
     */
    protected function noPermission(){
        return $this->response("You don't have permissions to perform this action", 403);
    }

}
