<?php
require_once 'Database.php';

/**
 * Public Service
 * Handles public-facing functionality like pageant validation
 */
class PublicService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Validate if a pageant code exists and is active
     */
    public function validatePageantCode(string $code): bool {
        try {
            $result = $this->db->fetch(
                "SELECT id FROM pageants WHERE code = ? AND is_active = 1",
                [$code]
            );
            return $result !== false;
        } catch (Exception $e) {
            error_log("Error validating pageant code: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get pageant info by code for public display
     */
    public function getPageantInfo(string $code): ?array {
        try {
            $result = $this->db->fetch(
                "SELECT code, title, show_participant_names, prelim_results_revealed, final_results_revealed FROM pageants WHERE code = ? AND is_active = 1",
                [$code]
            );
            return $result ?: null;
        } catch (Exception $e) {
            error_log("Error getting pageant info: " . $e->getMessage());
            return null;
        }
    }
}
?>