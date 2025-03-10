<?php

namespace App\Http\Controllers;

use App\Helpers\LogHelper;
use Illuminate\Http\Request;

class StoryController extends Controller
{
    public function generateStory() {
        // Mevcut hikaye oluşturma işlemleri...
        
        // Hikaye oluşturma işlemini logla
        LogHelper::logSystemActivity(
            'hikaye_olusturma', 
            'hikaye', 
            'Kullanıcı #' . $userId . ' tarafından yeni hikaye oluşturuldu',
            [
                'user_id' => $userId,
                'prompt' => $userPrompt,
                'credits_used' => $creditsUsed,
                'story_id' => $storyId
            ]
        );
    }
} 