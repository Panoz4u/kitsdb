<?php
$host = 'localhost';  // Su cPanel è quasi sempre localhost
$dbname = 'jznbfzwq_kitsdb';
$user = 'jznbfzwq_kitsdb';  // ← CORRETTO (senza la 'k')
$pass = 'LaMaglia+bella!25|db';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connessione riuscita!";
} catch (PDOException $e) {
    echo "❌ Connessione fallita: " . $e->getMessage();
}
?>