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
    header("Location: login.html");
    exit();
}

$user_id_to_delete = $_GET['id'] ?? null;

if ($user_id_to_delete) {
    $conn = getDbConnection();

    // IMPORTANT SECURITY CHECK: Prevent an admin from deleting their own account
    if ($user_id_to_delete == $_SESSION['user_id']) {
        $_SESSION['error_message'] = "You cannot delete your own admin account.";
        closeDbConnection($conn);
        header("Location: admin_users.php");
        exit();
    }

    // Optional: Check if the user to be deleted is also an admin.
    // You might want to prevent deleting other admins or require a higher privilege.
    $stmt_check_admin = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
    $stmt_check_admin->bind_param("i", $user_id_to_delete);
    $stmt_check_admin->execute();
    $result_check_admin = $stmt_check_admin->get_result();
    $user_to_delete_type = null;
    if ($row = $result_check_admin->fetch_assoc()) {
        $user_to_delete_type = $row['user_type'];
    }
    $stmt_check_admin->close();

    if ($user_to_delete_type === 'admin') {
        $_SESSION['error_message'] = "Cannot delete another administrator account directly. Please demote them first if necessary.";
        closeDbConnection($conn);
        header("Location: admin_users.php");
        exit();
    }


    // Delete the user from the database
    // Consider also deleting related items (lost_items, found_items) or reassigning them
    // For simplicity, this example only deletes the user record.
    // A more robust solution would handle cascading deletes or set user_id to NULL for items.
    $stmt_delete = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt_delete->bind_param("i", $user_id_to_delete);

    if ($stmt_delete->execute()) {
        $_SESSION['success_message'] = "User (ID: " . htmlspecialchars($user_id_to_delete) . ") deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting user (ID: " . htmlspecialchars($user_id_to_delete) . "): " . $stmt_delete->error;
    }

    $stmt_delete->close();
    closeDbConnection($conn);

    header("Location: admin_users.php"); // Redirect back to the user list
    exit();

} else {
    $_SESSION['error_message'] = "No user ID provided for deletion.";
    header("Location: admin_users.php");
    exit();
}
?>
