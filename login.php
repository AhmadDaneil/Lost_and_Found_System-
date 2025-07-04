<?php
session_start(); // Start the session
require_once 'db_connect.php'; // Include the database connection file
require_once 'config.php'; // Include config for BASE_URL

// Enable error reporting for debugging
error_reporting(E_ALL); // Report all PHP errors
ini_set('display_errors', 1); // Display errors on the screen

// Check for "remember me" cookie and log in automatically
if (isset($_COOKIE['remember_user_id']) && !isset($_SESSION['user_id'])) {
    $conn = getDbConnection();
    $user_id = $_COOKIE['remember_user_id'];

    $stmt = $conn->prepare("SELECT id, full_name, user_type FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($user_id, $full_name, $user_type);
        $stmt->fetch();

        $_SESSION['user_id'] = $user_id;
        $_SESSION['full_name'] = $full_name;
        $_SESSION['user_type'] = $user_type;
        $_SESSION['logged_in'] = true;

        if ($user_type === 'admin') {
            header("Location: admin_homepage.php");
        } else {
            header("Location: user_dashboard.php");
        }
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
  <title>Login</title>
  <link rel="stylesheet" href="unified_styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <style>
    /* Add specific login styles or override unified_styles.css here */
    body {
      height: 100vh;
      background-color: #f9fb9f; /* pale yellow */
      display: flex;
      justify-content: center;
      align-items: center;
      font-family: 'Poppins', sans-serif; /* Changed to Poppins for consistency */
    }

    .login-container {
      background-color: #fffdd0; /* Light yellow background */
      padding: 40px;
      border-radius: 15px;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
      text-align: center;
      width: 100%;
      max-width: 400px;
      position: relative; /* For back button positioning */
    }

    .back-button-header {
      position: absolute;
      top: 20px;
      left: 20px;
    }

    .back-button {
      display: inline-flex;
      align-items: center;
      text-decoration: none;
      color: #333;
      font-weight: 600;
      transition: color 0.3s ease;
    }

    .back-button i {
      margin-right: 8px;
      font-size: 18px;
    }

    .back-button:hover {
      color: #8b1e1e; /* Dark red on hover */
    }

    h2 {
      color: #333;
      margin-bottom: 30px;
      font-size: 28px;
      font-weight: 700;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 1rem;
      box-sizing: border-box;
    }

    input[type="email"]::placeholder,
    input[type="password"]::placeholder {
      color: #aaa;
    }

    .remember {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.9rem;
      color: #555;
    }

    .remember label {
      display: flex;
      align-items: center;
      gap: 5px;
      cursor: pointer;
    }

    .remember input[type="checkbox"] {
      accent-color: #8b1e1e; /* Custom checkbox color */
    }

    .forgot {
      color: #1a53ff;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s ease;
    }

    .forgot:hover {
      text-decoration: underline;
    }

    .login-btn {
      background-color: #00ff00;
      color: white; /* Changed from black to white for consistency */
      padding: 12px 10px;
      border: none;
      border-radius: 8px;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
    }

    .login-btn i {
      font-size: 1.2rem;
    }

    .login-btn:hover {
      background-color: #00ff00; /* Changed from #004d00 to #6a1515 for consistency */
    }

    .separator {
      margin: 30px 0;
      border-bottom: 1px solid #eee;
      position: relative;
      line-height: 0.1em;
    }

    .separator::before {
      content: 'OR';
      background: #fffdd0; /* Match background */
      padding: 0 10px;
      color: #888;
      font-size: 0.9rem;
    }

    .social-icons {
      display: flex;
      justify-content: center;
      gap: 20px;
      font-size: 2.2rem;
      color: #555;
    }

    .social-icons i {
      cursor: pointer;
      transition: color 0.3s ease, transform 0.3s ease;
    }

    .social-icons i:hover {
      color: #8b1e1e; /* Dark red on hover */
      transform: scale(1.1);
    }

    .signup-link {
      margin-top: 30px;
      font-size: 0.9rem;
      color: #555;
    }

    .signup-link a {
      color: #8b1e1e;
      text-decoration: none;
      font-weight: 500;
      transition: text-decoration 0.3s ease;
    }

    /* Responsive adjustments */
    @media (max-width: 480px) {
      .login-container {
        padding: 30px 20px;
        margin: 10px;
      }

      h2 {
        font-size: 24px;
      }

      .login-btn {
        font-size: 1rem;
        padding: 5px 15px;
      }

      .social-icons {
        font-size: 1.8rem;
      }
    }
  </style>
</head>
<body>

  <div class="login-container">
    <div class="back-button-header">
    <a href="index.html" class="back-button">
    <i class="fas fa-arrow-left"></i> Back
    </a>
  </div>
    <h2>LOG IN</h2>
    <!-- Corrected form action, method, and input names -->
    <form action="process_login.php" method="POST">
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>

    <div class="remember">
        <label>
            <input type="checkbox" name="remember_me"> Remember me?
        </label>
        <a href="forgot-password.php" class="forgot">Forgot your password?</a> <!-- Updated from forgot-password.html to forgot-password.php -->
    </div>

      <button type="submit" class="login-btn"><i class="fas fa-sign-in-alt"></i> LOG IN</button>
    </form>

    <div class="separator"></div>

    <div class="social-icons">
      <i class="fab fa-google"></i>
      <i class="fab fa-facebook-f"></i>
      <i class="fab fa-apple"></i>
    </div>

    <div class="signup-link">
      Don't have an account? <a href="signup.php">Sign Up</a> <!-- Updated from signup.html to signup.php -->
    </div>
  </div>

  <!-- Include the message modal at the end of the body -->
  <?php include 'message_modal.php'; ?>

</body>
</html>
