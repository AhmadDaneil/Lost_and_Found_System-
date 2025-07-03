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
    header("Location: login.php"); // Updated from login.html to login.php
    exit();
}

$admin_full_name = $_SESSION['full_name'] ?? 'Admin';

$conn = getDbConnection();

// Fetch all users
$users = [];
$stmt_users = $conn->prepare("SELECT id, full_name, email, phone_number, user_type, created_at FROM users ORDER BY created_at DESC");
if ($stmt_users) {
    $stmt_users->execute();
    $result_users = $stmt_users->get_result();
    while ($row = $result_users->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt_users->close();
} else {
    error_log("Error preparing users query for admin: " . $conn->error);
    $_SESSION['error_message'] = "Could not retrieve user list.";
}

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin User Management - FoundIt</title>
    <link rel="stylesheet" href="unified_styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Re-use admin dashboard styles, add specific ones if necessary */
        body {
            background-color: #f5ff9c;
            display: block;
            height: auto;
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
            background-color: #8b1e1e;
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
            text-decoration: none;
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

        .view-user-btn {
            background-color: #007bff;
        }
        .view-user-btn:hover {
            background-color: #0056b3;
        }

        .edit-user-btn {
            background-color: #ffc107; /* Yellow */
            color: #333;
        }
        .edit-user-btn:hover {
            background-color: #e0a800;
        }

        .delete-user-btn {
            background-color: #dc3545; /* Red */
        }
        .delete-user-btn:hover {
            background-color: #c82333;
        }

        /* Responsive adjustments (from admin_homepage.php) */
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
                border: 1px solid var(--border-color);
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

        /* Message Box / Modal Styles */
        .message-box {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            justify-content: center;
            align-items: center;
            padding-top: 50px; /* To center vertically */
        }

        .message-box-content {
            background-color: #fefefe;
            margin: auto;
            padding: 30px;
            border: 1px solid #888;
            border-radius: 10px;
            width: 80%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            animation-name: animatetop;
            animation-duration: 0.4s;
        }

        @keyframes animatetop {
            from {top: -300px; opacity: 0}
            to {top: 0; opacity: 1}
        }

        .message-box-content h3 {
            margin-top: 0;
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
        }

        .message-box-content p {
            margin: 20px 0;
            font-size: 16px;
            color: #555;
            margin-bottom: 25px;
        }

        .message-box-content .button-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .message-box-content .confirm-btn,
        .message-box-content .cancel-btn {
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s ease;
            border: none;
        }

        .message-box-content .confirm-btn {
            background-color: #dc3545; /* Red for delete */
            color: white;
        }

        .message-box-content .confirm-btn:hover {
            background-color: #c82333;
        }

        .message-box-content .cancel-btn {
            background-color: #6c757d; /* Gray for cancel */
            color: white;
        }

        .message-box-content .cancel-btn:hover {
            background-color: #5a6268;
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
            <h1>Admin Dashboard - User Management</h1>

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

            <section class="admin-section admin-table-section">
                <h2>All Users</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone Number</th>
                            <th>User Type</th>
                            <th>Registered On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td data-label="ID"><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td data-label="Full Name"><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td data-label="Email"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td data-label="Phone Number"><?php echo htmlspecialchars($user['phone_number'] ?? 'N/A'); ?></td>
                                    <td data-label="User Type"><?php echo ucfirst(htmlspecialchars($user['user_type'])); ?></td>
                                    <td data-label="Registered On"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                    <td data-label="Actions">
                                        <a href="admin_edit_user.php?id=<?php echo htmlspecialchars($user['id']); ?>" class="admin-btn edit-user-btn" style="padding: 8px 12px; font-size: 13px; margin-right: 5px;">Edit</a>
                                        <button type="button" class="admin-btn delete-user-btn" style="padding: 8px 12px; font-size: 13px;" onclick="showConfirmation(<?php echo htmlspecialchars($user['id']); ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="message-box">
      <div class="message-box-content">
        <h3>Confirm Deletion</h3>
        <p id="confirmationMessage"></p>
        <div class="button-group">
          <button class="confirm-btn" id="confirmDeleteAction">Yes, Delete</button>
          <button class="cancel-btn" onclick="hideConfirmation()">Cancel</button>
        </div>
      </div>
    </div>

    <script>
        let userIdToDelete = null; // Variable to store the ID of the user to be deleted

        function showConfirmation(userId, userName) {
            userIdToDelete = userId;
            const modal = document.getElementById('confirmationModal');
            const message = document.getElementById('confirmationMessage');
            message.innerHTML = `Are you sure you want to delete user: <strong>${userName}</strong> (ID: ${userId})? This action cannot be undone.`;
            modal.style.display = 'flex'; // Use flex to center content
        }

        function hideConfirmation() {
            document.getElementById('confirmationModal').style.display = 'none';
            userIdToDelete = null; // Clear the stored ID
        }

        document.getElementById('confirmDeleteAction').addEventListener('click', function() {
            if (userIdToDelete) {
                hideConfirmation();
                // Redirect to the delete script with the user ID
                window.location.href = `admin_delete_user.php?id=${userIdToDelete}`;
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
</body>
</html>
