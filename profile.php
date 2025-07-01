<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in, if not, redirect to login page
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to view your profile.";
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_details = null;

$conn = getDbConnection();

// Fetch user details from the database
$stmt = $conn->prepare("SELECT full_name, email, phone_number, city, country, telegram_username, gender, profile_image_path FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user_details = $result->fetch_assoc();
        // Update session with email if it's not already there (e.g., for display in dashboard)
        if (!isset($_SESSION['email'])) {
            $_SESSION['email'] = $user_details['email'];
        }
    } else {
        $_SESSION['error_message'] = "User profile not found.";
        header("Location: homepage.php"); // Redirect if profile not found
        exit();
    }
    $stmt->close();
} else {
    error_log("Error preparing user profile query: " . $conn->error);
    $_SESSION['error_message'] = "Could not retrieve your profile details.";
    header("Location: homepage.php");
    exit();
}

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Profile - FoundIt</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    /* Re-use and adapt styles from unified_styles.css and other pages */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background-color: #f5ff9c;
      padding: 20px;
      display: block; /* Override flex from unified_styles.css body */
      height: auto;
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
      color: #000;
      font-size: 18px;
      text-decoration: none; /* For a tag */
    }

    .icon-btn:hover {
      background-color: #000;
      color: #f5ff9c;
      transform: scale(1.1);
    }

    .profile-container {
      background-color: #fffdd0;
      padding: 30px;
      border-radius: 16px;
      max-width: 600px;
      margin: 20px auto;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      text-align: center;
    }

    .profile-photo-section {
      margin-bottom: 30px;
    }

    .profile-img {
      width: 120px;
      height: 120px;
      background-color: #8b1e1e;
      border-radius: 50%;
      margin: 0 auto 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 48px;
      color: white;
      overflow: hidden;
      border: 3px solid #ccc;
    }

    .profile-img img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .upload-btn-wrapper {
      position: relative;
      overflow: hidden;
      display: inline-block;
      margin-right: 10px;
    }

    .btn-upload {
      border: 2px solid #8b1e1e;
      color: #8b1e1e;
      background-color: white;
      padding: 8px 15px;
      border-radius: 20px;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .btn-upload:hover {
      background-color: #8b1e1e;
      color: white;
    }

    .upload-btn-wrapper input[type=file] {
      font-size: 100px;
      position: absolute;
      left: 0;
      top: 0;
      opacity: 0;
      cursor: pointer;
    }

    .remove-photo-btn {
      background-color: #dc3545;
      color: white;
      padding: 8px 15px;
      border: none;
      border-radius: 20px;
      font-size: 14px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .remove-photo-btn:hover {
      background-color: #c82333;
    }

    .profile-form, .password-form {
      text-align: left;
      margin-top: 30px; /* Add some space between sections */
      padding-top: 30px;
      border-top: 1px solid #eee;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #333;
    }

    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="tel"],
    .form-group input[type="password"] { /* Added password input type */
      width: 100%;
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1rem;
      box-sizing: border-box;
    }

    .form-group input[readonly] {
        background-color: #f0f0f0;
        cursor: not-allowed;
    }

    .gender-options {
        display: flex;
        gap: 20px;
        margin-top: 5px;
    }

    .gender-options label {
        font-weight: normal;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .save-btn {
      width: 100%;
      padding: 12px;
      background-color: #8b1e1e;
      color: white;
      font-weight: 600;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s ease;
      margin-top: 20px;
    }

    .save-btn:hover {
      background-color: #6a1515;
    }

    /* Responsive adjustments */
    @media (max-width: 600px) {
        .profile-container {
            padding: 20px;
            margin: 10px auto;
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
        .profile-img {
            width: 100px;
            height: 100px;
            font-size: 40px;
        }
        .btn-upload, .remove-photo-btn {
            padding: 6px 12px;
            font-size: 13px;
        }
        .form-group input {
            padding: 10px;
            font-size: 0.9rem;
        }
        .save-btn {
            padding: 10px;
            font-size: 1rem;
        }
    }
  </style>
</head>
<body>

  <div class="header">
    <div class="logo">FoundIt</div>
    <a href="settings.php" class="icon-btn" title="Settings">
      <svg fill="#000000" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.09-.73-1.7-.98l-.35-2.5c-.04-.22-.2-.38-.42-.38H12c-.22 0-.38.16-.42.38l-.35 2.5c-.61.25-1.18.58-1.7.98l-2.49-1c-.22-.09-.49 0-.61.22l-2 3.46c-.12.22-.07.49.12.64l2.11 1.65c-.04.32-.07.64-.07.98s.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1c.52.4 1.09.73 1.7.98l.35 2.5c.04.22.2.38.42.38h3.98c.22 0 .38-.16.42-.38l.35-2.5c.61-.25 1.18-.58 1.7-.98l2.49 1c.22.09.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/></svg>
    </a>
  </div>

  <div class="profile-container">
    <div class="profile-photo-section">
      <div class="profile-img" id="profileImg">
        <!-- Display initials if no image, otherwise the image -->
        <?php if (!empty($user_details['profile_image_path']) && file_exists($user_details['profile_image_path'])): ?>
            <img src="<?php echo htmlspecialchars(BASE_URL . $user_details['profile_image_path']); ?>" alt="Profile Photo">
        <?php else: ?>
            <span id="initials"><?php echo htmlspecialchars(substr($user_details['full_name'] ?? '?', 0, 1)); ?></span>
        <?php endif; ?>
      </div>
      <div class="upload-btn-wrapper">
        <button class="btn-upload">Upload Photo</button>
        <input type="file" id="profileUpload" name="profile_image" accept="image/*" />
      </div>
      <button class="remove-photo-btn" onclick="removePhoto()">Remove Photo</button>
    </div>

    <form class="profile-form" action="process_profile_update.php" method="POST" enctype="multipart/form-data">
      <!-- Hidden input to signal photo removal -->
      <input type="hidden" name="remove_photo" id="removePhotoFlag" value="0">

      <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="full_name" value="<?php echo htmlspecialchars($user_details['full_name'] ?? ''); ?>" oninput="updateInitials()" required />
      </div>

      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_details['email'] ?? ''); ?>" readonly />
      </div>

      <div class="form-group">
        <label for="phone_number">Phone Number</label>
        <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user_details['phone_number'] ?? ''); ?>" />
      </div>

      <div class="form-group">
        <label for="city">City</label>
        <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user_details['city'] ?? ''); ?>" />
      </div>

      <div class="form-group">
        <label for="country">Country</label>
        <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($user_details['country'] ?? ''); ?>" />
      </div>

      <div class="form-group">
        <label for="telegram">Telegram Username</label>
        <input type="text" id="telegram" name="telegram_username" value="<?php echo htmlspecialchars($user_details['telegram_username'] ?? ''); ?>" placeholder="@yourusername" />
      </div>

      <div class="form-group">
        <label>Gender</label>
        <div class="gender-options">
            <label><input type="radio" name="gender" value="Male" <?php echo ($user_details['gender'] ?? '') === 'Male' ? 'checked' : ''; ?>> Male</label>
            <label><input type="radio" name="gender" value="Female" <?php echo ($user_details['gender'] ?? '') === 'Female' ? 'checked' : ''; ?>> Female</label>
            <label><input type="radio" name="gender" value="Other" <?php echo ($user_details['gender'] ?? '') === 'Other' ? 'checked' : ''; ?>> Other</label>
        </div>
      </div>

      <button type="submit" class="save-btn">Save Changes</button>
    </form>

    <!-- Password Change Section -->
    <form class="password-form" action="process_password_change.php" method="POST">
        <h2>Change Password</h2>
        <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" required />
        </div>
        <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" required />
        </div>
        <div class="form-group">
            <label for="confirm_new_password">Confirm New Password</label>
            <input type="password" id="confirm_new_password" name="confirm_new_password" required />
        </div>
        <button type="submit" class="save-btn">Change Password</button>
    </form>

  </div>

  <script>
    const fileInput = document.getElementById('profileUpload');
    const profileImg = document.getElementById('profileImg');
    const initialsSpan = document.getElementById('initials'); // This might not exist if an image is present initially
    const removePhotoFlag = document.getElementById('removePhotoFlag');

    // Function to update the image preview
    fileInput.addEventListener('change', function () {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
          profileImg.innerHTML = `<img src="${e.target.result}" alt="Profile Photo">`;
          removePhotoFlag.value = '0'; // If a new photo is uploaded, reset the remove flag
        };
        reader.readAsDataURL(file);
      }
    });

    // Function to update initials if no photo is present
    function updateInitials() {
      const name = document.getElementById('name').value.trim();
      // Only update initials if there's no image currently displayed
      if (!profileImg.querySelector('img')) {
        profileImg.innerHTML = `<span id="initials">${name.length > 0 ? name[0].toUpperCase() : '?'}</span>`;
      }
    }

    // Function to remove photo and revert to initials
    function removePhoto() {
        profileImg.innerHTML = `<span id="initials">${document.getElementById('name').value.trim().length > 0 ? document.getElementById('name').value.trim()[0].toUpperCase() : '?'}</span>`;
        removePhotoFlag.value = '1'; // Set the flag to indicate photo removal
        fileInput.value = ''; // Clear the file input so it doesn't try to re-upload the old image
    }

    // Call updateInitials on load to ensure correct initial display if no image
    window.addEventListener('load', updateInitials);
  </script>

  <?php include 'message_modal.php'; ?>

</body>
</html>
