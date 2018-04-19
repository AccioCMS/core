<?php
/**
 * Created by PhpStorm.
 * User: Jetmir
 * Date: 1/25/2018
 * Time: 3:15 PM
 */

namespace Accio\App\Services;

use App\Models\CategoryRelation;
use App\Models\Post;
use App\Models\PostType;
use App\Models\TagRelation;
use App\Models\Task;
use Faker\Provider\ka_GE\DateTime;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\MySqlConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class Archive{

    /**
     * Object of DB
     * @var object
     */
    private $DB;


    /**
     * Count posts for each post type
     * @var array
     */
    private $postTypeCount = [];

    /**
     * Posts nr to be archive for each post type
     * @var array  $postNrToBeArchived
     */
    private $postNrToBeArchived = [];

    /**
     * Save DB connection with archive database
     * @var DB $DBArchive
     */
    private $DBArchive;

    /**
     * List of post types
     * @var array $postTypes
     */
    private $postTypes = [];


    /**
     * Category relations table name
     * @var string $categoryRelationsTable
     */
    private $categoryRelationsTable;

    /**
     * Tag relations table name
     * @var string $categoryRelationsTable
     */
    private $tagRelationsTable;


    public function __construct($run = true){
        if($run){
            return $this->run();
        }
    }

    /**
     * @param object $DB
     */
    public function setDB(MySqlConnection $DB): void{
        $this->DB = $DB;
    }

    /**
     * @param array $postTypeCount
     */
    public function setPostTypeCount(array $postTypeCount): void{
        $this->postTypeCount = $postTypeCount;
    }

    /**
     * @param array $postNrToBeArchived
     */
    public function setPostNrToBeArchived(array $postNrToBeArchived): void{
        $this->postNrToBeArchived = $postNrToBeArchived;
    }

    /**
     * @param DB $DBArchive
     */
    public function setDBArchive(MySqlConnection $DBArchive): void{
        $this->DBArchive = $DBArchive;
    }

    /**
     * @param array $postTypes
     */
    public function setPostTypes(Collection $postTypes): void{
        $this->postTypes = $postTypes;
    }

    /**
     * @param string $categoryRelationsTable
     */
    public function setCategoryRelationsTable(string $categoryRelationsTable): void{
        $this->categoryRelationsTable = $categoryRelationsTable;
    }

    /**
     * @param string $tagRelationsTable
     */
    public function setTagRelationsTable(string $tagRelationsTable): void{
        $this->tagRelationsTable = $tagRelationsTable;
    }

    /**
     * Archive Posts, Category and Tags relations
     * @return string
     */
    public function run(){
        $this->DB = DB::connection('mysql');
        $this->DBArchive = DB::connection('mysql_archive');
        $this->postTypes = PostType::all();
        $this->categoryRelationsTable = (new CategoryRelation)->getTable();
        $this->tagRelationsTable = (new TagRelation())->getTable();

        // TODO handle mesazhin "nothing to archive" kur ska qka me u arkivu

        // Count posts for each post type and estimate the number of posts to be archived
        $this->countAndEstimateNumberOfPost();

        // Move all posts, tags relations and categories relations to archive is archive is empty
        try{
            $this->moveAllPostsToArchive();
            $this->moveAllCategoriesToArchive();
            $this->moveAllTagsToArchive();
        }catch (\Exception $e){
            dd($e);
        }

        // Execute tasks of (CREATE, UPDATE, DELETE)
        $this->executeCUDTasks();

        try{
            $this->ensurePostsAreArchived();
            $this->deletePostsAboveLimit();
        }catch (\Exception $e){
            dd($e);
        }

        return "OK";
    }

    /**
     * Count posts for each post type and estimate the number of posts to be archived
     */
    public function countAndEstimateNumberOfPost(){
        foreach($this->postTypes as $postType) {
            $this->postTypeCount[$postType->slug] = DB::table($postType->slug)->count();
            // how many posts should be archived
            if($this->postTypeCount[$postType->slug] > Post::$postsAllowedInTable){
                $this->postNrToBeArchived[$postType->slug] = $this->postTypeCount[$postType->slug] - Post::$postsAllowedInTable;
            }else{
                $this->postNrToBeArchived[$postType->slug] = 0;
            }
        }
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function ensurePostsAreArchived(){
        foreach($this->postTypes as $postType){
            // if number of posts has exceeded allowed number of post in main database
            if($this->postTypeCount[$postType->slug] > Post::$postsAllowedInTable){

                // Last ID in post archive database
                $lastPostIDInArchive = $this->DBArchive->table($postType->slug)->orderBy('postID', 'desc')->first()->postID;

                // get all posts that don't exist in the archive database
                $postsToBeArchived = DB::table($postType->slug)->where('postID','>',$lastPostIDInArchive)->get();

                // if there are any post to be archived
                if($postsToBeArchived->count()){
                    // prepare array for insert
                    $postsToBeArchived = $this->objectToArray($postsToBeArchived, 'postID');
                    // insert post in archive
                    Event::fire('post:archiving', $postsToBeArchived['data']);
                    $archivedPosts = $this->DBArchive->table($postType->slug)->insert($postsToBeArchived['data']);

                    if($archivedPosts){
                        Event::fire('post:archived', $postsToBeArchived['data']);
                        $this->moveCategoryRelationsToArchive($postsToBeArchived['IDs'], $postType->slug);
                        $this->moveTagRelationsToArchive($postsToBeArchived['IDs'], $postType->slug);
                    }else{
                        throw new \Exception("Something wrong while ensuring posts are in archive");
                    }
                }
            }
        }
    }

    /**
     * Delete Posts Above Limit
     *
     * @return $this
     * @throws \Exception
     */
    public function deletePostsAboveLimit(){
        foreach($this->postTypes as $postType){
            // if number of posts has exceeded allowed number of post in main database
            if($this->postTypeCount[$postType->slug] > Post::$postsAllowedInTable){
                $posts = DB::table($postType->slug)->limit($this->postNrToBeArchived[$postType->slug]);
                $postsToBeDeleted = $posts->get();
                $postIDs = $this->objectToArray($postsToBeDeleted, 'postID')['IDs'];

                Event::fire('post:deleting:aboveLimit',$postsToBeDeleted);
                if($posts->delete()){
                    Event::fire('post:deleting:aboveLimit',$postsToBeDeleted);
                    $this->deleteCategoryRelations($postIDs, $postType->slug);
                    $this->deleteTagRelations($postIDs, $postType->slug);
                }else{
                    throw new \Exception("Something wrong while deleting posts above limit");
                }
            }
        }
    }


    /**
     * Move all posts to archive
     *
     * @return $this
     * @throws \Exception
     */
    public function moveAllPostsToArchive(){
        foreach($this->postTypes as $postType) {
            // ensure there are post in the main database
            if($this->postTypeCount[$postType->slug]){

                $archivedPostsCount = $this->DBArchive->table($postType->slug)->count();
                // insert all posts in archive if archive is empty
                if (!$archivedPostsCount) {
                    $allPostsObj = $this->DB->table($postType->slug)->get();
                    $allPosts = $this->objectToArray($allPostsObj, 'postID');

                    Event::fire('post:archiving', $allPosts['data']);
                    if ($this->DBArchive->table($postType->slug)->insert($allPosts['data'])) {
                        foreach($allPostsObj as $singlePost){
                            Event::fire('post:archived', $singlePost);
                        }
                    }else{
                        throw new \Exception("Something is wrong. Posts could not be inserted in the archive.");
                    }
                }
            }
        }
        return $this;
    }


    /**
     * Move all categories to archive
     *
     * @return $this
     * @throws \Exception
     */
    public function moveAllCategoriesToArchive(){
        $archivedCategories = $this->DBArchive->table("categories_relations")->count();
        if(!$archivedCategories){
            $allCategories = $this->DB->table($this->categoryRelationsTable)->get();
            if($allCategories->count()){
                // Move categories to archive
                $allCategories = $this->objectToArray($allCategories, 'categoryID');

                Event::fire('category_relations:archiving', $allCategories['data']);
                if($this->DBArchive->table($this->categoryRelationsTable)->insert($allCategories['data'])){
                    Event::fire('category_relations:archived', $allCategories['data']);
                }else{
                    throw new \Exception("Categories could not be moved to archive database");
                }
            }
        }
        return $this;
    }

    /**
     * Move all tags to archive
     *
     * @return $this
     * @throws \Exception
     */
    public function moveAllTagsToArchive(){
        $archivedTags = $this->DBArchive->table("tags_relations")->count();
        if(!$archivedTags){
            $allTags = $this->DB->table($this->tagRelationsTable)->get();
            if($allTags->count()){
                // Move tags to archive
                $allTags = $this->objectToArray($allTags, 'tagID');

                Event::fire('tags_relations:archiving', $allTags['data']);
                if($this->DBArchive->table($this->tagRelationsTable)->insert($allTags['data'])){
                    Event::fire('tags_relations:archived', $allTags['data']);
                }else{
                    throw new \Exception("Tags could not be moved to archive database");
                }
            }
        }
        return $this;
    }


    /**
     * Transfer category from main database to archive
     *
     * @param array $postIDs IDs of post to know which relations to archive
     * @param string $postTypeSlug which post type
     * @return $this
     * @throws \Exception
     */
    private function moveCategoryRelationsToArchive($postIDs, $postTypeSlug){
        // categories relations of the main database
        $categoryRelations = DB::table($this->categoryRelationsTable)->whereIn('belongsToID',$postIDs)->where('belongsTo', $postTypeSlug)->get();
        $categoryRelationsIDs = $this->objectToArray($categoryRelations, 'categoryRelationID')['IDs'];
        // get which IDs are already archived
        $categoryRelationsArchivedIDs = $this->objectToArray($this->DBArchive->table($this->categoryRelationsTable)->whereIn('categoryRelationID',$categoryRelationsIDs)->where('belongsTo', $postTypeSlug)->get(), 'categoryRelationID')['IDs'];
        // remove relations that are archived
        $categoryRelations = $this->removeDuplicated($categoryRelations, $categoryRelationsArchivedIDs, 'categoryRelationID');

        if(count($categoryRelations)){

            // archive category relations
            $categoryRelations = $this->objectToArray($categoryRelations, 'categoryRelationID');
            Event::fire('category_relations:archiving', $categoryRelations['data']);

            if($this->DBArchive->table($this->categoryRelationsTable)->insert($categoryRelations['data'])){
                $this->deleteCategoryRelations($postIDs, $postTypeSlug);
                Event::fire('category_relations:archived', $categoryRelations['data']);
            }else{
                throw new \Exception("Categories could not be moved to archive database");
            }
        }
        return $this;
    }


    /**
     * @param array $belongsToIDs
     * @param string $belongsTo
     * @return $this
     * @throws \Exception
     */
    private function deleteCategoryRelations($belongsToIDs, $belongsTo){
        Event::fire('categories_relations:deleting:aboveLimit',$belongsToIDs);
        if($this->DB->table($this->categoryRelationsTable)->whereIn('belongsToID',$belongsToIDs)->where('belongsTo', $belongsTo)->delete()){
            Event::fire('categories_relations:deleted:aboveLimit',$belongsToIDs);
        }else{
            throw new \Exception("Categories relations above limit could not be deleted from main database!");
        }
        return $this;
    }


    /**
     * Transfer tags from main database to archive
     *
     * @param array $postIDs IDs of post to know which relations to archive
     * @param string $postTypeSlug which post type
     *
     * @return $this
     * @throws \Exception
     */
    private function moveTagRelationsToArchive($postIDs, $postTypeSlug){
        // tags relations of the main database
        $tagRelations = DB::table($this->tagRelationsTable)->whereIn('belongsToID',$postIDs)->where('belongsTo', $postTypeSlug)->get();
        // tags relations IDs of the main database
        $tagRelationsIDs = $this->objectToArray($tagRelations, 'tagRelationID')['IDs'];
        // get which IDs are already archived
        $tagRelationsArchivedIDs = $this->objectToArray($this->DBArchive->table($this->tagRelationsTable)->whereIn('tagRelationID',$tagRelationsIDs)->where('belongsTo', $postTypeSlug)->get(), 'tagRelationID')['IDs'];
        // remove relations that are archived
        $tagRelations = $this->removeDuplicated($tagRelations, $tagRelationsArchivedIDs, 'tagRelationID');

        if(count($tagRelations)){
            $tagRelations = $this->objectToArray($tagRelations, 'tagRelationID');
            Event::fire('tags_relations:archiving', $tagRelations['data']);
            if($this->DBArchive->table($this->tagRelationsTable)->insert($tagRelations['data'])){
                $this->deleteCategoryRelations($postIDs, $postTypeSlug);
                Event::fire('tags_relations:archived', $tagRelations['data']);
            }else{
                throw new \Exception("Tags could not be moved to archive database");
            }
        }

        return $this;
    }

    /**
     * @param array $belongsToIDs
     * @param string $belongsTo
     *
     * @return $this
     * @throws \Exception
     */
    private function deleteTagRelations($belongsToIDs, $belongsTo){
        Event::fire('tags_relations:deleting:aboveLimit',$belongsToIDs);
        if($this->DB->table($this->tagRelationsTable)->whereIn('belongsToID',$belongsToIDs)->where('belongsTo', $belongsTo)->delete()){
            Event::fire('tags_relations:deleted:aboveLimit',$belongsToIDs);
        }else{
            throw new \Exception("Tags Relations above limit could not be deleted from main database!");
        }
        return $this;
    }


    /**
     * This function is called to execute the cached tasks
     */
    public function executeCUDTasks(){
        if(Task::has()){
            foreach(Task::get() as $task){
                switch ($task->belongsTo){
                    // Post
                    case 'post':
                        try{
                            $this->archivePost($task->type, $task->data, $task->additional);
                        }catch(\Exception $e){
                            dd($e);
                        }
                        break;

                    // Category relations
                    case $this->categoryRelationsTable:

                        // Delete category relations
                        $this->DBArchive
                            ->table($this->categoryRelationsTable)
                            ->where('belongsToID', $task->additional['postID'])
                            ->where('belongsTo', $task->additional['postType'])
                            ->delete();

                        try{
                            $this->deleteCategoriesFromArchive($task->additional['postID'], $task->additional['postType']);
                            $this->insertCategoryRelations($task->type, $task->data, $task->additional);
                        }catch (\Exception $e){
                            dd($e);
                        }
                        break;

                    // Tags relations
                    case $this->tagRelationsTable:
                        try{
                            $this->deleteTagsFromArchive($task->additional['postID'], $task->additional['postType']);
                            $this->insertTagRelations($task->type, $task->data, $task->additional);
                        }catch (\Exception $e){
                            dd($e);
                        }

                        break;
                }
            }
            // empty tasks
            Task::clear();
            dump("TASKS EXECUTED");
        }else{
            dump("NO TASKS TO EXECUTE");
        }
    }


    /**
     * Move a post to archive
     *
     * @param string $action
     * @param object $post
     * @param array $extra
     *
     * @return $this
     * @throws \Exception
     */
    private function archivePost($action, $post, $extra){
        $archiveStatus = false;

        Event::fire('post:archiving', [$post, $action]);

        // Handle Create
        if($action == 'create'){
            $postIsArchived = $this->DBArchive
                ->table($extra['postType'])
                ->where("postID", $post->postID)
                ->count();

            // Move the post to archive if it has not been previously archived
            if(!$postIsArchived){
                $archiveStatus = $this->DBArchive
                    ->table($extra['postType'])
                    ->where("postID", $post->postID)
                    ->insert($this->encodeObjects($this->objectToArray([$post], 'postID')['data'][0]));
                if(!$archiveStatus){
                    throw new \Exception("Post could not be inserted on archive database!");
                }
            }
        }

        // Handle Update
        elseif($action == 'update'){
            $archiveStatus = $this->DBArchive
                ->table($extra['postType'])
                ->where("postID", $post->postID)
                ->update($this->encodeObjects($this->objectToArray([$post], 'postID')['data'][0]));
        }

        // Handle Delete
        elseif($action == 'delete'){
            $archiveStatus = $this->DBArchive
                ->table($extra['postType'])
                ->where('postID', $post->postID)
                ->delete();

            if(!$archiveStatus){
                throw new \Exception("Post could not be deleted on archive database!");
            }else{
                try{
                    $this->deleteCategoriesFromArchive($post->postID, $extra['postType']);
                    $this->deleteTagsFromArchive($post->postID, $extra['postType']);
                }
                catch (\Exception $e){
                    dd($e);
                };
            }
        }
        Event::fire('post:archived', [$post]);

        return $this;
    }

    /**
     * @param $type
     * @param $data
     * @param $additional
     * @throws \Exception
     */
    public function insertCategoryRelations($type, $data, $additional){
        $areCategoriesInserted = $this->DBArchive->table($this->categoryRelationsTable)->insert($this->objectToArray($data)['data']);
        if(!$areCategoriesInserted){
            throw new \Exception("Categories relations could not be inserted in archive database");
        }
    }

    /**
     * @param $type
     * @param $data
     * @param $additional
     * @throws \Exception
     */
    public function insertTagRelations($type, $data, $additional){
        $areTagsInserted = $this->DBArchive->table($this->tagRelationsTable)->insert($this->objectToArray($data)['data']);
        if(!$areTagsInserted){
            throw new \Exception("Tags relations could not be inserted in archive database");
        }
    }

    /**
     * DELETE all category relations of this post
     * @param $postIDs
     * @param $postType
     *
     * @return $this
     * @throws \Exception
     */
    private function deleteCategoriesFromArchive($postID, $postType){
        $this->DBArchive
            ->table($this->categoryRelationsTable)
            ->where('belongsToID', $postID)
            ->where('belongsTo', $postType)
            ->delete();
        return $this;
    }

    /**
     * DELETE all tags relations of this post
     *
     * @param array $postIDs
     * @param string $postType
     *
     * @return $this
     * @throws \Exception
     */
    private function deleteTagsFromArchive($postID, $postType){
        $this->DBArchive
            ->table($this->tagRelationsTable)
            ->where('belongsToID', $postID)
            ->where('belongsTo', $postType)
            ->delete();
        return $this;
    }

    /**
     * Make object to array and returns a array of all it's IDs
     *
     * @param array $data
     * @param string $primaryKey
     * @return array
     */
    private function objectToArray($data, $primaryKey = ''){
        $result = [];
        $ids = [];
        foreach ($data as $key => $post){
            if($primaryKey){ $ids[] = $post->$primaryKey; }

            if (isset($post->created_at) && is_object($post->created_at)){
                $post->created_at = $post->created_at->date;
            }
            if (isset($post->updated_at) && is_object($post->updated_at)){
                $post->updated_at = $post->updated_at->date;
            }

            $result[$key] = (array) $post;
        }
        return [
            'data' => $result,
            'IDs' => $ids
        ];
    }


    /**
     * Used to loop throw an array ($mainItems) and remove children if they have the specific key ($mainKey) in the $excludedItems (array)
     *
     * @param array $mainItems list of items
     * @param array $excludedItems list of keys to be excluded
     * @param string $mainKey
     * @return array without the excluded items
     */
    private function removeDuplicated($mainItems, $excludedItems, $mainKey){
        $result = [];
        foreach($mainItems as $key => $value){
            if(is_object($value)){
                if(!in_array($value->$mainKey,$excludedItems)){ $result[$key] = $value; }
            }else if(is_array($value)){
                if(!in_array($value[$mainKey],$excludedItems)){ $result[$key] = $value; }
            }
        }

        return $result;
    }


    /**
     * Encode object or array values of a array
     *
     * @param array $data
     * @return array result of the encoding
     */
    private function encodeObjects($data){
        $tmp = [];
        foreach ($data as $key => $value){
            $tmpValue = $value;
            if(is_object($value) || is_array($value)){
                if(!isset($value->date)){
                    $tmpValue = json_encode($value);
                }else{
                    $tmpValue = new \DateTime($value->date);
                }
            }
            $tmp[$key] = $tmpValue;
        }
        return $tmp;
    }
}