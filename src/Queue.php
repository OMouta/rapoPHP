<?php

namespace Rapo;

class Queue {
    protected $db;
    protected $table = 'rapo_jobs';

    public function __construct($db = null) {
        $this->db = $db ?: Store::getDefault()->get('db');
        $this->ensureTableExists();
    }

    protected function ensureTableExists() {
        $this->db->query("CREATE TABLE IF NOT EXISTS {$this->table} (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            payload TEXT,
            attempts INTEGER DEFAULT 0,
            reserved_at DATETIME,
            available_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }

    public function push($job, $data = [], $delay = 0) {
        $availableAt = date('Y-m-d H:i:s', time() + $delay);
        $payload = json_encode(['job' => $job, 'data' => $data]);
        
        $this->db->query(
            "INSERT INTO {$this->table} (name, payload, available_at) VALUES (?, ?, ?)",
            [is_string($job) ? $job : get_class($job), $payload, $availableAt]
        );
    }

    public function work() {
        $job = $this->db->fetch("SELECT * FROM {$this->table} WHERE reserved_at IS NULL AND available_at <= CURRENT_TIMESTAMP ORDER BY id ASC LIMIT 1");
        
        if (!$job) return false;

        // Reserve job
        $this->db->query("UPDATE {$this->table} SET reserved_at = CURRENT_TIMESTAMP WHERE id = ?", [$job['id']]);
        
        $payload = json_decode($job['payload'], true);
        $jobClass = $payload['job'];
        $data = $payload['data'];

        try {
            if (class_exists($jobClass)) {
                $instance = new $jobClass();
                if (method_exists($instance, 'handle')) {
                    $instance->handle($data);
                }
            } elseif (is_callable($jobClass)) {
                $jobClass($data);
            }

            // Delete successful job
            $this->db->query("DELETE FROM {$this->table} WHERE id = ?", [$job['id']]);
            return true;
        } catch (\Throwable $e) {
            // Put back for retry
            $this->db->query("UPDATE {$this->table} SET reserved_at = NULL, attempts = attempts + 1 WHERE id = ?", [$job['id']]);
            throw $e;
        }
    }
}
