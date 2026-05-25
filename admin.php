<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
require_once 'config/db_pdo.php';
require_once 'config/db_sqlite.php';

checkRememberToken($pdo);
requireRole('admin');   // Only admins may access this page

$error   = '';
$success = '';

// Delete user (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_user') {
        $targetId = (int)($_POST['target_id'] ?? 0);
        if ($targetId === (int)$_SESSION['user_id']) {
            $error = 'Nu te poți șterge pe tine însuți!';
        } elseif ($targetId > 0) {
            // Delete profile picture if exists
            $sel = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
            $sel->execute([$targetId]);
            $pic = $sel->fetchColumn();
            if ($pic && file_exists(__DIR__ . '/uploads/' . $pic)) {
                unlink(__DIR__ . '/uploads/' . $pic);
            }
            $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $del->execute([$targetId]);
            logActivity($sqlite, $_SESSION['user_id'], $_SESSION['username'], 'admin_delete_user_' . $targetId);
            $success = 'Utilizatorul a fost șters.';
        }
    }
}

// Fetch all users (PDO)
$users = $pdo->query(
    "SELECT u.id, u.username, u.email, u.role, u.faction, u.rank_title, u.created_at,
            COUNT(b.id) AS battles,
            COALESCE(SUM(b.xp_gained), 0) AS total_xp
     FROM users u
     LEFT JOIN battles b ON b.user_id = u.id
     GROUP BY u.id
     ORDER BY u.created_at DESC"
)->fetchAll();

// Activity log from SQLite
$logRows = [];
if ($sqlite !== null) {
    $logRows = $sqlite->query(
        "SELECT username, action, ip_address, created_at
         FROM activity_log ORDER BY created_at DESC LIMIT 20"
    )->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style-responsive.css">
    <title>Quizztador - Panou Admin</title>
    <style>
        .alert-error   { color: #c0392b; background: #fde8e8; border: 1px solid #e74c3c; padding: 10px; border-radius: 5px; margin: 10px auto; max-width: 900px; }
        .alert-success { color: #1a6e1a; background: #e8fde8; border: 1px solid #27ae60; padding: 10px; border-radius: 5px; margin: 10px auto; max-width: 900px; }
        .section-box   { background: #fff; border-radius: 12px; padding: 25px; margin: 20px auto; max-width: 960px; box-shadow: 0 4px 6px rgba(0,0,0,.1); }
        .btn-danger    { background: #e74c3c; color: #fff; border: none; padding: 5px 12px; border-radius: 4px; cursor: pointer; }
        .admin-badge   { display: inline-block; background: #e74c3c; color: #fff; border-radius: 4px; padding: 2px 7px; font-size: .8em; }
        .player-badge  { display: inline-block; background: #2c3e50; color: #fff; border-radius: 4px; padding: 2px 7px; font-size: .8em; }
        table.admin-table { width: 100%; border-collapse: collapse; }
        table.admin-table th, table.admin-table td { padding: 10px; border-bottom: 1px solid #ddd; text-align: center; }
        table.admin-table th { background: #2c3e50; color: #fff; }
        table.admin-table tr:hover { background: #f9f9f9; }
    </style>
</head>
<body>
    <header>
        <center>
            <h1>&#9881; Panou de Administrare</h1>
            <h3>Acces restricționat — doar administratori</h3>
        </center>
    </header>

    <ul id="navi-bar">
        <li><a href="home.php">&#127968; Acasă</a></li>
        <li><a href="profile.php">&#128100; Profil</a></li>
        <li><a href="battles_json.php">&#9876; Batalii</a></li>
        <li><a href="admin.php">&#9881; Admin</a></li>
        <li><a href="logout.php">&#128274; Deconectare</a></li>
    </ul>

    <?php if ($error !== ''): ?>
        <p class="alert-error"><?= h($error) ?></p>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <p class="alert-success"><?= h($success) ?></p>
    <?php endif; ?>

    <!-- Statistici generale -->
    <main class="dashboard-container">
        <div class="card">
            <h3 class="card-title">Utilizatori</h3>
            <p class="card-data"><?= count($users) ?></p>
        </div>
        <div class="card">
            <h3 class="card-title">Bătălii totale</h3>
            <p class="card-data"><?= array_sum(array_column($users, 'battles')) ?></p>
        </div>
        <div class="card">
            <h3 class="card-title">XP total</h3>
            <p class="card-data"><?= array_sum(array_column($users, 'total_xp')) ?></p>
        </div>
        <div class="card">
            <h3 class="card-title">Admin conectat</h3>
            <p class="card-data" style="font-size:1.1em;"><?= h($_SESSION['username']) ?></p>
        </div>
    </main>

    <!-- Lista utilizatori -->
    <div class="section-box">
        <h3>Toți utilizatorii
            <small style="font-size:0.6em;color:#666;">(PDO SQLite)</small>
        </h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Utilizator</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Facțiune</th>
                    <th>Rang</th>
                    <th>Bătălii</th>
                    <th>XP</th>
                    <th>Înregistrat</th>
                    <th>Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= (int)$u['id'] ?></td>
                    <td><strong><?= h($u['username']) ?></strong></td>
                    <td><?= h($u['email']) ?></td>
                    <td>
                        <?php if ($u['role'] === 'admin'): ?>
                            <span class="admin-badge">admin</span>
                        <?php else: ?>
                            <span class="player-badge">player</span>
                        <?php endif; ?>
                    </td>
                    <td><?= h($u['faction']) ?></td>
                    <td><?= h($u['rank_title']) ?></td>
                    <td><?= (int)$u['battles'] ?></td>
                    <td><?= (int)$u['total_xp'] ?></td>
                    <td><?= h(substr($u['created_at'], 0, 10)) ?></td>
                    <td>
                        <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                        <form method="post" action="admin.php">
                            <input type="hidden" name="action"    value="delete_user">
                            <input type="hidden" name="target_id" value="<?= (int)$u['id'] ?>">
                            <button type="submit" class="btn-danger">&#128465; Șterge</button>
                        </form>
                        <?php else: ?>
                            <em>(tu)</em>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Jurnal activitate (SQLite) -->
    <div class="section-box">
        <h3>Jurnal activitate (ultimele 20 înregistrări)
            <small style="font-size:0.6em;color:#666;">(PDO SQLite)</small>
        </h3>
        <?php if (empty($logRows)): ?>
            <p>Nicio activitate înregistrată.</p>
        <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr><th>Utilizator</th><th>Acțiune</th><th>IP</th><th>Timestamp</th></tr>
            </thead>
            <tbody>
                <?php foreach ($logRows as $log): ?>
                <tr>
                    <td><?= h($log['username'] ?? '-') ?></td>
                    <td><?= h($log['action']) ?></td>
                    <td><?= h($log['ip_address']) ?></td>
                    <td><?= h($log['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <footer>
        <center><p>Quizztador &copy; 2024 &mdash; Admin: <?= h($_SESSION['username']) ?></p></center>
    </footer>
</body>
</html>
