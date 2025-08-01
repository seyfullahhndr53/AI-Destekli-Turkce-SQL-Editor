<?php

namespace Cache;

class SimpleCache
{
    private static $cache = [];
    private static $ttl = [];
    
    public static function get(string $key)
    {
        if (!self::has($key)) {
            return null;
        }
        
        if (isset(self::$ttl[$key]) && self::$ttl[$key] < time()) {
            self::delete($key);
            return null;
        }
        
        return self::$cache[$key];
    }
    
    public static function set(string $key, $value, int $ttl = 300): void
    {
        self::$cache[$key] = $value;
        self::$ttl[$key] = time() + $ttl;
    }
    
    public static function has(string $key): bool
    {
        return isset(self::$cache[$key]);
    }
    
    public static function delete(string $key): void
    {
        unset(self::$cache[$key], self::$ttl[$key]);
    }
    
    public static function clear(): void
    {
        self::$cache = [];
        self::$ttl = [];
    }
    
    public static function cleanup(): void
    {
        $now = time();
        foreach (self::$ttl as $key => $expiry) {
            if ($expiry < $now) {
                self::delete($key);
            }
        }
    }
}