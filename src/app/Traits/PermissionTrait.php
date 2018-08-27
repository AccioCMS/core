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
     * Checks if a permission exists
     *
     * @param string $app
     * @param string $key
     * @return boolean
     */
    public static function exists($app = 'global', $key){
        return ((new static())->where('app',$app)->where('key', $key)->where('value', true)->count() ? true : false);
    }

}