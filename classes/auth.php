<?php
require_once __DIR__.'/database.php';
function auth_login(string $email, string $password): bool {
    $pdo = Database::get();
    $stmt = $pdo->prepare("SELECT id, password_hash, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if (!$row || !password_verify($password, $row['password_hash'])) return false;
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$row['id'];
    $_SESSION['user_role'] = $row['role'];
    return true;
}
function auth_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
function auth_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    return ['id'=>(int)$_SESSION['user_id'],'role'=>$_SESSION['user_role']??'UNKNOWN'];
}
function auth_require_login(): void { if (!auth_user()) { header('Location: login.php'); exit; } }
function auth_require_role(array $roles): void { $u = auth_user(); if (!$u || !in_array($u['role'], $roles, true)) { http_response_code(403); echo 'Forbidden'; exit; } }
