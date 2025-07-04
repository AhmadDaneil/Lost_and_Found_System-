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

// Handle Excel exports
function exportToExcel($data, $filename, $headers) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="'.$filename.'_'.date('Y-m-d').'.xls"');
    
    echo implode("\t", $headers) . "\n";
    
    foreach ($data as $row) {
        $rowData = [];
        foreach (array_keys($headers) as $key) {
            $rowData[] = $row[$key] ?? '';
        }
        echo implode("\t", $rowData) . "\n";
    }
    
    exit();
}

// Handle Excel export for items
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_excel'])) {
    $conn = getDbConnection();
    
    $sql = "(SELECT id, item_name, description, status, created_at, user_id, lost_location AS location, category, 'Lost' AS item_type FROM lost_items)
            UNION ALL
            (SELECT id, item_name, description, status, created_at, user_id, found_location AS location, category, 'Found' AS item_type FROM found_items)
            ORDER BY created_at DESC";
    
    $result = $conn->query($sql);
    $items = $result->fetch_all(MYSQLI_ASSOC);
    
    $headers = [
        'id' => 'ID',
        'item_name' => 'Item Name',
        'item_type' => 'Type',
        'description' => 'Description',
        'status' => 'Status',
        'location' => 'Location',
        'category' => 'Category',
        'created_at' => 'Reported Date'
    ];
    
    exportToExcel($items, 'lost_and_found_report', $headers);
}

// Handle Excel export for users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_users_excel'])) {
    $conn = getDbConnection();
    $result = $conn->query("SELECT id, full_name, email, phone_number, created_at FROM users ORDER BY created_at DESC");
    $users = $result->fetch_all(MYSQLI_ASSOC);
    
    $headers = [
        'id' => 'ID',
        'full_name' => 'Full Name',
        'email' => 'Email',
        'phone_number' => 'Phone',
        'created_at' => 'Registration Date'
    ];
    
    exportToExcel($users, 'users_report', $headers);
}

// Fetch data for display
$conn = getDbConnection();

// Get search query (for items)
$search_query = $_GET['search'] ?? '';

// Get all users
$users = [];
$users_result = $conn->query("SELECT id, full_name, email, phone_number, created_at FROM users ORDER BY created_at DESC");
if ($users_result) {
    $users = $users_result->fetch_all(MYSQLI_ASSOC);
}

// Get all reported items
$all_reported_items = [];
$sql_lost = "SELECT id, item_name, description, status, created_at, user_id, lost_location AS location, category FROM lost_items";
$sql_found = "SELECT id, item_name, description, status, created_at, user_id, found_location AS location, category FROM found_items";

if (!empty($search_query)) {
    $search_param = '%' . $search_query . '%';
    $sql_lost .= " WHERE item_name LIKE ? OR description LIKE ? OR lost_location LIKE ? OR category LIKE ?";
    $sql_found .= " WHERE item_name LIKE ? OR description LIKE ? OR found_location LIKE ? OR category LIKE ?";
}

// Fetch lost items
$stmt_lost = $conn->prepare($sql_lost . " ORDER BY created_at DESC");
if ($stmt_lost) {
    if (!empty($search_query)) {
        $stmt_lost->bind_param('ssss', $search_param, $search_param, $search_param, $search_param);
    }
    $stmt_lost->execute();
    $result_lost = $stmt_lost->get_result();
    while ($row = $result_lost->fetch_assoc()) {
        $row['item_type'] = 'Lost';
        $all_reported_items[] = $row;
    }
    $stmt_lost->close();
}

// Fetch found items
$stmt_found = $conn->prepare($sql_found . " ORDER BY created_at DESC");
if ($stmt_found) {
    if (!empty($search_query)) {
        $stmt_found->bind_param('ssss', $search_param, $search_param, $search_param, $search_param);
    }
    $stmt_found->execute();
    $result_found = $stmt_found->get_result();
    while ($row = $result_found->fetch_assoc()) {
        $row['item_type'] = 'Found';
        $all_reported_items[] = $row;
    }
    $stmt_found->close();
}

// Get user names for displayed items
$user_names = [];
if (!empty($all_reported_items)) {
    $user_ids = array_unique(array_column($all_reported_items, 'user_id'));
    if (!empty($user_ids)) {
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
        }
    }
}

closeDbConnection($conn);

