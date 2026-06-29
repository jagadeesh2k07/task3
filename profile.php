<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$id      = $_SESSION['user_id'];
$success = '';
$error   = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);

    // Email uniqueness check (exclude current user)
    $chk = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
    mysqli_stmt_bind_param($chk, "si", $email, $id);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);
    if (mysqli_stmt_num_rows($chk) > 0) {
        $error = "That email is already in use by another account.";
    } else {
        // Handle avatar upload
        $pic_update = '';
        if (!empty($_FILES['profile_pic']['name'])) {
            $allowed   = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $mime      = mime_content_type($_FILES['profile_pic']['tmp_name']);
            $max_size  = 2 * 1024 * 1024; // 2 MB

            if (!in_array($mime, $allowed)) {
                $error = "Only JPG, PNG, GIF, or WEBP images are allowed.";
            } elseif ($_FILES['profile_pic']['size'] > $max_size) {
                $error = "Image must be under 2 MB.";
            } else {
                if (!is_dir('uploads')) mkdir('uploads', 0755, true);
                $ext      = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . $id . '_' . time() . '.' . strtolower($ext);
                $dest     = 'uploads/' . $filename;
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dest)) {
                    $pic_update = $filename;
                } else {
                    $error = "Failed to upload image. Check folder permissions.";
                }
            }
        }

        if (!$error) {
            // Handle optional password change
            $new_password = $_POST['new_password'] ?? '';
            $cur_password = $_POST['current_password'] ?? '';
            $conf_password = $_POST['confirm_password'] ?? '';

            if ($new_password) {
                // Verify current password first
                $pstmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
                mysqli_stmt_bind_param($pstmt, "i", $id);
                mysqli_stmt_execute($pstmt);
                $pres = mysqli_stmt_get_result($pstmt);
                $prow = mysqli_fetch_assoc($pres);

                if (!password_verify($cur_password, $prow['password'])) {
                    $error = "Current password is incorrect.";
                } elseif (strlen($new_password) < 8) {
                    $error = "New password must be at least 8 characters.";
                } elseif ($new_password !== $conf_password) {
                    $error = "New passwords do not match.";
                } else {
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $pstmt2 = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
                    mysqli_stmt_bind_param($pstmt2, "si", $hashed, $id);
                    if (!mysqli_stmt_execute($pstmt2)) {
                        $error = "Failed to update password.";
                    }
                }
            }

            if (!$error) {
                if ($pic_update) {
                    $stmt = mysqli_prepare($conn,
                        "UPDATE users SET first_name=?, last_name=?, email=?, phone=?, profile_pic=? WHERE id=?");
                    mysqli_stmt_bind_param($stmt, "sssssi", $first_name, $last_name, $email, $phone, $pic_update, $id);
                } else {
                    $stmt = mysqli_prepare($conn,
                        "UPDATE users SET first_name=?, last_name=?, email=?, phone=? WHERE id=?");
                    mysqli_stmt_bind_param($stmt, "ssssi", $first_name, $last_name, $email, $phone, $id);
                }

                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['first_name'] = $first_name;
                    $success = "Profile updated successfully!";
                } else {
                    $error = "Update failed. Please try again.";
                }
            }
        }
    }
}

