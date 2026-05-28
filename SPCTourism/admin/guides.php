<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

$current_page = 'guides';
$message = $error = '';
$edit = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $first   = trim($_POST['first_name']     ?? '');
        $middle  = trim($_POST['middle_name']    ?? '') ?: null;
        $last    = trim($_POST['last_name']      ?? '');
        $ext     = trim($_POST['extension_name'] ?? '') ?: null;
        $contact = trim($_POST['contact_number'] ?? '');
        $email   = trim($_POST['email']          ?? '');
        $status  = in_array($_POST['status'] ?? '', ['Available', 'Unavailable', 'On Leave'])
                   ? $_POST['status'] : 'Available';

        if (!$first || !$last) {
            $error = 'First and last name are required.';
        } else {
            if ($action === 'add') {
                $stmt = mysqli_prepare($conn, "INSERT INTO tour_guides (first_name, middle_name, last_name, extension_name, contact_number, email, status) VALUES (?,?,?,?,?,?,?)");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'sssssss', $first, $middle, $last, $ext, $contact, $email, $status);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
                $guide_id = (int)mysqli_insert_id($conn);
                $message  = "\"$first $last\" added.";
            } else {
                $guide_id = (int)$_POST['guide_id'];
                $stmt = mysqli_prepare($conn, "UPDATE tour_guides SET first_name=?, middle_name=?, last_name=?, extension_name=?, contact_number=?, email=?, status=? WHERE guide_id=?");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'sssssssi', $first, $middle, $last, $ext, $contact, $email, $status, $guide_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
                $message = "\"$first $last\" updated.";
            }

            // Handle photo upload
            if (!empty($_FILES['photo']['name'])) {
                $file    = $_FILES['photo'];
                $ext_img = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];

                if (!in_array($ext_img, $allowed)) {
                    $error = ($error ? $error . ' ' : '') . 'Photo must be JPG, PNG, or WEBP.';
                } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                    $error = ($error ? $error . ' ' : '') . 'Photo upload failed.';
                } else {
                    $dest_dir  = __DIR__ . '/../images/guides/';
                    $safe_name = 'guide_' . $guide_id . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
                    $dest_path = $dest_dir . $safe_name;
                    $file_path = 'images/guides/' . $safe_name;

                    if (move_uploaded_file($file['tmp_name'], $dest_path)) {
                        // Remove old photo record + file for this guide
                        $old_row_path = null;
                        $stmt = mysqli_prepare($conn, "SELECT file_path FROM photos WHERE guide_id = ? LIMIT 1");
                        if ($stmt) {
                            mysqli_stmt_bind_param($stmt, 'i', $guide_id);
                            mysqli_stmt_execute($stmt);
                            mysqli_stmt_bind_result($stmt, $old_row_path);
                            $fetched = mysqli_stmt_fetch($stmt);
                            mysqli_stmt_close($stmt);

                            if ($fetched) {
                                $old_full = __DIR__ . '/../' . $old_row_path;
                                if (file_exists($old_full)) @unlink($old_full);
                                
                                $stmt_del = mysqli_prepare($conn, "DELETE FROM photos WHERE guide_id = ?");
                                if ($stmt_del) {
                                    mysqli_stmt_bind_param($stmt_del, 'i', $guide_id);
                                    mysqli_stmt_execute($stmt_del);
                                    mysqli_stmt_close($stmt_del);
                                }
                            }
                        }

                        $stmt_ins = mysqli_prepare($conn, "INSERT INTO photos (file_name, file_path, guide_id) VALUES (?,?,?)");
                        if ($stmt_ins) {
                            mysqli_stmt_bind_param($stmt_ins, 'ssi', $safe_name, $file_path, $guide_id);
                            mysqli_stmt_execute($stmt_ins);
                            mysqli_stmt_close($stmt_ins);
                        }
                    }
                }
            }

            // Handle photo removal
            if (!empty($_POST['remove_photo'])) {
                $gid = (int)($_POST['guide_id'] ?? $guide_id);
                $old_row_path = null;
                $stmt = mysqli_prepare($conn, "SELECT file_path FROM photos WHERE guide_id = ? LIMIT 1");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'i', $gid);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_bind_result($stmt, $old_row_path);
                    $fetched = mysqli_stmt_fetch($stmt);
                    mysqli_stmt_close($stmt);

                    if ($fetched) {
                        $old_full = __DIR__ . '/../' . $old_row_path;
                        if (file_exists($old_full)) @unlink($old_full);
                        
                        $stmt_del = mysqli_prepare($conn, "DELETE FROM photos WHERE guide_id = ?");
                        if ($stmt_del) {
                            mysqli_stmt_bind_param($stmt_del, 'i', $gid);
                            mysqli_stmt_execute($stmt_del);
                            mysqli_stmt_close($stmt_del);
                        }
                    }
                }
            }
        }

    } elseif ($action === 'delete') {
        $id  = (int)$_POST['guide_id'];
        $gname = 'Guide';
        $stmt = mysqli_prepare($conn, "SELECT CONCAT(first_name,' ',last_name) FROM tour_guides WHERE guide_id=?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $gname);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
        }

        // Delete photo file
        $old_photos = [];
        $stmt = mysqli_prepare($conn, "SELECT file_path FROM photos WHERE guide_id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($res) {
                while ($p = mysqli_fetch_assoc($res)) {
                    $old_photos[] = $p;
                }
            }
            mysqli_stmt_close($stmt);
        }
        foreach ($old_photos as $p) {
            $full = __DIR__ . '/../' . $p['file_path'];
            if (file_exists($full)) @unlink($full);
        }

        $stmt = mysqli_prepare($conn, "DELETE FROM tour_guides WHERE guide_id=?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        $message = "\"$gname\" deleted.";
    }
}

