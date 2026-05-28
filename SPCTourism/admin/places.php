<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

$current_page = 'places';
$message = $error = '';
$edit = null;

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name     = trim($_POST['place_name'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $cat_id   = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $map_link = trim($_POST['google_map_link'] ?? '') ?: null;

        if (!$name || !$location) {
            $error = 'Name and location are required.';
        } elseif ($action === 'add') {
            $stmt = mysqli_prepare($conn, "INSERT INTO places (place_name, description, location, category_id, google_map_link) VALUES (?,?,?,?,?)");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'sssis', $name, $desc, $location, $cat_id, $map_link);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            $message = "Place \"$name\" added successfully.";
        } else {
            $id = (int)$_POST['place_id'];
            $stmt = mysqli_prepare($conn, "UPDATE places SET place_name=?, description=?, location=?, category_id=?, google_map_link=? WHERE place_id=?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'sssisi', $name, $desc, $location, $cat_id, $map_link, $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            $message = "Place \"$name\" updated successfully.";
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['place_id'];
        $name = '';
        $stmt = mysqli_prepare($conn, "SELECT place_name FROM places WHERE place_id=?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $name);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
        }

        $stmt = mysqli_prepare($conn, "DELETE FROM places WHERE place_id=?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        $message = "Place \"$name\" deleted.";
    }
}

// Edit prefill
if (isset($_GET['edit'])) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM places WHERE place_id=?");
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
    $where_clause = "WHERE (p.place_name LIKE '%$key%' OR p.location LIKE '%$key%' OR c.category_name LIKE '%$key%' OR p.description LIKE '%$key%')";
}

$places = [];
$res = mysqli_query($conn, "SELECT p.*, c.category_name, (SELECT ph.file_path FROM photos ph WHERE ph.place_id = p.place_id LIMIT 1) AS photo FROM places p LEFT JOIN categories c ON p.category_id = c.category_id $where_clause ORDER BY p.place_id");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $places[] = $row;
    }
}

$categories = [];
$res = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $categories[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Places — Admin</title>
  <link rel="icon" type="image/png" href="../images/logo/logo.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="admin.css" />
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="admin-main">

  <div class="topbar">
    <div class="topbar-left">
      <span class="topbar-title">Places</span>
      <span class="topbar-breadcrumb"><a href="dashboard.php">Dashboard</a> / Places</span>
    </div>
    <div class="topbar-right">
      <a href="places.php?add=1" class="btn btn-primary btn-sm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Place
      </a>
    </div>
  </div>

  <div class="admin-content">
    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Add / Edit Form -->
    <?php if (isset($_GET['add']) || $edit): ?>
    <div class="form-panel">
      <div class="form-panel-title"><?= $edit ? 'Edit Place' : 'Add New Place' ?></div>
      <form method="POST">
        <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>" />
        <?php if ($edit): ?><input type="hidden" name="place_id" value="<?= $edit['place_id'] ?>" /><?php endif; ?>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Place Name *</label>
            <input class="form-input" type="text" name="place_name" value="<?= htmlspecialchars($edit['place_name'] ?? '') ?>" required />
          </div>
          <div class="form-group">
            <label class="form-label">Location *</label>
            <input class="form-input" type="text" name="location" value="<?= htmlspecialchars($edit['location'] ?? '') ?>" required />
          </div>
          <div class="form-group">
            <label class="form-label">Category</label>
            <select class="form-select" name="category_id">
              <option value="">— None —</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['category_id'] ?>" <?= ($edit['category_id'] ?? '') == $cat['category_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cat['category_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Google Maps Link</label>
            <input class="form-input" type="url" name="google_map_link" value="<?= htmlspecialchars($edit['google_map_link'] ?? '') ?>" placeholder="https://maps.google.com/..." />
          </div>
          <div class="form-group full">
            <label class="form-label">Description</label>
            <textarea class="form-textarea" name="description"><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary"><?= $edit ? 'Update Place' : 'Add Place' ?></button>
          <a href="places.php" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="admin-card">
      <div class="admin-card-header">
        <span class="admin-card-title">All Places (<?= count($places) ?>)</span>
        <form method="POST" action="" class="card-search-wrap">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input type="text" name="key" placeholder="Search places..." class="card-search-input" value="<?= htmlspecialchars($_POST['key'] ?? '') ?>" />
          <button type="submit" name="search" style="display:none;"></button>
        </form>
      </div>
      <?php if ($places): ?>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th></th><th>Name</th><th>Location</th><th>Category</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($places as $p): ?>
            <tr>
              <td>
                <?php if ($p['photo']): ?>
                  <img src="../<?= htmlspecialchars($p['photo']) ?>" class="thumb" alt="" />
                <?php else: ?>
                  <div class="thumb-placeholder"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><polyline points="21 15 16 10 5 21"/></svg></div>
                <?php endif; ?>
              </td>
              <td><strong><?= htmlspecialchars($p['place_name']) ?></strong></td>
              <td><?= htmlspecialchars($p['location']) ?></td>
              <td><span class="badge badge-green"><?= htmlspecialchars($p['category_name'] ?? '—') ?></span></td>
              <td>
                <a href="places.php?edit=<?= $p['place_id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this place?')">
                  <input type="hidden" name="action" value="delete" />
                  <input type="hidden" name="place_id" value="<?= $p['place_id'] ?>" />
                  <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="empty-state"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/></svg><p>No places yet. Add one above.</p></div>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
