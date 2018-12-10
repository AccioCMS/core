<?php

namespace Accio\App\Http\Controllers\Backend;

use App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Validator;
use App\Models\Tag;
use App\Models\Media;
use Accio\Support\Facades\Search;
use App\Models\User;
use Illuminate\Http\Request;

class BaseTagController extends MainController
{

    /**
     * Get list of the tags for a specific post type.
     *
     * @param  string $lang
     * @param  int    $postTypeID
     * @return mixed
     */
    public function getAllByPostType($lang = "", $postTypeID)
    {
        $orderBy = (isset($_GET['order'])) ? $orderBy = $_GET['order'] : 'tagID';
        $orderType = (isset($_GET['type'])) ? $orderType = $_GET['type'] : 'DESC';
        // get data
        return Tag::where('postTypeID', $postTypeID)->orderBy($orderBy, $orderType)->paginate(Tag::$rowsPerPage);
    }

    /**
     * Delete request for a single tag.
     *
     * @param  string $lang
     * @param  int    $id
     * @return array
     */
    public function delete($lang, $id)
    {
        // check if user has permissions to access this link
        if(!User::hasAccess('Tags', 'delete')) {
            return $this->noPermission();
        }

        $tagDeleteRes = $this->deleteTag($id);
        if(gettype($tagDeleteRes) == "boolean") {
            if($tagDeleteRes) {
                return $this->response('Tag is successfully deleted');
            }
        }else{
            return $tagDeleteRes;
        }
        return $this->response('Tag could not be deleted. Please try again later or contact your Administrator!', 500);
    }

    /**
     * Bulk Delete tags, delete many tags with one request.
     * 
     * @param  Request $request
     * @return array
     */
    public function bulkDelete(Request $request)
    {
        // check if user has permissions to access this link
        if(!User::hasAccess('Tags', 'delete')) {
            return $this->noPermission();
        }

        // if there are no item selected
        if (count($request->all()) <= 0) {
            return $this->response('Please select items to be deleted', 500);
        }
        // loop throw the item array (ids) and delete them
        foreach ($request->all() as $id) {
            $tagDeleteRes = $this->deleteTag($id);
            if(gettype($tagDeleteRes) == "boolean") {
                if(!$tagDeleteRes) {
                    return $this->response('Tag could not be deleted.', 500);
                }
            }else{
                return $tagDeleteRes;
            }
        }
        return $this->response('Selected tags are successfully deleted');
    }

    /**
     * Deletes tag by id.
     *
     * @param  int $id
     * @return array|bool
     */
    private function deleteTag($id)
    {
        $tags = Tag::find($id);
        if(Tag::hasPosts($id)) {
            return $this->response("You can't delete this Tag. There are posts associated with it.", 403);
        }
        if ($tags->delete()) {
            return true;
        }
        return false;
    }

    /**
     * Store a new tag in database.
     *
     * @param  Request $request
     * @return array
     */
    public function store(Request $request)
    {
        // check if user has permissions to access this link
        if(!User::hasAccess('Tags', 'create')) {
            return $this->noPermission();
        }

        $validatorData = [
            'title' => 'required',
            'postTypeID' => 'required',
            'slug' => 'required|max:500|unique:tags',
        ];

        if(isset($request->formData['id'])) {
            // Update Tag
            $tags = Tag::findOrFail($request->formData['id']);
            $validatorData['slug'] = 'required';
        }else{
            $tags = new Tag();
            $tags->createdByUserID = Auth::user()->userID;
            $tags->postTypeID = $request->formData['postTypeID'];
        }

        // validation
        $validator = Validator::make($request->formData, $validatorData, []);
        // check validation
        if ($validator->fails()) {
            return $this->response("Please check all required fields!", 400, null, false, false, true, $validator->errors());
        }

        $tags->title         = $request->formData['title'];
        $tags->description   = $request->formData['description'];
        $tags->slug          = $request->formData['slug'];
        $tags->featuredImageID = ($request->formData['featuredImage']  !== 0 ? $request->formData['featuredImage'] : null);

        // return results
        if ($tags->save()) {
            $redirect = $this->redirect($request->formData['redirect'], $tags->tagID, $request->formData['postTypeID']);
            $result = $this->response('Tag is saved', 200, $redirect['id'], $redirect['view'], $redirect['url']);
        }else{
            $result = $this->response('Internal server error. Please try again later', 500);
        }
        return $result;
    }

