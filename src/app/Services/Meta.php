<?php

namespace Accio\App\Services;

use App\Models\Language;
use Mockery\Exception;

class  Meta
{
    /**
     * Meta data list
     * @var array
     */
    private $metaList = [];

    /**
     * Which meta tags are allowed to be duplicate?
     * @var array
     */
    private $allowedDuplicateMeta = [
      "article:section",
      "article:tag"
    ];

    /**
     * Meta title
     * @var string
     */
    private $metaTitle;

    /**
     * Canonical
     * @var string
     */
    private $canonical;

    /**
     * Extra mata data needed to be used in wild cards
     * @var array $extraMetaData
     */
    private $wildcardData = [];

    /**
     * Href lang alternate
     * @var array  $hreflang
     */
    private $hreflang = [];

    /**
     * A single model data
     * @var object $modelData
     */
    private $modelData;

    private $metaIsPrinted = false;

    /**
     * Set meta
     * @param $name
     * @param $content
     * @param string $metaType
     * @param boolean $overwrite Overwrite previous defined meta
     * @return $this
     */
    public function set($name, $content, $metaType="name", $overwrite = true){
        if($content) {
            $content = strip_tags($content);
            if($overwrite) {
                if (in_array($name, $this->allowedDuplicateMeta)) {
                    $this->metaList[$name][] = [
                      'type' => $metaType,
                      'content' => $content
                    ];
                } else {

                    // limit lengs on description tag
                    if ($name == 'description' || $name == "og:description") {
                        $content = str_limit($content, 300);
                    }

                    $this->metaList[$name] = [
                      'type' => $metaType,
                      'content' => $content
                    ];
                }
            }
        }

        return $this;
    }

    /**
     * Get meta
     *
     * @param $name
     * @return mixed
     */
    public function get($name){
        return (isset($this->metaList[$name]) ? $this->metaList[$name] : []);
    }

    /**
     * Get meta tags
     * @return array
     */
    public function getMetaTags(){
        return $this->metaList;
    }

    /**
     * Get meta tags
     *
     * @param string $name
     * @return $this
     */
    public function removeMetaTag($name){
        if(isset($this->metaList[$name])){
            unset($this->metaList[$name]);
        }
        return $this;
    }

    /**
     * Checks if a meta tag exist
     *
     * @param $name
     * @return bool
     */
    public function metaExists($name){
        return (isset($this->metaList[$name]) ? true : false);
    }

