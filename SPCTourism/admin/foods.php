<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

$current_page = 'foods';
$message = $error = '';
$edit = null;

// ── Photo helper ────────────────────────────────────────────
// ── Photo helper ────────────────────────────────────────────
function handle_photo($conn, int $id, string $field, string $folder, string $prefix): void {
    $file = $_FILES['photo'] ?? null;
    if (!empty($file['name'])) {
        $ext_img = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext_img, ['jpg','jpeg','png','webp']) || $file['error'] !== UPLOAD_ERR_OK) return;
        $dest_dir  = __DIR__ . '/../' . $folder . '/';
        $safe_name = $prefix . $id . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
        if (move_uploaded_file($file['tmp_name'], $dest_dir . $safe_name)) {
            $old_row_path = null;
            $stmt = mysqli_prepare($conn, "SELECT file_path FROM photos WHERE $field = ? LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $old_row_path);
                $fetched = mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);

                if ($fetched) {
                    $full = __DIR__ . '/../' . $old_row_path;
                    if (file_exists($full)) @unlink($full);
                    
                    $stmt_del = mysqli_prepare($conn, "DELETE FROM photos WHERE $field = ?");
                    if ($stmt_del) {
                        mysqli_stmt_bind_param($stmt_del, 'i', $id);
                        mysqli_stmt_execute($stmt_del);
                        mysqli_stmt_close($stmt_del);
                    }
                }
            }
            $stmt_ins = mysqli_prepare($conn, "INSERT INTO photos (file_name, file_path, $field) VALUES (?,?,?)");
            if ($stmt_ins) {
                $db_path = $folder . '/' . $safe_name;
                mysqli_stmt_bind_param($stmt_ins, 'ssi', $safe_name, $db_path, $id);
                mysqli_stmt_execute($stmt_ins);
                mysqli_stmt_close($stmt_ins);
            }
        }
    }
    if (!empty($_POST['remove_photo'])) {
        $old_row_path = null;
        $stmt = mysqli_prepare($conn, "SELECT file_path FROM photos WHERE $field = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $old_row_path);
            $fetched = mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            if ($fetched) {
                $full = __DIR__ . '/../' . $old_row_path;
                if (file_exists($full)) @unlink($full);
                
                $stmt_del = mysqli_prepare($conn, "DELETE FROM photos WHERE $field = ?");
                if ($stmt_del) {
                    mysqli_stmt_bind_param($stmt_del, 'i', $id);
                    mysqli_stmt_execute($stmt_del);
                    mysqli_stmt_close($stmt_del);
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' || $action === 'edit') {
        $name  = trim($_POST['food_name']       ?? '');
        $desc  = trim($_POST['description']     ?? '');
        $resto = trim($_POST['restaurant_name'] ?? '') ?: null;
        $loc   = trim($_POST['location']        ?? '');
        $price = trim($_POST['price_range']     ?? '') ?: null;
        $place = !empty($_POST['place_id']) ? (int)$_POST['place_id'] : null;

        if (!$name) {
            $error = 'Food name is required.';
        } elseif ($action === 'add') {
            $stmt = mysqli_prepare($conn, "INSERT INTO foods (food_name, description, restaurant_name, location, price_range, place_id) VALUES (?,?,?,?,?,?)");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'sssssi', $name, $desc, $resto, $loc, $price, $place);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            $food_id = (int)mysqli_insert_id($conn);
            handle_photo($conn, $food_id, 'food_id', 'images/food', 'food_');
            $message = "\"$name\" added.";
        } else {
            $food_id = (int)$_POST['food_id'];
            $stmt = mysqli_prepare($conn, "UPDATE foods SET food_name=?, description=?, restaurant_name=?, location=?, price_range=?, place_id=? WHERE food_id=?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'sssssii', $name, $desc, $resto, $loc, $price, $place, $food_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            handle_photo($conn, $food_id, 'food_id', 'images/food', 'food_');
            $message = "\"$name\" updated.";
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['food_id'];
        $name = '';
        $stmt = mysqli_prepare($conn, "SELECT food_name FROM foods WHERE food_id=?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $name);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
        }

        $old_photos = [];
        $stmt = mysqli_prepare($conn, "SELECT file_path FROM photos WHERE food_id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($res) {
                while ($row = mysqli_fetch_assoc($res)) {
                    $old_photos[] = $row;
                }
            }
            mysqli_stmt_close($stmt);
        }
        foreach ($old_photos as $p) {
            $full = __DIR__ . '/../' . $p['file_path'];
            if (file_exists($full)) @unlink($full);
        }

        $stmt = mysqli_prepare($conn, "DELETE FROM foods WHERE food_id=?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        $message = "\"$name\" deleted.";
    }
}

if (isset($_GET['edit'])) {
    $stmt = mysqli_prepare($conn, "
        SELECT f.*, (SELECT ph.file_path FROM photos ph WHERE ph.food_id = f.food_id LIMIT 1) AS photo
        FROM foods f WHERE f.food_id = ?
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
    $where_clause = "WHERE (f.food_name LIKE '%$key%' OR f.restaurant_name LIKE '%$key%' OR f.location LIKE '%$key%' OR f.price_range LIKE '%$key%' OR f.description LIKE '%$key%')";
}

$foods = [];
$res = mysqli_query($conn, "SELECT f.*, (SELECT ph.file_path FROM photos ph WHERE ph.food_id = f.food_id LIMIT 1) AS photo FROM foods f $where_clause ORDER BY f.food_id");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $foods[] = $row;
    }
}

$places = [];
$res = mysqli_query($conn, "SELECT place_id, place_name FROM places ORDER BY place_name");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $places[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Food & Drinks — Admin</title>
  <link rel="icon" type="image/png" href="../images/logo/logo.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="admin.css" />
  <style>
    .photo-upload-wrap {
      display: flex; align-items: center; gap: 20px;
      padding: 16px;
      background: rgba(51,100,61,0.04);
      border: 1.5px dashed rgba(51,100,61,0.2);
      border-radius: 10px;
    }
    .photo-thumb-preview {
      width: 80px; height: 80px; border-radius: 8px;
      object-fit: cover; border: 2px solid rgba(51,100,61,0.2);
      flex-shrink: 0; background: #e4eee3;
      display: flex; align-items: center; justify-content: center; overflow: hidden;
    }
    .photo-thumb-preview img { width: 100%; height: 100%; object-fit: cover; }
    .photo-thumb-preview svg { width: 32px; height: 32px; color: rgba(51,100,61,0.3); }
    .remove-photo-row { display:flex;align-items:center;gap:6px;margin-top:8px;font-size:0.8rem;color:#c0392b; }
    .remove-photo-row input { accent-color:#c0392b; }
  </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="admin-main">
  <div class="topbar">
    <div class="topbar-left">
      <span class="topbar-title">Food &amp; Drinks</span>
      <span class="topbar-breadcrumb"><a href="dashboard.php">Dashboard</a> / Food &amp; Drinks</span>
    </div>
    <div class="topbar-right">
      <a href="foods.php?add=1" class="btn btn-primary btn-sm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Food
      </a>
    </div>
  </div>

  <div class="admin-content">
    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <?php if (isset($_GET['add']) || $edit): ?>
    <div class="form-panel">
      <div class="form-panel-title"><?= $edit ? 'Edit Food Item' : 'Add New Food Item' ?></div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>" />
        <?php if ($edit): ?><input type="hidden" name="food_id" value="<?= $edit['food_id'] ?>" /><?php endif; ?>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Food Name *</label>
            <input class="form-input" type="text" name="food_name" value="<?= htmlspecialchars($edit['food_name'] ?? '') ?>" required />
          </div>
          <div class="form-group">
            <label class="form-label">Restaurant / Source</label>
            <input class="form-input" type="text" name="restaurant_name" value="<?= htmlspecialchars($edit['restaurant_name'] ?? '') ?>" />
          </div>
          <div class="form-group">
            <label class="form-label">Location</label>
            <input class="form-input" type="text" name="location" value="<?= htmlspecialchars($edit['location'] ?? '') ?>" />
          </div>
          <div class="form-group">
            <label class="form-label">Price Range</label>
            <input class="form-input" type="text" name="price_range" value="<?= htmlspecialchars($edit['price_range'] ?? '') ?>" placeholder="e.g. ₱80–₱150" />
          </div>
          <div class="form-group">
            <label class="form-label">Related Place</label>
            <select class="form-select" name="place_id">
              <option value="">— None —</option>
              <?php foreach ($places as $pl): ?>
                <option value="<?= $pl['place_id'] ?>" <?= ($edit['place_id'] ?? '') == $pl['place_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($pl['place_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group full">
            <label class="form-label">Description</label>
            <textarea class="form-textarea" name="description"><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>
          </div>

          <!-- Photo -->
          <div class="form-group full">
            <label class="form-label">Photo</label>
            <div class="photo-upload-wrap">
              <div class="photo-thumb-preview" id="photo-preview">
                <?php if (!empty($edit['photo'])): ?>
                  <img src="../<?= htmlspecialchars($edit['photo']) ?>" alt="Current photo" />
                <?php else: ?>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/></svg>
                <?php endif; ?>
              </div>
              <div style="flex:1;">
                <label class="form-label" for="photo-input"><?= !empty($edit['photo']) ? 'Change Photo' : 'Upload Photo' ?></label>
                <input class="form-input" type="file" id="photo-input" name="photo" accept=".jpg,.jpeg,.png,.webp" style="padding:6px;" />
                <div style="font-size:0.75rem;color:rgba(51,100,61,0.5);margin-top:4px;">JPG, PNG or WEBP.</div>
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
          <button type="submit" class="btn btn-primary"><?= $edit ? 'Update' : 'Add Food' ?></button>
          <a href="foods.php" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <div class="admin-card">
      <div class="admin-card-header">
        <span class="admin-card-title">All Foods (<?= count($foods) ?>)</span>
        <form method="POST" action="" class="card-search-wrap">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input type="text" name="key" placeholder="Search food items..." class="card-search-input" value="<?= htmlspecialchars($_POST['key'] ?? '') ?>" />
          <button type="submit" name="search" style="display:none;"></button>
        </form>
      </div>
      <?php if ($foods): ?>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th></th><th>Name</th><th>Restaurant</th><th>Price Range</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($foods as $f): ?>
            <tr>
              <td>
                <?php if ($f['photo']): ?>
                  <img src="../<?= htmlspecialchars($f['photo']) ?>" class="thumb" alt="" />
                <?php else: ?>
                  <div class="thumb-placeholder"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/></svg></div>
                <?php endif; ?>
              </td>
              <td><strong><?= htmlspecialchars($f['food_name']) ?></strong></td>
              <td><?= htmlspecialchars($f['restaurant_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($f['price_range'] ?? '—') ?></td>
              <td>
                <a href="foods.php?edit=<?= $f['food_id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this item?')">
                  <input type="hidden" name="action" value="delete" />
                  <input type="hidden" name="food_id" value="<?= $f['food_id'] ?>" />
                  <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="empty-state"><p>No food items yet.</p></div>
      <?php endif; ?>
    </div>
  </div>
</div>
<script>
document.getElementById('photo-input')?.addEventListener('change', function () {
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const wrap = document.getElementById('photo-preview');
    wrap.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;" />';
  };
  reader.readAsDataURL(file);
});
</script>
</body>
</html>
