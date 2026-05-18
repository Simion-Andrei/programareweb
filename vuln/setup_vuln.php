<?php
// Rulează o singură dată pentru a crea tabelele necesare demo-urilor de vulnerabilități
require_once '../config/db.php';

// Tabela cu parole în PLAIN TEXT (intentionat insecure pentru demo SQLi)
$mysqli->query("CREATE TABLE IF NOT EXISTS vuln_users (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(50) NOT NULL,
    role     VARCHAR(10) DEFAULT 'player'
)");
$mysqli->query("DELETE FROM vuln_users");
$mysqli->query("INSERT INTO vuln_users (username, password, role) VALUES
    ('admin',   'admin123',  'admin'),
    ('player1', 'player123', 'player'),
    ('player2', 'player456', 'player')");

// Tabela pentru XSS Stored (guestbook)
$mysqli->query("CREATE TABLE IF NOT EXISTS guestbook (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    message    TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$mysqli->query("DELETE FROM guestbook");

echo '<h2 style="font-family:sans-serif;color:green">&#10003; Vuln setup complet!</h2>';
echo '<a href="index.php" style="font-family:sans-serif">Mergi la index vulnurabilități</a>';
