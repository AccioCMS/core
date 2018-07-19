<?php

namespace Accio\App\Http\Controllers\Backend;

use App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Validator;

use App\Models\Tag;
use Accio\Support\Facades\Pagination;
use App\Models\PostType;
use Accio\Support\Facades\Search;
use App\Models\User;
use Illuminate\Http\Request;

class BaseTagController extends MainController{
    /**
     * BaseTagController constructor.
     */
    public function __construct(){
        parent::__construct();
    }

    /**
     * Get list of the tags for a specific post type
     *
     * @return Accio\Support\Facades\Pagination::make
     * */
    public function getAllByPostType($lang = "", $postTypeID){
        $orderBy = (isset($_GET['order'])) ? $orderBy = $_GET['order'] : 'tagID';
        $orderType = (isset($_GET['type'])) ? $orderType = $_GET['type'] : 'DESC';
        // get data
        return Tag::where('postTypeID',$postTypeID)->orderBy($orderBy, $orderType)->paginate(Tag::$rowsPerPage);
    }

    /**
     * Delete a tag
     * */
    public function delete($lang, $id){
        // check if user has permissions to access this link
        if(!User::hasAccess('Tags','delete')){
            return $this->noPermission();
        }

        $tags = App\Models\Tag::find($id);
        $postType = PostType::findByID($tags->postTypeID);

        // Check if this post type has posts
        // Post type should not be able to be deleted if it has posts
        if(Tag::hasPosts($postType->slug,$tags)){
            return $this->response("You can't delete this Tag. There are posts associated with it.", 403);
        }

        $tags->delete();
        if ($tags){
            $result = $this->response( 'Tag is successfully deleted');
        }else{
            $result = $this->response('Tag could not be deleted. Please try again later or contact your Administrator!', 500);
        }
        return $result;
    }

    /**
     *  Bulk Delete tags
     *  Delete many tags
     *  @params $request Request
     *  @return array
     * */
    public function bulkDelete(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('Tags','delete')){
            return $this->noPermission();
        }

