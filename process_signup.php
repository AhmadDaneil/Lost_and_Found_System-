<?php
session_start(); // Start the session for potential redirects or messages
require_once 'db_connect.php'; // Include the database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $email = $_POST['email'] ?? '';
    $city = $_POST['city'] ?? '';
    $country = $_POST['country'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $telegram_username = $_POST['telegram_username'] ?? '';
    $gender = $_POST['gender'] ?? ''; // Added gender field

    // Basic validation
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['error_message'] = "Please fill in all required fields.";
        header("Location: signup.php");
        exit();
    }

    if ($password !== $confirm_password) {
        $_SESSION['error_message'] = "Passwords do not match.";
        header("Location: signup.php");
        exit();
    }

    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $conn = getDbConnection(); // Get database connection

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $_SESSION['error_message'] = "Email already registered. Please use a different email or log in.";
            $stmt->close();
            closeDbConnection($conn);
            header("Location: signup.php");
            exit();
        }
        $stmt->close();
    } else {
        error_log("Error preparing email check query: " . $conn->error);
        $_SESSION['error_message'] = "Database error during email check.";
        closeDbConnection($conn);
        header("Location: signup.php");
        exit();
    }


    // Check if Telegram username already exists (if provided)
    if (!empty($telegram_username)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE telegram_username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $telegram_username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $_SESSION['error_message'] = "Telegram username already taken. Please choose another.";
                $stmt->close();
                closeDbConnection($conn);
                header("Location: signup.php"); // Redirect to signup.php, not login.php
                exit();
            }
            $stmt->close();
        } else {
            error_log("Error preparing telegram username check query: " . $conn->error);
            $_SESSION['error_message'] = "Database error during Telegram username check.";
            closeDbConnection($conn);
            header("Location: signup.php");
            exit();
        }
    }

    // Insert new user into the database
    $stmt = $conn->prepare("INSERT INTO users (full_name, phone_number, email, city, country, password_hash, telegram_username, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    // 'ssisssss' - s for string, i for integer (if phone_number were integer, but it's varchar)
    if ($stmt) {
        $stmt->bind_param("ssssssss", $full_name, $phone_number, $email, $city, $country, $password_hash, $telegram_username, $gender);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Registration successful! You can now log in.";
            header("Location: login.php"); // Redirect to login page after successful registration
            exit();
        } else {
            $_SESSION['error_message'] = "Error during registration: " . $stmt->error;
            header("Location: signup.php"); // Redirect back to signup on error
            exit();
        }
        $stmt->close();
    } else {
        error_log("Error preparing user insertion query: " . $conn->error);
        $_SESSION['error_message'] = "Database error during registration.";
        closeDbConnection($conn);
        header("Location: signup.php");
        exit();
    }

} else {
    // If accessed directly without POST request
    header("Location: signup.php"); // Redirect to signup.php
    exit();
}
?>
