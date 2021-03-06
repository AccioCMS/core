<?php

namespace Accio\App\Traits;

use App\Models\CategoryRelation;
use App\Models\MediaRelation;
use App\Models\MenuLink;
use App\Models\Post;
use App\Models\PostType;
use App\Models\TagRelation;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;
use Carbon\Carbon;
use Datetime;

trait PostTrait
{


    /**
     * Noty message.
     *
     * @var array $notyList
     */
    protected $notyMessages = [];

    public function getNotyMessages()
    {
        return $this->notyMessages;
    }

    /**
     * Noty messages.
     * retrun nory messages array structure.
     *
     * @param string $type
     * @param string $message
     * @param string $key
     */
    public function noty($type, $message, $key = "")
    {
        array_push(
            $this->notyMessages, [
            'key' => $key,
            'type' => $type,
            'message' => $message,
            ]
        );
        return;
    }

    /**
     * Find a post by slug.
     *
     * @param string $slug         the slug of the post
     * @param string $postTypeSlug the name of the post type, ex. post_services
     *
     * @return object|null Returns post as array or null if not found
     **/
    public static function findBySlug($slug, $postTypeSlug = '')
    {
        $postTypeSlug = ($postTypeSlug ? $postTypeSlug : PostType::getSlug());

        $postObj = (new Post())->setTable($postTypeSlug);
        $post = $postObj
            ->where('slug_'.App::getLocale(), $slug)
            ->with($postObj->getDefaultRelations(getPostType($postTypeSlug)))
            ->first();

        return $post;
    }

    /**
     * Find a post by ID.
     *
     * @param int    $postID       ID of the post
     * @param string $postTypeSlug Name of the post type, ex. post_services
     *
     * @return object|null Returns post as array or null if not found
     **/
    public static function findByID($postID, $postTypeSlug = '')
    {
        $postTypeSlug = ($postTypeSlug ? $postTypeSlug : PostType::getSlug());
        $postObj = (new Post())->setTable($postTypeSlug);
        $post = $postObj
            ->where('postID', $postID)
            ->with($postObj->getDefaultRelations(getPostType($postTypeSlug)))
            ->first();
        return $post;
    }

    /**
     * Store (Create or Update) the post and its related data (ex. media, categories, tags) in the database
     *
     * @param  $data array of data from request
     * @return array Returns 200 if successful, 500 if any internal error is found
     * @throws \Exception
     * */
    public static function store($data)
    {
        // return errors if there are any
        $errorMessages = self::validateStore($data);
        if(count($errorMessages)) {
            return array('error' => true, 'data' => [], 'errorMessages' => $errorMessages, 'noty' => []);
        }

        // Set posts table
        $postObj = new Post();
        $postObj->setTable($data['postType']);
        $postType = PostType::findBySlug($data['postType']);

        // on create
        if(!isset($data['postID'])) {
            $populatedFields = self::populateStoreColumns($postObj, $data);
            $populatedFields['post']->createdByUserID = (!User::isAdmin() || !isset($data['createdByUserID'])) ? Auth::user()->userID : $data['createdByUserID'];
            $postObj = $populatedFields['post'];

            if(!$postObj->save()) {
                throw new \Exception("Post could not be saved");
            }

            $postID = $postObj->postID;
        }else{ // on update
            $postObj = $postObj->where('postID', $data['postID'])->first();

            if($postObj) {
                $populatedFields = self::populateStoreColumns($postObj, $data);
                $populatedFields['post']->createdByUserID = (!User::isAdmin() || !isset($data['createdByUserID'])) ? Auth::user()->userID : $data['createdByUserID'];
                $postObj = $populatedFields['post'];

                if($postObj->save()) {
                    // Delete existing relations, to ensure accuracy
                    if($postType->hasCategories) {
                        DB::table($data['postType'].'_categories')->where("postID", $data['postID'])->delete();
                    }

                    if($postType->hasTags) {
                        DB::table($data['postType'].'_tags')->where("postID", $data['postID'])->delete();
                    }
                    DB::table($data['postType'].'_media')->where("postID", $data['postID'])->delete();
                }

                $postID = $postObj->postID;
            }else{
                throw new \Exception("Trying to edit a post that doesn't exist!");
            }
        }

        if($postID) {
            if($postType->hasCategories) {
                // Insert categories
                self::insertCategories($data['selectedCategories'], $postObj->postID, $data['postType']);
            }

            if($postType->hasTags) {
                // Insert tags
                self::insertTags($data['selectedTags'], $postObj->postID, $postType);
            }

            // Insert media
            self::insertMedia($data['files'], $postObj->postID, $data['postType'], $data['languages'], $populatedFields['files'], $data['filesToBeIgnored']);

            // Event fired after post is stored
            Event::fire('post:stored', [$data, $postObj]);

            return [
              'error' => false,
              'postID' => $postID,
              'postType' => $data['postType'],
              'noty' => $postObj->getNotyMessages()
            ];
        }else{
            return [
              'error' => true,
              'errorMessages' => $errorMessages,
              'noty' => $postObj->getNotyMessages()
            ];
        }
    }

