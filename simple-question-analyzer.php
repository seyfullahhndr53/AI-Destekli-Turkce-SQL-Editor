<?php
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$question = $input['question'] ?? '';

function analyzeQuestionSimple($question) {
    $question = mb_strtolower($question, 'UTF-8');
    
    $suggestions = [];
    $suggestedQuery = '';
    
    if (preg_match('/müşteri|customer|kişi|insan/u', $question)) {
        $suggestions[] = [
            'table' => 'Musteriler',
            'icon' => '👥',
            'description' => 'Müşteri bilgileri ve iletişim detayları',
            'columns' => ['MusteriID', 'Ad', 'Soyad', 'Email', 'Telefon', 'Adres'],
            'primary_columns' => ['MusteriID']
        ];
        
        if (preg_match('/sayı|kaç|count|toplam/u', $question)) {
            $suggestedQuery = 'SELECT COUNT(*) as ToplamMusteri FROM Musteriler;';
        } else {
            $suggestedQuery = 'SELECT * FROM Musteriler LIMIT 10;';
        }
    }
    
    if (preg_match('/ürün|product|satış|fiyat/u', $question)) {
        $suggestions[] = [
            'table' => 'Urunler',
            'icon' => '🛍️',
            'description' => 'Ürün bilgileri, fiyatlar ve stok durumu',
            'columns' => ['UrunID', 'UrunAdi', 'Fiyat', 'StokMiktari', 'Kategori'],
            'primary_columns' => ['UrunID']
        ];
        
        if (preg_match('/pahalı|expensive|en yüksek|max/u', $question)) {
            $suggestedQuery = 'SELECT * FROM Urunler ORDER BY Fiyat DESC LIMIT 10;';
        } elseif (preg_match('/ucuz|cheap|en düşük|min/u', $question)) {
            $suggestedQuery = 'SELECT * FROM Urunler ORDER BY Fiyat ASC LIMIT 10;';
        } else {
            $suggestedQuery = 'SELECT * FROM Urunler LIMIT 10;';
        }
    }
    
    if (preg_match('/sipariş|order|satış|alış/u', $question)) {
        $suggestions[] = [
            'table' => 'Siparisler',
            'icon' => '📦',
            'description' => 'Sipariş bilgileri ve tarihleri',
            'columns' => ['SiparisID', 'MusteriID', 'SiparisTarihi', 'ToplamTutar'],
            'primary_columns' => ['SiparisID']
        ];
        
        if (preg_match('/en çok|most|top|best/u', $question)) {
            $suggestedQuery = 'SELECT TOP 10 s.*, m.Ad, m.Soyad FROM Siparisler s JOIN Musteriler m ON s.MusteriID = m.MusteriID ORDER BY s.ToplamTutar DESC;';
        } else {
            $suggestedQuery = 'SELECT * FROM Siparisler ORDER BY SiparisTarihi DESC LIMIT 10;';
        }
    }
    
    if (empty($suggestions)) {
        $suggestions = [
            [
                'table' => 'Musteriler',
                'icon' => '👥',
                'description' => 'Müşteri bilgileri ve iletişim detayları',
                'columns' => ['MusteriID', 'Ad', 'Soyad', 'Email', 'Telefon', 'Adres'],
                'primary_columns' => ['MusteriID']
            ],
            [
                'table' => 'Urunler',
                'icon' => '🛍️',
                'description' => 'Ürün bilgileri, fiyatlar ve stok durumu',
                'columns' => ['UrunID', 'UrunAdi', 'Fiyat', 'StokMiktari', 'Kategori'],
                'primary_columns' => ['UrunID']
            ],
            [
                'table' => 'Siparisler',
                'icon' => '📦',
                'description' => 'Sipariş bilgileri ve tarihleri',
                'columns' => ['SiparisID', 'MusteriID', 'SiparisTarihi', 'ToplamTutar'],
                'primary_columns' => ['SiparisID']
            ]
        ];
        $suggestedQuery = 'SELECT * FROM Musteriler LIMIT 5;';
    }
    
    return [
        'question' => $question,
        'suggestions' => $suggestions,
        'suggested_query' => $suggestedQuery,
        'powered_by' => '🧠 Akıllı Analiz Sistemi',
        'success' => true
    ];
}

echo json_encode(analyzeQuestionSimple($question), JSON_UNESCAPED_UNICODE);
?>