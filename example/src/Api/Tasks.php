<?php

namespace App\Api;

use Rapo\Controller;

class Tasks extends Controller {
    public function index() {
        $db = $this->db;
        $tasks = $db->query("SELECT * FROM tasks")->fetchAll();
        return [
            'status' => 'success',
            'data' => $tasks
        ];
    }
}
