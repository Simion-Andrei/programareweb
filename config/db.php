<?php
// PDO SQLite — conexiune principală (users + battles)
$dbPath = __DIR__ . '/../data/quizztador.db';

try {
    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
} catch (PDOException $e) {
    die('Eroare conexiune SQLite: ' . htmlspecialchars($e->getMessage()));
}
