<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to view item details.";
    header("Location: login.php"); // Updated from login.html to login.php
    exit();
}

$item_id = $_GET['id'] ?? null;
$item_type = $_GET['type'] ?? null; // 'lost' or 'found'

$item_details = null;
$reporter_telegram = null;
$is_owner = false; // Flag to check if the logged-in user is the item owner

if ($item_id && ($item_type === 'lost' || $item_type === 'found')) {
    $conn = getDbConnection();

    if ($item_type === 'lost') {
        $table = 'lost_items';
        $date_field = 'date_lost';
        $location_field = 'lost_location';
        $status_options = [
            'not_found' => '❌ Not found',
            'found' => '✅ Found'
        ];
    } else { // $item_type === 'found'
        $table = 'found_items';
        $date_field = 'date_found';
        $location_field = 'found_location';
        $status_options = [
            'unclaimed' => '❌ Unclaimed',
            'claimed' => '✅ Claimed by owner'
        ];
    }

    // Fetch item details and reporter's Telegram username
    $stmt = $conn->prepare("SELECT i.id, i.item_name, i.description, i.$date_field, i.$location_field, i.category, i.image_path, i.status, i.user_id, u.telegram_username FROM $table i JOIN users u ON i.user_id = u.id WHERE i.id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $item_details = $result->fetch_assoc();
        $reporter_telegram = $item_details['telegram_username'];

        // Check if the logged-in user is the owner of this item
        if ($_SESSION['user_id'] == $item_details['user_id']) {
            $is_owner = true;
        }
    } else {
        $_SESSION['error_message'] = "Item not found or invalid type.";
        header("Location: homepage.php");
        exit();
    }

    $stmt->close();
    closeDbConnection($conn);
} else {
    $_SESSION['error_message'] = "Invalid item ID or type provided.";
    header("Location: homepage.php");
    exit();
}

