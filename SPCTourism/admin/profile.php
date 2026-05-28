<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

$current_page = 'profile';
$uid = (int)$_SESSION['user_id'];
$photo_msg = $photo_err = $msg = $err = $pw_msg = $pw_err = '';

$user = null;
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE user_id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $uid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res) {
        $user = mysqli_fetch_assoc($res);
    }
    mysqli_stmt_close($stmt);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_photo') {
        $file = $_FILES['profile_photo'] ?? null;

        if (!empty($_POST['remove_photo']) && !empty($user['profile_photo'])) {
            $old = __DIR__ . '/../' . $user['profile_photo'];
            if (file_exists($old)) @unlink($old);
            $stmt = mysqli_prepare($conn, "UPDATE users SET profile_photo = NULL WHERE user_id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $uid);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            $_SESSION['profile_photo'] = null;
            $user['profile_photo'] = null;
            $photo_msg = 'Profile photo removed.';

        } elseif (!empty($file['name']) && $file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $dest_dir = __DIR__ . '/../images/profile/';
                if (!is_dir($dest_dir)) mkdir($dest_dir, 0755, true);
                $safe_name = 'user_' . $uid . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
                if (move_uploaded_file($file['tmp_name'], $dest_dir . $safe_name)) {
                    if (!empty($user['profile_photo'])) {
                        $old = __DIR__ . '/../' . $user['profile_photo'];
                        if (file_exists($old)) @unlink($old);
                    }
                    $new_path = 'images/profile/' . $safe_name;
                    $stmt = mysqli_prepare($conn, "UPDATE users SET profile_photo = ? WHERE user_id = ?");
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, 'si', $new_path, $uid);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }
                    $_SESSION['profile_photo'] = $new_path;
                    $user['profile_photo'] = $new_path;
                    $photo_msg = 'Profile photo updated.';
                } else {
                    $photo_err = 'Failed to save photo. Check folder permissions.';
                }
            } else {
                $photo_err = 'Only JPG, PNG, or WEBP files are accepted.';
            }
        }

    } elseif ($action === 'update_profile') {
        $name     = trim($_POST['name']     ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email']    ?? '');

        if (!$username || !$email) {
            $err = 'Username and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err = 'Please enter a valid email address.';
        } else {
            $dup_u_id = null;
            $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username = ? AND user_id != ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'si', $username, $uid);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $dup_u_id);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);
            }

            $dup_e_id = null;
            $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'si', $email, $uid);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $dup_e_id);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);
            }

            if ($dup_u_id !== null) {
                $err = 'That username is already taken.';
            } elseif ($dup_e_id !== null) {
                $err = 'That email address is already in use.';
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, username = ?, email = ? WHERE user_id = ?");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'sssi', $name, $username, $email, $uid);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
                $_SESSION['username'] = $username;
                $user['name']     = $name;
                $user['username'] = $username;
                $user['email']    = $email;
                $msg = 'Profile updated successfully.';
            }
        }

    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new_pw  = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$current || !$new_pw || !$confirm) {
            $pw_err = 'All three password fields are required.';
        } elseif (!password_verify($current, $user['password'])) {
            $pw_err = 'Current password is incorrect.';
        } elseif (strlen($new_pw) < 8) {
            $pw_err = 'New password must be at least 8 characters.';
        } elseif ($new_pw !== $confirm) {
            $pw_err = 'New passwords do not match.';
        } else {
            $hashed = password_hash($new_pw, PASSWORD_BCRYPT);
            $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE user_id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'si', $hashed, $uid);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            $pw_msg = 'Password changed successfully.';
        }
    }
}

