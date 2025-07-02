<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to access settings.";
    header("Location: login.html");
    exit();
}

$user_full_name = $_SESSION['full_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Settings - FoundIt</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet"/>
  <style>
    html {
      font-size: 16px; /* Default base font size */
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background-color: #f5ff9c;
      padding: 1.25rem;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.875rem;
    }

    .logo {
      font-size: 2rem;
      font-weight: 800;
      text-shadow: 2px 2px 2px rgba(0, 0, 0, 0.1);
    }

    .icon-btn {
      width: 2.375rem;
      height: 2.375rem;
      border-radius: 50%;
      background-color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 0.125rem solid #000;
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
      width: 1.25rem;
      height: 1.25rem;
      fill: #000;
      transition: fill 0.3s ease;
    }

    .settings-container {
      background-color: #fffdd0;
      padding: 1.875rem;
      border-radius: 1rem;
      max-width: 600px;
      margin: 0 auto;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      position: relative;
    }

    .settings-header {
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1.875rem;
      position: relative;
    }

    .settings-title {
      font-size: 1.5rem;
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
      padding: 1.25rem;
      border-radius: 0.75rem;
      margin-bottom: 0.9375rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .setting-title {
      font-size: 1.125rem;
      font-weight: 600;
      color: #333;
    }

    .setting-sub {
      font-size: 0.875rem;
      color: #666;
    }

    .profile-box {
      display: flex;
      align-items: center;
      gap: 0.9375rem;
      text-decoration: none;
      color: inherit;
    }

    .profile-img {
      width: 3.125rem;
      height: 3.125rem;
      border-radius: 50%;
      background-color: #8b1e1e;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 1.5rem;
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

    .font-size-slider {
      display: flex;
      flex-direction: column;
      align-items: center;
      flex-grow: 1;
    }

    .font-size-slider input[type="range"] {
      width: 100%;
      margin-bottom: 0.3125rem;
    }

    .font-size-slider small {
      font-size: 0.75rem;
      color: #777;
    }

    .toggle-switch {
      position: relative;
      display: inline-block;
      width: 3.125rem;
      height: 1.75rem;
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
      border-radius: 1.75rem;
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 1.25rem;
      width: 1.25rem;
      left: 0.25rem;
      bottom: 0.25rem;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
    }

    input:checked + .slider {
      background-color: #8b1e1e;
    }

    input:checked + .slider:before {
      transform: translateX(1.375rem);
    }

    .message {
      padding: 0.625rem;
      margin-bottom: 0.9375rem;
      border-radius: 0.3125rem;
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

    <div class="settings-header">
      <a href="homepage.php" class="icon-btn back-btn" title="Back to Home">
        <svg viewBox="0 0 24 24">
          <path d="M15 18l-6-6 6-6"/>
        </svg>
      </a>
      <div class="settings-title">Settings</div>
    </div>

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

    <a href="profile.php" class="profile-link">
      <div class="setting-tile profile-box">
        <div class="profile-img">
          <?php echo htmlspecialchars(strtoupper(substr($user_full_name, 0, 1))); ?>
        </div>
        <div class="profile-texts">
          <div class="setting-title"><?php echo htmlspecialchars($user_full_name); ?></div>
          <div class="setting-sub">Manage profile</div>
        </div>
      </div>
    </a>

    <div class="setting-tile">
      <div class="setting-title">Font Size</div>
      <div class="font-size-slider">
        <input type="range" min="12" max="24" value="16" step="2" id="fontSizeSlider" />
        <small>Small - Medium - Large</small>
      </div>
    </div>

    <div class="setting-tile">
      <div class="setting-title">Dark Mode</div>
      <label class="toggle-switch">
        <input type="checkbox" id="darkModeToggle">
        <span class="slider"></span>
      </label>
    </div>

    <div class="setting-tile">
      <div class="setting-title">Notifications</div>
      <label class="toggle-switch">
        <input type="checkbox" checked>
        <span class="slider"></span>
      </label>
    </div>

    <div class="setting-tile">
      <div class="setting-title">About Us</div>
      <a href="#" class="setting-sub" onclick="alert('FoundIt App - Version 1.0. Developed to help you find and report lost and found items easily.')">Learn more</a>
    </div>

    <div class="setting-tile">
      <div class="setting-title">Help & Support</div>
      <a href="https://t.me/+ma-FOEPB6wg0ZDU9" target="_blank" class="setting-sub">FoundIt telegram group</a>
    </div>

  </div>

  <script>
    const fontSizeSlider = document.getElementById('fontSizeSlider');
    const html = document.documentElement;

    const savedFontSize = localStorage.getItem('fontSize');
    if (savedFontSize) {
      html.style.fontSize = savedFontSize + 'px';
      fontSizeSlider.value = savedFontSize;
    }

    fontSizeSlider.addEventListener('input', function () {
      const newSize = this.value;
      html.style.fontSize = newSize + 'px';
      localStorage.setItem('fontSize', newSize);
    });

    const darkModeToggle = document.getElementById('darkModeToggle');
    const body = document.body;

    const savedDarkMode = localStorage.getItem('darkMode');
    if (savedDarkMode === 'enabled') {
      body.classList.add('dark-mode');
      darkModeToggle.checked = true;
    }

    darkModeToggle.addEventListener('change', function () {
      if (this.checked) {
        body.classList.add('dark-mode');
        localStorage.setItem('darkMode', 'enabled');
      } else {
        body.classList.remove('dark-mode');
        localStorage.setItem('darkMode', 'disabled');
      }
    });

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
        background-color: #a04040;
      }
    `;
    document.head.appendChild(style);
  </script>

</body>
</html>
