<?php
// MySQLi connection — used for auth queries (login, signup, remember-me)
define('DB_HOST', 'localhost');
define('DB_USER', 'quizz');
define('DB_PASS', 'quizz123');
define('DB_NAME', 'quizztador');

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    die('Eroare conexiune MySQLi: ' . htmlspecialchars($mysqli->connect_error));
}
$mysqli->set_charset('utf8mb4');
