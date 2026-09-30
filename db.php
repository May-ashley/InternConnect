<?php

function connect() {
    $configFile = __DIR__ . '/config.php';

    if (file_exists($configFile)) {
        $config = require $configFile;
    } else {
        $config = [
            'host' => getenv('DB_HOST') ?: 'localhost',
            'user' => getenv('DB_USER') ?: 'root',
            'pass' => getenv('DB_PASS') ?: '',
            'name' => getenv('DB_NAME') ?: 'internconnect',
        ];
    }

    $conn = mysqli_connect(
        $config['host'],
        $config['user'],
        $config['pass'],
        $config['name']
    );

    if (!$conn) {
        die("Failed to connect to database.");
    }

    mysqli_set_charset($conn, "utf8mb4");

    return $conn;
}

?>
