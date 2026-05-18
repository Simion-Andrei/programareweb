<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
require_once 'config/db_pdo.php';
require_once 'config/db_sqlite.php';

checkRememberToken($mysqli);
requireLogin();

$user = $_SESSION;

// Leaderboard via PDO (requirement: PDO usage)
$stmt = $pdo->query(
    "SELECT u.username, u.faction,
            COUNT(b.id) AS total_battles,
            SUM(b.result = 'victorie') AS victories,
            SUM(b.xp_gained) AS total_xp
     FROM users u
     LEFT JOIN battles b ON b.user_id = u.id
     GROUP BY u.id
     ORDER BY total_xp DESC
     LIMIT 10"
);
$leaderboard = $stmt->fetchAll();

// Message of the day via PDO
$messageOfDay = 'Fii pe fază pentru turneul de la ora 20:00!';

$error   = h($_GET['error'] ?? '');
$success = h($_GET['msg']   ?? '');
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style-orizontal.css">
    <title>Quizztador - Acasă</title>
    <style>
        .alert-error   { color: #c0392b; background: #fde8e8; border: 1px solid #e74c3c; padding: 10px; border-radius: 5px; margin: 10px auto; max-width: 600px; }
        .alert-success { color: #1a6e1a; background: #e8fde8; border: 1px solid #27ae60; padding: 10px; border-radius: 5px; margin: 10px auto; max-width: 600px; }
        .user-badge    { float: right; padding: 14px 20px; color: #fff; font-weight: bold; }
    </style>
</head>
<body bgcolor="#e6e6fa">
    <header>
        <center>
            <h1>Bun venit în Tabăra de Bază, <?= h($user['username']) ?>!</h1>
            <h3>Facțiunea: <?= h($user['faction']) ?> &bull; Rang: <?= h($user['rank_title']) ?></h3>
        </center>
    </header>

    <ul id="navi-bar">
        <li><a href="home.php"><i class="icon icon-home"></i> Acasă</a></li>
        <li><a href="profile.php"><i class="icon icon-profile"></i> Profil</a></li>
        <?php if ($user['user_role'] === 'admin'): ?>
        <li><a href="admin.php">&#9881; Admin</a></li>
        <?php endif; ?>
        <li><a href="logout.php">&#128274; Deconectare</a></li>
    </ul>

    <?php if ($error !== ''): ?>
        <p class="alert-error"><?= $error ?></p>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <p class="alert-success"><?= $success ?></p>
    <?php endif; ?>

    <div align="center">
        <h4>Informații Rapide</h4>
        <h5>Mesajul zilei: <?= h($messageOfDay) ?></h5>
    </div>

    <br>

    <!-- Clasament dinamic din baza de date (PDO) -->
    <table border="2" width="70%" align="center" bgcolor="#ffffff">
        <tr>
            <th colspan="5" bgcolor="#d3d3d3">CLASAMENT JUCĂTORI</th>
        </tr>
        <tr bgcolor="#e0e0e0">
            <th>#</th>
            <th>Jucător</th>
            <th>Facțiune</th>
            <th>Victorii</th>
            <th>XP Total</th>
        </tr>
        <?php foreach ($leaderboard as $i => $row): ?>
        <tr <?= $row['username'] === $user['username'] ? 'bgcolor="#fffacd"' : '' ?>>
            <td align="center"><?= $i + 1 ?></td>
            <td align="center"><strong><?= h($row['username']) ?></strong></td>
            <td align="center"><?= h($row['faction']) ?></td>
            <td align="center"><?= (int)$row['victories'] ?></td>
            <td align="center"><?= (int)$row['total_xp'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($leaderboard)): ?>
        <tr><td colspan="5" align="center">Nicio bătălie înregistrată.</td></tr>
        <?php endif; ?>
    </table>

    <br>

    <table border="2" width="95%" align="center" bgcolor="#ffffff">
        <tr>
            <th colspan="3" bgcolor="#d3d3d3">HARTA IMPERIILOR ȘI CLASAMENT</th>
        </tr>
        <tr>
            <th width="30%">Distribuția Teritoriilor</th>
            <th width="40%">Regiuni Controlate</th>
            <th width="30%">Domenii de Întrebări</th>
        </tr>
        <tr>
            <td valign="top" align="left">
                <ul>
                    <li>Imperiul Roșu — 40%</li>
                    <li>Imperiul Albastru — 35%</li>
                    <li>Hoarda Verde — 25%</li>
                </ul>
            </td>
            <td align="center" valign="middle">
                <table border="1" width="90%" bgcolor="#fff0f5">
                    <tr><th colspan="2">Regiuni</th></tr>
                    <tr>
                        <td rowspan="2" align="center">Transilvania</td>
                        <td align="center">Roșu: 40%</td>
                    </tr>
                    <tr><td align="center">Albastru: 60%</td></tr>
                    <tr>
                        <td colspan="2" align="center">
                            <table border="1" width="100%" bgcolor="#e0ffff">
                                <tr><th>Muntenia</th><th>Moldova</th></tr>
                                <tr>
                                    <td align="center">Egalitate de forțe</td>
                                    <td align="center">Hoarda Verde domină</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
            <td valign="top">
                <ul>
                    <li>Istorie &amp; Geografie</li>
                    <li>Științe &amp; Natură
                        <ul>
                            <li>Fizică</li>
                            <li>Biologie</li>
                        </ul>
                    </li>
                    <li>Artă &amp; Literatură</li>
                </ul>
                <p>Ajutoare: Telescopul zburător, Papagalul vorbăreț</p>
            </td>
        </tr>
    </table>

    <br>

    <center>
        <h2>Contactează echipa de suport</h2>
        <form method="post" action="support_handler.php"
              style="border:1px solid #333; padding:20px; background:#fff; width:340px; border-radius:10px; display:inline-block; text-align:left;">
            <label for="msgTitle">Subiectul problemei:</label><br>
            <input type="text" id="msgTitle" name="subject" placeholder="Ex: Eroare de conectare" style="width:100%;"><br><br>

            <label for="msgDate">Data apariției problemei:</label><br>
            <input type="date" id="msgDate" name="incident_date" required style="width:100%;"><br><br>

            <label for="msgDetails">Descrierea detaliată:</label><br>
            <textarea id="msgDetails" name="details" placeholder="Descrie problema" rows="4" style="width:100%;"></textarea><br><br>

            <input type="submit" value="Trimite la Suport">
        </form>
    </center>

    <br>

    <footer>
        <center>
            <p>Quizztador &copy; 2024 &mdash; Conectat ca: <strong><?= h($user['username']) ?></strong></p>
        </center>
    </footer>

    <script src="data.js"></script>
    <script src="script.js"></script>
</body>
</html>
