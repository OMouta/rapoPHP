<?php

namespace Rapo;

abstract class Migration {
    protected $db;

    public function __construct() {
        $this->db = Store::getDefault()->get('db');
    }

    abstract public function up();
    abstract public function down();

    protected function query($sql, $params = []) {
        return $this->db->query($sql, $params);
    }
}
