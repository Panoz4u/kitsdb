<?php
// config.php
define('DB_HOST', 'YOUR_ARUBA_HOST_HERE');
define('DB_PORT', '3306');
define('DB_NAME', 'YOUR_ARUBA_DB_NAME_HERE');  // il nome che hai creato
define('DB_USER', 'YOUR_ARUBA_DB_USER_HERE');          // l’utente MySQL
define('DB_PASS', 'YOUR_ARUBA_PASSWORD_HERE');     // la password

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