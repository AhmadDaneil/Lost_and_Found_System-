<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['error_message'] = "Unauthorized access. You must be logged in as an administrator to generate reports.";
    header("Location: login.html");
    exit();
}

$conn = getDbConnection();

$all_reported_items = [];

// Fetch lost items
$stmt_lost = $conn->prepare("SELECT id, item_name, description, date_lost AS item_date, lost_location AS item_location, category, status, user_id, created_at FROM lost_items ORDER BY created_at DESC");
if ($stmt_lost) {
    $stmt_lost->execute();
    $result_lost = $stmt_lost->get_result();
    while ($row = $result_lost->fetch_assoc()) {
        $row['item_type'] = 'Lost';
        $all_reported_items[] = $row;
    }
    $stmt_lost->close();
} else {
    error_log("Error preparing lost_items query for report: " . $conn->error);
}

// Fetch found items
$stmt_found = $conn->prepare("SELECT id, item_name, description, date_found AS item_date, found_location AS item_location, category, status, user_id, created_at FROM found_items ORDER BY created_at DESC");
if ($stmt_found) {
    $stmt_found->execute();
    $result_found = $stmt_found->get_result();
    while ($row = $result_found->fetch_assoc()) {
        $row['item_type'] = 'Found';
        $all_reported_items[] = $row;
    }
    $stmt_found->close();
} else {
    error_log("Error preparing found_items query for report: " . $conn->error);
}

// Sort all items by created_at (most recent first)
usort($all_reported_items, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Fetch usernames for the items
$user_names = [];
if (!empty($all_reported_items)) {
    $user_ids = array_unique(array_column($all_reported_items, 'user_id'));
    if (!empty($user_ids)) {
        $in_clause = implode(',', array_fill(0, count($user_ids), '?'));
        $stmt_users = $conn->prepare("SELECT id, full_name FROM users WHERE id IN ($in_clause)");
        $types = str_repeat('i', count($user_ids));
        $stmt_users->bind_param($types, ...$user_ids);
        $stmt_users->execute();
        $result_users = $stmt_users->get_result();
        while ($row = $result_users->fetch_assoc()) {
            $user_names[$row['id']] = $row['full_name'];
        }
        $stmt_users->close();
    }
}

closeDbConnection($conn);

// Generate CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="foundit_report_' . date('Ymd_His') . '.csv"');

$output = fopen('php://output', 'w');

// CSV Header
fputcsv($output, ['Item ID', 'Item Name', 'Description', 'Type', 'Date', 'Location', 'Category', 'Status', 'Reported By', 'Reported On']);

// CSV Data
foreach ($all_reported_items as $item) {
    $status_display = '';
    if ($item['item_type'] === 'Lost') {
        switch ($item['status']) {
            case 'not_found': $status_display = 'Not Found'; break;
            case 'found': $status_display = 'Found'; break;
            case 'pending_approval': $status_display = 'Pending Approval'; break;
            case 'rejected': $status_display = 'Rejected'; break;
            default: $status_display = $item['status'];
        }
    } elseif ($item['item_type'] === 'Found') {
        switch ($item['status']) {
            case 'unclaimed': $status_display = 'Unclaimed'; break;
            case 'claimed': $status_display = 'Claimed by owner'; break;
            case 'pending_approval': $status_display = 'Pending Approval'; break;
            case 'rejected': $status_display = 'Rejected'; break;
            default: $status_display = $item['status'];
        }
    }

    fputcsv($output, [
        $item['id'],
        $item['item_name'],
        $item['description'],
        $item['item_type'],
        $item['item_date'],
        $item['item_location'],
        $item['category'],
        $status_display,
        $user_names[$item['user_id']] ?? 'N/A',
        date('Y-m-d H:i:s', strtotime($item['created_at']))
    ]);
}

fclose($output);
exit();
?>