    /**
     * Prepares an array with object to be used from the Laravel Validator class and validates the inputs.
     *
     * @param array $data all data from the store request
     *
     * @return array of messages if validation is not passed
     * */
    private static function validateStore($data)
    {
        // custom messages for validation
        $validationMessagesTemplate = array(
          'required'=> "{{field}} can't be empty",
          'email'=>"{{field}} must be an email",
          'integer'=>"{{field}} must be a number",
        );
        $validationMessages = array();
        $validationRules = array();
        $validationValues= array();

        //setup title validation
        $isOneTitleFilled = false;
        foreach ($data['title'] as $title){
            if($title !== '') {
                $isOneTitleFilled = true;
            }
        }
        if (!$isOneTitleFilled) {
            foreach ($data['languages'] as $lang) {
                $validationMessages["title_" . $lang['slug'].".required"] = str_replace("{{field}}", "Title", $validationMessagesTemplate["required"]);
                $validationRules["title_" . $lang['slug']] = 'required';
                $validationValues["title_" . $lang['slug']] = $data['title'][$lang['slug']];
            }
        }

        // Custom fields validation
        foreach($data['formData'] as $formData){ // loop throw form data
            if ($formData['required']) { // check if input is required
                if(!$formData['translatable']) { // if input is not multilanguage
                    foreach ($data['languages'] as $lang){
                        // if it is not a file return required error
                        if($formData['type']['inputType'] != "image" && $formData['type']['inputType'] != "file") {
                            if(isset($data['status']) && $data['status'][$lang['slug']] != 'draft') {
                                $validationMessages[$formData['slug'] . "_" . $lang['slug'].".required"] = str_replace("{{field}}", $formData['name'], $validationMessagesTemplate["required"]);
                                $validationRules[$formData['slug'] . "_" . $lang['slug']] = 'required';
                                $validationValues[$formData['slug']. "_" . $lang['slug']] = $formData['value'];
                            }
                        }else{
                            if($formData['translatable']) {
                                $imageKey = $formData['slug']."_".$lang['slug'];
                            }else{
                                $imageKey = $formData['slug'];
                            }

                            if(isset($data['status']) && $data['status'][$lang['slug']] != 'draft') {
                                $validationMessages[$formData['slug'] . "_" . $lang['slug'].".required"] = str_replace("{{field}}", $formData['name'], $validationMessagesTemplate["required"]);
                                $validationRules[$formData['slug'] . "_" . $lang['slug']] = 'required';
                            }

                            if(!isset($data['files'][$imageKey])) {  // errors if images or files are not set and the language is status
                                if(isset($data['status']) && $data['status'][$lang['slug']] != 'draft') {
                                    $validationValues[$formData['slug']. "_" . $lang['slug']] = "";
                                }
                            }else{
                                $validationValues[$formData['slug']. "_" . $lang['slug']] = $data['files'][$imageKey];
                            }
                        }
                    }

                }else{ // if input is multilanguage
                    if ($formData['value']) {
                        foreach($formData['value'] as $langSlug => $value){ // loop throw the values for each language
                            if(isset($data['status']) && !is_integer($langSlug) && $data['status'][$langSlug] != 'draft') {
                                $validationMessages[$formData['slug'] . "_" . $langSlug.".required"] = str_replace("{{field}}", $formData['name'], $validationMessagesTemplate["required"]);
                                $validationRules[$formData['slug'] . "_" . $langSlug] = 'required';
                            }

                            //if is dropdown or checbox
                            if(is_array($value)) {
                                if(isset($data['status']) && !is_integer($langSlug) && $data['status'][$langSlug] != 'draft') {
                                    $validationValues[$formData['slug']. "_" . $langSlug] = $value;
                                }
                            }else {
                                if(isset($data['status']) && !is_integer($langSlug) && $data['status'][$langSlug] != 'draft') {
                                    $validationValues[$formData['slug']. "_" . $langSlug] = $value;
                                }
                            }
                        }
                    }
                }
            }

            /*
             * CUSTOM validations like email, number etc
             * */
            switch ($formData['type']['inputType']){
            case "email":
                foreach ($data['languages'] as $lang){
                    if(isset($data['status']) && $data['status'][$lang['slug']] != 'draft') {
                        $validationMessages[$formData['slug'] . "_" . $lang['slug'] . ".email"] = str_replace("{{field}}", $formData['name'], $validationMessagesTemplate["email"]);

                        //set email rule
                        if(isset($validationRules[$formData['slug'] . "_" . $lang['slug']])) {
                            $validationRules[$formData['slug'] . "_" . $lang['slug']] .= '|email';
                        }else{
                            $validationRules[$formData['slug'] . "_" . $lang['slug']] = 'email';
                        }
                    }
                }
                break;
            case "number":
                foreach ($data['languages'] as $lang){
                    if(isset($data['status']) && $data['status'][$lang['slug']] != 'draft') {
                        $validationMessages[$formData['slug'] . "_" . $lang['slug'] . ".integer"] = str_replace("{{field}}", $formData['name'], $validationMessagesTemplate["integer"]);

                        //set email rule
                        if(isset($validationRules[$formData['slug'] . "_" . $lang['slug']])) {
                            $validationRules[$formData['slug'] . "_" . $lang['slug']] .= '|integer';
                        }else{
                            $validationRules[$formData['slug'] . "_" . $lang['slug']] = 'integer';
                        }
                    }
                }
                break;
            }
        }

        // Validate tags
        if($data['isFeaturedImageRequired']) {
            if(!isset($data['files']) || !isset($data['files']['featuredImage']) || (isset($data['files']['featuredImage']) && !count($data['files']['featuredImage']))) {
                $validationMessages["files_featuredImage.required"] = str_replace("{{field}}", "Featured Image ", $validationMessagesTemplate["required"]);
                $validationRules["files_featuredImage"] = 'required';
                $validationValues["featuredImage"] = "NONE";
            }
        }

        // Validatate categories
        if($data['isCategoryRequired']) {
            $validationMessages["categories.required"] = str_replace("{{field}}", "Categories", $validationMessagesTemplate["required"]);
            $validationRules["categories"] = 'required';
            $validationValues["categories"] = $data['selectedCategories'];
        }

        // Validate tags
        if($data['isTagRequired']) {
            foreach($data['selectedTags'] as $langKey => $tags){
                if(isset($data['status']) && $data['status'][$langKey] != 'draft') {
                    $validationMessages["tags_" . $langKey .".required"] = str_replace("{{field}}", "Tags", $validationMessagesTemplate["required"]);
                    $validationRules["tags_" . $langKey] = 'required';
                    $validationValues["tags_" . $langKey] = $data['selectedTags'][$langKey];
                }
            }
        }

        $validator = \Illuminate\Support\Facades\Validator::make($validationValues, $validationRules, $validationMessages);
        return $validator->messages();
    }

