<?php
session_start();
require_once 'conn.php';

$guides = [];
$query = "
    SELECT g.*,
           (SELECT ph.file_path FROM photos ph WHERE ph.guide_id = g.guide_id LIMIT 1) AS photo
    FROM tour_guides g
    WHERE g.status = 'Available'
    ORDER BY g.last_name, g.first_name
";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $guides[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Plan Your Visit — 7 Lakes Escapes</title>
  <link rel="icon" type="image/png" href="images/logo/logo.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="plan.css" />
  
  <style>
    .guide-cards {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 24px;
      margin-top: 36px;
    }
    .guide-card {
      background: #fff;
      border-radius: 10px;
      padding: 28px 20px 22px;
      text-align: center;
      border: 1px solid rgba(51,100,61,0.12);
      box-shadow: 0 2px 12px rgba(0,0,0,0.05);
      transition: box-shadow 0.2s, transform 0.2s;
    }
    .guide-card:hover { box-shadow: 0 6px 24px rgba(51,100,61,0.12); transform: translateY(-2px); }
    .guide-avatar {
      width: 88px; height: 88px; border-radius: 50%;
      margin: 0 auto 16px;
      overflow: hidden;
      background: #33643d;
      display: flex; align-items: center; justify-content: center;
      border: 3px solid rgba(51,100,61,0.15);
    }
    .guide-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .guide-avatar span {
      color: #fff;
      font-size: 1.6rem;
      font-weight: 600;
      font-family: 'Cormorant Garamond', serif;
      line-height: 1;
    }
    .guide-name {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.15rem;
      font-weight: 700;
      color: #102F1F;
      margin-bottom: 6px;
    }
    .guide-contact {
      font-size: 0.8rem;
      color: rgba(51,100,61,0.6);
      margin-bottom: 6px;
      line-height: 1.5;
    }
    .guide-status-badge {
      display: inline-block;
      padding: 3px 12px;
      background: rgba(51,100,61,0.08);
      color: #33643d;
      border-radius: 20px;
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      margin-top: 4px;
    }
    .guide-book-btn {
      display: inline-block;
      margin-top: 14px;
      padding: 8px 20px;
      background: #33643d;
      color: #fff;
      border-radius: 4px;
      font-size: 0.78rem;
      font-weight: 500;
      font-family: 'Jost', sans-serif;
      text-decoration: none;
      transition: background 0.2s;
    }
    .guide-book-btn:hover { background: #25502f; }
    .guides-empty {
      padding: 60px 0;
      text-align: center;
      color: rgba(51,100,61,0.45);
    }
    .guides-empty p { font-size: 1rem; }
  </style>
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
    <li><a href="food-culture.php">Food &amp; Culture</a></li>
    <li><a href="plan.php" class="active">Plan Your Visit</a></li>
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
    <h1 class="banner-title">Plan Your <em>Visit</em></h1>
    <p class="banner-sub">Getting Here &nbsp;&middot;&nbsp; Getting Around &nbsp;&middot;&nbsp; Tour Guides</p>
  </div>

  <nav class="tab-nav" role="tablist">
    <button class="tab-nav-btn active" data-tab="getting-here" role="tab" aria-selected="true">
      &#9670; &nbsp;Getting Here
    </button>
    <button class="tab-nav-btn" data-tab="getting-around" role="tab" aria-selected="false">
      &#9670; &nbsp;Getting Around
    </button>
    <button class="tab-nav-btn" data-tab="tour-guides" role="tab" aria-selected="false">
      &#9670; &nbsp;Tour Guides <span class="tab-count"><?= count($guides) ?></span>
    </button>
  </nav>

</div><!-- /page-banner -->

<!-- ── CONTENT ──────────────────────────────────────────────── -->
<div class="page-content">

  <!-- ── TAB: GETTING HERE ─────────────────────────────────── -->
  <div class="tab-panel active" id="tab-getting-here" role="tabpanel">

    <div class="section-header">
      <p class="section-eyebrow">Travel Guide</p>
      <h2 class="section-title">How to Get to <em>San Pablo City</em></h2>
      <p class="section-sub">Select a transport option below to view the route and directions.</p>
    </div>

    <div class="transport-selector">

      <div class="transport-card" data-transport="bus">
        <div class="transport-icon-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <rect x="2" y="5" width="20" height="13" rx="2"/><path d="M17 18v2M7 18v2M2 10h20"/><circle cx="7" cy="15" r="1"/><circle cx="17" cy="15" r="1"/>
          </svg>
        </div>
        <h3 class="transport-name">By Bus</h3>
        <p class="transport-hint">Regular services from Manila and nearby provinces</p>
        <span class="transport-select-label">View Route &#8594;</span>
      </div>

      <div class="transport-card" data-transport="car">
        <div class="transport-icon-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M5 17H3a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h11l4 4v4a2 2 0 0 1-2 2h-2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/><path d="M9 9V7"/>
          </svg>
        </div>
        <h3 class="transport-name">Private Car</h3>
        <p class="transport-hint">Drive via SLEX for a comfortable, scenic journey</p>
        <span class="transport-select-label">View Route &#8594;</span>
      </div>

      <div class="transport-card" data-transport="jeepney">
        <div class="transport-icon-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <rect x="1" y="6" width="22" height="12" rx="2"/><path d="M1 10h22M5 18v2M19 18v2"/><circle cx="6" cy="14" r="1"/><circle cx="18" cy="14" r="1"/>
          </svg>
        </div>
        <h3 class="transport-name">Jeepney</h3>
        <p class="transport-hint">Budget-friendly option from nearby towns</p>
        <span class="transport-select-label">View Route &#8594;</span>
      </div>

    </div>

    <div class="map-panel" id="map-bus">
      <div class="map-panel-header">
        <h3 class="map-panel-title">Bus Route — Manila to San Pablo City</h3>
        <button class="map-close-btn" id="close-bus">&#8592; Close</button>
      </div>
      <div class="map-body">
        <div class="map-frame-wrap">
          <iframe src="https://www.google.com/maps/embed?pb=!1m28!1m12!1m3!1d247474.83260273664!2d121.07916947812499!3d14.229426600000007!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!4m13!3e3!4m5!1s0x3397c85e23a54d01%3A0x4cabc2e3bfc5219a!2sManila%2C%20Metro%20Manila!3m2!1d14.5995124!2d120.9842195!4m5!1s0x33bd5e8f1b8b3f1d%3A0x3d9a4d5c7b8f3e1a!2sSan%20Pablo%20City%2C%20Laguna!3m2!1d14.0682!2d121.3253!5e0!3m2!1sen!2sph!4v1234567890" allowfullscreen="" loading="lazy" title="Bus route map"></iframe>
        </div>
        <div class="map-directions">
          <h4 class="directions-title">Bus Directions</h4>
          <ul class="directions-list">
            <li>Head to any major bus terminal in Manila — Buendia, Cubao, or Alabang</li>
            <li>Look for buses bound for <strong>San Pablo City</strong> or Lucena</li>
            <li><strong>Bus companies:</strong> JAC Liner, DLTB, or Raymond Bus</li>
            <li><strong>Travel time:</strong> Approximately 2–3 hours depending on traffic</li>
            <li><strong>Fare:</strong> ₱150–200 per person</li>
            <li>Alight at San Pablo City Terminal or City Hall</li>
          </ul>
        </div>
      </div>
    </div>

    <div class="map-panel" id="map-car">
      <div class="map-panel-header">
        <h3 class="map-panel-title">Driving Route — Manila to San Pablo City</h3>
        <button class="map-close-btn" id="close-car">&#8592; Close</button>
      </div>
      <div class="map-body">
        <div class="map-frame-wrap">
          <iframe src="https://www.google.com/maps/embed?pb=!1m28!1m12!1m3!1d247474.83260273664!2d121.07916947812499!3d14.229426600000007!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!4m13!3e0!4m5!1s0x3397c85e23a54d01%3A0x4cabc2e3bfc5219a!2sManila%2C%20Metro%20Manila!3m2!1d14.5995124!2d120.9842195!4m5!1s0x33bd5e8f1b8b3f1d%3A0x3d9a4d5c7b8f3e1a!2sSan%20Pablo%20City%2C%20Laguna!3m2!1d14.0682!2d121.3253!5e0!3m2!1sen!2sph!4v1234567890" allowfullscreen="" loading="lazy" title="Driving route map"></iframe>
        </div>
        <div class="map-directions">
          <h4 class="directions-title">Driving Directions</h4>
          <ul class="directions-list">
            <li>Take <strong>SLEX</strong> (South Luzon Expressway) southbound from Manila</li>
            <li>Exit at <strong>Alaminos / San Pablo Exit</strong> — before Calamba</li>
            <li>Follow signs to San Pablo City via National Highway</li>
            <li>Drive approximately 15–20 km from the expressway exit</li>
            <li><strong>Travel time:</strong> 1.5–2 hours from Manila</li>
            <li><strong>Toll fee:</strong> Approximately ₱150–200</li>
            <li>Parking available at major attractions and the city center</li>
          </ul>
        </div>
      </div>
    </div>

    <div class="map-panel" id="map-jeepney">
      <div class="map-panel-header">
        <h3 class="map-panel-title">Jeepney Route — Nearby Towns to San Pablo City</h3>
        <button class="map-close-btn" id="close-jeepney">&#8592; Close</button>
      </div>
      <div class="map-body">
        <div class="map-frame-wrap">
          <iframe src="https://www.google.com/maps/embed?pb=!1m28!1m12!1m3!1d62186.59574366785!2d121.20843!3d14.142!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!4m13!3e0!4m5!1s0x33bd5dd8b4f9c8b9%3A0x3c5f8f8b8f8f8f8f!2sCalamba%2C%20Laguna!3m2!1d14.2114!2d121.1654!4m5!1s0x33bd5e8f1b8b3f1d%3A0x3d9a4d5c7b8f3e1a!2sSan%20Pablo%20City%2C%20Laguna!3m2!1d14.0682!2d121.3253!5e0!3m2!1sen!2sph!4v1234567890" allowfullscreen="" loading="lazy" title="Jeepney route map"></iframe>
        </div>
        <div class="map-directions">
          <h4 class="directions-title">Jeepney Directions</h4>
          <ul class="directions-list">
            <li><strong>From Calamba:</strong> Take jeepney bound for San Pablo via Alaminos</li>
            <li><strong>From Sta. Cruz:</strong> Take jeepney or van directly to San Pablo</li>
            <li><strong>From Tiaong / Candelaria:</strong> Take jeepney heading to San Pablo</li>
            <li><strong>Travel time:</strong> 30–60 minutes from nearby towns</li>
            <li><strong>Fare:</strong> ₱30–60 depending on origin</li>
            <li>Jeepneys run regularly from 5:00 AM to 8:00 PM</li>
          </ul>
        </div>
      </div>
    </div>

  </div><!-- /tab-getting-here -->

  <!-- ── TAB: GETTING AROUND ───────────────────────────────── -->
  <div class="tab-panel" id="tab-getting-around" role="tabpanel">

    <div class="section-header">
      <p class="section-eyebrow">Local Transport</p>
      <h2 class="section-title">Getting Around <em>San Pablo City</em></h2>
      <p class="section-sub">Six ways to explore every corner of the city and its seven lakes.</p>
    </div>

    <div class="city-grid">

      <div class="city-card">
        <div class="city-card-header">
          <div class="city-card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
          </div>
          <h3 class="city-card-name">Tricycle</h3>
        </div>
        <div class="city-card-body">
          <p class="city-card-desc">The most common and convenient mode of transport within the city. Tricycles can take you to any destination within San Pablo.</p>
          <div class="city-card-details">
            <span class="city-card-detail">Available 24/7 throughout the city</span>
            <span class="city-card-detail">Can be hailed from any street corner</span>
            <span class="city-card-detail">Maximum capacity: 4–5 passengers</span>
            <span class="city-card-detail">Perfect for short to medium distances</span>
          </div>
          <span class="fare-badge">Fare: ₱10–50 depending on distance</span>
        </div>
      </div>

      <div class="city-card">
        <div class="city-card-header">
          <div class="city-card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="1" y="6" width="22" height="12" rx="2"/><path d="M1 10h22M5 18v2M19 18v2"/></svg>
          </div>
          <h3 class="city-card-name">Local Jeepney</h3>
        </div>
        <div class="city-card-body">
          <p class="city-card-desc">Traditional Filipino transport connecting barangays and major landmarks within San Pablo City.</p>
          <div class="city-card-details">
            <span class="city-card-detail">Fixed routes around the city</span>
            <span class="city-card-detail">Operates from 5:00 AM to 9:00 PM</span>
            <span class="city-card-detail">Budget-friendly option for locals</span>
            <span class="city-card-detail">Routes to all seven lakes available</span>
          </div>
          <span class="fare-badge">Fare: ₱12–20 per ride</span>
        </div>
      </div>

      <div class="city-card">
        <div class="city-card-header">
          <div class="city-card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 17H3a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h11l4 4v4a2 2 0 0 1-2 2h-2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
          </div>
          <h3 class="city-card-name">Taxi / Grab</h3>
        </div>
        <div class="city-card-body">
          <p class="city-card-desc">Metered taxis and ride-hailing services for comfortable, air-conditioned travel around the city.</p>
          <div class="city-card-details">
            <span class="city-card-detail">Grab and ride-hailing apps available</span>
            <span class="city-card-detail">Metered taxis at major terminals</span>
            <span class="city-card-detail">Air-conditioned and comfortable</span>
            <span class="city-card-detail">Ideal for groups or long distances</span>
          </div>
          <span class="fare-badge">Fare: ₱40 base + metered</span>
        </div>
      </div>

      <div class="city-card">
        <div class="city-card-header">
          <div class="city-card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg>
          </div>
          <h3 class="city-card-name">Bike Rental</h3>
        </div>
        <div class="city-card-body">
          <p class="city-card-desc">Explore San Pablo City at your own pace with bicycle rentals available at select accommodations.</p>
          <div class="city-card-details">
            <span class="city-card-detail">Available at major hotels and resorts</span>
            <span class="city-card-detail">Great for touring the seven lakes</span>
            <span class="city-card-detail">Eco-friendly transportation option</span>
            <span class="city-card-detail">Bike-friendly routes available</span>
          </div>
          <span class="fare-badge">Rental: ₱100–200 per day</span>
        </div>
      </div>

      <div class="city-card">
        <div class="city-card-header">
          <div class="city-card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="5" r="3"/><path d="M12 8v6M8 14l4 6 4-6"/></svg>
          </div>
          <h3 class="city-card-name">Habal-Habal</h3>
        </div>
        <div class="city-card-body">
          <p class="city-card-desc">Motorcycle taxis for quick point-to-point travel, especially useful for reaching remote lake areas and narrow trails.</p>
          <div class="city-card-details">
            <span class="city-card-detail">Fast and agile in traffic</span>
            <span class="city-card-detail">Access to narrow roads and trails</span>
            <span class="city-card-detail">Usually stationed at terminals</span>
            <span class="city-card-detail">Helmet provided by driver</span>
          </div>
          <span class="fare-badge">Fare: ₱20–60 depending on distance</span>
        </div>
      </div>

      <div class="city-card">
        <div class="city-card-header">
          <div class="city-card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="5" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/><line x1="12" y1="8" x2="12" y2="14"/></svg>
          </div>
          <h3 class="city-card-name">Walking</h3>
        </div>
        <div class="city-card-body">
          <p class="city-card-desc">The city center and lakeside areas are pedestrian-friendly. Walking is the best way to experience local culture and street life.</p>
          <div class="city-card-details">
            <span class="city-card-detail">City center is walkable</span>
            <span class="city-card-detail">Scenic routes around the lakes</span>
            <span class="city-card-detail">Safe during daytime hours</span>
            <span class="city-card-detail">Best way to experience local life</span>
          </div>
          <span class="fare-badge">Free &amp; Healthy!</span>
        </div>
      </div>

    </div>
  </div><!-- /tab-getting-around -->

  <!-- ── TAB: TOUR GUIDES ──────────────────────────────────── -->
  <div class="tab-panel" id="tab-tour-guides" role="tabpanel">

    <div class="section-header">
      <p class="section-eyebrow">Local Experts</p>
      <h2 class="section-title">Meet Your <em>Tour Guides</em></h2>
      <p class="section-sub">Our certified local guides are ready to show you the best of San Pablo City.</p>
    </div>

    <?php if ($guides): ?>
    <div class="guide-cards">
      <?php foreach ($guides as $g): ?>
      <?php
        $full_name = trim($g['first_name'] . ' ' . ($g['middle_name'] ? $g['middle_name'] . ' ' : '') . $g['last_name'] . ($g['extension_name'] ? ' ' . $g['extension_name'] : ''));
        $initials  = strtoupper(substr($g['first_name'], 0, 1) . substr($g['last_name'], 0, 1));
      ?>
      <div class="guide-card">
        <div class="guide-avatar">
          <?php if ($g['photo']): ?>
            <img src="<?= htmlspecialchars($g['photo']) ?>" alt="<?= htmlspecialchars($full_name) ?>" />
          <?php else: ?>
            <span><?= $initials ?></span>
          <?php endif; ?>
        </div>
        <p class="guide-name"><?= htmlspecialchars($full_name) ?></p>
        <?php if ($g['contact_number']): ?>
          <p class="guide-contact"><?= htmlspecialchars($g['contact_number']) ?></p>
        <?php endif; ?>
        <?php if ($g['email']): ?>
          <p class="guide-contact"><?= htmlspecialchars($g['email']) ?></p>
        <?php endif; ?>
        <span class="guide-status-badge"><?= htmlspecialchars($g['status']) ?></span>
        <br>
        <a href="tourist/book.php" class="guide-book-btn">Book a Tour</a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="guides-empty">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="rgba(51,100,61,0.3)" stroke-width="1.5" style="margin-bottom:16px;"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
      <p>No guides available at the moment. Please check back soon.</p>
    </div>
    <?php endif; ?>

  </div><!-- /tab-tour-guides -->

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

<!-- ── SCRIPTS ──────────────────────────────────────────────── -->
<script>
  /* ── Tab switching ── */
  const tabBtns   = document.querySelectorAll('.tab-nav-btn');
  const tabPanels = document.querySelectorAll('.tab-panel');

  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      tabBtns.forEach(b => { b.classList.remove('active'); b.setAttribute('aria-selected', 'false'); });
      tabPanels.forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      btn.setAttribute('aria-selected', 'true');
      document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
    });
  });

  /* ── Transport card → map panel ── */
  const transportCards = document.querySelectorAll('.transport-card');
  const mapPanels = {
    bus:     document.getElementById('map-bus'),
    car:     document.getElementById('map-car'),
    jeepney: document.getElementById('map-jeepney')
  };

  transportCards.forEach(card => {
    card.addEventListener('click', () => {
      const type = card.dataset.transport;
      transportCards.forEach(c => c.classList.remove('active'));
      Object.values(mapPanels).forEach(p => p.classList.remove('active'));
      card.classList.add('active');
      mapPanels[type].classList.add('active');
      setTimeout(() => {
        mapPanels[type].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }, 50);
    });
  });

  /* ── Close map buttons ── */
  ['bus', 'car', 'jeepney'].forEach(type => {
    document.getElementById('close-' + type).addEventListener('click', () => {
      mapPanels[type].classList.remove('active');
      transportCards.forEach(c => c.classList.remove('active'));
      document.querySelector('.transport-selector').scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
  });
</script>


</body>
</html>

