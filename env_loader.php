<?php
/**
 * Simple .env file loader
 * Loads environment variables from .env file
 */
function loadEnv($path = __DIR__ . '/.env') {
    if (!file_exists($path)) {
        throw new Exception('.env file not found. Please copy .env.example to .env and configure your credentials.');
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            $value = trim($value, '"\'');

            // Set as environment variable and in $_ENV
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

/**
 * Get environment variable value
 * @param string $key Variable name
 * @param mixed $default Default value if not found
 * @return mixed
 */
function env($key, $default = null) {
    if (array_key_exists($key, $_ENV)) {
        return $_ENV[$key];
    }

    $value = getenv($key);
    return $value !== false ? $value : $default;
}
