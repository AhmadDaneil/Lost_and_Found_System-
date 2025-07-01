<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to update your profile.";
    header("Location: login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];

    // Get form data
    $full_name = $_POST['full_name'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $city = $_POST['city'] ?? '';
    $country = $_POST['country'] ?? '';
    $telegram_username = $_POST['telegram_username'] ?? '';
    $gender = $_POST['gender'] ?? '';

    // Basic validation
    if (empty($full_name)) {
        $_SESSION['error_message'] = "Full Name is required.";
        header("Location: profile.php");
        exit();
    }

    $conn = getDbConnection();

    $profile_image_path = null;

    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = UPLOAD_DIR . 'profile_images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); // Create directory if it doesn't exist
        }

        $file_tmp_name = $_FILES['profile_image']['tmp_name'];
        $file_name = uniqid() . '_' . basename($_FILES['profile_image']['name']); // Unique filename
        $destination = $upload_dir . $file_name;

        if (move_uploaded_file($file_tmp_name, $destination)) {
            $profile_image_path = 'uploads/profile_images/' . $file_name;

            // Optional: Delete old profile image if it exists
            $stmt_old_image = $conn->prepare("SELECT profile_image_path FROM users WHERE id = ?");
            $stmt_old_image->bind_param("i", $user_id);
            $stmt_old_image->execute();
            $result_old_image = $stmt_old_image->get_result();
            if ($row = $result_old_image->fetch_assoc()) {
                if (!empty($row['profile_image_path']) && file_exists($row['profile_image_path'])) {
                    unlink($row['profile_image_path']); // Delete old file
                }
            }
            $stmt_old_image->close();

        } else {
            $_SESSION['error_message'] = "Failed to upload profile image.";
            header("Location: profile.php");
            exit();
        }
    } elseif (isset($_POST['remove_photo']) && $_POST['remove_photo'] === '1') {
        // User explicitly requested to remove photo
        $profile_image_path = null; // Set to null to clear in DB

        // Delete old profile image if it exists
        $stmt_old_image = $conn->prepare("SELECT profile_image_path FROM users WHERE id = ?");
        $stmt_old_image->bind_param("i", $user_id);
        $stmt_old_image->execute();
        $result_old_image = $stmt_old_image->get_result();
        if ($row = $result_old_image->fetch_assoc()) {
            if (!empty($row['profile_image_path']) && file_exists($row['profile_image_path'])) {
                unlink($row['profile_image_path']); // Delete old file
            }
        }
        $stmt_old_image->close();
    }


    // Prepare the update statement
    $sql = "UPDATE users SET full_name = ?, phone_number = ?, city = ?, country = ?, telegram_username = ?, gender = ?";
    $params = [$full_name, $phone_number, $city, $country, $telegram_username, $gender];
    $types = "ssssss";

    if ($profile_image_path !== null) { // Only update image path if a new one was uploaded or removal was requested
        $sql .= ", profile_image_path = ?";
        $params[] = $profile_image_path;
        $types .= "s";
    }

    $sql .= " WHERE id = ?";
    $params[] = $user_id;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Profile updated successfully!";
            // Update session full_name in case it changed
            $_SESSION['full_name'] = $full_name;
        } else {
            $_SESSION['error_message'] = "Error updating profile: " . $stmt->error;
        }
        $stmt->close();
    } else {
        error_log("Error preparing profile update query: " . $conn->error);
        $_SESSION['error_message'] = "Database error during profile update.";
    }

    closeDbConnection($conn);
    header("Location: profile.php");
    exit();

} else {
    // If accessed directly without POST request
    header("Location: profile.php");
    exit();
}
?>
