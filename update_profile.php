<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to update your profile.";
    header("Location: login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $full_name = $_POST['full_name'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $city = $_POST['city'] ?? '';
    $country = $_POST['country'] ?? '';
    $telegram_username = $_POST['telegram_username'] ?? '';
    $gender = $_POST['gender'] ?? '';

    // Basic validation
    if (empty($full_name)) {
        $_SESSION['error_message'] = "Full Name cannot be empty.";
        header("Location: profile.php");
        exit();
    }

    $conn = getDbConnection();

    // Prepare the update statement
    // Note: Email is not updated here as it's readonly in the form.
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone_number = ?, city = ?, country = ?, telegram_username = ?, gender = ? WHERE id = ?");
    $stmt->bind_param("ssssssi", $full_name, $phone_number, $city, $country, $telegram_username, $gender, $user_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Profile updated successfully!";
        // Update session full_name in case it changed
        $_SESSION['full_name'] = $full_name;
    } else {
        $_SESSION['error_message'] = "Error updating profile: " . $stmt->error;
    }

    $stmt->close();
    closeDbConnection($conn);

    header("Location: profile.php");
    exit();
} else {
    // If accessed directly without POST request
    header("Location: profile.php");
    exit();
}
?>
