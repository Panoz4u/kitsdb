<?php
// config.php
define('DB_HOST', '62.149.150.238');
define('DB_PORT', '3306');
define('DB_NAME', 'Sql889464_5');  // il nome che hai creato
define('DB_USER', 'Sql889464');          // l’utente MySQL
define('DB_PASS', '3l6kc6k66d');     // la password

function getDb() {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8';
    try {
        $pdo = new PDO(
            $dsn,
            DB_USER,
            DB_PASS,
            array(
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            )
        );
        return $pdo;
    } catch (PDOException $e) {
        die('DB connection failed: ' . $e->getMessage());
    }
}