    /**
     * Set title
     *
     * @param string $title
     * @return $this
     */
    public function setTitle($title){
        $this->metaTitle = $title;
        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle(){
        return $this->metaTitle;
    }

    /**
     * Remove title
     *
     * @return $this
     */
    public function removeTitle(){
        $this->setTitle(null);
        return $this;
    }

    /**
     * Set meta description
     *
     * @param object $objectData
     * @param array $customData
     * @return $this
     */
    public function setDescription($objectData, $customData = []){
        $this->set('description', $customData['description']);
        // Custom description
        if(isset($customData['description'])){
            $this->set('description', $customData['description']);
        }else{
            // Avoid duplicate meta tags
            if(!$this->metaExists('description') && isset($objectData->description)) {
                $this->set('description', $objectData->description);
            }
        }

        return $this;
    }


    /**
     * Set canonical
     *
     * @param string $url
     * @return $this
     */
    public function setCanonical($url){
        $this->canonical = $url;
        return $this;
    }

    /**
     * Get canonical
     *
     * @return string
     */
    public function getCanonical(){
        return $this->canonical;
    }

    /**
     * Remove canonical
     *
     * @return $this
     */
    public function removeCanonical(){
        $this->setCanonical(false);
        return $this;
    }

    /**
     * Set profile open graph
     * @param object $profileObj
     * @return $this
     */
    public function setProfileOG($profileObj){
        Meta::set("profile:first_name",$profileObj->firstName, "property");
        Meta::set("profile:last_name",$profileObj->lastName, "property");
        if(isset($profileObj->gender)){
            Meta::set("profile:gender",$profileObj->gender, "property");
        }
        return $this;
    }


    /**
     * Set article open graph
     * @param object $postObj
     * @return $this
     */
    public function setArticleOG($postObj){
        Meta::set("article:published_time", $postObj->created_at->format('c'), "propery");// When the article was first published.
        Meta::set("article:modified_time", $postObj->updated_at->format('c'), "propery");// When the article was last changed.

        // Author
        if($postObj->cachedUser()) {
            Meta::set("article:author", $postObj->cachedUser()->firstName . " " . $postObj->cachedUser()->lastName, "propery");// Writers of the article.
        }

        // Category
        if($postObj->cachedCategory) {
            Meta::set("article:section", $postObj->cachedCategory->title, "propery");// - A high-level section name. E.g. Technology
        }

        // Tags
        if($postObj->tags){
            foreach($postObj->tags as $tag){
                Meta::set("article:tag", $tag->title, "propery");// Tag words associated with this article.
            }
        }
        return $this;
    }
    /**
     * Set image open graph
     *
     * @param ojbect $imageObj
     * @return $this
     */
    public function setImageOG($imageObj){
        if($imageObj) {
            Meta::set("og:image", asset($imageObj->url), "property");
            Meta::set("og:image:type", $imageObj->type."/".str_replace("jpg", "jpeg", $imageObj->extension), "property");
            if($imageObj->description) {
                Meta::set("og:image:alt",$imageObj->description, "property");
            }

            if($imageObj->dimensions) {
                $image = explode("x", $imageObj->dimensions);
                Meta::set("og:image:width", $image[0], "property");
                Meta::set("og:image:height", $image[1], "property");
            }
        }
        return $this;
    }

    /**
     * Print meta html
     * @return $this
     */
    public function printMetaTags(){
        if(!$this->getMetaIsPrinted()){
            $this->printTitle();
            $this->printMetaTagsList();
            $this->printCanonical();
            $this->printHrefLang();
            $this->setMetaIsPrinted();
        }
        return $this;
    }

    /**
     * Set if media is printed
     */
    public function setMetaIsPrinted(){
        $this->metaIsPrinted = true;
    }

    /**
     * Get if media is printed
     */
    public function getMetaIsPrinted(){
        return $this->metaIsPrinted;
    }



    /**
     * Print title
     * @return $this
     */
    private function printTitle(){
        if($this->getTitle()){
            print '<title>'.$this->getTitle().'</title>'."\n";
        }
        return $this;
    }
    /**
     * Print meta tags list
     * @return $this
     */
    private function printMetaTagsList(){
        foreach($this->metaList as $metaName=>$metaData){
            if(in_array($metaName, $this->allowedDuplicateMeta)){
                foreach($metaData as $duplicateMetaData){
                    print '<meta '.$duplicateMetaData['type'].'="'.$metaName.'" content="'.$duplicateMetaData['content'].'" />'."\n";
                }
            }else{
                print '<meta '.$metaData['type'].'="'.$metaName.'" content="'.$metaData['content'].'" />'."\n";
            }

        }
        return $this;
    }

    /**
     * Print canonical
     * @return $this
     */
    private function printCanonical(){
        if($this->getCanonical()){
            print '<link rel="canonical" href="'.$this->getCanonical().'" />'."\n";
        }
        return $this;
    }

    /**
     * Parse a list of array as meta tags
     * @param array $attributes
     * @return string in form of param="value"
     */
    public function parseAttributes($attributes){
        $htmlMeta = '';
        foreach($attributes as $key=>$value){
            // false async means no async
            if($key == 'async' && $value == false){
                continue;
            }
            //convert boolean to string
            if($value === false){
                $value = 'false';
            }
            else if($value === true){
                $value = 'true';
            }

            $htmlMeta .= $key.'="'.$value.'" ';
        }
        return $htmlMeta;
    }

    /**
     * Set wildcards
     * @param $wildcardList
     * @return $this
     */
    public function setWildcards($wildcardList){
        $this->wildcardData = $wildcardList;
        return $this;
    }

    /**
     * Set wildcards
     * @return array
     */
    public function getWildcards(){
        return $this->wildcardData;
    }

    /**
     * Check if there is any undefined wildcard
     *
     * @param string $input
     * @param string $errorClass the class where the wildcard should be defined (if validation doesn't pass)
     *
     * @return boolean
     * @throws Exception
     */
    public function validateWildCards($input, $errorClass = ''){
        preg_match_all('@\{.*?\}@',  $input, $matches);
        $notDefined = [];

        if($matches) {
            foreach ($matches as $match) {
                foreach ($match as $wildcard) {
                    if (!array_key_exists($wildcard, $this->getWildcards())) {
                        $notDefined[] = $wildcard;
                    }
                }
            }
        }

        if($notDefined){
            throw new Exception('Not defined wildcards "'.implode($notDefined,', ').'". '.($errorClass ? 'Please declare them on "metaData" method of "'.$errorClass.'".' : ''));
        }

        return true;
    }

    /**
     * Replace wildcards
     *
     * @param string $input
     * @param string $errorClass the class where the wildcard should be defined (if validation doesn't pass)
     * @return string
     */
    public function replaceWildcards($input, $errorClass = ''){
        if ($this->validateWildCards($input, $errorClass)) {
            return str_replace(array_keys($this->getWildcards()), array_values($this->getWildcards()), $input);
        }
    }

    /**
     * Get href lang alternate list
     *
     * @return array
     */
    public function getHrefLang(){
        return $this->hreflang;
    }

    /**
     * Set href lang alternate list
     *
     * @param $url
     * @param $hreflang
     *
     * @return $this
     */
    public function setHrefLang($url, $hreflang){
        if($url && $hreflang) {
            $this->hreflang[] = [
              'url' => $url,
              'lang' => $hreflang
            ];
        }
        return $this;
    }

    public function printHrefLang(){
        if(config('project.multilanguage') ) {
            foreach ($this->getHrefLang() as $hreflang) {
                print '<link rel="alternate" href="' . $hreflang['url'] . '" hreflang="' . $hreflang['lang'] . '" />' . "\n";
            }
        }
    }

    /**
     * Set href lang data
     *
     * @param object $model
     * @return $this
     */
    public function setHrefLangData($model, $routeName = ''){
        foreach(Language::getVisibleList() as $language){
            Meta::setHrefLang($model->translate($language->slug)->href($routeName), $language->slug);
        }
        return $this;
    }

    /**
     * Set model data
     *
     * @param $modelData
     * @return $this;
     */
    public function setModelData($modelData){
        $this->modelData = $modelData;
        return $this;
    }

    /**
     * Get model data
     *
     * @return object
     */
    public function getModelData(){
        return $this->modelData;
    }
}