<?php
// Uploads dizinlerini oluştur
$directories = [
    __DIR__ . '/puzzle/uploads',
    __DIR__ . '/puzzle/uploads/pieces'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// .htaccess ile uploads dizinini koruma
$htaccess = __DIR__ . '/uploads/.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "
Options -Indexes
<FilesMatch \"\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|htm|shtml|sh|cgi)$\">
    Order Deny,Allow
    Deny from all
</FilesMatch>
    ");
} 