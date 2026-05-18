<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Path Traversal — Vulnerabil</title>
    <style>
        body { font-family: sans-serif; max-width: 750px; margin: 40px auto; background: #f5f5f5; }
        .vuln-box { background:#fff3cd; border:2px solid #e74c3c; padding:15px; border-radius:5px; margin:15px 0; }
        .code-box { background:#2c3e50; color:#2ecc71; padding:15px; border-radius:5px; font-family:monospace; white-space:pre-wrap; margin:10px 0; word-break:break-all; }
        .file-content { background:#1a1a2e; color:#ccc; padding:15px; border-radius:5px; font-family:monospace; white-space:pre-wrap; max-height:300px; overflow:auto; margin:10px 0; font-size:.85em; }
        input[type=text] { width:100%; padding:8px; box-sizing:border-box; border:1px solid #ccc; border-radius:4px; margin:5px 0; }
        input[type=submit] { background:#e74c3c; color:#fff; padding:8px 16px; border:none; border-radius:4px; cursor:pointer; }
        .exploit-btn { display:inline-block; background:#c0392b; color:#fff; padding:5px 10px; border-radius:4px; text-decoration:none; margin:3px; font-family:monospace; font-size:.85em; }
    </style>
</head>
<body>
    <h1>&#128272; Path Traversal Attack</h1>

    <div class="vuln-box">
        <strong>&#9888; PAGINĂ INTENȚIONAT VULNERABILĂ</strong><br>
        Parametrul <code>file</code> nu este sanitizat → atacatorul poate citi orice fișier
        accesibil de pe server folosind secvențe <code>../</code> (directory traversal).
    </div>

<?php
$file_param = $_GET['file'] ?? '';
$content    = '';
$error      = '';
$full_path  = '';

if ($file_param !== '') {
    // ===== VULNERABIL: nicio sanitizare — permite ../../../orice =====
    $full_path = __DIR__ . '/uploads/' . $file_param;
    // =================================================================

    if (file_exists($full_path) && is_file($full_path)) {
        $content = file_get_contents($full_path);
    } else {
        $error = "Fișierul nu există: " . htmlspecialchars($full_path);
    }
}
?>

    <h2>Viewer fișiere (vulnerabil)</h2>
    <form method="get">
        <label>Numele fișierului din <code>vuln/uploads/</code>:</label>
        <input type="text" name="file" value="<?= htmlspecialchars($file_param) ?>"
               placeholder="ex: test.txt sau ../config/db.php">
        <input type="submit" value="Citește fișierul">
    </form>

    <h3>Exploit-uri rapide:</h3>
    <a class="exploit-btn" href="?file=../config/db.php">../config/db.php<br>(credențiale BD!)</a>
    <a class="exploit-btn" href="?file=../includes/auth.php">../includes/auth.php</a>
    <a class="exploit-btn" href="?file=../../../etc/passwd">../../../etc/passwd<br>(utilizatori sistem)</a>
    <a class="exploit-btn" href="?file=../../../etc/hosts">../../../etc/hosts</a>

    <?php if ($full_path !== ''): ?>
        <h3>Cale rezolvată pe server:</h3>
        <div class="code-box"><?= htmlspecialchars($full_path) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p style="color:red"><?= $error ?></p>
    <?php elseif ($content !== ''): ?>
        <h3>Conținut fișier (<?= htmlspecialchars($file_param) ?>):</h3>
        <div class="file-content"><?= htmlspecialchars($content) ?></div>
    <?php endif; ?>

    <h2>De ce e periculos</h2>
    <div class="code-box">// Dacă aplicația rulează ca www-data sau root, poate citi:
?file=../../../etc/passwd          → lista utilizatorilor sistem
?file=../../../etc/shadow          → parole hash sistem (dacă are permisiuni)
?file=../config/db.php             → credențiale baza de date
?file=../../../var/log/apache2/access.log → loguri server
?file=../../../home/user/.ssh/id_rsa → chei SSH private</div>

    <h2>Remediere</h2>
    <div class="code-box">// ✅ Folosește basename() + realpath() pentru a bloca traversal
$base_dir  = realpath(__DIR__ . '/uploads/');
$file_name = basename($_GET['file']);          // elimină ../ și /
$full_path = realpath($base_dir . '/' . $file_name);

// Verifică că fișierul e CHIAR în uploads/, nu în altă parte
if ($full_path === false || !str_starts_with($full_path, $base_dir)) {
    die('Acces interzis!');
}
$content = file_get_contents($full_path);</div>

    <br><a href="index.php">← Înapoi la index</a>
</body>
</html>
