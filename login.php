<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
require_once 'config/db_sqlite.php';

checkRememberToken($pdo);

if (isLoggedIn()) {
    header('Location: home.php');
    exit;
}

$error   = h($_GET['error']   ?? '');
$success = h($_GET['msg']     ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username     = trim($_POST['user']    ?? '');
    $password     = $_POST['pass']         ?? '';
    $captchaInput = strtoupper(trim($_POST['captcha'] ?? ''));
    $remember     = isset($_POST['keep_log']);

    if ($username === '' || $password === '') {
        header('Location: login.php?error=' . urlencode('Completează toate câmpurile!'));
        exit;
    }

    if (empty($_SESSION['captcha']) || $captchaInput !== $_SESSION['captcha']) {
        header('Location: login.php?error=' . urlencode('Codul CAPTCHA este incorect!'));
        exit;
    }

    $stmt = $pdo->prepare(
        "SELECT id, username, password_hash, role, faction, rank_title
         FROM users WHERE username = ? LIMIT 1"
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['faction']    = $user['faction'];
        $_SESSION['rank_title'] = $user['rank_title'];
        unset($_SESSION['captcha']);

        if ($remember) {
            $token  = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
            $upd = $pdo->prepare(
                "UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?"
            );
            $upd->execute([$token, $expiry, $user['id']]);
            setcookie('remember_token', $token, [
                'expires'  => time() + 30 * 24 * 3600,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        logActivity($sqlite, $user['id'], $user['username'], 'login');
        header('Location: home.php');
        exit;
    }

    header('Location: login.php?error=' . urlencode('Utilizator sau parolă incorectă!'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style-vertical.css">
    <title>Quizztador - Autentificare</title>
    <style>
        .main-content { margin-left: 270px; padding: 30px; }
        .alert-error   { color: #c0392b; background: #fde8e8; border: 1px solid #e74c3c; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .alert-success { color: #1a6e1a; background: #e8fde8; border: 1px solid #27ae60; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .captcha-wrap  { display: flex; align-items: center; gap: 10px; margin: 8px 0; }
        .captcha-wrap img { border: 2px solid #ccc; border-radius: 5px; }
        .captcha-wrap a { color: #2c3e50; font-size: 0.85em; text-decoration: underline; }
        fieldset { border: 2px solid #2c3e50; border-radius: 8px; padding: 25px; background: #fff; max-width: 360px; }
        legend { font-weight: bold; color: #2c3e50; padding: 0 10px; }
    </style>
</head>
<body>
    <ul id="navi-bar">
        <li><a href="login.php">&#128274; Autentificare</a></li>
        <li><a href="signup.php">&#43; Cont nou</a></li>
    </ul>

    <div class="main-content">
        <h1>Quizztador</h1>
        <h2>Intră în contul tău</h2>

        <?php if ($error !== ''): ?>
            <p class="alert-error"><?= $error ?></p>
        <?php endif; ?>
        <?php if ($success !== ''): ?>
            <p class="alert-success"><?= $success ?></p>
        <?php endif; ?>

        <form method="post" action="login.php">
            <fieldset>
                <legend>Date de autentificare</legend>

                <label for="user">Nume utilizator:</label><br>
                <input type="text" id="user" name="user" size="25" maxlength="50"
                       placeholder="Pseudonimul tău" required><br><br>

                <label for="pass">Parolă:</label><br>
                <input type="password" id="pass" name="pass" size="25" maxlength="50"
                       placeholder="Parola secretă" required><br><br>

                <label>Cod de verificare (CAPTCHA):</label><br>
                <div class="captcha-wrap">
                    <img src="captcha.php" alt="Cod CAPTCHA" width="160" height="50">
                    <a href="login.php">&#8635; Reîncarcă</a>
                </div>
                <input type="text" name="captcha" size="10" maxlength="5"
                       placeholder="Introdu codul" required autocomplete="off"><br><br>

                <input type="checkbox" name="keep_log" value="1" id="remember">
                <label for="remember">Rămâi conectat (30 zile)</label><br><br>

                <input type="submit" value="Autentificare">
            </fieldset>
        </form>

        <br>
        <a href="signup.php">Nu ai cont? Creează-l aici!</a>
    </div>

    <footer style="margin-left:270px; padding:10px; text-align:center;">
        <p>Quizztador &copy; 2024</p>
    </footer>
</body>
</html>
