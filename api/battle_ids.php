<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db_pdo.php';

requireLogin();

header('Content-Type: application/json; charset=utf-8');

$userId = (int)$_SESSION['user_id'];

try {
    $stmt = $pdo->prepare(
        "SELECT id, opponent, battle_date
         FROM battles
         WHERE user_id = ?
         ORDER BY battle_date DESC"
    );
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    $result = array_map(fn($r) => [
        'id'    => (int)$r['id'],
        'label' => '#' . $r['id'] . ' vs ' . $r['opponent'] . ' (' . $r['battle_date'] . ')',
    ], $rows);

    echo json_encode($result);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Eroare la baza de date.']);
}
