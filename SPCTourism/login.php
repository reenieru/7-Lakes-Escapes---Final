<?php
session_start();
require_once 'conn.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_input = trim($_POST['email'] ?? '');
    $pass_input  = $_POST['password'] ?? '';

    if (empty($email_input) || empty($pass_input)) {
        $error_message = 'Please fill in all fields.';
    } else {
        $email_safe = mysqli_real_escape_string($conn, $email_input);
        $query = "SELECT * FROM users WHERE email = '$email_safe' LIMIT 1";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);

            if (password_verify($pass_input, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']  = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];
                $_SESSION['email']    = $user['email'];
                $_SESSION['profile_photo'] = $user['profile_photo'] ?? null;

                if ($user['role'] === 'tourist') {
                    $redirect = $_SESSION['login_redirect'] ?? 'index.php';
                    unset($_SESSION['login_redirect']);
                    header('Location: ' . $redirect);
                    exit;
                } else {
                    header('Location: admin/dashboard.php');
                    exit;
                }
            } else {
                $error_message = 'Invalid email or password.';
            }
        } else {
            $error_message = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>7 Lakes Escapes &mdash; Sign In</title>
  <link rel="icon" type="image/png" href="images/logo/logo.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="login.css" />
</head>
<body>

<div class="bg-pattern"></div>

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

<div class="card-wrap">

  <!-- LEFT PANEL -->
  <div class="panel-left">
    <div class="panel-content">

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

    <div class="form-panel active">
      <div class="form-head">
        <h2>Mabuhay, <em>Traveler</em></h2>
        <p>Sign in with your email and password to continue.</p>
      </div>

      <?php if ($error_message): ?>
        <div class="msg error">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <?= htmlspecialchars($error_message) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="" id="login-form">

        <div class="form-group">
          <label for="email">Email Address</label>
          <div class="input-wrap">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="#3A8A3C" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
            <input class="form-input" type="email" id="email" name="email"
                   placeholder="you@example.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required />
          </div>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrap">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="#3A8A3C" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <input class="form-input" type="password" id="password" name="password"
                   placeholder="••••••••" required />
            <button type="button" class="toggle-pass" data-target="password" aria-label="Toggle password">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="eye-icon">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-submit">
          <span class="btn-text">Sign In</span>
          <span class="spinner">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
            </svg>
          </span>
        </button>
      </form>

      <p class="form-footer">
        New here? <a href="register.php">Create an account</a>
      </p>
    </div>

  </div><!-- /panel-right -->
</div><!-- /card-wrap -->

<script src="login.js"></script>

</body>
</html>
