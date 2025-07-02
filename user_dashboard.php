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

$conn = getDbConnection();

// Fetch user's telegram username
$stmt_user_info = $conn->prepare("SELECT telegram_username FROM users WHERE id = ?");
if ($stmt_user_info) {
    $stmt_user_info->bind_param("i", $user_id);
    $stmt_user_info->execute();
    $result_user_info = $stmt_user_info->get_result();
    if ($row = $result_user_info->fetch_assoc()) {
        $user_telegram = $row['telegram_username'] ?? '';
    }
    $stmt_user_info->close();
} else {
    error_log("Error preparing user info query: " . $conn->error);
}


// Fetch user's reported lost items
$user_lost_items = [];
$stmt_lost = $conn->prepare("SELECT id, item_name, description, date_lost, lost_location, category, status, created_at FROM lost_items WHERE user_id = ? ORDER BY created_at DESC");
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
    $_SESSION['error_message'] = "Could not retrieve your lost items.";
}

// Fetch user's reported found items
$user_found_items = [];
$stmt_found = $conn->prepare("SELECT id, item_name, description, date_found, found_location, category, status, created_at FROM found_items WHERE user_id = ? ORDER BY created_at DESC");
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
    $_SESSION['error_message'] = "Could not retrieve your found items.";
}

closeDbConnection($conn);

// Function to format status for display (re-using from view_item.php logic)
function formatUserLostStatus($status) {
    switch ($status) {
        case 'not_found': return '<span class="status-pending">❌ Not found</span>';
        case 'found': return '<span class="status-approved">✅ Found</span>';
        case 'pending_approval': return '<span class="status-pending">⏳ Pending Approval</span>';
        case 'rejected': return '<span class="status-rejected">🚫 Rejected</span>';
        default: return $status;
    }
}

