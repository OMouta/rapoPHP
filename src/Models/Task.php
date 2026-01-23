<?php

namespace App\Models;

use Rapo\Model;

class Task extends Model
{
    // The table name defaults to 'tasks' based on class name
    // You can customize it:
    // protected $table = 'my_custom_tasks';

    /**
     * Define the schema or validation if needed
     */
    public $id;
    public $title;
    public $completed = false;
    public $created_at;

    /**
     * Example of a scope or custom query
     */
    public static function getPending()
    {
        return self::where('completed', 0)->get();
    }
}
