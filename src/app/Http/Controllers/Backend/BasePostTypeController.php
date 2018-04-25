<?php

namespace Accio\App\Http\Controllers\Backend;

use App;
use App\Models\Language;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Validator;
use Accio\Support\Facades\Pagination;
use App\Models\PostType;
use App\Models\Post;

use Illuminate\Http\Request;

class BasePostTypeController extends MainController{
    // request in post to be able to use it in inner functions
    public $req;

    public function __construct(){
        parent::__construct();
        $this->middleware('auth');
    }

    /**
     * Gets post type with relations
     *
     * @param string $lang
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll($lang = ""){
        return PostType::with('categories')->paginate(Post::$rowsPerPage);
    }

    /**
     * Get all post types
     * @param string $lang
     * @return object
     * */
    public function menuPanelItems($lang = ""){
        // check if user has permissions to access this link
        if(!User::hasAccess('PostType','read')){
            return $this->noPermission();
        }

        // Find post types
        if(Input::input('keyword')){
            $postTypes = PostType::where('name', 'like', '%test%')->paginate(PostType::$rowsPerPage);
        }else{
            $postTypes = PostType::where("slug", '!=', "post_pages")->paginate(PostType::$rowsPerPage);
        }

        // Append extra attributes
        foreach($postTypes->all() as $postType){
            $postType->belongsTo = 'post_type';
            $postType->belongsToID = $postType->postTypeID;
            $postType->label = $postType->name;
            $postType->menuLinkParameters = $postType->menuLinkParameters();
        }

        // Append columns
        $results = $postTypes->toArray();
        $results['columns'] = [
            'postTypeID' => trans('id'),
            'name' => trans('base.title'),
        ];

        return $results;
    }

    /**
     *  Get all post types including their posts
     * */
    public function getAllIncludingPost($lang = ""){
        // check if user has permissions to access this link
        if(!User::hasAccess('PostType','read')){
            return $this->noPermission();
        }
        $orderBy = (isset($_GET['order'])) ? $_GET['order'] : 'postTypeID';
        $orderType = (isset($_GET['type'])) ? $_GET['type'] : 'DESC';

        $postTypes = DB::table('post_type')->orderBy($orderBy, $orderType)->offset(0)->limit(3000)->get();
        foreach ($postTypes as $postType){
            $posts = Pagination::make($postType->slug, Post::$rowsPerPage,'','','','');
            $posts = Language::filterRows($posts)['list'];
            $postType->posts = $posts;
            $postType->fields = json_decode($postType->fields);
        }
        return $postTypes;
    }

    /**
     * Get post type by ID
     *
     * @param integer $id post type id
     * @return object post type data
     */
    public static function findByID($lang, $id){
        return App\Models\PostType::findOrFail($id);
    }

    /**
     * Delete a Post Type
     * */
    public function delete($lang, $id){
        // check if user has permissions to access this link
        if(!User::hasAccess('PostType','delete')){
            return $this->noPermission();
        }
        $postType = PostType::findByID($id);

        // Check if this post type has posts
        // Post type should not be able to be deleted if it has posts
        if(PostType::hasPosts($postType->slug) && PostType::isInMenuLinks($id)){
            return $this->response( "You can't delete this Post Type. There could be posts associated with it or it is part of a menu", 403);
        }

        $postType->delete();
        if ($postType){
            Schema::drop($postType->slug);

            // delete route file
            if(file_exists(base_path().'/routes/'.$postType->slug.'.php')) {
                unlink(base_path() . '/routes/' . $postType->slug . '.php');
            }

            $result = $this->response( 'Post Type is successfully deleted');
        }else{
            $result = $this->response( 'Post Type could not be deleted. Please try again later or contact your Administrator!', 500);
        }
        return $result;
    }

