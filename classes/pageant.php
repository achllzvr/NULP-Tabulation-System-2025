<?php
// Pageant procedural helpers (migrated from PageantService OOP class)
require_once __DIR__.'/database.php';

// --- Basic getters / context ---
function pageant_get_current(): ?array {
	if (empty($_SESSION['pageant_id'])) return null;
	$pdo = Database::get();
	$stmt = $pdo->prepare('SELECT * FROM pageants WHERE id=?');
	$stmt->execute([$_SESSION['pageant_id']]);
	return $stmt->fetch() ?: null;
}

function pageant_set_current(int $id): bool {
	$pdo = Database::get();
	$stmt = $pdo->prepare('SELECT id FROM pageants WHERE id=?');
	$stmt->execute([$id]);
	if ($stmt->fetch()) { $_SESSION['pageant_id'] = $id; return true; }
	return false;
}

function pageant_get(int $id): ?array {
	$pdo = Database::get();
	$stmt = $pdo->prepare('SELECT * FROM pageants WHERE id=?');
	$stmt->execute([$id]);
	return $stmt->fetch() ?: null;
}

function pageant_get_by_code(string $code): ?array {
	$pdo = Database::get();
	$stmt = $pdo->prepare('SELECT * FROM pageants WHERE code=?');
	$stmt->execute([$code]);
	return $stmt->fetch() ?: null;
}

function pageant_get_active(): ?array {
	$pdo = Database::get();
	$stmt = $pdo->query("SELECT * FROM pageants WHERE status IN ('PRELIM_RUNNING','FINAL_RUNNING') ORDER BY created_at DESC LIMIT 1");
	return $stmt->fetch() ?: null;
}

function pageant_get_default(): ?array {
	$pdo = Database::get();
	$stmt = $pdo->query("SELECT * FROM pageants ORDER BY created_at DESC LIMIT 1");
	return $stmt->fetch() ?: null;
}

// --- Listing helpers ---
function pageant_list_all(): array {
	$pdo = Database::get();
	return $pdo->query('SELECT * FROM pageants ORDER BY created_at DESC')->fetchAll();
}

function pageant_list_divisions(int $pageantId): array {
	$pdo = Database::get();
	$stmt = $pdo->prepare('SELECT * FROM divisions WHERE pageant_id=? ORDER BY name');
	$stmt->execute([$pageantId]);
	return $stmt->fetchAll();
}

function pageant_list_rounds(int $pageantId): array {
	$pdo = Database::get();
	// Keep ordering compatibility: prefer explicit sequence/ordering columns if present
	$stmt = $pdo->prepare('SELECT * FROM rounds WHERE pageant_id=? ORDER BY sequence ASC, id ASC');
	$stmt->execute([$pageantId]);
	return $stmt->fetchAll();
}

// --- Mutations ---
function pageant_create(string $name, string $code, ?int $year=null): ?array {
	$pdo = Database::get();
	if ($year === null) $year = (int)date('Y');
	$stmt = $pdo->prepare('INSERT INTO pageants (name, code, year, status, created_at) VALUES (?,?,?,"DRAFT",NOW())');
	$stmt->execute([$name,$code,$year]);
	return pageant_get((int)$pdo->lastInsertId());
}

function pageant_update_status(int $pageantId, string $status): bool {
	$pdo = Database::get();
	$stmt = $pdo->prepare('UPDATE pageants SET status=?, updated_at=NOW() WHERE id=?');
	$stmt->execute([$status,$pageantId]);
	return $stmt->rowCount()===1;
}

function pageant_assign_admin(int $pageantId, int $userId): void {
	$pdo = Database::get();
	$stmt = $pdo->prepare("INSERT INTO pageant_users (pageant_id,user_id,role) VALUES (?,?, 'ADMIN') ON DUPLICATE KEY UPDATE role='ADMIN'");
	$stmt->execute([$pageantId,$userId]);
}

function pageant_assign_judge(int $pageantId, int $userId): void {
	$pdo = Database::get();
	$stmt = $pdo->prepare("INSERT INTO pageant_users (pageant_id,user_id,role) VALUES (?,?, 'JUDGE') ON DUPLICATE KEY UPDATE role='JUDGE'");
	$stmt->execute([$pageantId,$userId]);
}

// Utility: ensure pageant selected in session (fallback to default if absent)
function pageant_ensure_session(): void {
	if (empty($_SESSION['pageant_id'])) {
		$p = pageant_get_active() ?: pageant_get_default();
		if ($p) $_SESSION['pageant_id'] = (int)$p['id'];
	}
}

