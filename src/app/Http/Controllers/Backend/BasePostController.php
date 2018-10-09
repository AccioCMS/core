<?php

namespace Accio\App\Http\Controllers\Backend;

use App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use App\Models\Language;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Input;
use Accio\Support\Facades\Search;
use App\Models\PostType;
use App\Models\Task;
use App\Models\Post;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Media;
use App\Models\CustomField;
use App\Models\Category;
use App\Models\CustomFieldGroup;

class BasePostController extends MainController {
    // Check authentication in the constructor
    public function __construct(){
        parent::__construct();
    }


    /**
     * Get all post of a post type
     *
     * TODO: me shti ne funksion ElasticSearch
     *
     * @param string $lang language slug
     * @param string $postType post type slug
     * @return array post data
     */
    public function getAllPosts($lang, $postType){
        // check if user has permissions to access this link
        if(!User::hasAccess($postType,'read')){
            return $this->noPermission();
        }

        $defaultOrderBy = 'postID';
        $orderBy = (Input::get('order') ) ? Input::get('order') : $defaultOrderBy;
        $orderType = (Input::get('type')) ? Input::get('type') : 'DESC';
        $advancedSearch = (Input::get('advancedSearch')) ? Input::get('advancedSearch') : false;
        $categoryID = (Input::get('categoryID')) ? Input::get('categoryID') : null;

        // get field type in db
        try{
            $orderColumnType = DB::connection()->getDoctrineColumn($postType, $orderBy)->getType()->getName();
        }catch (\Exception $e){
            $orderColumnType = false;
            $orderBy = $defaultOrderBy;
        }

        // make the query
        $queryObject =  (new Post())->setTable($postType);
        // get user relation
        $queryObject = $queryObject->with("users");

        // get categories relations (if post type has categories)
        $postTypeOBJ = getPostType($postType);
        if($postTypeOBJ->hasCategories){
            $queryObject = $queryObject->with("categories");
        }

        // Advanced Search
        if($advancedSearch){
            $queryObject = $this->advancedSearch($queryObject, request(), $postType);
        }else if($categoryID){
            // select based on categories
            $queryObject = $this->selectByCategory($queryObject, $categoryID, $postType);
        }

        // json langauge fields needs to be filtered by their langauge
        if($orderColumnType == "json"){
            $queryObject = $queryObject->orderBy($orderBy."->".App::getLocale(), $orderType);
        }else{
            $queryObject = $queryObject->orderBy($orderBy, $orderType);
        }

        // paginate
        $paginationResult = $queryObject->paginate(Post::$rowsPerPage);

        $response = $this
            ->appendListColumnsFromEvents($postType)
            ->appendListRowsFromEvents($paginationResult, $postType)
            ->toArray();

        $response['inTableColumns'] = $this->getInTableColumns($postType);

        $lang = App::getLocale();
        // set category title and author name
        foreach ($response['data'] as $key => $item){
            if(isset($item['categories']) && count($item['categories'])){
                array_set($response, "data.$key.category",$item['categories'][0]['title']->$lang);
            }
            array_set($response, "data.$key.author",$item['users']['firstName'] . " ". $item['users']['lastName']);
        }

        return $response;
    }

    /**
     * Get all data needed in the post create form
     * Fields, Custom Fields, Categories, Languages
     *
     * @param $lang
     * @param $postTypeSlug
     * @return array
     * @throws \Exception
     */
    public function getDataForCreate($lang, $postTypeSlug){
        // Custom field groups
        $customFieldsGroups = CustomFieldGroup::findGroups('post-type', 'create', 0, $postTypeSlug);

        // post type
        $postType = PostType::cache()->where('slug', $postTypeSlug)->getItems()->first();

        // Categories (options to select)
        $categories = array_values(Category::cache()->where("postTypeID", $postType->postTypeID)->toArray());

        return [
            'postType' => $postType,
            'languages' => Language::cache()->getItems(),
            'categories' => $categories,
            'customFieldsGroups' => $customFieldsGroups,
            'postTypeFieldsValues' => $this->getPostTypeFieldsValues($postTypeSlug),
            'users' => User::cache()->getItems(),
            'createdByUserID' => Auth::user()->userID,
        ];
    }

