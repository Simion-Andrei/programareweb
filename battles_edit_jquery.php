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
    <title>Quizztador - Editare Batalii (jQuery)</title>
    <style>
        .section-box { max-width: 700px; margin: 20px auto; background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .form-group  { margin-bottom: 14px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 4px; color: #2c3e50; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; font-size: 14px; }
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
        <center><h1>&#9876; Editare Bătălie — jQuery (Cerința 6)</h1></center>
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
        <a href="battles_edit.php">5. Edit JS</a>
        <a href="battles_edit_jquery.php" class="active">6. Edit jQuery</a>
    </div>

    <div class="section-box">
        <h2>Editare bătălie — jQuery AJAX</h2>
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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
    var isDirty = false;

    function showError(msg) {
        $('#error-box').text(msg).show();
        $('#success-box').hide();
    }

    function showSuccess(msg) {
        $('#success-box').text(msg).show();
        $('#error-box').hide();
    }

    function hideMessages() {
        $('#error-box').hide();
        $('#success-box').hide();
    }

    function setDirty(dirty) {
        isDirty = dirty;
        $('#save-btn').prop('disabled', !dirty);
    }

    function loadBattleIds() {
        $.ajax({
            url: 'api/battle_ids.php',
            dataType: 'json',
            success: function(data) {
                if (data.error) { showError(data.error); return; }
                var sel = $('#battle-select').empty().append('<option value="">-- Selectează o bătălie --</option>');
                $.each(data, function(i, item) {
                    sel.append($('<option>').val(item.id).text(item.label));
                });
            },
            error: function(xhr, status) {
                if (xhr.status === 0) showError('Eroare de rețea. Verifică conexiunea la internet.');
                else showError('Eroare server (HTTP ' + xhr.status + ').');
            }
        });
    }

    function loadBattle(id) {
        hideMessages();
        $.ajax({
            url: 'api/battle_get.php',
            data: { id: id },
            dataType: 'json',
            success: function(b) {
                if (b.error) { showError(b.error); return; }
                $('#field-id').val(b.id);
                $('#field-opponent').val(b.opponent);
                $('#field-opponent-faction').val(b.opponent_faction);
                $('#field-territory').val(b.territory);
                $('#field-domain').val(b.question_domain);
                $('#field-score-user').val(b.score_user);
                $('#field-score-opp').val(b.score_opponent);
                $('#field-result').val(b.result);
                $('#edit-fields').show();
                setDirty(false);
            },
            error: function(xhr, status) {
                if (xhr.status === 0) showError('Eroare de rețea. Verifică conexiunea la internet.');
                else showError('Eroare server (HTTP ' + xhr.status + ').');
            }
        });
    }

    function saveBattle(callback) {
        $.ajax({
            url: 'api/battle_save.php',
            method: 'POST',
            data: {
                id:               $('#field-id').val(),
                opponent:         $('#field-opponent').val(),
                opponent_faction: $('#field-opponent-faction').val(),
                territory:        $('#field-territory').val(),
                question_domain:  $('#field-domain').val(),
                score_user:       $('#field-score-user').val(),
                score_opponent:   $('#field-score-opp').val(),
                result:           $('#field-result').val(),
            },
            dataType: 'json',
            success: function(resp) {
                if (resp.error) { showError(resp.error); if (callback) callback(false); return; }
                showSuccess('Bătălia a fost salvată cu succes!');
                setDirty(false);
                if (callback) callback(true);
            },
            error: function(xhr, status) {
                if (xhr.status === 0) showError('Eroare de rețea. Verifică conexiunea la internet.');
                else showError('Eroare server (HTTP ' + xhr.status + ').');
                if (callback) callback(false);
            }
        });
    }

    $(document).ready(function() {
        loadBattleIds();

        $('#battle-select').on('change', function() {
            var newId = $(this).val();
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

        $('#edit-fields input, #edit-fields select').on('input change', function() {
            setDirty(true);
        });

        $('#save-btn').on('click', function() {
            saveBattle(null);
        });
    });
    </script>
</body>
</html>
