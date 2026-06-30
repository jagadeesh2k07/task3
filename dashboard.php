<?php
session_start();
include 'db.php';

// If not logged in, redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// Fetch user details
$id   = $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);

// Fetch all users for CRUD table
$all = mysqli_query($conn, "SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id ORDER BY u.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard</title>
  <link rel="stylesheet" href="style.css"/>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
  <style>
    body { display: block; padding: 0; background: var(--bg-primary); }

    .dash-wrap {
      max-width: 1100px;
      margin: 0 auto;
      padding: 30px 20px;
      position: relative;
      z-index: 1;
    }

    /* NAVBAR */
    .dash-nav {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: var(--bg-secondary);
      border: 1px solid var(--border-light);
      border-radius: 14px;
      padding: 14px 24px;
      margin-bottom: 28px;
    }
    .dash-nav-logo {
      font-size: 16px;
      font-weight: 700;
      color: var(--text-main);
    }
    .dash-nav-logo span { color: var(--accent-color); }
    .dash-nav-right { display: flex; align-items: center; gap: 14px; }
    .dash-greeting {
      font-size: 13px;
      color: var(--text-muted);
    }
    .dash-greeting strong { color: var(--text-main); }
    .btn-logout {
      padding: 8px 16px;
      background: var(--accent-dim);
      border: 1px solid var(--accent-dim-border);
      border-radius: 8px;
      color: var(--accent-color);
      font-family: var(--font);
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.2s ease, border-color 0.2s ease;
    }
    .btn-logout:hover {
      background: rgba(251,146,60,0.14);
      border-color: var(--accent-color);
    }

    /* STAT CARDS */
    .stat-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
      margin-bottom: 28px;
    }
    .stat-card {
      background: var(--bg-secondary);
      border: 1px solid var(--border-light);
      border-radius: 14px;
      padding: 22px 24px;
      display: flex;
      align-items: center;
      gap: 16px;
      animation: cardEntrance 0.5s ease both;
    }
    .stat-icon {
      width: 44px; height: 44px;
      border-radius: 10px;
      background: var(--accent-dim);
      border: 1px solid var(--accent-dim-border);
      display: flex; align-items: center; justify-content: center;
      font-size: 18px;
      color: var(--accent-color);
      flex-shrink: 0;
    }
    .stat-info h3 {
      font-size: 22px;
      font-weight: 700;
      color: var(--text-main);
    }
    .stat-info p {
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 2px;
    }

    /* TABLE SECTION */
    .section-card {
      background: var(--bg-secondary);
      border: 1px solid var(--border-light);
      border-radius: 14px;
      padding: 24px;
      margin-bottom: 24px;
    }
    .section-title {
      font-size: 15px;
      font-weight: 700;
      color: var(--text-main);
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .section-title i { color: var(--accent-color); }

    .users-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
    }
    .users-table th {
      text-align: left;
      padding: 10px 14px;
      color: var(--text-muted);
      font-weight: 600;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-bottom: 1px solid var(--border-light);
    }
    .users-table td {
      padding: 12px 14px;
      color: var(--text-main);
      border-bottom: 1px solid rgba(251,146,60,0.04);
      vertical-align: middle;
    }
    .users-table tr:last-child td { border-bottom: none; }
    .users-table tr:hover td { background: var(--accent-dim); }

    .role-badge {
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 600;
    }
    .role-admin {
      background: rgba(251,146,60,0.12);
      color: var(--accent-color);
      border: 1px solid var(--accent-dim-border);
    }
    .role-user {
      background: rgba(74,222,128,0.08);
      color: #4ade80;
      border: 1px solid rgba(74,222,128,0.2);
    }

    .action-btns { display: flex; gap: 8px; }
    .btn-edit, .btn-delete {
      padding: 5px 12px;
      border-radius: 6px;
      font-size: 11px;
      font-weight: 600;
      cursor: pointer;
      border: 1px solid;
      font-family: var(--font);
      transition: all 0.2s ease;
      text-decoration: none;
    }
    .btn-edit {
      background: var(--accent-dim);
      color: var(--accent-color);
      border-color: var(--accent-dim-border);
    }
    .btn-edit:hover {
      background: rgba(251,146,60,0.14);
      border-color: var(--accent-color);
    }
    .btn-delete {
      background: rgba(248,113,113,0.08);
      color: #f87171;
      border-color: rgba(248,113,113,0.25);
    }
    .btn-delete:hover {
      background: rgba(248,113,113,0.15);
      border-color: #f87171;
    }

    /* PROFILE SECTION */
    .profile-grid {
      display: grid;
      grid-template-columns: auto 1fr;
      gap: 20px;
      align-items: center;
    }
    .profile-avatar {
      width: 70px; height: 70px;
      border-radius: 50%;
      background: var(--accent-dim);
      border: 2px solid var(--accent-dim-border);
      display: flex; align-items: center; justify-content: center;
      font-size: 26px;
      color: var(--accent-color);
      overflow: hidden;
    }
    .profile-avatar img {
      width: 100%; height: 100%;
      object-fit: cover;
    }
    .profile-name {
      font-size: 18px;
      font-weight: 700;
      color: var(--text-main);
      margin-bottom: 4px;
    }
    .profile-email {
      font-size: 13px;
      color: var(--text-muted);
    }
    .btn-edit-profile {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-top: 12px;
      padding: 8px 16px;
      background: var(--accent-dim);
      border: 1px solid var(--accent-dim-border);
      border-radius: 8px;
      color: var(--accent-color);
      font-size: 12px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.2s ease;
    }
    .btn-edit-profile:hover {
      background: rgba(251,146,60,0.14);
      border-color: var(--accent-color);
    }

    /* DELETE CONFIRM MODAL */
    .del-overlay {
      position: fixed; inset: 0; z-index: 200;
      display: flex; align-items: center; justify-content: center;
      background: rgba(8,8,7,0.85);
      backdrop-filter: blur(8px);
    }
    .del-overlay.hidden { display: none !important; }
    .del-card {
      background: var(--bg-secondary);
      border: 1px solid rgba(248,113,113,0.25);
      border-radius: 16px;
      padding: 32px 28px;
      max-width: 360px;
      width: 100%;
      text-align: center;
      animation: modalPop 0.35s cubic-bezier(.34,1.4,.64,1) both;
    }
    @keyframes modalPop {
      from { opacity: 0; transform: scale(0.85); }
      to   { opacity: 1; transform: scale(1); }
    }
    .del-icon {
      font-size: 28px; color: #f87171;
      margin-bottom: 14px;
    }
    .del-title { font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 8px; }
    .del-sub   { font-size: 13px; color: var(--text-muted); margin-bottom: 22px; }
    .del-btns  { display: flex; gap: 10px; justify-content: center; }
    .btn-cancel {
      padding: 9px 20px;
      background: var(--accent-dim);
      border: 1px solid var(--accent-dim-border);
      border-radius: 8px;
      color: var(--text-muted);
      font-family: var(--font);
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
    }
    .btn-confirm-del {
      padding: 9px 20px;
      background: rgba(248,113,113,0.12);
      border: 1px solid rgba(248,113,113,0.3);
      border-radius: 8px;
      color: #f87171;
      font-family: var(--font);
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
    }
    .btn-confirm-del:hover { background: rgba(248,113,113,0.2); }

    @media (max-width: 768px) {
      .users-table thead { display: none; }
      .users-table td {
        display: block;
        padding: 6px 14px;
      }
      .users-table tr { border-bottom: 1px solid var(--border-light); display: block; padding: 10px 0; }
      .stat-row { grid-template-columns: 1fr 1fr; }
    }
  </style>
