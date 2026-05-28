<?php
require_once 'includes/auth.php';
require_once '../conn.php';

$uid = (int)$_SESSION['user_id'];
$msg = $err = $pw_msg = $pw_err = '';

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

    // ── Upload / remove profile photo ───────────────────────
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
            $msg = 'Profile photo removed.';

        } elseif (!empty($file['name']) && $file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $dest_dir = __DIR__ . '/../images/profile/';
                if (!is_dir($dest_dir)) mkdir($dest_dir, 0755, true);
                $safe_name = 'user_' . $uid . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
                if (move_uploaded_file($file['tmp_name'], $dest_dir . $safe_name)) {
                    // Delete old photo file
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
                    $msg = 'Profile photo updated.';
                } else {
                    $err = 'Failed to save photo. Check folder permissions.';
                }
            } else {
                $err = 'Only JPG, PNG, or WEBP files are accepted.';
            }
        }

    // ── Update profile info ─────────────────────────────────
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

    // ── Change password ─────────────────────────────────────
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

$initials = strtoupper(substr($user['username'] ?? 'T', 0, 1));
$has_photo = !empty($user['profile_photo']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Profile &mdash; 7 Lakes Escapes</title>
  <link rel="icon" type="image/png" href="../images/logo/logo.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="tourist.css" />
  <style>
    .profile-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
      align-items: start;
    }
    @media (max-width: 720px) { .profile-grid { grid-template-columns: 1fr; } }

    /* Photo upload area */
    .photo-section {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 28px 20px 20px;
      border-bottom: 1px solid rgba(51,100,61,0.08);
      margin-bottom: 20px;
    }
    .profile-avatar-lg {
      width: 96px; height: 96px; border-radius: 50%;
      background: #33643d;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Cormorant Garamond', serif;
      font-size: 2.4rem; font-weight: 700; color: #fff;
      margin-bottom: 14px;
      border: 4px solid rgba(51,100,61,0.15);
      overflow: hidden;
      flex-shrink: 0;
    }
    .profile-avatar-lg img { width: 100%; height: 100%; object-fit: cover; }
    .profile-display-name {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.2rem; font-weight: 700; color: #102F1F; margin-bottom: 2px;
    }
    .profile-display-sub { font-size: 0.77rem; color: rgba(51,100,61,0.5); }

    .photo-upload-row {
      display: flex; gap: 10px; align-items: center; margin-top: 14px; flex-wrap: wrap; justify-content: center;
    }
    .photo-upload-label {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 16px;
      background: rgba(51,100,61,0.07);
      border: 1.5px dashed rgba(51,100,61,0.3);
      border-radius: 6px;
      font-size: 0.78rem; font-weight: 500; color: #33643d;
      cursor: pointer; transition: background 0.15s;
    }
    .photo-upload-label:hover { background: rgba(51,100,61,0.13); }
    .photo-upload-label input { display: none; }
    .remove-photo-btn {
      font-size: 0.75rem; color: #c0392b; background: none;
      border: 1.5px solid rgba(192,57,43,0.25); border-radius: 6px;
      padding: 7px 14px; cursor: pointer; transition: background 0.15s;
    }
    .remove-photo-btn:hover { background: rgba(192,57,43,0.06); }
    .photo-hint { font-size: 0.72rem; color: rgba(51,100,61,0.45); margin-top: 8px; }
    .section-label {
      font-size: 0.68rem; font-weight: 600; letter-spacing: 0.09em;
      text-transform: uppercase; color: rgba(51,100,61,0.4); margin-bottom: 16px;
    }
  </style>
</head>
<body>

<nav class="t-nav">
  <a href="../index.php" class="t-nav-brand">
    <img src="../images/logo/logo.png" alt="Logo" />
    <span class="t-nav-brand-name">7 Lakes Escapes</span>
  </a>
  <div class="t-nav-links">
    <a href="../index.php" class="t-nav-link">Home</a>
    <a href="../attractions.php" class="t-nav-link">Attractions</a>
    <a href="../food-culture.php" class="t-nav-link">Food &amp; Culture</a>
    <a href="../plan.php" class="t-nav-link">Plan Your Visit</a>
  </div>
  <div class="t-nav-user">
    <a href="profile.php" style="display:flex;align-items:center;gap:8px;text-decoration:none;color:inherit;">
      <div class="t-nav-avatar" style="overflow:hidden;">
        <?php if ($has_photo): ?>
          <img src="../<?= htmlspecialchars($user['profile_photo']) ?>" style="width:100%;height:100%;object-fit:cover;" alt="" />
        <?php else: ?>
          <?= $initials ?>
        <?php endif; ?>
      </div>
      <span><?= htmlspecialchars($_SESSION['username'] ?? '') ?></span>
    </a>
    <a href="logout.php" class="t-nav-logout">Sign Out</a>
  </div>
</nav>

