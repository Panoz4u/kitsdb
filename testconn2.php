<?php
$host = 'localhost';  // Su cPanel è quasi sempre localhost
$dbname = 'YOUR_DB_NAME_HERE';
$user = 'YOUR_DB_NAME_HERE';  // ← CORRETTO (senza la 'k')
$pass = 'YOUR_DB_PASSWORD_HERE';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connessione riuscita!";
} catch (PDOException $e) {
    echo "❌ Connessione fallita: " . $e->getMessage();
}
?>