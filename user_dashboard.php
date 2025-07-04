<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in, if not, redirect to login page
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to access your dashboard.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_full_name = $_SESSION['full_name'] ?? 'User';
$user_email = $_SESSION['email'] ?? ''; // Assuming email is stored in session upon login
$user_telegram = ''; // Will fetch from DB if available
$user_profile_image_path = ''; // Will fetch from DB if available

$conn = getDbConnection();

// Fetch user's telegram username and profile image path
$stmt_user_info = $conn->prepare("SELECT telegram_username, profile_image_path FROM users WHERE id = ?");
if ($stmt_user_info) {
    $stmt_user_info->bind_param("i", $user_id);
    $stmt_user_info->execute();
    $result_user_info = $stmt_user_info->get_result();
    if ($row = $result_user_info->fetch_assoc()) {
        $user_telegram = $row['telegram_username'] ?? '';
        $user_profile_image_path = $row['profile_image_path'] ?? '';
    }
    $stmt_user_info->close();
} else {
    error_log("Error preparing user info query: " . $conn->error);
}


// Fetch user's reported lost items
$user_lost_items = [];
$stmt_lost = $conn->prepare("SELECT id, item_name, description, status, created_at FROM lost_items WHERE user_id = ? ORDER BY created_at DESC");
if ($stmt_lost) {
    $stmt_lost->bind_param("i", $user_id);
    $stmt_lost->execute();
    $result_lost = $stmt_lost->get_result();
    while ($row = $result_lost->fetch_assoc()) {
        $user_lost_items[] = $row;
    }
    $stmt_lost->close();
} else {
    error_log("Error preparing user lost items query: " . $conn->error);
}

// Fetch user's reported found items
$user_found_items = [];
$stmt_found = $conn->prepare("SELECT id, item_name, description, status, created_at FROM found_items WHERE user_id = ? ORDER BY created_at DESC");
if ($stmt_found) {
    $stmt_found->bind_param("i", $user_id);
    $stmt_found->execute();
    $result_found = $stmt_found->get_result();
    while ($row = $result_found->fetch_assoc()) {
        $user_found_items[] = $row;
    }
    $stmt_found->close();
} else {
    error_log("Error preparing user found items query: " . $conn->error);
}

closeDbConnection($conn);