    /**
     * Return view (list, create) and check permissions
     *
     * @param $lang
     * @param $postTypeSlug
     * @param $view
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function postsIndex($lang, $postTypeSlug, $view){
        $postType = PostType::findBySlug($postTypeSlug);
        if(!$postType){
            return response()->view('errors.404', ['message' => "This post type does not exist!"], 404);
        }

        // check if user has permissions to access this link
        $key = ($view == 'list') ? 'read' : $view;
        if(!User::hasAccess($postTypeSlug,$key)){
            return view('errors.permissions');
        }

        return view('content');
    }

    /**
     * This method loads single post view (like update etc.)
     *
     * @param $lang
     * @param $postTypeSlug
     * @param $view
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function postsSingle($lang, $postTypeSlug, $view, $id){
        // check if user has permissions to access this link
        if(!User::hasAccess($postTypeSlug,$view, $id, true)){
            $message = "You can't edit this post because you don't own it!";
            return view('errors.permissions', compact('message'));
        }
        return view('content');
    }

    /**
     * Prepare values for each field.
     * used when this function is called in detailsJson (update form of the post)
     *
     * @param $postType
     * @return array
     */
    public function getTranslatableFields($postType){
        // check if user has permissions to access this link
        if(!User::hasAccess($postType,'read')){
            return $this->noPermission();
        }

        // fields of this post type
        $translatableFields = [];
        $fields = App\Models\PostType::getFields($postType);
        foreach ($fields as $field) {
            if (isset($post) && $post) {
                $slug = $field->slug;
                if ($field->translatable) {
                    array_push($translatableFields, $slug);
                }
            }
        }

        return $translatableFields;
    }

    /**
     * Get columns list that will be used only in post list table
     * Used to add extra columns in post list table
     *
     * @param $postType
     * @return array
     */
    public function getInTableColumns($postType){
        $inTableColumnsSlugs = [];
        $fields = PostType::getFields($postType);

        // default columns
        foreach(Post::$defaultListColumns as $key => $label){
            if(substr($label,0, 2) == '__'){
                $label = __(substr($label,2));
            }

            $inTableColumnsSlugs[$key] = $label;
        }

        // post type columns
        foreach($fields as $field){
            if($field->inTable){
                $inTableColumnsSlugs[$field->slug] = $field->name;
            }
        }

        // custom events columns
        $customListColumms = Event::fire('post:table_list_columns', [$postType]);
        foreach($customListColumms as $customList){
            if(is_array($customList)) {
                foreach ($customList as $key => $value) {
                    $inTableColumnsSlugs[$key] = $value;
                }
            }
        }
        return $inTableColumnsSlugs;
    }