</head>
<body>

<canvas id="particleCanvas"></canvas>

<div class="dash-wrap">

  <!-- NAVBAR -->
  <nav class="dash-nav">
    <div class="dash-nav-logo">JAG<span>.</span></div>
    <div class="dash-nav-right">
      <span class="dash-greeting">Hello, <strong><?= htmlspecialchars($user['first_name']) ?></strong> 👋</span>
      <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </nav>

  <!-- STATS -->
  <div class="stat-row">
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-users"></i></div>
      <div class="stat-info">
        <h3><?= mysqli_num_rows($all) ?></h3>
        <p>Total Users</p>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
      <div class="stat-info">
        <?php
          $adminCount = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE role_id = 2"));
        ?>
        <h3><?= $adminCount ?></h3>
        <p>Admins</p>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-user-check"></i></div>
      <div class="stat-info">
        <?php
          $userCount = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE role_id = 1"));
        ?>
        <h3><?= $userCount ?></h3>
        <p>Regular Users</p>
      </div>
    </div>
  </div>

  <!-- PROFILE -->
  <div class="section-card">
    <div class="section-title"><i class="fas fa-id-card"></i> My Profile</div>
    <div class="profile-grid">
      <div class="profile-avatar">
        <?php if (!empty($user['profile_pic'])): ?>
          <img src="uploads/<?= htmlspecialchars($user['profile_pic']) ?>" alt="Profile"/>
        <?php else: ?>
          <i class="fas fa-user"></i>
        <?php endif; ?>
      </div>
      <div>
        <div class="profile-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
        <div class="profile-email"><?= htmlspecialchars($user['email']) ?></div>
        <a href="profile.php" class="btn-edit-profile"><i class="fas fa-pen"></i> Edit Profile</a>
      </div>
    </div>
  </div>

  <!-- USERS TABLE -->
  <div class="section-card">
    <div class="section-title"><i class="fas fa-table"></i> All Users</div>
    <table class="users-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Role</th>
          <th>Joined</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        mysqli_data_seek($all, 0);
        $i = 1;
        while ($row = mysqli_fetch_assoc($all)):
        ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
          <td><?= htmlspecialchars($row['email']) ?></td>
          <td><?= htmlspecialchars($row['phone'] ?: '—') ?></td>
          <td>
            <span class="role-badge <?= $row['role_id'] == 2 ? 'role-admin' : 'role-user' ?>">
              <?= htmlspecialchars($row['role_name']) ?>
            </span>
          </td>
          <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
          <td>
            <?php if ($_SESSION['role_id'] == 2): ?>
            <div class="action-btns">
              <a href="edit_user.php?id=<?= $row['id'] ?>" class="btn-edit"><i class="fas fa-pen"></i> Edit</a>
              <?php if ($row['id'] != $_SESSION['user_id']): ?>
              <button class="btn-delete" onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['first_name']) ?>')">
                <i class="fas fa-trash"></i> Delete
              </button>
              <?php endif; ?>
            </div>
            <?php else: ?>
            <span style="font-size:12px;color:var(--text-muted);">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