// Function to format status for display
function formatStatus($status, $item_type) {
    if ($item_type === 'lost') {
        switch ($status) {
            case 'not_found': return '❌ Not found';
            case 'found': return '✅ Found';
            case 'pending_approval': return '⏳ Pending Approval';
            case 'rejected': return '🚫 Rejected'; // Added rejected status
            default: return $status;
        }
    } else { // found_items
        switch ($status) {
            case 'unclaimed': return '❌ Unclaimed';
            case 'claimed': return '✅ Claimed by owner';
            case 'pending_approval': return '⏳ Pending Approval';
            case 'rejected': return '🚫 Rejected'; // Added rejected status
            default: return $status;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo ucfirst($item_type); ?> Item - FoundIt</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet"/>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background-color: #f5ff9c;
      padding: 20px;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }

    .logo {
      font-size: 32px;
      font-weight: 800;
      text-shadow: 2px 2px 2px rgba(0, 0, 0, 0.1);
    }

    .icons {
      display: flex;
      gap: 20px;
    }

    .icon-btn,
    .back-btn,
    .edit-btn {
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
    }

    .icon-btn:hover,
    .back-btn:hover,
    .edit-btn:hover {
      background-color: #000;
      transform: scale(1.1);
    }

    .icon-btn:hover svg,
    .back-btn:hover svg,
    .edit-btn:hover svg {
      fill: #f5ff9c;
    }

    .icon-btn svg,
    .back-btn svg,
    .edit-btn svg {
      width: 20px;
      height: 20px;
      fill: #000;
      transition: fill 0.3s ease;
    }

    .content-box {
      background-color: #fffdd0;
      padding: 30px;
      border-radius: 16px;
      max-width: 900px;
      margin: 0 auto;
      position: relative;
    }

    .back-btn {
      position: absolute;
      top: 20px;
      left: 20px;
    }

    .edit-btn {
      position: absolute;
      top: 20px;
      right: 20px;
    }

    .section-title {
      text-align: center;
      font-weight: 600;
      margin-bottom: 30px;
      font-size: 20px;
    }

    .top-content {
      display: flex;
      gap: 30px;
      flex-wrap: wrap;
    }

    .image-box {
      background-color: #8b1e1e;
      color: #fff;
      width: 180px;
      height: 200px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      border-radius: 12px;
      overflow: hidden;
    }

    .image-box img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .details-box {
      flex: 1;
      background-color: white;
      padding: 20px;
      border-radius: 12px;
      display: flex;
      justify-content: space-between;
      flex-direction: column;
    }

    .details-box h3 {
      font-size: 20px;
      margin-bottom: 8px;
    }

    .details-box p {
      font-size: 14px;
      color: #444;
      line-height: 1.4;
    }

    .details-date {
      text-align: right;
      font-size: 14px;
      color: #666;
    }

    .description-box {
      margin-top: 20px;
      background-color: white;
      padding: 20px;
      border-radius: 12px;
      min-height: 100px;
      font-size: 14px;
      color: #444;
    }

    .status-box {
      margin-top: 20px;
      background-color: white;
      padding: 16px;
      border-radius: 12px;
      font-size: 14px;
      color: #444;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
    }

    .status-box strong {
      display: inline-block;
      width: 60px; /* Adjust as needed */
      color: #222;
    }

    .status-update-btn {
        background-color: #8b1e1e;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        font-weight: 600;
        margin-left: 10px; /* Space from status text */
    }

    .status-update-btn:hover {
        background-color: #6a1515;
    }

    /* Message Box for confirmations/errors */
    .message-box {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: #fffdd0;
        border: 2px solid #8b1e1e;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        z-index: 1000;
        text-align: center;
        max-width: 400px;
        width: 90%;
        display: none; /* Hidden by default */
    }

    .message-box h3 {
        margin-bottom: 20px;
        color: #8b1e1e;
    }

    .message-box .button-group {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-top: 20px;
    }

    .message-box button {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: background-color 0.3s ease;
    }

    .message-box .confirm-btn {
        background-color: #4CAF50; /* Green */
        color: white;
    }

    .message-box .confirm-btn:hover {
        background-color: #45a049;
    }

    .message-box .cancel-btn {
        background-color: #f44336; /* Red */
        color: white;
    }

    .message-box .cancel-btn:hover {
        background-color: #da190b;
    }
  </style>
</head>
<body>

  <div class="header">
    <div class="logo">FoundIt</div>
    <div class="icons">
      <!-- Home Icon -->
      <a href="homepage.php" class="icon-btn" title="Home">
        <svg viewBox="0 0 24 24">
          <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1h-6v-6H10v6H4a1 1 0 0 1-1-1V9.5z"/>
        </svg>
      </a>
      <!-- Telegram Icon (General support, not item specific) -->
      <a href="https://t.me/FoundItSupport" target="_blank" class="icon-btn" title="Chat on Telegram">
        <svg viewBox="0 0 24 24">
          <path d="M9.036 17.813c-.267 0-.224-.101-.316-.354l-1.105-3.641 8.506-5.375c.388-.264.747.084.58.526l-2.44 7.768c-.135.408-.355.51-.722.318l-2.006-1.478-0.97.936c-.1.1-.184.184-.378.184zM12 0C5.373 0 0 5.373 0 12c0 6.628 5.373 12 12 12 6.628 0 12-5.372 12-12 0-6.627-5.372-12-12-12z"/>
        </svg>
      </a>
    </div>
  </div>

  <div class="content-box">
    <!-- Back Button -->
    <a href="homepage.php" class="back-btn" title="Back to Home">
      <svg viewBox="0 0 24 24">
        <path d="M15 18l-6-6 6-6"/>
      </svg>
    </a>

    <?php if ($is_owner): // Show edit button only if current user is the owner ?>
    <!-- Edit Button (links to the specific item's edit page) -->
    <a href="report_<?php echo htmlspecialchars($item_type); ?>_form.php?id=<?php echo htmlspecialchars($item_id); ?>" class="edit-btn" title="Edit <?php echo ucfirst($item_type); ?> Item">
      <svg viewBox="0 0 24 24">
        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM21.71 6.04a1.003 1.003 0 0 0 0-1.41l-2.34-2.34a1.003 1.003 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
      </svg>
    </a>
    <?php endif; ?>

    <div class="section-title"><?php echo ucfirst($item_type); ?> Item</div>

    <div class="top-content">
      <div class="image-box">
        <?php if ($item_details['image_path'] && file_exists($item_details['image_path'])): ?>
            <img src="<?php echo htmlspecialchars(BASE_URL . $item_details['image_path']); ?>" alt="<?php echo htmlspecialchars($item_details['item_name']); ?>">
        <?php else: ?>
            No Image
        <?php endif; ?>
      </div>

      <div class="details-box">
        <div>
          <h3><?php echo htmlspecialchars($item_details['item_name']); ?></h3>
          <p>Category: <?php echo htmlspecialchars($item_details['category']); ?><br/>
          <?php echo ucfirst($item_type); ?> Location: <?php echo htmlspecialchars($item_details[$location_field]); ?></p>
        </div>
        <div class="details-date">Date: <?php echo htmlspecialchars($item_details[$date_field]); ?></div>
      </div>
    </div>

    <div class="description-box">
      <?php echo nl2br(htmlspecialchars($item_details['description'])); ?>
    </div>

    <div class="status-box">
      <strong>Status:</strong> <?php echo formatStatus($item_details['status'], $item_type); ?>
      <?php if ($is_owner): // Show status update button only if current user is the owner ?>
        <?php if ($item_type === 'lost' && $item_details['status'] === 'not_found'): ?>
            <button class="status-update-btn" onclick="showConfirmation('found')">Mark as Found</button>
        <?php elseif ($item_type === 'found' && $item_details['status'] === 'unclaimed'): ?>
            <button class="status-update-btn" onclick="showConfirmation('claimed')">Mark as Claimed</button>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <?php if ($reporter_telegram): ?>
    <div class="status-box" style="margin-top: 10px;">
        <strong>Contact:</strong>
        <a href="https://t.me/<?php echo htmlspecialchars(ltrim($reporter_telegram, '@')); ?>" target="_blank" class="status-update-btn" style="background-color: #0088cc;">
            <svg style="width:20px;height:20px;vertical-align:middle;margin-right:5px;fill:white;" viewBox="0 0 24 24">
                <path d="M9.036 17.813c-.267 0-.224-.101-.316-.354l-1.105-3.641 8.506-5.375c.388-.264.747.084.58.526l-2.44 7.768c-.135.408-.355.51-.722.318l-2.006-1.478-0.97.936c-.1.1-.184.184-.378.184zM12 0C5.373 0 0 5.373 0 12c0 6.628 5.373 12 12 12 6.628 0 12-5.372 12-12 0-6.627-5.372-12-12-12z"/>
            </svg>
            Chat with Reporter
        </a>
    </div>
    <?php endif; ?>
  </div>

  <!-- Confirmation Modal -->
  <div id="confirmationModal" class="message-box">
    <h3>Are you sure?</h3>
    <p id="confirmationMessage"></p>
    <div class="button-group">
      <button class="confirm-btn" id="confirmAction">Yes</button>
      <button class="cancel-btn" onclick="hideConfirmation()">No</button>
    </div>
  </div>

  <script>
    let currentStatusAction = '';
    const itemId = <?php echo json_encode($item_id); ?>;
    const itemType = <?php echo json_encode($item_type); ?>;

    function showConfirmation(action) {
      currentStatusAction = action;
      const modal = document.getElementById('confirmationModal');
      const message = document.getElementById('confirmationMessage');
      if (action === 'found') {
        message.textContent = "Are you sure you want to mark this lost item as 'Found'? This action cannot be undone.";
      } else if (action === 'claimed') {
        message.textContent = "Are you sure you want to mark this found item as 'Claimed by owner'? This action cannot be undone.";
      }
      modal.style.display = 'block';
    }

    function hideConfirmation() {
      document.getElementById('confirmationModal').style.display = 'none';
    }

    document.getElementById('confirmAction').addEventListener('click', function() {
      hideConfirmation();
      // Redirect to update status script with item ID, type, and new status
      window.location.href = `update_item_status.php?id=${itemId}&type=${itemType}&new_status=${currentStatusAction}`;
    });
  </script>

</body>
</html>
