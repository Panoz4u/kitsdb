<?php
// Script per creare l'utente admin
require_once 'config.php';

$username = 'admin';
$password = 'FMkits!25';

// Genera hash della password
$cost = 10;
if (function_exists('openssl_random_pseudo_bytes')) {
    $raw_salt = openssl_random_pseudo_bytes(16);
} else {
    $raw_salt = '';
    for ($i = 0; $i < 16; $i++) {
        $raw_salt .= chr(mt_rand(0, 255));
    }
}

$salt = substr(strtr(base64_encode($raw_salt), '+', '.'), 0, 22);
$blowfish_salt = sprintf('$2y$%02d$%s', $cost, $salt);
$hash = crypt($password, $blowfish_salt);

echo "Hash generato: " . $hash . "\n\n";

// Inserisci nel database
try {
    $db = getDb();
    
    // Controlla se esiste già
    $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->fetch()) {
        echo "Utente 'admin' esiste già!\n";
    } else {
        // Inserisci nuovo utente
        $stmt = $db->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
        $result = $stmt->execute([$username, $hash, 'admin']);
        
        if ($result) {
            echo "✓ Utente admin creato con successo!\n";
            echo "Username: admin\n";
            echo "Password: FMkits!25\n";
        } else {
            echo "✗ Errore durante la creazione dell'utente\n";
        }
    }
} catch (PDOException $e) {
    echo "Errore database: " . $e->getMessage() . "\n";
}
?>