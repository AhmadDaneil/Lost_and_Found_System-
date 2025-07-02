<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to view your profile.";
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_details = null;

$conn = getDbConnection();

$stmt = $conn->prepare("SELECT full_name, email, phone_number, city, country, telegram_username, gender, profile_image_path FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user_details = $result->fetch_assoc();
        if (!isset($_SESSION['email'])) {
            $_SESSION['email'] = $user_details['email'];
        }
    } else {
        $_SESSION['error_message'] = "User profile not found.";
        header("Location: homepage.php");
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
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }
    body {
      background-color: #f5ff9c;
      padding: 20px;
      display: block;
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
      margin-top: 30px;
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
    .form-group input {
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
    @media (max-width: 600px) {
      .profile-container { padding: 20px; margin: 10px auto; }
      .header { margin-bottom: 20px; }
      .logo { font-size: 28px; }
      .icon-btn { width: 34px; height: 34px; font-size: 16px; }
      .profile-img { width: 100px; height: 100px; font-size: 40px; }
      .btn-upload, .remove-photo-btn { padding: 6px 12px; font-size: 13px; }
      .form-group input { padding: 10px; font-size: 0.9rem; }
      .save-btn { padding: 10px; font-size: 1rem; }
    }
  </style>
</head>
<body>

  <div class="header">
    <div class="logo">FoundIt</div>
    <a href="settings.php" class="icon-btn" title="Settings">
      <i class="fas fa-cog"></i>
    </a>
  </div>

  <div class="profile-container">
    <div style="text-align: left; margin-bottom: 20px;">
      <a href="homepage.php" class="icon-btn" title="Back to Homepage">
        <i class="fas fa-arrow-left"></i>
      </a>
    </div>

    <div class="profile-photo-section">
      <div class="profile-img" id="profileImg">
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
      <input type="hidden" name="remove_photo" id="removePhotoFlag" value="0">

      <div class="form-group"><label for="name">Full Name</label>
        <input type="text" id="name" name="full_name" value="<?php echo htmlspecialchars($user_details['full_name'] ?? ''); ?>" oninput="updateInitials()" required />
      </div>

      <div class="form-group"><label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_details['email'] ?? ''); ?>" readonly />
      </div>

      <div class="form-group"><label for="phone_number">Phone</label>
        <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user_details['phone_number'] ?? ''); ?>" />
      </div>

      <div class="form-group"><label for="city">City</label>
        <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user_details['city'] ?? ''); ?>" />
      </div>

      <div class="form-group"><label for="country">Country</label>
        <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($user_details['country'] ?? ''); ?>" />
      </div>

      <div class="form-group"><label for="telegram">Telegram Username</label>
        <input type="text" id="telegram" name="telegram_username" value="<?php echo htmlspecialchars($user_details['telegram_username'] ?? ''); ?>" placeholder="@yourusername" />
      </div>

      <div class="form-group"><label>Gender</label>
        <div class="gender-options">
          <label><input type="radio" name="gender" value="Male" <?php echo ($user_details['gender'] ?? '') === 'Male' ? 'checked' : ''; ?>> Male</label>
          <label><input type="radio" name="gender" value="Female" <?php echo ($user_details['gender'] ?? '') === 'Female' ? 'checked' : ''; ?>> Female</label>
          <label><input type="radio" name="gender" value="Other" <?php echo ($user_details['gender'] ?? '') === 'Other' ? 'checked' : ''; ?>> Other</label>
        </div>
      </div>

      <button type="submit" class="save-btn">Save Changes</button>
    </form>

    <form class="password-form" action="process_password_change.php" method="POST">
      <h2>Change Password</h2>
      <div class="form-group"><label for="current_password">Current Password</label>
        <input type="password" id="current_password" name="current_password" required />
      </div>
      <div class="form-group"><label for="new_password">New Password</label>
        <input type="password" id="new_password" name="new_password" required />
      </div>
      <div class="form-group"><label for="confirm_new_password">Confirm New Password</label>
        <input type="password" id="confirm_new_password" name="confirm_new_password" required />
      </div>
      <button type="submit" class="save-btn">Change Password</button>
    </form>
  </div>

  <script>
    const fileInput = document.getElementById('profileUpload');
    const profileImg = document.getElementById('profileImg');
    const initialsSpan = document.getElementById('initials');
    const removePhotoFlag = document.getElementById('removePhotoFlag');

    fileInput.addEventListener('change', function () {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
          profileImg.innerHTML = `<img src="${e.target.result}" alt="Profile Photo">`;
          removePhotoFlag.value = '0';
        };
        reader.readAsDataURL(file);
      }
    });

    function updateInitials() {
      const name = document.getElementById('name').value.trim();
      if (!profileImg.querySelector('img')) {
        profileImg.innerHTML = `<span id="initials">${name.length > 0 ? name[0].toUpperCase() : '?'}</span>`;
      }
    }

    function removePhoto() {
      const name = document.getElementById('name').value.trim();
      profileImg.innerHTML = `<span id="initials">${name.length > 0 ? name[0].toUpperCase() : '?'}</span>`;
      removePhotoFlag.value = '1';
      fileInput.value = '';
    }

    window.addEventListener('load', updateInitials);
  </script>

  <?php include 'message_modal.php'; ?>

</body>
</html>
