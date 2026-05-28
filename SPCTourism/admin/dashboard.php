<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

$current_page = 'dashboard';

$stats = [
    'places'   => 0,
    'foods'    => 0,
    'culture'  => 0,
    'photos'   => 0,
    'guides'   => 0,
    'bookings' => 0,
    'tourists' => 0,
];

function get_count($conn, $query) {
    $res = mysqli_query($conn, $query);
    if ($res) {
        $row = mysqli_fetch_row($res);
        return $row ? (int)$row[0] : 0;
    }
    return 0;
}

$stats['places']   = get_count($conn, "SELECT COUNT(*) FROM places");
$stats['foods']    = get_count($conn, "SELECT COUNT(*) FROM foods");
$stats['culture']  = get_count($conn, "SELECT COUNT(*) FROM culture");
$stats['photos']   = get_count($conn, "SELECT COUNT(*) FROM photos");
$stats['guides']   = get_count($conn, "SELECT COUNT(*) FROM tour_guides");
$stats['bookings'] = get_count($conn, "SELECT COUNT(*) FROM bookings");
$stats['tourists'] = get_count($conn, "SELECT COUNT(*) FROM users WHERE role = 'tourist'");

$recent_bookings = [];
$res = mysqli_query($conn, "
    SELECT b.booking_id, b.visit_date, b.number_of_people, b.status, b.created_at,
           p.place_name, u.username
    FROM bookings b
    JOIN places p ON b.place_id = p.place_id
    JOIN users u ON b.tourist_id = u.user_id
    ORDER BY b.created_at DESC LIMIT 6
");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $recent_bookings[] = $row;
    }
}

$recent_places = [];
$res = mysqli_query($conn, "
    SELECT p.place_id, p.place_name, p.location, c.category_name,
           (SELECT ph.file_path FROM photos ph WHERE ph.place_id = p.place_id LIMIT 1) AS photo
    FROM places p
    LEFT JOIN categories c ON p.category_id = c.category_id
    ORDER BY p.place_id DESC LIMIT 5
");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $recent_places[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard — Admin</title>
  <link rel="icon" type="image/png" href="../images/logo/logo.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="admin.css" />
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="admin-main">

  <!-- Topbar -->
  <div class="topbar">
    <div class="topbar-left">
      <span class="topbar-title">Dashboard</span>
      <span class="topbar-breadcrumb">Welcome back, <?= htmlspecialchars($_SESSION['username']) ?></span>
    </div>
    <div class="topbar-right">
      <a href="../index.php" class="topbar-visit" target="_blank">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        View Site
      </a>
    </div>
  </div>

  <div class="admin-content">

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
        <div><div class="stat-value"><?= $stats['places'] ?></div><div class="stat-label">Places</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg></div>
        <div><div class="stat-value"><?= $stats['foods'] ?></div><div class="stat-label">Foods</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div>
        <div><div class="stat-value"><?= $stats['culture'] ?></div><div class="stat-label">Culture</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>
        <div><div class="stat-value"><?= $stats['photos'] ?></div><div class="stat-label">Photos</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
        <div><div class="stat-value"><?= $stats['guides'] ?></div><div class="stat-label">Tour Guides</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
        <div><div class="stat-value"><?= $stats['bookings'] ?></div><div class="stat-label">Bookings</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
        <div><div class="stat-value"><?= $stats['tourists'] ?></div><div class="stat-label">Tourists</div></div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

      <!-- Recent Bookings -->
      <div class="admin-card">
        <div class="admin-card-header">
          <span class="admin-card-title">Recent Bookings</span>
          <a href="bookings.php" class="btn btn-outline btn-sm">View All</a>
        </div>
        <?php if ($recent_bookings): ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Place</th><th>Tourist</th><th>Visit Date</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($recent_bookings as $b): ?>
              <tr>
                <td><strong><?= htmlspecialchars($b['place_name']) ?></strong></td>
                <td><?= htmlspecialchars($b['username']) ?></td>
                <td><?= htmlspecialchars($b['visit_date']) ?></td>
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
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg><p>No bookings yet.</p></div>
        <?php endif; ?>
      </div>

      <!-- Recent Places -->
      <div class="admin-card">
        <div class="admin-card-header">
          <span class="admin-card-title">Places</span>
          <a href="places.php" class="btn btn-outline btn-sm">Manage</a>
        </div>
        <?php if ($recent_places): ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th></th><th>Name</th><th>Category</th></tr></thead>
            <tbody>
              <?php foreach ($recent_places as $p): ?>
              <tr>
                <td>
                  <?php if ($p['photo']): ?>
                    <img src="../<?= htmlspecialchars($p['photo']) ?>" class="thumb" alt="" />
                  <?php else: ?>
                    <div class="thumb-placeholder"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>
                  <?php endif; ?>
                </td>
                <td><strong><?= htmlspecialchars($p['place_name']) ?></strong><br><small style="color:rgba(51,100,61,0.5)"><?= htmlspecialchars($p['location']) ?></small></td>
                <td><span class="badge badge-green"><?= htmlspecialchars($p['category_name'] ?? '—') ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><p>No places yet.</p></div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

</body>
</html>
