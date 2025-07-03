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
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_details = null;

$conn = getDbConnection();

// Fetch user details
$stmt = $conn->prepare("SELECT full_name, email, phone_number, city, country, telegram_username, gender, profile_image_path FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user_details = $result->fetch_assoc();
    } else {
        // This should ideally not happen if user_id is set in session
        $_SESSION['error_message'] = "User profile not found.";
        header("Location: homepage.php");
        exit();
    }
    $stmt->close();
} else {
    error_log("Error preparing user profile query: " . $conn->error);
    $_SESSION['error_message'] = "Database error during profile retrieval.";
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
  <title>User Profile - FoundIt</title>
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
      text-decoration: none;
    }

    .icon-btn:hover {
      background-color: #000;
      color: #f5ff9c;
      transform: scale(1.1);
    }

    .profile-container {
      background-color: #fffdd0;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 600px;
      text-align: center;
      position: relative;
    }

    .back-btn {
      position: absolute;
      top: 20px;
      left: 20px;
      z-index: 10;
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

    .back-btn:hover {
      background-color: #000;
      color: #f5ff9c;
      transform: scale(1.1);
    }

    .profile-header {
      margin-bottom: 30px;
    }

    .profile-image-container {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      background-color: #8b1e1e;
      color: white;
      font-size: 48px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      overflow: hidden;
      border: 3px solid #ccc;
      position: relative;
    }

    .profile-image-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-image-container .upload-icon-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
        cursor: pointer;
    }

    .profile-image-container:hover .upload-icon-overlay {
        opacity: 1;
    }

    .profile-image-container input[type="file"] {
        display: none;
    }

    .profile-header h2 {
      font-size: 28px;
      color: #333;
      margin-bottom: 10px;
    }

    .profile-header p {
      font-size: 16px;
      color: #777;
    }

    .profile-details {
      text-align: left;
      margin-bottom: 30px;
    }

    .detail-row {
      display: flex;
      justify-content: space-between;
      padding: 10px 0;
      border-bottom: 1px dashed #eee;
    }

    .detail-row:last-child {
      border-bottom: none;
    }

    .detail-row span:first-child {
      font-weight: 600;
      color: #333;
    }

    .detail-row span:last-child {
      color: #555;
    }

    .edit-profile-btn {
      background-color: #8b1e1e;
      color: white;
      padding: 12px 25px;
      border: none;
      border-radius: 8px;
      font-size: 18px;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.3s ease;
      text-decoration: none;
      display: inline-block;
      width: auto;
    }

    .edit-profile-btn:hover {
      background-color: #6a1515;
    }

    .remove-photo-checkbox {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        font-size: 14px;
        color: #555;
        margin-top: 10px;
    }

    /* Form for editing (initially hidden) */
    .edit-form-container {
        display: none; /* Hidden by default */
        background-color: #fffdd0;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 600px;
        text-align: center;
        position: relative;
        margin-top: 20px;
    }

    .edit-form-container h2 {
        font-size: 28px;
        color: #333;
        margin-bottom: 25px;
    }

    .edit-form-container form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .edit-form-container .form-group {
        text-align: left;
    }

    .edit-form-container .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #333;
    }

    .edit-form-container input[type="text"],
    .edit-form-container input[type="email"],
    .edit-form-container input[type="tel"],
    .edit-form-container input[type="password"], /* Added password input type */
    .edit-form-container select {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 16px;
        background-color: white;
        color: #333;
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

    .save-changes-btn, .cancel-edit-btn {
        background-color: #28a745;
        color: white;
        padding: 12px 25px;
        border: none;
        border-radius: 8px;
        font-size: 18px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.3s ease;
        margin-top: 20px;
        width: 100%;
    }

    .cancel-edit-btn {
        background-color: #dc3545;
        margin-top: 10px;
    }

    .save-changes-btn:hover {
        background-color: #218838;
    }

    .cancel-edit-btn:hover {
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
      .icon-btn, .back-btn {
        width: 34px;
        height: 34px;
        font-size: 16px;
      }
      .profile-container, .edit-form-container {
        padding: 20px;
        margin: 10px;
      }
      .profile-image-container {
        width: 100px;
        height: 100px;
        font-size: 40px;
      }
      .profile-header h2 {
        font-size: 24px;
      }
      .profile-header p {
        font-size: 14px;
      }
      .detail-row {
        font-size: 14px;
      }
      .edit-profile-btn, .save-changes-btn, .cancel-edit-btn {
        font-size: 16px;
        padding: 10px 20px;
      }
      .edit-form-container input, .edit-form-container select {
        font-size: 14px;
        padding: 10px 12px;
      }
    }
  </style>
</head>
<body>

  <div class="header">
    <div class="logo">FoundIt</div>
    <div class="icons">
      <a href="homepage.php" class="icon-btn" title="Home">
        <svg fill="#000000" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
      </a>
      <a href="user_dashboard.php" class="icon-btn" title="Dashboard">
        <i class="fas fa-tachometer-alt"></i>
      </a>
      <a href="settings.php" class="icon-btn" title="Settings">
        <i class="fas fa-cog"></i>
      </a>
    </div>
  </div>

  <div class="profile-container" id="viewProfileSection">
    <a href="homepage.php" class="back-btn" title="Back to Home">
      <svg viewBox="0 0 24 24">
        <path d="M15 18l-6-6 6-6"/>
      </svg>
    </a>

    <div class="profile-header">
      <div class="profile-image-container" onclick="document.getElementById('profile_image_upload').click();">
        <?php if (!empty($user_details['profile_image_path']) && file_exists($user_details['profile_image_path'])): ?>
            <img src="<?php echo htmlspecialchars(BASE_URL . $user_details['profile_image_path']); ?>" alt="Profile Photo">
        <?php else: ?>
            <?php echo htmlspecialchars(substr($user_details['full_name'], 0, 1)); ?>
        <?php endif; ?>
        <div class="upload-icon-overlay">
            <i class="fas fa-camera"></i>
        </div>
      </div>
      <h2><?php echo htmlspecialchars($user_details['full_name']); ?></h2>
      <p><?php echo htmlspecialchars($user_details['email']); ?></p>
    </div>

    <div class="profile-details">
      <div class="detail-row">
        <span>Phone Number:</span>
        <span><?php echo htmlspecialchars($user_details['phone_number'] ?? 'N/A'); ?></span>
      </div>
      <div class="detail-row">
        <span>City:</span>
        <span><?php echo htmlspecialchars($user_details['city'] ?? 'N/A'); ?></span>
      </div>
      <div class="detail-row">
        <span>Country:</span>
        <span><?php echo htmlspecialchars($user_details['country'] ?? 'N/A'); ?></span>
      </div>
      <div class="detail-row">
        <span>Telegram:</span>
        <span><?php echo htmlspecialchars($user_details['telegram_username'] ?? 'N/A'); ?></span>
      </div>
      <div class="detail-row">
        <span>Gender:</span>
        <span><?php echo htmlspecialchars($user_details['gender'] ?? 'N/A'); ?></span>
      </div>
    </div>

    <button class="edit-profile-btn" onclick="showEditForm()">Edit Profile</button>
  </div>

  <div class="edit-form-container" id="editProfileSection">
    <h2>Edit Profile</h2>
    <form action="process_profile_update.php" method="POST" enctype="multipart/form-data">
      <input type="file" id="profile_image_upload" name="profile_image" accept="image/*" style="display: none;">

      <div class="form-group">
        <label for="edit_full_name">Full Name</label>
        <input type="text" id="edit_full_name" name="full_name" value="<?php echo htmlspecialchars($user_details['full_name'] ?? ''); ?>" required />
      </div>

      <div class="form-group">
        <label for="edit_phone_number">Phone Number</label>
        <input type="tel" id="edit_phone_number" name="phone_number" value="<?php echo htmlspecialchars($user_details['phone_number'] ?? ''); ?>" />
      </div>

      <div class="form-group">
        <label for="edit_city">City</label>
        <input type="text" id="edit_city" name="city" value="<?php echo htmlspecialchars($user_details['city'] ?? ''); ?>" />
      </div>

      <div class="form-group">
        <label for="edit_country">Country</label>
        <input type="text" id="edit_country" name="country" value="<?php echo htmlspecialchars($user_details['country'] ?? ''); ?>" />
      </div>

      <div class="form-group">
        <label for="edit_telegram_username">Telegram Username</label>
        <input type="text" id="edit_telegram_username" name="telegram_username" value="<?php echo htmlspecialchars($user_details['telegram_username'] ?? ''); ?>" placeholder="@username" />
      </div>

      <div class="form-group">
        <label>Gender</label>
        <div class="gender-options">
            <label><input type="radio" name="gender" value="Male" <?php echo ($user_details['gender'] ?? '') === 'Male' ? 'checked' : ''; ?>> Male</label>
            <label><input type="radio" name="gender" value="Female" <?php echo ($user_details['gender'] ?? '') === 'Female' ? 'checked' : ''; ?>> Female</label>
        </div>
      </div>

      <?php if (!empty($user_details['profile_image_path'])): ?>
      <div class="remove-photo-checkbox">
          <input type="checkbox" id="remove_photo_flag" name="remove_photo_flag" value="1">
          <label for="remove_photo_flag">Remove current profile photo</label>
      </div>
      <?php endif; ?>

      <h3 style="margin-top: 30px; margin-bottom: 15px; color: #333; font-size: 20px; text-align: left; border-top: 1px solid #eee; padding-top: 20px;">Change Password</h3>
      <div class="form-group">
        <label for="current_password">Current Password</label>
        <input type="password" id="current_password" name="current_password" />
      </div>
      <div class="form-group">
        <label for="new_password">New Password</label>
        <input type="password" id="new_password" name="new_password" />
      </div>
      <div class="form-group">
        <label for="confirm_new_password">Confirm New Password</label>
        <input type="password" id="confirm_new_password" name="confirm_new_password" />
      </div>

      <button type="submit" class="save-changes-btn">Save Changes</button>
      <button type="button" class="cancel-edit-btn" onclick="hideEditForm()">Cancel</button>
    </form>
  </div>

  <script>
    function showEditForm() {
      document.getElementById('viewProfileSection').style.display = 'none';
      document.getElementById('editProfileSection').style.display = 'block';
    }

    function hideEditForm() {
      document.getElementById('viewProfileSection').style.display = 'block';
      document.getElementById('editProfileSection').style.display = 'none';
      // Clear password fields when hiding the form
      document.getElementById('current_password').value = '';
      document.getElementById('new_password').value = '';
      document.getElementById('confirm_new_password').value = '';
    }

    // Handle profile image upload preview
    document.getElementById('profile_image_upload').addEventListener('change', function(event) {
        const [file] = event.target.files;
        if (file) {
            const previewImg = document.querySelector('.profile-image-container img');
            const previewText = document.querySelector('.profile-image-container span'); // If using text fallback
            if (previewImg) {
                previewImg.src = URL.createObjectURL(file);
                previewImg.style.display = 'block';
            }
            if (previewText) {
                previewText.style.display = 'none';
            }
            // Uncheck "Remove current profile photo" if a new one is selected
            const removePhotoCheckbox = document.getElementById('remove_photo_flag');
            if (removePhotoCheckbox) {
                removePhotoCheckbox.checked = false;
            }
        }
    });

    // Handle "Remove current profile photo" checkbox
    const removePhotoCheckbox = document.getElementById('remove_photo_flag');
    if (removePhotoCheckbox) {
        removePhotoCheckbox.addEventListener('change', function() {
            const profileImageContainer = document.querySelector('.profile-image-container');
            const previewImg = profileImageContainer.querySelector('img');
            const previewText = profileImageContainer.querySelector('span'); // If using text fallback

            if (this.checked) {
                // Hide image, show initial if applicable
                if (previewImg) {
                    previewImg.style.display = 'none';
                    previewImg.src = ''; // Clear source
                }
                if (previewText) {
                    previewText.style.display = 'block';
                } else {
                    // If no text fallback, just remove img and add a character
                    profileImageContainer.innerHTML = '<div class="upload-icon-overlay"><i class="fas fa-camera"></i></div><?php echo htmlspecialchars(substr($user_details['full_name'], 0, 1)); ?><input type="file" id="profile_image_upload" name="profile_image" accept="image/*" style="display: none;">';
                }
            } else {
                // If unchecked, restore original image or text
                <?php if (!empty($user_details['profile_image_path'])): ?>
                    if (previewImg) {
                        previewImg.src = "<?php echo htmlspecialchars(BASE_URL . $user_details['profile_image_path']); ?>";
                        previewImg.style.display = 'block';
                    }
                    if (previewText) {
                        previewText.style.display = 'none';
                    }
                <?php else: ?>
                    // If there was no original image, and checkbox is unchecked, show text fallback
                    if (previewImg) {
                        previewImg.style.display = 'none';
                    }
                    if (previewText) {
                        previewText.style.display = 'block';
                    } else {
                        profileImageContainer.innerHTML = '<div class="upload-icon-overlay"><i class="fas fa-camera"></i></div><?php echo htmlspecialchars(substr($user_details['full_name'], 0, 1)); ?><input type="file" id="profile_image_upload" name="profile_image" accept="image/*" style="display: none;">';
                    }
                <?php endif; ?>
            }
        });
    }
  </script>

  <?php include 'message_modal.php'; ?>

</body>
</html>
