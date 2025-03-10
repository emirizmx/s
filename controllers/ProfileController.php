<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\LogHelper;

class ProfileController extends Controller
{
    public function updateProfile() {
        // Mevcut profil güncelleme işlemleri...
        
        // Profil güncellemesini logla
        LogHelper::logSystemActivity(
            'profil_guncelleme', 
            'kullanici', 
            'Kullanıcı #' . $userId . ' profilini güncelledi',
            [
                'user_id' => $userId,
                'updated_fields' => $updatedFields
            ]
        );
    }

    public function changePassword() {
        // Mevcut şifre değiştirme işlemleri...
        
        // Şifre değişikliğini logla (hassas bilgiler olmadan)
        LogHelper::logSystemActivity(
            'sifre_degistirme', 
            'kullanici', 
            'Kullanıcı #' . $userId . ' şifresini değiştirdi',
            [
                'user_id' => $userId
            ]
        );
    }
} 