    /**
     * Handel values for post type fields needed in Create and Update form
     * Get values of every post field (custom post field)
     *
     * @param string $postType
     * @param $post
     * @return array
     * @throws \Exception
     */
    public function getPostTypeFieldsValues(string $postType, $post = ""){
        $fields = PostType::getFields($postType);
        $languages = Language::cache()->getItems();
        $values = [];

        foreach ($fields as $field){
            if($field->inTable){
                // add columns that should appear in table
                array_push($column, $field);
            }

            /**
             * prepare values for each field
             * used when this function is called in detailsJson (update form of the post)
             */
            if (isset($post) && $post){
                $slug = $field->slug;
                if(!$field->translatable){
                    if(array_key_exists($slug,$post->getAttributes())){
                        if($field->type->inputType == "checkbox"){ // if input type is checkbox the value should be array
                            $value = ($post->$slug) ? explode(",", $post->$slug) : [];
                            $field->value = $value;
                        }else if ($field->type->inputType == "date"){ // if input type is date remove the php time form the string
                            $field->value = explode(" ", $post->$slug)[0];
                        }else{
                            $field->value = $post->$slug;
                        }
                    }
                }else{
                    if(gettype($post->$slug) != "array" && json_decode($post->$slug) == null){
                        $value = [];
                    }else{
                        $value = (array) json_decode($post->$slug);
                    }
                    // if there is a new language this code puts the new language key in the details
                    foreach($languages as $lang){
                        if(!key_exists($lang->slug, $value)){
                            if($field->type->inputType == "checkbox"){
                                $value[$lang->slug] = [];
                            }else{
                                $value[$lang->slug] = [];
                            }
                        }
                    }
                    $field->value = $value;
                }
            }


            /**
             * get data from database if custom field is "Dropdown from DB"
             */
            if($field->type->inputType == "db"){

                /**
                 * construct categories field (@If categories 0 it means it's value is all @else construct a array with categories slug)
                 * categories when field is dropdown from db (used to take posts of only a category)
                 */
                if(count($field->categories)){
                    $categoriesTmp = [];
                    foreach ($field->categories as $category){
                        if($category->slug === 0){
                            $field->categories = 0;
                            break;
                        }
                        $categoriesTmp[] = $category->slug;
                    }
                    $field->categories = $categoriesTmp;
                }else{
                    $field->categories = 0;
                }

                if($field->dbTable){
                    $table = $field->dbTable->name;
                    $field->data = Language::filterRows(DB::table($table)->get(), false);
                    // if user add fullName as key
                    if($table == "users"){
                        $tmp = [];
                        foreach ($field->data as $userItem){
                            $userItem['fullName'] = $userItem['firstName']. " ". $userItem['lastName'];
                            $tmp[] = $userItem;
                        }
                        $field->data = $tmp;
                    }
                }
            }
            array_push($values, $field);
        }

        return $values;
    }

    /**
     * Joins post with category and selects only ones that have the specific category
     *
     * @param $queryObject
     * @param $categoryID
     * @param $postTypeSlug
     * @return mixed
     */
    private function selectByCategory($queryObject, $categoryID, $postTypeSlug){
        return $queryObject->join('categories_relations','categories_relations.belongsToID',$postTypeSlug.'.postID')
            ->where('categories_relations.categoryID', '=', $categoryID);
    }

    /**
     * Advanced search for posts
     * TODO use ElasticSearch
     *
     * @param object $obj
     * @param $data object data for the search (table name, title, userID, categoryID, from, to)
     * @param string $postTypeSlug
     * @return object result
     */
    public function advancedSearch($obj, $data, $postTypeSlug){
        // Title
        if($data->title != ""){
            $obj->where('title', 'like', '%'.trim($data->title).'%');
        }

        // User ID
        if($data->userID != 0){
            $obj->where('createdByUserID', $data->userID);
        }

        // Category
        if($data->categoryID != 0){
            $obj = $this->selectByCategory($obj, $data->categoryID, $postTypeSlug);
        }

        // From date
        if($data->from != ""){
            $obj->where('created_at', '>=', $data->from);
        }
        // To date
        if($data->to != ""){
            $obj->where('created_at', '<=', $data->to);
        }

        return $obj;
    }

    /**
     * Create event 'table_list_rows' to manipulate with data elsewhere
     *
     * @param string $postType
     * @return $this
     */
    private function appendListColumnsFromEvents(string $postType){
        Event::listen('post:table_list_rows', function ($results) use($postType){
            $rows = [];

            $inTableColumns = $this->getInTableColumns($postType);

            foreach($results as $key => $item){
                // title
                if(array_key_exists('title', $inTableColumns)){
                    $rows[$key]['title'] = $item->title;
                }

                // created at
                if(array_key_exists('created_at', $inTableColumns)){
                    $rows[$key]['created_at'] = $item->created_at;
                }

            }

            return $rows;
        });

        return $this;
    }

