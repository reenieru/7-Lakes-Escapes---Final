<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

$current_page = 'users';
$message = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'change_role') {
        $uid  = (int)$_POST['user_id'];
        $role = in_array($_POST['role'], ['admin', 'tourist']) ? $_POST['role'] : 'tourist';
        if ($uid === (int)$_SESSION['user_id']) {
            $error = 'You cannot change your own role.';
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE users SET role=? WHERE user_id=?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'si', $role, $uid);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            $message = 'Role updated.';
        }
    } elseif ($action === 'delete') {
        $uid = (int)$_POST['user_id'];
        if ($uid === (int)$_SESSION['user_id']) {
            $error = 'You cannot delete your own account.';
        } else {
            $uname = '';
            $stmt = mysqli_prepare($conn, "SELECT username FROM users WHERE user_id=?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $uid);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $uname);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);
            }

            $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id=?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $uid);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            $message = "User \"$uname\" deleted.";
        }
    } elseif ($action === 'reset_password') {
        $uid  = (int)$_POST['user_id'];
        $pass = trim($_POST['new_password'] ?? '');
        if (strlen($pass) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = mysqli_prepare($conn, "UPDATE users SET password=? WHERE user_id=?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'si', $hash, $uid);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            $message = 'Password updated.';
        }
    }
}

$filter = $_GET['filter'] ?? 'all';
$where_cond  = match($filter) {
    'admin'   => "role='admin'",
    'tourist' => "role='tourist'",
    default   => ''
};

$search_cond = "";
if (isset($_POST['search']) && !empty($_POST['key'])) {
    $key = mysqli_real_escape_string($conn, trim($_POST['key']));
    $search_cond = "(name LIKE '%$key%' OR username LIKE '%$key%' OR email LIKE '%$key%' OR role LIKE '%$key%')";
}

$where_clause = "";
if ($where_cond && $search_cond) {
    $where_clause = "WHERE $where_cond AND $search_cond";
} elseif ($where_cond) {
    $where_clause = "WHERE $where_cond";
} elseif ($search_cond) {
    $where_clause = "WHERE $search_cond";
}

$users = [];
$res = mysqli_query($conn, "SELECT user_id, name, username, email, role, created_at FROM users $where_clause ORDER BY created_at DESC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Users — Admin</title>
  <link rel="icon" type="image/png" href="../images/logo/logo.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="admin.css" />
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="admin-main">
  <div class="topbar">
    <div class="topbar-left">
      <span class="topbar-title">Users</span>
      <span class="topbar-breadcrumb"><a href="dashboard.php">Dashboard</a> / Users</span>
    </div>
  </div>

  <div class="admin-content">
    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Filter tabs -->
    <div style="display:flex;gap:8px;margin-bottom:20px;">
      <?php foreach (['all' => 'All', 'admin' => 'Admins', 'tourist' => 'Tourists'] as $k => $v): ?>
        <a href="users.php?filter=<?= $k ?>" class="btn <?= $filter === $k ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= $v ?></a>
      <?php endforeach; ?>
    </div>

    <div class="admin-card">
      <div class="admin-card-header">
        <span class="admin-card-title">Users (<?= count($users) ?>)</span>
        <form method="POST" action="" class="card-search-wrap">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input type="text" name="key" placeholder="Search users..." class="card-search-input" value="<?= htmlspecialchars($_POST['key'] ?? '') ?>" />
          <button type="submit" name="search" style="display:none;"></button>
        </form>
      </div>
      <?php if ($users): ?>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td><?= htmlspecialchars($u['name'] ?? '—') ?></td>
              <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
              <td><?= htmlspecialchars($u['email'] ?? '—') ?></td>
              <td>
                <span class="badge <?= $u['role'] === 'admin' ? 'badge-green' : 'badge-orange' ?>">
                  <?= htmlspecialchars($u['role']) ?>
                </span>
              </td>
              <td style="font-size:0.78rem;color:rgba(51,100,61,0.55)"><?= htmlspecialchars(substr($u['created_at'], 0, 10)) ?></td>
              <td style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                <!-- Change role -->
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action" value="change_role" />
                  <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>" />
                  <select name="role" class="form-select" style="padding:3px 6px;font-size:0.75rem;height:auto;">
                    <option value="tourist" <?= $u['role'] === 'tourist' ? 'selected' : '' ?>>Tourist</option>
                    <option value="admin"   <?= $u['role'] === 'admin'   ? 'selected' : '' ?>>Admin</option>
                  </select>
                  <button type="submit" class="btn btn-outline btn-sm">Set</button>
                </form>
                <!-- Reset password -->
                <button type="button" class="btn btn-outline btn-sm" onclick="toggleReset(<?= $u['user_id'] ?>)">Reset PW</button>
                <!-- Delete -->
                <?php if ($u['user_id'] !== (int)($_SESSION['user_id'] ?? 0)): ?>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this user?')">
                  <input type="hidden" name="action" value="delete" />
                  <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>" />
                  <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
            <!-- Inline reset-password row -->
            <tr id="reset-<?= $u['user_id'] ?>" style="display:none;background:rgba(51,100,61,0.03)">
              <td colspan="6" style="padding:10px 16px;">
                <form method="POST" style="display:flex;gap:8px;align-items:center;">
                  <input type="hidden" name="action" value="reset_password" />
                  <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>" />
                  <input type="password" name="new_password" class="form-input" placeholder="New password (min 6 chars)" style="max-width:280px;padding:6px 10px;" required minlength="6" />
                  <button type="submit" class="btn btn-primary btn-sm">Save Password</button>
                  <button type="button" class="btn btn-outline btn-sm" onclick="toggleReset(<?= $u['user_id'] ?>)">Cancel</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="empty-state"><p>No users found.</p></div>
      <?php endif; ?>
    </div>
  </div>
</div>
<script>
function toggleReset(id) {
  const row = document.getElementById('reset-' + id);
  row.style.display = row.style.display === 'none' ? '' : 'none';
}
</script>
</body>
</html>
