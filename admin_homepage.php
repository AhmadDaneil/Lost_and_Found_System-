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
    header("Location: login.html");
    exit();
}

$admin_full_name = $_SESSION['full_name'] ?? 'Admin';

$conn = getDbConnection();

$search_query = $_GET['search'] ?? ''; // Get search query from URL parameter

// Fetch all reported items (both lost and found)
$all_reported_items = [];

// Base SQL for lost items
$sql_lost = "SELECT id, item_name, description, status, created_at, user_id, lost_location, category FROM lost_items";
// Base SQL for found items
$sql_found = "SELECT id, item_name, description, status, created_at, user_id, found_location, category FROM found_items";

$search_params = [];
$search_types = "";

if (!empty($search_query)) {
    $search_param = '%' . $search_query . '%';
    // Add WHERE clause for lost items
    $sql_lost .= " WHERE item_name LIKE ? OR description LIKE ? OR lost_location LIKE ? OR category LIKE ?";
    // Add WHERE clause for found items
    $sql_found .= " WHERE item_name LIKE ? OR description LIKE ? OR found_location LIKE ? OR category LIKE ?";

    // Prepare parameters for binding
    $search_params = [$search_param, $search_param, $search_param, $search_param];
    $search_types = "ssss";
}

$sql_lost .= " ORDER BY created_at DESC";
$sql_found .= " ORDER BY created_at DESC";

// Fetch lost items
$stmt_lost = $conn->prepare($sql_lost);
if ($stmt_lost) {
    if (!empty($search_query)) {
        $stmt_lost->bind_param($search_types, ...$search_params);
    }
    $stmt_lost->execute();
    $result_lost = $stmt_lost->get_result();
    while ($row = $result_lost->fetch_assoc()) {
        $row['item_type'] = 'lost';
        $all_reported_items[] = $row;
    }
    $stmt_lost->close();
} else {
    error_log("Error preparing lost_items query for admin: " . $conn->error);
}

