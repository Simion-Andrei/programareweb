<?php
// Run this ONCE to create the database and tables.
// Open: http://server/LAB2/setup.php
// Delete or restrict access to this file after setup!

$dbPath = __DIR__ . '/data/quizztador.db';

if (!is_dir(__DIR__ . '/uploads')) {
    mkdir(__DIR__ . '/uploads', 0775, true);
}

try {
    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
} catch (PDOException $e) {
    die('Conexiune esuata: ' . htmlspecialchars($e->getMessage()));
}

// Create tables
$pdo->exec("
DROP TABLE IF EXISTS battles;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    username        TEXT UNIQUE NOT NULL,
    password_hash   TEXT NOT NULL,
    email           TEXT DEFAULT '',
    role            TEXT DEFAULT 'player' CHECK(role IN ('player','admin')),
    faction         TEXT DEFAULT 'rosu',
    rank_title      TEXT DEFAULT 'scutier',
    profile_picture TEXT DEFAULT NULL,
    remember_token  TEXT DEFAULT NULL,
    token_expiry    DATETIME DEFAULT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE battles (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id          INTEGER NOT NULL,
    opponent         TEXT NOT NULL,
    opponent_faction TEXT,
    territory        TEXT,
    question_domain  TEXT,
    score_user       INTEGER DEFAULT 0,
    score_opponent   INTEGER DEFAULT 0,
    result           TEXT NOT NULL CHECK(result IN ('victorie','infrangere','remiza')),
    xp_gained        INTEGER DEFAULT 0,
    battle_date      TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
");

// Seed users
$users = [
    ['admin',   'admin123',  'admin@quizztador.ro', 'admin',  'albastru', 'cavaler'],
    ['player1', 'player123', 'player1@example.ro',  'player', 'rosu',     'scutier'],
    ['player2', 'player456', 'player2@example.ro',  'player', 'verde',    'taran'],
];
$ins = $pdo->prepare(
    "INSERT OR IGNORE INTO users (username, password_hash, email, role, faction, rank_title)
     VALUES (?, ?, ?, ?, ?, ?)"
);
foreach ($users as [$u, $p, $e, $r, $f, $rk]) {
    $ins->execute([$u, password_hash($p, PASSWORD_DEFAULT), $e, $r, $f, $rk]);
}

// Seed battle history for player1
$row = $pdo->query("SELECT id FROM users WHERE username='player1' LIMIT 1")->fetch();
$uid = $row['id'] ?? null;

if ($uid) {
    $battles = [
        [$uid, 'player2',   'Hoarda Verde',     'Moldova',        'Istorie',       5, 3, 'victorie',    150, '2024-03-15'],
        [$uid, 'player3',   'Alianta Albastra', 'Transilvania',   'Stiinte',       2, 5, 'infrangere',   10, '2024-03-12'],
        [$uid, 'player3',   'Hoarda Verde',     'Muntenia',       'Literatura',    4, 4, 'remiza',        50, '2024-03-10'],
        [$uid, 'Dragonul',  'Imperiul Rosu',    'Dobrogea',       'Geografie',     6, 2, 'victorie',    200, '2024-03-08'],
        [$uid, 'Vulturul',  'Imperiul Albastru','Oltenia',        'Matematica',    3, 5, 'infrangere',   20, '2024-03-05'],
        [$uid, 'player4',   'Hoarda Verde',     'Banat',          'Istorie',       5, 5, 'remiza',        60, '2024-03-03'],
        [$uid, 'Lupul Gri', 'Alianta Albastra', 'Crisana',        'Stiinte',       7, 1, 'victorie',    250, '2024-02-28'],
        [$uid, 'player2',   'Hoarda Verde',     'Maramures',      'Literatura',    4, 3, 'victorie',    130, '2024-02-25'],
        [$uid, 'Corbul',    'Imperiul Rosu',    'Bucovina',       'Geografie',     2, 6, 'infrangere',   15, '2024-02-22'],
        [$uid, 'player5',   'Imperiul Albastru','Moldova',        'Matematica',    5, 5, 'remiza',        55, '2024-02-20'],
        [$uid, 'Soimul',    'Hoarda Verde',     'Transilvania',   'Istorie',       8, 0, 'victorie',    300, '2024-02-18'],
        [$uid, 'player3',   'Alianta Albastra', 'Muntenia',       'Stiinte',       3, 4, 'infrangere',   25, '2024-02-15'],
    ];
    $bi = $pdo->prepare(
        "INSERT INTO battles
         (user_id,opponent,opponent_faction,territory,question_domain,score_user,score_opponent,result,xp_gained,battle_date)
         VALUES (?,?,?,?,?,?,?,?,?,?)"
    );
    foreach ($battles as $b) {
        $bi->execute($b);
    }
}

echo '<p style="font-family:sans-serif;color:green;padding:20px;">Setup complet! Sterge sau redenumeste acest fisier.</p>';
echo '<p style="font-family:sans-serif;padding:0 20px;"><a href="login.php">Mergi la login &rarr;</a></p>';
