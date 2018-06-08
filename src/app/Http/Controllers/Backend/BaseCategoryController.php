<?php

namespace Accio\App\Http\Controllers\Backend;

use App;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Input;
use Validator;
use App\Models\MenuLink;
use App\Models\CustomFieldGroup;
use App\Models\CustomField;
use App\Models\Language;
use App\Models\Media;
use App\Models\User;
use App\Models\PostType;
use App\Models\Category;
use Accio\Support\Facades\Search;
use Illuminate\Http\Request;

class BaseCategoryController extends MainController {
    // Check authentification in the constructor
    public function __construct(){
        parent::__construct();
        $this->middleware('auth');
    }

    /**
     * Get categories to be displayed in menu panel
     *
     * @param string $lang
     * @return array
     * */
    public function menuPanelItems($lang = ""){
        // check if user has permissions to access this link
        if(!User::hasAccess('PostType','read')){
            return $this->noPermission();
        }

        // Find categories
        $categoryObj = Category::visible();
        if(Input::input('keyword')){
            $categoryObj->where('title', 'like', '%'.Input::input('keyword').'%');
        }
        $categoryObj->orderBy('postTypeID', 'ASC');
        $categories = $categoryObj->paginate(25);

        // Append menuLink Parameters
        foreach($categories->all() as $category){
            $postTypeData = PostType::findByID($category->postTypeID);
            $category->postTypeName = $postTypeData->name;
            $category->belongsTo = 'category';
            $category->belongsToID = $category->categoryID;
            $category->label = $category->title;
            $category->menuLinkParameters = $category->menuLinkParameters();
        }

        // Append columns
        $results = $categories->toArray();
        $results['columns']= [
            'categoryID' => trans('id'),
            'title' => trans('base.title'),
            'postTypeName' => trans('postTypes.title'),
        ];

        return $results;
    }

    /**
     * @return array list of categories for a specific post type
     * */
    public function getAllByPostType($lang = "", $postTypeID){
        $orderBy = (isset($_GET['order'])) ? $orderBy = $_GET['order'] : 'order';
        $orderType = (isset($_GET['type'])) ? $orderType = $_GET['type'] : 'ASC';
        $list = Category::where('postTypeID',$postTypeID)->where('parentID', null)->orWhere('parentID', 0)->orderBy($orderBy, $orderType)->paginate(Category::$rowsPerPage);
        return Language::filterRows($list);
    }

    /**
     * Gets categories by pust type with parent child relations
     *
     * @param string $lang
     * @param $postTypeID
     * @return array
     */
    public function getTree($lang = "", $postTypeID){
        $orderBy = (isset($_GET['order'])) ? $orderBy = $_GET['order'] : 'order';
        $orderType = (isset($_GET['type'])) ? $orderType = $_GET['type'] : 'ASC';
        $parentList = Category::where('postTypeID',$postTypeID)->where('parentID', null)
          ->orWhere('parentID', 0)
          ->orderBy($orderBy, $orderType)
          ->paginate(Category::$rowsPerPage);

        $category = new Category();

        // get all categories of the selected post type
        $category->categoryList = Category::where('postTypeID',$postTypeID)->orderBy($orderBy, $orderType)->get();
        
        // make the parent child format tree
        $treeList = collect($category->makeChildrenTree($category->categoryList));

        // get only categories selected for this page (pagination)
        $tmp = [];
        foreach($parentList->items() as $item){
            $getCategory = $treeList->where('categoryID', $item->categoryID)->first();
            if($getCategory){
                $tmp[] = $getCategory;
            }
        }

        $parentList->setCollection(collect($tmp));

        return Language::filterRows($parentList);
    }


    /**
     * This function returns list of all categories
     * */
//    public function getAll($lang = ""){
//        $orderBy = '';
//        $orderType = '';
//        if(isset($_GET['order'])){
//            $orderBy = $_GET['order'];
//        }
//        if(isset($_GET['type'])){
//            $orderType = $_GET['type'];
//        }
//
//        // join parameters for the query
//        // we are left joinin the the media table
//        $joins = array(
//            [
//                'table' => 'post_type',
//                'type' => 'left',
//                'whereTable1' => "postTypeID",
//                'whereTable2' => "postTypeID",
//            ]
//        );
//        $list = Pagination::make('categories', App\Models\Category::$rowsPerPage, '', '', $orderBy, $orderType, $joins);
//        return Language::filterRows($list);
//    }

