<?php

namespace Utils;

class ResponseHelper
{
    public static function jsonResponse($data, $httpCode = 200)
    {
        \Security\XSSProtection::addSecurityHeaders();
        
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        
        echo \Security\XSSProtection::sanitizeJSON($data);
        exit;
    }

    public static function successResponse($data)
    {
        self::jsonResponse([
            'success' => true,
            'data' => $data
        ]);
    }

    public static function errorResponse($message, $httpCode = 400)
    {
        self::jsonResponse([
            'success' => false,
            'error' => $message
        ], $httpCode);
    }
}