<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

$current_page = 'photos';
$message = $error = '';

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        $place_id   = !empty($_POST['place_id']) ? (int)$_POST['place_id'] : null;
        $food_id    = !empty($_POST['food_id']) ? (int)$_POST['food_id'] : null;
        $culture_id = !empty($_POST['culture_id']) ? (int)$_POST['culture_id'] : null;
        $guide_id   = !empty($_POST['guide_id']) ? (int)$_POST['guide_id'] : null;

        if (empty($_FILES['photo']['name'])) {
            $error = 'Please select a file to upload.';
        } else {
            $file     = $_FILES['photo'];
            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed  = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $allowed)) {
                $error = 'Only JPG, PNG, and WEBP files are allowed.';
            } else {
                // Determine subfolder
                if ($place_id) {
                    $cat_name = '';
                    $stmt = mysqli_prepare($conn, "SELECT c.category_name FROM places p LEFT JOIN categories c ON p.category_id = c.category_id WHERE p.place_id = ?");
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, 'i', $place_id);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_bind_result($stmt, $cat_name);
                        mysqli_stmt_fetch($stmt);
                        mysqli_stmt_close($stmt);
                    }
                    $cat_name = strtolower($cat_name ?? '');
                    $folder = str_contains($cat_name, 'lake') ? 'images/lakes' : 'images/attractions';
                } elseif ($food_id) {
                    $folder = 'images/food';
                } elseif ($culture_id) {
                    $folder = 'images/culture';
                } elseif ($guide_id) {
                    $folder = 'images/guides';
                } else {
                    $folder = 'images/attractions';
                }

                $dest_dir  = __DIR__ . '/../' . $folder . '/';
                $safe_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
                $dest_path = $dest_dir . $safe_name;

                if (move_uploaded_file($file['tmp_name'], $dest_path)) {
                    $db_path = $folder . '/' . $safe_name;
                    $stmt = mysqli_prepare($conn, "INSERT INTO photos (file_name, file_path, place_id, food_id, culture_id, guide_id) VALUES (?,?,?,?,?,?)");
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, 'ssiiii', $safe_name, $db_path, $place_id, $food_id, $culture_id, $guide_id);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }
                    $message = "Photo \"$safe_name\" uploaded successfully.";
                } else {
                    $error = 'Failed to save file. Check folder permissions.';
                }
            }
        }
    } elseif ($action === 'delete') {
        $photo_id = (int)$_POST['photo_id'];
        $fname = '';
        $fpath = '';
        $stmt = mysqli_prepare($conn, "SELECT file_name, file_path FROM photos WHERE photo_id=?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $photo_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $fname, $fpath);
            $fetched = mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            if ($fetched) {
                $full_path = __DIR__ . '/../' . $fpath;
                if (file_exists($full_path)) @unlink($full_path);
                
                $stmt_del = mysqli_prepare($conn, "DELETE FROM photos WHERE photo_id=?");
                if ($stmt_del) {
                    mysqli_stmt_bind_param($stmt_del, 'i', $photo_id);
                    mysqli_stmt_execute($stmt_del);
                    mysqli_stmt_close($stmt_del);
                }
                $message = "Photo \"$fname\" deleted.";
            }
        }
    }
}

$filter    = $_GET['filter'] ?? 'all';
$where_map = [
    'places'  => 'ph.place_id IS NOT NULL',
    'foods'   => 'ph.food_id IS NOT NULL',
    'culture' => 'ph.culture_id IS NOT NULL',
    'guides'  => 'ph.guide_id IS NOT NULL',
    'general' => 'ph.place_id IS NULL AND ph.food_id IS NULL AND ph.culture_id IS NULL AND ph.guide_id IS NULL',
];
$where_clauses = [];
if (isset($where_map[$filter])) {
    $where_clauses[] = $where_map[$filter];
}
if (isset($_POST['search']) && !empty($_POST['key'])) {
    $key = mysqli_real_escape_string($conn, trim($_POST['key']));
    $where_clauses[] = "(ph.file_name LIKE '%$key%' OR p.place_name LIKE '%$key%' OR f.food_name LIKE '%$key%' OR c.culture_name LIKE '%$key%' OR tg.first_name LIKE '%$key%' OR tg.last_name LIKE '%$key%')";
}

$where = "";
if (!empty($where_clauses)) {
    $where = "WHERE " . implode(" AND ", $where_clauses);
}

$photos = [];
$res = mysqli_query($conn, "SELECT ph.*, p.place_name, f.food_name, c.culture_name, CONCAT(tg.first_name,' ',tg.last_name) AS guide_name FROM photos ph LEFT JOIN places p ON ph.place_id = p.place_id LEFT JOIN foods f ON ph.food_id = f.food_id LEFT JOIN culture c ON ph.culture_id = c.culture_id LEFT JOIN tour_guides tg ON ph.guide_id = tg.guide_id $where ORDER BY ph.uploaded_at DESC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $photos[] = $row;
    }
}

