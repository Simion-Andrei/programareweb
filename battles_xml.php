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
    <title>Quizztador - Batalii (XML)</title>
    <style>
        .section-box   { max-width: 960px; margin: 20px auto; background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .admin-table   { width: 100%; border-collapse: collapse; }
        .admin-table th { background: #2c3e50; color: #fff; padding: 10px; text-align: left; }
        .admin-table td { padding: 10px; border-bottom: 1px solid #ddd; }
        .admin-table tr:hover td { background: #f9f9f9; }
        .result-victorie  { color: #27ae60; font-weight: bold; }
        .result-infrangere{ color: #e74c3c; font-weight: bold; }
        .result-remiza    { color: #f39c12; font-weight: bold; }
        .btn-nav  { padding: 8px 18px; margin: 4px; background: #2c3e50; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
        .btn-nav:disabled { background: #95a5a6; cursor: default; }
        .error-box  { background: #fdd; border: 1px solid #c00; padding: 10px; border-radius: 6px; margin: 10px 0; display: none; }
        .sub-nav  { max-width: 960px; margin: 10px auto; display: flex; gap: 8px; flex-wrap: wrap; }
        .sub-nav a { padding: 6px 14px; background: #e74c3c; color: #fff; border-radius: 5px; text-decoration: none; font-size: 13px; }
        .sub-nav a.active { background: #2c3e50; }
        .page-info { color: #555; font-size: 13px; margin: 8px 0; }
    </style>
</head>
<body>
    <header>
        <center><h1>&#9876; Bataliile Mele — AJAX XML (Cerința 2)</h1></center>
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
        <a href="battles_xml.php" class="active">2. JS + XML</a>
        <a href="battles_jquery.php">3. jQuery</a>
        <a href="battles_paged.php">4. Server-side</a>
        <a href="battles_edit.php">5. Edit JS</a>
        <a href="battles_edit_jquery.php">6. Edit jQuery</a>
    </div>

    <div class="section-box">
        <h2>Afișare paginată — Vanilla JS + AJAX + XML</h2>
        <p>Înregistrările sunt preluate câte <strong>5</strong> prin apel AJAX care returnează date în format XML.</p>

        <div id="error-box" class="error-box"></div>

        <p class="page-info" id="page-info">Se încarcă...</p>

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
            <tbody id="battles-tbody">
                <tr><td colspan="9" style="text-align:center">Se încarcă...</td></tr>
            </tbody>
        </table>

        <div style="margin-top:14px;">
            <button class="btn-nav" id="btn-prev" onclick="changePage(-1)" disabled>&#8592; Previous 5</button>
            <button class="btn-nav" id="btn-next" onclick="changePage(1)">Next 5 &#8594;</button>
        </div>
    </div>

    <footer>
        <center><p>Quizztador &copy; 2024 &mdash; Conectat ca: <strong><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></strong></p></center>
    </footer>

    <script>
    var currentPage = 1;
    var K = 5;
    var totalRecords = 0;

    function showError(msg) {
        var box = document.getElementById('error-box');
        box.textContent = msg;
        box.style.display = 'block';
    }

    function hideError() {
        document.getElementById('error-box').style.display = 'none';
    }

    function getXmlText(node, tag) {
        var el = node.getElementsByTagName(tag)[0];
        return el ? (el.textContent || el.innerText || '') : '';
    }

    function resultClass(result) {
        if (result === 'victorie')   return 'result-victorie';
        if (result === 'infrangere') return 'result-infrangere';
        return 'result-remiza';
    }

    function escHtml(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(str || ''));
        return d.innerHTML;
    }

    function loadPage(page) {
        hideError();
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'api/battles_xml.php?page=' + page + '&k=' + K, true);

        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;

            if (xhr.status === 0) {
                showError('Eroare de rețea. Verifică conexiunea la internet.');
                return;
            }
            if (xhr.status !== 200) {
                showError('Eroare server (HTTP ' + xhr.status + '). Încearcă din nou.');
                return;
            }

            var xml = xhr.responseXML;
            if (!xml) {
                showError('Răspuns XML invalid de la server.');
                return;
            }

            var battlesNode = xml.getElementsByTagName('battles')[0];
            if (!battlesNode) {
                var errNode = xml.getElementsByTagName('error')[0];
                showError(errNode ? errNode.textContent : 'Eroare necunoscută.');
                return;
            }

            totalRecords = parseInt(battlesNode.getAttribute('total'), 10);
            currentPage  = parseInt(battlesNode.getAttribute('page'),  10);

            var battles = xml.getElementsByTagName('battle');
            var tbody   = document.getElementById('battles-tbody');

            if (battles.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align:center">Nicio bătălie înregistrată.</td></tr>';
            } else {
                var html = '';
                for (var i = 0; i < battles.length; i++) {
                    var b      = battles[i];
                    var res    = getXmlText(b, 'result');
                    html += '<tr>';
                    html += '<td>' + escHtml(getXmlText(b, 'id')) + '</td>';
                    html += '<td>' + escHtml(getXmlText(b, 'opponent')) + '</td>';
                    html += '<td>' + escHtml(getXmlText(b, 'opponent_faction')) + '</td>';
                    html += '<td>' + escHtml(getXmlText(b, 'territory')) + '</td>';
                    html += '<td>' + escHtml(getXmlText(b, 'question_domain')) + '</td>';
                    html += '<td>' + escHtml(getXmlText(b, 'score_user')) + ' - ' + escHtml(getXmlText(b, 'score_opponent')) + '</td>';
                    html += '<td class="' + resultClass(res) + '">' + escHtml(res) + '</td>';
                    html += '<td>' + escHtml(getXmlText(b, 'xp_gained')) + '</td>';
                    html += '<td>' + escHtml(getXmlText(b, 'battle_date')) + '</td>';
                    html += '</tr>';
                }
                tbody.innerHTML = html;
            }

            var totalPages = Math.ceil(totalRecords / K);
            document.getElementById('page-info').textContent =
                'Pagina ' + currentPage + ' din ' + totalPages + ' (' + totalRecords + ' bătălii total)';

            document.getElementById('btn-prev').disabled = (currentPage <= 1);
            document.getElementById('btn-next').disabled = (currentPage * K >= totalRecords);
        };

        xhr.onerror = function() {
            showError('Eroare de rețea. Verifică conexiunea la internet.');
        };

        xhr.send();
    }

    function changePage(delta) {
        loadPage(currentPage + delta);
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadPage(1);
    });
    </script>
</body>
</html>
