<?php
/**
 * Shortcuts to manage manual pagination
 */

namespace Accio\App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Pagination{

    /**
     * Make normal pagination
     * @param string $table Table name to get the list from
     * @param int    How many rows to show in pagination
     * @param object $object Laravel DB Query object
     *                              - if $object is , it set does not select the table because is already selected
     *                              - id $object is not, set select the table
     * @param int    $pageNumber Which page of the pagination we currently are
     * @param string $orderBy By which column should results be ordered
     * @param string $orderType How should we order data, ASC or DESC
     * @param array  $joins = List of table to join, ex.
     *                                      $join = array(
     *                                           [
     *                                              'table' => 'media',
     *                                               'type' => 'left',
     *                                               'whereTable1' => "profileImageID",
     *                                               'whereTable2' => "mediaID",
     *                                           ]
     *                                        );
     * @return array
     * */
    public function make_old($table = '', $show = 10, $object = null, $pageNumber = 1, $orderBy = '', $orderType = 'DESC', $joins = [] ){
        // if pagination is not set make it default 1
        if(isset($_GET['pagination'])){
            $pageNumber = $_GET['pagination'];
        }

        //fix php version numerical error
        if(!$pageNumber){
            $pageNumber = 1;
        }

        // calculate the pagination from which number to be shown
        $from = ($pageNumber-1)*$show;

        if(!$object){
            // ## if we dont have a object
            // it means if we didn't create a object and did something with that before we call make pagination
            $queryObject = DB::table($table);

            ## If join array is set make join between tables
            if($joins){
                foreach ($joins as $join){
                    if($join['type'] == 'left'){ // if it is a left join
                        $queryObject->leftJoin($join['table'], $table.'.'.$join['whereTable1'], '=', $join['table'].'.'.$join['whereTable2']);
                    }else if($join['type'] == 'inner'){ // if it is a inner join
                        $queryObject->join($join['table'], $table.'.'.$join['whereTable1'], '=', $join['table'].'.'.$join['whereTable2']);
                    }else{ // if type is not left nether inner
                        return "Join type should be inner or left";
                    }
                }
            }

            if($orderBy){
                $queryObject->orderBy($orderBy, $orderType);
            }
            $list = $queryObject->offset($from)->limit($show)->get();

            // count rows
            $count = DB::table($table)->get()->count();
        }else{
            // join between tables
            if($joins != ''){
                foreach ($joins as $join){
                    if($join['type'] == 'left'){ // if it is a left join
                        $object->leftJoin($join['table'], $table.'.'.$join['whereTable1'], '=', $join['table'].'.'.$join['whereTable2']);
                    }else if($join['type'] == 'inner'){ // if it is a inner join
                        $object->join($join['table'], $table.'.'.$join['whereTable1'], '=', $join['table'].'.'.$join['whereTable2']);
                    }else{ // if type is not left nether inner
                        return "Join type should be inner or left";
                    }
                }
            }

            // count rows
            $count = $object->count();

            if($orderBy){
                $list = $object->orderBy($orderBy, $orderType)->offset($from)->limit($show)->get();
            }else{
                $list = $object->offset($from)->limit($show)->get();
            }
        }

        // calculate the number of pages throw the pagination
        $totalPages = ceil($count/$show);

        return [
            'list' => $list,
            'totalPages' => $totalPages,
            "pagination" => $pageNumber
        ];
    }

    /**
     * Infinite scroll Pagination (primary used in Media Library)
     *
     * @param string $table Name of DB table
     * @param int    $pageNumber Which page of the pagination we currently are
     * @param int    $limit Number of results that should be shown in a single page
     * @param string $orderType = How should we order data, ASC or DESC
     * @param object $object Laravel DB Query object
     *                              - if $object is , it set does not select the table because is already selected
     *                              - id $object is not, set select the table
     *
     * @return array
     * */
    public function infiniteScrollPagination($table, $pageNumber = 1, $limit = 100, $orderType = 'DESC', $object = null){
        $from = ($pageNumber-1)*$limit;

        if($object == ''){
            $list = DB::table($table)->orderBy('mediaID', $orderType)->offset($from)->limit($limit)->get();
        }else{
            $list = $object->orderBy('mediaID', $orderType)->offset($from)->limit($limit)->get();
        }
        return $list;
    }

    /**
     * Initializes a LengthAwarePaginator instance
     *
     * @param array|object $items
     * @param int $resultsPerPage Define how many items we want to be visible in each page
     *
     * @return LengthAwarePaginator
     */
    public function LengthAwarePaginator($items, $resultsPerPage = 4){
        //Get current page form url e.g. &page=6
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        //Create a new Laravel collection from data
        $collection = new Collection($items);

        //Slice the collection to get the items to display in current page
        $currentPageSearchResults = $collection->slice (($currentPage - 1) * $resultsPerPage, $resultsPerPage) -> all ();

        //Create our paginator and pass it to the view
        return new LengthAwarePaginator($currentPageSearchResults, count($collection), $resultsPerPage);
    }
}