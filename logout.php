<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
require_once 'config/db_sqlite.php';

if (isLoggedIn()) {
    // Clear remember_me token from DB
    if (isset($_COOKIE['remember_token'])) {
        $stmt = $mysqli->prepare(
            "UPDATE users SET remember_token = NULL, token_expiry = NULL WHERE id = ?"
        );
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        setcookie('remember_token', '', ['expires' => time() - 3600, 'path' => '/']);
    }

    logActivity($sqlite, $_SESSION['user_id'], $_SESSION['username'], 'logout');
    $_SESSION = [];
    session_destroy();
}

header('Location: login.php?msg=' . urlencode('Ai fost deconectat cu succes.'));
exit;
