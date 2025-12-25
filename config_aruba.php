<?php
// config_aruba.php - Alternative Aruba hosting configuration
require_once __DIR__ . '/env_loader.php';

// Load environment variables
loadEnv();

// Database configuration from environment variables (Aruba)
define('DB_HOST', env('DB_ARUBA_HOST'));
define('DB_PORT', env('DB_ARUBA_PORT', '3306'));
define('DB_NAME', env('DB_ARUBA_NAME'));
define('DB_USER', env('DB_ARUBA_USER'));
define('DB_PASS', env('DB_ARUBA_PASS'));

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