// Function to format status for display
function formatStatus($status) {
    switch ($status) {
        case 'not_found': return '❌ Not Found';
        case 'found': return '✅ Found';
        case 'unclaimed': return '❓ Unclaimed';
        case 'claimed': return '✅ Claimed';
        case 'pending_approval': return '⏳ Pending Approval';
        case 'rejected': return '🚫 Rejected';
        default: return ucfirst(str_replace('_', ' ', $status));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - FoundIt</title>
    <link rel="stylesheet" href="unified_styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f5ff9c;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            display: flex; /* Ensure flexbox for layout */
            min-height: 100vh; /* Full viewport height */
        }

        .dashboard-container {
            display: flex;
            width: 100%;
            background-color: #fffdd0;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin: 20px;
            overflow: hidden; /* For rounded corners */
        }

        .sidebar {
            width: 250px;
            background-color: #8b1e1e;
            color: white;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar .logo {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 30px;
            text-shadow: 2px 2px 2px rgba(0, 0, 0, 0.2);
        }

        .user-profile {
            text-align: center;
            margin-bottom: 40px;
        }

        .user-profile .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            margin-bottom: 10px;
        }

        .user-profile .user-info .user-name {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .user-profile .user-info .user-email {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 10px;
        }

        .telegram-link-btn {
            display: inline-flex;
            align-items: center;
            background-color: #0088cc; /* Telegram blue */
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .telegram-link-btn i {
            margin-right: 8px;
        }

        .telegram-link-btn:hover {
            background-color: #006699;
        }

        .navigation ul {
            list-style: none;
            padding: 0;
            width: 100%;
        }

        .navigation ul li {
            margin-bottom: 15px;
            width: 100%;
        }

        .navigation ul li a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            font-size: 16px;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }

        .navigation ul li a i {
            margin-right: 10px;
            font-size: 18px;
        }

        .navigation ul li a:hover,
        .navigation ul li a.active {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .main-content {
            flex-grow: 1;
            padding: 30px;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .main-header h1 {
            font-size: 32px;
            color: #333;
            font-weight: 700;
        }

        .header-icons {
            display: flex;
            gap: 15px;
        }

        .header-icons .icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #000;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #000;
            font-size: 20px;
            text-decoration: none;
        }

        .header-icons .icon-btn:hover {
            background-color: #000;
            color: #f5ff9c;
            transform: scale(1.1);
        }

        .search-bar {
            display: flex;
            width: 100%;
            max-width: 400px;
            position: relative;
        }

        .search-bar input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ccc;
            border-radius: 20px;
            font-size: 16px;
            padding-right: 40px; /* Space for icon */
        }

        .search-bar .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
        }

        .dashboard-section {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .dashboard-section h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .dashboard-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .dashboard-table th,
        .dashboard-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .dashboard-table th {
            background-color: #f8f8f8;
            font-weight: 600;
            color: #555;
        }

        .dashboard-table tbody tr:last-child td {
            border-bottom: none;
        }

        .dashboard-table .status-not_found, .dashboard-table .status-unclaimed {
            color: #dc3545; /* Red */
            font-weight: 600;
        }

        .dashboard-table .status-found, .dashboard-table .status-claimed {
            color: #6c757d; /* Green */
            background-color: #d4edda;
            font-weight: 600;
        }

        .dashboard-table .status-pending_approval {
            color: #ffc107; /* Yellow/Orange */
            font-weight: 600;
        }

        .dashboard-table .status-rejected {
            color: #6c757d; /* Grey */
            font-weight: 600;
        }

        .dashboard-table .action-buttons a {
            display: inline-block;
            padding: 6px 12px;
            margin-right: 5px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .dashboard-table .action-buttons .view-btn {
            background-color: #007bff;
            color: white;
        }

        .dashboard-table .action-buttons .view-btn:hover {
            background-color: #0056b3;
        }

        .dashboard-table .action-buttons .edit-btn {
            background-color: #ffc107;
            color: #333;
        }

        .dashboard-table .action-buttons .edit-btn:hover {
            background-color: #e0a800;
        }

        .dashboard-table .action-buttons .mark-found-btn,
        .dashboard-table .action-buttons .mark-claimed-btn {
            background-color: #28a745;
            color: white;
        }

        .dashboard-table .action-buttons .mark-found-btn:hover,
        .dashboard-table .action-buttons .mark-claimed-btn:hover {
            background-color: #218838;
        }

        .dashboard-bottom-row {
            display: flex;
            gap: 30px;
            flex-wrap: wrap; /* Allow wrapping */
        }

        .dashboard-stats,
        .match-alerts {
            flex: 1;
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            min-width: 300px; /* Ensure they don't get too small */
        }

        .dashboard-stats h2,
        .match-alerts h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .dashboard-stats ul {
            list-style: none;
            padding: 0;
        }

        .dashboard-stats ul li {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
            font-size: 16px;
            color: #555;
        }

        .dashboard-stats ul li:last-child {
            border-bottom: none;
        }

        .dashboard-stats ul li span {
            font-weight: 600;
            color: #333;
        }

        .match-alerts p {
            font-size: 16px;
            color: #555;
            margin-bottom: 20px;
        }

        .match-alerts .alert-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .match-alerts .alert-actions .btn {
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            color: white;
        }

        .match-alerts .alert-actions .view-image-btn {
            background-color: #6c757d;
        }

        .match-alerts .alert-actions .view-image-btn:hover {
            background-color: #5a6268;
        }

        .match-alerts .alert-actions .contact-finder-btn {
            background-color: #17a2b8;
        }

        .match-alerts .alert-actions .contact-finder-btn:hover {
            background-color: #138496;
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .dashboard-container {
                flex-direction: column;
                margin: 15px;
            }

            .sidebar {
                width: 100%;
                padding: 20px;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
            }

            .sidebar .logo {
                margin-bottom: 0;
            }

            .user-profile {
                margin-bottom: 0;
                display: flex;
                align-items: center;
                gap: 15px;
            }

            .user-profile .avatar {
                width: 70px;
                height: 70px;
            }

            .user-profile .user-info {
                text-align: left;
            }

            .navigation {
                width: 100%;
                margin-top: 20px;
            }

            .navigation ul {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
            }

            .navigation ul li {
                margin-bottom: 0;
                width: auto;
            }

            .main-content {
                padding: 20px;
            }

            .main-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .search-bar {
                width: 100%;
                max-width: none;
            }

            .dashboard-bottom-row {
                flex-direction: column;
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                flex-direction: column;
                align-items: center;
            }

            .user-profile {
                flex-direction: column;
                text-align: center;
                margin-bottom: 20px;
            }

            .telegram-link-btn {
                margin-top: 10px;
            }

            .navigation ul {
                flex-direction: column;
                align-items: center;
            }
        }

        @media (max-width: 480px) {
            .dashboard-container {
                margin: 10px;
            }

            .sidebar .logo {
                font-size: 30px;
            }

            .user-profile .avatar {
                width: 60px;
                height: 60px;
            }

            .user-profile .user-info .user-name {
                font-size: 18px;
            }

            .user-profile .user-info .user-email {
                font-size: 12px;
            }

            .main-content {
                padding: 15px;
                gap: 20px;
            }

            .main-header h1 {
                font-size: 28px;
            }

            .header-icons .icon-btn {
                width: 35px;
                height: 35px;
                font-size: 18px;
            }

            .dashboard-section h2,
            .dashboard-stats h2,
            .match-alerts h2 {
                font-size: 20px;
            }

            .dashboard-table th,
            .dashboard-table td {
                padding: 8px 10px;
                font-size: 14px;
            }

            .dashboard-table .action-buttons a {
                padding: 4px 8px;
                font-size: 12px;
            }

            .dashboard-stats ul li {
                font-size: 14px;
            }

            .match-alerts p {
                font-size: 14px;
            }

            .match-alerts .alert-actions .btn {
                padding: 8px 12px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">FoundIt</div>
            <div class="user-profile">
                <?php if (!empty($user_profile_image_path) && file_exists($user_profile_image_path)): ?>
                    <img src="<?php echo htmlspecialchars(BASE_URL . $user_profile_image_path); ?>" alt="<?php echo htmlspecialchars($user_full_name); ?>" class="avatar">
                <?php else: ?>
                    <img src="https://placehold.co/100x100/8b1e1e/ffffff?text=<?php echo htmlspecialchars(substr($user_full_name, 0, 1)); ?>" alt="<?php echo htmlspecialchars($user_full_name); ?>" class="avatar">
                <?php endif; ?>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($user_full_name); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></div>
                    <?php if (!empty($user_telegram)): ?>
                    <?php endif; ?>
                </div>
            </div>
            <nav class="navigation">
                <ul>
                    <li><a href="homepage.php" class="active"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="main-header">
                <h1>Welcome, <?php echo htmlspecialchars($user_full_name); ?>!</h1>
                <div class="header-icons">
                    <a href="report_lost_form.php" class="icon-btn" title="Report Lost Item">
                        <i class="fas fa-exclamation-circle"></i>
                    </a>
                    <a href="report_found_form.php" class="icon-btn" title="Report Found Item">
                        <i class="fas fa-plus-circle"></i>
                    </a>
                </div>
            </div>

            <section class="dashboard-section">
                <h2>Your Reported Items</h2>
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Date Reported</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($user_lost_items) || !empty($user_found_items)): ?>
                            <?php
                            $all_user_items = array_merge(
                                array_map(function($item) { $item['type'] = 'lost'; return $item; }, $user_lost_items),
                                array_map(function($item) { $item['type'] = 'found'; return $item; }, $user_found_items)
                            );
                            // Sort by created_at descending
                            usort($all_user_items, function($a, $b) {
                                return strtotime($b['created_at']) - strtotime($a['created_at']);
                            });

                            foreach ($all_user_items as $item):
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($item['type'])); ?></td>
                                    <td class="status-<?php echo htmlspecialchars($item['status']); ?>">
                                        <?php echo formatStatus($item['status']); ?>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($item['created_at'])); ?></td>
                                    <td class="action-buttons">
                                        <a href="<?php echo htmlspecialchars($item['type']); ?>_item_view.php?id=<?php echo htmlspecialchars($item['id']); ?>" class="view-btn">View</a>
                                        <?php if ($item['status'] !== 'found' && $item['status'] !== 'claimed' && $item['status'] !== 'rejected'): // Only allow edit if not resolved or rejected ?>
                                            <a href="report_<?php echo htmlspecialchars($item['type']); ?>_form.php?id=<?php echo htmlspecialchars($item['id']); ?>" class="edit-btn">Edit</a>
                                        <?php endif; ?>
                                        <?php if ($item['type'] === 'lost' && $item['status'] === 'not_found'): ?>
                                            
                                        <?php elseif ($item['type'] === 'found' && $item['status'] === 'unclaimed'): ?>
                                            
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">You haven't reported any items yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <div class="dashboard-bottom-row">
                <section class="dashboard-stats">
                    <h2>Dashboard Stats</h2>
                    <ul>
                        <li>Items Reported (Lost) <span><?php echo count($user_lost_items); ?></span></li>
                        <li>Items Reported (Found) <span><?php echo count($user_found_items); ?></span></li>
                        <li>Total Reported Items <span><?php echo count($user_lost_items) + count($user_found_items); ?></span></li>
                        <!-- Placeholder for future features -->
                        <li>Pending Matches <span>0</span></li>
                        <li>Resolved Cases <span>0</span></li>
                    </ul>
                </section>

                <section class="match-alerts">
                    <h2>Match Alerts</h2>
                    <p>No new match alerts at the moment.</p>
                    <div class="alert-actions">
                        <!-- Placeholder buttons for future features -->
                        <button class="btn view-image-btn" onclick="alert('View Image functionality to be implemented.')"><i class="fas fa-image"></i> View Image</button>
                        <button class="btn contact-finder-btn" onclick="alert('Contact Finder functionality to be implemented.')"><i class="fas fa-user-circle"></i> Contact Finder</button>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        let currentStatusAction = '';
        let currentItemId = null;
        let currentItemType = '';

        function showConfirmation(action, itemId, itemType) {
            currentStatusAction = action;
            currentItemId = itemId;
            currentItemType = itemType;
            const modal = document.getElementById('confirmationModal');
            const message = document.getElementById('confirmationMessage');

            if (action === 'found') {
                message.textContent = "Are you sure you want to mark this lost item as 'Found'? This action cannot be undone.";
            } else if (action === 'claimed') {
                message.textContent = "Are you sure you want to mark this found item as 'Claimed by owner'? This action cannot be undone.";
            }
            modal.style.display = 'flex';
        }

        function hideConfirmation() {
            document.getElementById('confirmationModal').style.display = 'none';
            currentItemId = null;
            currentItemType = '';
            currentStatusAction = '';
        }

        document.getElementById('confirmAction').addEventListener('click', function() {
            hideConfirmation();
            if (currentItemId && currentItemType && currentStatusAction) {
                // Redirect to update status script with item ID, type, and new status
                window.location.href = `item_status.php?id=${currentItemId}&type=${currentItemType}&new_status=${currentStatusAction}`;
            }
        });

        // Close the modal if the user clicks outside of it
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('confirmationModal');
            if (event.target === modal) {
                hideConfirmation();
            }
        });
    </script>

    <?php include 'message_modal.php'; ?>

</body>
</html>