    /**
     * Appends event rows in table list
     * Manipulate with post data using: 'table_list_rows' event
     *
     * @param $paginationResult
     * @param string $postType
     * @return mixed
     */
    private function appendListRowsFromEvents($paginationResult, string $postType){
        $customListRows = Event::fire('post:table_list_rows', [$paginationResult, $postType]);

        foreach($customListRows as $customList){
            if(is_array($customList)) {
                foreach ($customList as $rowID => $rows) {
                    if (isset($paginationResult->items()[$rowID])) {
                        foreach ($rows as $key => $value) {
                            $paginationResult->items()[$rowID]->$key = $value;
                        }
                    }
                }
            }
        }

        return $paginationResult;
    }


    /**
     * Get all post of a category
     *
     * @param string $lang language slug
     * @param string $postType post type slug
     * @param integer $categoryID selected category ID
     *
     * @return array data of posts of a specific category
     */
    public function getAllPostsOfCategory($lang, $postType, $categoryID){
        // check if user has permissions to access this link
        if(!User::hasAccess($postType,'read')){
            return $this->noPermission();
        }
        // order info
        $orderBy = (isset($_GET['order'])) ? $_GET['order'] : 'postID';
        $orderType = (isset($_GET['type'])) ? $_GET['type'] : 'DESC';

        // get postIDs from relations
        $relations = DB::table('categories_relations')->where('categoryID', $categoryID)->select('belongsToID')->get();
        $postIDs = [];
        foreach ($relations as $postID){
            $postIDs[] = $postID->belongsToID;
        }
        // get post list
        $list = DB::table($postType)->whereIn('postID', $postIDs)->orderBy($orderBy, $orderType)->paginate(Post::$rowsPerPage);
        return Language::filterRows($list);
    }

    /**
     * Delete a Post (if it isn't being used)
     *
     * @param string $lang current language
     * @param string $postType slug of the post type of this post
     * @param integer $id of the post that is being deleted
     * @return array ErrorHandler
     * */
    public function delete($lang, $postType, $id){
        $deleteResult = $this->deletePost($id, $postType);
        if(gettype($deleteResult) == "boolean"){
            if($deleteResult){
                return $this->response('Post is deleted successfully');
            }
            return $this->response( 'Post could not be deleted. Please try again later or contact administrator', 500);
        }
        return $deleteResult;
    }

    /**
     * Bulk Delete posts (Delete many posts)
     *
     * @param Request $request array of posts IDs
     * @return array
     * */
    public function bulkDelete(Request $request){
        $postType = $request->all()[0];
        $ids = $request->all()[1];

        // if there are no item selected
        if (count($ids) <= 0) {
            return $this->response( 'Please select items to be deleted', 500);
        }
        // loop throw the item array (ids) and delete them
        foreach($ids as $id) {
            $deleteResult = $this->deletePost($id, $postType);
            if(gettype($deleteResult) == "boolean"){
                if(!$deleteResult){
                    return $this->response( 'Post could not be deleted. Please try again later or contact administrator', 500);
                }
            }else{
                return $deleteResult;
            }
        }
        return $this->response('Posts are deleted successfully');
    }

