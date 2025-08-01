<?php

namespace Utils;

class InputValidator
{
    public static function sanitizeString($input)
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    public static function validateSQLQuery($query)
    {
        if (empty(trim($query))) {
            return false;
        }

        if (strlen($query) > 5000) {
            return false;
        }

        return true;
    }

    public static function sanitizeForJSON($data)
    {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeForJSON'], $data);
        }
        
        if (is_string($data)) {
            return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        
        return $data;
    }

    public static function validateCSRFToken($token, $sessionToken)
    {
        return hash_equals($sessionToken, $token);
    }

    public static function generateCSRFToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
}