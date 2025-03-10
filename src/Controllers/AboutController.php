<?php
require_once 'src/Controllers/BaseController.php';

class AboutController extends BaseController {
    public function index() {
        require_once 'src/Views/about.php';
    }
} 