<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['error_message'] = "You must be logged in as an administrator to access this page.";
    header("Location: login.php"); // Updated from login.html to login.php
    exit();
}

$admin_full_name = $_SESSION['full_name'] ?? 'Admin';

$conn = getDbConnection();

$search_query = $_GET['search'] ?? ''; // Get search query from URL parameter

// Fetch all reported items (both lost and found)
$all_reported_items = [];

// Base SQL for lost items
$sql_lost = "SELECT id, item_name, description, status, created_at, user_id, lost_location AS location, category FROM lost_items";
// Base SQL for found items
$sql_found = "SELECT id, item_name, description, status, created_at, user_id, found_location AS location, category FROM found_items";

$search_params = [];
$search_types = "";

if (!empty($search_query)) {
    $search_param = '%' . $search_query . '%';
    // Add WHERE clause for lost items
    $sql_lost .= " WHERE item_name LIKE ? OR description LIKE ? OR lost_location LIKE ? OR category LIKE ?";
    // Add WHERE clause for found items
    $sql_found .= " WHERE item_name LIKE ? OR description LIKE ? OR found_location LIKE ? OR category LIKE ?";

    // Prepare search parameters for both queries
    $search_params = [$search_param, $search_param, $search_param, $search_param];
    $search_types = "ssss";
}

// Fetch lost items
$stmt_lost = $conn->prepare($sql_lost . " ORDER BY created_at DESC");
if ($stmt_lost) {
    if (!empty($search_query)) {
        $stmt_lost->bind_param($search_types, ...$search_params);
    }
    $stmt_lost->execute();
    $result_lost = $stmt_lost->get_result();
    while ($row = $result_lost->fetch_assoc()) {
        $row['item_type'] = 'Lost';
        $all_reported_items[] = $row;
    }
    $stmt_lost->close();
} else {
    error_log("Error preparing lost_items query: " . $conn->error);
}

// Fetch found items
$stmt_found = $conn->prepare($sql_found . " ORDER BY created_at DESC");
if ($stmt_found) {
    if (!empty($search_query)) {
        $stmt_found->bind_param($search_types, ...$search_params);
    }
    $stmt_found->execute();
    $result_found = $stmt_found->get_result();
    while ($row = $result_found->fetch_assoc()) {
        $row['item_type'] = 'Found';
        $all_reported_items[] = $row;
    }
    $stmt_found->close();
} else {
    error_log("Error preparing found_items query: " . $conn->error);
}

// Fetch usernames for all items
$user_names = [];
if (!empty($all_reported_items)) {
    $user_ids = array_unique(array_column($all_reported_items, 'user_id'));
    $id_placeholders = implode(',', array_fill(0, count($user_ids), '?'));
    $stmt_users = $conn->prepare("SELECT id, full_name FROM users WHERE id IN ($id_placeholders)");
    if ($stmt_users) {
        $types = str_repeat('i', count($user_ids));
        $stmt_users->bind_param($types, ...$user_ids);
        $stmt_users->execute();
        $result_users = $stmt_users->get_result();
        while ($row = $result_users->fetch_assoc()) {
            $user_names[$row['id']] = $row['full_name'];
        }
        $stmt_users->close();
    } else {
        error_log("Error preparing users query: " . $conn->error);
    }
}

