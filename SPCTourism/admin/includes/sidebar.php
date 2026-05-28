<?php
$nav = [
    'dashboard' => ['label' => 'Dashboard',    'href' => 'dashboard.php', 'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
    'places'    => ['label' => 'Places',        'href' => 'places.php',   'icon' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>'],
    'foods'     => ['label' => 'Food & Drinks', 'href' => 'foods.php',    'icon' => '<path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/>'],
    'culture'   => ['label' => 'Culture',       'href' => 'culture.php',  'icon' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>'],
    'photos'    => ['label' => 'Photos',        'href' => 'photos.php',   'icon' => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>'],
    'guides'    => ['label' => 'Tour Guides',   'href' => 'guides.php',   'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
    'bookings'  => ['label' => 'Bookings',      'href' => 'bookings.php', 'icon' => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>'],
    'users'     => ['label' => 'Users',         'href' => 'users.php',    'icon' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'],
];
?>
<aside class="sidebar" id="admin-sidebar">

  <div class="sidebar-brand">
    <img src="../images/logo/logo.png" alt="Logo" class="sidebar-logo" />
    <div class="sidebar-brand-text">
      <span class="sidebar-title">7 Lakes Escapes</span>
      <span class="sidebar-sub">Admin Panel</span>
    </div>
    <button class="sidebar-toggle" id="sidebar-toggle" title="Collapse sidebar">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="15 18 9 12 15 6"/>
      </svg>
    </button>
  </div>

  <nav class="sidebar-nav">
    <?php foreach ($nav as $key => $item): ?>
      <a href="<?= $item['href'] ?>"
         class="sidebar-link <?= ($current_page ?? '') === $key ? 'active' : '' ?>"
         data-label="<?= htmlspecialchars($item['label']) ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <?= $item['icon'] ?>
        </svg>
        <span class="sidebar-link-label"><?= $item['label'] ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">
    <a href="profile.php" class="sidebar-user <?= ($current_page ?? '') === 'profile' ? 'sidebar-user-active' : '' ?>">
      <div class="sidebar-avatar">
        <?php if (!empty($_SESSION['profile_photo'])): ?>
          <img src="../<?= htmlspecialchars($_SESSION['profile_photo']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:2px;" alt="" />
        <?php else: ?>
          <?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?>
        <?php endif; ?>
      </div>
      <div class="sidebar-user-info">
        <span class="sidebar-username"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></span>
        <span class="sidebar-role"><?= htmlspecialchars($_SESSION['role'] ?? '') ?></span>
      </div>
    </a>
    <a href="logout.php" class="sidebar-logout" title="Logout">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    </a>
  </div>

</aside>

<script>
(function () {
  var body = document.body;

  /* ── Sidebar collapse ── */
  var sBtn = document.getElementById('sidebar-toggle');
  if (localStorage.getItem('adminSidebar') === 'collapsed') {
    body.classList.add('sidebar-collapsed');
  }
  sBtn.addEventListener('click', function () {
    body.classList.toggle('sidebar-collapsed');
    localStorage.setItem('adminSidebar', body.classList.contains('sidebar-collapsed') ? 'collapsed' : 'expanded');
  });

}());
</script>