    /**
     * Prepares a array to be stored in the database.
     *
     * @param  object $postObj
     * @param  array  $data    all data from the store request
     * @return array with post object prepared to be inserted to DB and array of file slugs
     * @throws \Exception
     */
    private static function populateStoreColumns($postObj, $data)
    {
        $files = array();
        //setup values for insert/update
        foreach($data['formData'] as $formData){ // loop throw form data
            if($formData['type']['inputType'] !== "image" && $formData['type']['inputType'] !== "video" && $formData['type']['inputType'] !== "file") {
                if(!isset($formData['slug'])) {
                    throw new \Exception("Slug missing at ".$formData['name']. ". Please go to post type and delete this field");
                }
                $fieldName = $formData['slug'];
                // store all data values in post array
                if($formData['translatable'] == true) {
                    // if value is empty make it NULL
                    if(!isset($formData['value']) || !$formData['value']) {

                        $postObj->$fieldName = null;
                    }else{
                        $postObj->$fieldName = self::handleObjectOrArrayValues($formData, true, $data['languages']);
                    }
                }else{
                    // if value is empty make it NULL
                    if(!isset($formData['value']) || !$formData['value']) {
                        $postObj->$fieldName = null;
                    }else{
                        if(is_array($formData['value']) || is_object($formData['value'])) {
                            $postObj->$fieldName = self::handleObjectOrArrayValues($formData, false);
                        }else{
                            $postObj->$fieldName = $formData['value'];
                        }
                    }
                }
            }else{
                array_push($files, $formData['slug']);
            }
        }

        //if the published_at date is not set
        if(!$data['published_at']['dateFormatted']) {
            $date = Carbon::now();
        }else{
            // if the time is not set
            if($data['published_at']['time']['HH'] == "") {
                $date = new DateTime($data['published_at']['dateFormatted']);
            }else{
                $date = new DateTime($data['published_at']['dateFormatted']." ".$data['published_at']['time']['HH'].":".$data['published_at']['time']['mm']);
            }
        }
        // finalize published at
        $postObj->published_at = $date;

        if(!isset($data['postID'])) {
            $postObj->created_at = Carbon::now();
        }
        $postObj->title = $data['title'];
        $postObj->updated_at = Carbon::now();
        $postObj->status = $data['status'];
        $postObj->content = $data['content'];
        $postObj->slug = $data['slug'];
        $postObj->customFields = $data['customFieldValues'];

        // feature image
        if (isset($data['files']['featuredImage']) && $data['files']['featuredImage'][0]['mediaID']) {
            $postObj->featuredImageID = $data['files']['featuredImage'][0]['mediaID'];
        }

        // feature video
        if (isset($data['files']['featuredVideo']) && $data['files']['featuredVideo'][0]['mediaID']) {
            $postObj->featuredVideoID = $data['files']['featuredVideo'][0]['mediaID'];
        }

        return array(
          'post' => $postObj,
          'files' => $files
        );
    }