    /**
     * Delete a single post and all his relation data (related tags and categories)
     *
     * @param $id
     * @param $postType
     * @return array|bool
     */
    private function deletePost($id, $postType){
        // check if user has permissions to access this link
        if(!User::hasAccess($postType,'delete', $id, true)){
            return $this->noPermission();
        }

        // Check if this post type has posts
        // Post type should not be able to be deleted if it has posts
        if(Post::isInMenuLinks($id, $postType)){
            return $this->response( "You can't delete this Post because It is being used in menu links", 403);
        }

        $post = (new Post())->setTable($postType);
        $post = $post->find($id);

        if(!$post){
            // create delete task
            Task::create('post','delete', [$id], ['postType' => $postType]);
            return $this->response( 'Post doesn\'n exist in the database. If it exist in archive, it will be deleted after 10 min.', 500);
        }

        if ($post->delete()){
            // Delete categories relations of this post
            DB::table('categories_relations')->where('belongsTo', $postType)->where('belongsToID',$id)->delete();
            // Delete tags relations of this post
            DB::table('tags_relations')->where('belongsTo', $postType)->where('belongsToID',$id)->delete();
            return true;
        }
        return false;
    }

    /**
     * return view for search component
     *
     * @param string $lang language slug
     * @param string $postTypeSlug slug of the post type
     * @param string $term search term
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View view to search component
     */
    public function search($lang, $postTypeSlug, $term){
        // check if user has permissions to access this link
        if(!User::hasAccess($postTypeSlug,'read')){
            return view('errors.permissions');
        }

        return view('content');
    }

    /**
     *
     * Make simple search with a search term
     * TODO: use ElasticSearch
     *
     * @param string $lang language slug
     * @param string $postTypeSlug post type slug
     * @param string $term search term
     *
     * @return array search result
     *
     */
    public function makeSearch($lang, $postTypeSlug, $term){
        // check if user has permissions to access this link
        if(!User::hasAccess($postTypeSlug,'read')){
            return $this->noPermission();
        }


        $orderBy = (isset($_GET['order'])) ? $_GET['order'] : 'postID';
        $orderType  = (isset($_GET['type'])) ? $orderType = $_GET['type'] : 'DESC';

        $excludeColumns = array('remember_token', 'created_at', 'updated_at');
        // if archive is activated search in archive
        if(env('DB_ARCHIVE')){
            Search::setDatabaseConnection("mysql_archive");
        }
        $searchResults = Search::searchByTerm($postTypeSlug, $term, App\Models\Post::$rowsPerPage, true, [], $excludeColumns, $orderBy, $orderType);

        $response = $this
            ->appendListColumnsFromEvents($postTypeSlug)
            ->appendListRowsFromEvents($searchResults, $postTypeSlug)
            ->toArray();

        $response['inTableColumns'] = $this->getInTableColumns($postTypeSlug);

        return $response;
    }

    /**
     * This function is used to store the post in the database
     * Is used to store the media relations too
     *
     * Request $request (parameter) post Request object with all data from the custom fields:
     *      files - files array used to store the media relations (the files or images of a post)
     *      formData - All custom fields with their values
     *      languages - array with all languages where are the schedule date and time is stored
     *      onlyInLang - a object array with languages and the value that contains (true or false) if the post is available in the specific language
     *      postType - in which post type we are posting on
     *      ID (Only in update) - used when we are updating a post not storing a new one
     *
     * @param Request $request
     * @return array
     */
    public function store(Request $request){
        $data = $request->all();

        // check permissions
        if(isset($data['postID'])){
            if(!User::hasAccess($request->postType,'update', $data['postID'],true)){
                return $this->noPermission();
            }
        }else{
            if(!User::hasAccess($request->postType,'create')){
                return $this->noPermission();
            }
        }

        $results = $this->fillEmptyTitles($data);
        $data = $results['data'];
        $firstFilledTitle = $results['firstFilledTitle'];

        // generate a unique slug
        $postID = 0;
        if(isset($data['postID'])){
            $postID = $data['postID'];
        }
        $data['slug'] = $this->fillEmptySlugs($data['slug'], $data['postType'], $firstFilledTitle, $postID);

        try{
            $postResult = Post::store($data);

            if($postResult['error']){
                if(isset($postResult['errorMessages'])){
                    $result = $this->response("Please fill all required fields in all languages", 400, null, false, false, true, $postResult['errorMessages'], $postResult['noty']);
                }else{
                    $result = $this->response('You post could not be saved. Please try again later', 500, null, false, false, false, [], $postResult['noty']);
                }
            }else{
                $redirectParams = self::redirectParams($request->redirect, $postResult['postType'], $postResult['postID']);
                $result = $this->response( 'Your post was successfully saved', 200, $postResult['postID'], $redirectParams['view'], $redirectParams['redirectUrl'], false, [], $postResult['noty']);
            }

        }catch(\Exception $e){
            $result = $this->response($e->getMessage(), 500, null, false, false, false, [], []);
        }

        return $result;
    }

