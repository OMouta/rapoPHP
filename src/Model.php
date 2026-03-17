<?php

namespace Rapo;

abstract class Model {
    protected static $table;
    protected static $fields = [
        'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
        'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
    ];
    protected $data = [];
    public $magicApi = false;

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
        return static::query()->get();
    }

    public static function query() {
        return new QueryBuilder(static::getDb(), static::$table, static::class);
    }

    public static function where($column, $operator, $value = null) {
        return static::query()->where(...func_get_args());
    }

    public static function orderBy($column, $direction = 'ASC') {
        return static::query()->orderBy($column, $direction);
    }

    public static function create(array $data) {
        $instance = new static($data);
        $instance->save();
        return $instance;
    }

    public function update(array $data) {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
        return $this->save();
    }

    public function save() {
        $db = static::getDb();
        $fields = $this->data;
        unset($fields['id']);

        if (isset($this->data['id'])) {
            // Update
            $sqlFields = array_keys($fields);
            $set = implode(', ', array_map(fn($f) => "$f = ?", $sqlFields));
            $sql = "UPDATE " . static::$table . " SET $set WHERE id = ?";
            $db->query($sql, [...array_values($fields), $this->data['id']]);
        } else {
            // Insert
            $sqlFields = array_keys($this->data);
            $placeholders = implode(', ', array_fill(0, count($sqlFields), '?'));
            $sql = "INSERT INTO " . static::$table . " (" . implode(', ', $sqlFields) . ") VALUES ($placeholders)";
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
        // Accessor: getAttributeNameAttribute
        $method = 'get' . str_replace('_', '', ucwords($name, '_')) . 'Attribute';
        if (method_exists($this, $method)) {
            return $this->$method($this->data[$name] ?? null);
        }
        return $this->data[$name] ?? null;
    }

    public function __set($name, $value) {
        // Mutator: setAttributeNameAttribute
        $method = 'set' . str_replace('_', '', ucwords($name, '_')) . 'Attribute';
        if (method_exists($this, $method)) {
            $value = $this->$method($value);
        }
        $this->data[$name] = $value;
    }

    public function toArray() {
        return $this->data;
    }

    public function hasMany($relatedClass, $foreignKey = null, $localKey = 'id') {
        if ($foreignKey === null) {
            $className = (new \ReflectionClass($this))->getShortName();
            $foreignKey = strtolower($className) . '_id';
        }
        return $relatedClass::where($foreignKey, $this->$localKey);
    }

    public function belongsTo($relatedClass, $foreignKey = null, $ownerKey = 'id') {
        if ($foreignKey === null) {
            $className = (new \ReflectionClass($relatedClass))->getShortName();
            $foreignKey = strtolower($className) . '_id';
        }
        return $relatedClass::find($this->$foreignKey);
    }
}
