<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>7 Lakes Escapes</title>
  <link rel="icon" type="image/png" href="images/logo/logo.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="index.css" />
  
</head>
<body>

<!-- ── NAVBAR ───────────────────────────────────────────── -->
<nav class="navbar">
  <a href="index.php" class="nav-logo">
    <img src="images/logo/logo.png" alt="7 Lakes Escapes" />
  </a>
  <ul class="nav-links">
    <li><a href="index.php" class="active">Home</a></li>
    <li><a href="attractions.php">Attractions</a></li>
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

<!-- ── HERO ─────────────────────────────────────────────── -->
<section class="hero">

  <!-- Left weave -->
  <div class="geo-left">
    <svg width="160" height="560" viewBox="0 0 160 560" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <pattern id="weave-l" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
          <rect width="40" height="40" fill="none"/>
          <polygon points="20,0 40,20 20,40 0,20" fill="none" stroke="#6DA170" stroke-width="0.8"/>
          <polygon points="20,8 32,20 20,32 8,20" fill="none" stroke="#8CC48B" stroke-width="0.5"/>
          <circle cx="20" cy="20" r="3" fill="#6DA170" opacity="0.6"/>
          <line x1="0" y1="0" x2="40" y2="40" stroke="#3A8A3C" stroke-width="0.4" opacity="0.5"/>
          <line x1="40" y1="0" x2="0" y2="40" stroke="#3A8A3C" stroke-width="0.4" opacity="0.5"/>
        </pattern>
      </defs>
      <rect width="160" height="560" fill="url(#weave-l)" opacity="0.7"/>
    </svg>
  </div>

  <!-- Right weave -->
  <div class="geo-right">
    <svg width="160" height="560" viewBox="0 0 160 560" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <pattern id="weave-r" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
          <rect width="40" height="40" fill="none"/>
          <polygon points="20,0 40,20 20,40 0,20" fill="none" stroke="#6DA170" stroke-width="0.8"/>
          <polygon points="20,8 32,20 20,32 8,20" fill="none" stroke="#8CC48B" stroke-width="0.5"/>
          <circle cx="20" cy="20" r="3" fill="#6DA170" opacity="0.6"/>
          <line x1="0" y1="0" x2="40" y2="40" stroke="#3A8A3C" stroke-width="0.4" opacity="0.5"/>
          <line x1="40" y1="0" x2="0" y2="40" stroke="#3A8A3C" stroke-width="0.4" opacity="0.5"/>
        </pattern>
      </defs>
      <rect width="160" height="560" fill="url(#weave-r)" opacity="0.7"/>
    </svg>
  </div>

  <!-- Content -->
  <div class="hero-content">

    <p class="hero-eyebrow">San Pablo City, Laguna</p>

    <div class="rule">
      <span class="rule-line"></span>
      <span class="rule-diamond"></span>
      <span class="rule-line"></span>
    </div>

    <h1 class="hero-title">
      7 Lakes
      <em>Escapes</em>
    </h1>

    <p class="hero-tagline">Discover &nbsp;&middot;&nbsp; Explore &nbsp;&middot;&nbsp; Experience</p>

    <div class="hero-actions">
      <a href="attractions.php" class="btn-primary">Explore Now</a>
      <a href="plan.php" class="btn-outline">Plan Your Visit</a>
    </div>

  </div>

  <!-- Scroll hint -->
  <!-- <div class="scroll-hint" aria-hidden="true">
    <span>Scroll</span>
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <polyline points="6 9 12 15 18 9"/>
    </svg>
  </div> -->
  
</section>


</body>
</html>

