<?php
session_start();
include 'db.php';

$action = $_POST['action'] ?? '';

if ($action === 'check_email') {
    $email = trim($_POST['email']);
    $stmt  = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        $_SESSION['fp_email'] = $email;
        $_SESSION['fp_step']  = 1;
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No account found with this email.']);
    }
    exit();
}

if ($action === 'verify_password') {
    if (empty($_SESSION['fp_email']) || ($_SESSION['fp_step'] ?? 0) < 1) {
        echo json_encode(['status' => 'error', 'message' => 'Session expired. Please start again.']);
        exit();
    }

    $email   = $_SESSION['fp_email'];
    $current = $_POST['currentPassword'];

    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row    = mysqli_fetch_assoc($result);

    if ($row && password_verify($current, $row['password'])) {
        $_SESSION['fp_step'] = 2;
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Incorrect current password.']);
    }
    exit();
}

if ($action === 'reset') {
    if (empty($_SESSION['fp_email']) || ($_SESSION['fp_step'] ?? 0) < 2) {
        echo json_encode(['status' => 'error', 'message' => 'Session expired or verification incomplete. Please start again.']);
        exit();
    }

    $email  = $_SESSION['fp_email'];
    $newPw  = $_POST['newPassword'];

    if (strlen($newPw) < 8) {
        echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters.']);
        exit();
    }

    $hashed = password_hash($newPw, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "ss", $hashed, $email);

    if (mysqli_stmt_execute($stmt)) {
        unset($_SESSION['fp_email'], $_SESSION['fp_step']);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update password.']);
    }
    exit();
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
?>