<?php
/**
 * Created by PhpStorm.
 * User: sopa
 * Date: 20/10/2017
 * Time: 10:11 AM
 */

namespace Accio\App\Traits;


use App\Models\Media;

trait CustomFieldsValuesTrait
{

    /**
     * Get custom field value by key
     *
     * @param $key
     * @return mixed
     */
    public function customFieldValue($key){
        $value = null;
        if($this->isCustomField($key)) {
            //if translate is enabled in a specific language
            if(isset($this->customFields->$key->{$this->getTranslateLanguage()})){
                $value = $this->customFields->$key->{$this->getTranslateLanguage()};
            }
            else {
                $value = $this->customFields->$key;
            }
        }

        //rollback to default language
        $this->resetTranslateLanguage();

        return $value;
    }

    /**
     * Check if a given key is a custom field
     *
     * @param $key
     * @return bool
     */
    public function isCustomField($key){
        if($key !== 'customFields' && !array_key_exists($key, $this->getAttributes())){
            if(isset($this->customFields->$key)){
                return true;
            }
        }
        return false;
    }

    /**
     * Replaces mediaIDs eith the actuall media object
     *
     * @param array $customFields group of the custom field
     * @param array ...$slugs slugs of media in custom field group
     * @return array
     */
    public function getMediaFromCustomFields($customFields, ...$slugs){
        $ids = [];
        foreach($customFields as $customFieldsGroup){
            foreach($customFieldsGroup as $group){
                foreach($group as $field => $value){
                    if(in_array($field, $slugs)){
                        if(!is_array($value)){
                            $ids[] = $value;
                        }else{
                            foreach($value as $subValue){
                                $ids[] = $subValue;
                            }
                        }
                    }
                }
            }
        }
        $media = Media::whereIn("mediaID", $ids)->get()->keyBy("mediaID")->toArray();

        $object = [];
        foreach($customFields as $cKey => $customFieldsGroup){
            foreach($customFieldsGroup as $group){
                foreach($group as $field => $value){
                    if(in_array($field, $slugs)){
                        if(!is_array($value)){
                            $group->$field = new Media($media[$value]);
                        }else{
                            $tmp = [];
                            foreach($value as $subValue){
                                $tmp[] = new Media($media[$value]);
                            }
                            $group->$field = $tmp;
                        }
                    }
                }
                $object[$cKey][] = $group;
            }
        }

        return $object;
    }
}