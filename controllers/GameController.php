<?php

namespace App\Http\Controllers;

use App\Helpers\LogHelper;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function startGame() {
        // Mevcut oyun başlatma işlemleri...
        
        // Oyun başlatma işlemini logla
        LogHelper::logSystemActivity(
            'oyun_baslama', 
            'oyun', 
            'Kullanıcı #' . $userId . ' tarafından ' . $gameName . ' oyunu başlatıldı',
            [
                'user_id' => $userId,
                'game_id' => $gameId,
                'credits_used' => $credits
            ]
        );
    }

    public function completeGame() {
        // Mevcut oyun tamamlama işlemleri...
        
        // Oyun tamamlama işlemini logla
        LogHelper::logSystemActivity(
            'oyun_tamamlama', 
            'oyun', 
            'Kullanıcı #' . $userId . ' tarafından ' . $gameName . ' oyunu tamamlandı',
            [
                'user_id' => $userId,
                'game_id' => $gameId,
                'result' => $result,
                'score' => $score
            ]
        );
    }
} 