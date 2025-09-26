<?php
/**
 * VisibilityService
 * Manages public reveal flags for a pageant (names, scores, awards).
 */
require_once __DIR__ . '/database.php';

class VisibilityService {
    private PDO $db;
    public function __construct(){ $this->db = Database::getConnection(); $this->ensureTable(); }

    private function ensureTable(): void {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS pageant_visibility (
                pageant_id BIGINT UNSIGNED PRIMARY KEY,
                reveal_names TINYINT(1) NOT NULL DEFAULT 0,
                reveal_scores TINYINT(1) NOT NULL DEFAULT 0,
                reveal_awards TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_vis_pageant FOREIGN KEY (pageant_id) REFERENCES pageants(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Exception $e) { /* Silent if permission restricted */ }
    }

    public function getFlags(int $pageantId): array {
        $stmt = $this->db->prepare('SELECT reveal_names, reveal_scores, reveal_awards FROM pageant_visibility WHERE pageant_id = ?');
        $stmt->execute([$pageantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return [
                'reveal_names'=>false,
                'reveal_scores'=>false,
                'reveal_awards'=>false,
            ];
        }
        return [
            'reveal_names'=>(bool)$row['reveal_names'],
            'reveal_scores'=>(bool)$row['reveal_scores'],
            'reveal_awards'=>(bool)$row['reveal_awards'],
        ];
    }

    public function setFlags(int $pageantId, array $flags): array {
        $current = $this->getFlags($pageantId);
        $merged = [
            'reveal_names' => (bool)($flags['reveal_names'] ?? $current['reveal_names']),
            'reveal_scores' => (bool)($flags['reveal_scores'] ?? $current['reveal_scores']),
            'reveal_awards' => (bool)($flags['reveal_awards'] ?? $current['reveal_awards']),
        ];
        $stmt = $this->db->prepare('INSERT INTO pageant_visibility (pageant_id, reveal_names, reveal_scores, reveal_awards) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE reveal_names=VALUES(reveal_names), reveal_scores=VALUES(reveal_scores), reveal_awards=VALUES(reveal_awards), updated_at=NOW()');
        $stmt->execute([$pageantId, (int)$merged['reveal_names'], (int)$merged['reveal_scores'], (int)$merged['reveal_awards']]);
        return $merged;
    }
}
