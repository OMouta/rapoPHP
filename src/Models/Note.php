<?php

namespace App\Models;

use Rapo\Model;

class Note extends Model
{
    protected static $table = 'notes';
    protected static $fields = [
        'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
        'title' => 'TEXT NOT NULL',
        'content' => 'TEXT',
        'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
    ];
}
