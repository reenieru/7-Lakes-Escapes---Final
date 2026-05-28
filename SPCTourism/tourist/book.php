<?php
require_once 'includes/auth.php';
require_once '../conn.php';

$uid     = (int)$_SESSION['user_id'];
$message = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $place_id  = (int)($_POST['place_id'] ?? 0);
    $guide_id  = !empty($_POST['guide_id']) ? (int)$_POST['guide_id'] : null;
    $visit_date = trim($_POST['visit_date'] ?? '');
    $people    = max(1, (int)($_POST['number_of_people'] ?? 1));
    $notes     = trim($_POST['notes'] ?? '');

    if (!$place_id) {
        $error = 'Please select a destination.';
    } elseif (!$visit_date || $visit_date < date('Y-m-d')) {
        $error = 'Please choose a valid future date.';
    } else {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO bookings (tourist_id, guide_id, place_id, visit_date, number_of_people, status)
            VALUES (?, ?, ?, ?, ?, 'Pending')
        ");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'iiisi', $uid, $guide_id, $place_id, $visit_date, $people);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        header('Location: my-bookings.php?booked=1');
        exit;
    }
}

$places = [];
$res = mysqli_query($conn, "SELECT place_id, place_name FROM places ORDER BY place_name");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $places[] = $row;
    }
}

$guides = [];
$res = mysqli_query($conn, "SELECT guide_id, CONCAT(first_name,' ',last_name) AS guide_name, status FROM tour_guides WHERE status='Available' ORDER BY last_name");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $guides[] = $row;
    }
}

$preselect = (int)($_GET['place_id'] ?? $_POST['place_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Book a Visit — 7 Lakes Escapes</title>
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

<div class="t-page">

  <div class="t-header">
    <div class="t-header-eyebrow">Plan Your Trip</div>
    <h1>Book a <em style="font-style:italic;color:var(--green-mid)">Visit</em></h1>
    <p>Choose your destination, pick a date, and we'll handle the rest.</p>
  </div>

  <?php if ($error): ?>
    <div class="t-alert t-alert-error">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 320px;gap:28px;align-items:start;">

    <div class="t-card">
      <div class="t-card-header">
        <span class="t-card-title">Booking Details</span>
      </div>
      <div class="t-card-body">
        <form method="POST" class="t-form">
          <div class="t-form-grid">
            <div class="t-form-group full">
              <label class="t-label">Destination *</label>
              <select name="place_id" class="t-select" required>
                <option value="">— Select a place —</option>
                <?php foreach ($places as $pl): ?>
                  <option value="<?= $pl['place_id'] ?>" <?= $preselect === (int)$pl['place_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($pl['place_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="t-form-group">
              <label class="t-label">Visit Date *</label>
              <input type="date" name="visit_date" class="t-input" min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                     value="<?= htmlspecialchars($_POST['visit_date'] ?? '') ?>" required />
            </div>
            <div class="t-form-group">
              <label class="t-label">Number of People *</label>
              <input type="number" name="number_of_people" class="t-input" min="1" max="50"
                     value="<?= (int)($_POST['number_of_people'] ?? 1) ?>" required />
            </div>
            <?php if ($guides): ?>
            <div class="t-form-group full">
              <label class="t-label">Tour Guide <span style="color:var(--text-muted);font-weight:400;text-transform:none">(optional)</span></label>
              <select name="guide_id" class="t-select">
                <option value="">— No guide needed —</option>
                <?php foreach ($guides as $g): ?>
                  <option value="<?= $g['guide_id'] ?>"><?= htmlspecialchars($g['guide_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php endif; ?>
            <div class="t-form-group full">
              <label class="t-label">Notes / Special Requests</label>
              <textarea name="notes" class="t-textarea" placeholder="Any special requirements, accessibility needs, or questions..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
            </div>
          </div>
          <div style="display:flex;gap:10px;margin-top:4px;">
            <button type="submit" class="t-btn t-btn-primary">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
              Confirm Booking
            </button>
            <a href="dashboard.php" class="t-btn t-btn-outline">Cancel</a>
          </div>
        </form>
      </div>
    </div>

    <!-- Info panel -->
    <div style="display:flex;flex-direction:column;gap:16px;">
      <div class="t-card">
        <div class="t-card-body">
          <p style="font-family:'Cormorant Garamond',serif;font-size:1.05rem;font-weight:600;color:var(--green);margin-bottom:10px;">How it works</p>
          <ol style="padding-left:18px;display:flex;flex-direction:column;gap:8px;font-size:0.85rem;color:var(--text-muted);line-height:1.5;">
            <li>Select a destination and preferred date.</li>
            <li>Submit your booking request.</li>
            <li>Our team will confirm within 24 hours.</li>
            <li>You'll find your confirmed booking in <a href="my-bookings.php" style="color:var(--green)">My Bookings</a>.</li>
          </ol>
        </div>
      </div>
      <div class="t-card">
        <div class="t-card-body" style="font-size:0.82rem;color:var(--text-muted);line-height:1.6;">
          <p style="color:var(--green);font-weight:500;margin-bottom:6px;">Important Notes</p>
          <ul style="padding-left:16px;display:flex;flex-direction:column;gap:4px;">
            <li>Bookings must be made at least 1 day in advance.</li>
            <li>Maximum group size is 50 people.</li>
            <li>You can cancel pending bookings anytime.</li>
          </ul>
        </div>
      </div>
    </div>

  </div>
</div>


</body>
</html>

