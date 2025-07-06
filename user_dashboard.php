<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to access your dashboard.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_full_name = $_SESSION['full_name'] ?? 'User ';
$user_email = $_SESSION['email'] ?? '';
$user_telegram = '';
$user_profile_image_path = '';

$conn = getDbConnection();

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
}

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
}

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
}

closeDbConnection($conn);

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
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User Dashboard</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      margin: 0;
      background: #f5ff9c;
    }

    .dashboard-container {
      display: flex;
      flex-direction: row;
      min-height: 100vh;
      background-color: #fffdd0;
    }

    .sidebar {
      width: 250px;
      background-color: #8b1e1e;
      color: white;
      padding: 30px 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .logo {
      font-size: 32px;
      font-weight: bold;
      text-align: center;
    }

    .user-profile {
      text-align: center;
      margin: 20px 0;
    }

    .avatar {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      object-fit: cover;
      margin-bottom: 10px;
      border: 3px solid white;
    }

    .user-info {
      color: white;
    }

    .navigation ul {
      list-style: none;
      padding: 0;
      width: 100%;
    }

    .navigation ul li {
      margin: 10px 0;
    }

    .navigation ul li a {
      display: block;
      color: white;
      text-decoration: none;
      padding: 10px;
      border-radius: 6px;
      background-color: rgba(255,255,255,0.1);
      transition: background 0.3s;
    }

    .navigation ul li a:hover {
      background-color: rgba(255,255,255,0.3);
    }

    .main-content {
      flex: 1;
      padding: 30px;
    }

    .main-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
    }

    .header-icons {
      display: flex;
      gap: 10px;
    }

    .icon-btn {
      background: white;
      border: 2px solid #000;
      width: 40px;
      height: 40px;
      display: flex;
      justify-content: center;
      align-items: center;
      border-radius: 50%;
      text-decoration: none;
      color: black;
      font-size: 18px;
    }

    .dashboard-section {
      margin-top: 30px;
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .table-container {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    th, td {
      padding: 12px 15px;
      border-bottom: 1px solid #ccc;
      text-align: left;
    }

    th {
      background: #f2f2f2;
    }

    @media (max-width: 768px) {
      .dashboard-container {
        flex-direction: column;
      }

      .sidebar {
        width: 100%;
        align-items: center;
        text-align: center;
      }

      .main-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }

      .user-profile {
        margin-top: 10px;
      }
    }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <aside class="sidebar">
      <div class="logo">FoundIt</div>
      <div class="user-profile">
        <img src="<?php echo $user_profile_image_path ?: 'https://placehold.co/100x100/8b1e1e/ffffff?text=' . htmlspecialchars(substr($user_full_name, 0, 1)); ?>" class="avatar">
        <div class="user-info">
          <strong><?php echo htmlspecialchars($user_full_name); ?></strong><br>
          <?php echo htmlspecialchars($user_email); ?>
        </div>
      </div>
      <nav class="navigation">
        <ul>
          <li><a href="homepage.php"><i class="fas fa-home"></i> Home</a></li>
          <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
          <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
      </nav>
    </aside>

    <main class="main-content">
      <div class="main-header">
        <h1>Welcome, <?php echo htmlspecialchars($user_full_name); ?>!</h1>
        <div class="header-icons">
          <a href="report_lost_form.php" class="icon-btn" title="Report Lost"><i class="fas fa-exclamation-circle"></i></a>
          <a href="report_found_form.php" class="icon-btn" title="Report Found"><i class="fas fa-plus-circle"></i></a>
        </div>
      </div>

      <section class="dashboard-section">
        <h2>Your Reported Items</h2>
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>Item</th>
                <th>Type</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $all_items = array_merge(
                  array_map(fn($i) => $i + ['type' => 'lost'], $user_lost_items),
                  array_map(fn($i) => $i + ['type' => 'found'], $user_found_items)
              );
              usort($all_items, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
              if (empty($all_items)):
              ?>
                <tr><td colspan="5" style="text-align:center;">No items reported yet.</td></tr>
              <?php else:
                  foreach ($all_items as $item): ?>
                <tr>
                  <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                  <td><?php echo ucfirst($item['type']); ?></td>
                  <td><?php echo formatStatus($item['status']); ?></td>
                  <td><?php echo date('d M Y', strtotime($item['created_at'])); ?></td>
                  <td>
                    <a href="<?php echo $item['type']; ?>_item_view.php?id=<?php echo $item['id']; ?>">View</a>
                    <?php if (!in_array($item['status'], ['found', 'claimed', 'rejected'])): ?>
                      <a href="report_<?php echo $item['type']; ?>_form.php?id=<?php echo $item['id']; ?>">Edit</a>
                      <form method="POST" action="delete_item.php" style="display:inline;">
                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                        <button type="submit" onclick="return confirm('Delete this item?');">Delete</button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</body>
</html>