function formatUserFoundStatus($status) {
    switch ($status) {
        case 'unclaimed': return '<span class="status-pending">❌ Unclaimed</span>';
        case 'claimed': return '<span class="status-approved">✅ Claimed by owner</span>';
        case 'pending_approval': return '<span class="status-pending">⏳ Pending Approval</span>';
        case 'rejected': return '<span class="status-rejected">🚫 Rejected</span>';
        default: return $status;
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
        /* Re-use and adapt styles from unified_styles.css and admin_homepage.php */
        body {
            background-color: #f5ff9c;
            display: block;
            height: auto;
            padding: 0; /* Remove padding from body as container will handle it */
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
            background-color: #fffdd0;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            max-width: 1200px;
            margin: 20px auto;
        }

        .sidebar {
            width: 250px;
            background-color: #8b1e1e; /* Dark red */
            color: white;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar .logo {
            font-size: 30px;
            font-weight: 800;
            margin-bottom: 30px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }

        .user-profile {
            text-align: center;
            margin-bottom: 40px;
        }

        .user-profile .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid white;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .user-profile .user-info {
            margin-top: 10px;
        }

        .user-profile .user-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .user-profile .user-email {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 10px;
        }

        .telegram-link-btn {
            background-color: #0088cc; /* Telegram blue */
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.3s ease;
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
        }

        .navigation ul li a {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 8px;
            transition: background-color 0.3s ease, color 0.3s ease;
            font-weight: 500;
        }

        .navigation ul li a:hover,
        .navigation ul li a.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: #f5ff9c; /* Light yellow for active/hover text */
        }

        .navigation ul li a i {
            font-size: 18px;
        }

        .main-content {
            flex-grow: 1;
            padding: 30px;
            background-color: #fefefe;
        }

        .main-content h1 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        .dashboard-section {
            background-color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .dashboard-section h2 {
            font-size: 22px;
            color: #333;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .dashboard-table table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .dashboard-table th,
        .dashboard-table td {
            text-align: left;
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        .dashboard-table th {
            background-color: #f8f8f8;
            font-weight: 600;
            color: #555;
            text-transform: uppercase;
        }

        .dashboard-table tbody tr:hover {
            background-color: #f1f1f1;
        }

        .dashboard-table .status-pending {
            color: #d39e00; /* Yellowish */
            font-weight: 600;
        }

        .dashboard-table .status-approved {
            color: #28a745; /* Green */
            font-weight: 600;
        }

        .dashboard-table .status-rejected {
            color: #dc3545; /* Red */
            font-weight: 600;
        }

        .dashboard-bottom-row {
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
        }

        .dashboard-stats, .match-alerts {
            flex: 1;
            min-width: 300px;
            background-color: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .dashboard-stats h2, .match-alerts h2 {
            font-size: 20px;
            color: #333;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .dashboard-stats ul {
            list-style: none;
            padding: 0;
        }

        .dashboard-stats ul li {
            margin-bottom: 10px;
            font-size: 15px;
            color: #555;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dashboard-stats ul li span {
            font-weight: 600;
            color: #8b1e1e;
        }

        .match-alerts p {
            font-size: 15px;
            color: #555;
            margin-bottom: 20px;
        }

        .alert-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .alert-actions .btn {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.3s ease;
            border: none; /* Ensure no default button border */
            cursor: pointer;
        }

        .alert-actions .btn:hover {
            background-color: #0056b3;
        }

        .alert-actions .contact-finder-btn {
            background-color: #28a745;
        }

        .alert-actions .contact-finder-btn:hover {
            background-color: #218838;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
                margin: 0;
                border-radius: 0;
                box-shadow: none;
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
                width: 50px;
                height: 50px;
            }

            .user-profile .user-info {
                text-align: left;
            }

            .user-profile .user-email, .telegram-link-btn {
                display: none; /* Hide email and telegram button on small screens for brevity */
            }

            .navigation {
                width: 100%;
                margin-top: 20px;
            }

            .navigation ul {
                display: flex;
                justify-content: space-around;
                flex-wrap: wrap;
            }

            .navigation ul li {
                margin-bottom: 10px;
            }

            .navigation ul li a {
                padding: 8px 10px;
                font-size: 14px;
                gap: 8px;
            }

            .main-content {
                padding: 20px;
            }

            .dashboard-table th,
            .dashboard-table td {
                display: block;
                width: 100%;
                text-align: right;
                padding-left: 50%;
                position: relative;
            }

            .dashboard-table td::before {
                content: attr(data-label);
                position: absolute;
                left: 6px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: 600;
            }

            .dashboard-table thead {
                display: none;
            }

            .dashboard-table tr {
                margin-bottom: 15px;
                border: 1px solid #eee;
                display: block;
                border-radius: 8px;
                padding: 10px 0;
            }

            .dashboard-bottom-row {
                flex-direction: column;
            }

            .dashboard-stats, .match-alerts {
                min-width: unset;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">FoundIt</div>
            <div class="user-profile">
                <img src="https://placehold.co/80x80/cccccc/000000?text=<?php echo substr($user_full_name, 0, 1); ?>" alt="<?php echo htmlspecialchars($user_full_name); ?>" class="avatar">
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($user_full_name); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($user_email); ?></div>
                    <?php if (!empty($user_telegram)): ?>
                        <a href="https://t.me/<?php echo htmlspecialchars(ltrim($user_telegram, '@')); ?>" target="_blank" class="telegram-link-btn">
                            <i class="fab fa-telegram-plane"></i> Message on Telegram
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <nav class="navigation">
                <ul>
                    <li><a href="homepage.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="user_dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                    <li><a href="profile.php"><i class="fas fa-user-circle"></i> Profile</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <h1>Your Dashboard</h1>

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

            <section class="dashboard-section dashboard-table">
                <h2>Your Reported Lost Items</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Date Lost</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Date Reported</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($user_lost_items)): ?>
                            <?php foreach ($user_lost_items as $item): ?>
                                <tr>
                                    <td data-label="Item Name"><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td data-label="Category"><?php echo htmlspecialchars($item['category']); ?></td>
                                    <td data-label="Date Lost"><?php echo htmlspecialchars($item['date_lost']); ?></td>
                                    <td data-label="Location"><?php echo htmlspecialchars($item['lost_location']); ?></td>
                                    <td data-label="Status"><?php echo formatUserLostStatus($item['status']); ?></td>
                                    <td data-label="Date Reported"><?php echo date('d M Y', strtotime($item['created_at'])); ?></td>
                                    <td data-label="Actions">
                                        <a href="lost_item_view.php?id=<?php echo htmlspecialchars($item['id']); ?>" class="btn" style="background-color: #007bff; padding: 8px 12px; font-size: 13px; text-decoration: none; color: white; border-radius: 5px;">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">You haven't reported any lost items yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <section class="dashboard-section dashboard-table">
                <h2>Your Reported Found Items</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Date Found</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Date Reported</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($user_found_items)): ?>
                            <?php foreach ($user_found_items as $item): ?>
                                <tr>
                                    <td data-label="Item Name"><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td data-label="Category"><?php echo htmlspecialchars($item['category']); ?></td>
                                    <td data-label="Date Found"><?php echo htmlspecialchars($item['date_found']); ?></td>
                                    <td data-label="Location"><?php echo htmlspecialchars($item['found_location']); ?></td>
                                    <td data-label="Status"><?php echo formatUserFoundStatus($item['status']); ?></td>
                                    <td data-label="Date Reported"><?php echo date('d M Y', strtotime($item['created_at'])); ?></td>
                                    <td data-label="Actions">
                                        <a href="found_item_view.php?id=<?php echo htmlspecialchars($item['id']); ?>" class="btn" style="background-color: #007bff; padding: 8px 12px; font-size: 13px; text-decoration: none; color: white; border-radius: 5px;">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">You haven't reported any found items yet.</td>
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

    <?php include 'message_modal.php'; ?>

</body>
</html>
