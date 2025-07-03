<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in, if not, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Changed to login.php
    exit();
}

$conn = getDbConnection();

$search_query = $_GET['search'] ?? ''; // Get search query from URL parameter

// Fetch recent found items
$found_items = [];
$sql_found = "SELECT id, item_name, description, image_path FROM found_items";
if (!empty($search_query)) {
    $sql_found .= " WHERE item_name LIKE ? OR description LIKE ? OR found_location LIKE ?";
}
$sql_found .= " ORDER BY created_at DESC LIMIT 5"; // Still limit for homepage recent

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
} else {
    error_log("Error preparing found_items query: " . $conn->error);
}


// Fetch recent lost items
$lost_items = [];
$sql_lost = "SELECT id, item_name, description, image_path FROM lost_items";
if (!empty($search_query)) {
    $sql_lost .= " WHERE item_name LIKE ? OR description LIKE ? OR lost_location LIKE ?";
}
$sql_lost .= " ORDER BY created_at DESC LIMIT 5"; // Still limit for homepage recent

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
} else {
    error_log("Error preparing lost_items query: " . $conn->error);
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

    body {
      background-color: #f5ff9c;
      padding: 20px;
      display: block; /* Override flex from unified_styles.css body */
      height: auto;
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
      border: 2px solid #000;
      border-radius: 25px;
      font-size: 1rem;
      padding-right: 50px; /* Space for icon */
    }

    .search-box .search-icon {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #000;
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
      text-decoration: none; /* For a tag */
    }

    .icon:hover {
      background-color: #000;
      color: #f5ff9c;
      transform: scale(1.1);
    }

    .section {
      background-color: #fffdd0;
      padding: 25px;
      border-radius: 16px;
      margin-bottom: 30px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
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
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 20px;
      justify-content: center;
    }

    .item-card {
      background-color: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      text-align: center;
      padding: 15px;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      text-decoration: none; /* For a tag */
      color: #333;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .item-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
    }

    .item-img {
      width: 100%;
      height: 120px;
      background-color: #e0e0e0;
      border-radius: 8px;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #777;
      font-size: 14px;
      overflow: hidden; /* Ensure image doesn't overflow */
    }

    .item-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .item-desc {
      font-size: 14px;
      color: #555;
      text-align: left;
      width: 100%;
    }

    /* Floating action buttons */
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
        cursor: pointer;
        transition: transform 0.2s ease, background-color 0.2s ease;
        text-decoration: none; /* For a tag */
    }

    .fab:hover {
        transform: scale(1.1);
        background-color: #6a1515;
    }

    .fab i {
        pointer-events: none; /* Prevents icon from interfering with click */
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .navbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }

      .search-box {
        margin: 15px 0;
        width: 100%;
      }

      .nav-icons {
        width: 100%;
        justify-content: space-around;
      }

      .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }

      .items-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      }

      .fab-container {
        bottom: 20px;
        right: 20px;
        gap: 10px;
      }

      .fab {
        width: 50px;
        height: 50px;
        font-size: 20px;
      }
    }
  </style>
</head>
<body>

  <div class="navbar">
    <div class="logo">FoundIt</div>
    <div class="search-box">
      <form action="homepage.php" method="GET">
        <input type="text" name="search" placeholder="Search for items..." value="<?php echo htmlspecialchars($search_query); ?>">
        <i class="fas fa-search search-icon"></i>
      </form>
    </div>
    <div class="nav-icons">
      <a href="user_dashboard.php" class="icon" title="Dashboard">
        <svg fill="#000000" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
      </a>
      <a href="profile.php" class="icon" title="Profile">
        <svg fill="#000000" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
      </a>
      <a href="settings.php" class="icon" title="Settings">
        <svg fill="#000000" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.09-.73-1.7-.98l-.35-2.5c-.04-.22-.2-.38-.42-.38H12c-.22 0-.38.16-.42.38l-.35 2.5c-.61.25-1.18.58-1.7.98l-2.49-1c-.22-.09-.49 0-.61.22l-2 3.46c-.12.22-.07.49.12.64l2.11 1.65c-.04.32-.07.64-.07.98s.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1c.52.4 1.09.73 1.7.98l.35 2.5c.04.22.2.38.42.38h3.98c.22 0 .38-.16.42-.38l.35-2.5c.61-.25 1.18-.58 1.7-.98l2.49 1c.22.09.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/></svg>
      </a>
      <a href="logout.php" class="icon" title="Logout">
        <svg fill="#000000" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
      </a>
    </div>
  </div>

  <!-- Recent found items -->
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
                <?php else: ?>
                    No Image
                <?php endif; ?>
            </div>
            <div class="item-desc">
              <?php echo htmlspecialchars($item['item_name']); ?><br>
              <?php echo nl2br(htmlspecialchars(substr($item['description'], 0, 50) . (strlen($item['description']) > 50 ? '...' : ''))); ?>
            </div>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="text-align: center; width: 100%;">No found items to display yet. <?php echo !empty($search_query) ? 'Try a different search term.' : ''; ?></p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent lost items -->
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
                <?php else: ?>
                    No Image
                <?php endif; ?>
            </div>
            <div class="item-desc">
              <?php echo htmlspecialchars($item['item_name']); ?><br>
              <?php echo nl2br(htmlspecialchars(substr($item['description'], 0, 50) . (strlen($item['description']) > 50 ? '...' : ''))); ?>
            </div>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="text-align: center; width: 100%;">No lost items to display yet. <?php echo !empty($search_query) ? 'Try a different search term.' : ''; ?></p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Floating action buttons -->
  <div class="fab-container">
    <a href="report_lost_form.php" class="fab" title="Report Lost Item">
      <i class="fas fa-question"></i>
    </a>
    <a href="report_found_form.php" class="fab" title="Report Found Item">
      <i class="fas fa-plus"></i>
    </a>
  </div>

  <?php include 'message_modal.php'; ?>

</body>
</html>
