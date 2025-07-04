<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['error_message'] = "You must be logged in as an administrator to access this page.";
    header("Location: login.php");
    exit();
}

$admin_full_name = $_SESSION['full_name'] ?? 'Admin';

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    
    // Prevent admin from deleting themselves
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['error_message'] = "You cannot delete your own admin account.";
        header("Location: admin_user.php");
        exit();
    }
    
    $conn = getDbConnection();
    
    try {
        $conn->begin_transaction();
        
        // Delete user's items
        $stmt_items = $conn->prepare("DELETE FROM lost_items WHERE user_id = ?");
        $stmt_items->bind_param("i", $user_id);
        $stmt_items->execute();
        
        $stmt_items = $conn->prepare("DELETE FROM found_items WHERE user_id = ?");
        $stmt_items->bind_param("i", $user_id);
        $stmt_items->execute();
        
        // Delete user
        $stmt_user = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt_user->bind_param("i", $user_id);
        $stmt_user->execute();
        
        $conn->commit();
        $_SESSION['success_message'] = "User and all their items deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error deleting user: " . $e->getMessage();
    }
    
    closeDbConnection($conn);
    header("Location: admin_user.php");
    exit();
}

// Get all users
$conn = getDbConnection();
$users = [];
$result = $conn->query("SELECT id, full_name, email, phone_number, created_at FROM users ORDER BY created_at DESC");
if ($result) {
    $users = $result->fetch_all(MYSQLI_ASSOC);
}
closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | FoundIt</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --danger: #f72585;
            --success: #4cc9f0;
            --warning: #ff9f1c;
            --dark: #212529;
            --light: #f8f9fa;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --border-radius: 0.5rem;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --transition: all 0.15s ease-in-out;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .admin-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
        }

        .admin-header .admin-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .admin-name {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid transparent;
        }

        .btn i {
            font-size: 0.9rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background-color: #e5177a;
        }

        .btn-success {
            background-color: var(--success);
            color: white;
        }

        .btn-success:hover {
            background-color: #3ab8dd;
        }

        .search-export-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            gap: 1rem;
        }

        .search-box {
            flex-grow: 1;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-light);
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.2);
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        .card-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .card-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
        }

        .card-header h2 i {
            margin-right: 0.5rem;
        }

        .badge {
            background-color: var(--light);
            color: var(--gray);
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .admin-table thead th {
            background-color: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 500;
            color: var(--gray);
            border-bottom: 2px solid var(--gray-light);
        }

        .admin-table tbody tr {
            border-bottom: 1px solid var(--gray-light);
            transition: var(--transition);
        }

        .admin-table tbody tr:last-child {
            border-bottom: none;
        }

        .admin-table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .admin-table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .actions-cell {
            display: flex;
            gap: 0.5rem;
        }

        .empty-state {
            padding: 2rem;
            text-align: center;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }

        .empty-state p {
            margin-bottom: 1rem;
        }

        /* Confirmation Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: var(--transition);
        }

        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-content {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
            padding: 1.5rem;
            transform: translateY(20px);
            transition: var(--transition);
        }

        .modal-overlay.active .modal-content {
            transform: translateY(0);
        }

        .modal-header {
            margin-bottom: 1rem;
        }

        .modal-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
        }

        .modal-header h3 i {
            margin-right: 0.5rem;
        }

        .modal-body {
            margin-bottom: 1.5rem;
            color: var(--gray);
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .search-export-container {
                flex-direction: column;
            }
            
            .export-actions {
                justify-content: flex-end;
            }
            
            .admin-table {
                display: block;
                overflow-x: auto;
            }
        }

        @media (max-width: 576px) {
            .admin-container {
                padding: 1rem;
            }
            
            .modal-content {
                margin: 0 1rem;
            }
            
            .actions-cell {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1><i class="fas fa-shield-alt"></i> Admin Dashboard</h1>
            <div class="admin-info">
                <span class="admin-name"><i class="fas fa-user"></i> <?= htmlspecialchars($admin_full_name) ?></span>
                <button class="btn btn-danger" onclick="window.location.href='logout.php'">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </header>

        <div class="card-container">
            <div class="card-header">
                <h2><i class="fas fa-users"></i> User Management</h2>
                <span class="badge"><?= count($users) ?> users</span>
            </div>
            
            <div class="search-export-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="user-search" placeholder="Search users...">
                </div>
                <div class="export-actions">
                    <form method="POST" action="admin_homepage.php">
                        <button type="submit" name="export_users_excel" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export Users
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr class="user-row">
                                    <td><?= htmlspecialchars($user['id']) ?></td>
                                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['phone_number'] ?? 'N/A') ?></td>
                                    <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                    <td class="actions-cell">
                                        <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                            <span class="btn btn-light"><i class="fas fa-crown"></i> Current Admin</span>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" name="delete_user" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user and all their items?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="fas fa-user-slash"></i>
                                    <p>No registered users found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // User search functionality
        document.getElementById('user-search').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.user-row').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
