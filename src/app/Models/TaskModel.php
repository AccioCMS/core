<?php

namespace Accio\App\Models;

use App\Models\Task;
use Illuminate\Database\Eloquent\Model;

class TaskModel extends Model
{
    /**
     * @var string table name
     */
    protected $table = 'tasks';

    protected $casts = [
        'data' => 'object',
        'additional' => 'array',
    ];

    /**
     * Create new task
     *
     * @param string       $belongsTo
     * @param string       $type
     * @param object|array $data
     * @param array        $attributes
     */
    public static function create($belongsTo, $type, $data, $attributes = [])
    {
        $obj = new Task();
        $obj->belongsTo = $belongsTo;
        $obj->type = $type;
        $obj->data = $data;
        $obj->additional = $attributes;
        $obj->save();
    }

    /**
     * Get all tasks
     *
     * @return array
     */
    public static function get()
    {
        return self::all();
    }

    /**
     * @return mixed
     */
    public static function has()
    {
        if(self::count()) {
            return true;
        }
        return false;
    }

    /**
     * Clear tasks from database
     */
    public static function clear()
    {
        self::truncate();
    }

}