<?php

namespace App\Http\Controllers;

use App\Helpers\LogHelper;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login() {
        // Mevcut giriş işlemleri...
        
        if ($user && password_verify($password, $user['password'])) {
            // Oturum başlatma işlemleri...
            
            // Başarılı girişi logla
            LogHelper::logLogin($user['id'], $user['username'], $user['email'], true);
            
            // Yönlendirme...
        } else {
            // Başarısız girişi logla
            LogHelper::logLogin(null, $username, null, false, 'Geçersiz kullanıcı adı veya şifre');
            
            // Hata mesajı...
        }
    }

    public function logout() {
        // Kullanıcı çıkışını logla
        if (isset($_SESSION['user_id'])) {
            LogHelper::logSystemActivity(
                'oturum_kapama', 
                'kullanici', 
                'Kullanıcı oturumu kapatıldı: #' . $_SESSION['user_id'],
                null
            );
        }
        
        // Mevcut çıkış işlemleri...
    }
} 