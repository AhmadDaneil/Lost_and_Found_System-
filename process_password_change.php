<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to change your password.";
    header("Location: login.php"); // Updated from login.html to login.php
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    $conn = getDbConnection();

    // 1. Verify current password
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $stored_password_hash = $user['password_hash'];

            if (!password_verify($current_password, $stored_password_hash)) {
                $_SESSION['error_message'] = "Current password is incorrect.";
                header("Location: profile.php");
                exit();
            }
        } else {
            $_SESSION['error_message'] = "User not found.";
            header("Location: profile.php");
            exit();
        }
        $stmt->close();
    } else {
        error_log("Error preparing current password verification query: " . $conn->error);
        $_SESSION['error_message'] = "Database error during password verification.";
        closeDbConnection($conn);
        header("Location: profile.php");
        exit();
    }

    // 2. Validate new password
    if (empty($new_password) || empty($confirm_new_password)) {
        $_SESSION['error_message'] = "New password and confirmation are required.";
        header("Location: profile.php");
        exit();
    }

    if ($new_password !== $confirm_new_password) {
        $_SESSION['error_message'] = "New password and confirmation do not match.";
        header("Location: profile.php");
        exit();
    }

    // Optional: Add more password strength validation (e.g., minimum length, complexity)
    if (strlen($new_password) < 8) {
        $_SESSION['error_message'] = "New password must be at least 8 characters long.";
        header("Location: profile.php");
        exit();
    }

    // 3. Hash and update new password
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt_update = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    if ($stmt_update) {
        $stmt_update->bind_param("si", $new_password_hash, $user_id);

        if ($stmt_update->execute()) {
            $_SESSION['success_message'] = "Password changed successfully!";
        } else {
            $_SESSION['error_message'] = "Error changing password: " . $stmt_update->error;
        }
        $stmt_update->close();
    } else {
        error_log("Error preparing password update query: " . $conn->error);
        $_SESSION['error_message'] = "Database error during password update.";
    }

    closeDbConnection($conn);
    header("Location: profile.php");
    exit();

} else {
    // If accessed directly without POST request
    header("Location: profile.php");
    exit();
}
?>
