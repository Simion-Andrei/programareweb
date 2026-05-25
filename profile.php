<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
require_once 'config/db_pdo.php';
require_once 'config/db_sqlite.php';

checkRememberToken($pdo);
requireLogin();

$uid     = (int)$_SESSION['user_id'];
$error   = '';
$success = '';

// ── CSRF token (generate once per session) ───────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ── Handle profile update (POST – update form) ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Validare CSRF token — protecție împotriva CSRF attacks
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Token CSRF invalid! Request respins (posibil atac CSRF).';
    } elseif (true) { // CSRF valid, continuă

    // Profile data update (PDO)
    if ($_POST['action'] === 'update_profile') {
        $email   = trim($_POST['email']   ?? '');
        $faction = $_POST['faction']      ?? '';
        $rank    = $_POST['rank_title']   ?? '';

        $validFactions = ['rosu', 'albastru', 'verde'];
        $validRanks    = ['taran', 'scutier', 'cavaler'];

        if (!in_array($faction, $validFactions, true) || !in_array($rank, $validRanks, true)) {
            $error = 'Valori invalide pentru facțiune sau rang.';
        } else {
            $stmt = $pdo->prepare(
                "UPDATE users SET email = ?, faction = ?, rank_title = ? WHERE id = ?"
            );
            $stmt->execute([$email, $faction, $rank, $uid]);
            $_SESSION['faction']    = $faction;
            $_SESSION['rank_title'] = $rank;
            logActivity($sqlite, $uid, $_SESSION['username'], 'profile_update');
            $success = 'Profilul a fost actualizat cu succes!';
        }
    }

    // File upload — profile picture
    if ($_POST['action'] === 'upload_picture') {
        if (!isset($_FILES['profile_pic']) || $_FILES['profile_pic']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Eroare la încărcarea fișierului.';
        } else {
            $file    = $_FILES['profile_pic'];
            $maxSize = 2 * 1024 * 1024; // 2 MB

            if ($file['size'] > $maxSize) {
                $error = 'Fișierul este prea mare (maxim 2 MB).';
            } else {
                $finfo    = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($file['tmp_name']);
                $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                if (!in_array($mimeType, $allowed, true)) {
                    $error = 'Tip de fișier nepermis. Sunt acceptate: JPG, PNG, GIF, WEBP.';
                } else {
                    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'user_' . $uid . '_' . bin2hex(random_bytes(8)) . '.' . strtolower($ext);
                    $destPath = __DIR__ . '/uploads/' . $filename;

                    // Delete old picture if exists
                    $old = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
                    $old->execute([$uid]);
                    $oldPic = $old->fetchColumn();
                    if ($oldPic && file_exists(__DIR__ . '/uploads/' . $oldPic)) {
                        unlink(__DIR__ . '/uploads/' . $oldPic);
                    }

                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        $upd = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                        $upd->execute([$filename, $uid]);
                        logActivity($sqlite, $uid, $_SESSION['username'], 'upload_picture');
                        $success = 'Poza de profil a fost actualizată!';
                    } else {
                        $error = 'Nu s-a putut salva fișierul pe server.';
                    }
                }
            }
        }
    }

    // Delete profile picture
    if ($_POST['action'] === 'delete_picture') {
        $old = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
        $old->execute([$uid]);
        $oldPic = $old->fetchColumn();
        if ($oldPic && file_exists(__DIR__ . '/uploads/' . $oldPic)) {
            unlink(__DIR__ . '/uploads/' . $oldPic);
        }
        $del = $pdo->prepare("UPDATE users SET profile_picture = NULL WHERE id = ?");
        $del->execute([$uid]);
        logActivity($sqlite, $uid, $_SESSION['username'], 'delete_picture');
        $success = 'Poza de profil a fost ștearsă.';
    }
    } // end CSRF valid block
}

// ── Fetch user data (PDO – precompletare formular) ───────────────────────────
$stmt = $pdo->prepare(
    "SELECT username, email, role, faction, rank_title, profile_picture, created_at
     FROM users WHERE id = ?"
);
$stmt->execute([$uid]);
$user = $stmt->fetch();

// ── Fetch battle history (PDO SQLite) ───────────────────────────────────────
$bStmt = $pdo->prepare(
    "SELECT opponent, opponent_faction, territory, question_domain,
            score_user, score_opponent, result, xp_gained, battle_date
     FROM battles WHERE user_id = ? ORDER BY battle_date DESC LIMIT 10"
);
$bStmt->execute([$uid]);
$battles = $bStmt->fetchAll();

