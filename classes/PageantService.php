<?php
/**
 * PageantService
 * Handles retrieval and basic operations on pageants, participants, rounds.
 */
require_once __DIR__ . '/database.php';

class PageantService {
    public static function getPageantByCode(string $code): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM pageants WHERE code = ? LIMIT 1');
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getRounds(int $pageant_id): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM rounds WHERE pageant_id = ? ORDER BY display_order');
        $stmt->execute([$pageant_id]);
        return $stmt->fetchAll();
    }

    public static function getParticipants(int $pageant_id, ?string $division = null): array {
        $pdo = Database::getConnection();
        if ($division) {
            $stmt = $pdo->prepare('SELECT * FROM participants WHERE pageant_id = ? AND division = ? AND is_active = 1 ORDER BY number_label');
            $stmt->execute([$pageant_id, $division]);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM participants WHERE pageant_id = ? AND is_active = 1 ORDER BY division, number_label');
            $stmt->execute([$pageant_id]);
        }
        return $stmt->fetchAll();
    }

    public static function addParticipants(int $pageant_id, array $participants): int {
        if (empty($participants)) return 0;
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO participants (pageant_id, division, number_label, full_name, advocacy, is_active, created_at) VALUES (?,?,?,?,?,1,NOW())');
            $count = 0;
            foreach ($participants as $p) {
                $stmt->execute([
                    $pageant_id,
                    $p['division'] ?? 'Mr',
                    $p['number_label'] ?? '',
                    $p['full_name'] ?? '',
                    $p['advocacy'] ?? null,
                ]);
                $count++;
            }
            $pdo->commit();
            return $count;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
