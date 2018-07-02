<?php

namespace Accio\App\Http\Controllers\Backend;

use Accio\App\Models\RoleRelationsModel;
use App;

use App\Models\Media;
use Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Accio\Support\Facades\Search;
use Validator;
use Response;
use Auth;
use Input;
use Route;
use \App\Models\UserGroup;
use App\Models\User;
use Illuminate\Http\Request;

class BaseUserController extends MainController{
    /**
     * BaseUserController constructor.
     */
    public function __construct(){
        parent::__construct();
    }

    /**
     * Return views for search component
     *
     * @params search term
     * */
    public function search($lang, $term){
        $postTypes = \App\Models\PostType::getFromCache();
        $adminPrefix = Config::get('project')['adminPrefix'];
        $isSearch = true; // used when generatin language menu (language meu that chenges the locate in backend)
        //$isPostView = true; // used when generating language menu (language meu that chenges the locate in backend)

        // check if user has permissions to access this link
        if(!User::hasAccess('User','read')){
            return view('errors.permissions', compact('lang','view','term','isSearch','isPostView','adminPrefix'));
        }

        $view = 'search';
        return view('content', compact('lang','view','term','postTypes','isSearch','isPostView', 'adminPrefix'));
    }

    /**
     * Make simple search with a search term
     * */
    public function makeSearch($term){
        // check if user has permissions to access this link
        if(!User::hasAccess('User','read')){
            return $this->noPermission();
        }

        $orderBy = (isset($_GET['order'])) ? $orderBy = $_GET['order'] : 'userID';
        $orderType = (isset($_GET['type'])) ? $orderType = $_GET['type'] : 'DESC';

        // join parameters for the query
        // we are left joinin the the media table
        $joins = array(
          [
            'table' => 'media',
            'type' => 'left',
            'whereTable1' => "profileImageID",
            'whereTable2' => "mediaID",
          ]
        );

        $excludeColumns = array('remember_token', 'created_at', 'updated_at');
        return Search::searchByTerm('users',$term, 1, true, array(), $excludeColumns, $orderBy, $orderType, $joins);
    }

    /**
     * Get the list of all users
     *
     * @param string $lang Language slug (ex. en)
     * @return array
     * */
    public function getAll($lang = ""){
        // check if user has permissions to access this link
        if(!User::hasAccess('User','read')){
            return $this->noPermission();
        }

        $orderBy = (isset($_GET['order'])) ? $orderBy = $_GET['order'] : 'userID';
        $orderType = (isset($_GET['type'])) ? $orderType = $_GET['type'] : 'DESC';

        return DB::table('users')
          ->leftJoin('media', 'users.profileImageID', '=', 'media.mediaID')
          ->orderBy($orderBy, $orderType)
          ->paginate(User::$rowsPerPage);
    }

    /**
     *  Me kthy json listen e grupeve te userave
     * */
    public function getGroups(){
        // check if user has permissions to access this link
        if(!User::hasAccess('User','read')){
            return $this->noPermission();
        }
        return UserGroup::all();
    }

    /**
     * @param Request $request
     * @return array
     */
    public function store(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('user','create')){
            return $this->noPermission();
        }
        // custom messages for validation
        $messages = array(
          'email.required'=>'Hello You cant leave Email field empty',
          'firstName.required'=>'Hello You cant leave name field empty',
        );

        // validation
        $validator = Validator::make($request->user, [
          'firstName' => 'required',
          'lastName' => 'required',
          'password' => 'required|same:confpassword',
          'email' => 'required|unique:users',
          'groups' => 'required',
        ], $messages);

        // if validation fails return json response
        if($validator->fails()){
            return $this->response("Please check all required fields!", 400, null, false, false, true, $validator->errors());
        }

        // if image is not set make it 0
        if (!isset($request->user['profileImageID']) || $request->user['profileImageID'] == ""){
            $profileImageID = null;
        }else{
            $profileImageID = $request->user['profileImageID'];
        }

        // Create user
        $user = new User();
        $user->email = $request->user['email'];
        $user->firstName = $request->user['firstName'];
        $user->lastName = $request->user['lastName'];
        $user->phone = $request->user['phone'];
        $user->street = $request->user['street'];
        $user->country = $request->user['country'];
        $user->password = Hash::make($request->user['password']);
        $user->isActive = 1;
        $user->slug = parent::generateSlug($request->user['firstName']." ".$request->user['lastName'], 'users', 'userID', '', 0, false);;
        $user->about = $request->user['about'];
        $user->profileImageID = $profileImageID;
        $user->gravatar = User::getGravatarFromEmail($request->user['email']);
        $user->createdByUserID = Auth::user()->userID;

