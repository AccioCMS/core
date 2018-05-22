<?php

namespace Accio\App\Http\Controllers\Backend;

use App;
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
use App\Models\CustomFieldGroup;

class BasePostController extends MainController {
    // Check authentification in the constructor
    public function __construct(){
        parent::__construct();
        $this->middleware('auth');
    }

    /**
     * Get all data needed in the post create form
     * @param $lang: language
     * @param $postTypeSlug
     * @return array
     */
    public function getDataForCreate($lang, $postTypeSlug){
        // Languages
        $languages = Language::getFromCache();
        // Custom field groups
        $customFieldsGroups = CustomFieldGroup::findGroups('post-type', 'create', 0, $postTypeSlug);
        // post type
        $postType = PostType::getFromCache()->where('slug', $postTypeSlug)->first();
        // Categories (options to select)
        $categories = array_values(App\Models\Category::getFromCache()->where("postTypeID", $postType->postTypeID)->toArray());
        // get columns
        $columns = $this->getColumns('en', $postTypeSlug);

        return[
            'postType' => $postType,
            'languages' => $languages,
            'categories' => $categories,
            'customFieldsGroups' => $customFieldsGroups,
            'column' => $columns['column'],
            'inTableColumnsSlugs' => $columns['inTableColumnsSlugs'],
            'allColumn' => $columns['allColumn'],
        ];
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
        $postsObj = new Post();
        $postsObj->setTable($postType);
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

    /**
     * @param $lang
     * @param $post_type
     * @param $view
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function postsIndex($lang, $post_type, $view){
        // get the post type information
        $adminPrefix = Config::get('project')['adminPrefix'];

        $postType = PostType::findBySlug($post_type);
        if(!$postType){
            return response()->view('errors.404', ['message' => "This post type does not exist!"], 404);
        }

        $id = $postType->postTypeID;

        $isPostView = true; // used when generate language menu (language meu that changes the locate in backend)

        // check if user has permissions to access this link
        $key = ($view == 'list') ? 'read' : $view;
        if(!User::hasAccess($post_type,$key)){
            return view('errors.permissions', compact('lang','view','id','post_type','isPostView','isSinglePostView','adminPrefix'));
        }

        return view('content');
    }

    /**
     * This method loads single post view (like update etc.)
     * */
    public function postsSingle($lang, $post_type, $view, $id){
        $adminPrefix = Config::get('project')['adminPrefix'];
        $isPostView = true; // used when generate language menu (language menu that changes the locate in backend)
        $isSinglePostView = true; // used when generate language menu (language meu that chenges the locate in backend)

        // check if user has permissions to access this link
        if(!User::hasAccess($post_type,$view, $id, true)){
            $message = "You can't edit this post because you don't own it!";
            return view('errors.permissions', compact('message','lang','view','id','post_type','isPostView','isSinglePostView','adminPrefix'));
        }
        return view('content');
    }

    /**
     * Get the column names
     * the column names of the specific post type
     *
     * @param $lang
     * @param $postType
     * @return array
     */
    public function getColumns($lang, $postType, $post = ""){
        // check if user has permissions to access this link
        if(!User::hasAccess($postType,'read')){
            return $this->noPermission();
        }
        // fields of this post type
        $fields = App\Models\PostType::getFields($postType);
        // languages
        $languages = Language::getFromCache();

        $column = array();
        $allColumn = array();
        $inTableColumnsSlugs = array();
        $translatableFields = array();

        foreach ($fields as $field){
            if($field->inTable){
                // add columns that should appear in table
                array_push($column, $field);
                array_push($inTableColumnsSlugs, $field->slug);
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
                    // populate the $translatableFields with the slugs of the fields that can be translated to use it below for the media array
                    array_push($translatableFields, $slug);
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

                if(count($field->dbTable)){
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
            array_push($allColumn, $field);
        }
        return array('column' => $column, 'inTableColumnsSlugs' => $inTableColumnsSlugs, 'allColumn' => $allColumn, 'translatableFields' => $translatableFields);
    }


    /**
     * Get all post of a post type
     *
     * @param string $lang language slug
     * @param string $postType post type slug
     * @return array post data
     */
    public function getAllPosts($lang, $postType){
        $inTableColumnSlugs = self::getColumns($lang, $postType)['inTableColumnsSlugs'];
        // check if user has permissions to access this link
        if(!User::hasAccess($postType,'read')){
            return $this->noPermission();
        }

        // get order from link if they are set
        $orderBy = (isset($_GET['order'])) ? $orderBy = $_GET['order'] : 'postID';
        $orderType = (isset($_GET['type'])) ? $orderType = $_GET['type'] : 'DESC';

        // if the posts are filtered by menu link ID // it means we are searching only for related posts
        if(isset($_GET['menu_link_id']) && $_GET['menu_link_id'] != '' && isset($_GET['related'])){
            $menuLinkID = $_GET['menu_link_id'];
            $postTypes = PostType::getFromCache();

            // get the post type of the related link
            $currentPostType = '';
            foreach ($postTypes as $item){
                if($item->slug == $postType){
                    $currentPostType = $item;
                    break;
                }
            }
            // if post type does't exist throw a error
            if($currentPostType == ''){
                return $this->response("This post type doesn't exist", 403);
            }else{
                // get relation throw the post type ID
                $menuRelation = App\Models\MenuLinkConfig::where('menuLinkID', $menuLinkID)->where('belongsToID', $currentPostType->postTypeID)->first();
                if($menuRelation->postIDs === NULL){ // if there are no postIDs return the list of all posts
                    $paginationResult = DB::table($postType)->orderBy($orderBy, $orderType)->paginate(Post::$rowsPerPage);
                }else{ // if there are post IDs return only those posts
                    $postIDs = array();
                    if(is_array(json_decode($menuRelation->postIDs)) || is_object(json_decode($menuRelation->postIDs))){
                        foreach (json_decode($menuRelation->postIDs) as $selectedPostID){
                            array_push($postIDs,$selectedPostID);
                        }
                    }
                    $paginationResult = DB::table($postType)->whereIn('postID', $postIDs)->orderBy($orderBy, $orderType)->paginate(Post::$rowsPerPage);
                }
            }
        }else{ // the normal list of posts
            $paginationResult = DB::table($postType)->orderBy($orderBy, $orderType)->paginate(Post::$rowsPerPage);
        }

        // ORDER IF JSON COLUMN
        if(isset($_GET['order'])){
            $columnType = DB::connection()->getDoctrineColumn($postType, $orderBy)->getType()->getName();
            if($columnType == "json"){
                if(strtoupper($orderType) == "ASC"){
                    $orderResult = $paginationResult->sortBy(function($post) use ($orderBy, $lang){
                        return json_decode($post->$orderBy)->$lang;
                    });
                }elseif(strtoupper($orderType) == "DESC"){
                    $orderResult = $paginationResult->sortByDesc(function($post) use ($orderBy, $lang){
                        return json_decode($post->$orderBy)->$lang;
                    });
                }

                $paginationResult = $paginationResult->toArray();
                $paginationResult['data'] =  $orderResult;
            }
        }
        return Language::filterRows($paginationResult, true, true, $inTableColumnSlugs);
    }


    /**
     * Get all post of a category
     *
     * @param string $lang language slug
     * @param string $postType post type slug
     * @param integer $categoryID selected category ID
     *
     * @return array data of posts of a specific category
     *
     */
    public function getAllPostsOfCategory($lang, $postType, $categoryID){
        // check if user has permissions to access this link
        if(!User::hasAccess($postType,'read')){
            return $this->noPermission();
        }

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
     * Delete a Post
     *
     * @param string $lang current language
     * @param string $postType slug of the post type of this post
     * @param integer $id of the post that is being deleted
     *
     * @return array ErrorHandler
     *
     * */
    public function delete($lang, $postType, $id){
        // check if user has permissions to access this link
        if(!User::hasAccess($postType,'delete', $id, true)){
            return $this->noPermission();
        }

        // Check if this post type has posts
        // Post type should not be able to be deleted if it has posts
        if(Post::isInMenuLinks($id, $postType)){
            return $this->response( "You can't delete this Post becauase It is being used in menu links", 403);
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
            DB::table('categories_relations')->where('belongsTo',$postType)->where('belongsToID',$id)->delete();

            // Delete tags relations of this post
            DB::table('tags_relations')->where('belongsTo',$postType)->where('belongsToID',$id)->delete();

            $result = $this->response( 'Post Type is successfully deleted');

        }else{
            $result = $this->response( 'Internal server error. Please try again later', 500);
        }
        return $result;
    }

    /**
     * Bulk Delete posts
     * Delete many posts
     *
     * @param Request $request array of posts IDs
     *
     * @return array
     *
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
            // check if user has permissions to access this link
            if(!User::hasAccess($postType,'delete', $id, true)){
                return $this->noPermission();
            }

            // Check if this post type has posts
            // Post type should not be able to be deleted if it has posts
            if(Post::isInMenuLinks($id, $postType)){
                return $this->response( "You can't delete this Post. It is being used in menu links", 403);
            }

            $postObj = (new Post())->setTable($postType);
            $post = $postObj->find($id);
            $deletePost = $post->delete();

            if (!$deletePost) {
                // create delete task
                Task::create('post','delete', $post, ['postType' => $postType]);
            }else{
                // get categories IDs to delete posts from cache
                $categoryIDs = DB::table('categories_relations')->where('belongsTo',$postType)->where('belongsToID',$id)->select('categoryID')->get()->toArray();
                // delete categories relations of this post
                DB::table('categories_relations')->where('belongsTo',$postType)->where('belongsToID',$id)->delete();
                // delete tags relations of this post
                DB::table('tags_relations')->where('belongsTo',$postType)->where('belongsToID',$id)->delete();

            }
        }
        return $this->response('Post Types are deleted successfully');
    }

    /**
     * return view for search component
     *
     * @param string $lang language slug
     * @param string $post_type slug of the post type
     * @param string $term search term
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View view to search component
     */
    public function search($lang, $post_type, $term){
        $adminPrefix = Config::get('project')['adminPrefix'];
        $isPostView = true; // used when generatin language menu (language meu that changes the locate in backend)
        $view = 'search';

        // check if user has permissions to access this link
        if(!User::hasAccess($post_type,'read')){
            return view('errors.permissions', compact('lang','view','id','post_type','isPostView','isSinglePostView','adminPrefix'));
        }

        return view('content');
    }

    /**
     *
     * Make simple search with a search term
     *
     * @param string $lang language slug
     * @param string $post_type post type slug
     * @param string $term search term
     *
     * @return array search result
     *
     */
    public function makeSearch($lang, $post_type, $term){
        $inTableColumnSlugs = self::getColumns($lang, $post_type)['inTableColumnsSlugs'];

        // check if user has permissions to access this link
        if(!User::hasAccess($post_type,'read')){
            return $this->noPermission();
        }

        $pagination = '';
        if(isset($_GET['pagination'])){
            $pagination = $_GET['pagination'];
        }

        $orderBy = (isset($_GET['order'])) ? $_GET['order'] : 'postID';
        $orderType  = (isset($_GET['type'])) ? $orderType = $_GET['type'] : 'DESC';

        $excludeColumns = array('remember_token', 'created_at', 'updated_at');
        // if archive is activated search in archive
        if(env('DB_ARCHIVE')){
            Search::setDatabaseConnection("mysql_archive");
        }
        $searchResults = Search::searchByTerm($post_type, $term, App\Models\Post::$rowsPerPage, true, [], $excludeColumns, $orderBy, $orderType);
        return Language::filterRows($searchResults, true, $inTableColumnSlugs);
    }

    /**
     *  This function is used to store the post in the database
     *  Is used to store the media relations too
     *
     *  @param Request $request post Request object with all data from the custom fields :
     *      files - files array used to store the media relations (the files or images of a post)
     *      formData - All custom fields with their values
     *      languages - array with all languages where are the schedule date and time is stored
     *      onlyInLang - a object array with languages and the value that contains (true or false) if the post is available in the specific language
     *      postType - in which post type we are posting on
     *      ID (Only in update) - used when we are updating a post not storing a new one
     *
     *  @return array
     *
     * */
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

        }catch (\Exception $e){
            $result = $this->response($e->getMessage(), 500, null, false, false, false, [], []);
        }

        return $result;
    }

    /**
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
     * @param $lang
     * @param $post_type
     * @param $id
     *
     * @return array JSON object with details for a specific post type
     */
    public function detailsJSON($lang, $post_type, $id){
        // check if user has permissions to access this link
        if(!User::hasAccess($post_type,'update', $id, true)){
            return $this->noPermission();
        }
        // languages
        $languages = Language::getFromCache();

        // Get post
        $post = new Post();
        $post->setTable($post_type);
        $post = $post->find($id);

        if(!$post){
            $post = new Post();
            $post->setConnection('mysql_archive');
            $post = $post->find($id);
            if(!$post){
                return $this->response("There is no post with ID: " . $id, 404);
            }

        }
        // request the url of the post
        $href = $post->href;

        $post->setAutoTranslate(false);

        $columns = $this->getColumns($lang, $post_type, $post);

        $column = $columns['column']; // single column - used temporary in the loop
        $allColumn = $columns['allColumn']; // all columns - the form object used to construct the fields in the frontend
        $inTableColumnsSlugs = $columns['inTableColumnsSlugs']; // get the columns that should appear in the table
        $translatableFields = $columns['translatableFields'];  // the fields that are translatable

        // get the media relation joining the media table and the post
        $mediaRelationsResults = DB::table('media_relations')
            ->where("belongsTo", $post_type)
            ->join('media','media_relations.mediaID','media.mediaID')
            ->join($post_type,'media_relations.belongsToID',$post_type.'.postID')
            ->where("belongsToID", $id)
            ->select('media.title as title', 'media.mediaID', 'media_relations.mediaRelationID', 'media_relations.belongsTo', 'media_relations.belongsToID', 'media_relations.language',
                'media.description', 'media.credit', 'media.type', 'media.extension',
                'media.url', 'media.filename', 'media.fileDirectory', 'media.filesize', 'media.dimensions', 'media_relations.field')
            ->get();

        $media = array(); // the object media used for the media custom fields in the front end
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

        foreach ($mediaRelationsResults as $relation){ // loop throw the media relation and construct the $media object
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

        // get the selected categories from the DB table categories_relations
        $selectedCategories = DB::table('categories_relations')
            ->leftJoin('categories','categories_relations.categoryID','categories.categoryID')
            ->where('categories_relations.belongsTo',$post_type)
            ->where('categories_relations.belongsToID',$id)
            ->get();

        $selectedCategories = Language::filterRows($selectedCategories, false);

        // get selected tags from the DB table tags_relations
        $selectedTags = [];
        $tagsRelations = DB::table('tags_relations')
            ->leftJoin('tags','tags_relations.tagID','tags.tagID')
            ->where('tags_relations.belongsTo',$post_type)
            ->where('tags_relations.belongsToID',$id)
            ->get();

        foreach ($tagsRelations as $tagsRelation){
            if(!isset($selectedTags[$tagsRelation->language])){
                $selectedTags[$tagsRelation->language] = [];
            }
            $selectedTags[$tagsRelation->language][] = $tagsRelation;
        }

        // handle custom fields
        $customFieldGroups = CustomFieldGroup::findGroups('post-type', 'update', $id, $post_type);
        $customFieldOBJ = new CustomField();
        if($post->customFields) {
            $customFieldOBJ->constructValues($customFieldGroups, $post->customFields);
            $media = array_merge($media, $customFieldOBJ->getMedia());
        }

        // post type
        $currentPostType = PostType::findBySlug($post_type);
        // Categories (options to select)
        $categories = array_values(App\Models\Category::getFromCache()->where("postTypeID", $currentPostType->postTypeID)->toArray());

        $response = array(
            'details' => $allColumn,
            'title' => $post->title,
            'content' => $post->content,
            'status' => $post->status,
            'customFieldsValues' => $customFieldOBJ->getCustomFieldValues(),
            'customFieldsGroups' => $customFieldGroups,
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
            'categories' => $categories,
            'languages' => $languages,
        );
        
        // Fire event
        $response['events'] = Event::fire('post:pre_update', [$response]);
        return $response;
    }

    /**
     *
     * @param string $lang language slug
     * @param string $post_type post type slug
     *
     * @return array fields that are used to make advanced search for a model
     *
     */
    public function getAdvancedSearchFields($lang, $post_type){
        return Post::getAdvancedSearchFields($post_type);
    }

    /**
     *
     * @param Request $request
     *
     * @return array result of the advanced search
     *
     */
    public function advancedSearch(Request $request){
        $searchResults = Post::advancedSearch($request)->orderBy($request->orderBy, $request->orderType)->paginate(Post::$rowsPerPage, ['*'], 'page', $request->page);
        return Language::filterRows($searchResults);
    }

    public function getAllPostsWithoutPagination($lang = "", $postType){
        $posts = DB::table($postType)->get();
        return Language::filterRows($posts, false);
    }

    /**
     * This function creates the slug for a category and makes sure that slugs it is not being used from a other category
     *
     * @param string $lang language slug
     * @param string $postType post type slug
     * @param string $slug text to be made slug
     *
     * @return string generated slug
     *
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

}
