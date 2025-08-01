<?php

namespace Config;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $connection;
    private $host = 'sqlite:database.db';

    private function __construct()
    {
        try {
            $this->connection = new PDO($this->host);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new PDOException("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function __clone() {}
    public function __wakeup() {}
}