<?php
/**
 * Created by PhpStorm.
 * User: sopa
 * Date: 05/10/2017
 * Time: 5:26 PM
 */

namespace Accio\App\Traits;


use App\Models\CustomFieldGroup;
use App\Models\Media;

trait CustomFieldTrait{

    public function constructValues(array $customFieldGroups, \stdClass $customFields){
        // get only the values of the allowed custom fields in update form
        $allowedCustomFields = [];
        $fields = [];
        foreach ($customFieldGroups as $customFieldGroup){
            array_push($allowedCustomFields, $customFieldGroup->slug);
            foreach ($customFieldGroup->fields as $fieldKey => $fieldVal){
                $fields[$customFieldGroup->slug."__".$fieldVal->slug] = $fieldVal;
            }
        }

        // get custom field values
        foreach($customFields as $fieldKey => $fieldVal){
            $groupKey = explode('__', $fieldKey)[0];
            if (in_array($groupKey, $allowedCustomFields)) {
                $this->customFieldValues[$fieldKey] = $fieldVal;
                // if custom field type is file
                if (($fields[$fieldKey]->type == 'image' || $fields[$fieldKey]->type == 'video' || $fields[$fieldKey]->type == 'file') && count($fieldVal)) {
                    if (!$fields[$fieldKey]->isTranslatable) {
                        $tmpMedia = Media::whereIn('mediaID', $fieldVal)->get();
                        $this->media[$fieldKey] = $tmpMedia;
                    }else{
                        // if custom field is multi-language loop throw all languages
                        foreach($fieldVal as $langKey => $val){
                            // if custom field is multiple files (array)
                            if(gettype($val) == 'array'){
                                $tmpMedia = Media::whereIn('mediaID', $val)->get();
                            }else{
                                // if custom field can have onli one file (is not multiple)
                                $tmpMedia = Media::where('mediaID', $val)->get();
                            }
                            $this->media[$fieldKey.'__lang__'.$langKey] = $tmpMedia;
                        }
                    }
                }else if ($fields[$fieldKey]->type == 'repeater') {
                    if ($fields[$fieldKey]->isTranslatable) {
                        // loop throw languages
                        foreach ($fieldVal as $langKey => $langArr) {
                            // loop throw repeater array (group of custom fields)
                            foreach ($langArr as $subFieldGroupIndex => $subFieldsGroup) {
                                // custom fields of a repeater row
                                foreach ($subFieldsGroup as $subFieldKey => $subFieldVal) {
                                    if (($fields[$subFieldKey]->type == 'image' || $fields[$subFieldKey]->type == 'file') && count($subFieldVal)) {
                                        if (is_array($subFieldVal)) {
                                            $tmpMedia = Media::whereIn('mediaID', $subFieldVal)->get();
                                            $this->media[$fieldKey . "___" . $subFieldGroupIndex . "___" . $subFieldKey . "___lang___" . $langKey] = $tmpMedia;
                                        } else {
                                            $tmpMedia = Media::where('mediaID', $subFieldVal)->get();
                                            $this->media[$fieldKey . "___" . $subFieldGroupIndex . "___" . $subFieldKey . "___lang___" . $langKey] = $tmpMedia;
                                        }
                                    }
                                }
                            }
                        }
                    }else {
                        // loop throw repeater array (group of custom fields)
                        foreach ($fieldVal as $subFieldGroupIndex => $subFieldsGroup) {
                            // custom fields of a repeater row
                            foreach ($subFieldsGroup as $subFieldKey => $subFieldVal) {
                                if (($fields[$subFieldKey]->type == 'image' || $fields[$subFieldKey]->type == 'file') && count($subFieldVal)) {
                                    if (is_array($subFieldVal)) {
                                        $tmpMedia = Media::whereIn('mediaID', $subFieldVal)->get();
                                        $this->media[$fieldKey . "___" . $subFieldGroupIndex . "___" . $subFieldKey] = $tmpMedia;
                                    } else {
                                        $tmpMedia = Media::where('mediaID', $subFieldVal)->get();
                                        $this->media[$fieldKey . "___" . $subFieldGroupIndex . "___" . $subFieldKey] = $tmpMedia;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

    }
}