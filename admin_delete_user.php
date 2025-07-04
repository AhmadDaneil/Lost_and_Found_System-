<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['error_message'] = "Unauthorized access. You must be logged in as an administrator.";
    header("Location: login.php");
    exit();
}

$user_id_to_delete = $_GET['id'] ?? null;

// Don't allow admins to delete themselves
if ($user_id_to_delete == $_SESSION['user_id']) {
    $_SESSION['error_message'] = "You cannot delete your own admin account.";
    header("Location: admin_homepage.php");
    exit();
}

if ($user_id_to_delete) {
    $conn = getDbConnection();

    try {
        // First delete any items associated with this user
        $conn->begin_transaction();
        
        // Delete from lost_items
        $stmt_delete_lost = $conn->prepare("DELETE FROM lost_items WHERE user_id = ?");
        $stmt_delete_lost->bind_param("i", $user_id_to_delete);
        $stmt_delete_lost->execute();
        $stmt_delete_lost->close();
        
        // Delete from found_items
        $stmt_delete_found = $conn->prepare("DELETE FROM found_items WHERE user_id = ?");
        $stmt_delete_found->bind_param("i", $user_id_to_delete);
        $stmt_delete_found->execute();
        $stmt_delete_found->close();
        
        // Now delete the user
        $stmt_delete_user = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt_delete_user->bind_param("i", $user_id_to_delete);
        
        if ($stmt_delete_user->execute()) {
            $conn->commit();
            $_SESSION['success_message'] = "User (ID: " . htmlspecialchars($user_id_to_delete) . ") and all their items deleted successfully!";
        } else {
            $conn->rollback();
            $_SESSION['error_message'] = "Error deleting user (ID: " . htmlspecialchars($user_id_to_delete) . ")";
        }
        
        $stmt_delete_user->close();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error deleting user: " . $e->getMessage();
    }

    closeDbConnection($conn);
} else {
    $_SESSION['error_message'] = "No user ID provided for deletion.";
}

header("Location: admin_homepage.php");
exit();
?>
