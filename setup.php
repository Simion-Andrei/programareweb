<?php
// Run this ONCE to create the database and tables.
// Open: http://localhost/LAB2/setup.php
// Delete or restrict access to this file after setup!

define('DB_HOST', 'localhost');
define('DB_USER', 'quizz');
define('DB_PASS', 'quizz123');
define('DB_NAME', 'quizztador');

// 1. Create database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($conn->connect_error) {
    die('Conexiune esuata: ' . $conn->connect_error);
}
$conn->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db(DB_NAME);

// 2. Create tables
$conn->multi_query("
DROP TABLE IF EXISTS battles;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(50)  UNIQUE NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    email           VARCHAR(100) DEFAULT '',
    role            ENUM('player','admin') DEFAULT 'player',
    faction         VARCHAR(20)  DEFAULT 'rosu',
    rank_title      VARCHAR(20)  DEFAULT 'scutier',
    profile_picture VARCHAR(255) DEFAULT NULL,
    remember_token  VARCHAR(64)  DEFAULT NULL,
    token_expiry    DATETIME     DEFAULT NULL,
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE battles (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,
    opponent         VARCHAR(50)  NOT NULL,
    opponent_faction VARCHAR(20),
    territory        VARCHAR(50),
    question_domain  VARCHAR(50),
    score_user       INT DEFAULT 0,
    score_opponent   INT DEFAULT 0,
    result           ENUM('victorie','infrangere','remiza') NOT NULL,
    xp_gained        INT DEFAULT 0,
    battle_date      DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
");
// flush multi_query results
while ($conn->more_results()) { $conn->next_result(); }

// 3. Seed users
$users = [
    ['admin',   'admin123',   'admin@quizztador.ro',  'admin',  'albastru', 'cavaler'],
    ['player1', 'player123',  'player1@example.ro',  'player', 'rosu',     'scutier'],
    ['player2', 'player456',  'player2@example.ro',  'player', 'verde',    'taran'],
];
$ins = $conn->prepare(
    "INSERT IGNORE INTO users (username, password_hash, email, role, faction, rank_title)
     VALUES (?, ?, ?, ?, ?, ?)"
);
foreach ($users as [$u, $p, $e, $r, $f, $rk]) {
    $hash = password_hash($p, PASSWORD_DEFAULT);
    $ins->bind_param('ssssss', $u, $hash, $e, $r, $f, $rk);
    $ins->execute();
}

// 4. Seed battle history for player1 (id depends on insert order — fetch it)
$res    = $conn->query("SELECT id FROM users WHERE username='player1' LIMIT 1");
$player = $res->fetch_assoc();
$uid    = $player['id'] ?? null;

if ($uid) {
    $battles = [
        [$uid, 'player2', 'Hoarda Verde',   'Moldova',     'Istorie',    5, 3, 'victorie',  150, '2023-11-12'],
        [$uid, 'player3', 'Alianta Albastra','Transilvania','Stiinte',    2, 5, 'infrangere', 10, '2023-11-10'],
        [$uid, 'player3', 'Hoarda Verde',   'Muntenia',    'Literatura', 4, 4, 'remiza',      50, '2023-11-08'],
    ];
    $bi = $conn->prepare(
        "INSERT INTO battles
         (user_id,opponent,opponent_faction,territory,question_domain,score_user,score_opponent,result,xp_gained,battle_date)
         VALUES (?,?,?,?,?,?,?,?,?,?)"
    );
    foreach ($battles as [$buid, $opp, $ofac, $ter, $dom, $su, $so, $res, $xp, $dt]) {
        $bi->bind_param('issssiisss', $buid, $opp, $ofac, $ter, $dom, $su, $so, $res, $xp, $dt);
        $bi->execute();
    }
}

$conn->close();