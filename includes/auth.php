<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(string $redirect = 'login.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . $redirect);
        exit;
    }
}

function requireRole(string $role, string $redirect = 'home.php'): void {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== $role) {
        header('Location: ' . $redirect . '?error=forbidden');
        exit;
    }
}

// Auto-login via remember_me cookie (MySQLi)
function checkRememberToken(mysqli $db): void {
    if (isLoggedIn() || !isset($_COOKIE['remember_token'])) {
        return;
    }
    $token = $_COOKIE['remember_token'];
    $stmt  = $db->prepare(
        "SELECT id, username, role, faction, rank_title
         FROM users
         WHERE remember_token = ? AND token_expiry > NOW()
         LIMIT 1"
    );
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user) {
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['faction']    = $user['faction'];
        $_SESSION['rank_title'] = $user['rank_title'];
    }
}

// Write to SQLite activity log (PDO SQLite)
function logActivity(?PDO $sqlite, int $userId, string $username, string $action): void {
    if ($sqlite === null) return;
    $ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $sqlite->prepare(
        "INSERT INTO activity_log (user_id, username, action, ip_address) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$userId, $username, $action, $ip]);
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
