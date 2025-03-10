<?php

namespace App\Http\Controllers;

use App\Helpers\LogHelper;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function register() {
        // Mevcut kayıt işlemleri...
        
        // Kullanıcı başarıyla oluşturulduktan sonra
        $userId = $this->db->lastInsertId();
        
        // Yeni kullanıcı kaydını logla
        LogHelper::logSystemActivity(
            'kullanici_kayit', 
            'kullanici', 
            'Yeni kullanıcı kaydı: ' . $username,
            [
                'user_id' => $userId,
                'username' => $username,
                'email' => $email
            ]
        );
        
        // Devam eden işlemler...
    }
} 