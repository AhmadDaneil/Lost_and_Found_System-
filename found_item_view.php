<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

$darkMode = isset($_SESSION['dark_mode']) && $_SESSION['dark_mode'] === true;

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

$item_details = null;
$reporter_telegram = null;
$is_owner = false; // Flag to check if the logged-in user is the item owner

if ($item_id) {
    $conn = getDbConnection();

    // Fetch item details and reporter's Telegram username
    $stmt = $conn->prepare("SELECT fi.id, fi.item_name, fi.description, fi.date_found, fi.found_location, fi.category, fi.image_path, fi.status, fi.user_id, u.telegram_username FROM found_items fi JOIN users u ON fi.user_id = u.id WHERE fi.id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $item_details = $result->fetch_assoc();
        $reporter_telegram = $item_details['telegram_username'];
        // Check if the logged-in user is the owner of this item
        if ($item_details['user_id'] == $_SESSION['user_id']) {
            $is_owner = true;
        }
    } else {
        $_SESSION['error_message'] = "Found item not found.";
        header("Location: homepage.php");
        exit();
    }
    $stmt->close();
    closeDbConnection($conn);
} else {
    $_SESSION['error_message'] = "Invalid item ID provided.";
    header("Location: homepage.php");
    exit();
}

// Function to format status for display
function formatStatus($status) {
    switch ($status) {
        case 'unclaimed': return '❌ Unclaimed';
        case 'claimed': return '✅ Claimed by owner';
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
  <title><?php echo htmlspecialchars($item_details['item_name']); ?> - FoundIt</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
      display: flex;
      flex-direction: column;
      align-items: center;
      min-height: 100vh;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      width: 100%;
      max-width: 800px;
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
      color: #000;
      font-size: 18px;
      text-decoration: none;
    }

    .icon-btn:hover,
    .back-btn:hover,
    .edit-btn:hover {
      background-color: #000;
      color: #f5ff9c;
      transform: scale(1.1);
    }

    .content-box {
      background-color: #fffdd0;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 800px;
      position: relative;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .back-btn {
      position: absolute;
      top: 20px;
      left: 20px;
      z-index: 10;
    }

    .edit-btn {
        position: absolute;
        top: 20px;
        right: 20px;
        z-index: 10;
        background-color: #ffc107; /* Yellow for edit */
        border-color: #ffc107;
        color: #333;
    }

    .edit-btn:hover {
        background-color: #e0a800;
        border-color: #e0a800;
        color: white;
    }

    .item-image-box {
      width: 100%;
      height: 700px;
      background-color: #e0e0e0;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      margin-bottom: 20px;
    }

    .item-image-box img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .item-image-box span {
        color: #666;
        font-size: 1.2em;
    }

    .item-details {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-bottom: 20px;
    }

    .item-details h2 {
      font-size: 32px;
      color: #333;
      margin-bottom: 5px;
    }

    .item-details p {
      font-size: 16px;
      color: #555;
      line-height: 1.5;
    }

    .item-details .detail-row {
      display: flex;
      justify-content: space-between;
      font-size: 15px;
      color: #666;
    }

    .item-details .detail-row span:first-child {
      font-weight: 600;
      color: #333;
    }

    .item-description {
      background-color: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      margin-bottom: 20px;
    }

    .item-description h3 {
      font-size: 20px;
      color: #333;
      margin-bottom: 10px;
    }

    .item-description p {
      font-size: 16px;
      color: #555;
      line-height: 1.6;
      white-space: pre-wrap; /* Preserve line breaks from textarea */
    }

    .status-box {
      background-color: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      margin-bottom: 20px;
      text-align: center;
    }

    .status-box p {
      font-size: 18px;
      font-weight: 600;
      color: #333;
      margin-bottom: 10px;
    }

    .status-display {
      font-size: 24px;
      font-weight: 700;
      padding: 10px 20px;
      border-radius: 8px;
      display: inline-block;
    }

    .status-unclaimed {
        background-color: #ffe0e0; /* Light red */
        color: #d9534f; /* Darker red */
    }

    .status-claimed {
        background-color: #e6ffe6; /* Light green */
        color: #5cb85c; /* Darker green */
    }

    .status-pending_approval {
        background-color: #fff3cd; /* Light yellow */
        color: #f0ad4e; /* Darker yellow/orange */
    }

    .status-rejected {
        background-color: #e9ecef; /* Light grey */
        color: #6c757d; /* Darker grey */
    }

    .action-buttons {
      display: flex;
      justify-content: center;
      gap: 15px;
      flex-wrap: wrap;
    }

    .action-buttons button,
    .action-buttons a {
      padding: 12px 25px;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.3s ease;
      text-decoration: none;
      color: white;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      justify-content: center;
    }

    .action-buttons .mark-claimed-btn {
      background-color: #28a745; /* Green for claimed */
    }

    .action-buttons .mark-claimed-btn:hover {
      background-color: #218838;
    }

    .action-buttons .chat-btn {
      background-color: #17a2b8; /* Info blue for chat */
    }

    .action-buttons .chat-btn:hover {
      background-color: #138496;
    }

    /* Message Box / Confirmation Modal */
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
        display: flex; /* Use flexbox for centering */
        align-items: center; /* Center vertically */
        justify-content: center; /* Center horizontally */
    }

    .message-box h3 {
        color: #333;
        margin-bottom: 15px;
        font-size: 24px;
    }

    .message-box p {
        color: #555;
        margin-bottom: 20px;
        font-size: 16px;
    }

    .message-box .button-group {
        display: flex;
        justify-content: center;
        gap: 10px;
    }

    .message-box .confirm-btn,
    .message-box .cancel-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
        transition: background-color 0.3s ease;
    }

    .message-box .confirm-btn {
        background-color: #28a745;
        color: white;
    }

    .message-box .confirm-btn:hover {
        background-color: #218838;
    }

    .message-box .cancel-btn {
        background-color: #dc3545;
        color: white;
    }

    .message-box .cancel-btn:hover {
        background-color: #c82333;
    }

    /* Responsive adjustments */
    @media (max-width: 600px) {
      body {
        padding: 15px;
      }
      .header {
        margin-bottom: 20px;
      }
      .logo {
        font-size: 28px;
      }
      .icon-btn,
      .back-btn,
      .edit-btn {
        width: 34px;
        height: 34px;
        font-size: 16px;
      }
      .content-box {
        padding: 20px;
        gap: 15px;
      }
      .back-btn, .edit-btn {
        top: 15px;
        left: 15px;
        right: 15px;
      }
      .item-image-box {
        height: 200px;
      }
      .item-details h2 {
        font-size: 28px;
      }
      .item-details p,
      .item-details .detail-row {
        font-size: 14px;
      }
      .item-description h3 {
        font-size: 18px;
      }
      .item-description p {
        font-size: 14px;
      }
      .status-box p {
        font-size: 16px;
      }
      .status-display {
        font-size: 20px;
        padding: 8px 15px;
      }
      .action-buttons button,
      .action-buttons a {
        padding: 10px 20px;
        font-size: 0.9rem;
      }
    }
