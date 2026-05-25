<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db_pdo.php';

requireLogin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodă nepermisă.']);
    exit;
}

$userId          = (int)$_SESSION['user_id'];
$id              = (int)($_POST['id'] ?? 0);
$opponent        = trim($_POST['opponent']        ?? '');
$opponentFaction = trim($_POST['opponent_faction'] ?? '');
$territory       = trim($_POST['territory']       ?? '');
$questionDomain  = trim($_POST['question_domain'] ?? '');
$scoreUser       = (int)($_POST['score_user']     ?? 0);
$scoreOpponent   = (int)($_POST['score_opponent'] ?? 0);
$result          = trim($_POST['result']          ?? '');

if ($id <= 0 || $opponent === '' || $result === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Date incomplete.']);
    exit;
}

$allowed = ['victorie', 'infrangere', 'remiza'];
if (!in_array($result, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Rezultat invalid.']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "UPDATE battles
         SET opponent = ?, opponent_faction = ?, territory = ?,
             question_domain = ?, score_user = ?, score_opponent = ?, result = ?
         WHERE id = ? AND user_id = ?"
    );
    $stmt->execute([
        $opponent, $opponentFaction, $territory,
        $questionDomain, $scoreUser, $scoreOpponent, $result,
        $id, $userId,
    ]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Bătălia nu a fost găsită sau nu îți aparține.']);
        exit;
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Eroare la baza de date.']);
}
