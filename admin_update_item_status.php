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

$item_id = $_GET['id'] ?? null;
$item_type = $_GET['type'] ?? null; // 'lost' or 'found'
$new_status = $_GET['new_status'] ?? null; // 'found', 'claimed', 'pending_approval', 'rejected'

if ($item_id && ($item_type === 'lost' || $item_type === 'found') && $new_status) {
    $conn = getDbConnection();
    $table = '';
    $valid_statuses = []; // Define what statuses an admin can set

    if ($item_type === 'lost') {
        $table = 'lost_items';
        // Admin can set lost items to 'found' or 'not_found' or 'pending_approval' or 'rejected'
        $valid_statuses = ['found', 'not_found', 'pending_approval', 'rejected'];
    } elseif ($item_type === 'found') {
        $table = 'found_items';
        // Admin can set found items to 'claimed' or 'unclaimed' or 'pending_approval' or 'rejected'
        $valid_statuses = ['claimed', 'unclaimed', 'pending_approval', 'rejected'];
    }

    // Validate new_status against allowed values for the item type
    if (!in_array($new_status, $valid_statuses)) {
        $_SESSION['error_message'] = "Invalid status update requested for " . htmlspecialchars($item_type) . " item.";
        closeDbConnection($conn);
        header("Location: admin_homepage.php");
        exit();
    }

    // Update the item status
    $stmt_update = $conn->prepare("UPDATE $table SET status = ? WHERE id = ?");
    $stmt_update->bind_param("si", $new_status, $item_id);

    if ($stmt_update->execute()) {
        $_SESSION['success_message'] = "Item (ID: " . htmlspecialchars($item_id) . ") status updated successfully to '" . htmlspecialchars($new_status) . "'.";
    } else {
        $_SESSION['error_message'] = "Error updating item status (ID: " . htmlspecialchars($item_id) . "): " . $stmt_update->error;
    }

    $stmt_update->close();
    closeDbConnection($conn);

    // Redirect back to the admin dashboard
    header("Location: admin_homepage.php");
    exit();

} else {
    $_SESSION['error_message'] = "Invalid request for admin status update.";
    header("Location: admin_homepage.php");
    exit();
}
?>