    /**
     * Get latest categories
     * */
    public function getLatest($lang = ""){
        $result = DB::table("categories")
            ->join('post_type', 'categories.postTypeID', '=', 'post_type.postTypeID')
            ->select('categories.*', 'post_type.name')
            ->get();

        return Language::filterRows($result, false);
    }

    /**
     * Delete a Category and his children
     *
     * @param $lang
     * @param $id
     * @return array response
     */
    public function delete($lang, $id){
        if(!User::hasAccess('Categories','delete')){
            return $this->noPermission();
        }

        $category = Category::find($id);
        if($category) {
            $postType = PostType::findByID($category->postTypeID);

            // Post type should not be able to be deleted if it has posts
            if (Category::isInMenuLinks($id)) {
                return $this->response("You can't delete this Category because it is part of a menu.", 403);
            }

            // delete children
            $category->deleteChildren($category->categoryID, $postType->postTypeID);

            // delete relations
            DB::table('categories_relations')->where("categoryID", $category->categoryID)->delete();

            // Delete the item
            if ($category->delete()){
                return $this->response('Category is deleted successfully');
            }
        }

        return $this->response('Category could not be deleted. Please try again later or contact your Administrator!', 500);
    }


    /**
     * Bulk Delete categories
     * Delete many categories
     *
     * @param Request $request array of category IDs
     * @return array
     */
    public function bulkDelete(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('Categories','delete')){
            return $this->noPermission();
        }

        // if there are no item selected
        if (count($request->all()) <= 0) {
            return $this->response( 'Please select items to be deleted', 500);
        }

        // loop throw the items
        foreach ($request->all() as $id) {
            $category = Category::find($id);

            $postType = PostType::findByID($category->postTypeID);
            if(Category::isInMenuLinks($id)){
                return $this->response("You can't delete this Category. It is part of a menu", 403);
            }

            // delete children
            $category->deleteChildren($category->categoryID, $postType->postTypeID);

            // delete relations
            DB::table('categories_relations')->where("categoryID", $category->categoryID)->delete();

            // Delete the item
            if(!$category->delete()){
                return $this->response('Category '.$category->categoryID.'  could not be deleted therefore the deleting process has been stopped. Please try again later or contact your Administrator!', 500);
            }
        }

