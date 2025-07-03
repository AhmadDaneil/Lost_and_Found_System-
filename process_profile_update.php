<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

// Enable error reporting for debugging (keep this for general errors, but no verbose echoes)
error_reporting(E_ALL);
ini_set('display_errors', 1);


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to update your profile.";
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $conn = getDbConnection();

    $update_successful = true; // Flag to track overall success
    $error_messages = []; // Array to collect all error messages

    // --- PROFILE INFORMATION UPDATE ---
    $full_name = $_POST['full_name'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $city = $_POST['city'] ?? '';
    $country = $_POST['country'] ?? '';
    $telegram_username = $_POST['telegram_username'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $remove_photo_flag = $_POST['remove_photo_flag'] ?? '0';

    // Basic validation for profile
    if (empty($full_name)) {
        $error_messages[] = "Full Name is required.";
        $update_successful = false;
    }

    $profile_image_path = null; // Initialize to null

    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = UPLOAD_DIR . 'profile_images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_tmp_name = $_FILES['profile_image']['tmp_name'];
        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('profile_') . '.' . $file_extension;
        $destination = $upload_dir . $file_name;

        if (move_uploaded_file($file_tmp_name, $destination)) {
            $profile_image_path = 'uploads/profile_images/' . $file_name;

            // Delete old profile image if it exists
            $stmt_old_image = $conn->prepare("SELECT profile_image_path FROM users WHERE id = ?");
            if ($stmt_old_image) {
                $stmt_old_image->bind_param("i", $user_id);
                $stmt_old_image->execute();
                $result_old_image = $stmt_old_image->get_result();
                if ($row = $result_old_image->fetch_assoc()) {
                    if (!empty($row['profile_image_path']) && file_exists($row['profile_image_path'])) {
                        unlink($row['profile_image_path']);
                    }
                }
                $stmt_old_image->close();
            } else {
                error_log("Error preparing old image path query: " . $conn->error);
            }
        } else {
            $error_messages[] = "Failed to upload profile image.";
            $update_successful = false;
        }
    } elseif ($remove_photo_flag === '1') {
        $profile_image_path = null;

        // Delete old profile image if it exists
        $stmt_old_image = $conn->prepare("SELECT profile_image_path FROM users WHERE id = ?");
        if ($stmt_old_image) {
            $stmt_old_image->bind_param("i", $user_id);
            $stmt_old_image->execute();
            $result_old_image = $stmt_old_image->get_result();
            if ($row = $result_old_image->fetch_assoc()) {
                if (!empty($row['profile_image_path']) && file_exists($row['profile_image_path'])) {
                    unlink($row['profile_image_path']);
                }
            }
            $stmt_old_image->close();
        } else {
            error_log("Error preparing old image path query for removal: " . $conn->error);
        }
    }

    // Prepare the profile update statement
    if ($update_successful) {
        $sql_profile = "UPDATE users SET full_name = ?, phone_number = ?, city = ?, country = ?, telegram_username = ?, gender = ?";
        $params_profile = [$full_name, $phone_number, $city, $country, $telegram_username, $gender];
        $types_profile = "ssssss";

        if ((isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) || ($remove_photo_flag === '1')) {
            $sql_profile .= ", profile_image_path = ?";
            $params_profile[] = $profile_image_path;
            $types_profile .= "s";
        }

        $sql_profile .= " WHERE id = ?";
        $params_profile[] = $user_id;
        $types_profile .= "i";

        $stmt_profile = $conn->prepare($sql_profile);
        if ($stmt_profile) {
            array_unshift($params_profile, $types_profile);
            call_user_func_array([$stmt_profile, 'bind_param'], $params_profile);

            if (!$stmt_profile->execute()) {
                $error_messages[] = "Error updating profile information: " . $stmt_profile->error;
                $update_successful = false;
            } else {
                $_SESSION['full_name'] = $full_name;
            }
            $stmt_profile->close();
        } else {
            error_log("Error preparing profile update query: " . $conn->error);
            $error_messages[] = "Database error during profile update preparation.";
            $update_successful = false;
        }
    }


    // --- PASSWORD CHANGE LOGIC ---
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    // Only attempt password change if new password fields are provided
    if (!empty($new_password) || !empty($confirm_new_password) || !empty($current_password)) {
        // 1. Verify current password
        $stmt_verify_pass = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
        if ($stmt_verify_pass) {
            $stmt_verify_pass->bind_param("i", $user_id);
            $stmt_verify_pass->execute();
            $result_verify_pass = $stmt_verify_pass->get_result();

            if ($result_verify_pass->num_rows === 1) {
                $user = $result_verify_pass->fetch_assoc();
                $stored_password_hash = $user['password_hash'];

                if (!password_verify($current_password, $stored_password_hash)) {
                    $error_messages[] = "Current password is incorrect.";
                    $update_successful = false;
                }
            } else {
                $error_messages[] = "User not found for password verification.";
                $update_successful = false;
            }
            $stmt_verify_pass->close();
        } else {
            error_log("Error preparing current password verification query: " . $conn->error);
            $error_messages[] = "Database error during password verification preparation.";
            $update_successful = false;
        }

        // 2. Validate new password (only if current password was correct or not checked yet)
        if ($update_successful) {
            if (empty($new_password) || empty($confirm_new_password)) {
                $error_messages[] = "New password and confirmation are required if changing password.";
                $update_successful = false;
            } elseif ($new_password !== $confirm_new_password) {
                $error_messages[] = "New password and confirmation do not match.";
                $update_successful = false;
            } elseif (strlen($new_password) < 8) {
                $error_messages[] = "New password must be at least 8 characters long.";
                $update_successful = false;
            }
        }

        // 3. Hash and update new password (only if all validations passed)
        if ($update_successful) {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt_update_pass = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            if ($stmt_update_pass) {
                $stmt_update_pass->bind_param("si", $new_password_hash, $user_id);

                if (!$stmt_update_pass->execute()) {
                    $error_messages[] = "Error changing password: " . $stmt_update_pass->error;
                    $update_successful = false;
                }
                $stmt_update_pass->close();
            } else {
                error_log("Error preparing password update query: " . $conn->error);
                $error_messages[] = "Database error during password update preparation.";
                $update_successful = false;
            }
        }
    }


    // --- FINAL REDIRECTION AND MESSAGE HANDLING ---
    closeDbConnection($conn);

    if ($update_successful && empty($error_messages)) {
        $_SESSION['success_message'] = "Profile updated successfully!";
    } else {
        $_SESSION['error_message'] = implode("<br>", $error_messages);
        if (empty($error_messages)) {
            $_SESSION['error_message'] = "An unknown error occurred during profile update.";
        }
    }

    // Redirect to profile.php after processing
    header("Location: profile.php");
    exit();

} else {
    // If accessed directly without POST request
    $_SESSION['error_message'] = "Invalid request method.";
    header("Location: profile.php");
    exit();
}
?>
