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
        header("Location: signup.html");
        exit();
    }

    if ($password !== $confirm_password) {
        $_SESSION['error_message'] = "Passwords do not match.";
        header("Location: signup.html");
        exit();
    }

    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $conn = getDbConnection(); // Get database connection

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['error_message'] = "Email already registered. Please use a different email or log in.";
        $stmt->close();
        closeDbConnection($conn);
        header("Location: signup.html");
        exit();
    }
    $stmt->close();

    // Check if Telegram username already exists (if provided)
    if (!empty($telegram_username)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE telegram_username = ?");
        $stmt->bind_param("s", $telegram_username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $_SESSION['error_message'] = "Telegram username already taken. Please choose another.";
            $stmt->close();
            closeDbConnection($conn);
            header("Location: signup.html");
            exit();
        }
        $stmt->close();
    }

    // Insert new user into the database
    $stmt = $conn->prepare("INSERT INTO users (full_name, phone_number, email, city, country, password_hash, telegram_username, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    // 'ssisssss' - s for string, i for integer (if phone_number were integer, but it's varchar)
    $stmt->bind_param("ssssssss", $full_name, $phone_number, $email, $city, $country, $password_hash, $telegram_username, $gender);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Registration successful! You can now log in.";
        header("Location: login.html"); // Redirect to login page after successful registration
        exit();
    } else {
        $_SESSION['error_message'] = "Error during registration: " . $stmt->error;
        header("Location: signup.html");
        exit();
    }

    $stmt->close();
    closeDbConnection($conn);
} else {
    // If accessed directly without POST request
    header("Location: signup.html");
    exit();
}
?>