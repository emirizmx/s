<?php
class Voice {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getActiveVoices() {
        $stmt = $this->db->query("SELECT * FROM dh_voices WHERE is_active = 1 ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}