$initials  = strtoupper(substr($user['username'] ?? 'A', 0, 1));
$has_photo = !empty($user['profile_photo']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profile Settings — Admin</title>
  <link rel="icon" type="image/png" href="../images/logo/logo.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="admin.css" />
  <style>
    .profile-layout {
      display: grid;
      grid-template-columns: 260px 1fr;
      gap: 24px;
      align-items: start;
    }
    @media (max-width: 860px) {
      .profile-layout { grid-template-columns: 1fr; }
    }
    .avatar-lg {
      width: 96px; height: 96px;
      border-radius: 50%;
      margin: 0 auto 16px;
      overflow: hidden;
      background: var(--forest);
      border: 3px solid var(--border-c);
      display: flex; align-items: center; justify-content: center;
      font-family: 'Cormorant Garamond', serif;
      font-size: 2.4rem; font-weight: 700;
      color: var(--cream);
      flex-shrink: 0;
    }
    .avatar-lg img { width: 100%; height: 100%; object-fit: cover; }
    .upload-label {
      display: inline-flex; align-items: center; gap: 7px;
      cursor: pointer;
      font-size: 0.72rem; font-weight: 600;
      letter-spacing: 0.14em; text-transform: uppercase;
      color: var(--moss);
      border: 1.5px solid rgba(58,138,60,0.35);
      padding: 7px 16px; border-radius: 2px;
      transition: border-color 0.2s, background 0.2s;
    }
    .upload-label:hover { border-color: var(--moss); background: rgba(58,138,60,0.05); }
  </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="admin-main">

  <div class="topbar">
    <div class="topbar-left">
      <span class="topbar-title">Profile Settings</span>
      <span class="topbar-breadcrumb"><a href="dashboard.php">Dashboard</a> / Profile</span>
    </div>
  </div>

  <div class="admin-content">
    <div class="profile-layout">

      <!-- Avatar card -->
      <div class="admin-card">
        <div class="admin-card-header">
          <span class="admin-card-title">Profile Photo</span>
        </div>
        <div class="admin-card-body" style="text-align:center;padding:28px 24px;">

          <?php if ($photo_msg): ?><div class="alert alert-success"><?= htmlspecialchars($photo_msg) ?></div><?php endif; ?>
          <?php if ($photo_err): ?><div class="alert alert-error"><?= htmlspecialchars($photo_err) ?></div><?php endif; ?>

          <div class="avatar-lg">
            <?php if ($has_photo): ?>
              <img src="../<?= htmlspecialchars($user['profile_photo']) ?>" alt="" />
            <?php else: ?>
              <?= $initials ?>
            <?php endif; ?>
          </div>

          <p style="font-size:0.88rem;font-weight:600;color:var(--t1);margin-bottom:3px;">
            <?= htmlspecialchars($user['name'] ?: $user['username']) ?>
          </p>
          <p style="font-size:0.65rem;letter-spacing:0.18em;text-transform:uppercase;color:var(--t3);margin-bottom:20px;">
            <?= htmlspecialchars($user['role']) ?>
          </p>

          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_photo" />
            <label class="upload-label">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              Upload Photo
              <input type="file" name="profile_photo" accept="image/*" style="display:none;" onchange="this.form.submit()" />
            </label>
            <?php if ($has_photo): ?>
              <div style="margin-top:12px;">
                <button type="submit" name="remove_photo" value="1" class="btn btn-danger btn-sm">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6m4-6v6"/><path d="M9 6V4h6v2"/></svg>
                  Remove Photo
                </button>
              </div>
            <?php endif; ?>
          </form>

          <div style="margin-top:20px;padding-top:20px;border-top:1px solid var(--border-c);text-align:left;">
            <div style="font-size:0.65rem;letter-spacing:0.14em;text-transform:uppercase;color:var(--t3);margin-bottom:8px;">Member since</div>
            <div style="font-size:0.84rem;color:var(--t2);">
              <?= date('F j, Y', strtotime($user['created_at'])) ?>
            </div>
          </div>

        </div>
      </div>

      <!-- Right column -->
      <div>

        <!-- Account info -->
        <div class="admin-card" style="margin-bottom:24px;">
          <div class="admin-card-header">
            <span class="admin-card-title">Account Information</span>
          </div>
          <div class="admin-card-body">
            <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
            <?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
            <form method="post">
              <input type="hidden" name="action" value="update_profile" />
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label">Full Name</label>
                  <input class="form-input" type="text" name="name"
                         value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                         placeholder="Your full name" />
                </div>
                <div class="form-group">
                  <label class="form-label">Username <span style="color:#c0392b">*</span></label>
                  <input class="form-input" type="text" name="username"
                         value="<?= htmlspecialchars($user['username']) ?>" required />
                </div>
                <div class="form-group">
                  <label class="form-label">Email Address <span style="color:#c0392b">*</span></label>
                  <input class="form-input" type="email" name="email"
                         value="<?= htmlspecialchars($user['email'] ?? '') ?>" required />
                </div>
                <div class="form-group">
                  <label class="form-label">Role</label>
                  <input class="form-input" type="text"
                         value="<?= htmlspecialchars(ucfirst($user['role'])) ?>"
                         disabled style="opacity:0.55;cursor:not-allowed;" />
                </div>
              </div>
              <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                  Save Changes
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Change password -->
        <div class="admin-card">
          <div class="admin-card-header">
            <span class="admin-card-title">Change Password</span>
          </div>
          <div class="admin-card-body">
            <?php if ($pw_msg): ?><div class="alert alert-success"><?= htmlspecialchars($pw_msg) ?></div><?php endif; ?>
            <?php if ($pw_err): ?><div class="alert alert-error"><?= htmlspecialchars($pw_err) ?></div><?php endif; ?>
            <form method="post" id="pw-form">
              <input type="hidden" name="action" value="change_password" />
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label">Current Password</label>
                  <input class="form-input" type="password" name="current_password" required autocomplete="current-password" />
                </div>
                <div class="form-group">
                  <label class="form-label">New Password <span style="color:var(--t3);font-size:0.62rem;letter-spacing:0.06em;">(min. 8 characters)</span></label>
                  <input class="form-input" type="password" name="new_password" id="new-pw" minlength="8" required autocomplete="new-password" />
                </div>
                <div class="form-group">
                  <label class="form-label">Confirm New Password</label>
                  <input class="form-input" type="password" name="confirm_password" id="confirm-pw" required autocomplete="new-password" />
                </div>
              </div>
              <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                  Update Password
                </button>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('pw-form').addEventListener('submit', function (e) {
  var p1 = document.getElementById('new-pw');
  var p2 = document.getElementById('confirm-pw');
  if (p1.value !== p2.value) {
    e.preventDefault();
    p2.setCustomValidity('Passwords do not match.');
    p2.reportValidity();
  }
});
document.getElementById('confirm-pw').addEventListener('input', function () {
  this.setCustomValidity('');
});
</script>
</body>
</html>
