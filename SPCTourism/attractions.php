<?php
session_start();
require_once 'conn.php';

$all_places = [];
$events = [];

$places_query = "
    SELECT p.*, c.category_name,
           (SELECT ph.file_path FROM photos ph WHERE ph.place_id = p.place_id LIMIT 1) AS photo
    FROM places p LEFT JOIN categories c ON p.category_id = c.category_id
    ORDER BY p.place_id
";
$places_res = mysqli_query($conn, $places_query);
if ($places_res) {
    while ($row = mysqli_fetch_assoc($places_res)) {
        $all_places[] = $row;
    }
}

$events_query = "
    SELECT cu.*, (SELECT ph.file_path FROM photos ph WHERE ph.culture_id = cu.culture_id LIMIT 1) AS photo
    FROM culture cu
    WHERE cu.event_date IS NOT NULL
    ORDER BY cu.event_date
";
$events_res = mysqli_query($conn, $events_query);
if ($events_res) {
    while ($row = mysqli_fetch_assoc($events_res)) {
        $events[] = $row;
    }
}

$lakes = $featured = $discover = [];
foreach ($all_places as $p) {
    $cat = strtolower($p['category_name'] ?? '');
    if (strpos($cat, 'lake') !== false) {
        $lakes[] = $p;
    } elseif (strpos($cat, 'attraction') !== false || strpos($cat, 'landmark') !== false
           || strpos($cat, 'heritage') !== false  || strpos($cat, 'featured') !== false) {
        $featured[] = $p;
    } else {
        $discover[] = $p;
    }
}

// Fallback: if no category matched, split by position
if (empty($lakes) && !empty($all_places)) {
    $lakes    = array_slice($all_places, 0, min(7, count($all_places)));
    $rest     = array_slice($all_places, count($lakes));
    $featured = array_slice($rest, 0, min(3, count($rest)));
    $discover = array_slice($rest, 3);
}

// Static lake image fallbacks (used when no photo uploaded yet)
$lake_fallbacks = [
    1 => 'images/lakes/Sampaloc.jpg',
    2 => 'images/lakes/Bunot.png',
    3 => 'images/lakes/Calibato.jpg',
    4 => 'images/lakes/Pandin.jpg',
    5 => 'images/lakes/Yambo.png',
    6 => 'images/lakes/Mohicap.png',
    7 => 'images/lakes/Palakpakin.jpg',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Attractions — 7 Lakes Escapes</title>
  <link rel="icon" type="image/png" href="images/logo/logo.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="attractions.css" />
  
</head>
<body>

<!-- ── NAVBAR ───────────────────────────────────────────────── -->
<nav class="navbar">
  <a href="index.php" class="nav-logo">
    <img src="images/logo/logo.png" alt="7 Lakes Escapes" />
  </a>
  <ul class="nav-links">
    <li><a href="index.php">Home</a></li>
    <li><a href="attractions.php" class="active">Attractions</a></li>
    <li><a href="food-culture.php">Food &amp; Culture</a></li>
    <li><a href="plan.php">Plan Your Visit</a></li>
  </ul>
  <div style="display:flex;align-items:center;gap:10px;">
    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'tourist'): ?>
      <div class="nav-user-menu">
        <a href="tourist/dashboard.php" class="nav-cta">My Account <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></a>
        <div class="nav-dropdown">
          <a href="tourist/dashboard.php">My Dashboard</a>
          <a href="tourist/logout.php" class="nav-dropdown-signout">Sign Out</a>
        </div>
      </div>
    <?php elseif (isset($_SESSION['user_id'])): ?>
      <div class="nav-user-menu">
        <a href="admin/dashboard.php" class="nav-cta">Admin Panel <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></a>
        <div class="nav-dropdown">
          <a href="admin/dashboard.php">Dashboard</a>
          <a href="admin/logout.php" class="nav-dropdown-signout">Sign Out</a>
        </div>
      </div>
    <?php else: ?>
      <a href="login.php" class="nav-cta">Sign In</a>
    <?php endif; ?>
  </div>
</nav>

