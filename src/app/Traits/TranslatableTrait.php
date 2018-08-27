<?php
/**
 * Created by PhpStorm.
 * User: sopa
 * Date: 19/10/2017
 * Time: 10:34 PM
 */

namespace Accio\App\Traits;


use App\Models\Language;
use Illuminate\Support\Facades\App;

trait TranslatableTrait
{

    protected static $counti = 0;
    /**
     * Specifies the language that data should be translated when they are called
     * @var
     */
    protected $_translateLanguage;

    /**
     * Specifies the default translate language that data should be translated when they are called
     * @var
     */
    protected $_defaultTranslateLanguage;

    /**
     * Defines if translatable attributes should be auto translate when called
     * @var bool
     */
    protected $autoTranslate = true;

    /**
     * Set auto translate
     * @param boolean $autoTranslate
     * @return $this
     */
    public function setAutoTranslate($autoTranslate){
        $this->autoTranslate = $autoTranslate;
        return $this;
    }

    /**
     * Get auto translate
     */
    public function getAutoTranslate(){
        return $this->autoTranslate;
    }

    /**
     * Get class attributes
     * @param string $key
     *
     * @return mixed
     */
    public function getAttribute($key)
    {
        //set default language
        if(!$this->_defaultTranslateLanguage){
            $this->setDefaultTranslateLanguage();
        }

        // In case we just need the translation for current property
        if(!$this->getTranslateLanguage()){
            $this->translate($this->_defaultTranslateLanguage);
        }

        //handle custom fields
        if(method_exists($this,'isCustomField') && $this->isCustomField($key)) {
            $value = $this->customFieldValue($key);

            // If the given $attribute has a mutator, we push it to $attributes and then call getAttributeValue
            // on it. This way, we can use Eloquent's checking for Mutation, type casting, and
            // Date fields.
            if ($this->hasGetMutator($key)) {
                $this->attributes[$key] = $value;
                $value = $this->getAttributeValue($key);
            }
        }
        //is translatable value
        else{
            $value = parent::getAttribute($key);

            if ($this->autoTranslate && $this->isTranslatable($value, $key)) {

                // If the given $attribute has a mutator, we push it to $attributes and then call getAttributeValue
                // on it. This way, we can use Eloquent's checking for Mutation, type casting, and
                // Date fields.
                if ($this->hasGetMutator($key)) {
                    $this->attributes[$key] = $this->getTranslation($value);
                    $value = $this->getAttributeValue($key);
                }else {
                    $value = $this->getTranslation($value);
                }

                // we don't need current language any more, we have the default une
                $this->resetTranslateLanguage();
            }else{

                // If the given $attribute has a mutator, we push it to $attributes and then call getAttributeValue
                // on it. This way, we can use Eloquent's checking for Mutation, type casting, and
                // Date fields.
                if ($this->hasGetMutator($key)) {
                    $this->attributes[$key] = $value;
                    $value =  $this->getAttributeValue($key);
                }
            }
        }

        return $value;
    }


    /**
     * Translate an object into a specific language
     *
     * @param $languageSlug
     * @return $this
     */
    public function translate($languageSlug = ''){
        $this->_translateLanguage = $languageSlug;
        return $this;
    }
    /**
     * Get current translating language
     * @return string
     */
    private function getTranslateLanguage(){
        return $this->_translateLanguage;
    }

    /**
     * Get current translating language
     * @return string
     */
    private function resetTranslateLanguage(){
        $this->_translateLanguage = $this->_defaultTranslateLanguage;
    }

    /**
     * Set default language
     *
     * @param string $languageSlug
     * @return $this
     */
    public function setDefaultTranslateLanguage($languageSlug = ''){
        if(!$languageSlug){
            $languageSlug = App::getLocale();
        }
        $this->_defaultTranslateLanguage = $languageSlug;
        return $this;
    }

    /**
     * Gets current language value
     *
     * @param  array|object|string $value The rows to be translated
     *
     * @return mixed  Returns the translated value
     * */
    public function getTranslation($value){
        // in case cast is not used, we need to manually convert the value to object
        if(!is_object($value) && !is_array($value)){
            $value = json_decode($value);
        }

        $translatedValue = '';
        //is an object
        if (is_object($value)) {
            if (isset($value->{$this->getTranslateLanguage()})) {
                $translatedValue = $value->{$this->getTranslateLanguage()};
            }
        }
        //is an array
        else if (is_array($value)) {
            if (isset($value[$this->getTranslateLanguage()])) {
                $translatedValue = $value[$this->getTranslateLanguage()];
            }
        }
        return $translatedValue;
    }

    /**
     * Check if a value has language keys
     * @param string $value
     * @return bool
     */
    public function isTranslatable($value, $key){

        // in case cast is not used, we need to manually convert the value to object
        if(!is_object($value) && !is_array($value)){
            $value = json_decode($value);
        }

        // if current language is present as key
        if (isset($value->{$this->getTranslateLanguage()})) {
            return true;
        }

        // verify if this is another language
        // TODO find a better way of verification
        $valueToArray = key((array) $value);
        if(strlen($valueToArray) == 2){
            return true;
        }

        return false;
    }


    /**
     * Add language keys in translatable json columns where a key doesn't exits
     *
     * @return $this
     */
    public function appendLanguageKeys(){
        $attributes = $this->getAttributes();
        foreach($attributes as $attrKey => $attr){
            if(isset($this->translatableColumns[$attrKey])){
                if(is_array($attr)){
                    continue;
                }
                if($attr == null || $attr == "" || $attr == '[]'){
                    $attr = new \stdClass();
                }else{
                    if(!is_object($attr)){
                        $attr = json_decode($attr);
                    }
                }
                foreach(Language::cache()->getItems() as $language){
                    $langSlug = $language->slug;

                    if (!isset($attr->$langSlug)){
                        if($this->translatableColumns[$attrKey] == "string"){
                            $attr->$langSlug = "";
                        }else{
                            $attr->$langSlug = [];
                        }
                    }
                }
                $this->$attrKey = $attr;
            }
        }
        return $this;
    }

}