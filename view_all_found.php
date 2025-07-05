<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to view items.";
    header("Location: login.php"); // Changed to login.php
    exit();
}

$conn = getDbConnection();

$search_query = $_GET['search'] ?? ''; // Get search query from URL parameter

// Fetch all found items
$found_items = [];
$sql_found = "SELECT id, item_name, description, image_path, date_found, found_location, category, status FROM found_items";
$search_params = [];
$search_types = "";

if (!empty($search_query)) {
    $sql_found .= " WHERE item_name LIKE ? OR description LIKE ? OR found_location LIKE ? OR category LIKE ?";
    $search_param = '%' . $search_query . '%';
    $search_params = [$search_param, $search_param, $search_param, $search_param];
    $search_types = "ssss";
}
$sql_found .= " ORDER BY created_at DESC";

$stmt_found = $conn->prepare($sql_found);
if ($stmt_found) {
    if (!empty($search_query)) {
        $stmt_found->bind_param($search_types, ...$search_params);
    }
    $stmt_found->execute();
    $result = $stmt_found->get_result();
    while ($row = $result->fetch_assoc()) {
        $found_items[] = $row;
    }
    $stmt_found->close();
} else {
    error_log("Error preparing found_items query: " . $conn->error);
    $_SESSION['error_message'] = "Could not retrieve found items list.";
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>All Found Items - FoundIt</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    * {
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
      margin: 0;
      padding: 0;
    }

    body {
      background-color: #f5ff9c;
      padding: 20px;
    }

    .navbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 30px;
    }

    .logo {
      font-size: 36px;
      font-weight: 800;
      text-shadow: 2px 2px 2px rgba(0, 0, 0, 0.2);
    }

    .search-box {
      flex: 1;
      margin: 0 40px;
      max-width: 700px;
      position: relative;
    }

    .search-box input {
      width: 100%;
      padding: 12px 20px;
      border: 1px solid #ccc;
      border-radius: 25px;
      font-size: 16px;
      padding-right: 50px; /* Space for search icon */
    }

    .search-box .search-icon {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #888;
      cursor: pointer;
      font-size: 18px;
    }

    .icons {
      display: flex;
      gap: 20px;
    }

    .icon-btn {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background-color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid #000;
      cursor: pointer;
      transition: all 0.3s ease;
      color: #000;
      font-size: 18px;
      text-decoration: none; /* For a tag */
    }

    .icon-btn:hover {
      background-color: #000;
      color: #f5ff9c;
      transform: scale(1.1);
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .section-header h3 {
      font-size: 28px;
      font-weight: 700;
      color: #333;
    }

    .section-header a {
      text-decoration: none;
      color: #8b1e1e;
      font-weight: 600;
      transition: color 0.3s ease;
    }

    .section-header a:hover {
      color: #6a1515;
    }

    .items-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 25px;
      margin-bottom: 40px;
    }

    .item-card {
      background-color: white;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      text-decoration: none;
      color: #333;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .item-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
    }

    .item-img {
      width: 100%;
      height: 180px;
      background-color: #e0e0e0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      color: #666;
      overflow: hidden; /* Ensure image fits */
    }

    .item-img img {
        width: 100%;
        height: 100%;
        object-fit: cover; /* Cover the area, cropping if necessary */
    }

    .item-desc {
      padding: 15px;
      text-align: left;
      flex-grow: 1; /* Allow description to take available space */
      display: flex;
      flex-direction: column;
      justify-content: space-between; /* Push status to bottom */
    }

    .item-name {
        font-weight: 600;
        font-size: 1.1em;
        margin-bottom: 5px;
        color: #333;
    }

    .item-meta {
        font-size: 0.85em;
        color: #777;
        margin-top: 10px;
    }

    .item-status {
        margin-top: 10px;
        padding: 5px 10px;
        border-radius: 5px;
        font-size: 0.8em;
        font-weight: 600;
        display: inline-block; /* So it doesn't take full width */
    }

    .item-status.status-not_found, .item-status.status-unclaimed {
        background-color: #ffe0e0; /* Light red */
        color: #d9534f; /* Darker red */
    }

    .item-status.status-found, .item-status.status-claimed {
        background-color: #e6ffe6; /* Light green */
        color: #5cb85c; /* Darker green */
    }

    .item-status.status-pending_approval {
        background-color: #fff3cd; /* Light yellow */
        color: #f0ad4e; /* Darker yellow/orange */
    }

    .item-status.status-rejected {
        background-color: #e9ecef; /* Light grey */
        color: #6c757d; /* Darker grey */
    }


    /* Responsive adjustments */
    @media (max-width: 768px) {
      .navbar {
        flex-wrap: wrap;
        justify-content: center;
        gap: 15px;
      }
      .search-box {
        order: 3; /* Move search below logo and icons */
        margin: 15px 0 0 0;
        max-width: 100%;
      }
      .logo {
        font-size: 30px;
      }
      .icons {
        gap: 10px;
      }
      .icon-btn {
        width: 34px;
        height: 34px;
        font-size: 16px;
      }
      .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }
      .section-header h3 {
        font-size: 24px;
      }
      .items-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
      }
    }

    @media (max-width: 480px) {
      body {
        padding: 15px;
      }
      .logo {
        font-size: 28px;
      }
      .section-header h3 {
        font-size: 22px;
      }
      .items-grid {
        grid-template-columns: 1fr; /* Single column on very small screens */
        gap: 15px;
      }
      .item-img {
        height: 150px;
      }
    }
  </style>
