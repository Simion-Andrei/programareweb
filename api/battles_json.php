<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db_pdo.php';

requireLogin();

header('Content-Type: application/json; charset=utf-8');

$userId = (int)$_SESSION['user_id'];
$page   = max(1, (int)($_GET['page'] ?? 1));
$k      = max(1, (int)($_GET['k'] ?? 5));
$offset = ($page - 1) * $k;

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

    echo json_encode([
        'data'  => $rows,
        'total' => $total,
        'page'  => $page,
        'k'     => $k,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Eroare la baza de date.']);
}
