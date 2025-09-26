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
            $stmt = $pdo->prepare('INSERT INTO audit_logs (created_at, user_id, action, entity_type, entity_id, meta_json) VALUES (NOW(),?,?,?,?,?)');
            $stmt->execute([
                $userId,
                $action,
                $entityType,
                $entityId,
                $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : null,
            ]);
        } catch (Exception $e) {
            // Fail silent – do not block core flow on audit failure.
        }
    }
}
