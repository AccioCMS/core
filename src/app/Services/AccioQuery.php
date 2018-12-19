<?php
namespace Accio\App\Services;

use Illuminate\Support\Facades\Schema;

class AccioQuery
{

    /**
     * Create virtual columns for selected table an column.
     * 
     * @param string $tableName
     * @param string $column
     * @param array  $virtualColumns
     * @param bool   $isJson
     */
    public static function createVirtualColumns(string $tableName, string $column, array $virtualColumns, bool $isJson = true)
    {

        if(Schema::hasTable($tableName)) {
            Schema::table(
                $tableName, function ($table) use ($tableName, $column, $virtualColumns, $isJson) {
                    foreach ($virtualColumns as $virtualColumn){
                        $length = (is_array($virtualColumn) &&
                        isset($virtualColumn['length']) &&
                        $virtualColumn['length']) ?
                            $virtualColumn['length'] : null;

                        $type = (is_array($virtualColumn) &&
                            isset($virtualColumn['type']) &&
                            $virtualColumn['type']) ?
                            $virtualColumn['type'] : "string";

                        $isIndex = (is_array($virtualColumn)  &&
                            isset($virtualColumn['index'])
                            ? $virtualColumn['index'] : false);

                        $virtualColumnName = (is_array($virtualColumn) ? $virtualColumn['name'] : $virtualColumn);

                        if(Schema::hasColumn($tableName, $column."_".$virtualColumnName)) {
                            continue;
                        }

                        if($isJson) {
                            $virtualAs = "(JSON_UNQUOTE($column->\"$.$virtualColumnName\"))";
                        }else{
                            $virtualAs = $column;
                        }

                        if($length) {
                            $table->$type($column."_".$virtualColumnName, $length)->virtualAs($virtualAs)->nullable();
                        }else{
                            $table->$type($column."_".$virtualColumnName)->virtualAs($virtualAs)->nullable();
                        }

                        // make it index
                        if ($isIndex) {
                            $table->index($column."_".$virtualColumnName);
                        }

                    }
                }
            );
        }

    }

    /**
     * Delete column from the database
     *
     * @param string $tableName
     * @param string $column
     */
    public static function deleteColumn(string $tableName, string $column)
    {
        if(Schema::hasTable($tableName)) {
            Schema::table(
                $tableName, function ($table) use ($tableName, $column) {
                    if(Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            );
        }
    }
    
}