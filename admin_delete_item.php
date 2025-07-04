<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['error_message'] = "You must be logged in as an administrator to access this page.";
    header("Location: login.php");
    exit();
}

$item_id_to_delete = $_GET['id'] ?? null;

if ($item_id_to_delete) {
    $conn = getDbConnection();
    
    // First check if item exists in lost_items
    $check_lost = $conn->prepare("SELECT id FROM lost_items WHERE id = ?");
    $check_lost->bind_param("i", $item_id_to_delete);
    $check_lost->execute();
    $item_in_lost = $check_lost->get_result()->num_rows > 0;
    $check_lost->close();
    
    // Then check if item exists in found_items
    $check_found = $conn->prepare("SELECT id FROM found_items WHERE id = ?");
    $check_found->bind_param("i", $item_id_to_delete);
    $check_found->execute();
    $item_in_found = $check_found->get_result()->num_rows > 0;
    $check_found->close();

    if ($item_in_lost || $item_in_found) {
        // Delete from the appropriate table
        if ($item_in_lost) {
            $stmt = $conn->prepare("DELETE FROM lost_items WHERE id = ?");
        } else {
            $stmt = $conn->prepare("DELETE FROM found_items WHERE id = ?");
        }
        
        $stmt->bind_param("i", $item_id_to_delete);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Item (ID: " . htmlspecialchars($item_id_to_delete) . ") deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Error deleting item (ID: " . htmlspecialchars($item_id_to_delete) . "): " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Item (ID: " . htmlspecialchars($item_id_to_delete) . ") not found.";
    }

    closeDbConnection($conn);
    header("Location: admin_homepage.php");
    exit();
} else {
    $_SESSION['error_message'] = "No item ID provided for deletion.";
    header("Location: admin_homepage.php");
    exit();
}
?>
