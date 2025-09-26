<?php
/**
 * AuditLogger - simple centralized audit log helper.
 * Expects an audit_logs table with (id, created_at, user_id, action, entity_type, entity_id, meta_json)
 */
require_once __DIR__ . '/database.php';

class AuditLogger {
    public static function log(?int $userId, string $action, ?string $entityType = null, $entityId = null, array $meta = []): void {
        try {
            $pdo = Database::getConnection();
            // Map to existing schema: pageant_id, user_id, action_type, entity_type, entity_id, before_json, after_json, created_at
            $pageantId = isset($_SESSION['pageant_id']) ? (int)$_SESSION['pageant_id'] : null;
            $stmt = $pdo->prepare('INSERT INTO audit_logs (pageant_id, user_id, action_type, entity_type, entity_id, before_json, after_json, created_at) VALUES (?,?,?,?,?,?,?,NOW())');
            $before = null; // not tracked in this app pathway
            $after = $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : null;
            $stmt->execute([
                $pageantId,
                $userId,
                $action,
                $entityType,
                $entityId,
                $before,
                $after,
            ]);
        } catch (Exception $e) {
            // Fail silent – do not block core flow on audit failure.
        }
    }
}