        return $this->response('Selected categories are successfully deleted');
    }

    /**
     *  Store a new Category in database
     *  @param Request $request all category data comming from the form
     *  @return array
     * */
    public function store(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('Categories','create')){
            return $this->noPermission();
        }

        $data = $request->all();
        $form = $data['form'];
        $formDataResult = array();
        $errors = [];
        $requiredMessage = " is required";

        $defaultLanguage = Language::getDefault();
        $defaultLanguageTitle = '';
        // title in default language should no be empty
        if(
            isset($form['title']) &&
            isset($form['title'][$defaultLanguage->slug]) &&
            $form['title'][$defaultLanguage->slug] != ""){
                $defaultLanguageTitle = $form['title'][$defaultLanguage->slug];
        }else{
            $errors['title_'.$defaultLanguage['slug']] = array('Title '.$requiredMessage. " in ".$defaultLanguage['name']);
        }

        // loop throw data in each language and generate title and slug if there are empty
        $languages = $data['languages'];
        foreach($form as $formDataKey => $formDataValue){
            foreach($languages as $language){
                if($formDataKey == "title" && !$language['isDefault']){
                    if($formDataValue[$language['slug']] == ""){
                        if($defaultLanguageTitle){
                            $form[$formDataKey][$language['slug']] = $defaultLanguageTitle;
                        }
                    }
                }

                if($formDataKey == "slug"){
                    if($formDataValue[$language['slug']] == ""){
                        $title = $form['title'][$language['slug']];
                        // if new category
                        if(!isset($form['categoryID'])){
                            $id = 0;
                        }else{
                        // if category being updated
                            $id = $data['id'];
                        }
                        $newSlug = parent::generateSlug($title, 'categories', 'categoryID', App::getLocale(), 0, true);
                        $form['slug'][$language['slug']] = $newSlug;
                    }
                }
            }
        }

        // return errors if there are any
        if(count($errors)){
            return $this->response( "From errors", 400, null, false, false, true, $errors );
        }

        // if category is being created
        if(!isset($form['categoryID'])){
            // Order item
            $catCount = Category::where("postTypeID", $request->postTypeID)->count();
            $order = $catCount+1;
            $categoryModel = new Category();
        }else{
        // if category is being updated
            $categoryModel = Category::find($form['categoryID']);
            if(!$categoryModel){
                return $this->response( "Category doesn't exist", 500);
            }
            $order = $categoryModel->order;
            $oldTitle = $categoryModel->setAutoTranslate(false)->title;
        }

        // parent ID
        $parentID = 0;
        if(isset($form['parent']['categoryID'])){
            $parentID = $form['parent']['categoryID'];
        }

        // Create Category
        $categoryModel->title = $form['title'];
        $categoryModel->postTypeID = $data['postTypeID'];
        $categoryModel->parentID = $parentID;
        $categoryModel->featuredImageID = $form['featuredImageID'];
        $categoryModel->description = $form['description'];
        $categoryModel->slug = $form['slug'];
        $categoryModel->isVisible = $form['isVisible'];
        $categoryModel->order = $order;
        $categoryModel->createdByUserID = Auth::user()->userID;
        $categoryModel->customFields = $request->customFieldValues;

        // return results
        if ($categoryModel->save()){
            if(isset($form['categoryID'])){
                // update label in menu link with the category title
                $this->updateMenuLinkLabel($data['id'], $form['title'], $oldTitle);
            }

            $adminPrefix = Config::get('project')['adminPrefix'];
            if($request->redirect == 'save'){
                $redirectUrl = "/".$adminPrefix."/".App::getLocale()."/post-type/categoryupdate/".$categoryModel->categoryID;
                $view = 'categoryupdate';
                $redirectID = $categoryModel->categoryID;
            }else if($request->redirect == 'close'){
                $redirectUrl = "/".$adminPrefix."/".App::getLocale()."/post-type/categorylist/".$data['postTypeID'];
                $view = 'categorylist';
                $redirectID = $data['postTypeID'];
            }else{
                $redirectUrl = "/".$adminPrefix."/".App::getLocale()."/post-type/categorycreate/".$data['postTypeID'];
                $view = 'categorycreate';
                $redirectID = $data['postTypeID'];
            }
            $response = $this->response('Category is saved', 200, $redirectID, $view, $redirectUrl);
        }else{
            $response = $this->response( 'Category is not saved. Please try again later!', 500);
        }
        return $response;
    }

    /**
     * @param $id integer id of the category that is being updated
     * @param $newTitle object of the edited title
     * @param $oldTitle object old category title
     */
    private function updateMenuLinkLabel($id, $newTitle, $oldTitle){
        $menuLinksList = MenuLink::where('belongsToID', $id)->where('belongsTo','category')->get();

        $langSlug = App::getLocale();
        if($menuLinksList->count()){
            foreach ($menuLinksList as $menuLink){
                $updateLabel = false;
                foreach (Language::getFromCache() as $lang){
                    $langSlug = $lang->slug;
                    if(isset($oldTitle->App) && $oldTitle->$langSlug == $menuLink->label->$langSlug){
                        $updateLabel = true;
                    }
                }

                if($updateLabel){
                    $link = MenuLink::find($menuLink->menuLinkID);
                    $link->label = $newTitle;
                    $link->save();
                }
            }
        }
    }

    /**
     * return JSON object with details for a specific category
     * @params categoryID
     * */
    public function detailsJSON($lang, $id){
        // check if user has permissions to access this link
        if(!User::hasAccess('Categories','update')){
            return $this->noPermission();
        }

        $category = Category::find($id);
        $featuredImage = Media::find($category->featuredImageID);
        if($category) {
            $media = [];
            if ($featuredImage){
                $media['feature_image'] = [$featuredImage];
            }

            // get parent title and id translated
            if($category->parent){
                $parentCategoryID = $category->parent->categoryID;
                $parentTitle = $category->parent->title;
                // get parent
                $category->parentCategory = [
                    'categoryID' => $parentCategoryID,
                    'title' => $parentTitle,
                ];
            }

            // handle custom fields
            $customFieldGroups = CustomFieldGroup::findGroups('category', 'create', $id, 'none');
            $customFieldOBJ = new CustomField();
            if($category->customFields){
                $customFieldOBJ->constructValues($customFieldGroups, $category->customFields);
                $media = array_merge($media, $customFieldOBJ->getMedia());
            }

            $final = array(
                'details' => $category,
                'languages' => Language::getFromCache(),
                'media' => $media,
                'customFieldsValues' => $customFieldOBJ->getCustomFieldValues(),
                'customFieldsGroups' => $customFieldGroups,
            );

            // Fire event
            $final['events'] = Event::fire('category:pre_update', [$final]);
        }else{
            $final = array(
                'error' => 'Category not found'
            );
        }
        return $final;
    }

    /**
     * Make simple search with a search term
     * */
    public function makeSearch($lang, $postTypeID, $term){
        // check if user has permissions to access this link
        if(!User::hasAccess('Categories','read')){
            return $this->noPermission();
        }

        $orderBy    = 'categoryID';
        $orderType  = 'ASC';
        if(isset($_GET['order'])){
            $orderBy = $_GET['order'];
        }
        if(isset($_GET['type'])){
            $orderType = $_GET['type'];
        }
//        $excludeColumns = array('customFields','featuredImageID','remember_token','created_at','updated_at','postTypeID','name','slug','fields','visible','hasCategories','hasTags');

        $conditions = array();
        if ($postTypeID != 0){
            $conditions = array(
                0 => array(
                    'where',
                    'postTypeID',
                    '=',
                    $postTypeID
                )
            );
        }

        $column = [
            "Field" => "title",
            "Type" => "json"
        ];
        $columns = [];
        array_push($columns, (object) $column);

        $results = Search::searchByTerm('categories',$term, Category::$rowsPerPage, true, $columns, [], $orderBy, $orderType, [], $conditions);
        return Language::filterRows($results);
    }

    /**
     *  return views for search component
     * @params search term
     * */
    public function search($lang, $id, $term){
        // check if user has permissions to access this link
        if(!User::hasAccess('Categories','read')){
            return $this->noPermission();
        }
        return view('content');
    }

    /**
     * This function creates the slug for a category and makes sure that slugs it is not being used from a other category
     * @return string generated slug
     * */
    public function getSlug($lang, $postTypeID, $slug){
        return parent::generateSlug($slug, 'categories', 'categoryID', $lang, 0, true);
    }

    /**
     *  Get all categories without pagination
     * */
    public function getAllWithoutPagination($lang = ""){
        $result = DB::table('categories')->join('post_type', 'post_type.postTypeID', 'categories.postTypeID')->orderBy('name', 'postTypeID')->get();
        return Language::filterRows($result, false);
    }

    /**
     *  Get all categories without pagination filtering by post type
     * */
    public function getAllWithoutPaginationByPostType($lang = "", $postType = ""){
        //has access into all categories
        if(User::hasAccess($postType,'categories','hasAll')){
            $list = DB::table('categories')
                ->join('post_type', 'post_type.postTypeID', 'categories.postTypeID')
                ->where('post_type.slug', $postType)
                ->orderBy('name', 'postTypeID')
                ->select('categories.categoryID','categories.title','categories.slug','categories.createdByUserID','categories.order','categories.featuredImageID',
                    'categories.description','categories.created_at','categories.updated_at','post_type.name')
                ->get();
        }else if(User::getPermission($postType,'categories')){
            //has access into some categories
            $allowedCategories = Auth::user()->getPermission($postType,'categories');
            if($allowedCategories) {
                $list = DB::table('categories')
                  ->join('post_type', 'post_type.postTypeID', 'categories.postTypeID')
                  ->where('post_type.slug', $postType)
                  ->whereIn('categories.categoryID', $allowedCategories['value'])
                  ->orderBy('name', 'postTypeID')
                  ->select('categories.categoryID', 'categories.title', 'categories.slug', 'categories.createdByUserID', 'categories.order', 'categories.featuredImageID',
                    'categories.description', 'categories.created_at', 'categories.updated_at', 'post_type.name')
                  ->get();
            }
        }else{
            //or doesn't have access at all
            return [];
        }
        return Language::filterRows($list, false);
    }

    /**
     * GET post type by category id
     * */
    public function getPostType($lang = "", $categoryID){
        $result = DB::table('categories')->join('post_type', 'post_type.postTypeID', 'categories.postTypeID')->where('categoryID', $categoryID)->first();
        return array('list' => $result);
    }

}
