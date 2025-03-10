<?php
// Veritabanı bağlantısı ve temel ayarlar
require_once 'src/Database.php';

// Bağlantıyı al
$db = Database::getInstance()->getConnection();

// Tablo yapısını kontrol et
try {
    echo "<h2>Tablo Yapısı Kontrolü</h2>";
    $stmt = $db->query("DESCRIBE dh_credit_transactions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Test kaydı ekleme dene
    echo "<h2>Test Kaydı Ekleme</h2>";
    
    $db->beginTransaction();
    
    $stmt = $db->prepare("
        INSERT INTO dh_credit_transactions 
        (user_id, amount, type, product_type, description, created_at) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        1, // Test kullanıcı ID
        10, // Miktar
        'debit', // Tip
        'debug_test', // Ürün tipi
        'Debug test kaydı', // Açıklama
        date('Y-m-d H:i:s') // Tarih
    ]);
    
    $lastId = $db->lastInsertId();
    
    if ($result) {
        $db->commit();
        echo "Test kaydı başarıyla eklendi. ID: " . $lastId;
    } else {
        $db->rollBack();
        echo "Test kaydı eklenemedi!";
        echo "<pre>";
        print_r($stmt->errorInfo());
        echo "</pre>";
    }
    
    // Son 10 kaydı listele
    echo "<h2>Son 10 Kayıt</h2>";
    $stmt = $db->query("SELECT * FROM dh_credit_transactions ORDER BY id DESC LIMIT 10");
    $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($recentTransactions);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h2>HATA!</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    
    if ($db->inTransaction()) {
        $db->rollBack();
        echo "<p>Transaction rollback edildi.</p>";
    }
}
?> 