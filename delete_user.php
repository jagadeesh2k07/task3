<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

// Only admins can delete users
if ($_SESSION['role_id'] != 2) {
    echo json_encode(['status' => 'error', 'message' => 'Permission denied. Admins only.']);
    exit();
}

$id = intval($_POST['id']);

// Prevent self-delete
if ($id === intval($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own account.']);
    exit();
}

$stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete.']);
}
?>