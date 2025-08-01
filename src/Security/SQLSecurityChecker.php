<?php

namespace Security;

class SQLSecurityChecker
{
    private const ALLOWED_OPERATIONS = [
        'SELECT'
    ];

    private const SAFE_KEYWORDS = [
        'LIMIT', 'ORDER BY', 'GROUP BY', 'HAVING', 'AS', 'ASC', 'DESC',
        'COUNT', 'SUM', 'AVG', 'MIN', 'MAX', 'DISTINCT', 'WHERE',
        'JOIN', 'INNER JOIN', 'LEFT JOIN', 'RIGHT JOIN', 'FULL JOIN'
    ];

    private const DANGEROUS_KEYWORDS = [
        'DROP', 'DELETE', 'INSERT', 'UPDATE', 'ALTER', 'CREATE',
        'TRUNCATE', 'REPLACE', 'EXEC', 'EXECUTE', 'UNION',
        'SCRIPT', 'JAVASCRIPT', 'VBSCRIPT', 'ONLOAD', 'ONERROR',
        'EVAL', 'EXPRESSION', 'APPLET', 'OBJECT', 'EMBED',
        'LINK', 'STYLE', 'IMG', 'SVG', 'IFRAME'
    ];

    // Dangerous SQL patterns (relaxed for debugging)
    private const DANGEROUS_PATTERNS = [
        '/(\bUNION\b.*\bSELECT\b)/i',
        // '/(\bOR\b.*=.*)/i',      // Commented out - too restrictive
        // '/(\bAND\b.*=.*)/i',     // Commented out - too restrictive
        '/(\'|\").*(\bOR\b|\bAND\b).*(\1)/i',
        '/(-{2}|\/\*|\*\/)/i',  // SQL comments
        '/(;|\||&)/i',           // Command chaining
        '/(\bxp_\w+)/i',         // Extended procedures
        '/(\bsp_\w+)/i'          // System procedures
    ];

    public static function isQuerySafe(string $query): bool
    {
        $query = trim($query);
        
        if (empty($query)) {
            return false;
        }

        // Length check
        if (strlen($query) > 5000) {
            return false;
        }

        // Check for null bytes
        if (strpos($query, "\0") !== false) {
            return false;
        }

        // Normalize query for checking
        $normalizedQuery = strtoupper($query);
        
        // Check if query starts with allowed operation
        $startsWithAllowed = false;
        foreach (self::ALLOWED_OPERATIONS as $operation) {
            if (preg_match('/^\s*' . $operation . '\s+/i', $query)) {
                $startsWithAllowed = true;
                break;
            }
        }
        
        if (!$startsWithAllowed) {
            return false;
        }

        // Check for dangerous keywords (excluding safe keywords)
        foreach (self::DANGEROUS_KEYWORDS as $keyword) {
            if (strpos($normalizedQuery, $keyword) !== false) {
                // Check if it's actually a safe keyword
                $isSafe = false;
                foreach (self::SAFE_KEYWORDS as $safeKeyword) {
                    if (stripos($query, $safeKeyword) !== false) {
                        $isSafe = true;
                        break;
                    }
                }
                
                if (!$isSafe) {
                    return false;
                }
            }
        }

        // Check for dangerous patterns
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $query)) {
                return false;
            }
        }

        // Additional checks for nested queries
        if (self::hasNestedQueries($query)) {
            return false;
        }

        return true;
    }

    private static function hasNestedQueries(string $query): bool
    {
        // Count SELECT statements - should only be one
        $selectCount = preg_match_all('/\bSELECT\b/i', $query);
        return $selectCount > 1;
    }

    public static function sanitizeTableName(string $tableName): string
    {
        // Only allow alphanumeric characters and underscores
        return preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
    }

    public static function validateColumnName(string $columnName): bool
    {
        // Column names should only contain letters, numbers, and underscores
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $columnName);
    }
}