        // if there are no item selected
        if (count($request->all()) <= 0) {
            return $this->response( 'Please select items to be deleted', 500);
        }
        // loop throw the item array (ids) and delete them
        foreach ($request->all() as $id) {
            $tags = Tag::find($id);
            $postType = PostType::findByID($tags->postTypeID);
            if(Tag::hasPosts($postType->slug,$tags)){
                return $this->response( "You can't delete this Tag. There are posts associated with it.", 403);
            }
            $tags->delete();
            if (!$tags) {
                return $this->response( 'Tags could not be deleted. Please try again later or contact your Administrator!', 500);
            }
        }
        return $this->response( 'Selected tags are successfully deleted');
    }

    /**
     *  Store a new tag in database
     *  @param Request $request all tag data comming from the form
     *  @return array
     * */
    public function store(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('Tags','create')){
            return $this->noPermission();
        }

        // custom messages for validation
        $messages = array();

        // validation
        $validator = Validator::make($request->formData, [
            'title' => 'required',
            'postTypeID' => 'required',
            'slug' => 'required|max:500|unique:tags',
        ], $messages);

        if ($validator->fails()) {
            return $this->response("Please check all required fields!", 400, null, false, false, true, $validator->errors());
        }

        $tags = new Tag();
        $tags->title         = $request->formData['title'];
        $tags->postTypeID    = $request->formData['postTypeID'];
        $tags->description   = $request->formData['description'];
        $tags->slug          = $request->formData['slug'];
        $tags->featuredImageID = ($request->formData['featuredImage']  !== 0 ? $request->formData['featuredImage'] : null);
        $tags->createdByUserID = Auth::user()->userID;

        // return results
        if ($tags->save()){
            $adminPrefix = Config::get('project')['adminPrefix'];
            if($request->formData['redirect'] == 'save'){
                $redirectUrl = "/".$adminPrefix."/".App::getLocale()."/post-type/tagupdate/".$tags->tagID;
                $view = 'tagupdate';
                $redirectID = $tags->tagID;
            }else if($request->formData['redirect'] == 'close'){
                $redirectUrl = "/".$adminPrefix."/".App::getLocale()."/post-type/taglist/".$request->formData['postTypeID'];
                $view = 'taglist';
                $redirectID = $request->formData['postTypeID'];
            }else{
                $redirectUrl = "/".$adminPrefix."/".App::getLocale()."/post-type/tagcreate/".$request->formData['postTypeID'];
                $view = 'tagcreate';
                $redirectID = $request->formData['postTypeID'];
            }

            $result = $this->response('Tag is created', 200, $redirectID, $view, $redirectUrl);
        }else{
            $result = $this->response( 'Internal server error. Please try again later', 500);
        }
        return $result;
    }


    /**
     *  Store a new tag in database
     *  @param Request $request all tag data comming from the form
     *  @return array
     * */
    public function storeUpdate(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('Tags','update')){
            return $this->noPermission();
        }

        // custom messages for validation
        $messages = array();
        // validation
        $validator = Validator::make($request->formData, [
            'title' => 'required',
            'slug' => 'required',
        ], $messages);

        // if slug is being used from a other tag
        $isSlugBeingUsed = Tag::where('slug',$request->slug)->where('tagID','!=',$request->id)->count();
        if($isSlugBeingUsed){
            return $this->response("Fill all required fields", 400, null, false, false, true, ['slug'=>['Slug is being used from a other category']]);
        }

        // if validation fails return json response
        if ($validator->fails()) {
            return $this->response("Fill all required fields", 400, null, false, false, true, $validator->errors());
        }

        // Update Tag
        $tags = Tag::findOrFail($request->formData['id']);
        $tags->title         = $request->formData['title'];
        $tags->description   = $request->formData['description'];
        $tags->slug          = $request->formData['slug'];
        $tags->featuredImageID = ($request->formData['featuredImage']  !== 0 ? $request->formData['featuredImage'] : null);

        // return results
        if ($tags->save()){
            $adminPrefix = Config::get('project')['adminPrefix'];
            if($request->formData['redirect'] == 'save'){
                $redirectUrl = "/".$adminPrefix."/".App::getLocale()."/post-type/tagupdate/".$request->formData['id'];
                $view = 'tagupdate';
                $redirectID = $request->formData['id'];
            }else if($request->formData['redirect'] == 'close'){
                $redirectUrl = "/".$adminPrefix."/".App::getLocale()."/post-type/taglist/".$request->postTypeID;
                $view = 'taglist';
                $redirectID = $request->postTypeID;
            }else{
                $redirectUrl = "/".$adminPrefix."/".App::getLocale()."/post-type/tagcreate/".$request->postTypeID;
                $view = 'tagcreate';
                $redirectID = $request->postTypeID;
            }
            $result = $this->response('Tag is updated', 200, $redirectID, $view, $redirectUrl);
        }else{
            $result = $this->response( 'Internal server error. Please try again later', 500);
        }
        return $result;
    }


    /**
     *  return JSON object with details for a specific category
     * @params categoryID
     * */
    public function detailsJSON($lang, $id){
        // check if user has permissions to access this link
        if(!User::hasAccess('Tags','update')){
            return $this->noPermission();
        }

        $tags = App\Models\Tag::find($id);
        $media = App\Models\Media::find($tags->featuredImageID);
        $final = array(
            'details' => $tags,
            'featuredImage' => $media
        );

        // Fire event
        $final['events'] = Event::fire('tag:pre_update', [$final]);

        return $final;
    }

    /**
     * Make simple search with a search term
     * @return array search result
     * */
    public function makeSearch($lang,$postTypeID, $term){
        // check if user has permissions to access this link
        if(!User::hasAccess('Tags','read')){
            return $this->noPermission();
        }

        $orderBy    = 'tagID';
        $orderType  = 'DESC';
        if(isset($_GET['order'])){
            $orderBy = $_GET['order'];
        }
        if(isset($_GET['type'])){
            $orderType = $_GET['type'];
        }
        $excludeColumns = array('customFields','featuredImage','remember_token', 'created_at', 'updated_at');
        $conditions = array(
            0 => array(
                'where',
                'postTypeID',
                '=',
                $postTypeID
            )
        );
        return Search::searchByTerm('tags', $term, Tag::$rowsPerPage, true, array(), $excludeColumns, $orderBy, $orderType, '', $conditions);
    }

    /**
     *  return views for search component
     *  @params search term
     *  @return Search view
     * */
    public function search($lang, $id, $term){
        $adminPrefix = Config::get('project')['adminPrefix'];
        // check if user has permissions to access this link
        if(!User::hasAccess('Tags','read')){
            return $this->noPermission();
        }

        $adminPrefix = Config::get('project')['adminPrefix'];
        $postTypes = \App\Models\PostType::cache()->collect(); // get the post types list for the navigation bar from the middleware

        $view = 'tagsearch';
        $pagination = 1;
        $orderBy = '';
        $orderType = '';
        if(isset($_GET['pagination'])){
            $pagination = $_GET['pagination'];
        }else{
            $pagination = 1;
        }
        if(isset($_GET['order'])){
            $orderBy = $_GET['order'];
        }
        if(isset($_GET['type'])){
            $orderType = $_GET['type'];
        }
        $isSearch = true; // used when generatin language menu (language meu that chenges the locate in backend)
        return view(App\Models\PostType::$backendPathToView.'all', compact('lang','view','term','pagination','orderBy','orderType','id','postTypes','isSearch','adminPrefix'));
    }

    /**
     *  Get all categories without pagination filtering by post type
     * */
    public function getAllWithoutPaginationByPostType($lang = "", $postType = ""){
        return DB::table('tags')->join('post_type', 'post_type.postTypeID', 'tags.postTypeID')->where('post_type.slug', $postType)->orderBy('name', 'postTypeID')->get();
    }

    /**
     * This function creates the slug for a tag and makes sure that slugs it is not being used from a other tags
     * @return string generated slug
     * */
    public function getSlug($lang, $postTypeID, $slug){
        return parent::generateSlug($slug, 'tags', 'tagID', '', 0, false);
    }

}
