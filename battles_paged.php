<?php
require_once 'includes/auth.php';
require_once 'config/db_pdo.php';

checkRememberToken($pdo);
requireLogin();

$user   = $_SESSION;
$userId = (int)$user['user_id'];
$K      = 5;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $K;

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM battles WHERE user_id = ?");
$stmtTotal->execute([$userId]);
$total      = (int)$stmtTotal->fetchColumn();
$totalPages = max(1, (int)ceil($total / $K));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $K;

$stmt = $pdo->prepare(
    "SELECT id, opponent, opponent_faction, territory, question_domain,
            score_user, score_opponent, result, xp_gained, battle_date
     FROM battles
     WHERE user_id = ?
     ORDER BY battle_date DESC
     LIMIT {$K} OFFSET {$offset}"
);
$stmt->execute([$userId]);
$battles = $stmt->fetchAll();

function resultClass(string $r): string {
    if ($r === 'victorie')   return 'result-victorie';
    if ($r === 'infrangere') return 'result-infrangere';
    return 'result-remiza';
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style-responsive.css">
    <title>Quizztador - Batalii (Server-side)</title>
    <style>
        .section-box   { max-width: 960px; margin: 20px auto; background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .admin-table   { width: 100%; border-collapse: collapse; }
        .admin-table th { background: #2c3e50; color: #fff; padding: 10px; text-align: left; }
        .admin-table td { padding: 10px; border-bottom: 1px solid #ddd; }
        .admin-table tr:hover td { background: #f9f9f9; }
        .result-victorie  { color: #27ae60; font-weight: bold; }
        .result-infrangere{ color: #e74c3c; font-weight: bold; }
        .result-remiza    { color: #f39c12; font-weight: bold; }
        .btn-nav  { display: inline-block; padding: 8px 18px; margin: 4px; background: #2c3e50; color: #fff; border-radius: 6px; text-decoration: none; font-size: 14px; }
        .btn-nav.disabled { background: #95a5a6; pointer-events: none; }
        .sub-nav  { max-width: 960px; margin: 10px auto; display: flex; gap: 8px; flex-wrap: wrap; }
        .sub-nav a { padding: 6px 14px; background: #e74c3c; color: #fff; border-radius: 5px; text-decoration: none; font-size: 13px; }
        .sub-nav a.active { background: #2c3e50; }
        .page-info { color: #555; font-size: 13px; margin: 8px 0; }
    </style>
</head>
<body>
    <header>
        <center><h1>&#9876; Bataliile Mele — Server-side (Cerința 4)</h1></center>
    </header>

    <ul id="navi-bar">
        <li><a href="home.php">&#127968; Acasă</a></li>
        <li><a href="profile.php">&#128100; Profil</a></li>
        <li><a href="battles_json.php">&#9876; Batalii</a></li>
        <?php if ($user['user_role'] === 'admin'): ?>
        <li><a href="admin.php">&#9881; Admin</a></li>
        <?php endif; ?>
        <li><a href="logout.php">&#128274; Deconectare</a></li>
    </ul>

    <div class="sub-nav">
        <a href="battles_json.php">1. JS + JSON</a>
        <a href="battles_xml.php">2. JS + XML</a>
        <a href="battles_jquery.php">3. jQuery</a>
        <a href="battles_paged.php" class="active">4. Server-side</a>
        <a href="battles_edit.php">5. Edit JS</a>
        <a href="battles_edit_jquery.php">6. Edit jQuery</a>
    </div>

    <div class="section-box">
        <h2>Afișare paginată — exclusiv server-side (fără JavaScript)</h2>
        <p>Navigarea între pagini se realizează exclusiv prin mecanisme server-side (URL parameters). Câte <strong>5</strong> înregistrări pe pagină.</p>

        <p class="page-info">Pagina <?= $page ?> din <?= $totalPages ?> (<?= $total ?> bătălii total)</p>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Adversar</th>
                    <th>Facțiune adv.</th>
                    <th>Teritoriu</th>
                    <th>Domeniu</th>
                    <th>Scor</th>
                    <th>Rezultat</th>
                    <th>XP</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($battles)): ?>
                <tr><td colspan="9" style="text-align:center">Nicio bătălie înregistrată.</td></tr>
                <?php else: ?>
                <?php foreach ($battles as $b): ?>
                <tr>
                    <td><?= (int)$b['id'] ?></td>
                    <td><?= h($b['opponent']) ?></td>
                    <td><?= h($b['opponent_faction']) ?></td>
                    <td><?= h($b['territory']) ?></td>
                    <td><?= h($b['question_domain']) ?></td>
                    <td><?= (int)$b['score_user'] ?> - <?= (int)$b['score_opponent'] ?></td>
                    <td class="<?= resultClass($b['result']) ?>"><?= h($b['result']) ?></td>
                    <td><?= (int)$b['xp_gained'] ?></td>
                    <td><?= h($b['battle_date']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div style="margin-top:14px;">
            <?php if ($page <= 1): ?>
            <span class="btn-nav disabled">&#8592; Previous <?= $K ?></span>
            <?php else: ?>
            <a class="btn-nav" href="?page=<?= $page - 1 ?>">&#8592; Previous <?= $K ?></a>
            <?php endif; ?>

            <?php if ($page >= $totalPages): ?>
            <span class="btn-nav disabled">Next <?= $K ?> &#8594;</span>
            <?php else: ?>
            <a class="btn-nav" href="?page=<?= $page + 1 ?>">Next <?= $K ?> &#8594;</a>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <center><p>Quizztador &copy; 2024 &mdash; Conectat ca: <strong><?= h($user['username']) ?></strong></p></center>
    </footer>
</body>
</html>
