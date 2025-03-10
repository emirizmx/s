<?php
// PayTR API Bilgileri
define('PAYTR_MERCHANT_ID', '494586');
define('PAYTR_MERCHANT_KEY', 'y943brh1qCZ8xS9N');
define('PAYTR_MERCHANT_SALT', 'CG55CJdGiRBjGEyZ');

// Test modu (Canlıya alırken false yapılacak)
define('PAYTR_TEST_MODE', true);

// Ödeme sonrası yönlendirme
define('PAYTR_SUCCESS_URL', SITE_URL . '/payment/success');
define('PAYTR_FAIL_URL', SITE_URL . '/payment/failed');

// PayTR Callback URL (IPN - Instant Payment Notification)
define('PAYTR_NOTIFY_URL', SITE_URL . '/payment/notify'); 