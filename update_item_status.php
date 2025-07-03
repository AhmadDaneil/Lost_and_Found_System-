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
    $valid_statuses = [];

    if ($item_type === 'lost') {
        $table = 'lost_items';
        $valid_statuses = ['found']; // Only 'found' can be set by user from 'not_found'
    } elseif ($item_type === 'found') {
        $table = 'found_items';
        $valid_statuses = ['claimed']; // Only 'claimed' can be set by user from 'unclaimed'
    }

    // Validate new_status against allowed values for the item type
    if (!in_array($new_status, $valid_statuses)) {
        $_SESSION['error_message'] = "Invalid status update requested.";
        header("Location: " . $item_type . "_item_view.php?id=" . htmlspecialchars($item_id));
        exit();
    }

    // Verify ownership before updating
    $stmt_check_owner = $conn->prepare("SELECT user_id FROM $table WHERE id = ?");
    if ($stmt_check_owner) {
        $stmt_check_owner->bind_param("i", $item_id);
        $stmt_check_owner->execute();
        $result_check_owner = $stmt_check_owner->get_result();
        $item_owner_row = $result_check_owner->fetch_assoc();
        $stmt_check_owner->close();

        if (!$item_owner_row || $item_owner_row['user_id'] != $_SESSION['user_id']) {
            $_SESSION['error_message'] = "You are not authorized to update the status of this item.";
            header("Location: " . $item_type . "_item_view.php?id=" . htmlspecialchars($item_id));
            exit();
        }
    } else {
        error_log("Error preparing owner check query: " . $conn->error);
        $_SESSION['error_message'] = "Database error during ownership check.";
        closeDbConnection($conn);
        header("Location: homepage.php");
        exit();
    }


    // Update the item status
    $stmt_update = $conn->prepare("UPDATE $table SET status = ? WHERE id = ?");
    if ($stmt_update) {
        $stmt_update->bind_param("si", $new_status, $item_id);

        if ($stmt_update->execute()) {
            $_SESSION['success_message'] = "Item status updated successfully to '" . $new_status . "'.";
        } else {
            $_SESSION['error_message'] = "Error updating item status: " . $stmt_update->error;
        }
        $stmt_update->close();
    } else {
        error_log("Error preparing status update query: " . $conn->error);
        $_SESSION['error_message'] = "Database error during status update.";
        closeDbConnection($conn);
        header("Location: homepage.php");
        exit();
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