<div class="t-page-wide">

  <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;">
    <div class="t-header" style="margin-bottom:0;">
      <div class="t-header-eyebrow">Account</div>
      <h1>My <em style="font-style:italic;color:var(--green-mid)">Profile</em></h1>
    </div>
    <a href="dashboard.php" class="t-btn t-btn-outline">&larr; Dashboard</a>
  </div>

  <?php if ($msg): ?>
    <div class="t-alert t-alert-success" style="margin-bottom:20px;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>
  <?php if ($err): ?>
    <div class="t-alert t-alert-error" style="margin-bottom:20px;"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <div class="profile-grid">

    <!-- ── Left: avatar + profile info ── -->
    <div class="t-card">

      <!-- Photo upload (own form) -->
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_photo" />
        <div class="photo-section">

          <div class="profile-avatar-lg" id="avatar-preview">
            <?php if ($has_photo): ?>
              <img src="../<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile photo" />
            <?php else: ?>
              <?= $initials ?>
            <?php endif; ?>
          </div>

          <p class="profile-display-name"><?= htmlspecialchars($user['name'] ?: $user['username']) ?></p>
          <p class="profile-display-sub">@<?= htmlspecialchars($user['username']) ?></p>

          <div class="photo-upload-row">
            <label class="photo-upload-label">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              <?= $has_photo ? 'Change Photo' : 'Upload Photo' ?>
              <input type="file" name="profile_photo" id="photo-file-input" accept=".jpg,.jpeg,.png,.webp" />
            </label>
            <?php if ($has_photo): ?>
              <button type="submit" name="remove_photo" value="1" class="remove-photo-btn"
                      onclick="return confirm('Remove your profile photo?')">Remove</button>
            <?php endif; ?>
          </div>
          <p class="photo-hint">JPG, PNG or WEBP &mdash; max 5 MB</p>

          <!-- Auto-submit on file pick -->
          <button type="submit" id="photo-submit-btn" style="display:none;"></button>
        </div>
      </form>

      <!-- Profile info form -->
      <div class="t-card-body" style="padding-top:0;">
        <p class="section-label">Personal Information</p>

        <form method="POST" class="t-form">
          <input type="hidden" name="action" value="update_profile" />

          <div class="t-form-group">
            <label class="t-label">Full Name</label>
            <input class="t-input" type="text" name="name"
                   value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                   placeholder="Your full name" />
          </div>

          <div class="t-form-group">
            <label class="t-label">Username <span style="color:#c0392b">*</span></label>
            <input class="t-input" type="text" name="username"
                   value="<?= htmlspecialchars($user['username'] ?? '') ?>" required />
          </div>

          <div class="t-form-group">
            <label class="t-label">Email Address <span style="color:#c0392b">*</span></label>
            <input class="t-input" type="email" name="email"
                   value="<?= htmlspecialchars($user['email'] ?? '') ?>" required />
          </div>

          <button type="submit" class="t-btn t-btn-primary" style="width:100%;justify-content:center;margin-top:8px;">
            Save Changes
          </button>
        </form>
      </div>
    </div>

    <!-- ── Right: change password ── -->
    <div class="t-card">
      <div class="t-card-header">
        <span class="t-card-title">Change Password</span>
      </div>
      <div class="t-card-body">

        <?php if ($pw_msg): ?>
          <div class="t-alert t-alert-success" style="margin-bottom:16px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <?= htmlspecialchars($pw_msg) ?>
          </div>
        <?php endif; ?>
        <?php if ($pw_err): ?>
          <div class="t-alert t-alert-error" style="margin-bottom:16px;"><?= htmlspecialchars($pw_err) ?></div>
        <?php endif; ?>

        <form method="POST" class="t-form">
          <input type="hidden" name="action" value="change_password" />

          <div class="t-form-group">
            <label class="t-label">Current Password</label>
            <input class="t-input" type="password" name="current_password"
                   placeholder="Enter current password" required />
          </div>
          <div class="t-form-group">
            <label class="t-label">New Password</label>
            <input class="t-input" type="password" name="new_password" id="new-pw"
                   placeholder="At least 8 characters" required minlength="8" />
          </div>
          <div class="t-form-group">
            <label class="t-label">Confirm New Password</label>
            <input class="t-input" type="password" name="confirm_password" id="confirm-pw"
                   placeholder="Repeat new password" required minlength="8" />
          </div>

          <button type="submit" class="t-btn t-btn-primary" style="width:100%;justify-content:center;margin-top:8px;">
            Change Password
          </button>
        </form>
      </div>
    </div>

  </div><!-- /profile-grid -->
</div>

<script>
// Live preview when a file is picked, then auto-submit
document.getElementById('photo-file-input').addEventListener('change', function() {
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const el = document.getElementById('avatar-preview');
    el.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;" />';
  };
  reader.readAsDataURL(file);
  // Auto-submit the photo form
  document.getElementById('photo-submit-btn').click();
});

// Confirm password match
document.querySelector('form input[name="confirm_password"]')?.closest('form')
  ?.addEventListener('submit', function(e) {
    const p1 = document.getElementById('new-pw');
    const p2 = document.getElementById('confirm-pw');
    if (p1 && p2 && p1.value !== p2.value) {
      e.preventDefault();
      p2.setCustomValidity('Passwords do not match.');
      p2.reportValidity();
    } else if (p2) p2.setCustomValidity('');
  });
document.getElementById('confirm-pw')?.addEventListener('input', function() {
  this.setCustomValidity('');
});
</script>

</body>
</html>

