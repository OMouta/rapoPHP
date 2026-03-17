<?php

namespace Rapo;

class Schema {
    public static function create($table, \Closure $callback) {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        $sql = $blueprint->toSql();
        
        $db = Store::getDefault()->get('db');
        return $db->query($sql);
    }

    public static function dropIfExists($table) {
        $db = Store::getDefault()->get('db');
        return $db->query("DROP TABLE IF EXISTS {$table}");
    }
}
