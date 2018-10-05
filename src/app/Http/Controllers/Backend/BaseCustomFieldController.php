<?php

namespace Accio\App\Http\Controllers\Backend;

use App\Models\CustomFieldGroup;
use App\Models\Language;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Validator;
use App\Models\Category;
use App\Models\CustomField;
use Illuminate\Http\Request;
use Accio\Support\Facades\Pagination;

class BaseCustomFieldController extends MainController{

    public $usedSlugs = [];

    /**
     * BaseCustomFieldController constructor.
     */
    public function __construct(){
        parent::__construct();
    }

    /**
     * Used to get the list of custom field groups
     *
     * @param string $lang language slug
     * @return array
     */
    public function getAll($lang = ""){
        $orderBy = (isset($_GET['order'])) ? $orderBy = $_GET['order'] : 'customFieldGroupID';
        $orderType = (isset($_GET['type'])) ? $orderType = $_GET['type'] : 'DESC';
        return CustomFieldGroup::orderBy($orderBy, $orderType)->paginate(CustomFieldGroup::$rowsPerPage);
    }

    /**
     * Use to store the custom field groups and custom fields in the database
     *
     * @param Request $request object with multi-dimensional array with values of custom fields
     * @return array ErrorHandler response
     */
    public function store(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('custom_fields','create')){
            return $this->noPermission();
        }
        // custom messages for validation
        $messages = array(
            'title.required'=>'You cant leave Title field empty',
            'slug.required'=>'You cant leave slug field empty',
            'isActive.required'=>'You cant leave Is active field empty',
        );

