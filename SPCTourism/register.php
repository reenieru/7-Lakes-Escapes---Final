<?php
session_start();
require_once 'conn.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $name = trim($_POST['name'] ?? '');

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($name)) {
        $error_message = 'Please fill in all fields.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Invalid email format.';
    } else {
        // Check if email already exists
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM users WHERE email = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $count);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            if ($count > 0) {
                $error_message = 'Email already registered. Please use a different email or log in.';
            } else {
                // Insert new tourist user
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password, name, role, created_at) VALUES (?, ?, ?, ?, 'tourist', NOW())");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'ssss', $username, $email, $hashed_password, $name);
                    $result = mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    if ($result) {
                        $success_message = 'Account created successfully! Redirecting to login...';
                        header('Refresh: 2; url=login.php');
                    } else {
                        $error_message = 'Failed to create account. Please try again.';
                    }
                } else {
                    $error_message = 'Database error. Please try again later.';
                }
            }
        } else {
            $error_message = 'Database error. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>7 Lakes Escapes — Tourist Registration</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="login.css" />
</head>
<body>

<!-- ── BACKGROUND PATTERN ──────────────────────────── -->
<div class="bg-pattern"></div>

<!-- Filipino weave geo panels -->
<!-- LEFT GEO -->
<div class="geo-left">
  <svg width="160" height="500" viewBox="0 0 160 500" xmlns="http://www.w3.org/2000/svg">
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
    <rect width="160" height="500" fill="url(#weave-l)" opacity="0.7"/>
  </svg>
</div>

<!-- RIGHT GEO -->
<div class="geo-right">
  <svg width="160" height="500" viewBox="0 0 160 500" xmlns="http://www.w3.org/2000/svg">
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
    <rect width="160" height="500" fill="url(#weave-r)" opacity="0.7"/>
  </svg>
</div>

<!-- TOP GEO -->
<div class="geo-top">
  <svg width="600" height="100" viewBox="0 0 600 100" xmlns="http://www.w3.org/2000/svg">
    <defs>
      <pattern id="weave-t" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
        <polygon points="20,0 40,20 20,40 0,20" fill="none" stroke="#6DA170" stroke-width="0.8"/>
        <circle cx="20" cy="20" r="2.5" fill="#8CC48B" opacity="0.5"/>
        <line x1="0" y1="0" x2="40" y2="40" stroke="#3A8A3C" stroke-width="0.4" opacity="0.4"/>
        <line x1="40" y1="0" x2="0" y2="40" stroke="#3A8A3C" stroke-width="0.4" opacity="0.4"/>
      </pattern>
    </defs>
    <rect width="600" height="100" fill="url(#weave-t)" opacity="0.6"/>
  </svg>
</div>

<!-- ── MAIN CARD ────────────────────────────────────── -->
<div class="card-wrap">

  <!-- LEFT PANEL -->
  <div class="panel-left">
    <div class="panel-content">

      <!-- Logo -->
      <div class="logo-container">
        <img src="images/logo/logo.png" alt="7 Lakes Escapes Logo" class="logo-image" />
      </div>

      <div class="brand-title">
        <span>7 Lakes Escapes</span>
        Tourism Portal
      </div>

      <p class="brand-tagline">Discover &middot; Explore &middot; Experience</p>

      <div class="rule">
        <span class="rule-line"></span>
        <span class="rule-diamond"></span>
        <span class="rule-line"></span>
      </div>

      <ul class="left-features">
        <li>Explore local destinations</li>
        <li>Book certified tour guides</li>
        <li>Plan your itinerary</li>
        <li>Discover culture &amp; cuisine</li>
      </ul>

    </div>
  </div>

  <!-- RIGHT PANEL -->
  <div class="panel-right">

    <!-- REGISTRATION FORM -->
    <div class="form-panel active" id="panel-register">
      <div class="form-head">
        <h2>Join Us, <em>Traveler</em></h2>
        <p>Create your account to start exploring amazing destinations.</p>
      </div>

      <?php if (!empty($error_message)): ?>
        <div class="msg error">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <?= htmlspecialchars($error_message) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($success_message)): ?>
        <div class="msg success">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
          <?= htmlspecialchars($success_message) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="" id="form-register">
        <div class="form-group">
          <label for="name">Full Name</label>
          <div class="input-wrap">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="#3A8A3C" stroke-width="2">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
            <input
              class="form-input"
              type="text"
              id="name"
              name="name"
              placeholder="John Doe"
              value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
              required />
          </div>
        </div>

        <div class="form-group">
          <label for="username">Username</label>
          <div class="input-wrap">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="#3A8A3C" stroke-width="2">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
            <input
              class="form-input"
              type="text"
              id="username"
              name="username"
              placeholder="traveler_123"
              value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
              required />
          </div>
        </div>

        <div class="form-group">
          <label for="email">Email Address</label>
          <div class="input-wrap">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="#3A8A3C" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
            </svg>
            <input
              class="form-input"
              type="email"
              id="email"
              name="email"
              placeholder="traveler@example.com"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
              required />
          </div>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrap">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="#3A8A3C" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <input
              class="form-input"
              type="password"
              id="password"
              name="password"
              placeholder="••••••••"
              required />
            <button type="button" class="toggle-pass" data-target="password" aria-label="Toggle password">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="eye-icon">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <div class="input-wrap">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="#3A8A3C" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <input
              class="form-input"
              type="password"
              id="confirm_password"
              name="confirm_password"
              placeholder="••••••••"
              required />
            <button type="button" class="toggle-pass" data-target="confirm_password" aria-label="Toggle password">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="eye-icon">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-submit" id="btn-register">
          <span class="btn-text">Create Account</span>
          <span class="spinner">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
            </svg>
          </span>
        </button>
      </form>

      <p class="form-footer">
        Already have an account? <a href="login.php">Sign in here</a>
      </p>
    </div>

  </div><!-- /panel-right -->
</div><!-- /card-wrap -->

<script src="login.js"></script>
</body>
</html>