// Fetch fresh user data
$stmt   = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Profile</title>
  <link rel="stylesheet" href="style.css"/>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
  <style>
    body { display: block; background: var(--bg-primary); padding: 40px 20px; }

    .profile-wrap {
      max-width: 560px;
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

    .profile-card {
      background: var(--bg-secondary);
      border: 1px solid var(--border-light);
      border-radius: 18px;
      padding: 36px 32px;
      animation: cardEntrance 0.5s ease both;
    }

    .profile-card-title {
      font-size: 1.3rem;
      font-weight: 700;
      color: var(--text-main);
      margin-bottom: 6px;
    }
    .profile-card-sub {
      font-size: 13px;
      color: var(--text-muted);
      margin-bottom: 28px;
    }

    /* Avatar picker */
    .avatar-section {
      display: flex;
      align-items: center;
      gap: 20px;
      margin-bottom: 28px;
      padding-bottom: 24px;
      border-bottom: 1px solid var(--border-light);
    }
    .avatar-preview {
      width: 80px; height: 80px;
      border-radius: 50%;
      background: var(--accent-dim);
      border: 2px solid var(--accent-dim-border);
      display: flex; align-items: center; justify-content: center;
      font-size: 28px;
      color: var(--accent-color);
      overflow: hidden;
      flex-shrink: 0;
      transition: border-color 0.2s ease;
    }
    .avatar-preview:hover { border-color: var(--accent-color); }
    .avatar-preview img {
      width: 100%; height: 100%;
      object-fit: cover;
    }
    .avatar-info h4 {
      font-size: 14px;
      font-weight: 600;
      color: var(--text-main);
      margin-bottom: 4px;
    }
    .avatar-info p {
      font-size: 12px;
      color: var(--text-muted);
      margin-bottom: 10px;
    }
    .btn-pick-avatar {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 7px 14px;
      background: var(--accent-dim);
      border: 1px solid var(--accent-dim-border);
      border-radius: 8px;
      color: var(--accent-color);
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .btn-pick-avatar:hover {
      background: rgba(251,146,60,0.14);
      border-color: var(--accent-color);
    }
    #picInput { display: none; }

    /* Form fields */
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
    .field input {
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
    .field input:focus {
      border-color: var(--accent-color);
      box-shadow: 0 0 0 3px var(--accent-dim);
    }

    /* Change password section */
    .section-divider {
      border: none;
      border-top: 1px solid var(--border-light);
      margin: 28px 0;
    }
    .section-label {
      font-size: 13px;
      font-weight: 700;
      color: var(--text-main);
      margin-bottom: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .section-label i { color: var(--accent-color); font-size: 12px; }
    .section-hint {
      font-size: 12px;
      color: var(--text-muted);
      margin-bottom: 14px;
    }

    /* Messages */
    .msg-ok {
      background: rgba(74,222,128,0.08);
      border: 1px solid rgba(74,222,128,0.25);
      color: #4ade80;
      padding: 11px 14px;
      border-radius: 9px;
      font-size: 13px;
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .msg-err {
      background: rgba(248,113,113,0.08);
      border: 1px solid rgba(248,113,113,0.25);
      color: #f87171;
      padding: 11px 14px;
      border-radius: 9px;
      font-size: 13px;
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* Buttons */
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
    .btn-cancel-sm {
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

    /* Password change inputs (hidden by default) */
    .pw-change-box { display: none; }
    .pw-change-box.open { display: block; }
    .toggle-pw-link {
      font-size: 12px;
      color: var(--accent-color);
      cursor: pointer;
      text-decoration: underline;
      background: none;
      border: none;
      font-family: var(--font);
      padding: 0;
    }

    @media (max-width: 480px) {
      .form-grid { grid-template-columns: 1fr; }
      .profile-card { padding: 24px 18px; }
      .avatar-section { flex-direction: column; align-items: flex-start; }
    }
  </style>
</head>
<body>

<canvas id="particleCanvas"></canvas>

<div class="profile-wrap">
  <a href="dashboard.php" class="back-link">
    <i class="fas fa-arrow-left"></i> Back to Dashboard
  </a>

  <div class="profile-card">
    <div class="profile-card-title">Edit Profile</div>
    <div class="profile-card-sub">Update your personal information and avatar</div>

    <?php if ($success): ?>
      <div class="msg-ok"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="msg-err"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="profileForm">

      <!-- Avatar section -->
      <div class="avatar-section">
        <div class="avatar-preview" id="avatarPreview">
          <?php if (!empty($user['profile_pic'])): ?>
            <img src="uploads/<?= htmlspecialchars($user['profile_pic']) ?>" alt="Avatar" id="avatarImg"/>
          <?php else: ?>
            <i class="fas fa-user" id="avatarIcon"></i>
          <?php endif; ?>
        </div>
        <div class="avatar-info">
          <h4>Profile Photo</h4>
          <p>JPG, PNG, GIF or WEBP · Max 2 MB</p>
          <label class="btn-pick-avatar" for="picInput">
            <i class="fas fa-camera"></i> Change Photo
          </label>
          <input type="file" name="profile_pic" id="picInput" accept="image/*"/>
        </div>
      </div>

      <!-- Info fields -->
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
          <label>Phone <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
          <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"/>
        </div>
      </div>

      <!-- Password change -->
      <hr class="section-divider"/>
      <div class="section-label"><i class="fas fa-lock"></i> Password</div>
      <p class="section-hint">
        Leave blank to keep your current password. &nbsp;
        <button type="button" class="toggle-pw-link" id="togglePwBtn">Change password</button>
      </p>
      <div class="pw-change-box" id="pwChangeBox">
        <div class="form-grid">
          <div class="field full">
            <label>Current Password</label>
            <input type="password" name="current_password" id="currentPw" placeholder="Enter current password"/>
          </div>
          <div class="field">
            <label>New Password</label>
            <input type="password" name="new_password" id="newPw" placeholder="Min 8 characters"/>
          </div>
          <div class="field">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" id="confPw" placeholder="Re-enter new password"/>
          </div>
        </div>
        <p id="pwMsg" style="font-size:12px;margin-top:6px;"></p>
      </div>

      <div class="btn-row">
        <a href="dashboard.php" class="btn-cancel-sm">Cancel</a>
        <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script src="script.js"></script>
<script>
  // Live avatar preview
  document.getElementById('picInput').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
      const preview = document.getElementById('avatarPreview');
      preview.innerHTML = `<img src="${e.target.result}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;"/>`;
    };
    reader.readAsDataURL(file);
  });

  // Toggle password change section
  document.getElementById('togglePwBtn').addEventListener('click', function () {
    const box = document.getElementById('pwChangeBox');
    const open = box.classList.toggle('open');
    this.textContent = open ? 'Cancel password change' : 'Change password';
    if (!open) {
      document.getElementById('currentPw').value = '';
      document.getElementById('newPw').value     = '';
      document.getElementById('confPw').value    = '';
      document.getElementById('pwMsg').textContent = '';
    }
  });

  // Client-side password match hint
  document.getElementById('confPw').addEventListener('input', function () {
    const msg = document.getElementById('pwMsg');
    if (!this.value) { msg.textContent = ''; return; }
    if (this.value === document.getElementById('newPw').value) {
      msg.style.color = '#4ade80';
      msg.textContent = '✓ Passwords match';
    } else {
      msg.style.color = '#f87171';
      msg.textContent = '✗ Passwords do not match';
    }
  });
</script>
</body>
</html>