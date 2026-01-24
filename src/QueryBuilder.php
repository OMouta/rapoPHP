<?php

namespace Rapo;

class QueryBuilder {
    protected $db;
    protected $table;
    protected $modelClass;
    protected $wheres = [];
    protected $params = [];
    protected $orders = [];
    protected $limit;
    protected $offset;

    public function __construct($db, $table, $modelClass = null) {
        $this->db = $db;
        $this->table = $table;
        $this->modelClass = $modelClass;
    }

    public function where($column, $operator, $value = null) {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        $this->wheres[] = "$column $operator ?";
        $this->params[] = $value;
        return $this;
    }

    public function orderBy($column, $direction = 'ASC') {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = "$column $direction";
        return $this;
    }

    public function limit($limit, $offset = 0) {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    public function get() {
        $sql = "SELECT * FROM " . $this->table;
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }
        if (!empty($this->orders)) {
            $sql .= " ORDER BY " . implode(', ', $this->orders);
        }
        if ($this->limit !== null) {
            $sql .= " LIMIT " . (int)$this->limit;
            if ($this->offset) {
                $sql .= " OFFSET " . (int)$this->offset;
            }
        }

        $rows = $this->db->fetchAll($sql, $this->params);
        if ($this->modelClass) {
            return array_map(fn($row) => new $this->modelClass($row), $rows);
        }
        return $rows;
    }

    public function first() {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    public function count() {
        $sql = "SELECT COUNT(*) as count FROM " . $this->table;
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }
        $result = $this->db->fetch($sql, $this->params);
        return (int)$result['count'];
    }
}
