<?php

namespace Rapo;

class Blueprint {
    protected $table;
    protected $columns = [];
    protected $indexes = [];
    protected $driver;

    public function __construct($table) {
        $this->table = $table;
        $db = Store::getDefault()->get('db');
        $this->driver = $db ? $db->getDriver() : 'sqlite';
    }

    public function id($name = 'id') {
        if ($this->driver === 'mysql') {
            $this->columns[] = "{$name} INT AUTO_INCREMENT PRIMARY KEY";
        } elseif ($this->driver === 'pgsql') {
            $this->columns[] = "{$name} SERIAL PRIMARY KEY";
        } else {
            $this->columns[] = "{$name} INTEGER PRIMARY KEY AUTOINCREMENT";
        }
        return $this;
    }

    public function string($name, $length = 255) {
        $this->columns[] = "{$name} VARCHAR({$length})";
        return $this;
    }

    public function text($name) {
        $this->columns[] = "{$name} TEXT";
        return $this;
    }

    public function integer($name) {
        $this->columns[] = "{$name} INTEGER";
        return $this;
    }

    public function boolean($name) {
        $this->columns[] = "{$name} BOOLEAN";
        return $this;
    }

    public function timestamp($name) {
        $this->columns[] = "{$name} TIMESTAMP";
        return $this;
    }

    public function timestamps() {
        $this->columns[] = "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        $this->columns[] = "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        return $this;
    }

    public function unique($name) {
        // Find existing column and add UNIQUE
        foreach ($this->columns as &$col) {
            if (str_starts_with($col, $name . ' ')) {
                $col .= " UNIQUE";
                break;
            }
        }
        return $this;
    }

    public function toSql() {
        $cols = implode(', ', $this->columns);
        return "CREATE TABLE {$this->table} ({$cols})";
    }
}