        // validation
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'slug' => 'required',
            'isActive' => 'required',
        ], $messages);

        // if validation fails return json response
        if($validator->fails()){
            return $this->response( "Please check all required fields!", 400,null, false, false, true, $validator->errors());
        }

        // update
        if($request->id){
            $customFieldsGroup = CustomFieldGroup::findOrFail($request->id);
            $customFieldsDeletion = DB::table('custom_fields')->where('customFieldGroupID', $customFieldsGroup->customFieldGroupID)->delete();
            $slug = self::generateSlug($request->slug, 'custom_fields_groups', 'customFieldGroupID', '', $request->id, false, '_');
        }else{
        // create
            $customFieldsGroup = new CustomFieldGroup();
            $slug = self::generateSlug($request->slug, 'custom_fields_groups', 'customFieldGroupID', '', 0, false, '_');
        }
        // get custom field group values
        $customFieldsGroup->title = $request->title;
        $customFieldsGroup->slug = $slug;
        $customFieldsGroup->description = $request->description;
        $customFieldsGroup->isActive = $request->isActive;
        $customFieldsGroup->conditions = $request->groupRules;

        // if custom field group is stored
        if($customFieldsGroup->save()){
            $fields = $this->prepareCustomFieldArray($request->fields, $customFieldsGroup->customFieldGroupID);
            // store parents custom fields
            foreach ($fields['parents'] as $field){
                $tmpID = $field['tmpID'];
                unset($field['tmpID']);
                $customFieldsID = DB::table('custom_fields')->insertGetId($field);

                // if error in DB
                if (!$customFieldsID){
                    return $this->response('Internal server error. Please try again later',500);
                }

                // if field has sub fields
                if (isset($fields['children'][$tmpID])){
                    $childArr = [];
                    foreach ($fields['children'][$tmpID] as $childField){
                        unset($childField['tmpID']);
                        $childField['parentID'] = $customFieldsID;
                        $childArr[] =  $childField;
                    }
                    $childFields = DB::table('custom_fields')->insert($childArr);
                    // if error in DB
                    if (!$childFields){
                        return $this->response( 'Internal server error. Please try again later', 500);
                    }

                }
            }
        }

        $redirectParams = parent::redirectParams($request->redirect, 'custom-fields', $customFieldsGroup->customFieldGroupID);
        return $this->response( 'Custom fields are stored',200, $customFieldsGroup->customFieldGroupID, $redirectParams['view'], $redirectParams['redirectUrl']);
    }

    /**
     * Used to prepare the custom field array to insert the fields in the database
     *
     * @param array $fields list of custom fields
     * @param integer $groupID id of custom fields group
     * @return array
     */
    public function prepareCustomFieldArray($fields, $groupID){
        $fieldsQuery = [];
        $count = 0;
        foreach($fields as $field){
            $fieldsQuery[$count]['customFieldGroupID'] = $groupID;
            $fieldsQuery[$count]['tmpID'] = $field['id'];
            $fieldsQuery[$count]['label'] = json_encode($field['label']);
            $fieldsQuery[$count]['placeholder'] = json_encode($field['placeholder']);
            $fieldsQuery[$count]['note'] = json_encode($field['note']);
            $fieldsQuery[$count]['slug'] = $this->generateFieldSlug('', $field['slug'], $field['id'], $fields);
            $fieldsQuery[$count]['type'] = $field['type']['inputType'];
            $fieldsQuery[$count]['order'] = $field['order'];
            $fieldsQuery[$count]['isMultiple'] = $field['isMultiple'];
            $fieldsQuery[$count]['parentID'] = $field['parent'];
            $fieldsQuery[$count]['optionsValues'] = $field['multioptionValues'];
            $fieldsQuery[$count]['isRequired'] = $field['isRequired'];
            $fieldsQuery[$count]['isTranslatable'] = $field['isTranslatable'];
            $fieldsQuery[$count]['isActive'] = $field['isActive'];
            $fieldsQuery[$count]['defaultValue'] = $field['defaultValue'];
            $fieldsQuery[$count]['wrapperStyle'] = json_encode($field['wrapperStyle']);
            $fieldsQuery[$count]['fieldStyle'] = json_encode($field['fieldStyle']);
            $fieldsQuery[$count]['layout'] = $field['layout'];
            $fieldsQuery[$count]['conditions'] = json_encode($field['rules']);

            $properties = [
                'maxWidth' => $field['properties']['maxWidth'],
                'maxHeight' => $field['properties']['maxHeight'],
                'minWidth' => $field['properties']['minWidth'],
                'minHeight' => $field['properties']['minHeight'],
                'maxUploadSize' => $field['properties']['maxUploadSize'],
                'max' => $field['properties']['max'],
                'min' => $field['properties']['min'],
                'dbTable' => $field['properties']['dbTable'],
                'rangeLabel' => $field['properties']['rangeLabel'],
                'rows' => $field['properties']['rows'],
                'allowHTML' => $field['properties']['allowHTML'],
                'allowOther' => $field['properties']['allowOther'],
                'allowParagraphs' => $field['properties']['allowParagraphs'],
                'characterLimit' => $field['properties']['characterLimit'],
                'toolbar' => $field['properties']['toolbar'],
            ];
            $fieldsQuery[$count]['properties'] = json_encode($properties);
            $count++;
        }
        $customFieldsArr = $this->emptyFields($fieldsQuery);
        return $this->divideParentFromChildren($customFieldsArr);
    }

    /**
     * Used to make null the empty values of custom field columns (like '[]', '""', '')
     *
     * @param array $fieldsQuery multi-dimensional array with custom fields
     * @return array
     */
    public function emptyFields($fieldsQuery){
        $result = [];
        $count = 0;
        foreach ($fieldsQuery as $field){
            foreach ($field as $key => $fieldValue){
                if($fieldValue === "[]" || $fieldValue === "\"\"" || $fieldValue === "null" || $fieldValue === ""){
                    $result[$count][$key] = null;
                }else{
                    $result[$count][$key] = $fieldValue;
                }
            }
            $count++;
        }
        return $result;
    }

    /**
     * Divides Fields from sub fields in two different arrays
     *
     * @param array $customFieldsArr array of custom fields
     * @return array of custom fields
     */
    public function divideParentFromChildren($customFieldsArr){
        $parents = [];
        $children = [];
        for ($i = 0; $i < count($customFieldsArr); $i++){

            if(!$customFieldsArr[$i]['parentID']){
                $parents[] = $customFieldsArr[$i];
            }else{
                if(!isset($children[$customFieldsArr[$i]['parentID']])){
                    $children[$customFieldsArr[$i]['parentID']] = [];
                }
                $children[$customFieldsArr[$i]['parentID']][] = $customFieldsArr[$i];
            }
        }
        return array('parents' => $parents, 'children' => $children);
    }

    /**
     * Delete a custom field group from database
     *
     * @param string $lang language slug
     * @param integer $id custom fields group ID
     *
     * @return array ErrorHandler response
     */
    public function delete($lang, $id){
        if(!User::hasAccess('CustomField','delete')){
            return $this->noPermission();
        }
        $customFieldGroup = CustomFieldGroup::find($id);

        if($customFieldGroup) {
            $fields = CustomField::where('customFieldGroupID', $id);
            if ($fields){
                $fields->delete();
                $customFieldGroup->delete();

                return $this->response('Custom field group is deleted');
            }
        }

        return $this->response( 'Custom field group could not be deleted. Please try again later', 500);
    }

    /**
     * Bulk Delete custom fields groups and their custom fields
     *
     * @param Request $request array of custom fields IDs
     * @return array ErrorHandler response
     */
    public function bulkDelete(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('CustomField','delete')){
            return $this->noPermission();
        }
        $data = $request->all();
        foreach ($data as $id){
            $customFieldsGroup = CustomFieldGroup::find($id)->delete();
            if (!$customFieldsGroup) {
                return $this->response( 'Internal server error. Please try again later', 500);
            }else{
                CustomField::where('customFieldGroupID',$id)->delete();
                // TODO me i fshi vlerat e custom fildave neper poste, users dhe category
            }
        }
        return $this->response('Custom fields groups are deleted');
    }

    /**
     * JSON object with all details for a specific custom field
     *
     * @param $lang
     * @param $id
     * @return array
     */
    public function detailsJSON($lang, $id){
        // check if user has permissions to access this link
        if(!User::hasAccess('CustomField','read')){
            return $this->noPermission();
        }
        $customFieldGroup = CustomFieldGroup::find($id);
        $customFieldGroup->fields;

        // Fire event
        $customFieldGroup->events = Event::fire('customField:pre_update', [$customFieldGroup]);

        return $customFieldGroup;
    }


    /**
     * Get all data of a table (for custom field purposes in front-end)
     *
     * @param Request $request
     * @return array
     */
    public function getTableData(Request $request){
        $table = $request->all()['name'];
        return Language::filterRows(DB::table($table)->get(), false);
    }

    /**
     * This function creates the slug for a custom field group and makes sure that slugs it is not being used from a other group
     *
     * @param string $lang language slug
     * @param string $title the string to be slug
     *
     * @return string generated slug
     * */
    public function getSlug($lang, $title){
        return self::generateSlug($title, 'custom_fields_groups', 'customFieldGroupID', $lang, 0, false, "_");
    }

    /**
     * Catches the post request from frontend
     *
     * @param Request $request data of the request
     * @return string generated key
     */
    public function generateFieldSlugRequest(Request $request){
        $id = $request->id;
        $title = $request->title;
        $keys = $request->keys;
        return $this->generateFieldSlug('', $title, $id, $keys);
    }


    /**
     * This function generates the slug/key for a custom field by using a chosen value
     *
     * @param string $lang language slug
     * @param string $key text to be made slug
     * @param integer $id id of the current custom field
     * @return string generated key
     */
    public function generateFieldSlug($lang, $key, $id, $keys){
        $count = 1;
        $found = true;
        $slug = str_slug($key, '_');

        $keysArray = array();
        foreach ($keys as $key){
            if($id != $key['id']){
                $keysArray[] = $key['slug'];
            }
        }

        if(!in_array($slug, $keysArray) && !in_array($slug, $this->usedSlugs)){
            return $slug;
        }else{
            while ($found){
                $newSlug = $slug.'_'.$count;
                if(!in_array($newSlug, $keysArray) && !in_array($newSlug, $this->usedSlugs)){
                    $found = false;
                    $this->usedSlugs[] = $newSlug;
                    return $newSlug;
                }
                $count++;
            }
        }

        return $keysArray;
    }

    /**
     * Return all custom fields of a app/module
     *
     * @param string $module module/app
     * @param string $formType (create, update)
     * @param int $id (optional) id of the item
     *
     * @return array of custom fields by group
     */
    public function getByApp($lang, $module, $formType, $id, $postType = ""){
        return CustomFieldGroup::findGroups($module, $formType, $id, $postType);
    }

}
