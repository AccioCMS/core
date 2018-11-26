<?php

namespace Accio\App\Traits;


use App\Models\Media;

trait CustomFieldsValuesTrait{

    /**
     * Get custom field value by key.
     *
     * @param string $key
     * @return mixed
     */
    public function customFieldValue($key){
        $value = null;
        if($this->hasCustomField($key)) {
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
     * Check if a given key is a custom field.
     *
     * @param string $key
     * @return bool
     */
    public function hasCustomField($key){
        if($key !== 'customFields' && !array_key_exists($key, $this->getAttributes())){
            if(isset($this->customFields->$key)){
                return true;
            }
        }
        return false;
    }


    /**
     * Replaces mediaIDs eith the actuall media object.
     *
     * @param array ...$slugs slugs of media in custom field group
     * @return array
     */
    public function getMediaFromCustomFields($customFields, ...$slugs){
        $mediaIDs = [];
        foreach($customFields as $fields){
            foreach($fields as $field => $value){
                if(in_array($field, $slugs)){
                    if(!is_array($value)){
                        $mediaIDs[] = $value;
                    }else{
                        foreach($value as $subValue){
                            $mediaIDs[] = $subValue;
                        }
                    }
                }
            }
        }

        $media = Media::whereIn("mediaID", $mediaIDs)->get()->keyBy("mediaID")->toArray();

        $object = [];
        if(count($media)) {
            foreach ($customFields as $fields) {
                foreach($fields as $field => $value){
                    if(in_array($field, $slugs)){
                        if($value && isset($media[$value])){
                            $tmp = [];
                            if (!is_array($value)) {
                                $fields->$field = new Media($media[$value]);
                            } else {
                                foreach ($value as $subValue) {
                                    $tmp[] = new Media($media[$value]);
                                }
                                $fields->$field = $tmp;
                            }
                        }else{
                            $fields->$field = null;
                        }
                    }
                }
                $object[] = $fields;
            }
        }else{
            $object = $customFields;
        }

        return collect($object);
    }
}