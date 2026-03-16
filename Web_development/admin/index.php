<?php
require_once '../config/init.php';
Auth::checkAdmin();

$patient  = new Patient();
$medicine = new Medicine();
$appt     = new Appointment();
$medRec   = new MedicalRecord();
$userMgr  = new UserManager();
$notif    = new Notification();
$consult  = new ConsultationRequest();
$medReq   = new MedicineRequest();

$totalPatients  = $patient->count();
$totalMeds      = $medicine->count();
$todayAppts     = $appt->getTodayCount();
$todayVisits    = $medRec->countToday();
$totalUsers     = $userMgr->count();
$lowStock       = $medicine->countLowStock();
$pendingConsult = $consult->getPendingCount();
$pendingMedReq  = $medReq->getPendingCount();

$upcomingAppts  = $appt->getUpcoming();
$lowStockMeds   = $medicine->getLowStock();
$expiringSoon   = $medicine->getExpiringSoon(30);

$pageTitle    = 'Dashboard';
$pageSubtitle = 'Overview & Quick Stats';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard – DSRRM Clinic</title>
<link rel="stylesheet" href="../css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/topbar.php'; ?>

<main class="main-content">

  <!-- Low Stock Banner -->
  <?php if($lowStock > 0): ?>
  <div class="low-stock-banner">
    <i class="fa fa-exclamation-triangle"></i>
    <div><strong><?= $lowStock ?> medicine(s)</strong> are below minimum stock level.
      <a href="medicines.php?filter=low" style="color:#7c5800;font-weight:800;margin-left:8px">View Details →</a>
    </div>
  </div>
  <?php endif; ?>

  <!-- Stats Grid -->
  <div class="stats-grid">
    <div class="stat-card blue">
      <div class="stat-icon"><i class="fa fa-user-injured"></i></div>
      <div class="stat-info"><h3><?= $totalPatients ?></h3><p>Total Patients</p></div>
    </div>
    <div class="stat-card green">
      <div class="stat-icon"><i class="fa fa-calendar-check"></i></div>
      <div class="stat-info"><h3><?= $todayAppts ?></h3><p>Today's Appointments</p></div>
    </div>
    <div class="stat-card orange">
      <div class="stat-icon"><i class="fa fa-pills"></i></div>
      <div class="stat-info"><h3><?= $totalMeds ?></h3><p>Medicine Types</p></div>
    </div>
    <div class="stat-card red">
      <div class="stat-icon"><i class="fa fa-exclamation-triangle"></i></div>
      <div class="stat-info"><h3><?= $lowStock ?></h3><p>Low Stock Alerts</p></div>
    </div>
    <div class="stat-card teal">
      <div class="stat-icon"><i class="fa fa-users"></i></div>
      <div class="stat-info"><h3><?= $totalUsers ?></h3><p>Registered Students</p></div>
    </div>
    <div class="stat-card blue">
      <div class="stat-icon"><i class="fa fa-stethoscope"></i></div>
      <div class="stat-info"><h3><?= $todayVisits ?></h3><p>Clinic Visits Today</p></div>
    </div>
    <div class="stat-card green">
      <div class="stat-icon"><i class="fa fa-comments-medical"></i></div>
      <div class="stat-info"><h3><?= $pendingConsult ?></h3><p>Pending Consultations</p></div>
    </div>
    <div class="stat-card orange">
      <div class="stat-icon"><i class="fa fa-hand-holding-medical"></i></div>
      <div class="stat-info"><h3><?= $pendingMedReq ?></h3><p>Medicine Requests</p></div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;flex-wrap:wrap">

    <!-- Upcoming Appointments -->
    <div class="page-card">
      <div class="card-header">
        <h2><i class="fa fa-calendar-alt"></i> Upcoming Appointments</h2>
        <a href="appointments.php" class="btn btn-primary btn-sm">View All</a>
      </div>
      <div class="card-body" style="padding:0">
        <?php if(empty($upcomingAppts)): ?>
          <div class="empty-state"><i class="fa fa-calendar"></i><h4>No upcoming appointments</h4></div>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Patient</th><th>Date & Time</th><th>Type</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach($upcomingAppts as $a): ?>
            <tr>
              <td><div class="name-cell"><div class="patient-avatar"><?= strtoupper(substr($a['patient_name']??'?',0,1)) ?></div><?= htmlspecialchars($a['patient_name']??'Walk-in') ?></div></td>
              <td><?= date('M d, Y',strtotime($a['appointment_date'])) ?><br><small style="color:var(--muted)"><?= date('h:i A',strtotime($a['appointment_time'])) ?></small></td>
              <td><?= htmlspecialchars($a['type']) ?></td>
              <td><span class="badge badge-success"><?= $a['status'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Low Stock Medicines -->
    <div class="page-card">
      <div class="card-header">
        <h2><i class="fa fa-pills"></i> Low Stock Medicines</h2>
        <a href="medicines.php?filter=low" class="btn btn-warning btn-sm">View All</a>
      </div>
      <div class="card-body" style="padding:0">
        <?php if(empty($lowStockMeds)): ?>
          <div class="empty-state"><i class="fa fa-check-circle" style="color:var(--success);opacity:1"></i><h4>All medicines are well-stocked!</h4></div>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Medicine</th><th>Current Stock</th><th>Min. Stock</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach($lowStockMeds as $m): ?>
            <tr>
              <td><strong><?= htmlspecialchars($m['name']) ?></strong><br><small style="color:var(--muted)"><?= htmlspecialchars($m['category']??'') ?></small></td>
              <td><?= $m['quantity'] ?> <?= htmlspecialchars($m['unit']) ?></td>
              <td><?= $m['min_stock'] ?></td>
              <td><span class="badge <?= $m['quantity']==0?'badge-danger':'badge-warning' ?>"><?= $m['quantity']==0?'Out of Stock':'Low Stock' ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <!-- Expiring Soon -->
  <?php if(!empty($expiringSoon)): ?>
  <div class="page-card" style="margin-top:24px">
    <div class="card-header">
      <h2><i class="fa fa-calendar-times" style="color:var(--danger)"></i> Medicines Expiring Soon (within 30 days)</h2>
    </div>
    <div class="card-body" style="padding:0">
      <div class="table-wrap">
        <table>
          <thead><tr><th>Medicine</th><th>Category</th><th>Quantity</th><th>Expiry Date</th><th>Days Left</th></tr></thead>
          <tbody>
          <?php foreach($expiringSoon as $m): ?>
          <?php $daysLeft = (int)((strtotime($m['expiry_date'])-time())/86400); ?>
          <tr>
            <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
            <td><?= htmlspecialchars($m['category']??'') ?></td>
            <td><?= $m['quantity'] ?> <?= htmlspecialchars($m['unit']) ?></td>
            <td><?= date('M d, Y', strtotime($m['expiry_date'])) ?></td>
            <td><span class="badge <?= $daysLeft<=7?'badge-danger':($daysLeft<=14?'badge-warning':'badge-info') ?>"><?= $daysLeft ?> days</span></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

</main>
<script src="../js/dashboard.js"></script>
</body>
</html>