    /**
     * If title is sent empty in different languages (in store request) use the default -
     *      language title to fill the other ones
     *
     * @param $data array of post data
     * @return array post data with modified title and default language title
     */
    public function fillEmptyTitles($data){
        // get the first filled title
        $firstFilledTitle = "";
        foreach ($data['title'] as $title){
            if($title !== ''){
                $firstFilledTitle = $title;
                break;
            }
        }
        // if title is empty in a language where the language is not published fill the title with the first filled title of the multilanguage title array
        foreach ($data['title'] as $langKey => $title){
            if (isset($data['status'][$langKey]) && $data['status'][$langKey] != 'published'){
                if($title == ''){
                    $data['title'][$langKey] = $firstFilledTitle;
                }
            }
        }

        return [
            "data" => $data,
            "firstFilledTitle" => $firstFilledTitle
        ];
    }

    /**
     * Slug shouldn't be empty
     * Fills slug from default language to all other languages slugs
     *
     * @param $slugList array list of slugs for each language
     * @param $postType string post type
     * @param $defaultSlug string if slugs are empty use default slug
     * @param $id int id of post if update
     * @return array generated slugs for each language
     */
    public function fillEmptySlugs($slugList, $postType, $defaultSlug, $id = 0){
        $slugsFinal = array();
        foreach($slugList as $languageSlug => $slug){
            $tmpSlug = $slug;
            if($slug == ""){
                $tmpSlug = $defaultSlug;
            }
            $checkedSlug = parent::generateSlug($tmpSlug, $postType, 'postID', $languageSlug, $id, true);
            $slugsFinal[$languageSlug] = $checkedSlug;
        }
        return $slugsFinal;
    }

    /**
     * All required data for post update form
     *
     * @param $lang
     * @param $postTypeSlug
     * @param $id
     * @return array
     * @throws \Exception
     */
    public function detailsJSON($lang, $postTypeSlug, $id){
        // check if user has permissions to access this link
        if(!User::hasAccess($postTypeSlug,'update', $id, true)){
            return $this->noPermission();
        }

        // post type
        $currentPostType = PostType::findBySlug($postTypeSlug);
        // get post
        $post = (new Post())->setTable($postTypeSlug)->find($id);

        if(!$post){
            return $this->response("There is no post with ID: " . $id, 404);
        }
        // posts url
        $href = $post->href;

        // we need fields non-translated so we can play around with them in vuejs
        $post->setAutoTranslate(false);

        // the fields that are translatable
        $translatableFields = $this->getTranslatableFields($postTypeSlug);

        // get all related media and structure them for the post
        $media = $this->getPostRelatedMedia($id, $post, $postTypeSlug, $translatableFields);

        // handle custom fields
        $customFieldGroups = CustomFieldGroup::findGroups('post-type', 'update', $id, $postTypeSlug);
        $customFieldOBJ = new CustomField();
        if($post->customFields) {
            $customFieldOBJ->constructValues($customFieldGroups, $post->customFields);
            $media = array_merge($media, $customFieldOBJ->getMedia());
        }

        // Categories (options to select)
        $categories = array_values(Category::cache()->where("postTypeID", $currentPostType->postTypeID)->toArray());

        // selected categories of this post
        $selectedCategories = $this->getPostSelectedCategories($id, $postTypeSlug);

        // selected tags of this post
        $selectedTags = $this->getPostSelectedTags($id, $postTypeSlug);

        $response = array(
            'post' => [
                'title' => $post->title,
                'content' => $post->content,
                'status' => $post->status,
                'slug' => $post->slug,
                'href' => $href,
                'media' => $media,
                'published_at' => $post->published_at,
                'selectedCategories' => $selectedCategories,
                'postTypeID' => $currentPostType->postTypeID,
                'hasCategories' => $currentPostType->hasCategories,
                'isCategoryRequired' => $currentPostType->isCategoryRequired,
                'selectedTags' => $selectedTags,
                'hasTags' => $currentPostType->hasTags,
                'isTagRequired' => $currentPostType->isTagRequired,
                'isFeaturedImageRequired' => $currentPostType->isFeaturedImageRequired,
                'createdByUserID' => $post->createdByUserID,
            ],
            'customFieldsValues' => $customFieldOBJ->getCustomFieldValues(),
            'customFieldsGroups' => $customFieldGroups,
            'postTypeFieldsValues' => $this->getPostTypeFieldsValues($postTypeSlug, $post),
            'categories' => $categories,
            'languages' => Language::cache()->getItems(),
            'users' => User::cache()->getItems(),
        );

        // Fire event
        $response['events'] = Event::fire('post:pre_update', [$response]);

        return $response;
    }

