<?php
require_once 'includes/auth.php';
require_once '../conn.php';

$uid = (int)$_SESSION['user_id'];

$counts = [
    'pending'   => 0,
    'confirmed' => 0,
    'total'     => 0,
];

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM bookings WHERE tourist_id=? AND status='Pending'");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $uid);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $c_pending);
    mysqli_stmt_fetch($stmt);
    $counts['pending'] = (int)$c_pending;
    mysqli_stmt_close($stmt);
}

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM bookings WHERE tourist_id=? AND status='confirmed'");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $uid);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $c_confirmed);
    mysqli_stmt_fetch($stmt);
    $counts['confirmed'] = (int)$c_confirmed;
    mysqli_stmt_close($stmt);
}

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM bookings WHERE tourist_id=?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $uid);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $c_total);
    mysqli_stmt_fetch($stmt);
    $counts['total'] = (int)$c_total;
    mysqli_stmt_close($stmt);
}

$recent = [];
$stmt = mysqli_prepare($conn, "
    SELECT b.booking_id, b.visit_date, b.number_of_people, b.status, b.created_at, p.place_name
    FROM bookings b
    JOIN places p ON b.place_id = p.place_id
    WHERE b.tourist_id = ?
    ORDER BY b.created_at DESC
    LIMIT 5
");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $uid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $recent[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
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
  <title>My Dashboard - 7 Lakes Escapes</title>
  <link rel="icon" type="image/png" href="../images/logo/logo.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="tourist.css" />
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
        <?php if (!empty($_SESSION['profile_photo'])): ?>
          <img src="../<?= htmlspecialchars($_SESSION['profile_photo']) ?>" style="width:100%;height:100%;object-fit:cover;" alt="" />
        <?php else: ?>
          <?= strtoupper(substr($_SESSION['username'] ?? 'T', 0, 1)) ?>
        <?php endif; ?>
      </div>
      <span><?= htmlspecialchars($_SESSION['username'] ?? '') ?></span>
    </a>
    <a href="logout.php" class="t-nav-logout">Sign Out</a>
  </div>
</nav>

<div class="t-page-wide">

  <div class="t-welcome">
    <div class="t-welcome-text">
      <h2>Mabuhay, <?= htmlspecialchars($_SESSION['username'] ?? 'Traveler') ?>!</h2>
      <p>Plan your next adventure in San Pablo City, Laguna.</p>
    </div>
    <a href="book.php" class="t-btn" style="background:#fff;color:var(--green);font-weight:600;flex-shrink:0;">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Book a Visit
    </a>
  </div>

  <!-- Stats -->
  <div class="t-stats">
    <div class="t-stat">
      <div class="t-stat-val"><?= $counts['total'] ?></div>
      <div class="t-stat-label">Total Bookings</div>
    </div>
    <div class="t-stat">
      <div class="t-stat-val"><?= $counts['pending'] ?></div>
      <div class="t-stat-label">Pending</div>
    </div>
    <div class="t-stat">
      <div class="t-stat-val"><?= $counts['confirmed'] ?></div>
      <div class="t-stat-label">Confirmed</div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;">

    <!-- Recent bookings -->
    <div class="t-card">
      <div class="t-card-header">
        <span class="t-card-title">Recent Bookings</span>
        <a href="my-bookings.php" class="t-btn t-btn-outline t-btn-sm">View All</a>
      </div>
      <div class="t-card-body" style="padding:0;">
        <?php if ($recent): ?>
        <div class="t-table-wrap">
          <table class="t-table">
            <thead><tr><th>Place</th><th>Visit Date</th><th>People</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($recent as $b): ?>
              <tr>
                <td><strong><?= htmlspecialchars($b['place_name']) ?></strong></td>
                <td><?= htmlspecialchars($b['visit_date']) ?></td>
                <td style="text-align:center"><?= (int)$b['number_of_people'] ?></td>
                <td>
                  <?php $s = strtolower($b['status']); ?>
                  <span class="t-badge t-badge-<?= $s ?>"><?= htmlspecialchars($b['status']) ?></span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div class="t-empty">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          <p>No bookings yet.</p>
          <a href="book.php" class="t-btn t-btn-primary t-btn-sm" style="margin-top:14px;">Book a Place</a>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Quick book -->
    <div class="t-card">
      <div class="t-card-header">
        <span class="t-card-title">Quick Book</span>
      </div>
      <div class="t-card-body">
        <form method="GET" action="book.php">
          <div class="t-form">
            <div class="t-form-group">
              <label class="t-label">Select a Place</label>
              <select name="place_id" class="t-select" required>
                <option value=��>&mdash; Choose destination &mdash;</option>
                <?php foreach ($places as $pl): ?>
                  <option value="<?= $pl['place_id'] ?>"><?= htmlspecialchars($pl['place_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <button type="submit" class="t-btn t-btn-primary" style="width:100%;justify-content:center;">
              Continue to Booking
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>


</body>
</html>