</div>

<!-- DELETE CONFIRM MODAL -->
<div class="del-overlay hidden" id="delOverlay">
  <div class="del-card">
    <div class="del-icon"><i class="fas fa-triangle-exclamation"></i></div>
    <div class="del-title">Delete User?</div>
    <div class="del-sub" id="delSub">This action cannot be undone.</div>
    <div class="del-btns">
      <button class="btn-cancel" onclick="closeDelete()">Cancel</button>
      <button class="btn-confirm-del" id="delConfirmBtn">Delete</button>
    </div>
  </div>
</div>

<script src="script.js"></script>
<script>
  let deleteId = null;

  function confirmDelete(id, name) {
    deleteId = id;
    document.getElementById('delSub').textContent = `Are you sure you want to delete "${name}"? This cannot be undone.`;
    document.getElementById('delOverlay').classList.remove('hidden');
  }

  function closeDelete() {
    deleteId = null;
    document.getElementById('delOverlay').classList.add('hidden');
  }

  document.getElementById('delConfirmBtn').addEventListener('click', function () {
    if (!deleteId) return;
    const fd = new FormData();
    fd.append('id', deleteId);
    fetch('delete_user.php', { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          location.reload();
        } else {
          alert('Failed to delete user.');
        }
      });
  });
</script>
</body>
</html>