    /**
     * Get all media related to a single post
     *
     * @param $id
     * @param $post
     * @param $postTypeSlug
     * @param $translatableFields
     * @return array
     */
    private function getPostRelatedMedia($id, $post, $postTypeSlug, $translatableFields){
        $media = array();

        // get the media relation joining the media table and the post
        $mediaRelationsResults = DB::table('media_relations')
            ->where("belongsTo", $postTypeSlug)
            ->join('media','media_relations.mediaID','media.mediaID')
            ->join($postTypeSlug,'media_relations.belongsToID',$postTypeSlug.'.postID')
            ->where("belongsToID", $id)
            ->select('media.title as title', 'media.mediaID', 'media_relations.mediaRelationID', 'media_relations.belongsTo', 'media_relations.belongsToID', 'media_relations.language',
                'media.description', 'media.credit', 'media.type', 'media.extension',
                'media.url', 'media.filename', 'media.fileDirectory', 'media.filesize', 'media.dimensions', 'media_relations.field')
            ->get();


        // the object media used for the media custom fields in the front end
        if ($post->featuredImageID){
            $featuredImage = Media::find($post->featuredImageID);
            // set feature image if it exist
            if($featuredImage){
                $media["featuredImage"][] = $featuredImage;
            }
        }
        if ($post->featuredVideoID){
            $featuredVideo = Media::find($post->featuredVideoID);
            $media["featuredVideo"][] = $featuredVideo;
        }

        // loop throw the media relation and construct the $media object
        foreach ($mediaRelationsResults as $relation){
            if(in_array($relation->field, $translatableFields)){
                $langArr = json_decode($relation->language);
                foreach ($langArr as $langKey => $lang){
                    if(!isset($media[$relation->field."__lang__".$langKey])){
                        $media[$relation->field."__lang__".$langKey] = array();
                    }
                    if ($lang){
                        array_push($media[$relation->field."__lang__".$langKey], $relation);
                    }
                }
            }else{
                if(!isset($media[$relation->field])){
                    $media[$relation->field] = array();
                }
                array_push($media[$relation->field], $relation);
            }
        }

        return $media;
    }

    /**
     * Get selected categories of a single post
     *
     * @param $id
     * @param $postTypeSlug
     * @return array
     */
    private function getPostSelectedCategories($id, $postTypeSlug){
        // get the selected categories from the DB table categories_relations
        $selectedCategories = DB::table('categories_relations')
            ->leftJoin('categories','categories_relations.categoryID','categories.categoryID')
            ->where('categories_relations.belongsTo', $postTypeSlug)
            ->where('categories_relations.belongsToID', $id)
            ->select("categories.categoryID", "categories.postTypeID", "categories.title", "categories.slug")
            ->get();

        return Language::filterRows($selectedCategories, false);
    }