        if ($user->save()){
            // Add roles permissions
            $user->assignRoles($request->user['groups']);

            $redirectParams = parent::redirectParams($request->redirect, 'user', $user->userID);
            $result = $this->response( 'User is created', 200, $user->userID, $redirectParams['view'], $redirectParams['redirectUrl']);
        }else{
            $result = $this->response( 'Internal server error. Please try again later', 500);
        }
        return $result;
    }

    /**
     * Store profile image
     * */
    public function storeProfileImage(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('user','update')){
            return $this->noPermission();
        }
        return $path = $request->file('profileImageID')->storeAs('images', 'filename.jpg');
    }

    /**
     * Delete a user.
     *
     * @param $lang
     * @param $id
     * @return array
     */
    public function delete($lang, $id){
        // check if user has permissions to access this link
        if(!User::hasAccess('user','delete')){
            return $this->noPermission();
        }

        $user = User::find($id);
        if($user->hasRelatedData()){
            $user->isActive = false;
            if($user->save()){
                return $this->response('User is deleted');;
            }
            return $this->response( 'Internal server error. Please try again later', 500);
        }

        // Delete all roles relations
        $roles = RoleRelationsModel::where('userID',$id);
        if($roles){
            $roles->delete();
        }

        if ($user && $user->delete()){
            $result = $this->response('User is deleted');
        }else{
            $result = $this->response( 'Internal server error. Please try again later', 500);
        }
        return $result;
    }

    /**
     *  Bulk Delete users
     *  Delete many users
     *
     *  @params array of user IDs
     * */
    public function bulkDelete(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('user','delete')){
            return $this->noPermission();
        }
        foreach ($request->all() as $id){
            $user = App\Models\User::find($id)->delete();
            if (!$user) {
                return $this->response('Internal server error. Please try again later', 500 );
            }
        }
        return $this->response( 'Users are deleted');
    }

    /**
     * Change users data
     *
     * @params $request all users data comming from the form
     * @return array
     * */
    public function storeUpdate(Request $request){
        // check if user has permissions to access this link
        if(\Illuminate\Support\Facades\Auth::user()->userID != $request->user['id']) {
            if (!User::hasAccess('user', 'update')) {
                return $this->noPermission();
            }
        }

        // Validate
        $messages = array(
          'email.required'=>'Hello You cant leave Email field empty',
          'firstName.required'=>'Hello You cant leave name field empty',
          'firstName.min'=>'Hello The field has to be :min chars long',
        );

        // validation
        $validator = Validator::make($request->user, [
          'firstName' => 'required',
          'lastName' => 'required',
          'email' => 'required',
          'groups' => 'required',
        ], $messages);

        // if validation fails return json response
        if ($validator->fails()) {
            return $this->response("Please check all required fields!", 400, null, false, false, true, $validator->errors());
        }

        // if image is not set make it 0
        if (!isset($request->user['profileImageID']) || $request->user['profileImageID'] == ""){
            $profileImageID = null;
        }else{
            $profileImageID = $request->user['profileImageID'];
        }

        // Update user
        $user = App\Models\User::findOrFail($request->user['id']);
        $user->email = $request->user['email'];
        $user->firstName = $request->user['firstName'];
        $user->lastName = $request->user['lastName'];
        $user->phone= $request->user['phone'];
        $user->street = $request->user['street'];
        $user->country = $request->user['country'];
        $user->isActive = $request->user['isActive'];
        $user->slug = parent::generateSlug($request->user['firstName']." ".$request->user['lastName'], 'users', 'userID', '', $request->user['id'], false);;
        $user->profileImageID = $profileImageID;
        $user->about = $request->user['about'];
        $user->gravatar = User::getGravatarFromEmail($request->user['email']);

        if ($user->save()){
            // Add roles permissions
            $user->assignRoles($request->user['groups']);

            $redirectParams = parent::redirectParams($request->redirect, 'user', $request->user['id']);
            $result = $this->response( 'User updated!', 200, $request->user['id'], $redirectParams['view'], $redirectParams['redirectUrl'] );
            $result['data'] = $user;
        }else{
            $result = $this->response( 'Internal server error. Please try again later', 500);
        }
        return $result;
    }

    /**
     * JSON object with details for a specific user.
     *
     * @param $lang
     * @param $id
     * @return array
     */
    public function detailsJSON($lang, $id){
        // check if user has permissions to access this link
        if(\Illuminate\Support\Facades\Auth::user()->userID != $id) {
            if (!User::hasAccess('User', 'read')) {
                return $this->noPermission();
            }
        }

        $user = App\Models\User::with('roles','profileImage')->find($id)->appendLanguageKeys();

        $final = [
          'details' => $user,
          'allGroups' => UserGroup::all()
        ];

        // Fire event
        $final['events'] = Event::fire('user:pre_update', [$final]);

        return $final;
    }

    /**
     *  return success or error
     *
     *  method to reset users password
     *  @params user ID AND new password
     * */
    public function resetPassword(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('user','update')){
            return $this->noPermission();
        }
        // validation
        $validator = Validator::make($request->all(), [
          'password' => 'required|same:confpassword',
          'id' => 'required',
        ]);

        // if validation fails return json response
        if ($validator->fails()) {
            return $this->response( "Please check all required fields!", 400,null, false, false, true, $validator->errors());
        }

        $user = User::where('userID', $request->id)->update([
          'password' => $password = Hash::make($request->password)
        ]);

        if ($user){
            $result = $this->response('Password is updated');
        }else{
            $result = $this->response('Internal server error. Please try again later', 500);
        }
        return $result;
    }

    /**
     * Get the array of fields for advanced search
     *
     * I kthen fildat qe i perdorim per search te detajum
     * @return json fildat
     *
     * */
    public function getAdvancedSearchFields($lang = ""){
        // check if user has permissions to access this link
        if(!User::hasAccess('User','read')){
            return $this->noPermission();
        }
        return User::$advancedSearchFields;
    }

    /**
     *  Get the result of advanced search
     *  I kthen rezultatet e searchit te advancum
     *
     *  @params
     *  @return json fildat
     * */
    public function getAdvancedSearchFieldsResults(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('User','read')){
            return $this->noPermission();
        }
        // join parameters for the query
        // we are left joining the the media table
        $joins = array(
          [
            'table' => 'media',
            'type' => 'left',
            'whereTable1' => "profileImageID",
            'whereTable2' => "mediaID",
          ]
        );
        return Search::advanced('users',$request, User::$rowsPerPage, $request->page, $joins);
    }

    /**
     *  When the page is refreshed in while advanced search is done
     *
     *  @params only GET prarams and paginations
     *  @return view of the advanced search
     * */
    public function searchAdvanced($lang){
        // check if user has permissions to access this link
        if(!User::hasAccess('User','read')){
            return view('errors.permissions', compact('lang','view','adminPrefix'));
        }

        $adminPrefix = Config::get('project')['adminPrefix'];

        if(isset($_GET['pagination']) && $_GET['pagination'] != ''){
            $pagination = $_GET['pagination'];
        }else{
            $pagination = 1;
        }

        $orderBy = '';
        $orderType = '';

        $request = array();
        // loop throw all get parameters
        foreach ($_GET as $key => $field){
            if($key == 'pagination'){
                continue;
            }

            if ($key == 'order'){   // get order from get request
                $orderBy = $field;
                array_push($request, ['orderBy' => $orderBy]);
            }else if ($key == 'type'){ // get order type from get request
                $orderType = $field;
                array_push($request, ['orderType' => $orderType]);
            }else{ // get search fields from get request
                $requestValues = explode(',',$field);
                $boolean = ($requestValues[3] == "null") ? '' : $requestValues[3];
                array_push($request, ['type' => ['db-column' => $requestValues[0]], 'operator' => $requestValues[1], 'value' => $requestValues[2], 'boolean' => $boolean]);
            }
        }

        // join parameters for the query
        // we are left joinin the the media table
        $joins = array(
          [
            'table' => 'media',
            'type' => 'left',
            'whereTable1' => "profileImageID",
            'whereTable2' => "mediaID",
          ]
        );
        $advancedSearchData = json_encode(Search::advanced('users',$request, User::$rowsPerPage,$pagination,$joins));
        $view = 'list'; // the view parameter used in Vuejs to tell which component should be loaded
        $hasAdvancedSearchData = true;
        $fields = json_encode($request);

        return view(User::$backendPathToView.'all', compact('lang','view','hasAdvancedSearchData','advancedSearchData','pagination', 'fields', 'adminPrefix'));
    }

    /**
     * @param Request $request
     * @return array
     */
    public function mediaStore(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('user','create')){
            return $this->noPermission();
        }
        return (new Media())->upload($request, 'users');
    }

}