<!-- ── PAGE BANNER ─────────────────────────────────────────── -->
<div class="page-banner">

  <div class="geo-left">
    <svg width="140" height="500" viewBox="0 0 140 500" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <pattern id="weave-l" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
          <polygon points="20,0 40,20 20,40 0,20" fill="none" stroke="#6DA170" stroke-width="0.8"/>
          <polygon points="20,8 32,20 20,32 8,20" fill="none" stroke="#8CC48B" stroke-width="0.5"/>
          <circle cx="20" cy="20" r="3" fill="#6DA170" opacity="0.6"/>
          <line x1="0" y1="0" x2="40" y2="40" stroke="#3A8A3C" stroke-width="0.4" opacity="0.5"/>
          <line x1="40" y1="0" x2="0" y2="40" stroke="#3A8A3C" stroke-width="0.4" opacity="0.5"/>
        </pattern>
      </defs>
      <rect width="140" height="500" fill="url(#weave-l)" opacity="0.7"/>
    </svg>
  </div>

  <div class="geo-right">
    <svg width="140" height="500" viewBox="0 0 140 500" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <pattern id="weave-r" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
          <polygon points="20,0 40,20 20,40 0,20" fill="none" stroke="#6DA170" stroke-width="0.8"/>
          <polygon points="20,8 32,20 20,32 8,20" fill="none" stroke="#8CC48B" stroke-width="0.5"/>
          <circle cx="20" cy="20" r="3" fill="#6DA170" opacity="0.6"/>
          <line x1="0" y1="0" x2="40" y2="40" stroke="#3A8A3C" stroke-width="0.4" opacity="0.5"/>
          <line x1="40" y1="0" x2="0" y2="40" stroke="#3A8A3C" stroke-width="0.4" opacity="0.5"/>
        </pattern>
      </defs>
      <rect width="140" height="500" fill="url(#weave-r)" opacity="0.7"/>
    </svg>
  </div>

  <div class="banner-content">
    <p class="banner-eyebrow">San Pablo City, Laguna</p>
    <div class="rule">
      <span class="rule-line"></span>
      <span class="rule-diamond"></span>
      <span class="rule-line"></span>
    </div>
    <h1 class="banner-title">Explore <em>Attractions</em></h1>
    <p class="banner-sub">Lakes &middot;Landmarks &middot;Events &middot;Hidden Gems</p>
  </div>

  <nav class="tab-nav" role="tablist">
    <button class="tab-nav-btn active" data-tab="lakes" role="tab" aria-selected="true">
      Seven Lakes <span class="tab-count"><?= count($lakes) ?></span>
    </button>
    <button class="tab-nav-btn" data-tab="featured" role="tab" aria-selected="false">
      Featured <span class="tab-count"><?= count($featured) ?></span>
    </button>
    <button class="tab-nav-btn" data-tab="events" role="tab" aria-selected="false">
      Events <span class="tab-count"><?= count($events) ?></span>
    </button>
    <button class="tab-nav-btn" data-tab="discover" role="tab" aria-selected="false">
      Discover <span class="tab-count"><?= count($discover) ?></span>
    </button>
  </nav>

</div><!-- /page-banner -->