    /**
     * Handles post type field values.
     *
     * @param  array   $formData     data of a custom field
     * @param  boolean $translatable if this field is translatable
     * @param  array   $languages    list of languages data
     * @return string encoded value of a custom field
     * */
    private static function handleObjectOrArrayValues($formData, $translatable, $languages = [])
    {
        if($formData['type']['inputType'] == 'db') {
            $tmpArr = [];
            $primaryKey = "";
            if($formData['dbTable']['belongsTo'] == 'User') {
                $primaryKey = "userID";
            }else if($formData['dbTable']['belongsTo'] == 'PostType') {
                $primaryKey = "postID";
            }

            if($translatable) {
                if(is_array($formData['value']) && !count($formData['value'])) {
                    foreach ($languages as $language){
                        $tmpArr[$language['slug']] = "";
                    }
                    return json_encode($tmpArr);
                }

                if($formData['value'] != "") {
                    foreach ($formData['value'] as $langSlug => $valuesByLang){
                        if($valuesByLang == "") {
                            $tmpArr[$langSlug] = "";
                            continue;
                        }
                        if($formData['isMultiple']) {
                            $c = 0;
                            foreach ($valuesByLang as $singleValue){
                                if(isset($singleValue[$primaryKey])) {
                                    $tmpArr[$langSlug][] = $singleValue[$primaryKey];
                                    $c++;
                                }
                            }
                        }else{
                            if(isset($valuesByLang[$primaryKey])) {
                                $tmpArr[$langSlug] = $valuesByLang[$primaryKey];
                            }
                        }
                    }
                }
            }else{

                if($formData['isMultiple']) {
                    $c = 0;
                    foreach ($formData['value'] as $singleValue){
                        if(isset($singleValue[$primaryKey])) {
                            $tmpArr[] = $singleValue[$primaryKey];
                            $c++;
                        }
                    }
                }else{
                    if(isset($formData['value'][$primaryKey])) {
                        $tmpArr = $formData['value'][$primaryKey];
                    }
                }
            }

            return json_encode($tmpArr);
        }else{
            if($translatable) {
                return json_encode($formData['value']);
            }else{
                return implode(",", $formData['value']);
            }
        }
    }

