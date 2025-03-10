<?php
// Backblaze B2 ayarları
if (!defined('B2_KEY_ID')) {
    define('B2_KEY_ID', '00555c8d6938a8f0000000005');
}

if (!defined('B2_APP_KEY')) {
    define('B2_APP_KEY', 'K005Z5u4vAkE0GvApu3972w0uWEwkXc');
}

if (!defined('B2_BUCKET_ID')) {
    define('B2_BUCKET_ID', '45c58c583da669f3985a081f');
}

if (!defined('B2_BUCKET_NAME')) {
    define('B2_BUCKET_NAME', 'dijitalhediye');
}

if (!defined('B2_CDN_URL')) {
    define('B2_CDN_URL', 'https://s3.us-east-005.backblazeb2.com/' . B2_BUCKET_NAME);
}