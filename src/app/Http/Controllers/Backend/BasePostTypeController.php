<?php

namespace Accio\App\Http\Controllers\Backend;

use App;
use App\Models\Language;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Schema;
use Validator;
use App\Models\PostType;
use App\Models\Category;
use App\Models\Tag;
use App\Models\TagRelation;
use App\Models\CategoryRelation;

use Illuminate\Http\Request;

class BasePostTypeController extends MainController{
    // request in post to be able to use it in inner functions
    public $req;

    /**
     * Gets post type with his category.
     *
     * @param string $lang
     * @return array|\Illuminate\Contracts\Pagination\Paginator
     * @throws \Exception
     */
    public function getAll($lang = ""){
        $result = [];
        $postType = PostType::all()->orderBy('postTypeID');
        foreach ($postType as $postType){
            $postType->categories = Category::where("postTypeID", $postType->postTypeID)->get();
            $result[] = $postType;
        }

        return ['data' => $result];
    }

    /**
     * Get all post types for menu panel.
     *
     * @param string $lang
     * @return array
     */
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
     * Get post type by ID.
     *
     * @param integer $id post type id
     * @return object post type data
     */
    public static function findByID($lang, $id){
        return PostType::where("postTypeID", $id)->select("postTypeID", "name", "slug", "isVisible")->first();
    }

    /**
     * Delete a Post Type by using it's ID.
     *
     * @param string $lang
     * @param int $id
     * @return array
     */
    public function delete($lang, $id){
        // check if user has permissions to access this link
        if(!User::hasAccess('PostType','delete')){
            return $this->noPermission();
        }

        $res = $this->deletePostType($id);
        if($res && is_bool($res)){
            return $this->response( 'Post Type is successfully deleted');
        }
        if($res && !is_bool($res)){
            return $res;
        }
        return $this->response( 'Post Type could not be deleted. Please try again later or contact your Administrator!', 500);
    }


    /**
     * Bulk Delete post type.
     * Delete many post type in the same time.
     *
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function bulkDelete(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('PostType','delete')){
            return $this->noPermission();
        }
        // if there are no item selected
        if (count($request->all()) <= 0) {
            return $this->response( 'Please select items to be deleted', 500);
        }

        // loop throw the item array (ids) and delete them
        foreach($request->all() as $id){
            $res = $this->deletePostType($id);
            if(!$res){
                return $this->response( 'Post Type could not be deleted. Please try again later or contact your Administrator!', 500);
            }
            if(!is_bool($res)){
                return $res;
            }
        }
        return $this->response('Selected post types are successfully deleted', 200);
    }

    /**
     * Delete post type by using ID.
     * Used in bulkDelete and delete functions.
     *
     * @param $id
     * @return array|bool
     * @throws \Exception
     */
    private function deletePostType($id){
        $postType = PostType::find($id);
        if(!$postType){
            return false;
        }

        // Check if this post type has posts
        // Post type should not be able to be deleted if it has posts
        if(PostType::hasPosts($postType->slug) || PostType::isInMenuLinks($id)){
            return $this->response( "You can't delete this Post Type. There could be posts associated with it or it is part of a menu", 403);
        }

        if($this->deleteRelatedTagsAndCategory($postType) && $postType->delete()){
            Schema::drop($postType->slug);

            // delete route file
            if(file_exists(base_path().'/routes/'.$postType->slug.'.php')) {
                unlink(base_path() . '/routes/' . $postType->slug . '.php');
            }
            return true;
        }

        return false;
    }

    /**
     * Delete categories and tags with the relations for a post type.
     *
     * @param int $postTypeID
     * @return bool
     */
    public function deleteRelatedTagsAndCategory($postType){
        $categories = Category::where("postTypeID", $postType->postTypeID);
        $categoryIDs = $categories->pluck('categoryID')->toArray();
        $categoriesRelations = (new CategoryRelation)->setTable($postType->slug."_categories")->whereIn("categoryID", $categoryIDs);

        $tags = Tag::where("postTypeID", $postType->postTypeID);
        $tagIDs = $tags->pluck('tagID')->toArray();
        $tagsRelations = (new TagRelation)->setTable($postType->slug."_tags")->whereIn("tagID", $tagIDs);

        // return false if any delete failed
        if(($tagsRelations->count() && !$tagsRelations->delete()) ||
            ($categoriesRelations->count() && !$categoriesRelations->delete()) ||
            ($tags->count() && !$tags->delete()) ||
            ($categories->count() && !$categories->delete())
        ){
            return false;
        }else{
            Schema::drop($postType->slug."_media");
            if($postType->hasCategories){
                Schema::drop($postType->slug."_categories");
            }
            if($postType->hasTags){
                Schema::drop($postType->slug."_tags");
            }
        }
        return true;
    }

    /**
     * Delete column from DB.
     *
     * @param string $postTypeSlug
     * @param string $fieldSlug
     * @return bool
     */
    public function deleteField(string $postTypeSlug, string $fieldSlug){
        if (Schema::hasColumn($postTypeSlug, $fieldSlug)){
            Schema::table($postTypeSlug, function($table) use ($fieldSlug){
                $table->dropColumn($fieldSlug);
            });
        }
        return true;
    }