    /**
     *  Bulk Delete post type
     *  Delete many post type
     *  @params array of post type IDs
     * */
    public function bulkDelete(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('PostType','delete')){
            return $this->noPermission();
        }
        // if there are no item selected
        if (count($request->all()) <= 0) {
            return $this->response( 'Please select items to be deleted', 500);
        }
        $data = $request->all();
        if(isset($data['postTypes'])){
            unset($data['postTypes']);
        }
        // loop throw the item array (ids) and delete them
        foreach($data as $id){
            $postType = PostType::findByID($id);
            if(PostType::hasPosts($postType->slug) && PostType::isInMenuLinks($id)){
                return $this->response( "You can't delete this Post Type because there are posts associated with it.", 403);
            }
            $postType->delete();
            if(!$postType) {
                return $this->response( 'Post types could not be deleted. Please try again later or contact your Administrator!', 500);
            }
            Schema::drop($postType->slug);
            $filename = ucfirst(camel_case($postType->slug));
            $path = base_path().'/routes/'.$filename.'.php';
            if(File::exists($path)){
                unlink($path);
            }
        }
        return $this->response('Selected post types are successfully deleted', 200);
    }


    public function deleteField(string $postTypeSlug, string $fieldSlug){
        if (Schema::hasColumn($postTypeSlug, $fieldSlug)){
            Schema::table($postTypeSlug, function($table) use ($fieldSlug){
                $table->dropColumn($fieldSlug);
            });
        }
        return true;
    }

    /**
     *  Store a new Post Type in database
     *  @param Request $request all post type data comming from the form
     *  @return array ErrorHandler
     * */
    public function store(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('PostType','create')){
            return $this->noPermission();
        }
        // custom messages for validation
        $messages = array(
            'name.required'=>'Post type name is required',
        );
        // validation
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'isVisible' => 'required'
        ], $messages);

        // if validation fails return json response
        if ($validator->fails()) {
            return $this->response( "Bad request", 400, null, false, false, true, $validator->errors());
        }

        $slug = $request->slug;
        if(isset($slug) && empty($slug)){
            $slug = self::generateSlug($request->name, 'post_type', 'postTypeID', App::getLocale(), 0);
        }else{
            $slug = self::generateSlug($request->slug, 'post_type', 'postTypeID', App::getLocale(), 0);
        }

        // create new table for the posts of the post type
        $customFieldsArray = PostType::createTable($slug, $request->fields);

        // Create post type
        $postType = new PostType();
        $postType->createdByUserID  = Auth::user()->userID;
        $postType->name             = $request->name;
        $postType->isVisible        = $request->isVisible;
        $postType->slug             = $slug;
        $postType->fields           = json_encode($customFieldsArray);
        $postType->hasCategories    = $request->hasCategories;
        $postType->isCategoryRequired = $request->isCategoryRequired;
        $postType->hasTags          = $request->hasTags;
        $postType->isTagRequired    = $request->isTagRequired;
        $postType->hasFeaturedVideo = $request->hasFeaturedVideo;
        $postType->isFeaturedImageRequired = $request->isFeaturedImageRequired;

        // return results
        if ($postType->save()){
            $redirectParams = parent::redirectParams($request->redirect, 'post-type', $postType->postTypeID);
            return $this->response('Post type is created', 200, $postType->postTypeID, $redirectParams['view'], $redirectParams['redirectUrl']);
        }else{
            return $this->response( 'Post Type is not created. Please try again later', 500);
        }
    }

    /**
     *  Update the post type data
     *  @param Request $request all post type data comming from the form
     *  @return object ErrorHandler
     * */
    public function storeUpdate(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('PostType','update')){
            return $this->noPermission();
        }
        // custom messages for validation
        $messages = array(
            'name.required'=>'Post type name is required',
        );
        // validation
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'isVisible' => 'required',
        ], $messages);

        // if validation fails return json response
        if ($validator->fails()) {
            return $this->response( "Please fill all required fields", 400, null, false, false, true, $validator->errors());
        }

        // create new table for the posts of the post type
        $customFieldsArray = PostType::updateTable($request->slug, $request->fields);

        // update post type query
        $postType = App\Models\PostType::findOrFail($request->id);
        $postType->name = $request->name;
        $postType->isVisible = $request->isVisible;
        $postType->fields = json_encode($customFieldsArray);
        $postType->isCategoryRequired = $request->isCategoryRequired;
        $postType->isTagRequired = $request->isTagRequired;
        $postType->hasFeaturedVideo = $request->hasFeaturedVideo;
        $postType->isFeaturedImageRequired = $request->isFeaturedImageRequired;

        if($postType->save()){
            // delete fields
            foreach($request->deletedFieldsSlugs as $fieldsSlug){
                $this->deleteField($postType->slug, $fieldsSlug);
            }

            $redirectParams = parent::redirectParams($request->redirect, 'post-type', $request->id);
            $result = $this->response('Post type is updated', 200, $request->id, $redirectParams['view'], $redirectParams['redirectUrl']);
        }else{
            $result = $this->response('Post Type is not updated. Please try again later', 500);
        }
        return $result;
    }

    /**
     * @return array with details for a specific post type
     * @params post type ID
     * */
    public function detailsJSON($lang, $id){
        // check if user has permissions to access this link
        if(!User::hasAccess('PostType','read')){
            return $this->noPermission();
        }
        $postType = App\Models\PostType::find($id);

        $categories = [];
        if($postType){
            //has access into all categories
            if(User::hasAccess($postType->slug,'categories','hasAll')){
                $categories = DB::table('categories')
                    ->join('post_type', 'post_type.postTypeID', 'categories.postTypeID')
                    ->where('post_type.slug', $postType->slug)
                    ->orderBy('name', 'postTypeID')
                    ->select('categories.categoryID','categories.title','categories.slug','categories.createdByUserID','categories.order','categories.featuredImageID',
                        'categories.description','categories.created_at','categories.updated_at','post_type.name')
                    ->get();
            }else if(User::getPermission($postType->slug,'categories')){
                //has access into some categories
                $allowedCategories = User::getPermission()[$postType]['categories']['value'];
                $categories = DB::table('categories')
                    ->join('post_type', 'post_type.postTypeID', 'categories.postTypeID')
                    ->where('post_type.slug', $postType->slug)
                    ->whereIn('categories.categoryID', $allowedCategories)
                    ->orderBy('name', 'postTypeID')
                    ->select('categories.categoryID','categories.categoryID','categories.slug','categories.createdByUserID','categories.order','categories.featuredImageID',
                        'categories.description','categories.created_at','categories.updated_at','post_type.name')
                    ->get();
            }
        }

        $categories = Language::filterRows($categories, false);

        $final = array('details' => $postType, 'categories' => $categories);

        // Fire event
        $final['events'] = Event::fire('postType:pre_update', [$final]);

        return $final;
    }

    /**
     *  This function creates the slug for a post type and makes sure that slugs it is not being used from a other post type
     * */
    public function getSlug($lang, $slug){
        return self::generateSlug($slug, 'post_type', 'postTypeID', $lang, 0);
    }

    /**
     * Get post type by slug
     *
     * @param $lang language slug
     * @param $slug post type slug
     * @return array post type
     */
    public function getBySlug($lang, $slug){
        if(isset(PostType::getFromCache()[$slug])){
            return PostType::getFromCache()[$slug];
        }
        return [];
    }

    /**
     * @inheritdoc
     * */
    public function generateSlug($title, $tableName, $primaryKey, $languageSlug = '', $id = 0, $translatable = false, $delimiter = "-"){
        $count = 0;
        $found = true;
        $originalSlug = str_slug($title, '-');
        $originalSlug = str_replace('post-','',$originalSlug);
        $originalSlug = "post_".$originalSlug;

        while($found){
            if($count != 0){
                $slug = $originalSlug."-".$count;
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
     * Generates a list of tables that can be used as data for custom field drop down
     * @return array of table names
     * */
    public function getTables(){
        $tables = [
            0 => [
                'group' => 'Post Type',
                'options' => []
            ],
            1 => [
                'group' => 'Others',
                'options' => [
                    ['label' => 'Users', 'name' => 'users', 'belongsTo' => 'User'],
                ]
            ]
        ];
        // loop throw post types
        foreach(App\Models\PostType::getFromCache() as $postType){
            $tables[0]['options'][] = ['label' => $postType['name'], 'name' => $postType['slug'], 'belongsTo' => 'PostType'];
        }
        return $tables;
    }
}
