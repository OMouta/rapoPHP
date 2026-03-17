<?php

namespace Rapo;

class QueryBuilder {
    protected $db;
    protected $table;
    protected $modelClass;
    protected $select = ['*'];
    protected $wheres = [];
    protected $params = [];
    protected $joins = [];
    protected $orders = [];
    protected $limit;
    protected $offset;

    public function __construct($db, $table, $modelClass = null) {
        $this->db = $db;
        $this->table = $table;
        $this->modelClass = $modelClass;
    }

    public function select($columns = ['*']) {
        $this->select = is_array($columns) ? $columns : func_get_args();
        return $this;
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

    public function whereIn($column, array $values) {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = "$column IN ($placeholders)";
        $this->params = array_merge($this->params, $values);
        return $this;
    }

    public function whereNull($column) {
        $this->wheres[] = "$column IS NULL";
        return $this;
    }

    public function whereNotNull($column) {
        $this->wheres[] = "$column IS NOT NULL";
        return $this;
    }

    public function join($table, $first, $operator, $second, $type = 'INNER') {
        $this->joins[] = "$type JOIN $table ON $first $operator $second";
        return $this;
    }

    public function leftJoin($table, $first, $operator, $second) {
        return $this->join($table, $first, $operator, $second, 'LEFT');
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
        $sql = "SELECT " . implode(', ', $this->select) . " FROM " . $this->table;
        
        if (!empty($this->joins)) {
            $sql .= " " . implode(' ', $this->joins);
        }

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

    public function pluck($column) {
        $this->select([$column]);
        $results = $this->get();
        return array_column($results, $column);
    }

    public function update(array $values) {
        $set = implode(', ', array_map(fn($f) => "$f = ?", array_keys($values)));
        $sql = "UPDATE " . $this->table . " SET $set";
        
        $params = array_values($values);

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
            $params = array_merge($params, $this->params);
        }

        return $this->db->query($sql, $params);
    }

    public function delete() {
        $sql = "DELETE FROM " . $this->table;
        
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        return $this->db->query($sql, $this->params);
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
