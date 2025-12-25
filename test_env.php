<?php
/**
 * Test script to verify .env loading
 * This script doesn't require database connection
 */
require_once 'env_loader.php';

echo "Testing .env file loading...\n\n";

try {
    loadEnv();
    echo "✓ .env file loaded successfully!\n\n";

    echo "Environment variables loaded:\n";
    echo "----------------------------\n";

    $env_vars = [
        'DB_HOST',
        'DB_PORT',
        'DB_NAME',
        'DB_USER',
        'DB_PASS',
        'DB_ARUBA_HOST',
        'DB_ARUBA_PORT',
        'DB_ARUBA_NAME',
        'DB_ARUBA_USER',
        'DB_ARUBA_PASS',
        'ADMIN_USERNAME',
        'ADMIN_PASSWORD'
    ];

    foreach ($env_vars as $var) {
        $value = env($var);
        if ($value !== null) {
            // Mask passwords
            if (strpos($var, 'PASS') !== false) {
                $masked = str_repeat('*', min(8, strlen($value)));
                echo "✓ $var = $masked (length: " . strlen($value) . ")\n";
            } else {
                echo "✓ $var = $value\n";
            }
        } else {
            echo "✗ $var = NOT SET\n";
        }
    }

    echo "\n----------------------------\n";
    echo "Configuration test completed!\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