    /**
     * Insert Post Categories.
     *
     * @param array  $selectedCategories The list of selected categories
     * @param int    $postID             ID of the Post
     * @param string $postTypeSlug       The slug of post type
     *
     * @return array List of inserted categories IDs
     * */
    public static function insertCategories($selectedCategories, $postID, $postTypeSlug)
    {
        if (count($selectedCategories)) {
            $categoriesIDs = [];
            $newCategoryRelation = [];
            foreach ($selectedCategories as $selectedCategory){
                $newCategoryRelation[] = [
                  'categoryID' => $selectedCategory['categoryID'],
                  'postID' => $postID,
                ];
                $categoriesIDs[] = $selectedCategory['categoryID'];
            }

            // if post is only in the archive
            // if post is in the main database
            $insertedCategories = (new CategoryRelation())->setTable($postTypeSlug.'_categories')->insert($newCategoryRelation);
            if ($insertedCategories) {
                return $categoriesIDs;
            }
        }
        return [];
    }

    /**
     * Insert Post tags.
     *
     * @param array  $selectedTags The list of selected tags
     * @param int    $postID       ID of the Post
     * @param object $postType     The slug of post type
     *
     * @return array  List of inserted media files
     * */

    public static function insertTags($selectedTags, $postID, $postType)
    {
        if(count($selectedTags)) {
            $tagsIDs = [];

            $newTagsRelations = [];
            foreach ($selectedTags as $langSlug => $selectedTagForLanguage){
                if($selectedTagForLanguage) {
                    foreach ($selectedTagForLanguage as $selectedTag){

                        //insert tag if it doesn't exist
                        if($selectedTag['tagID'] == 0) {
                            $tagSlug = str_slug($selectedTag['title'], '-');
                            $tagsID = DB::table('tags')->insertGetId(
                                [
                                'postTypeID' => $postType['postTypeID'],
                                'createdByUserID' => Auth::user()->userID,
                                'title' => $selectedTag['title'],
                                'description' => $selectedTag['description'],
                                'slug' => $tagSlug,
                                ]
                            );
                        }else{
                            $tagsID = $selectedTag['tagID'];
                        }

                        //add new tag relationship
                        $newTagsRelations[] = [
                          'tagID' => $tagsID,
                          'postID' => $postID,
                          'language' => $langSlug
                        ];
                        $tagsIDs[] = $tagsID;
                    }
                }
            }

            // if post is in the main database
            $insertedTags = (new TagRelation())->setTable($postType->slug.'_tags')->insert($newTagsRelations);
            if($insertedTags) {
                return $tagsIDs;
            }
        }
        return [];
    }

    /**
     * Insert Post media files.
     *
     * @param array  $mediaFiles           The list of media files from Post Request
     * @param int    $postID               ID of the Post
     * @param string $postTypeSlug         The slug of post type
     * @param array  $languages            The list of languages to add media files
     * @param array  $notTranslatableFiles Slugs of all selected files. Used to identify media files that are not translatable
     *
     * @return array  List of inserted media files
     * */
    public static function insertMedia($mediaFiles, $postID, $postTypeSlug, $languages, $notTranslatableFiles, $filesToBeIgnored = [])
    {
        $imagesArr = array();
        foreach($mediaFiles as $fileKey => $files){
            // feature image is treated as a default column
            if($fileKey == 'featuredImage'
                || $fileKey == 'featuredVideo'
                || in_array($fileKey, $filesToBeIgnored)
                || substr($fileKey, 0, 6) == 'plugin'
            ) {
                continue;
            }
            //if not translatable
            if(in_array($fileKey, $notTranslatableFiles)) {
                foreach ($files as $file){
                    $image = array();
                    $image['field'] = $fileKey;
                    $image['mediaID'] = $file['mediaID'];
                    $image['postID'] = $postID;

                    $languageAvailability = array();
                    foreach ($languages as $lang){
                        $languageAvailability[$lang['slug']] = true;
                    }
                    $image['language'] = json_encode($languageAvailability);
                    array_push($imagesArr, $image);
                }
            }else{ // if translatable
                $explodedFileKey = explode("__lang__", $fileKey);  // remove the "__lang__" part
                $fieldName = $explodedFileKey[0]; // The input name
                $langSlug = $explodedFileKey[1];  // The lang slug
                foreach ($files as $file){
                    $image['field'] = $fieldName;
                    $image['mediaID'] = $file['mediaID'];
                    $image['postID'] = $postID;

                    //store only the language which the input belongs to
                    $languageAvailability = array();
                    foreach ($languages as $lang){
                        if($lang['slug'] == $langSlug) {
                            $languageAvailability[$lang['slug']] = true;
                        }else{
                            $languageAvailability[$lang['slug']] = false;
                        }
                    }
                    $image['language'] = json_encode($languageAvailability);
                    array_push($imagesArr, $image);
                }
            }
        }

        $mediaSaved = (new MediaRelation())->setTable($postTypeSlug.'_media')->insert($imagesArr);
        if($mediaSaved) {
            return $imagesArr;
        }
        return [];
    }

