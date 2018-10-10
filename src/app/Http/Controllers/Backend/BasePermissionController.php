<?php

namespace Accio\App\Http\Controllers\Backend;


use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Language;
use App\Models\Permission;

class BasePermissionController extends MainController{

    /**
     * Get the permission options for each module (Cms app) from their models.
     *
     * @param string $lang
     * @return array
     * @throws \Exception
     */
    public function getAllPermissionsOptions($lang = ""){
        if(!User::hasAccess('permissions','read')){
            return $this->noPermission();
        }
        return Permission::getPermissionsFromModels();
    }


    /**
     * All users group
     *
     * @return array
     * */
    public function getUserGroups($lang = ""){
        // check if user has permissions to access this link
        if(!User::hasAccess('Permissions','read')){
            return $this->noPermission();
        }
        return array('data' => \App\Models\UserGroup::all());
    }

    /**
     * Delete a group and it's permissions by it's ID.
     *
     * @param string $lang
     * @param int $id
     * @return array
     */
    public function delete($lang, $id){
        // check if user has permissions to access this link
        if(!User::hasAccess('Permissions','delete')){
            return $this->noPermission();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $group = \App\Models\UserGroup::find($id)->delete();
        if($group){
            DB::table('permissions')->where('groupID', $id)->delete();
            $result = $this->response('Group is deleted');
        }else{
            $result = $this->response('Internal server error. Please try again later', 500);
        }
        return $result;
    }

    /**
     * Creates or Updates group and permissions.
     *
     * @param Request $request
     * @return array
     */
    public function store(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('Permissions','create')){
            return $this->noPermission();
        }
        // custom messages for validation
        $messages = array(
            'id.required' => 'ID is missing',
            'name.required' => 'You cant leave name field empty',
            'permissions.required' => 'Something is wrong, please contact your administrator.',
        );
        // validation
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'name' => 'required',
            'permissions' => 'required',
        ], $messages);
        // if validation fails return json response
        if($validator->fails()){
            return $this->response( "Please check all required fields!", 400,null, false, false, true, $validator->errors());
        }

        $id = $request->id;
        $name = $request->name;
        $permissions = $request->permissions;
        $globalPermissions = $request->globalPermissions;

        if ($id == 0){
            $slug = str_slug($name, '-');
            $group = \App\Models\UserGroup::create([
                'name' => $name,
                'slug' => $slug
            ]);
            $id = $group->groupID;
        }else{
            $postDeletion = DB::table('permissions')->where('groupID', $id)->delete();
        }

        $query = Permission::createGlobalPermissions($globalPermissions, $id);
        $query = array_merge($query, Permission::createPermissions($permissions, $id));

        // make query
        if (count($query)){
            $permission = Permission::insert($query);
            if ($permission){
                return $this->response('Permissions updated');
            }
        }
    }

    /**
     * Bulk Delete groups and permissions, delete many groups in one request.
     *
     * @param Request $request
     * @return array
     */
    public function bulkDelete(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('Permissions','delete')){
            return $this->noPermission();
        }
        $data = $request->all();
        if(isset($data['postTypes'])){
            unset($data['postTypes']);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($data as $id){
            $page = \App\Models\UserGroup::findOrFail($id)->delete();
            if (!$page) {
                return $this->response( 'Internal server error. Please try again later', 500);
            }
            DB::table('permissions')->where('groupID', $id)->delete();
        }

        return $this->response( 'Groups are deleted');
    }

    /**
     * USED to get list of models for custom permissions.
     * Returns array list from DB with primary key as array key and selected columns as value.
     *
     * @param Request $request
     * @return array
     */
    public function getList(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('permissions','read')){
            return $this->noPermission();
        }
        $className = 'App\\Models\\'.$request->customPermissions['model'];
        $class = new $className(); // generated class from model name
        $currentLang = App::getLocale();
        $obj = DB::table($class->table);
        // where clause
        if (isset($request->customPermissions['keys'])){
            foreach ($request->customPermissions['keys'] as $clause){
                $obj->where($clause['field'], $clause['operator'], $clause['value']);
            }
        }
        // order
        if (isset($request->customPermissions['order'])){
            foreach ($request->customPermissions['order'] as $orderKey => $order){
                if(isset($order['isTranslatable']) && $order['isTranslatable']){
                    $obj->orderBy($order['field'].'->'.$currentLang, $order['type']);
                }else{
                    $obj->orderBy($order['field'], $order['type']);
                }
            }
        }
        // limit
        if (isset($request->customPermissions['limit'])){
            $obj->limit($request->customPermissions['limit']);
        }
        // push primary key in selected
        $selected = $request->customPermissions['select'];
        array_push($selected, $class->getKeyName());
        $list = $obj->select($selected)->get();
        // filter by language
        $filteredArr = Language::filterRows($list, false );

        // construct result array
        $result = array();
        foreach ($filteredArr as $k => $filtered){
            $title = '';
            foreach ($filtered as $key => $value){
                if(in_array($key, $request->customPermissions['select'])){
                    $title .= ($title != '') ? ' ' : '';
                    $title .= $value;
                }
            }
            array_push($result, array(
                $class->primaryKey => $filtered[$class->getKeyName()],
                'title' => $title
            ));

        }
        return $result;
    }


    /**
     * Return all permission and values structured for frontend.
     *
     * @param Request $request
     * @return array
     */
    public function getListValues(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('permissions','read')){
            return $this->noPermission();
        }
        $permissionsValues = Permission::where('groupID', $request->ID)->get();
        $groupName = \App\Models\UserGroup::findOrFail($request->ID)->name;
        $permissions = $request->permissions;
        $permissionResult = array();
        $currentLang = App::getLocale();
        // make array with all apps of a group that have hasAll = true
        $hasAllAppsObj = Permission::where('groupID', $request->ID)->where('key','categories')->where('hasAll',true)->select('app')->get()->toArray();
        $hasAllAppsArr = [];
        foreach ($hasAllAppsObj as $hasAllAppObj){
            $hasAllAppsArr[] = $hasAllAppObj['app'];
        }

        // used to get the categories for posts types
        $categories = Language::filterRows(\App\Models\Category::all()->toArray(),false);
        $categoryArr = [];
        foreach ($categories as $category){
            $categoryArr[$category['categoryID']] = $category;
        }

        $globalPermissions = array();
        // loop throw all apps in permissions array
        foreach ($permissions as $appName => $app){
            // loop throw the permissions of a app
            foreach ($app as $permissionType => $permission){
                // all permission rows taken from DB of a app
                foreach ($permissionsValues as $pemissionsValue) {
                    // get selected menu links ids
                    if($appName == 'MenuLinks' && $pemissionsValue->app == 'MenuLinks' && $pemissionsValue->key == 'id'){
                        $app['selectedMenuLinks'] = json_decode($pemissionsValue->ids);
                    }
                    // populate global permission array
                    if($pemissionsValue['app'] == 'global'){
                        $globalPermissions[$pemissionsValue['key']] = true;
                    }
                    // if it is a CRUD permissions it means it's values should go to defaultPermissionsValues array
                    if ($pemissionsValue->app == $appName){
                        if($pemissionsValue->key == 'create' ||
                            $pemissionsValue->key == 'update' ||
                            $pemissionsValue->key == 'read' ||
                            $pemissionsValue->key == 'delete'){
                            if (!in_array($pemissionsValue->key, $app['defaultPermissionsValues'])){
                                $app['defaultPermissionsValues'][] = $pemissionsValue->key;
                            }
                        }
                        // Populate Categories Values
                        if(isset($app['categoriesValues'])){
                            if(count($app['categoriesValues']) != 0){
                                continue;
                            }
                            if(in_array($appName, $hasAllAppsArr)){
                                $app['hasAll'] = true;
                                $app['categoriesValues'] = [];
                            }else{
                                $catIDsOBJ = json_decode($pemissionsValue->ids, true);
                                $catIDs = array();
                                if (is_object($catIDsOBJ) || is_array($catIDsOBJ)){
                                    foreach ($catIDsOBJ as $id){
                                        $catIDs[] = $id;
                                        if (isset($categoryArr[$id])){
                                            array_push($app['categoriesValues'], $categoryArr[$id]);
                                        }
                                    }
                                }
                            }
                        }
                        // if this app has custom permissions
                        if(isset($app['customPermissionsValues'])){
                            // loop throw the custom permissions
                            foreach ($app['customPermissionsValues'] as $customPermissionsValueKey => $customPermissionsValueValue){
                                if(is_array($customPermissionsValueValue)){ // if custom permission is array (it means it takes a list from a other table in database)
                                    if ($pemissionsValue->key == $customPermissionsValueKey){
                                        // if the list is already set don't set it twice
                                        if (isset($app['customPermissionsValues'][$pemissionsValue->key]) && count($app['customPermissionsValues'][$pemissionsValue->key]) != 0){
                                            continue;
                                        }
                                        $className = 'App\\Models\\'.$app['customPermissions'][$pemissionsValue->key]['value']['model'];
                                        $class = new $className(); // generated class from model name
                                        // get list from DB by using it's IDS
                                        $IDs = array_values(json_decode($pemissionsValue->ids, true));
                                        $listFromDB = DB::table($class->table)->whereIn($class->getKeyName(), $IDs)->get();
                                        // selected columns of table set in the model array of custom permissions
                                        $columns = $app['customPermissions'][$pemissionsValue->key]['value']['select'];

                                        foreach ($listFromDB as $item){
                                            $title = '';
                                            $count = 0;
                                            foreach ($columns as $column){
                                                if(is_object(json_decode($item->$column))){
                                                    if(isset(json_decode($item->$column)->$currentLang)){
                                                        $title .= json_decode($item->$column)->$currentLang;
                                                    }else{
                                                        $title .= '';
                                                    }
                                                }else{
                                                    $title .= $item->$column;
                                                }

                                                if(count($columns) == $count){
                                                    $title .= ' ';
                                                }
                                                $count++;
                                            }
                                            $primaryKey = $class->getKeyName();
                                            $app['customPermissionsValues'][$pemissionsValue->key][] = ['title' => $title, $primaryKey => $item->$primaryKey];
                                        }
                                        break;
                                    }

                                }else{
                                    // if custom permission it's not a array it means it is a boolean
                                    if ($pemissionsValue->key == $customPermissionsValueKey){
                                        $app['customPermissionsValues'][$pemissionsValue->key] = true;
                                        break;
                                    }
                                }
                            }

                        }
                    }
                }

            }

            $permissionResult[$appName] = $app;
        }
        return array('permissions' => $permissionResult, 'name' => $groupName, 'globalPermissions' => $globalPermissions);
    }

}
