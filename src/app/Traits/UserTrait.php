<?php

/**
 * User Library
 *
 * Methods to facilitate getting user's information and writing user html template parts
 *
 * @author Faton Sopa <faton.sopa@manaferra.com>
 * @version 1.0
 */
namespace Accio\App\Traits;

use App\Models\Language;
use App\Models\Permission;
use App\Models\PostType;
use App\Models\RoleRelation;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait UserTrait{

    /**
     * $permissions Stores permissions of the user that is logged in admin panel.
     * 
     * @var array
     */
    protected  static $permissions = [];

    /**
     * Checks if user has ownership in a particular post.
     *
     * @param  string  $app The name of the app. Ex: "post_service"
     * @param  integer $ownershipPostID The ID of the post we are checking for ownership
     * @return boolean
     * */
    public static function hasOwnership($app, $ownershipPostID){
        if($ownershipPostID){
            if(isPostType($app)){
                $checkDB = DB::table($app)
                  ->where('postID',$ownershipPostID)
                  ->where('createdByUserID',Auth::user()->userID)
                  ->count();
            }else{
                $class = 'App\\Models\\'.$app;
                $object = new $class();
                $checkDB = DB::table($object->table)
                  ->where($object->getKeyName(),$ownershipPostID)
                  ->where('createdByUserID',Auth::user()->userID)
                  ->count();
            }


            return ($checkDB) ? true : false;
        }
    }

    /**
     *  Checks if user has access in a particular permission.
     *
     *  @param  string  $app  The name of the app. Ex: MenuLinks
     *  @param  string  $key The key identifier of the ownership
     *  @param  integer $id The ID of the post we are checking for ownership
     *  @param  boolean $checkOwnership If the user is an author, should the post be checked for ownership?
     *
     *  @return boolean Returns true if the user has access in a particular requested permission
     * */
    public static function hasAccess($app, $key, $id = 0, $checkOwnership = false){
        //if admin
        if(self::isAdmin()){
            return true;
        }

        //if the user is a default author or editor, it has rights in publish posts
        $appPermission = false;
        $hasSinglePermission = false;

        if(self::isDefaultGroup()){
            $userSelfDataAccess = ($app == 'User' && $id == Auth::user()->userID);
            $isPostType = isPostType($app);

            if (self::isEditor()) {
                $allowedApps = array('Pages', 'Category', 'Tags', 'Media');
                $isAllowedApp = (
                in_array($app, $allowedApps)
                );

                //if app is an allowed app or is a post type, pass the appPermission check
                if(
                  (
                    $isAllowedApp
                    || $isPostType
                  )

                  //is a language
                  || ($app == "Language" && $key == "id" && $id)

                  // User can manage his own data
                  || $userSelfDataAccess
                ){
                    $appPermission = true;
                    $hasSinglePermission = true;
                }
            }elseif(self::isAuthor()) {
                $allowedApps = array('Media');
                $isAllowedApp = (
                in_array($app, $allowedApps)
                );

                //if app is an allowed ap or is a post type, pass the appPermission check
                if(
                  $isAllowedApp
                  || $isPostType

                  //authors have read access into all Categories, Tags and Languages
                  || (
                    in_array($app, array("Category", "Tags", "Language"))

                    &&

                    (
                        //is a specific id (used for listing in dropdown in create)
                      ($key == "id" && $id || $id === 'hasAll')
                      //has only read access
                      || in_array($key,array("read"))
                    )
                  )
                  // User can manage his own data
                  || $userSelfDataAccess
                ) {
                    $appPermission = true;
                    $hasSinglePermission = true;
                }
            }
        }else{
        // if the user does  belong to a default group and it has permissions in a default allowed app, check access of this particular permission
            if(isset(self::$permissions[$app]) && isset(self::$permissions[$app][$key])){
                //check if the user has access into all items
                if($id === 'hasAll'){
                    if(isset(self::$permissions[$app][$key]['hasAll']) && self::$permissions[$app][$key]['hasAll']){
                        $hasSinglePermission = true;
                    }
                }
                //or in a specific item
                else if($id){
                    if(is_array(self::$permissions[$app][$key]['value'])){
                        if (in_array($id,self::$permissions[$app][$key]['value'])){
                            $hasSinglePermission = true;
                        }
                    }else{
                        $hasSinglePermission = true;
                    }
                }else{
                    //if id is not given, it means we already have access into this permission
                    $hasSinglePermission = true;
                }
            }
        }

        //check author
        if(self::isAuthor()){
            // Check ownership
            //@TODO me e keqyre pse eshte e nevojshme me e ba check a eshte app-i post type perderisa ne hasOwnership e bajme query edhe ne app-a tjere
            if($checkOwnership && is_numeric($id)){
                $hasOwnership = self::hasOwnership($app,$id);
            }else{
                $hasOwnership = true;
            }
            // don't give te user access if it has not permissions in a default allowed app and it a particular permission
            if(!$appPermission && !$hasSinglePermission){
                return false;
            }
            //give the user access if it has ownership of that post and if it has access in a particular permissions
            if($hasSinglePermission && $hasOwnership){
                return true;
            }
        }else if(self::isDefaultGroup() && self::isEditor()){
            //check editor
            if($appPermission || $hasSinglePermission){
                return true;
            }
        }else if ($hasSinglePermission){
            //check specific permission
            return true;
        }
        return false;
    }

    /**
     * Checks if the user is an Administrator.
     *
     * @return boolean Returns true if the user is admin
     * */
    public static function isAdmin(){
        return (isset(self::$permissions['global']['admin']) ? self::$permissions['global']['admin'] : false);
    }

    /**
     * Checks if the user is an Editor.
     *
     * @return boolean Returns true if the user is editor
     * */
    public static  function isEditor(){
        return (isset(self::$permissions['global']['editor']) ? self::$permissions['global']['editor'] : false);
    }

    /**
     * Checks if the user is an Author.
     *
     * @return boolean Returns true if the user is author
     * */
    public static function isAuthor(){
        return (isset(self::$permissions['global']['author']) ? self::$permissions['global']['author'] : false);
    }

    /**
     * Get permissions of the user.
     *
     * @return array
     */
    public function getPermissions(){
        // return permissions if they have already been requested
        if(Session::get("usersPermission")) {
            self::$permissions = Session::get("usersPermission");
            return Session::get("usersPermission");
        }
        // return permissions if they have already been requested
        if(self::$permissions) {
            return self::$permissions;
        }

        $groupIDs = [];
        foreach (Auth::user()->roles as $group) {
            if ($group->isDefault) {
                self::$permissions["global"]["isDefault"] = true;
            }
            $groupIDs[] = $group->groupID;
        }

        $permissions = Permission::whereIn("groupID", $groupIDs)->get();

        // save values in keys & values for easy access
        foreach ($permissions as $permission) {
            self::$permissions[$permission->app][$permission->key] = [
              'value' => ($permission->ids !== NULL) ? array_values(json_decode($permission->ids, true)) : $permission->value,
              'hasAll' => $permission['hasAll']
            ];
        }
        Session::put("usersPermission", self::$permissions);
        return self::$permissions;
    }

    /**
     * Get a particular permission.
     *
     * @param int $userID Default: Authenticated User
     * @return mixed
     * */
    public static function getPermission($app, $key, $userID = 0){
        //TODO $userID me mujt me i marr permissions e nje useri specifik
        return (isset(self::$permissions[$app][$key]) ? self::$permissions[$app][$key] : false);
    }

    /**
     * Checks if a user belongs to one of default roles.
     *
     * @param int $userID Default: Authenticated User
     * @return boolean Returns true if the group is a default group
     * */
    public static function isDefaultGroup($userID = 0){
        //TODO $userID me mujt me i marr permissions e nje useri specifik
        return (isset(self::$permissions['global']['isDefault']) ? self::$permissions['global']['isDefault'] : false);
    }

    /**
     * Get full avatar url.
     *
     * @param int $width Width of image. ex: 200
     * @param int $height Height of image. ex: 200
     * @param boolean $returnGravatarIfNotFound Return gravatar if not avatar is found
     *
     * @return string|null
     */
    public function avatar($width = null, $height = null,  $returnGravatarIfNotFound = false){
        if($this->profileimage) {
            if(!$width && !$height){
                return url($this->profileimage->url);
            }else{
                return $this->profileimage->thumb($width,$height, $this->profileimage);
            }
        }

        if($returnGravatarIfNotFound && $this->gravatar){
            return $this->gravatar;
        }

        return;
    }

    /**
     * Print Avatar image.
     *
     * @param int $width Width of image. ex: 200
     * @param int $height Height of image. ex: 200
     * @param boolean $returnGravatarIfNotFound Return gravatar if not avatar is found
     *
     * @return string|null
     */
    public function avatarImage($width = null, $height = null, $returnGravatarIfNotFound = false){
        return new HtmlString(view()->make("vendor.user.avatar", [
            'width'=>$width,
            'height'=>$height,
            'returnGravatarIfNotFound'=>$returnGravatarIfNotFound,
            'imageURL'=>$this->avatar($width, $height,$returnGravatarIfNotFound),
            'user'=>$this
        ])->render());
    }

    /**
     * Print Gravatar image.
     *
     * @param int $width Width of image. ex: 200
     * @param int $height Height of image. ex: 200
     *
     * @return string|null
     */
    public function gavatarImage($width = null, $height = null){
        return new HtmlString(view()->make("vendor.user.avatar", [
            'width' => $width,
            'height' => $height,
            'returnGravatarIfNotFound'=>true,
            'imageURL'=> asset($this->gravatar),
            'user' => $this
        ])->render());
    }

    /**
     * Get user by Slug (Name-Surname).
     *
     * @param $slug
     * @param string $columnName
     * @return mixed
     * @throws \Exception
     */

    public static function findBySlug($slug, $columnName = ''){
        $userObj = User::where('slug', $slug)->first();

        // return custom column
        if ($columnName && isset($userObj->$columnName)) {
            return $userObj->$columnName;
        }

        return $userObj;
    }

    /**
     * Get user by ID.
     *
     * @param $userID
     * @param string $columnName
     * @return mixed
     * @throws \Exception
     */
    public static function findByID($userID, $columnName = ''){
        $userObj = User::where('userID', $userID)->first();

        // return custom column
        if ($columnName && isset($userObj->$columnName)) {
            return $userObj->$columnName;
        }

        return $userObj;
    }

    /**
     * Get user's avatar from gravatar.com by using email address.
     *
     * @param  string $email The email address of the user we want to get the avatar
     * @param  int $width The width of image
     *
     * @return string The Gravatar image url of the given email
     * */
    public static function getGravatarFromEmail($email ,$width = 80){
        $gravatarURL = "https://gravatar.com/avatar/".md5( strtolower( trim( $email ) ) ).($width ? '?s='.$width : "");

        return $gravatarURL;
    }

    /**
     * Returns if a logged in user is active or no.
     */
    public static function isActive($guard = "admin"){
        if (Auth::guard($guard)->check()) {
           return Auth::user()->isActive;
        }
        return false;
    }
    /**
     * Get the admin group.
     *
     * @return mixed
     * @throws \Exception
     */
    public function getAdminGroup(){
        $find = UserGroup::where('slug','admin')->first();
        if(!$find){
            throw new \Exception("No admin group found");
        }

        return $find;
    }

    /**
     * Get an admin.
     *
     * @return object
     * @throws \Exception
     */
    public static function getAnAdmin(){
        $permission = new Permission();
        $getPermission = $permission->where('app','global')->where('key', 'admin')->where('value', true)->get()->first();

        if($getPermission){
            $adminRelations  = RoleRelation::where('groupID', $getPermission->groupID)->first();
            if($adminRelations){
                return User::find($adminRelations->userID);
            }
        }

        throw new \Exception("No admin user found.");
    }

    /**
     * Get an editor.
     *
     * @return object
     * @throws \Exception
     */
    public static function getAnEditor(){
        $permission = new Permission();
        $getPermission = $permission->where('app','global')->where('key', 'editor')->where('value', true)->get()->first();

        if($getPermission){
            $adminRelations  = RoleRelation::where('groupID', $getPermission->groupID)->first();
            if($adminRelations){
                return User::find($adminRelations->userID);
            }
        }

        throw new \Exception("No admin user found.");
    }

    /**
     * Get an editor.
     *
     * @return object
     * @throws \Exception
     */
    public static function getAnAuthor(){
        $permission = new Permission();
        $getPermission = $permission->where('app','global')->where('key', 'author')->where('value', true)->get()->first();

        if($getPermission){
            $adminRelations  = RoleRelation::where('groupID', $getPermission->groupID)->first();
            if($adminRelations){
                return User::find($adminRelations->userID);
            }
        }

        throw new \Exception("No admin user found.");
    }

    /**
     * Assign roles to a user.
     *
     * @param array|int $groups groups that are selected in frontend for the new or existing user
     * @param boolean Bypass permission check. Useful when creating users via CLI.
     * @return bool
     * */
    public function assignRoles($groups, $bypassPermissionCheck = false){
        // allow permission bypass only in the local environment
        if(config('app.env') == 'production' && $bypassPermissionCheck){
            $bypassPermissionCheck = false;
        }

        // check if a user has permissions to access this link
        if(!$bypassPermissionCheck) {
            if (!self::hasAccess('user', 'update') && !self::hasAccess('user', 'create')) {
                return false;
            }
        }

        // First delete all previous relations
        RoleRelation::where('userID',$this->userID)->delete();

        $roles = [];

        // in case int is given
        if(is_numeric($groups)){
            $groups = [$groups];
        }

        foreach ($groups as $groupID){
            $roles[] = [
              'userID' => $this->userID,
              'groupID' => (isset($groupID['groupID']) ? $groupID['groupID'] : $groupID)
            ];
        }

        if(RoleRelation::insert($roles)){
            return true;
        }
        return false;
    }

    /**
     * Return if users ID is beeing used somewhere in the database.
     *
     * @return bool
     * @throws \Exception
     */
    public function hasRelatedData(){
        if(!$this->hasDataInDefaultApps() && !$this->hasDataInPostsType()){
            return false;
        }
        return true;
    }

    /**
     * Check if user has a data created by him in default apps.
     * If of the default apps "post_type", "categories", "tags", "languages", "media" has the createdByUsID associated with this user.
     *
     * @return bool
     */
    public function hasDataInDefaultApps(){
        $tables = ["post_type", "categories", "tags", "languages", "media"];

        foreach($tables as $table){
            $hasData = DB::table($table)->where("createdByUserID", $this->userID)->count();
            if($hasData){
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the current user has any post or is related to any post (in post fields "dropdown from db").
     *
     * @return bool
     * @throws \Exception
     */
    public function hasDataInPostsType(){
        $postTypes = PostType::all();
        foreach($postTypes as $postType){
            $hasData = DB::table($postType->slug)->where("createdByUserID", $this->userID)->count();
            if($hasData){
                return true;
            }

            foreach($postType->fields as $field){
                if($field->type->inputType == "db" && $field->dbTable->name == "users"){
                    if($field->translatable){
                        if($field->isMultiple){
                            foreach(Language::all() as $language){
                                $hasData = DB::table("post_articles")->whereRaw("JSON_CONTAINS($field->slug->\"$.$language->slug\", '[$this->userID]')")->count();
                                if($hasData) return true;
                            }
                        }else{
                            foreach(Language::all() as $language){
                                $hasData = DB::table($postType->slug)->where($field->slug."->".$language->slug, $this->userID)->count();
                                if($hasData) return true;
                            }
                        }
                    }else{
                        if($field->isMultiple){
                            $hasData = DB::table($postType->slug)->whereRaw("JSON_CONTAINS($field->slug, '[$this->userID]')")->count();
                            if($hasData) return true;
                        }else{
                            $hasData = DB::table($postType->slug)->where($field->slug, $this->userID)->count();
                            if($hasData) return true;
                        }
                    }
                }

            }

        }
        return false;
    }
}