    /**
     * Setup advanced search fields to be used in Posts advanced search.
     *
     * @param string $postType Slug of Post Type (ex. post_services)
     *
     * @return array
     * */
    public static function getAdvancedSearchFields($postType)
    {
        $postTypeFields = json_decode(DB::table('post_type')->where("slug", $postType)->first()->fields);
        $advancedSearchFields = array();
        foreach ($postTypeFields as $fieldArray){
            if($fieldArray->type->inputType == "image"
                || $fieldArray->type->inputType == "file"
                || $fieldArray->type->inputType == "editor"
                || $fieldArray->type->inputType == "checkbox"
                || $fieldArray->type->inputType == "dropdown"
                || $fieldArray->type->inputType == "radio"
            ) {
                continue;
            }
            $properties = array();
            foreach ($fieldArray as $key => $field){
                if($key == "name") {
                    $properties['name'] = $field;
                }else if($key == "type") {
                    $properties['type'] = $field->inputType;
                }else if($key == "slug") {
                    $properties['db-column'] = $field;
                }
            }
            if(count($properties)) {
                array_push($advancedSearchFields, $properties);
            }
        }
        return $advancedSearchFields;
    }

    /**
     *  Get a custom vuejs template for a particular default function.
     *
     * @param string $baseTemplateName The base name of Custom template (ex. 'Create' or 'Update')
     * @param string $postType         The slug of Post Type (ex. post_service)
     *
     * @return string Returns full view js custom design name if a file with that name is found (ex. CreateArticle.vue)
     * */
    public static function getCustomTemplate($baseTemplateName, $postType)
    {
        //fix post type name
        if(strstr($postType, '_')) {
            $explodePostTypeName = explode('_', $postType);
            $postType = $explodePostTypeName[1];
        }

        $postTypeFileName = $baseTemplateName.ucfirst($postType).".vue";
        if(file_exists($_SERVER['DOCUMENT_ROOT'].'/../resources/assets/js/components/posts/'.$postTypeFileName)) {
            return $baseTemplateName.ucfirst($postType);
        }
        return $baseTemplateName;
    }

    /**
     * Check if a post has featured image.
     *
     * @return boolean Returns true if found
     */
    public function hasFeaturedImage()
    {
        if($this->featuredImageID && $this->featuredImage) {
            return true;
        }
        return false;
    }

    /**
     * Check if a post has featured video.
     *
     * @return boolean Returns true if found
     */
    public function hasFeaturedVideo()
    {
        if($this->featuredVideoID && $this->featuredVideo) {
            return true;
        }
        return false;
    }

    /**
     * Get URL of post's featured image.
     *
     * @param int    $width
     * @param int    $height
     * @param string $defaultFeaturedImageURL The url of an image that should be returned if no featured image is found
     * @param array  $options
     *
     * @return string|null Returns url of featured image if found, null instead
     */
    public function featuredImageURL($width = null, $height = null, $defaultFeaturedImageURL = '', array $options = [])
    {
        $imageURL = null;
        if ($this->hasFeaturedImage()) {
            if (!$width && !$height) {
                $imageURL = url($this->featuredImage->url) . "?" . strtotime($this->updated_at);
            } else {
                $imageURL = $this->featuredImage->thumb($width, $height, $this->featuredImage, $options);
            }
        }

        if ($imageURL) {
            return $imageURL;
        }
        else if(!$imageURL && $defaultFeaturedImageURL) {
            // return default image if not image is found
            return $defaultFeaturedImageURL;
        }

        return null;
    }