<!-- ── CONTENT AREA ─────────────────────────────────────────── -->
<div class="page-content">

  <!-- ── TAB: SEVEN LAKES ──────────────────────────────────── -->
  <div class="tab-panel active" id="tab-lakes" role="tabpanel">

    <div class="section-header">
      <p class="section-eyebrow">The City of Seven Lakes</p>
      <h2 class="section-title">San Pablo's <em>Seven Lakes</em></h2>
      <p class="section-sub">Seven crater lakes nestled within the city — each with its own character and charm.</p>
    </div>

    <?php if ($lakes): ?>
    <div class="lakes-grid">
      <?php $i = 1; foreach ($lakes as $lake): ?>
      <?php $img = $lake['photo'] ?: ($lake_fallbacks[$lake['place_id']] ?? null); ?>
      <div class="lake-card">
        <?php if ($img): ?>
          <div class="lake-card-bg" style="background-image: url('<?= htmlspecialchars($img) ?>');"></div>
        <?php else: ?>
          <div class="lake-card-bg" style="background: linear-gradient(160deg, #1e3c0d 0%, #33643d 100%);"></div>
        <?php endif; ?>
        <span class="lake-num"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></span>
        <div class="lake-card-overlay">
          <span class="lake-label"><?= htmlspecialchars($lake['category_name'] ?? '7 Lakes') ?></span>
          <h3 class="lake-name"><?= htmlspecialchars($lake['place_name']) ?></h3>
          <?php if ($lake['description']): ?>
            <p class="lake-desc"><?= htmlspecialchars($lake['description']) ?></p>
          <?php endif; ?>
          <a href="tourist/book.php?place_id=<?= $lake['place_id'] ?>" class="lake-book-btn">Book a Visit</a>
        </div>
      </div>
      <?php $i++; endforeach; ?>
    </div>
    <?php else: ?>
    <div style="padding:60px 0;text-align:center;color:rgba(255,255,255,0.5);">
      <p style="font-size:1rem;">No lakes added yet.<?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'tourist'): ?> <a href="admin/places.php" style="color:#8cc48b;">Add via admin panel.</a><?php endif; ?></p>
    </div>
    <?php endif; ?>

  </div><!-- /tab-lakes -->

  <!-- ── TAB: FEATURED ─────────────────────────────────────── -->
  <div class="tab-panel" id="tab-featured" role="tabpanel">

    <div class="section-header">
      <p class="section-eyebrow">Must-Visit Spots</p>
      <h2 class="section-title">Featured <em>Attractions</em></h2>
      <p class="section-sub">The landmarks and destinations that define San Pablo City.</p>
    </div>

    <?php if ($featured): ?>
    <?php $feat_first = $featured[0]; $feat_rest = array_slice($featured, 1); ?>
    <div class="featured-grid">

      <div class="feat-card feat-card-large">
        <?php if ($feat_first['photo']): ?>
          <div class="feat-card-bg" style="background-image: url('<?= htmlspecialchars($feat_first['photo']) ?>');"></div>
        <?php else: ?>
          <div class="feat-card-bg" style="background: linear-gradient(160deg, #1a3a0a 0%, #2d6020 100%);"></div>
        <?php endif; ?>
        <div class="feat-card-overlay">
          <span class="feat-tag"><?= htmlspecialchars($feat_first['category_name'] ?? 'Top Attraction') ?></span>
          <h3 class="feat-name"><?= htmlspecialchars($feat_first['place_name']) ?></h3>
          <?php if ($feat_first['description']): ?>
            <p class="feat-desc"><?= htmlspecialchars($feat_first['description']) ?></p>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($feat_rest): ?>
      <div class="feat-sm-col">
        <?php foreach ($feat_rest as $fp): ?>
        <div class="feat-card feat-card-sm">
          <?php if ($fp['photo']): ?>
            <div class="feat-card-bg" style="background-image: url('<?= htmlspecialchars($fp['photo']) ?>');"></div>
          <?php else: ?>
            <div class="feat-card-bg" style="background: linear-gradient(160deg, #1e3c0d 0%, #33643d 100%);"></div>
          <?php endif; ?>
          <div class="feat-card-overlay">
            <span class="feat-tag"><?= htmlspecialchars($fp['category_name'] ?? 'Attraction') ?></span>
            <h3 class="feat-name"><?= htmlspecialchars($fp['place_name']) ?></h3>
            <?php if ($fp['description']): ?>
              <p class="feat-desc"><?= htmlspecialchars(mb_substr($fp['description'], 0, 90)) ?><?= mb_strlen($fp['description']) > 90 ? '…' : '' ?></p>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </div>
    <?php else: ?>
    <div style="padding:60px 0;text-align:center;color:rgba(51,100,61,0.5);">
      <p>No featured attractions added yet.<?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'tourist'): ?> <a href="admin/places.php">Add via admin panel.</a><?php endif; ?></p>
    </div>
    <?php endif; ?>

  </div><!-- /tab-featured -->

  <!-- ── TAB: EVENTS ───────────────────────────────────────── -->
  <div class="tab-panel" id="tab-events" role="tabpanel">

    <div class="section-header">
      <p class="section-eyebrow">Mark Your Calendar</p>
      <h2 class="section-title">Upcoming <em>Events</em></h2>
      <p class="section-sub">Festivals, celebrations, and gatherings throughout the year.</p>
    </div>

    <?php if ($events): ?>
    <div class="events-list">
      <?php foreach ($events as $ev): ?>
      <?php
        $day   = $ev['event_date'] ? date('j', strtotime($ev['event_date'])) : 'TBA';
        $month = $ev['event_date'] ? date('M', strtotime($ev['event_date'])) : '';
      ?>
      <div class="event-card">
        <?php if ($ev['photo']): ?>
          <div class="event-thumb" style="background-image: url('<?= htmlspecialchars($ev['photo']) ?>');"></div>
        <?php else: ?>
          <div class="event-thumb" style="background: linear-gradient(135deg, #1e3c0d 0%, #4a7c30 100%);"></div>
        <?php endif; ?>
        <div class="event-date">
          <span class="date-day"><?= htmlspecialchars($day) ?></span>
          <?php if ($month): ?><span class="date-month"><?= $month ?></span><?php endif; ?>
        </div>
        <div class="event-info">
          <h3 class="event-title"><?= htmlspecialchars($ev['culture_name']) ?></h3>
          <?php if ($ev['location']): ?>
            <p class="event-location"><?= htmlspecialchars($ev['location']) ?></p>
          <?php endif; ?>
          <?php if ($ev['description']): ?>
            <p class="event-desc"><?= htmlspecialchars($ev['description']) ?></p>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="padding:60px 0;text-align:center;color:rgba(51,100,61,0.5);">
      <p>No upcoming events at the moment. Check back soon.<?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'tourist'): ?> <a href="admin/culture.php">Add via admin panel.</a><?php endif; ?></p>
    </div>
    <?php endif; ?>

  </div><!-- /tab-events -->

  <!-- ── TAB: DISCOVER ─────────────────────────────────────── -->
  <div class="tab-panel" id="tab-discover" role="tabpanel">

    <div class="section-header">
      <p class="section-eyebrow">Beyond the Lakes</p>
      <h2 class="section-title">More to <em>Discover</em></h2>
      <p class="section-sub">Cafés, heritage houses, and hidden spots worth exploring in the city.</p>
    </div>

    <?php if ($discover): ?>
    <div class="discover-grid">
      <?php foreach ($discover as $d): ?>
      <div class="discover-card">
        <?php if ($d['photo']): ?>
          <div class="discover-img" style="background-image: url('<?= htmlspecialchars($d['photo']) ?>');"></div>
        <?php else: ?>
          <div class="discover-img" style="background: linear-gradient(160deg, #1e3c0d 0%, #33643d 100%);"></div>
        <?php endif; ?>
        <div class="discover-body">
          <h3 class="discover-name"><?= htmlspecialchars($d['place_name']) ?></h3>
          <?php if ($d['description']): ?>
            <p class="discover-desc"><?= htmlspecialchars($d['description']) ?></p>
          <?php endif; ?>
          <span class="discover-tag"><?= htmlspecialchars($d['category_name'] ?? 'Destination') ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="padding:60px 0;text-align:center;color:rgba(51,100,61,0.5);">
      <p>More destinations coming soon.<?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'tourist'): ?> <a href="admin/places.php">Add via admin panel.</a><?php endif; ?></p>
    </div>
    <?php endif; ?>

  </div><!-- /tab-discover -->

