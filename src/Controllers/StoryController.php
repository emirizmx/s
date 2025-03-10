<?php
require_once __DIR__ . '/BaseController.php';

class StoryController extends BaseController {
    
    public function __construct() {
        parent::__construct();
    }
    
    public function redirectToStory() {
        error_log('=== StoryController Debug ===');
        error_log('Session ID: ' . session_id());
        error_log('User ID: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));
        
        // Oturum kontrolü
        if (!isset($_SESSION['user_id'])) {
            error_log('StoryController: No user_id, redirecting to login');
            header('Location: /login');
            exit;
        }
        
        error_log('StoryController: Redirecting to story module...');
        // Story dizinine yönlendir
        header('Location: /story/');
        exit;
    }
}