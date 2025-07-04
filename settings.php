<?php
session_start();
$isDark = isset($_SESSION['dark_mode']) && $_SESSION['dark_mode'];
require_once 'db_connect.php';
require_once 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to access settings.";
    header("Location: login.php"); // Changed to login.php
    exit();
}

$user_full_name = $_SESSION['full_name'] ?? 'User';
$isDark = isset($_SESSION['dark_mode']) && $_SESSION['dark_mode'];

// Fetch user profile image path for display
$user_profile_image_path = '';
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT profile_image_path FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $user_profile_image_path = $row['profile_image_path'];
    }
    $stmt->close();
}
closeDbConnection($conn);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Settings - FoundIt</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
      display: block; /* Override flex from unified_styles.css body */
      height: auto;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.875rem; /* 30px */
    }

    .logo {
      font-size: 2rem; /* 32px */
      font-weight: 800;
    }

    .icon-btn {
      width: 2.375rem; /* 38px */
      height: 2.375rem; /* 38px */
      border-radius: 50%;
      background-color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid #000;
      cursor: pointer;
      transition: all 0.3s ease;
      color: #000;
      font-size: 1.125rem; /* 18px */
      text-decoration: none; /* For a tag */
    }

    .icon-btn:hover {
      background-color: #000;
      color: #f5ff9c;
      transform: scale(1.1);
    }

    .settings-container {
      background-color: #fffdd0;
      padding: 1.875rem; /* 30px */
      border-radius: 1rem; /* 16px */
      max-width: 37.5rem; /* 600px */
      margin: 1.25rem auto; /* 20px auto */
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .settings-header {
      display: flex;
      align-items: center;
      margin-bottom: 1.875rem; /* 30px */
    }

    .settings-header .back-btn {
      margin-right: 1.25rem; /* 20px */
      width: 2.375rem;
      height: 2.375rem;
      font-size: 1.125rem;
    }

    .settings-header .settings-title {
      font-size: 2rem; /* 32px */
      font-weight: 700;
      color: #333;
    }

    .profile-link {
        text-decoration: none;
        color: inherit;
    }

    .setting-tile {
      background-color: white;
      padding: 1.25rem; /* 20px */
      border-radius: 0.75rem; /* 12px */
      margin-bottom: 1.25rem; /* 20px */
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      display: flex;
      align-items: center;
      justify-content: space-between;
      transition: transform 0.2s ease;
    }

    .setting-tile:hover {
      transform: translateY(-3px);
    }

    .profile-box {
      gap: 1.25rem; /* 20px */
    }

    .profile-img {
      width: 3.75rem; /* 60px */
      height: 3.75rem; /* 60px */
      background-color: #8b1e1e;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.875rem; /* 30px */
      color: white;
      overflow: hidden;
      border: 2px solid #ccc;
    }

    .profile-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-texts {
      flex-grow: 1;
      text-align: left;
    }

    .setting-title {
      font-size: 1.25rem; /* 20px */
      font-weight: 600;
      color: #333;
    }

    .setting-sub {
      font-size: 0.875rem; /* 14px */
      color: #777;
    }

    .font-size-slider {
      flex-grow: 1;
      text-align: right;
    }

    .font-size-slider input[type="range"] {
      width: 100%;
      max-width: 12.5rem; /* 200px */
      -webkit-appearance: none;
      height: 8px;
      background: #ddd;
      border-radius: 5px;
      outline: none;
      opacity: 0.7;
      -webkit-transition: .2s;
      transition: opacity .2s;
    }

    .font-size-slider input[type="range"]::-webkit-slider-thumb {
      -webkit-appearance: none;
      appearance: none;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      background: #8b1e1e;
      cursor: pointer;
    }

    .font-size-slider input[type="range"]::-moz-range-thumb {
      width: 20px;
      height: 20px;
      border-radius: 50%;
      background: #8b1e1e;
      cursor: pointer;
    }

    .font-size-slider small {
      display: block;
      margin-top: 0.5rem; /* 8px */
      color: #777;
      font-size: 0.75rem; /* 12px */
    }

    .toggle-switch {
      position: relative;
      display: inline-block;
      width: 3.75rem; /* 60px */
      height: 2.125rem; /* 34px */
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
      border-radius: 2.125rem; /* 34px */
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 1.625rem; /* 26px */
      width: 1.625rem; /* 26px */
      left: 0.25rem; /* 4px */
      bottom: 0.25rem; /* 4px */
      background-color: white;
      transition: .4s;
      border-radius: 50%;
    }

    input:checked + .slider {
      background-color: #8b1e1e;
    }

    input:focus + .slider {
      box-shadow: 0 0 1px #8b1e1e;
    }

    input:checked + .slider:before {
      transform: translateX(1.625rem); /* 26px */
    }

    .logout-btn {
      background-color: #dc3545;
      color: white;
      padding: 0.75rem 1.25rem; /* 12px 20px */
      border: none;
      border-radius: 0.5rem; /* 8px */
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.3s ease;
      display: block;
      width: 100%;
      margin-top: 1.25rem; /* 20px */
      text-decoration: none; /* For a tag */
      text-align: center;
    }

    .logout-btn:hover {
      background-color: #c82333;
    }

    /* Dark Mode Styles */
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
      color: #555 !important;
    }
    .dark-mode .icon-btn:hover svg {
      fill: #555 !important;
    }
    .dark-mode .font-size-slider input[type="range"] {
      background: #777;
    }
    .dark-mode .font-size-slider input[type="range"]::-webkit-slider-thumb {
      background: #eee;
    }
    .dark-mode .font-size-slider input[type="range"]::-moz-range-thumb {
      background: #eee;
    }
    .dark-mode .slider {
      background-color: #777;
    }
    input:checked + .slider.dark-mode-checked {
      background-color: #8b1e1e; /* Keep original accent color for checked state */
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
      .icon-btn {
        width: 34px;
        height: 34px;
        font-size: 16px;
      }
      .settings-container {
        padding: 20px;
        margin: 10px auto;
      }
      .settings-header .settings-title {
        font-size: 24px;
      }
      .setting-tile {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }
      .profile-box {
        flex-direction: row; /* Keep profile image and text side-by-side */
        align-items: center;
      }
      .profile-texts {
        text-align: left;
      }
      .font-size-slider {
        text-align: left;
        width: 100%;
      }
      .font-size-slider input[type="range"] {
        max-width: 100%;
      }
      .logout-btn {
        padding: 10px 15px;
        font-size: 0.95rem;
      }
    }
  </style>
