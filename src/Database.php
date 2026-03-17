<?php

namespace Rapo;

use PDO;

class Database {
    protected $pdo;
    protected $driver;
    protected $loggingEnabled = false;

    public function getDriver() {
        return $this->driver;
    }

    public function __construct(array $config = []) {
        $this->loggingEnabled = $config['log_queries'] ?? false;
        $this->driver = $config['driver'] ?? 'sqlite';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        if ($this->driver === 'sqlite') {
            $path = $config['path'] ?? ':memory:';
            $this->pdo = new PDO("sqlite:$path", null, null, $options);
        } elseif ($$this->driver === 'pgsql') {
            $host = $config['host'] ?? 'localhost';
            $port = $config['port'] ?? '5432';
            $db   = $config['db'] ?? 'test';
            $user = $config['user'] ?? 'postgres';
            $pass = $config['pass'] ?? '';

            $dsn = "pgsql:host=$host;port=$port;dbname=$db";
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } else {
            $host = $config['host'] ?? 'localhost';
            $port = $config['port'] ?? '3306';
            $db   = $config['db'] ?? 'test';
            $user = $config['user'] ?? 'root';
            $pass = $config['pass'] ?? '';
            $charset = $config['charset'] ?? 'utf8mb4';

            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        }
    }

    public function query($sql, $params = []) {
        if ($this->loggingEnabled) {
            \Rapo\Log::debug("SQL Query: $sql", ['params' => $params]);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
}