/* === DARK MODE FIX === */
body.dark-mode {
  background-color: #121212;
  color: #ffffff;
  font-family: 'Poppins', sans-serif;
}

/* Headers */
body.dark-mode .header,
body.dark-mode header {
  background-color: #1e1e1e;
  color: #ffffff;
  border-bottom: 1px solid #333;
}

/* Container Boxes */
body.dark-mode .content-box,
body.dark-mode .form-container,
body.dark-mode .details-box,
body.dark-mode .description-box,
body.dark-mode .status-box,
body.dark-mode .upload-section,
body.dark-mode .image-box {
  background-color: #1e1e1e;
  color: #ffffff;
  border: 1px solid #444;
  box-shadow: 0 0 10px rgba(255, 255, 255, 0.05);
}

/* Labels & Section Titles */
body.dark-mode label,
body.dark-mode .section-title,
body.dark-mode h1,
body.dark-mode h2,
body.dark-mode h3 {
  color: #ffffff;
}

/* INPUTS, SELECTS, TEXTAREAS — GRAY BACKGROUND + WHITE TEXT */
body.dark-mode input[type="text"],
body.dark-mode input[type="email"],
body.dark-mode input[type="date"],
body.dark-mode input[type="password"],
body.dark-mode select,
body.dark-mode textarea {
  background-color: #2e2e2e !important;
  color: #ffffff !important;
  border: 1px solid #666 !important;
  border-radius: 6px;
  padding: 10px;
  font-size: 14px;
}

body.dark-mode input::placeholder,
body.dark-mode textarea::placeholder {
  color: #aaaaaa;
}

/* Image preview & file upload */
body.dark-mode .image-preview,
body.dark-mode .image-box {
  background-color: #2e2e2e;
  border: 1px dashed #777;
  color: #ffffff;
}

/* Buttons */
body.dark-mode .btn,
body.dark-mode .update-btn,
body.dark-mode .back-btn,
body.dark-mode .icon-btn,
body.dark-mode button {
  background-color: #333;
  color: #ffffff;
  border: 1px solid #777;
  transition: background-color 0.3s ease, color 0.3s ease;
}

