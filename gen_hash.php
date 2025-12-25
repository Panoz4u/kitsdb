<?php
// gen_hash.php – genera un hash bcrypt con crypt() e openssl_random_pseudo_bytes()
require_once 'env_loader.php';
loadEnv();

$password = env('ADMIN_PASSWORD');

if (!$password) {
    die("Error: ADMIN_PASSWORD not configured in .env file\n");
}

// 1) Imposta il costo (tra 4 e 31; 10 è un buon compromesso)
$cost = 10;

// 2) Genera 16 byte casuali per il salt
if (function_exists('openssl_random_pseudo_bytes')) {
    $raw_salt = openssl_random_pseudo_bytes(16);
} else {
    // fallback (meno sicuro) se non c’è OpenSSL
    $raw_salt = '';
    for ($i = 0; $i < 16; $i++) {
        $raw_salt .= chr(mt_rand(0, 255));
    }
}

// 3) Base64-encode e adatta al formato Blowfish (22 caratteri validi)
$salt = substr(strtr(base64_encode($raw_salt), '+', '.'), 0, 22);

// 4) Prepara il prefisso `$2y$` + costo + `$` + salt
$blowfish_salt = sprintf('$2y$%02d$%s', $cost, $salt);

// 5) Genera l’hash
$hash = crypt($password, $blowfish_salt);

echo $hash;