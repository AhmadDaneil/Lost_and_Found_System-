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
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? ''; // 'approve' or 'reject'
    $items_to_update = $_POST['items'] ?? []; // Array of {id: ..., type: ...}

    if (empty($items_to_update) || !in_array($action, ['approve', 'reject'])) {
        $_SESSION['error_message'] = "Invalid request for bulk update.";
        header("Location: admin_homepage.php");
        exit();
    }

    $conn = getDbConnection();
    $success_count = 0;
    $error_count = 0;

    foreach ($items_to_update as $item) {
        $item_id = $item['id'] ?? null;
        $item_type = $item['type'] ?? null;

        if (!$item_id || !($item_type === 'lost' || $item_type === 'found')) {
            $error_count++;
            continue; // Skip invalid items
        }

        $table = '';
        $new_status = '';

        if ($item_type === 'lost') {
            $table = 'lost_items';
            $new_status = ($action === 'approve') ? 'found' : 'rejected'; // Admin approves lost item -> found, rejects -> rejected
        } elseif ($item_type === 'found') {
            $table = 'found_items';
            $new_status = ($action === 'approve') ? 'claimed' : 'rejected'; // Admin approves found item -> claimed, rejects -> rejected
        }

        // Update the item status
        $stmt_update = $conn->prepare("UPDATE $table SET status = ? WHERE id = ?");
        if ($stmt_update) {
            $stmt_update->bind_param("si", $new_status, $item_id);
            if ($stmt_update->execute()) {
                $success_count++;
            } else {
                error_log("Error updating item ID {$item_id} in {$table}: " . $stmt_update->error);
                $error_count++;
            }
            $stmt_update->close();
        } else {
            error_log("Error preparing update statement for item ID {$item_id} in {$table}: " . $conn->error);
            $error_count++;
        }
    }

    closeDbConnection($conn);

    if ($success_count > 0) {
        $_SESSION['success_message'] = "Successfully {$action}d {$success_count} item(s).";
    }
    if ($error_count > 0) {
        $_SESSION['error_message'] = (isset($_SESSION['error_message']) ? $_SESSION['error_message'] . " " : "") . "Failed to {$action} {$error_count} item(s).";
    }
    if ($success_count === 0 && $error_count === 0) {
        $_SESSION['error_message'] = "No items were processed for bulk update.";
    }

    header("Location: admin_homepage.php");
    exit();

} else {
    // If accessed directly without POST request
    header("Location: admin_homepage.php");
    exit();
}
?>
