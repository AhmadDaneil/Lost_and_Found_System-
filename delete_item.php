<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
    $item_id = $_POST['item_id'];

    $conn = getDbConnection();
    $sql_delete = "DELETE FROM lost_items WHERE id = ? AND user_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("ii", $item_id, $_SESSION['user_id']);
    $stmt_delete->execute();
    $stmt_delete->close();

    // Check if the item was found in the found_items table
    $sql_delete_found = "DELETE FROM found_items WHERE id = ? AND user_id = ?";
    $stmt_delete_found = $conn->prepare($sql_delete_found);
    $stmt_delete_found->bind_param("ii", $item_id, $_SESSION['user_id']);
    $stmt_delete_found->execute();
    $stmt_delete_found->close();

    closeDbConnection($conn);

    header("Location: user_dashboard.php?message=Item deleted successfully");
    exit();
}
?>
