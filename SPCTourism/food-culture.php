<?php
session_start();
require_once 'conn.php';

$foods = [];
$cultures = [];

$food_query = "
    SELECT f.*, (SELECT ph.file_path FROM photos ph WHERE ph.food_id = f.food_id LIMIT 1) AS photo
    FROM foods f ORDER BY f.food_id
";
$food_res = mysqli_query($conn, $food_query);
if ($food_res) {
    while ($row = mysqli_fetch_assoc($food_res)) {
        $foods[] = $row;
    }
}

$culture_query = "
    SELECT c.*, (SELECT ph.file_path FROM photos ph WHERE ph.culture_id = c.culture_id LIMIT 1) AS photo
    FROM culture c ORDER BY c.culture_id
";
$culture_res = mysqli_query($conn, $culture_query);
if ($culture_res) {
    while ($row = mysqli_fetch_assoc($culture_res)) {
        $cultures[] = $row;
    }
}

$spotlight  = !empty($foods) ? $foods[0] : null;
$food_grid  = array_slice($foods, 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Food &amp; Culture — 7 Lakes Escapes</title>
  <link rel="icon" type="image/png" href="images/logo/logo.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="food-culture.css" />
  
</head>
<body>

<!-- ── NAVBAR ───────────────────────────────────────────────── -->
<nav class="navbar">
  <a href="index.php" class="nav-logo">
    <img src="images/logo/logo.png" alt="7 Lakes Escapes" />
  </a>
  <ul class="nav-links">
    <li><a href="index.php">Home</a></li>
    <li><a href="attractions.php">Attractions</a></li>
    <li><a href="food-culture.php" class="active">Food &amp; Culture</a></li>
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
    <p class="banner-eyebrow">A Taste of San Pablo City</p>
    <div class="rule">
      <span class="rule-line"></span>
      <span class="rule-diamond"></span>
      <span class="rule-line"></span>
    </div>
    <h1 class="banner-title">Food &amp; <em>Culture</em></h1>
    <p class="banner-sub">Flavors &nbsp;&middot;&nbsp; Traditions &nbsp;&middot;&nbsp; Heritage</p>
  </div>

  <nav class="tab-nav" role="tablist">
    <button class="tab-nav-btn active" data-tab="food" role="tab" aria-selected="true">
      <span class="tab-icon">&#9670;</span> Culinary
    </button>
    <button class="tab-nav-btn" data-tab="culture" role="tab" aria-selected="false">
      <span class="tab-icon">&#9670;</span> Culture
    </button>
    <button class="tab-nav-btn" data-tab="heritage" role="tab" aria-selected="false">
      <span class="tab-icon">&#9670;</span> Heritage
    </button>
  </nav>

</div><!-- /page-banner -->

<!-- ── CONTENT AREA ─────────────────────────────────────────── -->
<div class="page-content">

  <!-- ── TAB: FOOD ─────────────────────────────────────────── -->
  <div class="tab-panel active" id="tab-food" role="tabpanel">

    <div class="section-header">
      <p class="section-eyebrow">A Taste of San Pablo</p>
      <h2 class="section-title">Culinary <em>Highlights</em></h2>
      <p class="section-sub">From signature pastries to age-old local dishes — flavors worth traveling for.</p>
    </div>

    <?php if ($spotlight): ?>
    <!-- Spotlight: first food entry -->
    <div class="food-spotlight">
      <div class="spotlight-img">
        <?php if ($spotlight['photo']): ?>
          <img src="<?= htmlspecialchars($spotlight['photo']) ?>" alt="<?= htmlspecialchars($spotlight['food_name']) ?>" />
        <?php else: ?>
          <div style="width:100%;height:100%;background:linear-gradient(160deg,#1e3c0d,#4a7c30);display:flex;align-items:center;justify-content:center;border-radius:8px;">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.3)" stroke-width="1"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/></svg>
          </div>
        <?php endif; ?>
        <span class="spotlight-badge">Must Try</span>
      </div>
      <div class="spotlight-body">
        <p class="spotlight-label"><?= htmlspecialchars($spotlight['restaurant_name'] ?? 'Local Specialty') ?></p>
        <div class="spotlight-rule">
          <span></span>
          <span class="spotlight-rule-dot"></span>
          <span></span>
        </div>
        <h3 class="spotlight-name"><?= htmlspecialchars($spotlight['food_name']) ?></h3>
        <?php if ($spotlight['description']): ?>
          <p class="spotlight-desc"><?= htmlspecialchars($spotlight['description']) ?></p>
        <?php endif; ?>
        <?php if ($spotlight['price_range']): ?>
          <p style="margin-top:12px;font-size:0.82rem;color: #DBECDC;">Price range: <?= htmlspecialchars($spotlight['price_range']) ?></p>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Food grid -->
    <?php if ($food_grid): ?>
    <div class="food-grid">
      <?php foreach ($food_grid as $f): ?>
      <div class="food-card">
        <?php if ($f['photo']): ?>
          <div class="food-card-img" style="background-image: url('<?= htmlspecialchars($f['photo']) ?>');"></div>
        <?php else: ?>
          <div class="food-card-img" style="background: linear-gradient(160deg, #1e3c0d 0%, #4a7c30 100%);"></div>
        <?php endif; ?>
        <div class="food-card-body">
          <h3 class="food-card-name"><?= htmlspecialchars($f['food_name']) ?></h3>
          <?php if ($f['description']): ?>
            <p class="food-card-desc"><?= htmlspecialchars($f['description']) ?></p>
          <?php endif; ?>
          <span class="food-card-tag"><?= htmlspecialchars($f['price_range'] ?: ($f['restaurant_name'] ?: 'Local Specialty')) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php elseif (!$spotlight): ?>
    <div style="padding:60px 0;text-align:center;color:rgba(51,100,61,0.5);">
      <p>No food items listed yet. Check back soon.<?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'tourist'): ?> <a href="admin/foods.php">Add via admin panel.</a><?php endif; ?></p>
    </div>
    <?php endif; ?>

  </div><!-- /tab-food -->

  <!-- ── TAB: CULTURE ──────────────────────────────────────── -->
  <div class="tab-panel" id="tab-culture" role="tabpanel">

    <div class="section-header">
      <p class="section-eyebrow">Immersive Traditions</p>
      <h2 class="section-title">Cultural <em>Experiences</em></h2>
      <p class="section-sub">Living traditions passed down through generations — waiting to be shared with you.</p>
    </div>

    <?php if ($cultures): ?>
    <div class="culture-grid">
      <?php $ci = 1; foreach ($cultures as $c): ?>
      <?php
        if ($c['event_date']) {
            $label = date('M Y', strtotime($c['event_date']));
        } else {
            $label = 'Experience';
        }
      ?>
      <div class="culture-card">
        <?php if ($c['photo']): ?>
          <div class="culture-card-bg" style="background-image: url('<?= htmlspecialchars($c['photo']) ?>');"></div>
        <?php else: ?>
          <div class="culture-card-bg" style="background: linear-gradient(160deg, #0e2607 0%, #2d6020 100%);"></div>
        <?php endif; ?>
        <span class="culture-num"><?= str_pad($ci, 2, '0', STR_PAD_LEFT) ?></span>
        <div class="culture-card-overlay">
          <span class="culture-label"><?= htmlspecialchars($label) ?></span>
          <h3 class="culture-name"><?= htmlspecialchars($c['culture_name']) ?></h3>
          <?php if ($c['description']): ?>
            <p class="culture-desc"><?= htmlspecialchars($c['description']) ?></p>
          <?php endif; ?>
          <?php if ($c['location']): ?>
            <p style="margin-top:8px;font-size:0.75rem;color:var(--cream);"><?= htmlspecialchars($c['location']) ?></p>
          <?php endif; ?>
        </div>
      </div>
      <?php $ci++; endforeach; ?>
    </div>
    <?php else: ?>
    <div style="padding:60px 0;text-align:center;color:rgba(51,100,61,0.5);">
      <p>No cultural experiences listed yet. Check back soon.<?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'tourist'): ?> <a href="admin/culture.php">Add via admin panel.</a><?php endif; ?></p>
    </div>
    <?php endif; ?>

  </div><!-- /tab-culture -->

  <!-- ── TAB: HERITAGE (static editorial) ─────────────────── -->
  <div class="tab-panel heritage-panel" id="tab-heritage" role="tabpanel">

    <div class="heritage-hero">
      <div class="heritage-inner">
        <span class="heritage-badge">Kultura at Lasa</span>
        <h2 class="heritage-title">Bring a piece of <em>San Pablo</em> home</h2>
        <p class="heritage-desc">Beyond the flavors and the sights, the heart of our city lies in the warmth of our people. Whether it's a box of Uraro cookies or a handmade craft, don't leave without a souvenir of our heritage.</p>
      </div>
    </div>

    <div class="action-cards">

      <div class="action-card">
        <span class="action-num">01</span>
        <h4 class="action-name">Market Day</h4>
        <p class="action-desc">Visit the local wet market for the freshest seasonal ingredients, native produce, and a glimpse into daily life in San Pablo City.</p>
      </div>

      <div class="action-card">
        <span class="action-num">02</span>
        <h4 class="action-name">Festivals</h4>
        <p class="action-desc">Plan your visit around the Coco Festival in January or the Foundation Day celebration in May for an unforgettable cultural immersion.</p>
      </div>

      <div class="action-card">
        <span class="action-num">03</span>
        <h4 class="action-name">Pasalubong</h4>
        <p class="action-desc">Support local artisans and home-based bakeries. Take home Buko Pie, Uraro cookies, or handcrafted coconut products as gifts.</p>
      </div>

    </div>

    <div class="heritage-cta">
      <a href="attractions.php" class="btn-primary">Explore Attractions</a>
      <p class="cta-sub">Discover the lakes, landmarks, and hidden gems of San Pablo City</p>
    </div>

  </div><!-- /tab-heritage -->

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
<script>
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
</script>


</body>
</html>

