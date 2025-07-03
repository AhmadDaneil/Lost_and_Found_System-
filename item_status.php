<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to update item status.";
    header("Location: login.php"); // Updated from login.html to login.php
    exit();
}

$item_id = $_GET['id'] ?? null;
$item_type = $_GET['type'] ?? null;
$new_status = $_GET['new_status'] ?? null;

if ($item_id && ($item_type === 'lost' || $item_type === 'found') && $new_status) {
    $conn = getDbConnection();
    $table = '';
    $current_status = '';

    if ($item_type === 'lost') {
        $table = 'lost_items';
    } elseif ($item_type === 'found') {
        $table = 'found_items';
    }

    // First, verify ownership and fetch current status
    $stmt_check_owner = $conn->prepare("SELECT user_id, status FROM {$table} WHERE id = ?");
    if ($stmt_check_owner) {
        $stmt_check_owner->bind_param("i", $item_id);
        $stmt_check_owner->execute();
        $result_check_owner = $stmt_check_owner->get_result();
        $item_row = $result_check_owner->fetch_assoc();
        $stmt_check_owner->close();

        if (!$item_row || $item_row['user_id'] != $_SESSION['user_id']) {
            $_SESSION['error_message'] = "You are not authorized to update the status of this item.";
            closeDbConnection($conn);
            header("Location: " . $item_type . "_item_view.php?id=" . htmlspecialchars($item_id));
            exit();
        }
        $current_status = $item_row['status'];
    } else {
        error_log("Error preparing owner check query: " . $conn->error);
        $_SESSION['error_message'] = "Database error during ownership verification.";
        closeDbConnection($conn);
        header("Location: homepage.php");
        exit();
    }

    // Define valid status transitions based on current status and item type
    $valid_transitions = [];
    if ($item_type === 'lost') {
        if ($current_status === 'not_found') {
            $valid_transitions = ['found'];
        } elseif ($current_status === 'found') {
            $valid_transitions = ['not_found'];
        }
        // Admin can set other statuses, but user can only toggle between not_found and found
        // For 'pending_approval' or 'rejected' statuses, a user cannot change it back directly.
        // This logic assumes the user can only change 'not_found' to 'found' and vice-versa.
        // If an item is 'pending_approval' or 'rejected', the user cannot change its status via this script.
        if (!in_array($current_status, ['not_found', 'found'])) {
             $_SESSION['error_message'] = "This item's status cannot be changed by a user at this time.";
             closeDbConnection($conn);
             header("Location: " . $item_type . "_item_view.php?id=" . htmlspecialchars($item_id));
             exit();
        }
    } elseif ($item_type === 'found') {
        if ($current_status === 'unclaimed') {
            $valid_transitions = ['claimed'];
        } elseif ($current_status === 'claimed') {
            $valid_transitions = ['unclaimed'];
        }
        // Similar logic for 'pending_approval' or 'rejected' for found items.
        if (!in_array($current_status, ['unclaimed', 'claimed'])) {
             $_SESSION['error_message'] = "This item's status cannot be changed by a user at this time.";
             closeDbConnection($conn);
             header("Location: " . $item_type . "_item_view.php?id=" . htmlspecialchars($item_id));
             exit();
        }
    }

    // Validate new_status against allowed transitions
    if (!in_array($new_status, $valid_transitions)) {
        $_SESSION['error_message'] = "Invalid status update requested for this item or its current status.";
        closeDbConnection($conn);
        header("Location: " . $item_type . "_item_view.php?id=" . htmlspecialchars($item_id));
        exit();
    }

    // Update the item status
    $stmt_update = $conn->prepare("UPDATE {$table} SET status = ? WHERE id = ?");
    if ($stmt_update) {
        $stmt_update->bind_param("si", $new_status, $item_id);

        if ($stmt_update->execute()) {
            $_SESSION['success_message'] = "Item status updated successfully to '" . htmlspecialchars($new_status) . "'.";
        } else {
            $_SESSION['error_message'] = "Error updating item status: " . $stmt_update->error;
        }
        $stmt_update->close();
    } else {
        error_log("Error preparing update query: " . $conn->error);
        $_SESSION['error_message'] = "Database error during status update.";
    }

    closeDbConnection($conn);

    // Redirect back to the item view page
    header("Location: " . $item_type . "_item_view.php?id=" . htmlspecialchars($item_id));
    exit();

} else {
    $_SESSION['error_message'] = "Invalid request for status update.";
    header("Location: homepage.php");
    exit();
}
?>
