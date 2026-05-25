<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db_pdo.php';

requireLogin();

header('Content-Type: application/json; charset=utf-8');

$userId = (int)$_SESSION['user_id'];
$id     = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID invalid.']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT id, opponent, opponent_faction, territory, question_domain,
                score_user, score_opponent, result, xp_gained, battle_date
         FROM battles
         WHERE id = ? AND user_id = ?
         LIMIT 1"
    );
    $stmt->execute([$id, $userId]);
    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Bătălia nu a fost găsită.']);
        exit;
    }

    echo json_encode($row);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Eroare la baza de date.']);
}
