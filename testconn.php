<?php
$host = 'hostingssd79.netsons.net';
$dbname = 'jznbfkvzq_kitsdb';
$user = 'jznbfkvzq_kitsdb2';
$pass = 'z_h[1yym0o8^';  // ⚠️ Copia esattamente quella appena impostata

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connessione riuscita!";
} catch (PDOException $e) {
    echo "❌ Connessione fallita: " . $e->getMessage();
}
?>