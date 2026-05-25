<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db_pdo.php';

requireLogin();

header('Content-Type: application/xml; charset=utf-8');

$userId = (int)$_SESSION['user_id'];
$page   = max(1, (int)($_GET['page'] ?? 1));
$k      = max(1, (int)($_GET['k'] ?? 5));
$offset = ($page - 1) * $k;

function xmlEscape(string $s): string {
    return htmlspecialchars($s, ENT_XML1, 'UTF-8');
}

try {
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM battles WHERE user_id = ?");
    $stmtTotal->execute([$userId]);
    $total = (int)$stmtTotal->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT id, opponent, opponent_faction, territory, question_domain,
                score_user, score_opponent, result, xp_gained, battle_date
         FROM battles
         WHERE user_id = ?
         ORDER BY battle_date DESC
         LIMIT {$k} OFFSET {$offset}"
    );
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<battles total="' . $total . '" page="' . $page . '" k="' . $k . '">';
    foreach ($rows as $r) {
        echo '<battle>';
        echo '<id>'               . (int)$r['id']                        . '</id>';
        echo '<opponent>'         . xmlEscape($r['opponent'])             . '</opponent>';
        echo '<opponent_faction>' . xmlEscape($r['opponent_faction'])     . '</opponent_faction>';
        echo '<territory>'        . xmlEscape($r['territory'])            . '</territory>';
        echo '<question_domain>'  . xmlEscape($r['question_domain'])      . '</question_domain>';
        echo '<score_user>'       . (int)$r['score_user']                 . '</score_user>';
        echo '<score_opponent>'   . (int)$r['score_opponent']             . '</score_opponent>';
        echo '<result>'           . xmlEscape($r['result'])               . '</result>';
        echo '<xp_gained>'        . (int)$r['xp_gained']                  . '</xp_gained>';
        echo '<battle_date>'      . xmlEscape($r['battle_date'])          . '</battle_date>';
        echo '</battle>';
    }
    echo '</battles>';
} catch (PDOException $e) {
    echo '<error>Eroare la baza de date.</error>';
}