$places = [];
$res = mysqli_query($conn, "SELECT place_id, place_name FROM places ORDER BY place_name");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $places[] = $row;
    }
}

$foods = [];
$res = mysqli_query($conn, "SELECT food_id, food_name FROM foods ORDER BY food_name");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $foods[] = $row;
    }
}

$cultures = [];
$res = mysqli_query($conn, "SELECT culture_id, culture_name FROM culture ORDER BY culture_name");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $cultures[] = $row;
    }
}

$guides = [];
$res = mysqli_query($conn, "SELECT guide_id, CONCAT(first_name,' ',last_name) AS guide_name FROM tour_guides ORDER BY last_name");
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
  <title>Photos — Admin</title>
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
      <span class="topbar-title">Photos</span>
      <span class="topbar-breadcrumb"><a href="dashboard.php">Dashboard</a> / Photos</span>
    </div>
  </div>

  <div class="admin-content">
    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Upload Form -->
    <div class="form-panel">
      <div class="form-panel-title">Upload New Photo</div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload" />
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Photo File *</label>
            <input class="form-input" type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" required />
          </div>
          <div class="form-group">
            <label class="form-label">Link to Place</label>
            <select class="form-select" name="place_id" id="sel-place" onchange="clearOthers('place')">
              <option value="">— None —</option>
              <?php foreach ($places as $pl): ?>
                <option value="<?= $pl['place_id'] ?>"><?= htmlspecialchars($pl['place_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Link to Food</label>
            <select class="form-select" name="food_id" id="sel-food" onchange="clearOthers('food')">
              <option value="">— None —</option>
              <?php foreach ($foods as $fd): ?>
                <option value="<?= $fd['food_id'] ?>"><?= htmlspecialchars($fd['food_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Link to Culture</label>
            <select class="form-select" name="culture_id" id="sel-culture" onchange="clearOthers('culture')">
              <option value="">— None —</option>
              <?php foreach ($cultures as $cu): ?>
                <option value="<?= $cu['culture_id'] ?>"><?= htmlspecialchars($cu['culture_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Link to Tour Guide</label>
            <select class="form-select" name="guide_id" id="sel-guide" onchange="clearOthers('guide')">
              <option value="">— None —</option>
              <?php foreach ($guides as $gu): ?>
                <option value="<?= $gu['guide_id'] ?>"><?= htmlspecialchars($gu['guide_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Upload Photo</button>
        </div>
      </form>
    </div>

    <!-- Filter tabs -->
    <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
      <?php foreach (['all'=>'All','places'=>'Places','foods'=>'Food','culture'=>'Culture','guides'=>'Guides','general'=>'General'] as $k=>$v): ?>
        <a href="photos.php?filter=<?= $k ?>" class="btn <?= $filter===$k ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= $v ?></a>
      <?php endforeach; ?>
    </div>

    <!-- Photo Grid -->
    <div class="admin-card">
      <div class="admin-card-header">
        <span class="admin-card-title">Photos (<?= count($photos) ?>)</span>
        <div class="card-search-wrap">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input type="text" placeholder="Search photos..." class="card-search-input" />
        </div>
      </div>
      <div class="admin-card-body">
        <?php if ($photos): ?>
        <div class="photo-grid">
          <?php foreach ($photos as $ph): ?>
          <div class="photo-item">
            <img src="../<?= htmlspecialchars($ph['file_path']) ?>" class="photo-img" alt="<?= htmlspecialchars($ph['file_name']) ?>" onerror="this.style.background='#e4eee3'" />
            <div class="photo-meta">
              <span class="photo-name" title="<?= htmlspecialchars($ph['file_name']) ?>"><?= htmlspecialchars($ph['file_name']) ?></span>
              <form method="POST" onsubmit="return confirm('Delete this photo?')">
                <input type="hidden" name="action" value="delete" />
                <input type="hidden" name="photo_id" value="<?= $ph['photo_id'] ?>" />
                <button type="submit" class="btn btn-danger btn-sm" style="padding:4px 8px;">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                </button>
              </form>
            </div>
            <?php $tag = $ph['place_name'] ?? $ph['food_name'] ?? $ph['culture_name'] ?? $ph['guide_name'] ?? null; ?>
            <?php if ($tag): ?>
            <div style="padding:0 12px 10px;font-size:0.68rem;color:rgba(51,100,61,0.55)">
              <?= htmlspecialchars($tag) ?>
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg><p>No photos in this category.</p></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<script>
function clearOthers(selected) {
  const map = { place: 'sel-place', food: 'sel-food', culture: 'sel-culture', guide: 'sel-guide' };
  Object.entries(map).forEach(([k, id]) => { if (k !== selected) document.getElementById(id).value = ''; });
}
</script>
</body>
</html>
