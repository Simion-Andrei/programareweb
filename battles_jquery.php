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
    <title>Quizztador - Batalii (jQuery)</title>
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
        <center><h1>&#9876; Bataliile Mele — jQuery AJAX (Cerința 3)</h1></center>
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
        <a href="battles_jquery.php" class="active">3. jQuery</a>
        <a href="battles_paged.php">4. Server-side</a>
        <a href="battles_edit.php">5. Edit JS</a>
        <a href="battles_edit_jquery.php">6. Edit jQuery</a>
    </div>

    <div class="section-box">
        <h2>Afișare paginată — jQuery AJAX + JSON</h2>
        <p>Înregistrările sunt preluate câte <strong>5</strong> prin apel AJAX jQuery care returnează date în format JSON.</p>

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
            <button class="btn-nav" id="btn-prev">&#8592; Previous 5</button>
            <button class="btn-nav" id="btn-next">Next 5 &#8594;</button>
        </div>
    </div>

    <footer>
        <center><p>Quizztador &copy; 2024 &mdash; Conectat ca: <strong><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></strong></p></center>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
    var currentPage = 1;
    var K = 5;
    var totalRecords = 0;

    function resultClass(result) {
        if (result === 'victorie')   return 'result-victorie';
        if (result === 'infrangere') return 'result-infrangere';
        return 'result-remiza';
    }

    function loadPage(page) {
        $('#error-box').hide();

        $.ajax({
            url: 'api/battles_json.php',
            data: { page: page, k: K },
            dataType: 'json',
            success: function(data) {
                if (data.error) {
                    $('#error-box').text(data.error).show();
                    return;
                }

                totalRecords = data.total;
                currentPage  = data.page;

                var rows = '';
                if (data.data.length === 0) {
                    rows = '<tr><td colspan="9" style="text-align:center">Nicio bătălie înregistrată.</td></tr>';
                } else {
                    $.each(data.data, function(i, b) {
                        rows += '<tr>';
                        rows += '<td>' + $('<div>').text(String(b.id)).html() + '</td>';
                        rows += '<td>' + $('<div>').text(b.opponent).html() + '</td>';
                        rows += '<td>' + $('<div>').text(b.opponent_faction).html() + '</td>';
                        rows += '<td>' + $('<div>').text(b.territory).html() + '</td>';
                        rows += '<td>' + $('<div>').text(b.question_domain).html() + '</td>';
                        rows += '<td>' + b.score_user + ' - ' + b.score_opponent + '</td>';
                        rows += '<td class="' + resultClass(b.result) + '">' + $('<div>').text(b.result).html() + '</td>';
                        rows += '<td>' + b.xp_gained + '</td>';
                        rows += '<td>' + $('<div>').text(b.battle_date).html() + '</td>';
                        rows += '</tr>';
                    });
                }
                $('#battles-tbody').html(rows);

                var totalPages = Math.ceil(totalRecords / K);
                $('#page-info').text('Pagina ' + currentPage + ' din ' + totalPages + ' (' + totalRecords + ' bătălii total)');

                $('#btn-prev').prop('disabled', currentPage <= 1);
                $('#btn-next').prop('disabled', currentPage * K >= totalRecords);
            },
            error: function(xhr, status, err) {
                if (status === 'error' && xhr.status === 0) {
                    $('#error-box').text('Eroare de rețea. Verifică conexiunea la internet.').show();
                } else {
                    $('#error-box').text('Eroare server (HTTP ' + xhr.status + '). Încearcă din nou.').show();
                }
            }
        });
    }

    $(document).ready(function() {
        $('#btn-prev').prop('disabled', true);

        $('#btn-prev').on('click', function() {
            if (currentPage > 1) loadPage(currentPage - 1);
        });

        $('#btn-next').on('click', function() {
            loadPage(currentPage + 1);
        });

        loadPage(1);
    });
    </script>
</body>
</html>
