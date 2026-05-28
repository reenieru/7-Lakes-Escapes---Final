<?php
require_once 'includes/auth.php';
require_once '../conn.php';

$uid     = (int)$_SESSION['user_id'];
$message = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $bid = (int)$_POST['booking_id'];
    $stmt = mysqli_prepare($conn, "SELECT status FROM bookings WHERE booking_id=? AND tourist_id=?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ii', $bid, $uid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $status);
        $fetched = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if ($fetched && in_array(strtolower($status), ['pending', 'confirmed'])) {
            $stmt_up = mysqli_prepare($conn, "UPDATE bookings SET status='cancelled' WHERE booking_id=? AND tourist_id=?");
            if ($stmt_up) {
                mysqli_stmt_bind_param($stmt_up, 'ii', $bid, $uid);
                mysqli_stmt_execute($stmt_up);
                mysqli_stmt_close($stmt_up);
                $message = 'Booking cancelled successfully.';
            }
        } else {
            $error = 'This booking cannot be cancelled.';
        }
    }
}

$booked = isset($_GET['booked']);

$filter = $_GET['filter'] ?? 'all';
$where_map = [
    'pending'   => "AND LOWER(b.status)='pending'",
    'confirmed' => "AND LOWER(b.status)='confirmed'",
    'cancelled' => "AND LOWER(b.status)='cancelled'",
];
$where_extra = $where_map[$filter] ?? '';

$bookings = [];
$stmt = mysqli_prepare($conn, "
    SELECT b.booking_id, b.visit_date, b.number_of_people, b.status, b.created_at,
           p.place_name, p.location,
           CONCAT(tg.first_name,' ',tg.last_name) AS guide_name
    FROM bookings b
    JOIN places p ON b.place_id = p.place_id
    LEFT JOIN tour_guides tg ON b.guide_id = tg.guide_id
    WHERE b.tourist_id = ? $where_extra
    ORDER BY b.visit_date DESC
");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $uid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $bookings[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Bookings — 7 Lakes Escapes</title>
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

  <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;">
    <div class="t-header" style="margin-bottom:0;">
      <div class="t-header-eyebrow">Your Trips</div>
      <h1>My <em style="font-style:italic;color:var(--green-mid)">Bookings</em></h1>
    </div>
    <a href="book.php" class="t-btn t-btn-primary">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      New Booking
    </a>
  </div>

  <?php if ($booked): ?>
    <div class="t-alert t-alert-success">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      Booking submitted! We'll confirm it within 24 hours.
    </div>
  <?php endif; ?>
  <?php if ($message): ?>
    <div class="t-alert t-alert-success"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="t-alert t-alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Filter tabs -->
  <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
    <?php foreach (['all' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled'] as $k => $v): ?>
      <a href="my-bookings.php?filter=<?= $k ?>" class="t-btn t-btn-sm <?= $filter === $k ? 't-btn-primary' : 't-btn-outline' ?>"><?= $v ?></a>
    <?php endforeach; ?>
  </div>

  <div class="t-card">
    <div class="t-card-header">
      <span class="t-card-title">Bookings (<?= count($bookings) ?>)</span>
    </div>
    <?php if ($bookings): ?>
    <div class="t-table-wrap">
      <table class="t-table">
        <thead>
          <tr><th>Destination</th><th>Visit Date</th><th>People</th><th>Guide</th><th>Status</th><th>Booked On</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
          <tr>
            <td>
              <strong><?= htmlspecialchars($b['place_name']) ?></strong>
              <br><small style="color:var(--text-muted)"><?= htmlspecialchars($b['location'] ?? '') ?></small>
            </td>
            <td><?= htmlspecialchars($b['visit_date']) ?></td>
            <td style="text-align:center"><?= (int)$b['number_of_people'] ?></td>
            <td><?= $b['guide_name'] ? htmlspecialchars($b['guide_name']) : '<span style="color:var(--text-muted)">—</span>' ?></td>
            <td>
              <?php $s = strtolower($b['status']); ?>
              <span class="t-badge t-badge-<?= $s ?>"><?= htmlspecialchars($b['status']) ?></span>
            </td>
            <td style="font-size:0.78rem;color:var(--text-muted)"><?= substr($b['created_at'], 0, 10) ?></td>
            <td>
              <?php if (in_array(strtolower($b['status']), ['pending', 'confirmed'])): ?>
              <form method="POST" onsubmit="return confirm('Cancel this booking?')">
                <input type="hidden" name="action" value="cancel" />
                <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>" />
                <button type="submit" class="t-btn t-btn-danger t-btn-sm">Cancel</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="t-empty">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      <p>No bookings found.</p>
      <a href="book.php" class="t-btn t-btn-primary t-btn-sm" style="margin-top:14px;">Book a Place</a>
    </div>
    <?php endif; ?>
  </div>

</div>


</body>
</html>

