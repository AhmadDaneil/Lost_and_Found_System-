<?php
require_once 'config.php';

// Function to get a database connection
function getDbConnection() {
    // Create connection
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        // Log the error instead of displaying it directly in production
        error_log("Connection failed: " . $conn->connect_error);
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Function to close the database connection
function closeDbConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}
?>
