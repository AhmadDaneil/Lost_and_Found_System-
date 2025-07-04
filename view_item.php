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

// Get item details
$item_id = $_GET['id'] ?? null;
$item_type = $_GET['type'] ?? null;

if (!$item_id || !$item_type) {
    $_SESSION['error_message'] = "Invalid item request.";
    header("Location: admin_homepage.php");
    exit();
}

$conn = getDbConnection();

// Get item details based on type
$item = null;
$user = null;

if ($item_type === 'lost') {
    $stmt = $conn->prepare("SELECT * FROM lost_items WHERE id = ?");
} else {
    $stmt = $conn->prepare("SELECT * FROM found_items WHERE id = ?");
}

$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
$stmt->close();

if (!$item) {
    $_SESSION['error_message'] = "Item not found.";
    header("Location: admin_homepage.php");
    exit();
}

// Get user details
$stmt_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->bind_param("i", $item['user_id']);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

closeDbConnection($conn);

function formatStatus($status, $item_type) {
    $statusClasses = [
        'lost' => [
            'not_found' => ['class' => 'status-not-found', 'text' => 'Not Found'],
            'found' => ['class' => 'status-found', 'text' => 'Found'],
            'pending_approval' => ['class' => 'status-pending', 'text' => 'Pending Approval'],
            'rejected' => ['class' => 'status-rejected', 'text' => 'Rejected']
        ],
        'found' => [
            'unclaimed' => ['class' => 'status-not-found', 'text' => 'Unclaimed'],
            'claimed' => ['class' => 'status-found', 'text' => 'Claimed'],
            'pending_approval' => ['class' => 'status-pending', 'text' => 'Pending Approval'],
            'rejected' => ['class' => 'status-rejected', 'text' => 'Rejected']
        ]
    ];

    $statusInfo = $statusClasses[$item_type][$status] ?? 
                 ['class' => '', 'text' => ucfirst(str_replace('_', ' ', $status))];

    return sprintf(
        '<span class="status-badge %s">%s</span>',
        $statusInfo['class'],
        $statusInfo['text']
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Item | FoundIt</title>
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

        .btn-light {
            background-color: var(--light);
            color: var(--dark);
            border: 1px solid var(--gray-light);
        }

        .btn-light:hover {
            background-color: #e2e6ea;
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

        .item-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        @media (max-width: 768px) {
            .item-details {
                grid-template-columns: 1fr;
            }
        }

        .detail-group {
            margin-bottom: 1rem;
        }

        .detail-label {
            font-weight: 500;
            color: var(--gray);
            margin-bottom: 0.25rem;
            display: block;
        }

        .detail-value {
            font-size: 1rem;
            padding: 0.5rem;
            background-color: var(--light);
            border-radius: var(--border-radius);
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-not-found {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-found {
            background-color: #d4edda;
            color: #155724;
        }

        .status-pending {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }

        .actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        /* Responsive styles */
        @media (max-width: 576px) {
            .admin-container {
                padding: 1rem;
            }
            
            .actions {
                flex-direction: column;
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
                <h2><i class="fas fa-box"></i> Item Details</h2>
                <span class="detail-value"><?= ucfirst($item_type) ?> Item #<?= htmlspecialchars($item['id']) ?></span>
            </div>
            
            <div class="item-details">
                <div>
                    <div class="detail-group">
                        <span class="detail-label">Item Name</span>
                        <div class="detail-value"><?= htmlspecialchars($item['item_name']) ?></div>
                    </div>
                    
                    <div class="detail-group">
                        <span class="detail-label">Category</span>
                        <div class="detail-value"><?= htmlspecialchars($item['category']) ?></div>
                    </div>
                    
                    <div class="detail-group">
                        <span class="detail-label">Status</span>
                        <div class="detail-value"><?= formatStatus($item['status'], $item_type) ?></div>
                    </div>
                    
                    <div class="detail-group">
                        <span class="detail-label">Location</span>
                        <div class="detail-value"><?= htmlspecialchars($item_type === 'lost' ? $item['lost_location'] : $item['found_location']) ?></div>
                    </div>
                </div>
                
                <div>
                    <div class="detail-group">
                        <span class="detail-label">Description</span>
                        <div class="detail-value"><?= nl2br(htmlspecialchars($item['description'])) ?></div>
                    </div>
                    
                    <div class="detail-group">
                        <span class="detail-label">Reported By</span>
                        <div class="detail-value">
                            <?= htmlspecialchars($user['full_name'] ?? 'Unknown') ?><br>
                            (User ID: <?= htmlspecialchars($item['user_id']) ?>)
                        </div>
                    </div>
                    
                    <div class="detail-group">
                        <span class="detail-label">Date Reported</span>
                        <div class="detail-value"><?= date('M j, Y h:i A', strtotime($item['created_at'])) ?></div>
                    </div>
                </div>
            </div>
            
            <div class="actions">
                <a href="admin_homepage.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="admin_delete_item.php?id=<?= $item['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this item?')">
                    <i class="fas fa-trash"></i> Delete Item
                </a>
            </div>
        </div>
    </div>
</body>
</html>
