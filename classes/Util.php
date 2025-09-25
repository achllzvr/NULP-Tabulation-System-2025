<?php
require_once 'database.php';

/**
 * Utility Helper Class
 * Common utility functions for the application
 */
class Util {
    
    /**
     * Escape HTML output for security
     */
    public static function escape(string $string): string {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Return JSON response and exit
     */
    public static function jsonResponse(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Validate weights for a round (should sum to 100)
     */
    public static function weightsValidate(int $roundId): array {
        $db = Database::getInstance();
        
        $criteria = $db->fetchAll(
            "SELECT weight FROM criteria WHERE round_id = ?",
            [$roundId]
        );
        
        $totalWeight = array_sum(array_column($criteria, 'weight'));
        
        return [
            'valid' => $totalWeight === 100,
            'total' => $totalWeight,
            'criteria_count' => count($criteria)
        ];
    }
    
    /**
     * Generate random string for tokens
     */
    public static function generateToken(int $length = 32): string {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Format score for display
     */
    public static function formatScore(float $score, int $decimals = 1): string {
        return number_format($score, $decimals);
    }
    
    /**
     * Calculate percentage from score
     */
    public static function scoreToPercentage(float $score, float $maxScore = 10): float {
        return ($score / $maxScore) * 100;
    }
    
    /**
     * Get ordinal suffix for ranking (1st, 2nd, 3rd, etc.)
     */
    public static function getOrdinal(int $number): string {
        $suffix = 'th';
        
        if ($number % 100 < 11 || $number % 100 > 13) {
            switch ($number % 10) {
                case 1: $suffix = 'st'; break;
                case 2: $suffix = 'nd'; break;
                case 3: $suffix = 'rd'; break;
            }
        }
        
        return $number . $suffix;
    }
    
    /**
     * Validate required fields in array
     */
    public static function validateRequired(array $data, array $requiredFields): array {
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[] = "Field '{$field}' is required";
            }
        }
        
        return $errors;
    }
    
    /**
     * Sanitize filename for safe file operations
     */
    public static function sanitizeFilename(string $filename): string {
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        return trim($filename, '._-');
    }
    
    /**
     * Log activity for audit trail
     */
    public static function logActivity(int $userId, string $action, string $details = '', ?int $pageantId = null): void {
        $db = Database::getInstance();
        
        $db->execute(
            "INSERT INTO activity_logs (user_id, pageant_id, action, details, created_at) VALUES (?, ?, ?, ?, NOW())",
            [$userId, $pageantId, $action, $details]
        );
    }
    
    /**
     * Check if string is valid JSON
     */
    public static function isValidJson(string $json): bool {
        json_decode($json);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    /**
     * Convert array to CSV string
     */
    public static function arrayToCsv(array $data): string {
        $output = fopen('php://temp', 'r+');
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}