// Sort items by created_at (most recent first)
usort($all_reported_items, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

closeDbConnection($conn);

// Function to format status for display
function formatStatus($status, $item_type) {
    if ($item_type === 'Lost') {
        switch ($status) {
            case 'not_found': return '❌ Not Found';
            case 'found': return '✅ Found';
            case 'pending_approval': return '⏳ Pending Approval';
            case 'rejected': return '🚫 Rejected';
            default: return ucfirst(str_replace('_', ' ', $status));
        }
    } elseif ($item_type === 'Found') {
        switch ($status) {
            case 'unclaimed': return '❌ Unclaimed';
            case 'claimed': return '✅ Claimed by owner';
            case 'pending_approval': return '⏳ Pending Approval';
            case 'rejected': return '🚫 Rejected';
            default: return ucfirst(str_replace('_', ' ', $status));
        }
    }
    return ucfirst(str_replace('_', ' ', $status));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FoundIt</title>
    <link rel="stylesheet" href="unified_styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Admin Dashboard Specific Styles (can be moved to unified_styles.css if preferred) */
        .admin-dashboard-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f0f2f5;
            font-family: 'Poppins', sans-serif;
        }

        .admin-header {
            background-color: #8b1e1e;
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-logo {
            font-size: 28px;
            font-weight: 700;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid white;
            object-fit: cover;
        }

        .logout-btn {
            background-color: #d9534f;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.3s ease;
        }

        .logout-btn:hover {
            background-color: #c9302c;
        }

        .admin-main-content {
            flex-grow: 1;
            padding: 30px;
            background-color: #f8f9fa;
        }

        .controls-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .search-box {
            display: flex;
            border: 1px solid #ced4da;
            border-radius: 20px;
            overflow: hidden;
            flex-grow: 1;
            max-width: 400px;
        }

        .search-input {
            border: none;
            padding: 10px 15px;
            flex-grow: 1;
            font-size: 14px;
            outline: none;
        }

        .search-icon {
            background-color: #e9ecef;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .search-icon:hover {
            background-color: #dee2e6;
        }

        .sort-dropdown {
            position: relative;
            display: inline-block;
            border: 1px solid #ced4da;
            border-radius: 20px;
            background-color: white;
            overflow: hidden;
        }

        .sort-dropdown select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-color: transparent;
            border: none;
            padding: 10px 30px 10px 15px;
            font-size: 14px;
            cursor: pointer;
            outline: none;
            width: 100%;
        }

        .sort-dropdown i {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .admin-table-section {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
        }

        .admin-table-section table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px; /* Ensure table doesn't get too small */
        }

        .admin-table-section th,
        .admin-table-section td {
            text-align: left;
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .admin-table-section th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
            text-transform: uppercase;
            font-size: 12px;
        }

        .admin-table-section tbody tr:hover {
            background-color: #f2f2f2;
        }

        .status-pending {
            color: #ffc107;
            font-weight: 600;
        }

        .status-approved {
            color: #28a745;
            font-weight: 600;
        }

        .status-rejected {
            color: #dc3545;
            font-weight: 600;
        }

        .admin-action-buttons {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            flex-wrap: wrap;
        }

        .admin-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: background-color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none; /* For anchor tags */
        }

        .admin-btn:hover {
            background-color: #0056b3;
        }

        .admin-btn.approved-btn {
            background-color: #28a745;
        }
        .admin-btn.approved-btn:hover {
            background-color: #218838;
        }

        .admin-btn.revoke-btn {
            background-color: #dc3545;
        }
        .admin-btn.revoke-btn:hover {
            background-color: #c82333;
        }

        .admin-btn.view-report-btn {
            background-color: #17a2b8;
        }
        .admin-btn.view-report-btn:hover {
            background-color: #138496;
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .admin-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .admin-header .header-left,
            .admin-header .header-right {
                width: 100%;
                justify-content: space-between;
            }

            .admin-main-content {
                padding: 20px;
            }

            .admin-table-section table {
                min-width: unset;
            }

            .admin-table-section table,
            .admin-table-section thead,
            .admin-table-section tbody,
            .admin-table-section th,
            .admin-table-section td,
            .admin-table-section tr {
                display: block;
            }

            .admin-table-section thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }

            .admin-table-section tr {
                border: 1px solid #e9ecef;
                margin-bottom: 10px;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            }

            .admin-table-section td {
                border: none;
                position: relative;
                padding-left: 50%;
                text-align: right;
            }

            .admin-table-section td::before {
                position: absolute;
                left: 6px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: 600;
                color: #6c757d;
            }

            .admin-table-section td:nth-of-type(1)::before { content: "ID:"; }
            .admin-table-section td:nth-of-type(2)::before { content: "Item Name:"; }
            .admin-table-section td:nth-of-type(3)::before { content: "Username:"; }
            .admin-table-section td:nth-of-type(4)::before { content: "Status:"; }
            .admin-table-section td:nth-of-type(5)::before { content: "Date:"; }
            .admin-table-section td:nth-of-type(6)::before { content: "Type:"; }
            .admin-table-section td:nth-of-type(7)::before { content: "Actions:"; }

            .controls-row {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                max-width: 100%;
            }

            .sort-dropdown {
                width: 100%;
            }

            .admin-action-buttons {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard-container">
        <header class="admin-header">
            <div class="header-left">
                <button class="back-btn" onclick="window.history.back()">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <div class="admin-logo">FoundIt</div>
            </div>
            <div class="header-right">
                <span style="font-weight: 600;">Welcome, <?php echo htmlspecialchars($admin_full_name); ?></span>
                <img src="images/admin-avatar.png" alt="Admin" class="admin-avatar">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-arrow-right-from-bracket"></i> Logout
                </a>
            </div>
        </header>

        <main class="admin-main-content">
            <?php include 'message_modal.php'; // Include the message modal ?>

            <div class="controls-row">
                <form action="admin_homepage.php" method="GET" class="search-box">
                    <input type="text" name="search" placeholder="Search by item name, description, location, or category..." class="search-input" value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="search-icon"><i class="fas fa-search"></i></button>
                </form>
                <div class="sort-dropdown">
                    <select onchange="window.location.href = this.value;">
                        <option value="admin_homepage.php?sort=date_desc" <?php echo (!isset($_GET['sort']) || $_GET['sort'] === 'date_desc') ? 'selected' : ''; ?>>Date (Newest First)</option>
                        <option value="admin_homepage.php?sort=date_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'date_asc') ? 'selected' : ''; ?>>Date (Oldest First)</option>
                        <option value="admin_homepage.php?sort=name_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'name_asc') ? 'selected' : ''; ?>>Item Name (A-Z)</option>
                        <option value="admin_homepage.php?sort=name_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'name_desc') ? 'selected' : ''; ?>>Item Name (Z-A)</option>
                    </select>
                    <i class="fas fa-caret-down"></i>
                </div>
            </div>

            <section class="admin-table-section">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item Name</th>
                            <th>Type</th>
                            <th>Username</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($all_reported_items)): ?>
                            <?php
                            $sorted_items = $all_reported_items;
                            $sort_by = $_GET['sort'] ?? 'date_desc';

                            usort($sorted_items, function($a, $b) use ($sort_by) {
                                switch ($sort_by) {
                                    case 'date_asc':
                                        return strtotime($a['created_at']) - strtotime($b['created_at']);
                                    case 'date_desc':
                                        return strtotime($b['created_at']) - strtotime($a['created_at']);
                                    case 'name_asc':
                                        return strcmp($a['item_name'], $b['item_name']);
                                    case 'name_desc':
                                        return strcmp($b['item_name'], $a['item_name']);
                                    default:
                                        return strtotime($b['created_at']) - strtotime($a['created_at']);
                                }
                            });
                            ?>
                            <?php foreach ($sorted_items as $item): ?>
                                <tr>
                                    <td data-label="ID"><?php echo htmlspecialchars($item['id']); ?></td>
                                    <td data-label="Item Name"><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td data-label="Type"><?php echo htmlspecialchars($item['item_type']); ?></td>
                                    <td data-label="Username"><?php echo htmlspecialchars($user_names[$item['user_id']] ?? 'N/A'); ?></td>
                                    <td data-label="Status" class="status-<?php echo htmlspecialchars($item['status']); ?>">
                                        <?php echo formatStatus($item['status'], $item['item_type']); ?>
                                    </td>
                                    <td data-label="Date"><?php echo date('d/m/Y', strtotime($item['created_at'])); ?></td>
                                    <td data-label="Actions">
                                        <a href="view_item.php?id=<?php echo htmlspecialchars($item['id']); ?>&type=<?php echo strtolower(htmlspecialchars($item['item_type'])); ?>" class="admin-btn" style="padding: 8px 12px; font-size: 13px; margin-right: 5px; background-color: #007bff;">View</a>
                                        <?php if ($item['status'] === 'pending_approval'): ?>
                                            <a href="admin_update_item_status.php?id=<?php echo htmlspecialchars($item['id']); ?>&type=<?php echo strtolower(htmlspecialchars($item['item_type'])); ?>&new_status=approved" class="admin-btn approved-btn" style="padding: 8px 12px; font-size: 13px; margin-right: 5px;">Approve</a>
                                            <a href="admin_update_item_status.php?id=<?php echo htmlspecialchars($item['id']); ?>&type=<?php echo strtolower(htmlspecialchars($item['item_type'])); ?>&new_status=rejected" class="admin-btn revoke-btn" style="padding: 8px 12px; font-size: 13px; margin-right: 5px;">Reject</a>
                                        <?php elseif ($item['status'] === 'approved' || $item['status'] === 'found' || $item['status'] === 'claimed'): ?>
                                            <?php if ($item['item_type'] === 'Lost'): ?>
                                                <a href="admin_update_item_status.php?id=<?php echo htmlspecialchars($item['id']); ?>&type=<?php echo strtolower(htmlspecialchars($item['item_type'])); ?>&new_status=not_found" class="admin-btn revoke-btn" style="padding: 8px 12px; font-size: 13px; margin-right: 5px;">Mark Not Found</a>
                                            <?php elseif ($item['item_type'] === 'Found'): ?>
                                                <a href="admin_update_item_status.php?id=<?php echo htmlspecialchars($item['id']); ?>&type=<?php echo strtolower(htmlspecialchars($item['item_type'])); ?>&new_status=unclaimed" class="admin-btn revoke-btn" style="padding: 8px 12px; font-size: 13px; margin-right: 5px;">Mark Unclaimed</a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No items reported yet. <?php echo !empty($search_query) ? 'Try a different search term.' : ''; ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <!-- Admin action buttons (for selected items, will require JS and backend logic) -->
            <div class="admin-action-buttons">
            <!-- These buttons would typically work with checkboxes next to table rows -->
            <button class="admin-btn approved-btn" onclick="alert('Approve functionality to be implemented.')">Approve Selected</button>
            <button class="admin-btn revoke-btn" onclick="alert('Revoke functionality to be implemented.')">Revoke Selected</button>
            <a href="admin_generate_report.php" class="admin-btn" style="background-color: #17a2b8; text-decoration: none;" target="_blank">
                <i class="fas fa-file-csv"></i> Generate Report
            </a>
        </div>
        </main>
    </div>
</body>
</html>
