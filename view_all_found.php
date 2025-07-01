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
    header("Location: login.html");
    exit();
}

$conn = getDbConnection();

$search_query = $_GET['search'] ?? ''; // Get search query from URL parameter

// Fetch all found items
$found_items = [];
$sql_found = "SELECT id, item_name, description, image_path, date_found, found_location, category, status FROM found_items";
if (!empty($search_query)) {
    $sql_found .= " WHERE item_name LIKE ? OR description LIKE ? OR found_location LIKE ? OR category LIKE ?";
}
$sql_found .= " ORDER BY created_at DESC";

$stmt_found = $conn->prepare($sql_found);
if ($stmt_found) {
    if (!empty($search_query)) {
        $search_param = '%' . $search_query . '%';
        $stmt_found->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
    }
    $stmt_found->execute();
    $result_found = $stmt_found->get_result();
    while ($row = $result_found->fetch_assoc()) {
        $found_items[] = $row;
    }
    $stmt_found->close();
} else {
    error_log("Error preparing found_items query: " . $conn->error);
    $_SESSION['error_message'] = "Could not retrieve found items.";
}

closeDbConnection($conn);

// Function to format status for display (re-using from view_item.php logic)
function formatStatus($status) {
    switch ($status) {
        case 'unclaimed': return '❌ Unclaimed';
        case 'claimed': return '✅ Claimed by owner';
        case 'pending_approval': return '⏳ Pending Approval';
        case 'rejected': return '🚫 Rejected';
        default: return $status;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>All Found Items - FoundIt</title>
  <link rel="stylesheet" href="unified_styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet"/>
  <style>
    /* Re-using styles from homepage.php and unified_styles.css */
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
      padding: 14px 20px;
      border-radius: 20px;
      border: none;
      outline: 2px solid purple;
      font-size: 14px;
    }

    .search-box input::placeholder {
      color: #777;
    }

    .nav-icons {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .nav-icons .icon {
      display: flex;
      align-items: center;
      cursor: pointer;
      transition: transform 0.3s ease;
    }

    .nav-icons .icon svg {
        width: 24px;
        height: 24px;
        fill: #000;
        transition: fill 0.3s ease, transform 0.3s ease;
    }

    .nav-icons .icon:hover svg {
      transform: scale(1.2);
      fill: #555;
    }

    .section {
      margin-top: 40px;
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .section-header h3 {
      font-size: 18px;
      font-weight: 600;
    }

    .section-header a {
      font-size: 14px;
      text-decoration: none;
      color: #000;
    }

    .items-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); /* Slightly larger cards */
      gap: 20px;
    }

    .item-card {
      background-color: #fffdd0;
      padding: 15px;
      border-radius: 8px;
      text-align: center;
      text-decoration: none;
      color: inherit;
      display: block;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .item-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    }

    .item-img {
      background-color: #8b1e1e;
      color: #fff;
      height: 150px; /* Taller image box */
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      border-radius: 4px;
      overflow: hidden;
      margin-bottom: 10px;
    }

    .item-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .item-desc {
      font-size: 14px;
      color: #333;
      white-space: pre-line;
      overflow: hidden;
      text-overflow: ellipsis;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      text-align: left; /* Align text left */
    }

    .item-name {
        font-weight: 600;
        margin-bottom: 5px;
    }

    .item-meta {
        font-size: 12px;
        color: #666;
        margin-top: 5px;
    }

    .item-status {
        font-size: 13px;
        font-weight: 600;
        margin-top: 8px;
        padding: 4px 8px;
        border-radius: 4px;
        display: inline-block; /* To apply padding and background */
    }

    .status-unclaimed {
        background-color: #f8d7da; /* Light red */
        color: #721c24; /* Dark red */
    }

    .status-claimed {
        background-color: #d4edda; /* Light green */
        color: #155724; /* Dark green */
    }

    .status-pending_approval {
        background-color: #fff3cd; /* Light yellow */
        color: #856404; /* Dark yellow */
    }

    .status-rejected {
        background-color: #f8d7da; /* Light red */
        color: #721c24; /* Dark red */
    }

    .page-title {
        font-size: 28px;
        font-weight: 700;
        text-align: center;
        margin-bottom: 30px;
        color: #000;
    }

    .back-to-home {
        display: inline-flex;
        align-items: center;
        text-decoration: none;
        color: #000;
        font-weight: 600;
        margin-bottom: 20px;
        transition: color 0.3s ease;
    }

    .back-to-home svg {
        width: 20px;
        height: 20px;
        fill: #000;
        margin-right: 8px;
    }

    .back-to-home:hover {
        color: #555;
    }

    .back-to-home:hover svg {
        fill: #555;
    }

    .message {
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 5px;
        text-align: center;
        font-weight: 500;
    }
    .message.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .message.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
  </style>
</head>
<body>

  <div class="navbar">
    <div class="logo">FoundIt</div>

    <div class="search-box">
      <!-- Form for search functionality -->
      <form action="view_all_found.php" method="GET">
        <input type="text" name="search" placeholder="Search found items..." value="<?php echo htmlspecialchars($search_query); ?>" />
      </form>
    </div>

    <div class="nav-icons">
      <a href="report_lost_form.php" class="icon" title="Report Lost Item">
        <svg fill="#000000" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
      </a>
      <a href="report_found_form.php" class="icon" title="Report Found Item">
        <svg fill="#000000" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
      </a>
      <a href="settings.php" class="icon" title="Settings">
        <svg fill="#000000" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.09-.73-1.7-.98l-.35-2.5c-.04-.22-.2-.38-.42-.38H12c-.22 0-.38.16-.42.38l-.35 2.5c-.61.25-1.18.58-1.7.98l-2.49-1c-.22-.09-.49 0-.61.22l-2 3.46c-.12.22-.07.49.12.64l2.11 1.65c-.04.32-.07.64-.07.98s.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1c.52.4 1.09.73 1.7.98l.35 2.5c.04.22.2.38.42.38h3.98c.22 0 .38-.16.42-.38l.35-2.5c.61-.25 1.18-.58 1.7-.98l2.49 1c.22.09.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/></svg>
      </a>
      <a href="profile.php" class="icon" title="Profile">
        <svg fill="#000000" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
      </a>
    </div>
  </div>

  <a href="homepage.php" class="back-to-home">
    <svg viewBox="0 0 24 24">
      <path d="M15 18l-6-6 6-6"/>
    </svg>
    Back to Home
  </a>

  <div class="page-title">All Found Items</div>

  <!-- Display success/error messages from session -->
  <?php if (isset($_SESSION['success_message'])): ?>
    <div class="message success">
      <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
    </div>
  <?php endif; ?>
  <?php if (isset($_SESSION['error_message'])): ?>
    <div class="message error">
      <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
    </div>
  <?php endif; ?>

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

</body>
</html>
