<?php
// admin/partials/sidebar.php
$currentPage = basename($_SERVER['PHP_SELF']);
$medClass = new Medicine();
$lowStockCount = $medClass->countLowStock();
?>
<aside class="sidebar" id="sidebar">
  <a href="index.php" class="sidebar-brand">
  <img src="../img/logo clinic.jpg" alt="Logo" class="sidebar-logo-img">
    <div><span>DSRRM Clinic</span><small>Admin Panel</small></div>
  </a>

  <p class="sidebar-section">Main</p>
  <ul class="sidebar-nav">
    <li>
      <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
        <i class="fa fa-tachometer-alt"></i> Dashboard
      </a>
    </li>

    <!-- Patient Records -->
    <li>
      <button class="dropdown-toggle-btn <?= in_array($currentPage, ['patients.php','patient_history.php','add_patient.php']) ? 'open-parent' : '' ?>"
              onclick="toggleDrop('patientDrop',this)">
        <i class="fa fa-user-injured"></i> Patient Records
        <i class="fa fa-chevron-right chevron"></i>
      </button>
      <ul class="sidebar-dropdown <?= in_array($currentPage, ['patients.php','patient_history.php','add_patient.php']) ? 'open' : '' ?>" id="patientDrop">
        <li><a href="patients.php" class="<?= $currentPage==='patients.php'?'active':'' ?>"><i class="fa fa-list"></i> All Patients</a></li>
        <li><a href="patients.php?action=add" class="<?= ($currentPage==='patients.php'&&($_GET['action']??'')==='add')?'active':'' ?>"><i class="fa fa-user-plus"></i> Add Patient</a></li>
        <li><a href="patient_history.php" class="<?= $currentPage==='patient_history.php'?'active':'' ?>"><i class="fa fa-history"></i> Medical History</a></li>
      </ul>
    </li>

    <!-- Medicine Stock -->
    <li>
      <button class="dropdown-toggle-btn <?= in_array($currentPage, ['medicines.php']) ? 'open-parent' : '' ?>"
              onclick="toggleDrop('medicineDrop',this)">
        <i class="fa fa-pills"></i> Medicine Stock
        <?php if($lowStockCount > 0): ?>
          <span class="sidebar-badge"><?= $lowStockCount ?></span>
        <?php endif; ?>
        <i class="fa fa-chevron-right chevron"></i>
      </button>
      <ul class="sidebar-dropdown <?= $currentPage==='medicines.php'?'open':'' ?>" id="medicineDrop">
        <li><a href="medicines.php" class="<?= $currentPage==='medicines.php'?'active':'' ?>"><i class="fa fa-list"></i> All Medicines</a></li>
        <li><a href="medicines.php?action=add"><i class="fa fa-plus"></i> Add Medicine</a></li>
        <li><a href="medicines.php?filter=low"><i class="fa fa-exclamation-triangle"></i> Low Stock</a></li>
        <li><a href="medicines.php?filter=expiring"><i class="fa fa-calendar-times"></i> Expiring Soon</a></li>
      </ul>
    </li>

    <!-- Appointments -->
    <li>
      <button class="dropdown-toggle-btn <?= in_array($currentPage, ['appointments.php','consultations.php']) ? 'open-parent' : '' ?>"
              onclick="toggleDrop('apptDrop',this)">
        <i class="fa fa-calendar-check"></i> Appointments
        <i class="fa fa-chevron-right chevron"></i>
      </button>
      <ul class="sidebar-dropdown <?= in_array($currentPage,['appointments.php','consultations.php'])?'open':'' ?>" id="apptDrop">
        <li><a href="appointments.php" class="<?= $currentPage==='appointments.php'?'active':'' ?>"><i class="fa fa-calendar"></i> All Appointments</a></li>
        <li><a href="appointments.php?action=add"><i class="fa fa-plus"></i> Add Appointment</a></li>
        <li><a href="consultations.php" class="<?= $currentPage==='consultations.php'?'active':'' ?>"><i class="fa fa-comments-medical"></i> Consultation Requests</a></li>
      </ul>
    </li>

    <!-- Medicine Requests -->
    <li>
      <a href="medicine_requests.php" class="<?= $currentPage==='medicine_requests.php'?'active':'' ?>">
        <i class="fa fa-hand-holding-medical"></i> Medicine Requests
      </a>
    </li>

    <!-- Reports -->
    <li>
      <a href="reports.php" class="<?= $currentPage==='reports.php'?'active':'' ?>">
        <i class="fa fa-chart-bar"></i> Clinic Reports
      </a>
    </li>

    <p class="sidebar-section" style="margin-top:10px">Administration</p>

    <!-- User Management -->
    <li>
      <button class="dropdown-toggle-btn <?= in_array($currentPage,['users.php']) ? 'open-parent' : '' ?>"
              onclick="toggleDrop('userDrop',this)">
        <i class="fa fa-users-cog"></i> User Management
        <i class="fa fa-chevron-right chevron"></i>
      </button>
      <ul class="sidebar-dropdown <?= $currentPage==='users.php'?'open':'' ?>" id="userDrop">
        <li><a href="users.php" class="<?= $currentPage==='users.php'?'active':'' ?>"><i class="fa fa-list"></i> All Users</a></li>
        <li><a href="users.php?action=add"><i class="fa fa-user-plus"></i> Add User</a></li>
      </ul>
    </li>
  </ul>

  <div style="padding:14px 16px;border-top:1px solid rgba(255,255,255,.1);margin-top:auto">
    <a href="../logout.php" style="display:flex;align-items:center;gap:10px;color:rgba(255,255,255,.6);text-decoration:none;font-size:13px;font-weight:700;padding:8px 10px;border-radius:10px;transition:.2s"
       onmouseover="this.style.background='rgba(255,255,255,.1)';this.style.color='#fff'"
       onmouseout="this.style.background='';this.style.color='rgba(255,255,255,.6)'">
      <i class="fa fa-sign-out-alt"></i> Logout
    </a>
  </div>
</aside>