// Fetch found items
$stmt_found = $conn->prepare($sql_found);
if ($stmt_found) {
    if (!empty($search_query)) {
        $stmt_found->bind_param($search_types, ...$search_params);
    }
    $stmt_found->execute();
    $result_found = $stmt_found->get_result();
    while ($row = $result_found->fetch_assoc()) {
        $row['item_type'] = 'found';
        $all_reported_items[] = $row;
    }
    $stmt_found->close();
} else {
    error_log("Error preparing found_items query for admin: " . $conn->error);
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

// Function to format status for display (re-using from view_item.php logic)
function formatAdminStatus($status) {
    switch ($status) {
        case 'not_found':
        case 'unclaimed':
            return '<span class="status-pending">Pending</span>';
        case 'found':
        case 'claimed':
            return '<span class="status-approved">Approved</span>';
        case 'pending_approval': // For future admin approval workflow
            return '<span class="status-pending">Pending Approval</span>';
        case 'rejected':
            return '<span class="status-rejected">Rejected</span>';
        default:
            return $status;
    }
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
        /* Admin Dashboard specific styles */
        body {
            background-color: #f5ff9c; /* Light yellow background */
            display: block; /* Override flex from unified_styles.css body */
            height: auto; /* Allow content to dictate height */
        }

        .admin-dashboard-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #fffdd0;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            max-width: 1200px;
            margin: 20px auto;
        }

        .admin-header {
            background-color: #8b1e1e; /* Dark red */
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .admin-header .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-logo {
            font-size: 30px;
            font-weight: 800;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }

        .admin-header .header-right {
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

        .logout-btn, .back-btn {
            background: none;
            border: 1px solid white;
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            text-decoration: none; /* For the back-btn link */
        }

        .logout-btn i, .back-btn i {
            margin-right: 8px;
        }

        .logout-btn:hover, .back-btn:hover {
            background-color: white;
            color: #8b1e1e;
        }

        .admin-main-content {
            flex-grow: 1;
            padding: 30px;
        }

        .admin-section {
            background-color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .admin-section h2 {
            font-size: 22px;
            color: #333;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .admin-table-section table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .admin-table-section th,
        .admin-table-section td {
            text-align: left;
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        .admin-table-section th {
            background-color: #f8f8f8;
            font-weight: 600;
            color: #555;
            text-transform: uppercase;
        }

        .admin-table-section tbody tr:hover {
            background-color: #f1f1f1;
        }

        .admin-table-section .status-pending {
            color: #d39e00; /* Yellowish */
            font-weight: 600;
        }

        .admin-table-section .status-approved {
            color: #28a745; /* Green */
            font-weight: 600;
        }

        .admin-table-section .status-rejected {
            color: #dc3545; /* Red */
            font-weight: 600;
        }

        .admin-action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .admin-btn {
            background-color: #8b1e1e;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        .admin-btn:hover {
            background-color: #6a1515;
        }

        .view-report-btn {
            background-color: #007bff;
        }
        .view-report-btn:hover {
            background-color: #0056b3;
        }

        .approved-btn {
            background-color: #28a745;
        }
        .approved-btn:hover {
            background-color: #218838;
        }

        .revoke-btn {
            background-color: #dc3545;
        }
        .revoke-btn:hover {
            background-color: #c82333;
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
                margin-bottom: 10px;
            }

            .admin-main-content {
                padding: 20px;
            }

            .admin-table-section table {
                min-width: unset;
            }

            /* Table responsiveness (cards-like display) */
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
                border: 1px solid var(--border-color); /* Assuming var(--border-color) is defined or use a direct color */
                margin-bottom: 10px;
                border-radius: 8px;
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
                content: attr(data-label);
            }

            .admin-action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .admin-btn {
                width: 100%;
                max-width: 300px;
            }
        }

        /* Search box specific styles for admin dashboard */
        .admin-search-box {
            margin: 20px auto;
            max-width: 700px;
            position: relative;
        }

        .admin-search-box input {
            width: 100%;
            padding: 12px 18px;
            border-radius: 25px;
            border: 1px solid #ddd;
            outline: none;
            font-size: 15px;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
        }

        .admin-search-box input::placeholder {
            color: #999;
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
                <img src="https://placehold.co/40x40/cccccc/000000?text=A" alt="Admin" class="admin-avatar">
                <button class="logout-btn" onclick="window.location.href='logout.php'">
                    <i class="fas fa-arrow-right-from-bracket"></i> Logout
                </button>
            </div>
        </header>

        <main class="admin-main-content">
            <h1>Admin Dashboard - Welcome, <?php echo htmlspecialchars($admin_full_name); ?>!</h1>

            <!-- Display success/error messages from session -->
            <?php if (isset($_SESSION['success_message'])): ?>
              <div style="background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; margin-bottom: 20px; border-radius: 5px; text-align: center;">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
              </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
              <div style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; margin-bottom: 20px; border-radius: 5px; text-align: center;">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
              </div>
            <?php endif; ?>

            <div style="margin-bottom: 25px; text-align: center;">
                <a href="admin_users.php" class="admin-btn view-report-btn" style="background-color: #007bff; padding: 12px 25px; font-size: 16px;">
                    <i class="fas fa-users"></i> Manage Users
                </a>
            </div>

            <!-- Search Box for Admin Dashboard -->
            <div class="admin-search-box">
                <form action="admin_homepage.php" method="GET">
                    <input type="text" name="search" placeholder="Search items by name, description, location, or category..." value="<?php echo htmlspecialchars($search_query); ?>" />
                </form>
            </div>

            <section class="admin-section admin-table-section">
                <h2>All Reported Items</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item Name</th>
                            <th>Type</th>
                            <th>Username</th>
                            <th>Status</th>
                            <th>Date Reported</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($all_reported_items)): ?>
                            <?php foreach ($all_reported_items as $item): ?>
                                <tr>
                                    <td data-label="ID"><?php echo htmlspecialchars($item['id']); ?></td>
                                    <td data-label="Item Name"><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td data-label="Type"><?php echo ucfirst(htmlspecialchars($item['item_type'])); ?></td>
                                    <td data-label="Username"><?php echo htmlspecialchars($user_names[$item['user_id']] ?? 'N/A'); ?></td>
                                    <td data-label="Status" class="<?php echo htmlspecialchars('status-' . $item['status']); ?>"><?php echo formatAdminStatus($item['status']); ?></td>
                                    <td data-label="Date"><?php echo date('d/m/Y', strtotime($item['created_at'])); ?></td>
                                    <td data-label="Actions">
                                        <a href="<?php echo htmlspecialchars($item['item_type']); ?>_item_view.php?id=<?php echo htmlspecialchars($item['id']); ?>" class="admin-btn view-report-btn" style="padding: 8px 12px; font-size: 13px; margin-right: 5px;">View</a>
                                        <?php if ($item['item_type'] === 'lost'): ?>
                                            <?php if ($item['status'] !== 'found'): ?>
                                                <a href="admin_update_item_status.php?id=<?php echo htmlspecialchars($item['id']); ?>&type=lost&new_status=found" class="admin-btn approved-btn" style="padding: 8px 12px; font-size: 13px; margin-right: 5px;">Mark Found</a>
                                            <?php else: ?>
                                                <a href="admin_update_item_status.php?id=<?php echo htmlspecialchars($item['id']); ?>&type=lost&new_status=not_found" class="admin-btn revoke-btn" style="padding: 8px 12px; font-size: 13px; margin-right: 5px;">Mark Not Found</a>
                                            <?php endif; ?>
                                        <?php elseif ($item['item_type'] === 'found'): ?>
                                            <?php if ($item['status'] !== 'claimed'): ?>
                                                <a href="admin_update_item_status.php?id=<?php echo htmlspecialchars($item['id']); ?>&type=found&new_status=claimed" class="admin-btn approved-btn" style="padding: 8px 12px; font-size: 13px; margin-right: 5px;">Mark Claimed</a>
                                            <?php else: ?>
                                                <a href="admin_update_item_status.php?id=<?php echo htmlspecialchars($item['id']); ?>&type=found&new_status=unclaimed" class="admin-btn revoke-btn" style="padding: 8px 12px; font-size: 13px; margin-right: 5px;">Mark Unclaimed</a>
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