if (isset($_GET['edit'])) {
    $stmt = mysqli_prepare($conn, "
        SELECT tg.*, (SELECT ph.file_path FROM photos ph WHERE ph.guide_id = tg.guide_id LIMIT 1) AS photo
        FROM tour_guides tg WHERE tg.guide_id = ?
    ");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $_GET['edit']);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $edit = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
    }
}

$where_clause = "";
if (isset($_POST['search']) && !empty($_POST['key'])) {
    $key = mysqli_real_escape_string($conn, trim($_POST['key']));
    $where_clause = "WHERE (tg.first_name LIKE '%$key%' OR tg.middle_name LIKE '%$key%' OR tg.last_name LIKE '%$key%' OR tg.contact_number LIKE '%$key%' OR tg.email LIKE '%$key%' OR tg.status LIKE '%$key%')";
}

$guides = [];
$res = mysqli_query($conn, "
    SELECT tg.*, (SELECT ph.file_path FROM photos ph WHERE ph.guide_id = tg.guide_id LIMIT 1) AS photo
    FROM tour_guides tg
    $where_clause
    ORDER BY tg.last_name, tg.first_name
");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $guides[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tour Guides — Admin</title>
  <link rel="icon" type="image/png" href="../images/logo/logo.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="admin.css" />
  <style>
    .guide-photo-wrap {
      display: flex;
      align-items: center;
      gap: 20px;
      padding: 16px;
      background: rgba(51,100,61,0.04);
      border: 1.5px dashed rgba(51,100,61,0.2);
      border-radius: 10px;
    }
    .guide-avatar-preview {
      width: 80px; height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid rgba(51,100,61,0.2);
      flex-shrink: 0;
      background: #e4eee3;
      display: flex; align-items: center; justify-content: center;
      overflow: hidden;
    }
    .guide-avatar-preview img { width: 100%; height: 100%; object-fit: cover; }
    .guide-avatar-preview svg { width: 36px; height: 36px; color: rgba(51,100,61,0.3); }
    .guide-photo-info { flex: 1; }
    .guide-photo-info label { display: block; margin-bottom: 6px; }
    .remove-photo-row { display: flex; align-items: center; gap: 6px; margin-top: 8px; font-size: 0.8rem; color: #c0392b; }
    .remove-photo-row input { accent-color: #c0392b; }
  </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="admin-main">
  <div class="topbar">
    <div class="topbar-left">
      <span class="topbar-title">Tour Guides</span>
      <span class="topbar-breadcrumb"><a href="dashboard.php">Dashboard</a> / Tour Guides</span>
    </div>
    <div class="topbar-right">
      <a href="guides.php?add=1" class="btn btn-primary btn-sm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Guide
      </a>
    </div>
  </div>

  <div class="admin-content">
    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <?php if (isset($_GET['add']) || $edit): ?>
    <div class="form-panel">
      <div class="form-panel-title"><?= $edit ? 'Edit Tour Guide' : 'Add Tour Guide' ?></div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>" />
        <?php if ($edit): ?><input type="hidden" name="guide_id" value="<?= $edit['guide_id'] ?>" /><?php endif; ?>

        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">First Name *</label>
            <input class="form-input" type="text" name="first_name" value="<?= htmlspecialchars($edit['first_name'] ?? '') ?>" required />
          </div>
          <div class="form-group">
            <label class="form-label">Middle Name</label>
            <input class="form-input" type="text" name="middle_name" value="<?= htmlspecialchars($edit['middle_name'] ?? '') ?>" />
          </div>
          <div class="form-group">
            <label class="form-label">Last Name *</label>
            <input class="form-input" type="text" name="last_name" value="<?= htmlspecialchars($edit['last_name'] ?? '') ?>" required />
          </div>
          <div class="form-group">
            <label class="form-label">Extension (Jr./Sr./III)</label>
            <input class="form-input" type="text" name="extension_name" value="<?= htmlspecialchars($edit['extension_name'] ?? '') ?>" placeholder="e.g. Jr." />
          </div>
          <div class="form-group">
            <label class="form-label">Contact Number</label>
            <input class="form-input" type="text" name="contact_number" value="<?= htmlspecialchars($edit['contact_number'] ?? '') ?>" placeholder="+63 9XX XXX XXXX" />
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input class="form-input" type="email" name="email" value="<?= htmlspecialchars($edit['email'] ?? '') ?>" />
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
              <?php foreach (['Available', 'Unavailable', 'On Leave'] as $s): ?>
                <option value="<?= $s ?>" <?= ($edit['status'] ?? 'Available') === $s ? 'selected' : '' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Photo upload -->
          <div class="form-group full">
            <label class="form-label">Profile Photo</label>
            <div class="guide-photo-wrap">
              <div class="guide-avatar-preview" id="avatar-preview">
                <?php if (!empty($edit['photo'])): ?>
                  <img src="../<?= htmlspecialchars($edit['photo']) ?>" alt="Current photo" id="avatar-img" />
                <?php else: ?>
                  <svg id="avatar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <?php endif; ?>
              </div>
              <div class="guide-photo-info">
                <label class="form-label" for="photo-input">
                  <?= (!empty($edit['photo'])) ? 'Change Photo' : 'Upload Photo' ?>
                </label>
                <input class="form-input" type="file" id="photo-input" name="photo"
                       accept=".jpg,.jpeg,.png,.webp" style="padding:6px;" />
                <div style="font-size:0.75rem;color:rgba(51,100,61,0.5);margin-top:4px;">
                  JPG, PNG or WEBP. Recommended: square crop.
                </div>
                <?php if (!empty($edit['photo'])): ?>
                <div class="remove-photo-row">
                  <input type="checkbox" name="remove_photo" id="remove-photo" value="1" />
                  <label for="remove-photo">Remove current photo</label>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary"><?= $edit ? 'Update Guide' : 'Add Guide' ?></button>
          <a href="guides.php" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <div class="admin-card">
      <div class="admin-card-header">
        <span class="admin-card-title">All Tour Guides (<?= count($guides) ?>)</span>
        <form method="POST" action="" class="card-search-wrap">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input type="text" name="key" placeholder="Search tour guides..." class="card-search-input" value="<?= htmlspecialchars($_POST['key'] ?? '') ?>" />
          <button type="submit" name="search" style="display:none;"></button>
        </form>
      </div>
      <?php if ($guides): ?>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th></th><th>Name</th><th>Contact</th><th>Email</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($guides as $g): ?>
            <tr>
              <td>
                <?php if ($g['photo']): ?>
                  <img src="../<?= htmlspecialchars($g['photo']) ?>" class="thumb" alt=""
                       style="border-radius:50%;object-fit:cover;" />
                <?php else: ?>
                  <div class="thumb-placeholder" style="border-radius:50%;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <strong>
                  <?= htmlspecialchars($g['last_name']) ?>,
                  <?= htmlspecialchars($g['first_name']) ?>
                  <?= $g['middle_name'] ? htmlspecialchars($g['middle_name'][0]) . '.' : '' ?>
                  <?= $g['extension_name'] ? htmlspecialchars($g['extension_name']) : '' ?>
                </strong>
              </td>
              <td><?= htmlspecialchars($g['contact_number'] ?? '—') ?></td>
              <td><?= htmlspecialchars($g['email'] ?? '—') ?></td>
              <td>
                <?php $badge = match($g['status']) {
                  'Available'   => 'badge-green',
                  'Unavailable' => 'badge-red',
                  default       => 'badge-orange'
                }; ?>
                <span class="badge <?= $badge ?>"><?= htmlspecialchars($g['status'] ?? 'Available') ?></span>
              </td>
              <td>
                <a href="guides.php?edit=<?= $g['guide_id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this guide?')">
                  <input type="hidden" name="action" value="delete" />
                  <input type="hidden" name="guide_id" value="<?= $g['guide_id'] ?>" />
                  <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <p>No tour guides yet. Add one above.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// Live preview when a new photo is selected
document.getElementById('photo-input')?.addEventListener('change', function () {
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const wrap = document.getElementById('avatar-preview');
    wrap.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;" />';
  };
  reader.readAsDataURL(file);
});
</script>
</body>
</html>
