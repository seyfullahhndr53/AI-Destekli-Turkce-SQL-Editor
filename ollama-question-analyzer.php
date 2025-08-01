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
    sendJsonResponse(['error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    sendJsonResponse(['error' => 'Geçersiz JSON: ' . json_last_error_msg()], 400);
}

if (!isset($input['question']) || empty(trim($input['question']))) {
    sendJsonResponse(['error' => 'Soru parametresi gerekli'], 400);
}

$question = trim($input['question']);

function getDatabaseSchema() {
    try {
        $db = new PDO('sqlite:database.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $tables = [];
        $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        
        while ($table = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tableName = $table['name'];
            $columnStmt = $db->query("PRAGMA table_info($tableName)");
            $columns = [];
            
            while ($column = $columnStmt->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = [
                    'name' => $column['name'],
                    'type' => $column['type'],
                    'primary_key' => $column['pk'] == 1
                ];
            }
            
            $tables[$tableName] = $columns;
        }
        
        return $tables;
        
    } catch (Exception $e) {
        return [];
    }
}

// Ollama API'ye istek gönder
function callOllamaAPI($question, $schema) {
    $ollamaUrl = 'http://localhost:11434/api/generate';
    
    $schemaText = "Veritabanı Şeması:\n";
    foreach ($schema as $tableName => $columns) {
        $schemaText .= "\nTablo: $tableName\n";
        foreach ($columns as $column) {
            $pk = $column['primary_key'] ? ' (PRIMARY KEY)' : '';
            $schemaText .= "  - {$column['name']} ({$column['type']})$pk\n";
        }
    }
    
    $prompt = "Sen bir SQL uzmanısın. Türkçe veritabanı şemasını analiz edip SQL sorgusu oluşturacaksın.

VERITABANI ŞEMASI:
$schemaText

KULLANICI SORUSU: \"$question\"

GÖREV:
1. Yukarıdaki soruyu analiz et
2. Hangi tabloları kullanman gerektiğini belirle
3. Uygun SQL sorgusunu oluştur
4. Türkçe açıklama yap

KURALLAR:
- Sadece yukarıdaki tablo ve kolonları kullan
- Temiz ve optimize SQL yaz
- Gerekiyorsa JOIN kullan
- Sonuçları LIMIT ile sınırla
- Türkçe karakterleri dikkate al

CEVAP FORMATI:
```sql
[SQL SORGUSUNU BURAYA YAZ]
```

Kullanılan tablolar: [tablo isimleri]
Açıklama: [kısa Türkçe açıklama]";

    $data = [
        'model' => 'openhermes:latest',
        'prompt' => $prompt,
        'stream' => false,
        'options' => [
            'temperature' => 0.3,
            'top_p' => 0.9,
            'max_tokens' => 500
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $ollamaUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($response === false) {
        throw new Exception('Ollama bağlantı hatası: ' . $error);
    }
    
    if ($httpCode !== 200) {
        throw new Exception('Ollama HTTP hatası: ' . $httpCode . ' - ' . $response);
    }
    
    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Ollama yanıt parse hatası: ' . json_last_error_msg());
    }
    
    return $decoded;
}

function parseOllamaResponse($ollamaResponse, $schema, $question) {
    $aiText = $ollamaResponse['response'] ?? '';
    
    $sqlPattern = '/```sql\s*(.*?)\s*```/s';
    $sqlMatches = [];
    preg_match($sqlPattern, $aiText, $sqlMatches);
    $suggestedQuery = $sqlMatches[1] ?? '';
    
    if (empty($suggestedQuery)) {
        $lines = explode("\n", $aiText);
        foreach ($lines as $line) {
            $line = trim($line);
            if (stripos($line, 'SELECT') === 0) {
                $suggestedQuery = $line;
                break;
            }
        }
    }
    
    $suggestions = [];
    foreach ($schema as $tableName => $columns) {
        if (stripos($aiText, $tableName) !== false) {
            $primaryColumns = array_filter($columns, function($col) {
                return $col['primary_key'];
            });
            
            $suggestions[] = [
                'table' => $tableName,
                'icon' => getTableIcon($tableName),
                'description' => getTableDescription($tableName),
                'columns' => array_column($columns, 'name'),
                'primary_columns' => array_column($primaryColumns, 'name')
            ];
        }
    }
    
    if (empty($suggestions)) {
        $suggestions = getDefaultSuggestions($schema);
    }
    
    if (empty($suggestedQuery)) {
        $suggestedQuery = getDefaultQuery($question);
    }
    
    return [
        'question' => $question,
        'ai_response' => $aiText,
        'suggestions' => $suggestions,
        'suggested_query' => $suggestedQuery,
        'powered_by' => 'Ollama OpenHermes (Lokal AI)'
    ];
}

function getTableIcon($tableName) {
    $icons = [
        'Musteriler' => '👥',
        'Urunler' => '🛍️',
        'Siparisler' => '📦',
        'SiparisDetayları' => '📋',
        'Calisanlar' => '👔'
    ];
    return $icons[$tableName] ?? '📊';
}

function getTableDescription($tableName) {
    $descriptions = [
        'Musteriler' => 'Müşteri bilgileri ve iletişim detayları',
        'Urunler' => 'Ürün bilgileri, fiyatlar ve stok durumu',
        'Siparisler' => 'Sipariş bilgileri ve tarihleri',
        'SiparisDetayları' => 'Sipariş içindeki ürün detayları',
        'Calisanlar' => 'Çalışan bilgileri ve pozisyonları'
    ];
    return $descriptions[$tableName] ?? 'Tablo bilgileri';
}

function getDefaultSuggestions($schema) {
    $suggestions = [];
    foreach ($schema as $tableName => $columns) {
        $primaryColumns = array_filter($columns, function($col) {
            return $col['primary_key'];
        });
        
        $suggestions[] = [
            'table' => $tableName,
            'icon' => getTableIcon($tableName),
            'description' => getTableDescription($tableName),
            'columns' => array_column($columns, 'name'),
            'primary_columns' => array_column($primaryColumns, 'name')
        ];
    }
    return $suggestions;
}

function getDefaultQuery($question) {
    $question = mb_strtolower($question, 'UTF-8');
    
    if (preg_match('/müşteri|customer/u', $question)) {
        return "SELECT * FROM Musteriler LIMIT 5;";
    } elseif (preg_match('/ürün|product/u', $question)) {
        return "SELECT * FROM Urunler LIMIT 5;";
    } elseif (preg_match('/sipariş|order/u', $question)) {
        return "SELECT * FROM Siparisler LIMIT 5;";
    } else {
        return "SELECT * FROM Musteriler LIMIT 5;";
    }
}

try {
    $schema = getDatabaseSchema();
    
    if (empty($schema)) {
        sendJsonResponse([
            'error' => 'Veritabanı şeması okunamadı',
            'fallback' => true
        ], 500);
    }
    
    $ollamaResponse = callOllamaAPI($question, $schema);
    
    $result = parseOllamaResponse($ollamaResponse, $schema, $question);
    
    sendJsonResponse($result);
    
} catch (Exception $e) {
    sendJsonResponse([
        'error' => 'Ollama AI hatası: ' . $e->getMessage(),
        'fallback' => true,
        'debug_info' => [
            'error_message' => $e->getMessage(),
            'ollama_status' => 'Ollama çalışıyor mu kontrol edin'
        ]
    ], 500);
}
?>