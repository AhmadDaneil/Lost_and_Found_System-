<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to report an item.";
    header("Location: login.php"); // Updated from login.html to login.php
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $item_type = $_POST['item_type'] ?? ''; // 'lost' or 'found'
    $item_name = $_POST['item_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $location = $_POST['location'] ?? '';
    $date = ''; // Will be either date_lost or date_found

    // Determine the date field based on item type
    if ($item_type === 'lost') {
        $date = $_POST['date_lost'] ?? '';
    } elseif ($item_type === 'found') {
        $date = $_POST['date_found'] ?? '';
    }

    // Basic validation
    if (empty($item_name) || empty($description) || empty($category) || empty($location) || empty($date)) {
        $_SESSION['error_message'] = "Please fill in all required fields.";
        if ($item_type === 'lost') {
            header("Location: report_lost_form.php"); // Updated from report_lost_form.html to report_lost_form.php
        } else {
            header("Location: report_found_form.php"); // Updated from report_found_form.html to report_found_form.php
        }
        exit();
    }

    $image_path = null;
    // Handle image upload
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == UPLOAD_ERR_OK) {
        // Corrected: UPLOAD_DIR is already defined in config.php
        $target_dir = UPLOAD_DIR;
        
        // Create uploads directory if it doesn't exist
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true); // Create recursively and set permissions
        }

        $file_extension = pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('item_') . '.' . $file_extension;
        $target_file = $target_dir . $file_name;

        // Move the uploaded file
        if (move_uploaded_file($_FILES['item_image']['tmp_name'], $target_file)) {
            $image_path = 'uploads/' . $file_name; // Store relative path in DB
        } else {
            $_SESSION['error_message'] = "Error uploading image.";
            if ($item_type === 'lost') {
                header("Location: report_lost_form.php"); // Updated from report_lost_form.html to report_lost_form.php
            } else {
                header("Location: report_found_form.php"); // Updated from report_found_form.html to report_found_form.php
            }
            exit();
        }
    }

    $conn = getDbConnection();

    if ($item_type === 'lost') {
        $stmt = $conn->prepare("INSERT INTO lost_items (user_id, item_name, description, date_lost, lost_location, category, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $user_id, $item_name, $description, $date, $location, $category, $image_path);
    } elseif ($item_type === 'found') {
        $stmt = $conn->prepare("INSERT INTO found_items (user_id, item_name, description, date_found, found_location, category, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $user_id, $item_name, $description, $date, $location, $category, $image_path);
    } else {
        $_SESSION['error_message'] = "Invalid item type.";
        header("Location: homepage.php"); // Updated from homepage.html to homepage.php
        exit();
    }

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Item reported successfully!";
        header("Location: homepage.php"); // Updated from homepage.html to homepage.php
        exit();
    } else {
        $_SESSION['error_message'] = "Error reporting item: " . $stmt->error;
        // Redirect back to the form if there was a DB error
        if ($item_type === 'lost') {
            header("Location: report_lost_form.php"); // Updated from report_lost_form.html to report_lost_form.php
        } else {
            header("Location: report_found_form.php"); // Updated from report_found_form.html to report_found_form.php
        }
        exit();
    }

    $stmt->close();
    closeDbConnection($conn);
} else {
    // If accessed directly without POST request
    header("Location: homepage.php"); // Updated from homepage.html to homepage.php
    exit();
}
?>
