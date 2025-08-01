<?php

namespace Controllers;

use Config\Database;
use Database\QueryOptimizer;
use PDO;
use PDOException;

class QueryController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function executeQuery($queryString)
    {
        try {
            if (empty(trim($queryString))) {
                throw new PDOException("Query string cannot be empty");
            }


            $optimizer = new QueryOptimizer();
            $result = $optimizer->executeOptimizedQuery($queryString);
            
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

    private function isQuerySafe($query)
    {
        return \Security\SQLSecurityChecker::isQuerySafe($query);
    }
}