</head>
<body>

  <div class="header">
    <div class="logo">FoundIt</div>
    <a href="homepage.php" class="icon-btn" title="Home">
      <svg fill="#000000" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
    </a>
  </div>

  <div class="settings-container">

    <!-- Header with Back and Title -->
    <div class="settings-header">
      <a href="homepage.php" class="icon-btn back-btn" title="Back">
        <svg viewBox="0 0 24 24">
          <path d="M15 18l-6-6 6-6"/>
        </svg>
      </a>
      <div class="settings-title">Settings</div>
    </div>

    <!-- Profile -->
    <a href="profile.php" class="profile-link">
      <div class="setting-tile profile-box">
        <div class="profile-img">
            <?php if (!empty($user_profile_image_path) && file_exists($user_profile_image_path)): ?>
                <img src="<?php echo htmlspecialchars(BASE_URL . $user_profile_image_path); ?>" alt="Profile Photo">
            <?php else: ?>
                <?php echo htmlspecialchars(substr($user_full_name, 0, 1)); ?>
            <?php endif; ?>
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
        <span class="slider round"></span>
      </label>
    </div>

    <!-- Logout Button -->
    <a href="logout.php" class="logout-btn">Logout</a>
  </div>

  <script>
    // Font Size Adjustment
    const fontSizeSlider = document.getElementById('fontSizeSlider');
    const htmlElement = document.documentElement; // Target the root html element

    // Load saved font size
    const savedFontSize = localStorage.getItem('fontSize');
    if (savedFontSize) {
      htmlElement.style.fontSize = savedFontSize + 'px';
      fontSizeSlider.value = savedFontSize;
    }

    fontSizeSlider.addEventListener('input', function() {
      const newFontSize = this.value;
      htmlElement.style.fontSize = newFontSize + 'px';
      localStorage.setItem('fontSize', newFontSize);
    });

    // Dark Mode Toggle
    const darkModeToggle = document.getElementById('darkModeToggle');
    const body = document.body;

    // Load saved dark mode preference
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

    // Dynamic style for dark mode to ensure it applies correctly
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
        color: #555 !important;
      }
      .dark-mode .icon-btn:hover svg {
        fill: #555 !important;
      }
      .dark-mode .font-size-slider input[type="range"] {
        background: #777;
      }
      .dark-mode .font-size-slider input[type="range"]::-webkit-slider-thumb {
        background: #eee;
      }
      .dark-mode .font-size-slider input[type="range"]::-moz-range-thumb {
        background: #eee;
      }
      .dark-mode .slider {
        background-color: #777;
      }
      input:checked + .slider { /* This targets the checked state for the slider */
        background-color: #8b1e1e; /* Keep original accent color for checked state */
      }
    `;
    document.head.appendChild(style);
  </script>

  <?php include 'message_modal.php'; ?>

</body>
</html>
