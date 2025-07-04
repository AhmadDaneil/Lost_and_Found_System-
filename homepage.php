<?php
session_start();

require_once 'db_connect.php';
require_once 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = getDbConnection();
$search_query = $_GET['search'] ?? '';

// Fetch recent found items
$found_items = [];
$sql_found = "SELECT id, item_name, description, image_path FROM found_items";
if (!empty($search_query)) {
    $sql_found .= " WHERE item_name LIKE ? OR description LIKE ? OR found_location LIKE ?";
}
$sql_found .= " ORDER BY created_at DESC LIMIT 5";

$stmt_found = $conn->prepare($sql_found);
if ($stmt_found) {
    if (!empty($search_query)) {
        $search_param = '%' . $search_query . '%';
        $stmt_found->bind_param("sss", $search_param, $search_param, $search_param);
    }
    $stmt_found->execute();
    $result_found = $stmt_found->get_result();
    while ($row = $result_found->fetch_assoc()) {
        $found_items[] = $row;
    }
    $stmt_found->close();
}

// Fetch recent lost items
$lost_items = [];
$sql_lost = "SELECT id, item_name, description, image_path FROM lost_items";
if (!empty($search_query)) {
    $sql_lost .= " WHERE item_name LIKE ? OR description LIKE ? OR lost_location LIKE ?";
}
$sql_lost .= " ORDER BY created_at DESC LIMIT 5";

$stmt_lost = $conn->prepare($sql_lost);
if ($stmt_lost) {
    if (!empty($search_query)) {
        $search_param = '%' . $search_query . '%';
        $stmt_lost->bind_param("sss", $search_param, $search_param, $search_param);
    }
    $stmt_lost->execute();
    $result_lost = $stmt_lost->get_result();
    while ($row = $result_lost->fetch_assoc()) {
        $lost_items[] = $row;
    }
    $stmt_lost->close();
}

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>FoundIt - Home</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    * {
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
      margin: 0;
      padding: 0;
    }

    body.light {
      background-color: #f5ff9c;
      color: #000;
    }

    body.dark {
      background-color: #121212;
      color: #e0e0e0;
    }

    .section {
      background-color: #fffdd0;
      padding: 25px;
      border-radius: 16px;
      margin-bottom: 30px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    body.dark .section {
      background-color: #1e1e1e;
      color: #eee;
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
      border: 2px solid #000;
      border-radius: 25px;
      font-size: 1rem;
      padding-right: 50px;
    }

    .search-box .search-icon {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 1.2rem;
    }

    .nav-icons {
      display: flex;
      gap: 15px;
    }

    .icon {
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
      text-decoration: none;
    }

    body.dark .icon {
      background-color: #333;
      color: #fff;
      border-color: #aaa;
    }

    .icon:hover {
      background-color: #000;
      color: #f5ff9c;
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      border-bottom: 1px solid #eee;
      padding-bottom: 10px;
    }

    .section-header h3 {
      font-size: 24px;
      font-weight: 600;
    }

    .section-header a {
      text-decoration: none;
      color: #8b1e1e;
      font-weight: 600;
    }

    .items-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 20px;
    }

    .item-card {
      background-color: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      text-align: center;
      padding: 15px;
      color: #333;
      text-decoration: none;
    }

    body.dark .item-card {
      background-color: #2a2a2a;
      color: #e0e0e0;
    }

    .item-img {
      width: 100%;
      height: 120px;
      background-color: #e0e0e0;
      border-radius: 8px;
      margin-bottom: 10px;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .item-img img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .fab-container {
      position: fixed;
      bottom: 30px;
      right: 30px;
      display: flex;
      flex-direction: column;
      gap: 15px;
      z-index: 1000;
    }

    .fab {
      width: 60px;
      height: 60px;
      background-color: #8b1e1e;
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      text-decoration: none;
    }

    .fab:hover {
      transform: scale(1.1);
      background-color: #6a1515;
    }

    @media (max-width: 768px) {
      .navbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
    }
  </style>
</head>
  <div class="navbar">
    <div class="logo">FoundIt</div>
    <div class="search-box">
      <form action="homepage.php" method="GET">
        <input type="text" name="search" placeholder="Search for items..." value="<?php echo htmlspecialchars($search_query); ?>">
        <i class="fas fa-search search-icon"></i>
      </form>
    </div>
    <div class="nav-icons">
      <a href="user_dashboard.php" class="icon" title="Dashboard"><i class="fas fa-tachometer-alt"></i></a>
      <a href="profile.php" class="icon" title="Profile"><i class="fas fa-user"></i></a>
      <a href="settings.php" class="icon" title="Settings"><i class="fas fa-cog"></i></a>
      <a href="logout.php" class="icon" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>
  </div>

  <div class="section">
    <div class="section-header">
      <h3>Recent found items</h3>
      <a href="view_all_found.php">View all &gt;</a>
    </div>
    <div class="items-grid">
      <?php if (!empty($found_items)): ?>
        <?php foreach ($found_items as $item): ?>
          <a href="found_item_view.php?id=<?php echo htmlspecialchars($item['id']); ?>" class="item-card">
            <div class="item-img">
              <?php if ($item['image_path'] && file_exists($item['image_path'])): ?>
                <img src="<?php echo htmlspecialchars(BASE_URL . $item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
              <?php else: ?>No Image<?php endif; ?>
            </div>
            <div class="item-desc">
              <?php echo htmlspecialchars($item['item_name']); ?><br>
              <?php echo nl2br(htmlspecialchars(substr($item['description'], 0, 50))) . (strlen($item['description']) > 50 ? '...' : ''); ?>
            </div>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No found items yet. <?php echo $search_query ? 'Try a different search term.' : ''; ?></p>
      <?php endif; ?>
    </div>
  </div>

  <div class="section">
    <div class="section-header">
      <h3>Recent lost items</h3>
      <a href="view_all_lost.php">View all &gt;</a>
    </div>
    <div class="items-grid">
      <?php if (!empty($lost_items)): ?>
        <?php foreach ($lost_items as $item): ?>
          <a href="lost_item_view.php?id=<?php echo htmlspecialchars($item['id']); ?>" class="item-card">
            <div class="item-img">
              <?php if ($item['image_path'] && file_exists($item['image_path'])): ?>
                <img src="<?php echo htmlspecialchars(BASE_URL . $item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
              <?php else: ?>No Image<?php endif; ?>
            </div>
            <div class="item-desc">
              <?php echo htmlspecialchars($item['item_name']); ?><br>
              <?php echo nl2br(htmlspecialchars(substr($item['description'], 0, 50))) . (strlen($item['description']) > 50 ? '...' : ''); ?>
            </div>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No lost items yet. <?php echo $search_query ? 'Try a different search term.' : ''; ?></p>
      <?php endif; ?>
    </div>
  </div>

  <div class="fab-container">
    <a href="report_lost_form.php" class="fab" title="Report Lost Item"><i class="fas fa-question"></i></a>
    <a href="report_found_form.php" class="fab" title="Report Found Item"><i class="fas fa-plus"></i></a>
  </div>

  <?php include 'message_modal.php'; ?>

</body>
</html>
