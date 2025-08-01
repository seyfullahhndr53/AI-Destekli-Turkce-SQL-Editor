<?php

namespace Controllers;

use Config\Database;
use Database\QueryOptimizer;
use PDO;
use PDOException;

class TableController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllTables()
    {
        try {
            if (!file_exists('database.db')) {
                return [
                    'success' => false,
                    'error' => 'Database file not found!'
                ];
            }

            $optimizer = new QueryOptimizer();
            $result = $optimizer->getAllTablesOptimized();

            return [
                'success' => true,
                'data' => $result
            ];

        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}