    /**
     *  Save Post Type in database.
     *
     *  @param Request $request all post type data coming from the form
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
            return $this->response( "Please check all required fields!", 400, null, false, false, true, $validator->errors());
        }

        if(isset($request->id)){
            $postType = PostType::findOrFail($request->id);
            $customFieldsArray = PostType::updateTable($request->slug, $request->fields, $request->hasCategories, $request->hasTags);
        }else{
            $slug = $request->slug;
            if(isset($slug) && empty($slug)){
                $slug = self::generateSlug($request->name, 'post_type', 'postTypeID', App::getLocale(), 0);
            }else{
                $slug = self::generateSlug($request->slug, 'post_type', 'postTypeID', App::getLocale(), 0);
            }
            // create new table for the posts of the post type
            $customFieldsArray = PostType::createTable($slug, $request->fields, $request->hasCategories, $request->hasTags);

            // Create post type
            $postType = new PostType();
            $postType->createdByUserID  = Auth::user()->userID;
            $postType->slug = $slug;
        }

        $postType->name             = $request->name;
        $postType->isVisible        = $request->isVisible;
        $postType->fields           = $customFieldsArray;
        $postType->hasCategories    = $request->hasCategories;
        $postType->isCategoryRequired = $request->isCategoryRequired;
        $postType->hasTags          = $request->hasTags;
        $postType->isTagRequired    = $request->isTagRequired;
        $postType->hasFeaturedImage = $request->hasFeaturedImage;
        $postType->isFeaturedImageRequired = $request->isFeaturedImageRequired;
        $postType->hasFeaturedVideo = $request->hasFeaturedVideo;
        $postType->isFeaturedVideoRequired = $request->isFeaturedVideoRequired;

        // return results
        if ($postType->save()){
            if(isset($request->id)){
                // delete fields
                foreach($request->deletedFieldsSlugs as $fieldsSlug){
                    $this->deleteField($postType->slug, $fieldsSlug);
                }
            }

            $redirectParams = parent::redirectParams($request->redirect, 'post-type', $postType->postTypeID);
            return $this->response('Post type stored', 200, $postType->postTypeID, $redirectParams['view'], $redirectParams['redirectUrl']);
        }else{
            return $this->response( 'Post Type is not stored. Internal sever error', 500);
        }
    }

    /**
     * Get post type with all details - (Categories, fields etc).
     *
     * @param string $lang
     * @param int $id
     * @return array
     * @throws \Exception
     */
    public function detailsJSON($lang, $id){
        // check if user has permissions to access this link
        if(!User::hasAccess('PostType','read')){
            return $this->noPermission();
        }
        $postType = App\Models\PostType::find($id);

        $categories = [];
        if($postType){
            // has access into all categories
            if(User::hasAccess($postType->slug,'categories','hasAll')){
                $categories = DB::table('categories')
                    ->join('post_type', 'post_type.postTypeID', 'categories.postTypeID')
                    ->where('post_type.slug', $postType->slug)
                    ->orderBy('name', 'postTypeID')
                    ->select('categories.categoryID','categories.title','categories.slug','categories.createdByUserID','categories.order','categories.featuredImageID',
                        'categories.description','categories.created_at','categories.updated_at','post_type.name')
                    ->get();
            }else if(User::getPermission($postType->slug,'categories')){
                // has access into some categories
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

        $final = array('details' => $postType, 'categories' => $categories, 'dbTables' => $this->getTables());

        // Fire event
        $final['events'] = Event::fire('postType:pre_update', [$final]);

        return $final;
    }

    /**
     * This function creates the slug for a post type and makes sure that slugs it is not being used
     * from a other post type.
     *
     * @param string $lang
     * @param string $slug
     * @return mixed|string
     */
    public function getSlug($lang, $slug){
        return self::generateSlug($slug, 'post_type', 'postTypeID', $lang, 0);
    }

    /**
     * Get post type by slug.
     *
     * @param string $lang language slug
     * @param string $slug post type slug
     * @return array post type
     */
    public function getBySlug($lang, $slug){
        $postType = PostType::collection()->where('slug', $slug);
        if(!$postType->isEmpty()){
            return $postType->first();
        }
        return [];
    }

    /**
     * @inheritdoc
     * */
    public function generateSlug($title, $tableName, $primaryKey, $languageSlug = '', $id = 0, $translatable = false,
                                 $hasVirtualSlug = false, $delimiter = "_"){
        $count = 0;
        $found = true;
        $originalSlug = str_slug($title, $delimiter);
        $originalSlug = str_replace('post'.$delimiter,'',$originalSlug);
        $originalSlug = "post".$delimiter.$originalSlug;

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
     * Generates a list of tables that can be used as data for custom field drop down.
     *
     * @return array
     * @throws \Exception
     */
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
        foreach(PostType::all() as $postType){
            $tables[0]['options'][] = ['label' => $postType['name'], 'name' => $postType['slug'], 'belongsTo' => 'PostType'];
        }
        return $tables;
    }
}
