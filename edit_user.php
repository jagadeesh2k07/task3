<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// Only admins can edit other users
if ($_SESSION['role_id'] != 2) {
    header("Location: dashboard.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);
    $role_id    = intval($_POST['role_id']);

    // Email uniqueness (exclude the user being edited)
    $chk = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
    mysqli_stmt_bind_param($chk, "si", $email, $id);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);

    if (mysqli_stmt_num_rows($chk) > 0) {
        $error = "That email is already registered to another account.";
    } else {
        $stmt = mysqli_prepare($conn,
            "UPDATE users SET first_name=?, last_name=?, email=?, phone=?, role_id=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssssis", $first_name, $last_name, $email, $phone, $role_id, $id);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Update failed. Try again.";
        }
    }
}

// Fetch user to edit
$stmt   = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);

if (!$user) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit User</title>
  <link rel="stylesheet" href="style.css"/>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
  <style>
    body { display: block; background: var(--bg-primary); padding: 40px 20px; }
    .edit-wrap {
      max-width: 520px;
      margin: 0 auto;
      position: relative;
      z-index: 1;
    }
    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: var(--text-muted);
      text-decoration: none;
      font-size: 13px;
      font-weight: 500;
      margin-bottom: 24px;
      transition: color 0.2s ease;
    }
    .back-link:hover { color: var(--accent-color); }
    .edit-card {
      background: var(--bg-secondary);
      border: 1px solid var(--border-light);
      border-radius: 18px;
      padding: 36px 32px;
    }
    .edit-title {
      font-size: 1.3rem;
      font-weight: 700;
      color: var(--text-main);
      margin-bottom: 6px;
    }
    .edit-sub {
      font-size: 13px;
      color: var(--text-muted);
      margin-bottom: 26px;
    }
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }
    .form-grid .full { grid-column: 1 / -1; }
    .field label {
      display: block;
      font-size: 12.5px;
      font-weight: 600;
      color: var(--text-main);
      margin-bottom: 6px;
    }
    .field input, .field select {
      width: 100%;
      padding: 11px 14px;
      background: rgba(251,146,60,0.04);
      border: 1.5px solid rgba(251,146,60,0.14);
      border-radius: 9px;
      font-family: var(--font);
      font-size: 13.5px;
      color: var(--text-main);
      outline: none;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .field input:focus, .field select:focus {
      border-color: var(--accent-color);
      box-shadow: 0 0 0 3px var(--accent-dim);
    }
    .field select option { background: var(--bg-secondary); }
    .err-msg {
      background: rgba(248,113,113,0.1);
      border: 1px solid rgba(248,113,113,0.25);
      color: #f87171;
      padding: 10px 14px;
      border-radius: 8px;
      font-size: 13px;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .btn-row {
      display: flex;
      gap: 12px;
      margin-top: 24px;
    }
    .btn-save {
      flex: 1;
      padding: 12px;
      background: var(--accent-color);
      color: var(--bg-primary);
      border: none;
      border-radius: 9px;
      font-family: var(--font);
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.2s ease, transform 0.2s ease;
    }
    .btn-save:hover { background: #ffa552; transform: translateY(-1px); }
    .btn-back-sm {
      padding: 12px 20px;
      background: var(--accent-dim);
      border: 1px solid var(--accent-dim-border);
      border-radius: 9px;
      color: var(--text-muted);
      font-family: var(--font);
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      display: flex;
      align-items: center;
    }
    @media (max-width: 480px) {
      .form-grid { grid-template-columns: 1fr; }
      .edit-card { padding: 24px 18px; }
    }
  </style>
</head>
<body>

<canvas id="particleCanvas"></canvas>

<div class="edit-wrap">
  <a href="dashboard.php" class="back-link">
    <i class="fas fa-arrow-left"></i> Back to Dashboard
  </a>

  <div class="edit-card">
    <div class="edit-title">Edit User</div>
    <div class="edit-sub">Updating details for <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong></div>

    <?php if (!empty($error)): ?>
      <div class="err-msg"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-grid">
        <div class="field">
          <label>First Name</label>
          <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required/>
        </div>
        <div class="field">
          <label>Last Name</label>
          <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required/>
        </div>
        <div class="field full">
          <label>Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required/>
        </div>
        <div class="field full">
          <label>Phone</label>
          <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"/>
        </div>
        <div class="field full">
          <label>Role</label>
          <select name="role_id">
            <option value="1" <?= $user['role_id'] == 1 ? 'selected' : '' ?>>User</option>
            <option value="2" <?= $user['role_id'] == 2 ? 'selected' : '' ?>>Admin</option>
          </select>
        </div>
      </div>
      <div class="btn-row">
        <a href="dashboard.php" class="btn-back-sm">Cancel</a>
        <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script src="script.js"></script>
</body>
</html>