    /**
     * Get selected tags of a single post
     *
     * @param $id
     * @param $postTypeSlug
     * @return array
     */
    private function getPostSelectedTags($id, $postTypeSlug){
        // get selected tags from the DB table tags_relations
        $selectedTags = [];
        $tagsRelations = DB::table('tags_relations')
            ->leftJoin('tags','tags_relations.tagID','tags.tagID')
            ->where('tags_relations.belongsTo', $postTypeSlug)
            ->where('tags_relations.belongsToID', $id)
            ->select("tags.tagID", "tags_relations.language", "tags.title", "tags.slug")
            ->get();

        foreach($tagsRelations as $tagsRelation){
            if(!isset($selectedTags[$tagsRelation->language])){
                $selectedTags[$tagsRelation->language] = [];
            }
            $selectedTags[$tagsRelation->language][] = $tagsRelation;
        }

        return $selectedTags;
    }


    /**
     * Get fields for advanced search
     *
     * @param string $lang language slug
     * @param string $postTypeSlug post type slug
     *
     * @return array fields that are used to make advanced search for a model
     */
    public function getAdvancedSearchFields($lang, $postTypeSlug){
        return Post::getAdvancedSearchFields($postTypeSlug);
    }

    /**
     * This function creates the slug for a category and makes sure that slugs it is not being used from a other category
     *
     * @param string $lang language slug
     * @param string $postType post type slug
     * @param string $slug text to be made slug
     *
     * @return string generated slug
     */
    public function getSlug($lang, $postType, $slug){
        return parent::generateSlug($slug, $postType, 'postID', $lang, 0, true);
    }

    /**
     * @inheritdoc
     * */
    public function redirectParams($redirectChoice, $belongsTo = '', $id = 0, $customViews = []){
        $adminPrefix = Config::get('project')['adminPrefix'];
        if($redirectChoice == 'save'){
            $redirectUrl = "/".$adminPrefix."/".App::getLocale()."/posts/$belongsTo/update/".$id;
            $view = 'update';
        }else if($redirectChoice == 'close'){
            $redirectUrl = "/".$adminPrefix."/".App::getLocale()."/posts/$belongsTo/list";
            $view = 'list';
        }else{
            $redirectUrl = "/".$adminPrefix."/".App::getLocale()."/posts/$belongsTo/create";
            $view = 'create';
        }

        return array('redirectUrl' => $redirectUrl, 'view' => $view);
    }


    /**
     * Get posts to be displayed in menu panel
     *
     * @param string $lang
     * @param string $postType Slug of post type
     * @return array
     * */
    public function menuPanelItems($lang = "", $postType){
        // check if user has permissions to access this link
        if(!User::hasAccess('PostType','read')){
            return $this->noPermission();
        }

        // Find posts
        $postsObj = (new Post())->setTable($postType);
        $postsObj = $postsObj->published();
        if(Input::input('keyword')){
            $postsObj->where('title->'.App::getLocale(), 'like', '%'.Input::input('keyword').'%');
        }
        $postsObj->orderBy('created_at', 'DESC');
        $posts = $postsObj->paginate(25);

        // Append menuLink Parameters
        foreach($posts->all() as $post){
            $post->setAutoTranslate(false);
            $post->belongsTo = $postType;
            $post->belongsToID = $post->postID;
            $post->label = $post->title;
            $post->menuLinkParameters = $post->menuLinkParameters();
        }

        // Append columns
        $results = $posts->toArray();
        $results['columns']= [
            'postID' => trans('id'),
            'title' => trans('base.title'),
        ];

        return $results;
    }
}