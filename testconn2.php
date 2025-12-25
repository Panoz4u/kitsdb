<?php
require_once 'env_loader.php';
loadEnv();

$host = env('DB_HOST', 'localhost');
$dbname = env('DB_NAME');
$user = env('DB_USER');
$pass = env('DB_PASS');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connessione riuscita!";
} catch (PDOException $e) {
    echo "❌ Connessione fallita: " . $e->getMessage();
}
?>