    /**
     * Redirect parameters used in frontend.
     * TODO : me ndrru qit sistem te redirectit ne frontend nese nuk kthehemi ne angular
     *
     * @param  string $redirect
     * @param  int    $tagID
     * @param  int    $postTypeID
     * @return array
     */
    private function redirect($redirect, int $tagID, int $postTypeID)
    {
        $adminPrefix = Config::get('project')['adminPrefix'];
        if($redirect == 'save') {
            $redirectUrl = "/".$adminPrefix."/".App::getLocale()."/post-type/tagupdate/".$tagID;
            $view = 'tagupdate';
            $redirectID = $tagID;
        }else if($redirect == 'close') {
            $redirectUrl = "/".$adminPrefix."/".App::getLocale()."/post-type/taglist/".$postTypeID;
            $view = 'taglist';
            $redirectID = $postTypeID;
        }else{
            $redirectUrl = "/".$adminPrefix."/".App::getLocale()."/post-type/tagcreate/".$postTypeID;
            $view = 'tagcreate';
            $redirectID = $postTypeID;
        }

        return[
            "url" => $redirectUrl,
            "view" => $view,
            "id" => $redirectID
        ];
    }


    /**
     * JSON object with details for a specific tag.
     * All data used in update form.
     *
     * @param  string $lang
     * @param  int    $id
     * @return array
     */
    public function detailsJSON($lang, $id)
    {
        // check if user has permissions to access this link
        if(!User::hasAccess('Tags', 'update')) {
            return $this->noPermission();
        }

        $tags = Tag::find($id);
        $media = Media::find($tags->featuredImageID);
        $final = array(
            'details' => $tags,
            'featuredImage' => $media
        );

        // Fire event
        $final['events'] = Event::fire('tag:pre_update', [$final]);
        return $final;
    }

    /**
     * Make simple search with a search term.
     *
     * @param  string $lang
     * @param  int    $postTypeID
     * @param  string $term
     * @return array
     */
    public function makeSearch($lang,$postTypeID, $term)
    {
        // check if user has permissions to access this link
        if(!User::hasAccess('Tags', 'read')) {
            return $this->noPermission();
        }

        $orderBy    = (isset($_GET['order'])) ? $_GET['order'] : 'tagID';
        $orderType  = (isset($_GET['type'])) ? $_GET['type'] :'DESC';

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
     * Returns views for search component.
     *
     * @param  string $lang
     * @param  int    $id
     * @param  string $term
     * @return array|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function search($lang, $id, $term)
    {
        // check if user has permissions to access this link
        if(!User::hasAccess('Tags', 'read')) {
            return $this->noPermission();
        }
        return view('content');
    }

    /**
     * Get all categories without pagination filtering by post type.
     *
     * @param  string $lang
     * @param  string $postType
     * @return \Illuminate\Support\Collection
     */
    public function getAllWithoutPaginationByPostType($lang = "", $postType = "")
    {
        return DB::table('tags')->join('post_type', 'post_type.postTypeID', 'tags.postTypeID')->where('post_type.slug', $postType)->orderBy('name', 'postTypeID')->get();
    }

    /**
     * This function creates the slug for a tag and makes sure that slugs it is not being used from a other tags.
     *
     * @param  string $lang
     * @param  int    $postTypeID
     * @param  string $slug
     * @return string
     */
    public function getSlug($lang, $postTypeID, $slug)
    {
        return parent::generateSlug($slug, 'tags', 'tagID', '', 0, false);
    }
}