function formatStatus($status, $item_type) {
    $statusClasses = [
        'Lost' => [
            'not_found' => ['class' => 'status-not-found', 'text' => 'Not Found'],
            'found' => ['class' => 'status-found', 'text' => 'Found'],
            'pending_approval' => ['class' => 'status-pending', 'text' => 'Pending Approval'],
            'rejected' => ['class' => 'status-rejected', 'text' => 'Rejected']
        ],
        'Found' => [
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
    <title>Admin Dashboard | FoundIt</title>
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

        .tab-container {
            margin-bottom: 1.5rem;
        }

        .tab-buttons {
            display: flex;
            border-bottom: 1px solid var(--gray-light);
        }

        .tab-btn {
            padding: 0.75rem 1.5rem;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: var(--gray);
            position: relative;
            transition: var(--transition);
        }

        .tab-btn.active {
            color: var(--primary);
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--primary);
        }

        .tab-btn i {
            margin-right: 0.5rem;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
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

        .export-actions {
            display: flex;
            gap: 0.75rem;
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

        <div class="tab-container">
            <div class="tab-buttons">
                <button class="tab-btn active" onclick="showTab('items-tab')">
                    <i class="fas fa-box"></i> Reported Items
                </button>
                <button class="tab-btn" onclick="showTab('users-tab')">
                    <i class="fas fa-users"></i> User Management
                </button>
            </div>
        </div>

        <!-- Reported Items Tab -->
        <div id="items-tab" class="tab-content active">
            <div class="search-export-container">
                <form action="admin_homepage.php" method="GET" class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search items..." value="<?= htmlspecialchars($search_query) ?>">
                </form>
                <div class="export-actions">
                    <form method="POST">
                        <button type="submit" name="export_excel" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export Items
                        </button>
                    </form>
                </div>
            </div>

            <div class="card-container">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> Reported Items</h2>
                    <span class="badge"><?= count($all_reported_items) ?> items</span>
                </div>
                
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Item Name</th>
                                <th>Type</th>
                                <th>Reported By</th>
                                <th>Status</th>
                                <th>Date Reported</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_reported_items)): ?>
                                <?php foreach ($all_reported_items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['id']) ?></td>
                                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                                        <td><?= htmlspecialchars($item['item_type']) ?></td>
                                        <td><?= htmlspecialchars($user_names[$item['user_id']] ?? 'N/A') ?></td>
                                        <td><?= formatStatus($item['status'], $item['item_type']) ?></td>
                                        <td><?= date('M j, Y h:i A', strtotime($item['created_at'])) ?></td>
                                        <td class="actions-cell">
                                            <a href="view_item.php?id=<?= htmlspecialchars($item['id']) ?>&type=<?= strtolower(htmlspecialchars($item['item_type'])) ?>" class="btn btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <button class="btn btn-danger" onclick="showDeleteConfirmation(<?= htmlspecialchars($item['id']) ?>, '<?= htmlspecialchars($item['item_name']) ?>', 'item')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="empty-state">
                                        <i class="fas fa-box-open"></i>
                                        <p>No items found <?= !empty($search_query) ? 'matching your search' : '' ?></p>
                                        <?php if (!empty($search_query)): ?>
                                            <a href="admin_homepage.php" class="btn btn-primary">
                                                <i class="fas fa-times"></i> Clear search
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- User Management Tab -->
        <div id="users-tab" class="tab-content">
            <div class="search-export-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="user-search" placeholder="Search users...">
                </div>
                <div class="export-actions">
                    <form method="POST">
                        <button type="submit" name="export_users_excel" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export Users
                        </button>
                    </form>
                </div>
            </div>

            <div class="card-container">
                <div class="card-header">
                    <h2><i class="fas fa-users"></i> Registered Users</h2>
                    <span class="badge"><?= count($users) ?> users</span>
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
                                        <td class="user-actions">
                                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                <span class="btn btn-light"><i class="fas fa-crown"></i> Current Admin</span>
                                            <?php else: ?>
                                                <button class="btn btn-danger" onclick="showDeleteConfirmation(<?= $user['id'] ?>, '<?= htmlspecialchars($user['full_name']) ?>', 'user')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
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
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
            </div>
            <div class="modal-body" id="deleteModalMessage">
                Are you sure you want to delete this item?
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" onclick="hideModal()">Cancel</button>
                <a href="#" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i> Confirm Delete
                </a>
            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        function showTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            document.getElementById(tabId).classList.add('active');
            event.target.classList.add('active');
        }

        // Delete confirmation modal
        let currentItemToDelete = null;
        let deleteType = 'item';
        
        function showDeleteConfirmation(id, name, type) {
            deleteType = type;
            currentItemToDelete = id;
            
            const modalMessage = document.getElementById('deleteModalMessage');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            
            if (type === 'item') {
                modalMessage.innerHTML = `
                    <p>You are about to delete:</p>
                    <p><strong>${name}</strong> (ID: ${id})</p>
                    <p class="text-danger">This action cannot be undone!</p>
                `;
                confirmBtn.href = `admin_delete_item.php?id=${id}`;
            } else {
                modalMessage.innerHTML = `
                    <p>You are about to delete user:</p>
                    <p><strong>${name}</strong> (ID: ${id})</p>
                    <p class="text-danger">This will permanently delete the user account and all their items!</p>
                `;
                confirmBtn.href = `admin_delete_user.php?id=${id}`;
            }
            
            document.getElementById('deleteModal').classList.add('active');
        }

        function hideModal() {
            document.getElementById('deleteModal').classList.remove('active');
            currentItemToDelete = null;
        }
        
        // User search functionality
        document.getElementById('user-search').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.user-row').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideModal();
            }
        });
    </script>
</body>
</html>
