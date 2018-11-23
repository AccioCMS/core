<?php

namespace Accio\App\Http\Controllers\Backend;

use App;
use Illuminate\Support\Facades\Auth;
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
    }

    /**
     * Get categories to be displayed in menu panel.
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
     * Gets categories by post type with parent child relations.
     *
     * @param string $lang
     * @param int $postTypeID
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
        $category->categoryList = Category::where('postTypeID',$postTypeID)
            ->orderBy($orderBy, $orderType)
            ->select("categoryID", "title", "slug")
            ->get();
        
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
     * Delete a Category and his children.
     *
     * @param string $lang
     * @param int $id
     * @return array|bool
     * @throws \Exception
     */
    public function delete($lang, $id){
        if(!User::hasAccess('Categories','delete')){
            return $this->noPermission();
        }

        $categoryDeleteRes = $this->deleteCategory($id);
        if(gettype($categoryDeleteRes) == "boolean"){
            if($categoryDeleteRes){
                return $this->response('Category could not be deleted.', 500);
            }
        }else{
            return $categoryDeleteRes;
        }

        return $this->response('Category could not be deleted. Please try again later or contact your Administrator!', 500);
    }

    /**
     * Bulk Delete categories, Delete many categories in one request.
     *
     * @param Request $request
     * @return array|bool
     * @throws \Exception
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
            $categoryDeleteRes = $this->deleteCategory($id);
            if(gettype($categoryDeleteRes) == "boolean"){
                if(!$categoryDeleteRes)
                    return $this->response('Category could not be deleted.', 500);
            }else{
                return $categoryDeleteRes;
            }
        }

        return $this->response('Selected categories are successfully deleted');
    }

    /**
     * Delete single category and all its children.
     *
     * @param int $id
     * @return array|bool
     * @throws \Exception
     */
    private function deleteCategory(int $id){
        $category = Category::find($id);
        if($category) {
            $postType = PostType::findByID($category->postTypeID);
            if(Category::isInMenuLinks($id)){
                return $this->response("You can't delete this Category. It is part of a menu", 403);
            }

            // delete children
            $category->deleteChildren($category->categoryID, $postType->postTypeID);

            // delete relations
            DB::table(categoriesRelationTable($postType->slug))->where("categoryID", $category->categoryID)->delete();

            // Delete the item
            if($category->delete()){
                return true;
            }
        }
        return false;
    }

    /**
     * Save Category in database.
     *
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function store(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('Categories','create')){
            return $this->noPermission();
        }

        $data = $request->all();
        $structuredData =  $this->generateTitleAndSlug($data['form']);
        $form = $structuredData['form'];
        $errors = $structuredData['errors'];

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
        $categoryModel->parentID = ($parentID ? $parentID: null);
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
            $redirect = $this->redirect($request->redirect, $categoryModel->categoryID, $data['postTypeID']);
            $response = $this->response('Category is saved', 200, $redirect['id'], $redirect['view'], $redirect['url']);
        }else{
            $response = $this->response( 'Category is not saved. Please try again later!', 500);
        }
        return $response;
    }

    /**
     * If title and slug are empty fill them with the default language title.
     *
     * @param array $form
     * @return array
     * @throws \Exception
     */
    private function generateTitleAndSlug($form){
        $errors = [];
        $requiredMessage = " is required";
        $defaultLanguage = Language::getDefault();
        $result = $form;

        $defaultLanguageTitle = '';
        // title in default language should no be empty
        if(
            isset($form['title']) &&
            isset($form['title'][$defaultLanguage->slug]) &&
            $form['title'][$defaultLanguage->slug] != ""){
            $defaultLanguageTitle = $form['title'][$defaultLanguage->slug];
            $newSlug = parent::generateSlug($defaultLanguageTitle, 'categories', 'categoryID', App::getLocale(), 0, true);
        }else{
            $errors['title_'.$defaultLanguage['slug']] = array('Title '.$requiredMessage. " in ".$defaultLanguage['name']);
        }

        if ($defaultLanguageTitle){
            // loop throw data in each language and generate title and slug if there are empty
            $languages = Language::all();
            foreach($form as $formDataKey => $formDataValue){
                foreach($languages as $language){
                    if($formDataKey == "title" && !$language['isDefault']){
                        if(!$formDataValue[$language['slug']]){
                            if($defaultLanguageTitle){
                                $formDataValue[$language['slug']] = $defaultLanguageTitle;
                            }
                        }
                    }

                    if($formDataKey == "slug"){
                        if(!$formDataValue[$language['slug']]) {
                            $formDataValue[$language['slug']] = $newSlug;
                        }
                    }
                }

                $result[$formDataKey] = $formDataValue;
            }
        }

        return [
            "form" => $result,
            "errors" => $errors
        ];
    }

    /**
     * Redirect parameters used in frontend.
     *
     * @param string $redirect
     * @param int $categoryID
     * @param int $postTypeID
     * @return array
     */
    private function redirect(string $redirect, int $categoryID, int $postTypeID){
        $adminPrefix = Config::get('project')['adminPrefix'];
        if($redirect == 'save'){
            $redirectUrl = "/".$adminPrefix."/".App::getLocale()."/post-type/categoryupdate/".$categoryID;
            $view = 'categoryupdate';
            $redirectID = $categoryID;
        }else if($redirect == 'close'){
            $redirectUrl = "/".$adminPrefix."/".App::getLocale()."/post-type/categorylist/".$postTypeID;
            $view = 'categorylist';
            $redirectID = $postTypeID;
        }else{
            $redirectUrl = "/".$adminPrefix."/".App::getLocale()."/post-type/categorycreate/".$postTypeID;
            $view = 'categorycreate';
            $redirectID = $postTypeID;
        }

        return[
            "url" => $redirectUrl,
            "view" => $view,
            "id" => $redirectID
        ];
    }

    /**
     * Updates labels in menu link (if the category is being used as menu link).
     *
     * @param int $id
     * @param array $newTitle
     * @param object $oldTitle
     * @throws \Exception
     */
    private function updateMenuLinkLabel(int $id, array $newTitle, $oldTitle){
        $menuLinksList = MenuLink::where('belongsToID', $id)->where('belongsTo','category')->get();

        $langSlug = App::getLocale();
        if($menuLinksList->count()){
            foreach ($menuLinksList as $menuLink){
                $updateLabel = false;
                foreach (Language::all() as $lang){
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
     * JSON object with details for a specific category.
     * All data used in update form.
     * 
     * @param string $lang
     * @param int $id
     * @return array
     * @throws \Exception
     */
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
                'languages' => Language::all(),
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
     * Make simple search using term, returns array of data that meet the term search.
     *
     * @param string $lang
     * @param int $postTypeID
     * @param string $term
     * @return array
     */
    public function makeSearch($lang, $postTypeID, $term){
        // check if user has permissions to access this link
        if(!User::hasAccess('Categories','read')){
            return $this->noPermission();
        }

        $orderBy = (isset($_GET['order'])) ? $_GET['order'] : 'categoryID';
        $orderType = (isset($_GET['type'])) ? $_GET['type'] : 'ASC';

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
     * return views for search component.
     *
     * @param string $lang
     * @param int $id
     * @param string $term
     * @return array|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function search($lang, $id, $term){
        // check if user has permissions to access this link
        if(!User::hasAccess('Categories','read')){
            return $this->noPermission();
        }
        return view('content');
    }

    /**
     * This function creates the slug for a category and makes sure that slugs it is not being used from a other category.
     *
     * @param string $lang
     * @param int $postTypeID
     * @param string $slug
     * @return string
     */
    public function getSlug($lang, $postTypeID, $slug){
        return parent::generateSlug($slug, 'categories', 'categoryID', $lang, 0, true);
    }

    /**
     * Get all categories without pagination.
     *
     * @param string $lang
     * @return array|mixed
     */
    public function getAllWithoutPagination($lang = ""){
        $result = DB::table('categories')->join('post_type', 'post_type.postTypeID', 'categories.postTypeID')->orderBy('name', 'postTypeID')->get();
        return Language::filterRows($result, false);
    }

    /**
     * Get post type by category id.
     *
     * @param string $lang
     * @param int $categoryID
     * @return array
     */
    public function getPostType($lang = "", $categoryID){
        $result = DB::table('categories')
            ->join('post_type', 'post_type.postTypeID', 'categories.postTypeID')
            ->where('categoryID', $categoryID)
            ->first();
        return array('list' => $result);
    }

}
