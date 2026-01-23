<?php

namespace Rapo;

abstract class Model {
    protected static $table;
    protected static $fields = [
        'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
        'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
    ];
    protected $data = [];

    public static function getTable() {
        return static::$table;
    }

    public static function getFields() {
        return static::$fields;
    }

    public function __construct(array $data = []) {
        $this->data = $data;
    }

    public static function getDb() {
        return Store::getDefault()->get('db');
    }

    public static function find($id) {
        $data = static::getDb()->fetch("SELECT * FROM " . static::$table . " WHERE id = ?", [$id]);
        return $data ? new static($data) : null;
    }

    public static function all() {
        $rows = static::getDb()->fetchAll("SELECT * FROM " . static::$table);
        return array_map(fn($row) => new static($row), $rows);
    }

    public function save() {
        $db = static::getDb();
        if (isset($this->data['id'])) {
            // Update
            $fields = array_keys($this->data);
            $set = implode(', ', array_map(fn($f) => "$f = ?", $fields));
            $sql = "UPDATE " . static::$table . " SET $set WHERE id = ?";
            $db->query($sql, [...array_values($this->data), $this->data['id']]);
        } else {
            // Insert
            $fields = array_keys($this->data);
            $placeholders = implode(', ', array_fill(0, count($fields), '?'));
            $sql = "INSERT INTO " . static::$table . " (" . implode(', ', $fields) . ") VALUES ($placeholders)";
            $db->query($sql, array_values($this->data));
            $this->data['id'] = $db->lastInsertId();
        }
        return $this;
    }

    public function delete() {
        if (isset($this->data['id'])) {
            static::getDb()->query("DELETE FROM " . static::$table . " WHERE id = ?", [$this->data['id']]);
        }
    }

    public function __get($name) {
        return $this->data[$name] ?? null;
    }

    public function __set($name, $value) {
        $this->data[$name] = $value;
    }

    public function toArray() {
        return $this->data;
    }
}
