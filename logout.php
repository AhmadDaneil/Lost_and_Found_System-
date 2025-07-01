<?php
session_start(); // Start the session

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Clear the "remember me" cookie if it exists
if (isset($_COOKIE['remember_user_id'])) {
    setcookie('remember_user_id', '', time() - 3600, "/"); // Set expiration to an hour ago
}

// Redirect to the login page or homepage
header("Location: login.html"); // Or index.html
exit();
?>