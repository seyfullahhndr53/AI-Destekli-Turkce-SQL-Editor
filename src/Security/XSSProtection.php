<?php

namespace Security;

class XSSProtection
{
    public static function sanitizeOutput($data)
    {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeOutput'], $data);
        }
        
        if (is_object($data)) {
            $sanitized = new \stdClass();
            foreach ($data as $key => $value) {
                $sanitized->{self::sanitizeString($key)} = self::sanitizeOutput($value);
            }
            return $sanitized;
        }
        
        if (is_string($data)) {
            return self::sanitizeString($data);
        }
        
        return $data;
    }

    public static function sanitizeString(string $input): string
    {
        $input = str_replace("\0", '', $input);
        
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        $dangerous_patterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i',
            '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/on\w+\s*=/i'
        ];
        
        foreach ($dangerous_patterns as $pattern) {
            $result = preg_replace($pattern, '', $input);
            if ($result !== null) {
                $input = $result;
            }
        }
        
        return $input;
    }

    public static function sanitizeJSON($data): string
    {
        $sanitized = self::sanitizeOutput($data);
        return json_encode($sanitized, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public static function addSecurityHeaders(): void
    {
        header('X-XSS-Protection: 1; mode=block');
        
        header('X-Content-Type-Options: nosniff');
        
        header('X-Frame-Options: DENY');
        
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self';");
        
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}