body.dark-mode .btn:hover,
body.dark-mode .update-btn:hover,
body.dark-mode .back-btn:hover,
body.dark-mode .icon-btn:hover,
body.dark-mode button:hover {
  background-color: #ffffff;
  color: #121212;
}

/* Dropdown options */
body.dark-mode select option {
  background-color: #2e2e2e;
  color: #ffffff;
}

/* Alerts */
body.dark-mode .message.success {
  background-color: #2e7d32;
  color: #dfffd8;
  border: 1px solid #4caf50;
}

body.dark-mode .message.error {
  background-color: #b71c1c;
  color: #ffdada;
  border: 1px solid #ef5350;
}

/* Status badges */
body.dark-mode .status-found {
  backgro

  </style>
</head>
<body class="<?php echo $darkMode ? 'dark-mode' : ''; ?>">
  <div class="header">
    <div class="logo">FoundIt</div>
    <div class="icons">
      <a href="homepage.php" class="icon-btn" title="Home">
        <svg fill="#000000" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
      </a>
      <a href="user_dashboard.php" class="icon-btn" title="Dashboard">
        <i class="fas fa-tachometer-alt"></i>
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

    <?php if ($is_owner && ($item_details['status'] !== 'claimed' && $item_details['status'] !== 'rejected')): ?>
    <!-- Edit Button (only if current user is owner and item is not resolved/rejected) -->
    <a href="report_found_form.php?id=<?php echo htmlspecialchars($item_id); ?>" class="edit-btn" title="Edit Item">
        <i class="fas fa-edit"></i>
    </a>
    <?php endif; ?>

    <div class="item-image-box">
        <?php if ($item_details['image_path'] && file_exists($item_details['image_path'])): ?>
            <img src="<?php echo htmlspecialchars(BASE_URL . $item_details['image_path']); ?>" alt="<?php echo htmlspecialchars($item_details['item_name']); ?>">
        <?php else: ?>
            <span>No Image Available</span>
        <?php endif; ?>
    </div>

    <div class="item-details">
      <h2><?php echo htmlspecialchars($item_details['item_name']); ?></h2>
      <div class="detail-row">
        <span>Category:</span>
        <span><?php echo htmlspecialchars($item_details['category']); ?></span>
      </div>
      <div class="detail-row">
        <span>Date Found:</span>
        <span><?php echo htmlspecialchars($item_details['date_found']); ?></span>
      </div>
      <div class="detail-row">
        <span>Found Location:</span>
        <span><?php echo htmlspecialchars($item_details['found_location']); ?></span>
      </div>
    </div>

    <div class="item-description">
      <h3>Description</h3>
      <p><?php echo nl2br(htmlspecialchars($item_details['description'])); ?></p>
    </div>

    <div class="status-box">
      <p>Current Status:</p>
      <span class="status-display status-<?php echo htmlspecialchars($item_details['status']); ?>">
        <?php echo formatStatus($item_details['status']); ?>
      </span>
    </div>

    <div class="action-buttons">
    <?php if ($is_owner): ?>
        <?php if ($item_details['status'] === 'unclaimed'): ?>
            <button class="mark-claimed-btn" onclick="showConfirmation('claimed')">
                <i class="fas fa-check-circle"></i> Mark as Claimed by Owner
            </button>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($reporter_telegram) && !$is_owner): // Only show chat button if not the owner and telegram is available ?>
    <a href="https://<?php echo htmlspecialchars(ltrim($reporter_telegram, '@')); ?>" target="_blank" class="chat-btn">
        <i class="fab fa-telegram-plane"></i> Chat with Reporter
    </a>
    <?php endif; ?>
  </div>

  <!-- Confirmation Modal -->
  <div id="confirmationModal" class="message-box" style="display: none;">
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

    function showConfirmation(action) {
      currentStatusAction = action;
      const modal = document.getElementById('confirmationModal');
      const message = document.getElementById('confirmationMessage');
      if (action === 'claimed') {
        message.textContent = "Are you sure you want to mark this found item as 'Claimed by owner'? This action cannot be undone.";
      }
      modal.style.display = 'flex'; // Show the modal
    }

    function hideConfirmation() {
      document.getElementById('confirmationModal').style.display = 'none';
    }

    document.getElementById('confirmAction').addEventListener('click', function() {
      hideConfirmation();
      // Redirect to update status script with item ID, type, and new status
      window.location.href = `item_status.php?id=${itemId}&type=found&new_status=${currentStatusAction}`;
    });

    // Close the modal if the user clicks outside of it
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('confirmationModal');
        if (event.target === modal) {
            hideConfirmation();
        }
    });
  </script>

  <?php include 'message_modal.php'; ?>

</body>
</html> 
    