<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to update an item.";
    header("Location: login.php"); // Updated from login.html to login.php
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $item_id = $_POST['item_id'] ?? null; // This will be present only for updates
    $item_type = $_POST['item_type'] ?? ''; // 'lost' or 'found'
    $item_name = $_POST['item_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $location = $_POST['location'] ?? '';
    $date = ''; // Will be either date_lost or date_found
    $remove_image = isset($_POST['remove_image']) ? 1 : 0; // Check if remove image checkbox is ticked

    // Determine the date field based on item type
    if ($item_type === 'lost') {
        $date = $_POST['date_lost'] ?? '';
    } elseif ($item_type === 'found') {
        $date = $_POST['date_found'] ?? '';
    }

    // Basic validation
    if (empty($item_id) || empty($item_name) || empty($description) || empty($category) || empty($location) || empty($date)) {
        $_SESSION['error_message'] = "Please fill in all required fields to update the item.";
        header("Location: report_" . $item_type . "_form.php?id=" . htmlspecialchars($item_id)); // Redirect to correct form
        exit();
    }

    $conn = getDbConnection();

    $table = ($item_type === 'lost') ? 'lost_items' : 'found_items';
    $date_field = ($item_type === 'lost') ? 'date_lost' : 'date_found';
    $location_field = ($item_type === 'lost') ? 'lost_location' : 'found_location';

    $image_path = null;
    $current_image_path = null; // To store existing image path from DB

    // Get the current image path from the database
    $stmt_get_image = $conn->prepare("SELECT image_path FROM $table WHERE id = ? AND user_id = ?");
    if ($stmt_get_image) {
        $stmt_get_image->bind_param("ii", $item_id, $user_id);
        $stmt_get_image->execute();
        $result_get_image = $stmt_get_image->get_result();
        if ($row = $result_get_image->fetch_assoc()) {
            $current_image_path = $row['image_path'];
        }
        $stmt_get_image->close();
    } else {
        error_log("Error preparing get image query: " . $conn->error);
        $_SESSION['error_message'] = "Database error during image path retrieval.";
        closeDbConnection($conn);
        header("Location: report_" . $item_type . "_form.php?id=" . htmlspecialchars($item_id));
        exit();
    }


    // Handle image upload or removal
    if ($remove_image == 1) {
        // User requested to remove the image
        $image_path = null; // Set image path to null in DB
        // Delete the physical file if it exists
        if ($current_image_path && file_exists($current_image_path)) {
            unlink($current_image_path);
        }
    } elseif (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
        // New image uploaded
        $upload_dir = UPLOAD_DIR; // Defined in config.php
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); // Create directory if it doesn't exist
        }

        $file_tmp_name = $_FILES['item_image']['tmp_name'];
        $file_extension = pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('item_') . '.' . $file_extension;
        $destination = $upload_dir . $file_name;

        if (move_uploaded_file($file_tmp_name, $destination)) {
            $image_path = 'uploads/' . $file_name; // Store relative path
            // Delete old image if a new one is uploaded and an old one existed
            if ($current_image_path && file_exists($current_image_path)) {
                unlink($current_image_path);
            }
        } else {
            $_SESSION['error_message'] = "Failed to upload new image.";
            closeDbConnection($conn);
            header("Location: report_" . $item_type . "_form.php?id=" . htmlspecialchars($item_id));
            exit();
        }
    } else {
        // No new image uploaded and not requested to remove, retain the current one
        $image_path = $current_image_path;
    }

    // Prepare the update statement
    $sql = "UPDATE $table SET item_name = ?, description = ?, $date_field = ?, $location_field = ?, category = ?, image_path = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ssssssii", $item_name, $description, $date, $location, $category, $image_path, $item_id, $user_id);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Item updated successfully!";
            header("Location: " . $item_type . "_item_view.php?id=" . htmlspecialchars($item_id)); // Redirect to view page
            exit();
        } else {
            $_SESSION['error_message'] = "Error updating item: " . $stmt->error;
            header("Location: report_" . $item_type . "_form.php?id=" . htmlspecialchars($item_id));
            exit();
        }
        $stmt->close();
    } else {
        error_log("Error preparing update query: " . $conn->error);
        $_SESSION['error_message'] = "Database error during item update.";
        header("Location: report_" . $item_type . "_form.php?id=" . htmlspecialchars($item_id));
        exit();
    }

    closeDbConnection($conn);
} else {
    // If accessed directly without POST request
    header("Location: homepage.php");
    exit();
}
?>
