<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

$current_page = 'bookings';
$message = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $bid    = (int)$_POST['booking_id'];
        $status = in_array($_POST['status'], ['pending', 'confirmed', 'cancelled']) ? $_POST['status'] : 'pending';
        $stmt = mysqli_prepare($conn, "UPDATE bookings SET status=? WHERE booking_id=?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $status, $bid);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        $message = 'Booking status updated.';
    } elseif ($action === 'delete') {
        $bid = (int)$_POST['booking_id'];
        $stmt = mysqli_prepare($conn, "DELETE FROM bookings WHERE booking_id=?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $bid);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        $message = 'Booking deleted.';
    }
}

$filter   = $_GET['filter'] ?? 'all';
$where_map = [
    'pending'   => "b.status='pending'",
    'confirmed' => "b.status='confirmed'",
    'cancelled' => "b.status='cancelled'",
];
$where_cond = $where_map[$filter] ?? '';

$search_cond = "";
if (isset($_POST['search']) && !empty($_POST['key'])) {
    $key = mysqli_real_escape_string($conn, trim($_POST['key']));
    $search_cond = "(p.place_name LIKE '%$key%' OR u.username LIKE '%$key%' OR u.name LIKE '%$key%' OR b.status LIKE '%$key%' OR b.booking_id LIKE '%$key%')";
}

$where_clause = "";
if ($where_cond && $search_cond) {
    $where_clause = "WHERE $where_cond AND $search_cond";
} elseif ($where_cond) {
    $where_clause = "WHERE $where_cond";
} elseif ($search_cond) {
    $where_clause = "WHERE $search_cond";
}

$bookings = [];
$res = mysqli_query($conn, "
    SELECT b.*, p.place_name, u.username, u.name AS tourist_name
    FROM bookings b
    JOIN places p ON b.place_id = p.place_id
    JOIN users   u ON b.tourist_id = u.user_id
    $where_clause
    ORDER BY b.created_at DESC
");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $bookings[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bookings — Admin</title>
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
      <span class="topbar-title">Bookings</span>
      <span class="topbar-breadcrumb"><a href="dashboard.php">Dashboard</a> / Bookings</span>
    </div>
  </div>

  <div class="admin-content">
    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Filter tabs -->
    <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
      <?php foreach (['all' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled'] as $k => $v): ?>
        <a href="bookings.php?filter=<?= $k ?>" class="btn <?= $filter === $k ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= $v ?></a>
      <?php endforeach; ?>
    </div>

    <div class="admin-card">
      <div class="admin-card-header">
        <span class="admin-card-title">Bookings (<?= count($bookings) ?>)</span>
        <form method="POST" action="" class="card-search-wrap">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input type="text" name="key" placeholder="Search bookings..." class="card-search-input" value="<?= htmlspecialchars($_POST['key'] ?? '') ?>" />
          <button type="submit" name="search" style="display:none;"></button>
        </form>
      </div>
      <?php if ($bookings): ?>
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th><th>Place</th><th>Tourist</th><th>Visit Date</th><th>People</th><th>Status</th><th>Booked On</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bookings as $b): ?>
            <tr>
              <td style="color:rgba(51,100,61,0.4);font-size:0.78rem">#<?= $b['booking_id'] ?></td>
              <td><strong><?= htmlspecialchars($b['place_name']) ?></strong></td>
              <td>
                <?= htmlspecialchars($b['tourist_name'] ?? $b['username']) ?>
                <br><small style="color:rgba(51,100,61,0.45)">@<?= htmlspecialchars($b['username']) ?></small>
              </td>
              <td><?= htmlspecialchars($b['visit_date']) ?></td>
              <td style="text-align:center"><?= (int)$b['number_of_people'] ?></td>
              <td>
                <?php
                  $badge = match(strtolower($b['status'])) {
                    'confirmed' => 'badge-green',
                    'pending'   => 'badge-orange',
                    'cancelled' => 'badge-red',
                    default     => 'badge-gray'
                  };
                ?>
                <span class="badge <?= $badge ?>"><?= htmlspecialchars($b['status']) ?></span>
              </td>
              <td style="font-size:0.78rem;color:rgba(51,100,61,0.55)"><?= htmlspecialchars(substr($b['created_at'], 0, 10)) ?></td>
              <td style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                <form method="POST" style="display:flex;gap:4px;align-items:center;">
                  <input type="hidden" name="action" value="update_status" />
                  <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>" />
                  <select name="status" class="form-select" style="padding:3px 6px;font-size:0.75rem;height:auto;">
                    <option value="pending"   <?= $b['status'] === 'pending'   ? 'selected' : '' ?>>Pending</option>
                    <option value="confirmed" <?= $b['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <option value="cancelled" <?= $b['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                  </select>
                  <button type="submit" class="btn btn-outline btn-sm">Save</button>
                </form>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this booking?')">
                  <input type="hidden" name="action" value="delete" />
                  <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>" />
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
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <p>No bookings found.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>
