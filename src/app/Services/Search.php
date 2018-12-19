<?php
/**
 * Created by PhpStorm.
 * User: sopa
 * Date: 8/13/17
 * Time: 11:06 AM
 */

namespace Accio\App\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\HtmlString;
use Accio\Support\Facades\Pagination;

class Search
{

    /**
     * @var string which database connection to use
     */
    private $DATABASE_CONNECTION = 'mysql';

    /**
     * Get a search form
     *
     * @param string $customView Name of a custom blade.php file to render the template
     * @param string $formClass  Serch form class
     *
     * @return HtmlString|string
     */
    public function printSearchForm($customView ='', $formClass="")
    {
        return new HtmlString(
            view()->make(
                ($customView ? $customView : "vendor.search.default"), [
                'keyword' => $this->getKeyword(),
                'formClass' => $formClass
                ]
            )->render()
        );
    }

    /**
     * Get search keyword
     *
     * @return string
     */
    public function getKeyword()
    {
        Request::method();
        if (\Request::route('keyword')) {
            return \Request::route('keyword');
        }else{
            return Input::get('keyword');
        }
    }

    /**
     * Search data by a term
     *
     * @param string  $table              Name of DB Table
     * @param string  $searchTerm         Search Query
     * @param int     $limit              Number of results that should be shown in a single page
     * @param boolean $searchInAllColumns Search in all columns of the table, if false,
     * @param array   $columns            List of columns to search in
     * @param array   $excludeColumns     List of columns to exclude from search
     * @param string  $orderType          How should we order data, ASC or DESC
     * @param string  $orderBy            By which column should results be ordered
     * @param array   $joins              = List of table to join, ex.
     *                                    $join = array( [ 'table' =>
     *                                    'media', 'type' => 'left',
     *                                    'whereTable1' =>
     *                                    "profileImageID",
     *                                    'whereTable2' => "mediaID",
     *                                    ] );
     * @param array   $conditions         Conditions to search in table. It stimulates laravel's where() function, ex. [["where","id","=",2],["where","status","=","published"]]
     *
     * @return Pagination::make
     *
     * @throws Exception If no columns could be found
     * */
    public function searchByTerm($table, $searchTerm, $limit, $searchInAllColumns = true, $columns = array(), $excludeColumns = array(), $orderBy = 'created_at', $orderType = 'DESC', $joins = array(), $conditions = array())
    {
        $langSlug = App::getLocale();

        if($searchInAllColumns && !$columns) {
            $columns = DB::connection($this->DATABASE_CONNECTION)->select(DB::connection($this->DATABASE_CONNECTION)->raw("SHOW COLUMNS FROM $table FROM ".DB::getDatabaseName()));
            if(!$columns) {
                throw new \Exception("No columns could be found for table:".$table);
            }
        }
        // get table
        $queryObject = DB::connection($this->DATABASE_CONNECTION)->table($table);

        // use where clause for each column
        foreach ($columns as $column){
            if ($column->Field != 'password') {
                if(!in_array($column->Field, $excludeColumns)) {
                    if ($searchInAllColumns) {
                        if ($column->Type == "json") {
                            $queryObject->orWhere($column->Field.'->'.$langSlug, 'like', '%'.$searchTerm.'%');
                        }else{
                            $queryObject->orWhere($column->Field, 'like', '%'.$searchTerm.'%');
                        }
                    }else{
                        $queryObject->orWhere($column->Field, 'like', '%'.$searchTerm.'%');
                    }
                }

                if($conditions) {
                    foreach ($conditions as $condition){
                        $method = $condition[0];
                        $queryObject->$method($condition[1], $condition[2], $condition[3]);
                    }
                }
            }
        }

        if($joins != '') {
            foreach ($joins as $join){
                if($join['type'] == 'left') { // if it is a left join
                    $queryObject->leftJoin($join['table'], $table.'.'.$join['whereTable1'], '=', $join['table'].'.'.$join['whereTable2']);
                }else if($join['type'] == 'inner') { // if it is a inner join
                    $queryObject->join($join['table'], $table.'.'.$join['whereTable1'], '=', $join['table'].'.'.$join['whereTable2']);
                }
            }
        }

        return $queryObject->orderBy($orderBy, $orderType)
            ->paginate($limit);
    }


