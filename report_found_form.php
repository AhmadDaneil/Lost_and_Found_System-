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
    $_SESSION['error_message'] = "You must be logged in to report or edit an item.";
    header("Location: login.php"); // Updated from login.html to login.php
    exit();
}

$item_id = $_GET['id'] ?? null;
$item_details = null;
$page_title = "Report Found Item"; // Corrected title for found items
$form_action = "add_item.php"; // Default for new item

if ($item_id) {
    // If an item ID is provided, we are in edit mode
    $page_title = "Edit Found Item"; // Corrected title for found items
    $form_action = "update_item.php"; // Script to handle updates

    $conn = getDbConnection();
    // Fetch found item details for editing
    $stmt = $conn->prepare("SELECT item_name, description, date_found, found_location, category, image_path, status FROM found_items WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $item_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $item_details = $result->fetch_assoc();
    } else {
        $_SESSION['error_message'] = "Found item not found or you don't have permission to edit.";
        header("Location: homepage.php");
        exit();
    }
    $stmt->close();
    closeDbConnection($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($page_title); ?> - FoundIt</title>
  <link rel="stylesheet" href="unified_styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <style>
    /* Add styles specific to this form if not already in unified_styles.css */
    body {
      background-color: #f5ff9c;
      padding: 20px;
      display: block; /* Override flex from unified_styles.css body */
      height: auto;
    }

    .form-container {
      background-color: #fffdd0;
      padding: 30px;
      border-radius: 16px;
      max-width: 600px;
      margin: 20px auto;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      position: relative; /* For positioning back button */
    }

    .form-container header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .form-container h1 {
      font-size: 32px;
      font-weight: 800;
      text-shadow: 2px 2px 2px rgba(0, 0, 0, 0.1);
    }

    .form-container h2 {
      text-align: center;
      margin-bottom: 25px;
      font-size: 24px;
      color: #333;
    }

    .home-icon {
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
      color: #000; /* For font-awesome icon */
      font-size: 18px;
    }

    .home-icon:hover {
      background-color: #000;
      color: #f5ff9c;
      transform: scale(1.1);
    }

    .form-container form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .form-container input[type="text"],
    .form-container input[type="email"],
    .form-container input[type="date"],
    .form-container textarea,
    .form-container select {
      width: 100%;
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1rem;
      box-sizing: border-box;
    }

    .form-container textarea {
      min-height: 100px;
      resize: vertical;
    }

    .form-row {
      display: flex;
      gap: 15px;
      flex-wrap: wrap; /* Allow wrapping on smaller screens */
    }

    .form-row > div {
      flex: 1;
      min-width: 200px; /* Ensure elements don't get too small */
    }

    .upload-section {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      border: 2px dashed #ccc;
      border-radius: 8px;
      padding: 20px;
      cursor: pointer;
      min-height: 120px; /* Ensure a minimum height */
      text-align: center;
    }

    .upload-section label {
      font-weight: 600;
      margin-bottom: 10px;
      cursor: pointer;
    }

    .upload-icon {
      font-size: 40px;
      color: #8b1e1e;
      margin-bottom: 10px;
    }

    .upload-section input[type="file"] {
      display: none;
    }

    .upload-section img {
        max-width: 100%;
        max-height: 100px;
        margin-top: 10px;
        border-radius: 4px;
        object-fit: contain;
    }

    .form-container button[type="submit"] {
      padding: 12px 20px;
      background-color: #8b1e1e;
      color: white;
      font-weight: 600;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s ease;
      align-self: flex-end; /* Align button to the right */
      width: 150px; /* Fixed width for consistency */
    }

    .form-container button[type="submit"]:hover {
      background-color: #6a1515;
    }

    /* Message styling (from unified_styles.css or similar) */
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
    /* Dark Mode Styles */
/* Apply full page dark mode */
body.dark-mode {
  background-color: #121212;
  color: #f5f5f5;
}

/* Fix all form elements inside dark mode */
body.dark-mode input,
body.dark-mode textarea,
body.dark-mode select {
  background-color: #1e1e1e;
  color: #f5f5f5;
  border: 1px solid #444;
}

body.dark-mode input::placeholder,
body.dark-mode textarea::placeholder {
  color: #aaa;
}

body.dark-mode .form-container {
  background-color: #1e1e1e;
  color: #f5f5f5;
  box-shadow: 0 5px 15px rgba(255, 255, 255, 0.1);
}

/* Labels and headings */
body.dark-mode h1,
body.dark-mode h2,
body.dark-mode label {
  color: #f5f5f5;
}

/* Upload section */
body.dark-mode .upload-section {
  border-color: #666;
  background-color: #2c2c2c;
}

/* Upload icon */
body.dark-mode .upload-icon {
  color: #bb86fc;
}

/* Image preview border */
body.dark-mode .upload-section img {
  border: 1px solid #777;
}

/* Buttons */
body.dark-mode .form-container button[type="submit"] {
  background-color: #bb86fc;
  color: #000;
}

body.dark-mode .form-container button[type="submit"]:hover {
  background-color: #9c5de0;
}

/* Home icon button */
body.dark-mode .home-icon {
  background-color: #333;
  border-color: #f5f5f5;
  color: #f5f5f5;
}

body.dark-mode .home-icon:hover {
  background-color: #f5f5f5;
  color: #121212;
}

  </style>
</head>
<body class="<?php echo $darkMode ? 'dark-mode' : ''; ?>">
  <div class="form-container">
    <header>
      <h1>FoundIt</h1>
      <a href="homepage.php" class="home-icon"><i class="fas fa-home"></i></a>
    </header>

    <h2><?php echo htmlspecialchars($page_title); ?></h2>

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

    <!-- Form action dynamically set based on whether it's a new report or an edit -->
    <form action="<?php echo htmlspecialchars($form_action); ?>" method="POST" enctype="multipart/form-data">
      <!-- Hidden input to distinguish between lost and found items -->
      <input type="hidden" name="item_type" value="found"> <!-- Corrected to 'found' -->
      <?php if ($item_id): // Pass item_id if in edit mode ?>
        <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item_id); ?>">
      <?php endif; ?>

      <input type="text" name="item_name" placeholder="Item Name" value="<?php echo htmlspecialchars($item_details['item_name'] ?? ''); ?>" required>
      <textarea name="description" placeholder="Description" required><?php echo htmlspecialchars($item_details['description'] ?? ''); ?></textarea>

      <div class="form-row">
        <div class="date-section">
          <label for="date_found">Date Found</label> <!-- Corrected label to 'Date Found' -->
          <input type="date" id="date_found" name="date_found" value="<?php echo htmlspecialchars($item_details['date_found'] ?? ''); ?>" required> <!-- Corrected name to 'date_found' -->
        </div>

        <div class="upload-section" onclick="document.getElementById('item_image').click()">
          <label>Upload Image</label>
          <div class="upload-icon"><i class="fas fa-upload"></i></div>
          <input type="file" id="item_image" name="item_image" accept="image/*">
          <img id="image_preview_found" src="<?php echo ($item_details['image_path'] ?? '') ? htmlspecialchars(BASE_URL . $item_details['image_path']) : ''; ?>" alt="Image Preview" style="max-width: 100%; max-height: 100px; margin-top: 10px; <?php echo ($item_details['image_path'] ?? '') ? 'display: block;' : 'display: none;'; ?>"> <!-- Corrected ID to 'image_preview_found' -->
        </div>

        <div class="category-section">
          <label for="category">Item Category</label>
          <select id="category" name="category" required>
            <option disabled <?php echo !isset($item_details['category']) ? 'selected' : ''; ?>>Select Category</option>
            <option value="Electronics" <?php echo ($item_details['category'] ?? '') === 'Electronics' ? 'selected' : ''; ?>>Electronics</option>
            <option value="Clothing" <?php echo ($item_details['category'] ?? '') === 'Clothing' ? 'selected' : ''; ?>>Clothing</option>
            <option value="Accessories" <?php echo ($item_details['category'] ?? '') === 'Accessories' ? 'selected' : ''; ?>>Accessories</option>
            <option value="Documents" <?php echo ($item_details['category'] ?? '') === 'Documents' ? 'selected' : ''; ?>>Documents</option>
            <option value="Keys" <?php echo ($item_details['category'] ?? '') === 'Keys' ? 'selected' : ''; ?>>Keys</option>
            <option value="Bags" <?php echo ($item_details['category'] ?? '') === 'Bags' ? 'selected' : ''; ?>>Bags</option>
            <option value="Wallets" <?php echo ($item_details['category'] ?? '') === 'Wallets' ? 'selected' : ''; ?>>Wallets</option>
            <option value="Jewelry" <?php echo ($item_details['category'] ?? '') === 'Jewelry' ? 'selected' : ''; ?>>Jewelry</option>
            <option value="Books" <?php echo ($item_details['category'] ?? '') === 'Books' ? 'selected' : ''; ?>>Books</option>
            <option value="Other" <?php echo ($item_details['category'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
          </select>
        </div>
      </div>

      <input type="text" name="location" placeholder="Location Found" value="<?php echo htmlspecialchars($item_details['found_location'] ?? ''); ?>" required> <!-- Corrected placeholder and name to 'found_location' -->
      <button type="submit">SUBMIT</button>
    </form>
  </div>

  <script>
    // JavaScript for image preview
    document.getElementById('item_image').addEventListener('change', function(event) {
      const [file] = event.target.files;
      if (file) {
        const preview = document.getElementById('image_preview_found'); // Corrected ID
        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
      } else {
        document.getElementById('image_preview_found').style.display = 'none'; // Corrected ID
        document.getElementById('image_preview_found').src = '';
      }
    });

    // Display existing image if available on load
    window.onload = function() {
        const existingImagePath = document.getElementById('image_preview_found').src; // Corrected ID
        if (existingImagePath && existingImagePath !== window.location.href) { // Check if src is not empty and not just the current page URL
            document.getElementById('image_preview_found').style.display = 'block'; // Corrected ID
        }
    };
  </script>

  <?php include 'message_modal.php'; ?>

</body>
</html>
