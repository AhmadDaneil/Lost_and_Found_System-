<?php
session_start(); // Start the session
require_once 'db_connect.php'; // Include the database connection file
require_once 'config.php'; // Include config for BASE_URL

// --- DEBUGGING LINES (KEEP THESE DURING DEBUGGING, REMOVE IN PRODUCTION) ---
error_reporting(E_ALL); // Report all PHP errors
ini_set('display_errors', 1); // Display errors on the screen
// --- END DEBUGGING LINES ---

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);

    echo "<h3>Debugging Login Process:</h3>";
    echo "Attempting login for email: <strong>" . htmlspecialchars($email) . "</strong><br>";
    echo "Password entered (plain text): <strong>" . htmlspecialchars($password) . "</strong><br>"; // CAUTION: Do not leave this in production!

    // Basic validation
    if (empty($email) || empty($password)) {
        $_SESSION['error_message'] = "Please enter both email and password.";
        echo "Error: Email or password empty. Redirecting to login.php<br>"; // Updated from login.html to login.php
        header("Location: login.php"); // Updated from login.html to login.php
        exit();
    }

    $conn = getDbConnection(); // Get database connection

    // Prepare a statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, full_name, password_hash, user_type FROM users WHERE email = ?");

    if ($stmt === false) {
        // If prepare statement fails, output the MySQL error
        echo "Prepare statement failed: (" . $conn->errno . ") " . $conn->error . "<br>";
        $_SESSION['error_message'] = "Database error during login preparation.";
        closeDbConnection($conn);
        header("Location: login.php"); // Updated from login.html to login.php
        exit();
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    echo "Number of rows found for email '" . htmlspecialchars($email) . "': <strong>" . $stmt->num_rows . "</strong><br>";

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($user_id, $full_name, $password_hash, $user_type);
        $stmt->fetch();

        echo "User ID: " . htmlspecialchars($user_id) . "<br>";
        echo "Full Name: " . htmlspecialchars($full_name) . "<br>";
        echo "Stored password hash from DB: <strong>" . htmlspecialchars($password_hash) . "</strong><br>";

        // Verify password
        if (password_verify($password, $password_hash)) {
            echo "Password verification: <strong>SUCCESS!</strong><br>";
            // Password is correct, start session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['user_type'] = $user_type;
            $_SESSION['logged_in'] = true;

            // Set cookie for "remember me" (for 30 days)
            if ($remember_me) {
                setcookie('remember_user_id', $user_id, time() + (86400 * 30), "/"); // 86400 = 1 day
                echo "Remember me cookie set.<br>";
            } else {
                // Clear remember me cookie if it exists and not checked
                if (isset($_COOKIE['remember_user_id'])) {
                    setcookie('remember_user_id', '', time() - 3600, "/");
                    echo "Remember me cookie cleared.<br>";
                }
            }

            echo "Session variables set. Redirecting to dashboard...<br>";
            // Redirect based on user type
            if ($user_type === 'admin') {
                header("Location: admin_homepage.php"); // Updated from admin_homepage.html to admin_homepage.php
            } else {
                header("Location: homepage.php"); // Redirect to user homepage (ensure this is .php now)
            }
            exit();
        } else {
            echo "Password verification: <strong>FAILED!</strong><br>";
            $_SESSION['error_message'] = "Invalid email or password.";
            echo "Redirecting to login.php with error.<br>"; // Updated from login.html to login.php
            header("Location: login.php"); // Updated from login.html to login.php
            exit();
        }
    } else {
        echo "No user found with that email.<br>";
        $_SESSION['error_message'] = "Invalid email or password.";
        echo "Redirecting to login.php with error.<br>"; // Updated from login.html to login.php
        header("Location: login.php"); // Updated from login.html to login.php
        exit();
    }

    $stmt->close();
    closeDbConnection($conn);
} else {
    // If accessed directly without POST request
    echo "Accessed process_login.php directly without POST. Redirecting to login.php<br>"; // Updated from login.html to login.php
    header("Location: login.php"); // Updated from login.html to login.php
    exit();
}
?>
