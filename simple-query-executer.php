<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['error' => 'Only POST requests allowed'], 405);
}

$queryString = $_POST['queryString'] ?? '';

if (empty(trim($queryString))) {
    sendJsonResponse(['error' => 'Query string is required'], 400);
}

$dangerousKeywords = [
    'DROP', 'DELETE', 'INSERT', 'UPDATE', 'CREATE', 'ALTER', 
    'TRUNCATE', 'EXEC', 'EXECUTE', 'xp_', 'sp_'
];

$upperQuery = strtoupper($queryString);
foreach ($dangerousKeywords as $keyword) {
    if (strpos($upperQuery, $keyword) !== false) {
        sendJsonResponse(['error' => 'Tehlikeli SQL komutu tespit edildi: ' . $keyword], 400);
    }
}

try {
    $db = new PDO('sqlite:database.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $db->prepare($queryString);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $columnCount = $stmt->columnCount();
    $columns = [];
    
    for ($i = 0; $i < $columnCount; $i++) {
        $meta = $stmt->getColumnMeta($i);
        $columns[] = [
            'name' => $meta['name'],
            'type' => $meta['native_type'] ?? 'unknown'
        ];
    }
    
    sendJsonResponse([
        'success' => true,
        'data' => [
            'results' => $results,
            'columns' => $columns,
            'row_count' => count($results),
            'query' => $queryString
        ],
        'message' => count($results) . ' satır getirildi'
    ]);
    
} catch (PDOException $e) {
    sendJsonResponse([
        'error' => 'SQL Hatası: ' . $e->getMessage(),
        'query' => $queryString
    ], 400);
    
} catch (Exception $e) {
    sendJsonResponse([
        'error' => 'Genel Hata: ' . $e->getMessage()
    ], 500);
}
?>