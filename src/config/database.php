<?php

// Database configuration
define('DB_HOST', 'postgres');
define('DB_PORT', '5432');
define('DB_NAME', 'nu_daycare');
define('DB_USER', 'postgres');
define('DB_PASS', '1234');

// Create a connection to the PostgreSQL database
function getDatabaseConnection() {
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    
    try {
        // Create a new PDO instance
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        error_log("Database connection successful");
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        throw $e;
    }
}

getDatabaseConnection();

?>
