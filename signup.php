<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>FoundIt - Sign Up</title>
  <link rel="stylesheet" href="unified_styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
</head>
<body>
  <div class="signup-container">
    <div class="left-panel">
      <div class="vertical-text">
        <span>S</span>
        <span>I</span>
        <span>G</span>
        <img src="images/foundit_logo.png" alt="FoundIt Logo" class="logo">
        <span>U</span>
        <span>P</span>
      </div>
    </div>

    <div class="right-panel">
      <div class = "back-button-header">
        <a href = "index.html" class="back-button">
          <i class="fas fa-arrow-left"></i> Back
        </a>
      </div>

      <!-- Form action changed to process_signup.php -->
      <form action="process_signup.php" method="POST">
        <div class="form-row">
          <input type="text" name="full_name" placeholder="Full Name" required>
          <input type="text" name="phone_number" placeholder="Phone Number" required>
        </div>
        <div class="form-row">
          <input type="email" name="email" placeholder="Email" required>
          <input type="text" name="city" placeholder="City" required>
        </div>
        <div class="form-row">
          <input type="password" name="password" placeholder="Password" required>
          <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        </div>
        <div class="form-row">
          <input type="text" name="country" placeholder="Country" required>
          <!-- Added Telegram Username field -->
          <input type="text" name="telegram_username" placeholder="Telegram Username (e.g., @yourusername)">
        </div>
        <div class="form-row gender-row">
        <label>Gender:</label>
        <div class="gender-options">
            <!-- Changed to radio buttons for single selection -->
            <label><input type="radio" name="gender" value="Male"> Male</label>
            <label><input type="radio" name="gender" value="Female"> Female</label>
        </div>
        </div>
        <button type="submit" class="btn"> SIGN UP <i class="fas fa-arrow-right"></i></button>
      </form>

      <div class="login-link">
        Already have an account? <a href="login.html">Log In</a>
      </div>
    </div>
  </div>
  <?php include 'message_modal.php'; ?>
</body>
</html>