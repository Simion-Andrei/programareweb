<?php
require_once 'includes/auth.php';
require_once 'config/db.php';

if (isLoggedIn()) {
    header('Location: home.php');
    exit;
}

$error   = '';
$success = '';
$old     = [];  // repopulate form on error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['new_user'] ?? '');
    $password = $_POST['new_pass']      ?? '';
    $email    = trim($_POST['email']    ?? '');
    $age      = (int)($_POST['age']     ?? 0);
    $faction  = $_POST['faction']       ?? 'rosu';
    $rank     = $_POST['rank']          ?? 'scutier';
    $terms    = isset($_POST['terms']);
    $old      = compact('username', 'email', 'age', 'faction', 'rank');

    $validFactions = ['rosu', 'albastru', 'verde'];
    $validRanks    = ['taran', 'scutier', 'cavaler'];

    if ($username === '' || $password === '') {
        $error = 'Numele de utilizator și parola sunt obligatorii.';
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $error = 'Numele de utilizator trebuie să aibă între 3 și 20 de caractere.';
    } elseif (strlen($password) < 6) {
        $error = 'Parola trebuie să aibă cel puțin 6 caractere.';
    } elseif ($age < 10 || $age > 99) {
        $error = 'Vârsta trebuie să fie între 10 și 99 ani.';
    } elseif (!$terms) {
        $error = 'Trebuie să accepți regulamentul pentru a continua.';
    } elseif (!in_array($faction, $validFactions, true)) {
        $error = 'Facțiune invalidă.';
    } elseif (!in_array($rank, $validRanks, true)) {
        $error = 'Rang invalid.';
    } else {
        // Check username uniqueness with MySQLi
        $chk = $mysqli->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $chk->bind_param('s', $username);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = 'Acest nume de utilizator este deja folosit.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = $mysqli->prepare(
                "INSERT INTO users (username, password_hash, email, role, faction, rank_title)
                 VALUES (?, ?, ?, 'player', ?, ?)"
            );
            $ins->bind_param('sssss', $username, $hash, $email, $faction, $rank);
            if ($ins->execute()) {
                header('Location: login.php?msg=' . urlencode('Cont creat cu succes! Te poți autentifica acum.'));
                exit;
            } else {
                $error = 'Eroare la crearea contului. Încearcă din nou.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style-vertical.css">
    <title>Quizztador - Înregistrare</title>
    <style>
        .main-content { margin-left: 270px; padding: 30px; }
        .alert-error { color: #c0392b; background: #fde8e8; border: 1px solid #e74c3c; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        fieldset { border: 2px solid #2c3e50; border-radius: 8px; padding: 25px; background: #fff; max-width: 420px; }
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
        <h2>Formular de înregistrare</h2>

        <?php if ($error !== ''): ?>
            <p class="alert-error"><?= h($error) ?></p>
        <?php endif; ?>

        <form method="post" action="signup.php">
            <fieldset>
                <legend>Date cont nou</legend>

                <label for="new_user">Nume de utilizator: *</label><br>
                <input type="text" id="new_user" name="new_user" size="30" maxlength="20"
                       value="<?= h($old['username'] ?? '') ?>" required
                       title="Între 3 și 20 caractere"><br><br>

                <label for="new_pass">Parolă: * (min. 6 caractere)</label><br>
                <input type="password" id="new_pass" name="new_pass" size="30" maxlength="50" required><br><br>

                <label for="email">Adresă email:</label><br>
                <input type="email" id="email" name="email" size="30" maxlength="100"
                       value="<?= h($old['email'] ?? '') ?>"><br><br>

                <label for="age">Vârstă (ani): *</label><br>
                <input type="number" id="age" name="age" min="10" max="99"
                       value="<?= h((string)($old['age'] ?? 18)) ?>" required><br><br>

                <strong>Alege facțiunea: *</strong><br>
                <input type="radio" name="faction" value="rosu"
                       <?= (($old['faction'] ?? 'rosu') === 'rosu')    ? 'checked' : '' ?>> Imperiul Roșu<br>
                <input type="radio" name="faction" value="albastru"
                       <?= (($old['faction'] ?? '') === 'albastru') ? 'checked' : '' ?>> Imperiul Albastru<br>
                <input type="radio" name="faction" value="verde"
                       <?= (($old['faction'] ?? '') === 'verde')    ? 'checked' : '' ?>> Imperiul Verde<br><br>

                <label for="rank">Titlu de pornire:</label><br>
                <select name="rank" id="rank">
                    <option value="taran"   <?= (($old['rank'] ?? '') === 'taran')   ? 'selected' : '' ?>>Țăran</option>
                    <option value="scutier" <?= (($old['rank'] ?? 'scutier') === 'scutier') ? 'selected' : '' ?>>Scutier</option>
                    <option value="cavaler" <?= (($old['rank'] ?? '') === 'cavaler') ? 'selected' : '' ?>>Cavaler</option>
                </select><br><br>

                <p>Regulament:</p>
                <textarea name="rules" readonly cols="40" rows="3">Este strict interzisă folosirea programelor de trișat sau a dicționarelor în timpul meciurilor!</textarea><br><br>

                <input type="checkbox" name="terms" id="terms" value="1">
                <label for="terms">Sunt de acord cu regulamentul.</label><br><br>

                <input type="submit" value="Creează Contul">
                <input type="reset"  value="Șterge datele">
            </fieldset>
        </form>

        <br><a href="login.php">Ai deja cont? Autentifică-te!</a>
    </div>

    <footer style="margin-left:270px; padding:10px; text-align:center;">
        <p>Quizztador &copy; 2024</p>
    </footer>
</body>
</html>
