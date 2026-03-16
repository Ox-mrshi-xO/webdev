<?php
// user/partials/sidebar.php
$currentPage = basename($_SERVER['PHP_SELF']);
$notifClass  = new Notification();
$unread      = $notifClass->getUnreadCount((int)$_SESSION['user_id']);
?>
<aside class="sidebar" id="sidebar">
  <a href="index.php" class="sidebar-brand">
    <img src="../img/logo clinic.jpg" alt="Logo" class="sidebar-logo-img">
    <div>
      <span>DSRRM Clinic</span>
      <small>Student Portal</small>
    </div>
  </a>

  <p class="sidebar-section">My Dashboard</p>
  <ul class="sidebar-nav">
    <li>
      <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
        <i class="fa fa-home"></i> Home
      </a>
    </li>
    <li>
      <a href="consultation.php" class="<?= $currentPage === 'consultation.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-comments-medical"></i> Online Consultation
      </a>
    </li>
    <li>
      <a href="medical_record.php" class="<?= $currentPage === 'medical_record.php' ? 'active' : '' ?>">
        <i class="fa fa-notes-medical"></i> My Medical Record
      </a>
    </li>
    <li>
      <a href="medicine_request.php" class="<?= $currentPage === 'medicine_request.php' ? 'active' : '' ?>">
        <i class="fa fa-hand-holding-medical"></i> Medicine Request
      </a>
    </li>
    <li>
      <a href="appointments.php" class="<?= $currentPage === 'appointments.php' ? 'active' : '' ?>">
        <i class="fa fa-calendar-alt"></i> My Appointments
      </a>
    </li>
    <li>
      <a href="notifications.php" class="<?= $currentPage === 'notifications.php' ? 'active' : '' ?>">
        <i class="fa fa-bell"></i> Notifications
        <?php if($unread > 0): ?>
          <span class="sidebar-badge"><?= $unread ?></span>
        <?php endif; ?>
      </a>
    </li>
  </ul>

  <div class="sidebar-footer">
    <a href="../logout.php" class="logout-link">
      <i class="fa fa-sign-out-alt"></i> Logout
    </a>
  </div>
</aside>