// ── Stats ────────────────────────────────────────────────────────────────────
$stmtStats = $pdo->prepare(
    "SELECT COUNT(*) AS total,
            SUM(result='victorie')  AS wins,
            SUM(result='infrangere') AS losses,
            SUM(result='remiza')    AS draws,
            SUM(xp_gained)          AS total_xp
     FROM battles WHERE user_id = ?"
);
$stmtStats->execute([$uid]);
$stats = $stmtStats->fetch();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style-responsive.css">
    <title>Quizztador - Profilul meu</title>
    <style>
        .alert-error   { color: #c0392b; background: #fde8e8; border: 1px solid #e74c3c; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .alert-success { color: #1a6e1a; background: #e8fde8; border: 1px solid #27ae60; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .profile-pic   { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #2c3e50; }
        .profile-pic-placeholder { width: 120px; height: 120px; border-radius: 50%; background: #bdc3c7; display: inline-flex; align-items: center; justify-content: center; font-size: 3em; border: 3px solid #2c3e50; }
        .section-box   { background: #fff; border-radius: 12px; padding: 25px; margin: 20px auto; max-width: 900px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .upload-section { margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; }
        .result-victorie  { color: green; font-weight: bold; }
        .result-infrangere{ color: red;   font-weight: bold; }
        .result-remiza    { color: orange;font-weight: bold; }
    </style>
</head>
<body>
    <header>
        <center>
            <h1>Profilul Comandantului</h1>
            <h3>Statistici și Realizări Personale</h3>
        </center>
    </header>

    <ul id="navi-bar">
        <li><a href="home.php"><i class="icon icon-home"></i> Acasă</a></li>
        <li><a href="profile.php"><i class="icon icon-profile"></i> Profil</a></li>
        <li><a href="battles_json.php">&#9876; Batalii</a></li>
        <?php if ($_SESSION['user_role'] === 'admin'): ?>
        <li><a href="admin.php">&#9881; Admin</a></li>
        <?php endif; ?>
        <li><a href="logout.php">&#128274; Deconectare</a></li>
    </ul>

    <?php if ($error !== ''): ?>
        <p class="alert-error" style="max-width:860px;margin:10px auto;"><?= h($error) ?></p>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <p class="alert-success" style="max-width:860px;margin:10px auto;"><?= h($success) ?></p>
    <?php endif; ?>

    <!-- Statistici rapide -->
    <main class="dashboard-container">
        <div class="card">
            <h3 class="card-title">Rang Curent</h3>
            <p class="card-data"><?= h(ucfirst($user['rank_title'])) ?></p>
            <p>Facțiune: <strong><?= h(ucfirst($user['faction'])) ?></strong></p>
        </div>
        <div class="card">
            <h3 class="card-title">Victorii</h3>
            <p class="card-data"><?= (int)$stats['wins'] ?></p>
            <p>Total bătălii: <strong><?= (int)$stats['total'] ?></strong></p>
        </div>
        <div class="card">
            <h3 class="card-title">XP Total</h3>
            <p class="card-data"><?= (int)$stats['total_xp'] ?></p>
            <p>Înfrângeri: <strong><?= (int)$stats['losses'] ?></strong>
               &bull; Remize: <strong><?= (int)$stats['draws'] ?></strong></p>
        </div>
        <div class="card">
            <h3 class="card-title">Cont creat</h3>
            <p class="card-data" style="font-size:1em;"><?= h(substr($user['created_at'], 0, 10)) ?></p>
            <p>Rol: <strong><?= h($user['role']) ?></strong></p>
        </div>
    </main>

    <!-- Poza de profil + upload/stergere -->
    <div class="section-box">
        <h3>Poza de profil</h3>
        <div style="text-align:center; margin-bottom:15px;">
            <?php if (!empty($user['profile_picture']) && file_exists(__DIR__ . '/uploads/' . $user['profile_picture'])): ?>
                <img src="uploads/<?= h($user['profile_picture']) ?>"
                     alt="Poza de profil" class="profile-pic"><br><br>
                <!-- Stergere poza (fara JS) -->
                <form method="post" action="profile.php">
                    <input type="hidden" name="action" value="delete_picture">
                    <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">
                    <input type="submit" value="&#128465; Șterge poza"
                           style="background:#e74c3c; color:#fff; border:none; padding:8px 16px; border-radius:5px; cursor:pointer;">
                </form>
            <?php else: ?>
                <div class="profile-pic-placeholder">&#128100;</div>
                <p><em>Nicio poză încărcată.</em></p>
            <?php endif; ?>
        </div>

        <!-- Upload poza -->
        <div class="upload-section">
            <h4>Încarcă / Schimbă poza de profil</h4>
            <form method="post" action="profile.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_picture">
                <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">
                <label for="profile_pic">Selectează imagine (JPG/PNG/GIF/WEBP, max 2MB):</label><br><br>
                <input type="file" id="profile_pic" name="profile_pic" accept="image/*" required><br><br>
                <input type="submit" value="&#8679; Încarcă poza">
            </form>
        </div>
    </div>

    <!-- Editare profil (formular precompletat din BD via PDO) -->
    <div class="section-box">
        <h3>Editează profilul</h3>
        <p><em>Datele de mai jos sunt precompletate din baza de date.</em></p>

        <form method="post" action="profile.php">
            <input type="hidden" name="action" value="update_profile">
            <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">

            <label>Nume utilizator (nu poate fi schimbat):</label><br>
            <input type="text" value="<?= h($user['username']) ?>" disabled
                   style="background:#eee; max-width:300px;"><br><br>

            <label for="email">Adresă email:</label><br>
            <input type="email" id="email" name="email" maxlength="100"
                   value="<?= h($user['email'] ?? '') ?>"
                   style="max-width:300px;"><br><br>

            <label for="faction">Facțiunea:</label><br>
            <select id="faction" name="faction" style="max-width:300px;">
                <option value="rosu"     <?= $user['faction'] === 'rosu'     ? 'selected' : '' ?>>Imperiul Roșu</option>
                <option value="albastru" <?= $user['faction'] === 'albastru' ? 'selected' : '' ?>>Imperiul Albastru</option>
                <option value="verde"    <?= $user['faction'] === 'verde'    ? 'selected' : '' ?>>Hoarda Verde</option>
            </select><br><br>

            <label for="rank_title">Rang:</label><br>
            <select id="rank_title" name="rank_title" style="max-width:300px;">
                <option value="taran"   <?= $user['rank_title'] === 'taran'   ? 'selected' : '' ?>>Țăran</option>
                <option value="scutier" <?= $user['rank_title'] === 'scutier' ? 'selected' : '' ?>>Scutier</option>
                <option value="cavaler" <?= $user['rank_title'] === 'cavaler' ? 'selected' : '' ?>>Cavaler</option>
            </select><br><br>

            <input type="submit" value="&#10003; Salvează modificările">
        </form>
    </div>

    <!-- Istoricul bătăliilor (MySQLi) -->
    <div class="table-wrapper" style="max-width:920px; margin:0 auto 30px;">
        <h3 style="text-align:center; color:#333;">
            Istoric Bătălii Recente
            <small style="font-size:0.6em; color:#666;">(PDO SQLite)</small>
        </h3>
        <?php if (empty($battles)): ?>
            <p style="text-align:center;">Nu ai bătălii înregistrate.</p>
        <?php else: ?>
        <table class="responsive-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Oponent</th>
                    <th>Facțiune oponent</th>
                    <th>Teritoriu</th>
                    <th>Domeniu</th>
                    <th>Scor</th>
                    <th>Rezultat</th>
                    <th>XP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($battles as $b): ?>
                <tr>
                    <td><?= h($b['battle_date']) ?></td>
                    <td><?= h($b['opponent']) ?></td>
                    <td><?= h($b['opponent_faction']) ?></td>
                    <td><?= h($b['territory']) ?></td>
                    <td><?= h($b['question_domain']) ?></td>
                    <td><?= (int)$b['score_user'] ?> - <?= (int)$b['score_opponent'] ?></td>
                    <td class="result-<?= h($b['result']) ?>"><?= h(ucfirst($b['result'])) ?></td>
                    <td>+<?= (int)$b['xp_gained'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <footer>
        <center>
            <br>
            <a href="logout.php">Deconectare</a>
            <p>Quizztador &copy; 2024</p>
        </center>
    </footer>
</body>
</html>
