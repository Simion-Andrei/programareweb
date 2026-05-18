<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>SQLi Login — Vulnerabil</title>
    <style>
        body { font-family: sans-serif; max-width: 700px; margin: 40px auto; background: #f5f5f5; }
        .vuln-box { background:#fff3cd; border:2px solid #e74c3c; padding:15px; border-radius:5px; margin:15px 0; }
        .query-box { background:#2c3e50; color:#2ecc71; padding:15px; border-radius:5px; font-family:monospace; white-space:pre-wrap; word-break:break-all; }
        .success { background:#d4edda; border:1px solid #28a745; padding:10px; border-radius:5px; color:#155724; }
        .error   { background:#f8d7da; border:1px solid #dc3545; padding:10px; border-radius:5px; color:#721c24; }
        input[type=text], input[type=password] { width:100%; padding:8px; margin:5px 0 10px; box-sizing:border-box; border:1px solid #ccc; border-radius:4px; }
        input[type=submit] { background:#e74c3c; color:#fff; padding:10px 20px; border:none; border-radius:4px; cursor:pointer; }
        .exploit-examples a { display:block; margin:5px 0; font-family:monospace; color:#c0392b; }
    </style>
</head>
<body>
    <h1>&#128272; SQL Injection — Bypass Login</h1>

    <div class="vuln-box">
        <strong>&#9888; PAGINĂ INTENȚIONAT VULNERABILĂ</strong><br>
        Parola nu este hash-uită. Query-ul construiește direct din input utilizator.
    </div>

<?php
require_once '../config/db.php';

$username  = $_POST['username'] ?? '';
$password  = $_POST['password'] ?? '';
$result_msg = '';
$vuln_sql  = '';
$db_error  = '';
$user = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $username !== '') {
    // ===== VULNERABIL: concatenare directă — NICIODATĂ nu faceți asta în producție! =====
    $vuln_sql = "SELECT * FROM vuln_users WHERE username = '$username' AND password = '$password'";
    // =====================================================================================

    try {
        $res = $mysqli->query($vuln_sql);
        if ($res && $res->num_rows > 0) {
            $user = $res->fetch_assoc();
        }
    } catch (mysqli_sql_exception $e) {
        $db_error = $e->getMessage();
    }
}
?>

    <form method="post">
        <label>Username:</label>
        <input type="text"     name="username" value="<?= htmlspecialchars($username) ?>"
               placeholder="Încearcă: admin'#">
        <label>Parolă:</label>
        <input type="password" name="password" placeholder="(orice)">
        <input type="submit" value="Login vulnerabil">
    </form>

    <?php if ($vuln_sql !== ''): ?>
    <h3>Query executat:</h3>
    <div class="query-box"><?= htmlspecialchars($vuln_sql) ?></div>
    <?php endif; ?>

    <?php if ($db_error !== ''): ?>
        <br><div class="error">&#9888; Eroare SQL: <?= htmlspecialchars($db_error) ?></div>
    <?php endif; ?>

    <?php if ($user): ?>
        <br><div class="success">
            &#10003; Autentificat ca: <strong><?= htmlspecialchars($user['username']) ?></strong>
            (rol: <?= htmlspecialchars($user['role']) ?>)<br>
            <em>Parola introdusă: "<?= htmlspecialchars($password) ?>" — nu a contat!</em>
        </div>
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <br><div class="error">&#10005; Autentificare eșuată.</div>
    <?php endif; ?>

    <h2>Exemple de exploit</h2>
    <div class="exploit-examples">
        <strong>Username → bypass total (ignoră parola):</strong>
        <a onclick="document.querySelector('[name=username]').value=this.textContent; return false;"
           href="#">admin'#</a>
        <a onclick="document.querySelector('[name=username]').value=this.textContent; return false;"
           href="#">' OR '1'='1'#</a>

        <strong>Username → autentificare ca orice user:</strong>
        <a onclick="document.querySelector('[name=username]').value=this.textContent; return false;"
           href="#">' OR 1=1 LIMIT 1#</a>
    </div>

    <h2>Remediere (cod securizat)</h2>
    <div class="query-box">// Prepared statement — input-ul NU intră în query
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
$stmt->execute([$username, $password]);
$user = $stmt->fetch();</div>

    <br><a href="index.php">← Înapoi la index</a>
</body>
</html>
