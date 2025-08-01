<?php

namespace Database;

use Config\Database;
use Cache\SimpleCache;

class QueryOptimizer
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function executeOptimizedQuery(string $query): array
    {
        $cacheKey = 'query_' . md5($query);
        
        $cached = SimpleCache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        $optimizedQuery = $this->optimizeQuery($query);
        $statement = $this->db->prepare($optimizedQuery);
        $statement->execute();
        
        $result = $statement->fetchAll();
        
        SimpleCache::set($cacheKey, $result, 300);
        
        return $result;
    }
    
    public function getAllTablesOptimized(): array
    {
        $cacheKey = 'tables_list';
        
        $cached = SimpleCache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        $query = "
            SELECT 
                m.name as table_name,
                COALESCE(s.count, 0) as row_count
            FROM sqlite_master m
            LEFT JOIN (
                SELECT 
                    name,
                    (SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name=m2.name) as count
                FROM sqlite_master m2 
                WHERE type='table' AND name != 'sqlite_sequence'
            ) s ON m.name = s.name
            WHERE m.type='table' AND m.name != 'sqlite_sequence'
            ORDER BY m.name
        ";
        
        $tables = $this->db->query($query)->fetchAll();
        $result = [];
        
        foreach ($tables as $table) {
            $tableName = $table['table_name'];
            $countQuery = "SELECT COUNT(*) as count FROM `{$tableName}`";
            $count = $this->db->query($countQuery)->fetch();
            $result[$tableName] = (int)$count['count'];
        }
        
        SimpleCache::set($cacheKey, $result, 120);
        
        return $result;
    }
    
    private function optimizeQuery(string $query): string
    {
        $query = trim($query);
        
        if (preg_match('/^SELECT\s+/i', $query) && !preg_match('/\bLIMIT\s+\d+/i', $query)) {
            $query .= ' LIMIT 1000';
        }
        
        return $query;
    }
    
    public function warmupCache(): void
    {
        $this->getAllTablesOptimized();
        
        $commonQueries = [
            "SELECT name FROM sqlite_master WHERE type='table'",
            "SELECT COUNT(*) FROM sqlite_master WHERE type='table'"
        ];
        
        foreach ($commonQueries as $query) {
            try {
                $this->executeOptimizedQuery($query);
            } catch (\Exception $e) {
            }
        }
    }
    
    public function getQueryExecutionPlan(string $query): array
    {
        try {
            $explainQuery = "EXPLAIN QUERY PLAN " . $query;
            $statement = $this->db->prepare($explainQuery);
            $statement->execute();
            return $statement->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }
}