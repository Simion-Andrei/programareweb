<?php
require_once 'includes/auth.php';
require_once 'config/db_pdo.php';

checkRememberToken($pdo);
requireLogin();

$user = $_SESSION;
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style-responsive.css">
    <title>Quizztador - Editare Batalii (JS)</title>
    <style>
        .section-box { max-width: 700px; margin: 20px auto; background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .form-group  { margin-bottom: 14px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 4px; color: #2c3e50; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; font-size: 14px; }
        .form-group select { background: #fff; }
        .btn-save  { padding: 10px 24px; background: #27ae60; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 15px; }
        .btn-save:disabled { background: #95a5a6; cursor: default; }
        .error-box   { background: #fdd; border: 1px solid #c00; padding: 10px; border-radius: 6px; margin: 10px 0; display: none; }
        .success-box { background: #dfd; border: 1px solid #0a0; padding: 10px; border-radius: 6px; margin: 10px 0; display: none; }
        .sub-nav  { max-width: 700px; margin: 10px auto; display: flex; gap: 8px; flex-wrap: wrap; }
        .sub-nav a { padding: 6px 14px; background: #e74c3c; color: #fff; border-radius: 5px; text-decoration: none; font-size: 13px; }
        .sub-nav a.active { background: #2c3e50; }
        fieldset { border: 1px solid #ddd; border-radius: 8px; padding: 16px; }
        legend   { font-weight: bold; color: #2c3e50; padding: 0 8px; }
    </style>
</head>
<body>
    <header>
        <center><h1>&#9876; Editare Bătălie — Vanilla JS (Cerința 5)</h1></center>
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
        <a href="battles_paged.php">4. Server-side</a>
        <a href="battles_edit.php" class="active">5. Edit JS</a>
        <a href="battles_edit_jquery.php">6. Edit jQuery</a>
    </div>

    <div class="section-box">
        <h2>Editare bătălie — Vanilla AJAX</h2>
        <p>Selectează o bătălie din lista de mai jos, modifică câmpurile și apasă <strong>Save</strong>.</p>

        <div id="error-box"   class="error-box"></div>
        <div id="success-box" class="success-box"></div>

        <div class="form-group">
            <label for="battle-select">Selectează bătălia:</label>
            <select id="battle-select">
                <option value="">-- Se încarcă --</option>
            </select>
        </div>

        <fieldset id="edit-fields" style="display:none">
            <legend>Detalii bătălie</legend>

            <input type="hidden" id="field-id">

            <div class="form-group">
                <label for="field-opponent">Adversar:</label>
                <input type="text" id="field-opponent">
            </div>
            <div class="form-group">
                <label for="field-opponent-faction">Facțiunea adversarului:</label>
                <input type="text" id="field-opponent-faction">
            </div>
            <div class="form-group">
                <label for="field-territory">Teritoriu:</label>
                <input type="text" id="field-territory">
            </div>
            <div class="form-group">
                <label for="field-domain">Domeniu întrebări:</label>
                <input type="text" id="field-domain">
            </div>
            <div class="form-group">
                <label for="field-score-user">Scor propriu:</label>
                <input type="number" id="field-score-user" min="0">
            </div>
            <div class="form-group">
                <label for="field-score-opp">Scor adversar:</label>
                <input type="number" id="field-score-opp" min="0">
            </div>
            <div class="form-group">
                <label for="field-result">Rezultat:</label>
                <select id="field-result">
                    <option value="victorie">Victorie</option>
                    <option value="infrangere">Înfrângere</option>
                    <option value="remiza">Remiză</option>
                </select>
            </div>

            <button class="btn-save" id="save-btn" disabled>&#128190; Save</button>
        </fieldset>
    </div>

    <footer>
        <center><p>Quizztador &copy; 2024 &mdash; Conectat ca: <strong><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></strong></p></center>
    </footer>

    <script>
    var isDirty = false;

    function showError(msg) {
        var box = document.getElementById('error-box');
        box.textContent = msg;
        box.style.display = 'block';
        document.getElementById('success-box').style.display = 'none';
    }

    function showSuccess(msg) {
        var box = document.getElementById('success-box');
        box.textContent = msg;
        box.style.display = 'block';
        document.getElementById('error-box').style.display = 'none';
    }

    function hideMessages() {
        document.getElementById('error-box').style.display   = 'none';
        document.getElementById('success-box').style.display = 'none';
    }

    function setDirty(dirty) {
        isDirty = dirty;
        document.getElementById('save-btn').disabled = !dirty;
    }

    function loadBattleIds() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'api/battle_ids.php', true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            if (xhr.status === 0) { showError('Eroare de rețea. Verifică conexiunea la internet.'); return; }
            if (xhr.status !== 200) { showError('Eroare server (HTTP ' + xhr.status + ').'); return; }

            var data;
            try { data = JSON.parse(xhr.responseText); } catch(e) { showError('Răspuns invalid de la server.'); return; }
            if (data.error) { showError(data.error); return; }

            var sel = document.getElementById('battle-select');
            sel.innerHTML = '<option value="">-- Selectează o bătălie --</option>';
            for (var i = 0; i < data.length; i++) {
                var opt = document.createElement('option');
                opt.value       = data[i].id;
                opt.textContent = data[i].label;
                sel.appendChild(opt);
            }
        };
        xhr.onerror = function() { showError('Eroare de rețea. Verifică conexiunea la internet.'); };
        xhr.send();
    }

    function loadBattle(id) {
        hideMessages();
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'api/battle_get.php?id=' + id, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            if (xhr.status === 0) { showError('Eroare de rețea. Verifică conexiunea la internet.'); return; }
            if (xhr.status !== 200) { showError('Eroare server (HTTP ' + xhr.status + ').'); return; }

            var b;
            try { b = JSON.parse(xhr.responseText); } catch(e) { showError('Răspuns invalid de la server.'); return; }
            if (b.error) { showError(b.error); return; }

            document.getElementById('field-id').value             = b.id;
            document.getElementById('field-opponent').value       = b.opponent;
            document.getElementById('field-opponent-faction').value = b.opponent_faction;
            document.getElementById('field-territory').value      = b.territory;
            document.getElementById('field-domain').value         = b.question_domain;
            document.getElementById('field-score-user').value     = b.score_user;
            document.getElementById('field-score-opp').value      = b.score_opponent;
            document.getElementById('field-result').value         = b.result;

            document.getElementById('edit-fields').style.display = 'block';
            setDirty(false);
        };
        xhr.onerror = function() { showError('Eroare de rețea. Verifică conexiunea la internet.'); };
        xhr.send();
    }

    function saveBattle(callback) {
        var id              = document.getElementById('field-id').value;
        var opponent        = document.getElementById('field-opponent').value;
        var opponentFaction = document.getElementById('field-opponent-faction').value;
        var territory       = document.getElementById('field-territory').value;
        var domain          = document.getElementById('field-domain').value;
        var scoreUser       = document.getElementById('field-score-user').value;
        var scoreOpp        = document.getElementById('field-score-opp').value;
        var result          = document.getElementById('field-result').value;

        var body = 'id=' + encodeURIComponent(id)
                 + '&opponent=' + encodeURIComponent(opponent)
                 + '&opponent_faction=' + encodeURIComponent(opponentFaction)
                 + '&territory=' + encodeURIComponent(territory)
                 + '&question_domain=' + encodeURIComponent(domain)
                 + '&score_user=' + encodeURIComponent(scoreUser)
                 + '&score_opponent=' + encodeURIComponent(scoreOpp)
                 + '&result=' + encodeURIComponent(result);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'api/battle_save.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            if (xhr.status === 0) { showError('Eroare de rețea. Verifică conexiunea la internet.'); if (callback) callback(false); return; }

            var resp;
            try { resp = JSON.parse(xhr.responseText); } catch(e) { showError('Răspuns invalid de la server.'); if (callback) callback(false); return; }

            if (resp.error) { showError(resp.error); if (callback) callback(false); return; }

            showSuccess('Bătălia a fost salvată cu succes!');
            setDirty(false);
            if (callback) callback(true);
        };
        xhr.onerror = function() { showError('Eroare de rețea. Verifică conexiunea la internet.'); if (callback) callback(false); };
        xhr.send(body);
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadBattleIds();

        document.getElementById('battle-select').addEventListener('change', function() {
            var newId = this.value;
            if (!newId) return;

            if (isDirty) {
                var answer = confirm('Ai modificări nesalvate. Dorești să salvezi înainte să continui?');
                if (answer) {
                    saveBattle(function() { loadBattle(newId); });
                    return;
                }
            }
            loadBattle(newId);
        });

        var editableFields = ['field-opponent', 'field-opponent-faction', 'field-territory',
                              'field-domain', 'field-score-user', 'field-score-opp', 'field-result'];
        editableFields.forEach(function(fid) {
            document.getElementById(fid).addEventListener('input',  function() { setDirty(true); });
            document.getElementById(fid).addEventListener('change', function() { setDirty(true); });
        });

        document.getElementById('save-btn').addEventListener('click', function() {
            saveBattle(null);
        });
    });
    </script>
</body>
</html>