    /**
     * Search media filse
     *
     * @param string $searchTerm Search Query
     * @param int    $page       Which page of the pagination we currently
     * @param string $fromDate   From date ex. 2016-31-12
     * @param string $toDate     To date ex. 2017-31-12
     * @param string $mediaType  Media type to search for, ex. 'image'. 'all' for all media types
     * @param string $orderBy    Column name
     * @param string $orderType  DESC or ASC
     * @param int    $page
     *
     * @return mixed
     *
     * @throws Exception If no columns could be found
     * */
    public function media($searchTerm,  $fromDate = '', $toDate = '', $mediaType = '', $orderBy =  "created_at", $orderType = 'DESC', $page = 1)
    {

        $queryObject = DB::table("media");

        if($searchTerm != "") {
            $queryObject->where(
                function ($query) use ($searchTerm) {
                    $query->orWhere('filename', 'like', '%'.$searchTerm.'%');
                    $query->orWhere('title', 'like', '%'.$searchTerm.'%');
                    $query->orWhere('description', 'like', '%'.$searchTerm.'%');
                }
            );
        }

        //media type
        if ($mediaType && $mediaType != 'all') {
            $queryObject->where('type', $mediaType);
        }

        //dates
        if($fromDate && $fromDate !='null') {
            $fromDate = date('Y-m-d', strtotime($fromDate)); //need a space after dates.
        }else{
            $fromDate = false;
        }

        if($toDate && $toDate !='null') {
            $toDate = date('Y-m-d', strtotime($toDate));
        }else{
            $toDate = false;
        }

        if($fromDate && $toDate) {
            $queryObject->whereBetween('created_at', array($fromDate, $toDate));
        }else{
            if($fromDate) {
                $queryObject->whereDate('created_at', '>=', $fromDate);
            }
            if($toDate) {
                $queryObject->whereDate('created_at', '<=', $toDate);
            }
        }

        return $queryObject->orderBy($orderBy, $orderType)->get();
    }

    /**
     * Advanced search in a table
     *
     * @param string  $table
     * @param Request $request
     * @param int     $limit
     * @param int     $page
     * @param array   $joins
     *
     * @return mixed|object|array Returns search results list
     */
    public function advanced($table, $request, $limit = 10, $page = 0, $joins = array())
    {
        $list = DB::table($table);

        //make join between tables
        if($joins) {
            foreach ($joins as $join){
                if($join['type'] == 'left') { // if it is a left join
                    $list->leftJoin($join['table'], $table.'.'.$join['whereTable1'], '=', $join['table'].'.'.$join['whereTable2']);
                }else if($join['type'] == 'inner') { // if it is a inner join
                    $list->join($join['table'], $table.'.'.$join['whereTable1'], '=', $join['table'].'.'.$join['whereTable2']);
                }
            }
        }

        $orderBy = '';
        $orderType = '';
        if(is_array($request)) {
            $searchInAllColumns = $request;

        }else{
            $searchInAllColumns = $request->all()['fields'];
            $page = $request->all()['page'];
            $orderBy = (isset($request->all()['orderBy']) ? $request->all()['orderBy'] : '');
            $orderType = (isset($request->all()['orderType']) ? $request->all()['orderType'] : '');
        }


        foreach ($searchInAllColumns as $field){
            if(isset($field['orderBy'])) {
                if($orderBy == '') {
                    $orderBy = $field['orderBy'];
                }
                continue;
            }
            if(isset($field['orderType'])) {
                if($orderType == '') {
                    $orderType = $field['orderType'];
                }
                continue;
            }

            if($field['operator'] == "greater-than") {
                $list->where($field['type']['db-column'], '>', $field['value']);
            }else if ($field['operator'] == "less-than") {
                $list->where($field['type']['db-column'], '<', $field['value']);
            }else if ($field['operator'] == "equal") {
                if($field['boolean'] != '' && $field['boolean'] != null && $field['boolean'] != "null") {
                    if($field['boolean'] == 1) {
                        $list->where($field['type']['db-column'], $field['boolean']);
                    }else if($field['boolean'] == 0) {
                        $list->where($field['type']['db-column'], $field['boolean']);
                    }
                }else{
                    $list->where($field['type']['db-column'], $field['value']);
                }
            }else if ($field['operator'] == "not-equal") {
                if($field['boolean'] == 1) {
                    $list->where($field['type']['db-column'], '!=', $field['boolean']);
                }else if($field['boolean'] == 0) {
                    $list->where($field['type']['db-column'], '!=', $field['boolean']);
                }else{
                    $list->where($field['type']['db-column'], '!=', $field['value']);
                }
            }else if ($field['operator'] == "contains") {
                // like
                $list->where($field['type']['db-column'], 'like', '%'.$field['value'].'%');
            }else if ($field['operator'] == "starts-with") {
                // starts with
                $list->where($field['type']['db-column'], 'like', $field['value'].'%');
            }else if ($field['operator'] == "ends-with") {
                // ends with
                $list->where($field['type']['db-column'], 'like', '%'.$field['value']);
            }
        }

        return $list->paginate($limit, ['*'], 'page', $page);
    }
}