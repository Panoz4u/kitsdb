<?php
// config.php
define('DB_HOST', 'hostingssd79.netsons.net');
define('DB_PORT', '3306');
define('DB_NAME', 'jznbfkvzq_kitsdb');  // il nome che hai creato
define('DB_USER', 'jznbfkvzq_kitsdb');          // l’utente MySQL
define('DB_PASS', 'LaMaglia+bella!25|db');     // la password

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