</div><!-- /page-content -->

<!-- ── FOOTER ──────────────────────────────────────────────── -->
<footer class="main-footer">
  <div class="footer-inner">

    <div class="footer-brand">
      <p class="footer-brand-name">7 Lakes Escapes</p>
      <p class="footer-brand-sub">San Pablo City Tourism</p>
      <p class="footer-desc">Explore the City of Seven Lakes. Experience our rich culture, breathtaking scenery, and warm Filipino hospitality.</p>
      <div class="footer-social">
        <a href="https://www.facebook.com/TOURISMSANPABLOOFFICIALPAGE" target="_blank" rel="noopener">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
          Facebook
        </a>
      </div>
    </div>

    <div class="footer-contact">
      <p class="footer-contact-title">Contact Us</p>
      <ul>
        <li><strong>Address:</strong> City Hall Compound, San Pablo City, Laguna</li>
        <li><strong>Email:</strong> tourism@sanpablocity.gov.ph</li>
        <li><strong>Phone:</strong> (049) 562-1234</li>
        <li><strong>Hours:</strong> Mon – Fri &nbsp;8:00 AM – 5:00 PM</li>
      </ul>
    </div>

  </div>
  <div class="footer-bottom">
    &copy; 2026 Janssen Miranda &nbsp;&middot;&nbsp; All Rights Reserved
  </div>
</footer>

<!-- ── TAB SCRIPT ───────────────────────────────────────────── -->
<script>document.addEventListener('DOMContentLoaded', function() {
  const btns   = document.querySelectorAll('.tab-nav-btn');
  const panels = document.querySelectorAll('.tab-panel');

  btns.forEach(btn => {
    btn.addEventListener('click', () => {
      const target = btn.dataset.tab;
      btns.forEach(b => { b.classList.remove('active'); b.setAttribute('aria-selected', 'false'); });
      panels.forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      btn.setAttribute('aria-selected', 'true');
      document.getElementById('tab-' + target).classList.add('active');
    });
  });
});</script>


</body>
</html>

