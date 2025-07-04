<?php
// ALWAYS start the session at the very beginning of your PHP file.
// This allows you to access and modify session variables.
session_start();

// Set the content type to application/json for the response.
// This tells the browser that the response is JSON, which is important for JavaScript's fetch API.
header('Content-Type: application/json');

// Check if the 'darkModeState' parameter was sent via a POST request.
// This parameter will indicate whether dark mode should be enabled or disabled.
if (isset($_POST['darkModeState'])) {
    // Sanitize and validate the input.
    // filter_var with FILTER_VALIDATE_BOOLEAN converts 'true'/'false' strings (from JavaScript)
    // into actual boolean values (true or false).
    $darkModeState = filter_var($_POST['darkModeState'], FILTER_VALIDATE_BOOLEAN);

    // Store the dark mode preference in a session variable.
    // This makes the preference persist across different pages for the current user's session.
    $_SESSION['darkMode'] = $darkModeState;

    // Send a success response back to the client as JSON.
    // This allows your JavaScript to confirm that the preference was saved.
    echo json_encode([
        'status' => 'success',
        'message' => 'Dark mode preference updated successfully.',
        'darkMode' => $darkModeState // Echo back the confirmed state
    ]);
} else {
    // If 'darkModeState' parameter is missing, it means the request was invalid.
    // Send an error response back to the client.
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request: darkModeState parameter is missing.'
    ]);
}

// It's good practice to exit after sending a JSON response to prevent
// any additional output (like HTML or whitespace) that might corrupt the JSON.
exit();
?>