</head>
<body>
  <div class="navbar">
    <div class="logo">FoundIt</div>
    <div class="search-box">
      <form action="view_all_found.php" method="GET">
        <input type="text" name="search" placeholder="Search found items..." value="<?php echo htmlspecialchars($search_query); ?>">
        <i class="fas fa-search search-icon" onclick="this.closest('form').submit();"></i>
      </form>
    </div>
    <div class="icons">
      <a href="report_lost_form.php" class="icon-btn" title="Report Lost Item">
        <i class="fas fa-exclamation-circle"></i>
      </a>
      <a href="report_found_form.php" class="icon-btn" title="Report Found Item">
        <i class="fas fa-plus-circle"></i>
      </a>
      <a href="user_dashboard.php" class="icon-btn" title="Dashboard">
        <i class="fas fa-tachometer-alt"></i>
      </a>
      <a href="profile.php" class="icon-btn" title="Profile">
        <i class="fas fa-user"></i>
      </a>
      <a href="logout.php" class="icon-btn" title="Logout">
        <i class="fas fa-sign-out-alt"></i>
      </a>
    </div>
  </div>

  <div class="section">
    <div class="section-header">
      <h3>All Found Items</h3>
      <a href="homepage.php">Back to Home &gt;</a>
    </div>
    <div class="items-grid">
    <?php if (!empty($found_items)): ?>
      <?php foreach ($found_items as $item): ?>
        <a href="found_item_view.php?id=<?php echo htmlspecialchars($item['id']); ?>" class="item-card">
          <div class="item-img">
              <?php if ($item['image_path'] && file_exists($item['image_path'])): ?>
                  <img src="<?php echo htmlspecialchars(BASE_URL . $item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
              <?php else: ?>
                  No Image
              <?php endif; ?>
          </div>
          <div class="item-desc">
            <div class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
            <?php echo nl2br(htmlspecialchars(substr($item['description'], 0, 70) . (strlen($item['description']) > 70 ? '...' : ''))); ?><br>
            <div class="item-meta">Found on: <?php echo htmlspecialchars($item['date_found']); ?> in <?php echo htmlspecialchars($item['found_location']); ?></div>
            <div class="item-status status-<?php echo htmlspecialchars($item['status']); ?>">
                <?php echo formatStatus($item['status']); ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="text-align: center; width: 100%;">No found items to display yet. <?php echo !empty($search_query) ? 'Try a different search term.' : ''; ?></p>
    <?php endif; ?>
    </div>
  </div>

  <?php include 'message_modal.php'; ?>

</body>
</html>
