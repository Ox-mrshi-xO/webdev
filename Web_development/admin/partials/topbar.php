<?php
// admin/partials/topbar.php
$user = Auth::currentUser();
$initials = strtoupper(substr($user['name'],0,1) . (strpos($user['name'],' ')!==false ? substr($user['name'], strpos($user['name'],' ')+1, 1) : ''));
?>
<header class="topbar">
  <button class="icon-btn" id="sidebarToggle" onclick="toggleSidebar()">
    <i class="fa fa-bars"></i>
  </button>
  <div class="topbar-title">
    <?= $pageTitle ?? 'Dashboard' ?>
    <?php if(isset($pageSubtitle)): ?><small><?= $pageSubtitle ?></small><?php endif; ?>
  </div>
  <div class="topbar-actions">
    <a href="appointments.php" class="icon-btn" title="Appointments">
      <i class="fa fa-calendar-check"></i>
    </a>
    <div style="position:relative">
      <div class="user-pill" onclick="toggleUserMenu()">
        <div class="user-pill-avatar"><?= $initials ?></div>
        <div><span class="user-pill-name"><?= htmlspecialchars($user['name']) ?></span><span class="user-pill-role">Administrator</span></div>
        <i class="fa fa-chevron-down" style="font-size:10px;color:var(--muted);margin-left:4px"></i>
      </div>
      <div class="dropdown-menu-custom" id="userMenu">
        <a href="../logout.php" class="danger"><i class="fa fa-sign-out-alt"></i> Logout</a>
      </div>
    </div>
  </div>
</header>