    /**
     * Renders featured image of a post.
     *
     * @param  int    $width
     * @param  int    $height
     * @param  string $defaultFeaturedImageURL The url of an image that should be returned if no featured image is found
     * @return HtmlString Returns featured image html
     */
    public function printFeaturedImage($width = null, $height = null, $defaultFeaturedImageURL = '')
    {
        if($this->hasFeaturedImage()) {
            return new HtmlString(
                view()->make(
                    "vendor.posts.featuredImage", [
                    'imageURL' => $this->featuredImageURL($width, $height, $defaultFeaturedImageURL),
                    'featuredImage' => $this->featuredImage
                    ]
                )->render()
            );
        }
    }

    /**
     * Renders featured image of a post.
     *
     * @param  string $coverImage Absolute path of cover image
     * @return HtmlString Returns featured image html
     */
    public function printFeaturedVideo($coverImage = '')
    {
        if($this->hasFeaturedVideo()) {
            return new HtmlString(
                view()->make(
                    "vendor.posts.featuredVideo", [
                    'coverImage' => $coverImage,
                    'featuredVideo' => $this->featuredVideo
                    ]
                )->render()
            );
        }
    }


    /**
     * Render Tags of a post.
     *
     * @param string $customView Name of a custom blade.php file to render the template
     * @param string $ulClass
     *
     * @return HtmlString
     */
    public function printTags($customView = '', $ulClass ="")
    {
        if($this->hasTags()) {
            $tags = "tags";
            return new HtmlString(
                view()->make(
                    ($customView ? $customView : "vendor.tags.default"), [
                    'tagsList' => $this->$tags,
                    'ulClass' => $ulClass,
                    'postTypeSlug' => $this->getTable()

                    ]
                )->render()
            );
        }
    }

    /**
     * Check if a post has tags.
     *
     * @return boolean Returns true if found
     */
    public function hasTags()
    {
        $tags = "tags";
        $postType = getPostType($this->getTable());
        return ($postType->hasTags && isset($this->$tags) && !$this->$tags->isEmpty());
    }

    /**
     * Check if a post has a primary category.
     *
     * @return bool
     */
    public function hasCategory()
    {
        $postType = getPostType($this->getTable());
        return ($postType->hasCategories && isset($this->categories) && !$this->categories->isEmpty());
    }

    /**
     * Get posts a tag.
     * Accepts query parameters: limit, belongsTo.
     *
     * @param int   $limit
     * @param array $tagIDs
     *
     * @return mixed
     */
    public function getPostsByTags($limit = 6, $tagIDs = [])
    {
        $tags = "tags";
        // Validate post type
        if(!$tagIDs) {
            $tagIDs = [];
            foreach ($this->$tags as $tag) {
                $tagIDs[] = $tag->tagID;
            }
        }

        if($tagIDs) {
            $postsObj = new Post();
            $postsObj->setTable($this->getTable());
            $posts = $postsObj
                ->select('postID', 'title', 'featuredImageID', 'slug')
                ->join($this->getTable().'_tags', $this->getTable().'_tags.postID', $this->getTable() . '.postID')
                ->with('featuredImage')
                ->published()
                ->whereIn('tagID', $tagIDs)
                ->where('postID', "!=", $this->postID)
                ->orderBy('published_at', 'DESC')
                ->limit($limit)
                ->get();

            return $posts;
        }else{
            return collect();
        }
    }

