<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['error_message'] = "Unauthorized access. You must be logged in as an administrator.";
    header("Location: login.php"); // Updated from login.html to login.php
    exit();
}

$user_id_to_edit = $_GET['id'] ?? null;
$user_details = null;

if ($user_id_to_edit) {
    $conn = getDbConnection();

    // Fetch user details for editing
    $stmt = $conn->prepare("SELECT id, full_name, email, phone_number, city, country, telegram_username, gender, user_type FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user_details = $result->fetch_assoc();
    } else {
        $_SESSION['error_message'] = "User not found.";
        header("Location: admin_users.php");
        exit();
    }
    $stmt->close();
    closeDbConnection($conn);
} else {
    $_SESSION['error_message'] = "No user ID provided for editing.";
    header("Location: admin_users.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit User - FoundIt Admin</title>
  <link rel="stylesheet" href="unified_styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet"/>
  <style>
    /* Re-use form styles from report_lost_form.php / profile.php */
    body {
      background-color: #f5ff9c;
      padding: 20px;
      display: block; /* Override flex from unified_styles.css body */
      height: auto;
    }

    .form-container {
      background-color: #fffdd0;
      padding: 40px 30px 30px;
      border-radius: 16px;
      max-width: 700px;
      margin: 20px auto;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      position: relative;
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
      color: #000;
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

    .form-group {
      margin-bottom: 10px;
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
    .form-group select {
      width: 100%;
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1rem;
      box-sizing: border-box;
    }

    .gender-options, .user-type-options {
        display: flex;
        gap: 20px;
        margin-top: 5px;
    }

    .gender-options label, .user-type-options label {
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

    .back-to-users {
        display: inline-flex;
        align-items: center;
        text-decoration: none;
        color: #000;
        font-weight: 600;
        margin-bottom: 20px;
        transition: color 0.3s ease;
    }

    .back-to-users svg {
        width: 20px;
        height: 20px;
        fill: #000;
        margin-right: 8px;
    }

    .back-to-users:hover {
        color: #555;
    }

    .back-to-users:hover svg {
        fill: #555;
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
  <div class="form-container">
    <header>
      <h1>FoundIt</h1>
      <a href="admin_homepage.php" class="home-icon" title="Admin Home"><i class="fas fa-home"></i></a>
    </header>

    <h2>Edit User: <?php echo htmlspecialchars($user_details['full_name'] ?? 'N/A'); ?></h2>

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

    <form action="admin_update_user.php" method="POST">
      <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_details['id']); ?>">

      <div class="form-group">
        <label for="full_name">Full Name</label>
        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_details['full_name'] ?? ''); ?>" required />
      </div>

      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_details['email'] ?? ''); ?>" required readonly />
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
        <label for="telegram_username">Telegram Username</label>
        <input type="text" id="telegram_username" name="telegram_username" value="<?php echo htmlspecialchars($user_details['telegram_username'] ?? ''); ?>" placeholder="@username" />
      </div>

      <div class="form-group">
        <label>Gender</label>
        <div class="gender-options">
            <label><input type="radio" name="gender" value="Male" <?php echo ($user_details['gender'] ?? '') === 'Male' ? 'checked' : ''; ?>> Male</label>
            <label><input type="radio" name="gender" value="Female" <?php echo ($user_details['gender'] ?? '') === 'Female' ? 'checked' : ''; ?>> Female</label>
            <label><input type="radio" name="gender" value="Other" <?php echo ($user_details['gender'] ?? '') === 'Other' ? 'checked' : ''; ?>> Other</label>
        </div>
      </div>

      <div class="form-group">
        <label>User Type</label>
        <div class="user-type-options">
            <label><input type="radio" name="user_type" value="user" <?php echo ($user_details['user_type'] ?? '') === 'user' ? 'checked' : ''; ?>> User</label>
            <label><input type="radio" name="user_type" value="admin" <?php echo ($user_details['user_type'] ?? '') === 'admin' ? 'checked' : ''; ?>> Admin</label>
        </div>
      </div>

      <button type="submit" class="save-btn">Save Changes</button>
    </form>
  </div>

  <?php include 'message_modal.php'; ?>

</body>
</html>
