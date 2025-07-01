<?php
session_start();
require_once 'db_connect.php'; // Include if you plan to save settings to DB
require_once 'config.php';    // Include if you use BASE_URL or other config vars

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to access settings.";
    header("Location: login.html");
    exit();
}

$user_full_name = $_SESSION['full_name'] ?? 'User';
// In a real application, you'd fetch user settings from the DB here
// For now, we'll use client-side defaults or simple session values if needed.
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Settings - FoundIt</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet"/>
  <style>
    /* Your existing CSS from unified_styles.css and settings.html's style block */
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
    }

    .icon-btn:hover {
      background-color: #000;
      transform: scale(1.1);
    }

    .icon-btn:hover svg {
      fill: #f5ff9c;
    }

    .icon-btn svg {
      width: 20px;
      height: 20px;
      fill: #000;
      transition: fill 0.3s ease;
    }

    .settings-container {
      background-color: #fffdd0;
      padding: 30px;
      border-radius: 16px;
      max-width: 600px;
      margin: 0 auto;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      position: relative;
    }

    .settings-header {
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 30px;
      position: relative;
    }

    .settings-title {
      font-size: 24px;
      font-weight: 600;
      text-align: center;
    }

    .back-btn {
      position: absolute;
      left: 0;
      top: 0;
    }

    .setting-tile {
      background-color: white;
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 15px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .setting-tile:last-child {
      margin-bottom: 0;
    }

    .setting-title {
      font-size: 18px;
      font-weight: 600;
      color: #333;
    }

    .setting-sub {
      font-size: 14px;
      color: #666;
    }

    .profile-box {
      display: flex;
      align-items: center;
      gap: 15px;
      text-decoration: none; /* For the link */
      color: inherit;
    }

    .profile-img {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background-color: #8b1e1e;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 24px;
      overflow: hidden;
    }

    .profile-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-texts {
      flex-grow: 1;
    }

    /* Font Size Slider */
    .font-size-slider {
      display: flex;
      flex-direction: column;
      align-items: center;
      flex-grow: 1; /* Allow it to take available space */
    }

    .font-size-slider input[type="range"] {
      width: 100%;
      margin-bottom: 5px;
    }

    .font-size-slider small {
      font-size: 12px;
      color: #777;
    }

    /* Toggle Switch for Dark Mode */
    .toggle-switch {
      position: relative;
      display: inline-block;
      width: 50px;
      height: 28px;
    }

    .toggle-switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: .4s;
      border-radius: 28px;
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 20px;
      width: 20px;
      left: 4px;
      bottom: 4px;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
    }

    input:checked + .slider {
      background-color: #8b1e1e;
    }

    input:checked + .slider:before {
      transform: translateX(22px);
    }

    /* Message styling */
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

  <div class="header">
    <div class="logo">FoundIt</div>
    <a href="homepage.php" class="icon-btn" title="Home">
      <svg viewBox="0 0 24 24">
        <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1h-6v-6H10v6H4a1 1 0 0 1-1-1V9.5z"/>
      </svg>
    </a>
  </div>

  <div class="settings-container">

    <!-- Header with Back and Title -->
    <div class="settings-header">
      <a href="homepage.php" class="icon-btn back-btn" title="Back to Home">
        <svg viewBox="0 0 24 24">
          <path d="M15 18l-6-6 6-6"/>
        </svg>
      </a>
      <div class="settings-title">Settings</div>
    </div>

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

    <!-- Profile Link -->
    <a href="profile.php" class="profile-link">
      <div class="setting-tile profile-box">
        <div class="profile-img">
          <!-- Display first initial of full name -->
          <?php echo htmlspecialchars(strtoupper(substr($user_full_name, 0, 1))); ?>
        </div>
        <div class="profile-texts">
          <div class="setting-title"><?php echo htmlspecialchars($user_full_name); ?></div>
          <div class="setting-sub">Manage profile</div>
        </div>
      </div>
    </a>

    <!-- Font Size -->
    <div class="setting-tile">
      <div class="setting-title">Font Size</div>
      <div class="font-size-slider">
        <input type="range" min="12" max="24" value="16" step="2" id="fontSizeSlider" />
        <small>Small - Medium - Large</small>
      </div>
    </div>

    <!-- Dark Mode -->
    <div class="setting-tile">
      <div class="setting-title">Dark Mode</div>
      <label class="toggle-switch">
        <input type="checkbox" id="darkModeToggle">
        <span class="slider"></span>
      </label>
    </div>

    <!-- Notifications -->
    <div class="setting-tile">
      <div class="setting-title">Notifications</div>
      <label class="toggle-switch">
        <input type="checkbox" checked>
        <span class="slider"></span>
      </label>
    </div>

    <!-- About Us -->
    <div class="setting-tile">
      <div class="setting-title">About Us</div>
      <a href="#" class="setting-sub" onclick="alert('FoundIt App - Version 1.0. Developed to help you find and report lost and found items easily.')">Learn more</a>
    </div>

    <!-- Help & Support -->
    <div class="setting-tile">
      <div class="setting-title">Help & Support</div>
      <a href="https://t.me/+ma-FOEPB6wg0ZDU9" target="_blank" class="setting-sub">FoundIt telegram group</a>
    </div>

  </div>

  <script>
    // Client-side JavaScript for Font Size
    const fontSizeSlider = document.getElementById('fontSizeSlider');
    const body = document.body;

    // Load saved font size from localStorage (if any)
    const savedFontSize = localStorage.getItem('fontSize');
    if (savedFontSize) {
      body.style.fontSize = savedFontSize + 'px';
      fontSizeSlider.value = savedFontSize;
    }

    fontSizeSlider.addEventListener('input', function() {
      const newSize = this.value;
      body.style.fontSize = newSize + 'px';
      localStorage.setItem('fontSize', newSize); // Save to localStorage
    });

    // Client-side JavaScript for Dark Mode
    const darkModeToggle = document.getElementById('darkModeToggle');

    // Load saved dark mode state from localStorage (if any)
    const savedDarkMode = localStorage.getItem('darkMode');
    if (savedDarkMode === 'enabled') {
      body.classList.add('dark-mode');
      darkModeToggle.checked = true;
    }

    darkModeToggle.addEventListener('change', function() {
      if (this.checked) {
        body.classList.add('dark-mode');
        localStorage.setItem('darkMode', 'enabled');
      } else {
        body.classList.remove('dark-mode');
        localStorage.setItem('darkMode', 'disabled');
      }
    });

    // Basic dark mode styles (can be expanded in unified_styles.css)
    const style = document.createElement('style');
    style.innerHTML = `
      .dark-mode {
        background-color: #333 !important;
        color: #eee;
      }
      .dark-mode .settings-container,
      .dark-mode .setting-tile,
      .dark-mode .profile-img,
      .dark-mode .slider:before {
        background-color: #555 !important;
        color: #eee !important;
      }
      .dark-mode .logo,
      .dark-mode .settings-title,
      .dark-mode .setting-title,
      .dark-mode .setting-sub {
        color: #eee !important;
      }
      .dark-mode .icon-btn {
        background-color: #555 !important;
        border-color: #eee !important;
      }
      .dark-mode .icon-btn svg {
        fill: #eee !important;
      }
      .dark-mode .icon-btn:hover {
        background-color: #eee !important;
      }
      .dark-mode .icon-btn:hover svg {
        fill: #555 !important;
      }
      .dark-mode .slider {
        background-color: #777;
      }
      input:checked + .slider {
        background-color: #a04040; /* Darker red for dark mode toggle */
      }
    `;
    document.head.appendChild(style);

  </script>

</body>
</html>