    /**
     * Handle post's content.
     *
     * @return mixed
     */
    public function content()
    {
        ob_start();

        // Call pre events
        print $this->beforeContentEvents();
        print $this->content;
        print $this->afterContentEvents();

        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * Check if post is being used as menu link.
     *
     * @param  integer $postID ID of the post
     * @return bool is this post being used in menu links
     */
    public static function isInMenuLinks($postID, $postType)
    {
        $isInMenulinks = MenuLink::where('belongsToID', $postID)->where('belongsTo', $postType)->count();
        if ($isInMenulinks) {
            return true;
        }
        return false;
    }

    /**
     * Update post parameters in MenuLink.
     *
     * @param  object $post
     * @return void
     */
    public static function updateMenulink($post)
    {
        if(self::isInMenuLinks($post->postID, $post->getTable())) {
            $menuLinks = MenuLink::where('belongsToID', $post->postID)->where('belongsTo', $post->getTable())->get();
            foreach($menuLinks as $menuLink){
                $menuLink->params = $post->menuLinkParameters();
                $menuLink->save();
            }
        }
    }

    /**
     * Fire before content events.
     */
    public function beforeContentEvents()
    {
        event('theme:post:before_content', [$this]);
        event('theme:'.$this->getTable().':before_content', [$this]);
    }

    /**
     * Fire after content events.
     */
    public function afterContentEvents()
    {
        Event::fire('theme:post:after_content', [$this]);
        Event::fire('theme:'.$this->getTable().':after_content', [$this]);
    }

    /**
     * Fire before content events.
     */
    public function beforeListEvents()
    {
        Event::fire('theme:post:before_list', [$this]);
        Event::fire('theme:'.$this->getTable().':before_list', [$this]);
    }

    /**
     * Fire after content events.
     */
    public function afterListEvents()
    {
        Event::fire('theme:post:after_list', [$this]);
        Event::fire('theme:'.$this->getTable().':after_list', [$this]);
    }

    /**
     * Check if a post type has its on Controller (check made by patter {slug}Controller.php.
     *
     * @param  $postTypeSlug
     * @return bool
     */
    public static function haveItsOwnController($postTypeSlug)
    {
        $postTypeSlug = ucfirst(camel_case($postTypeSlug));
        $filePath = Theme::getPath() . '/controllers/'.$postTypeSlug.'Controller.php';

        if(file_exists($filePath)) {
            return true;
        }

        return false;
    }

    /**
     * Get Default routes for post types that do not have their own Controller.
     *
     * @param  object $postType
     * @return array
     */
    public static function getDefaultPostRoutes( $postType)
    {
        $baseRouteName = str_replace('_', '.', $postType->slug);

        return [
          'defaultRoute' => $baseRouteName.'.single',
          'list' => [
            $baseRouteName.'.single' => $postType->name.' single Post',
          ]
        ];
    }

    /**
     * Get default routes for a post type.
     *
     * @param  object $postType
     * @return array
     */
    public static  function getDefaultPostTypeRoutes($postType)
    {
        $baseRouteName = str_replace('_', '.', $postType->slug);

        return [
          'defaultRoute' => $baseRouteName.'.index',
          'list' => [
            $baseRouteName.'.index' => $postType->name.' Index'
          ]
        ];
    }

    /**
     * Get option value.
     *
     * @param  string $field
     * @return mixed
     * @throws \Exception
     */
    public function getOptionValue($field)
    {
        $postType = getPostType($this->getTable());
        if(!$postType) {
            throw new \Exception("Post type ".$this->getTable()."' does not exists!");
        }
        return $postType->getMultioptionFieldValue($field, $this->$field);
    }


    /**
     * This function creates the slug for a row of a model and makes sure that
     * slugs it is not being used from a other post.
     *
     * @return string unique slug
     * */
    public static function generateSlug($title, $tableName, $primaryKey, $languageSlug = '', $id = 0, $translatable = false, $delimiter = "-")
    {
        $count = 0;
        $found = true;
        $originalSlug = str_slug($title, $delimiter);

        while($found){
            if($count != 0) {
                $slug = $originalSlug.$delimiter.$count;
            }else{
                $slug = $originalSlug;
            }

            $countObj = DB::table($tableName);
            if ($translatable) {
                $countObj->where('slug->'.$languageSlug, $slug);
            }else{
                $countObj->where('slug', $slug);
            }
            if($id) {
                $countObj->where($primaryKey, '!=', $id);
            }
            $countPosts = $countObj->count();

            if(!$countPosts) {
                return $slug;
            }
            $count++;
        }
        return $originalSlug;
    }
}
