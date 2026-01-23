<?php

namespace App\Models;

use Rapo\Model;

class Task extends Model {
    protected static $table = 'tasks';
    protected static $fields = [
        'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
        'title' => 'TEXT',
        'status' => 'TEXT',
        'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
    ];
}
