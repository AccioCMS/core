<?php
namespace Accio\App\Traits;

use Illuminate\Support\Facades\File;
use App\Models\Language;
use Illuminate\Database\Eloquent\Model;

trait PermissionTrait{

    /**
     * Get permissions list from each model.
     *
     * @return array
     * @throws \Exception
     */
    public static function getPermissionsFromModels(){
        $result = array();
        $files = File::files(app_path().'/Models');

        //get all categories
        $categoriesByPostTypID  = \App\Models\Category::all()->groupBy('postTypeID')->toArray();

        foreach ($files as $file){
            $fileName = $file->getBaseName();
            $className = str_replace('.php', '', $fileName);
            $object = 'App\\Models\\'.$className;

            // check if model requests any permissions
            if(!property_exists($object,'defaultPermissions') && !property_exists($object,'customPermissions')){
                continue;
            }

            //each post type may have different permissions
            if($className == "PostType"){
                // Default permission
                if(property_exists($object,'defaultPermissions')){
                    $result[$className]['defaultPermissions'] = $object::$defaultPermissions;
                }
                // Custom permission
                if(property_exists($object,'customPermissions')){
                    $result[$className]['customPermissions'] = $object::$customPermissions;
                }

                // Label
                if(property_exists($object, "label")){
                    $result[$className]['label'] = trans($object::$label);
                }

                foreach (\App\Models\PostType::cache()->getItems() as $postType){
                    // Label
                    $result[$postType['slug']]['label'] = $postType['name'];

                    // Default permission
                    if(property_exists($object,'defaultPermissions')){
                        $result[$postType['slug']]['defaultPermissions'] = $object::$defaultPermissions;
                    }
                    // Custom permission
                    if(property_exists($object,'customPermissions')){
                        if (isset($object::$customPermissions[$postType['slug']])){
                            $result[$postType['slug']]['customPermissions'] = $object::$customPermissions[$postType['slug']];
                        }
                    }
                    if(isset($categoriesByPostTypID[$postType['postTypeID']])){
                        $result[$postType['slug']]['categories'] = Language::filterRows($categoriesByPostTypID[$postType['postTypeID']], false);
                    }
                }
            }else{
                // Default permission
                if(property_exists($object,'defaultPermissions')){
                    $result[$className]['defaultPermissions'] = $object::$defaultPermissions;
                }
                // Custom permission
                if(property_exists($object,'customPermissions')){
                    $result[$className]['customPermissions'] = $object::$customPermissions;
                }

                // Label
                if(property_exists($object, "label")){
                    $result[$className]['label'] = trans($object::$label);
                }else{
                    $result[$className]['label'] = $className;
                }
            }
        }

        // Get plugin permission
        $authors = File::directories(base_path().'/plugins');
        foreach ($authors as $author){
            $plugins = File::directories($author);
            foreach ($plugins as $plugin){
                $pluginConfig = json_decode(File::get($plugin."/config.json"));
                if(isset($pluginConfig->permissions)){
                    $title = str_replace('/', "_", $pluginConfig->namespace);
                    $title = str_slug($title, '_');
                    $result[$title] = $pluginConfig->permissions;
                    $result[$title]->label = $pluginConfig->organization." ".$pluginConfig->title;
                }
            }
        }

        return $result;
    }

    /**
     * Checks if a permission exists.
     *
     * @param string $app
     * @param string $key
     * @return boolean
     */
    public static function exists($app = 'global', $key){
        return ((new static())->where('app',$app)->where('key', $key)->where('value', true)->count() ? true : false);
    }


    /**
     * Prepare global permission array to be stored on database.
     * Gets input from the front-end and customizes object to be stored as JSON in DB.
     *
     * @param $globalPermissions
     * @param $id
     * @return array
     */
    public static function createGlobalPermissions($globalPermissions, $id){
        $query = [];

        if ($globalPermissions){
            // Create global permissions
            foreach ($globalPermissions as $globalPermissionKey => $globalPermissionValue){
                if($globalPermissionValue){
                    $query[] = array(
                        'groupID' => $id,
                        'app' => 'global',
                        'key' => $globalPermissionKey,
                        'value' => true,
                        'ids' => NULL,
                        'hasAll' => false,
                    );
                }
            }
        }
        return $query;
    }

    /**
     * Prepare custom and default permission array to be stored on database.
     * Gets input from the front-end and customizes object to be stored as JSON in DB.
     *
     * @param array $permissions
     * @param int $id
     * @return array
     */
    public static function createPermissions($permissions, $id){
        $query = [];

        if($permissions){
            // create other permissions
            foreach($permissions as $appName => $app){
                foreach ($app as $permissionType => $permission){
                    if($permissionType == 'defaultPermissionsValues'){
                        foreach ($permission as $key){
                            $query[] = array(
                                'groupID' => $id,
                                'app' => $appName,
                                'key' => $key,
                                'value' => true,
                                'ids' => NULL,
                                'hasAll' => false,
                            );
                        }
                    }else if ($permissionType == 'customPermissionsValues'){
                        foreach($permission as $key => $object){
                            if (is_array($object)){

                                if(count($object)){
                                    $IDs = array();
                                    $c = 0;
                                    foreach($object as $value){
                                        $class = "App\\Models\\".$appName;
                                        $model = new $class();
                                        $IDs['ID'.$c] = $value[$model->getKeyName()];
                                        $c++;
                                    }
                                    $query[] = array(
                                        'groupID' => $id,
                                        'app' => $appName,
                                        'key' => $key,
                                        'value' => true,
                                        'ids' => json_encode($IDs),
                                        'hasAll' => false,
                                    );
                                }
                            }else{
                                if ($object){
                                    $query[] = array(
                                        'groupID' => $id,
                                        'app' => $appName,
                                        'key' => $key,
                                        'value' => true,
                                        'ids' => NULL,
                                        'hasAll' => false,
                                    );
                                }
                            }
                        }
                    }else if ($permissionType == 'categoriesValues'){
                        if ($app['hasAll']){
                            $query[] = array(
                                'groupID' => $id,
                                'app' => $appName,
                                'key' => 'categories',
                                'value' => true,
                                'ids' => NULL,
                                'hasAll' => true,
                            );
                        }else{
                            $IDs = array();
                            $c=0;
                            foreach ($permission as $category){
                                $IDs['ID'.$c] = $category['categoryID'];
                                $c++;
                            }
                            if (count($IDs)){
                                $query[] = array(
                                    'groupID' => $id,
                                    'app' => $appName,
                                    'key' => 'categories',
                                    'value' => true,
                                    'ids' => json_encode($IDs),
                                    'hasAll' => false,
                                );
                            }
                        }
                    }
                }
            }
        }

        return $query;
    }

}