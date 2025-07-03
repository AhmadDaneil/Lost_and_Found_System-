<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['error_message'] = "Unauthorized access. You must be logged in as an administrator.";
    header("Location: login.php"); // Updated from login.html to login.php
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id_to_update = $_POST['user_id'] ?? null;
    $full_name = $_POST['full_name'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $city = $_POST['city'] ?? '';
    $country = $_POST['country'] ?? '';
    $telegram_username = $_POST['telegram_username'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $user_type = $_POST['user_type'] ?? 'user'; // Default to 'user' if not set

    // Basic validation
    if (empty($user_id_to_update) || empty($full_name)) {
        $_SESSION['error_message'] = "User ID and Full Name are required.";
        header("Location: admin_edit_user.php?id=" . htmlspecialchars($user_id_to_update));
        exit();
    }

    $conn = getDbConnection();

    // Prepare the update statement
    // Email is not updated here as it's readonly in the form and should not be changed by admin directly
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone_number = ?, city = ?, country = ?, telegram_username = ?, gender = ?, user_type = ? WHERE id = ?");
    $stmt->bind_param("sssssssi", $full_name, $phone_number, $city, $country, $telegram_username, $gender, $user_type, $user_id_to_update);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "User (ID: " . htmlspecialchars($user_id_to_update) . ") profile updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating user profile (ID: " . htmlspecialchars($user_id_to_update) . "): " . $stmt->error;
    }

    $stmt->close();
    closeDbConnection($conn);

    header("Location: admin_users.php"); // Redirect back to the user list
    exit();
} else {
    // If accessed directly without POST request
    header("Location: admin_users.php");
    exit();
}
?>
