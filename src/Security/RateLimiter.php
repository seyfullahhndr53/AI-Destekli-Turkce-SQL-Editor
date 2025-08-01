<?php

namespace Security;

class RateLimiter
{
    private const DEFAULT_LIMIT = 60;
    private const DEFAULT_WINDOW = 60;

    public static function checkLimit(string $identifier, int $limit = self::DEFAULT_LIMIT, int $window = self::DEFAULT_WINDOW): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $now = time();
        $key = "rate_limit_{$identifier}";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'count' => 1,
                'window_start' => $now
            ];
            return true;
        }

        $data = $_SESSION[$key];
        
        if ($now - $data['window_start'] >= $window) {
            $_SESSION[$key] = [
                'count' => 1,
                'window_start' => $now
            ];
            return true;
        }

        if ($data['count'] >= $limit) {
            return false;
        }

        $_SESSION[$key]['count']++;
        return true;
    }

    public static function getRemainingRequests(string $identifier, int $limit = self::DEFAULT_LIMIT): int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $key = "rate_limit_{$identifier}";
        
        if (!isset($_SESSION[$key])) {
            return $limit;
        }

        return max(0, $limit - $_SESSION[$key]['count']);
    }

    public static function getResetTime(string $identifier, int $window = self::DEFAULT_WINDOW): int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $key = "rate_limit_{$identifier}";
        
        if (!isset($_SESSION[$key])) {
            return time();
        }

        return $_SESSION[$key]['window_start'] + $window;
    }
}