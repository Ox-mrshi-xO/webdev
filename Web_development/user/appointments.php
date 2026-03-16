<?php
require_once '../config/init.php';
Auth::checkUser();

$userId    = (int)$_SESSION['user_id'];
$apptClass = new Appointment();

$appointments = $apptClass->getByUser($userId);
$statusColors = ['Pending'=>'badge-warning','Approved'=>'badge-success','Completed'=>'badge-info','Cancelled'=>'badge-danger','No-show'=>'badge-muted'];
$upcoming     = array_filter($appointments, fn($a) => $a['appointment_date'] >= date('Y-m-d') && !in_array($a['status'],['Cancelled','No-show']));
$past         = array_filter($appointments, fn($a) => $a['appointment_date'] < date('Y-m-d') || in_array($a['status'],['Completed','Cancelled','No-show']));

$pageTitle    = 'My Appointments';
$pageSubtitle = 'Your scheduled and past clinic appointments';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Appointments – DSRRM Clinic</title>
<link rel="stylesheet" href="../css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/topbar.php'; ?>

<main class="main-content">

  <?php if(empty($appointments)): ?>
    <div class="page-card">
      <div class="card-body">
        <div class="empty-state"><i class="fa fa-calendar-times"></i><h4>No appointments found</h4><p>Your appointments will appear here once the clinic schedules or approves them.<br>You can <a href="consultation.php" style="color:var(--primary);font-weight:700">request a consultation</a> to get an appointment.</p></div>
      </div>
    </div>
  <?php else: ?>

  <!-- Upcoming -->
  <?php if(!empty($upcoming)): ?>
  <div class="page-card" style="margin-bottom:24px">
    <div class="card-header"><h2><i class="fa fa-calendar-check" style="color:var(--success)"></i> Upcoming Appointments (<?= count($upcoming) ?>)</h2></div>
    <div class="card-body" style="padding:0">
      <?php foreach($upcoming as $a): ?>
      <div style="padding:18px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:18px;flex-wrap:wrap">
        <div style="text-align:center;background:var(--bg);border-radius:12px;padding:12px 16px;flex-shrink:0">
          <div style="font-size:24px;font-weight:800;color:var(--primary);line-height:1"><?= date('d',strtotime($a['appointment_date'])) ?></div>
          <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase"><?= date('M Y',strtotime($a['appointment_date'])) ?></div>
        </div>
        <div style="flex:1">
          <div style="font-size:16px;font-weight:800;margin-bottom:4px"><?= htmlspecialchars($a['type']) ?> Appointment</div>
          <div style="font-size:13px;color:var(--muted);margin-bottom:6px"><i class="fa fa-clock"></i> <?= date('h:i A',strtotime($a['appointment_time'])) ?></div>
          <?php if($a['notes']): ?><p style="font-size:13px;color:#555"><?= htmlspecialchars($a['notes']) ?></p><?php endif; ?>
        </div>
        <span class="badge <?= $statusColors[$a['status']] ?? 'badge-muted' ?>" style="font-size:12px;padding:5px 14px"><?= $a['status'] ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Past Appointments -->
  <?php if(!empty($past)): ?>
  <div class="page-card">
    <div class="card-header"><h2><i class="fa fa-history"></i> Appointment History (<?= count($past) ?>)</h2></div>
    <div class="card-body" style="padding:0">
      <div class="table-wrap">
        <table>
          <thead><tr><th>Date</th><th>Time</th><th>Type</th><th>Patient</th><th>Status</th><th>Notes</th></tr></thead>
          <tbody>
          <?php foreach($past as $a): ?>
          <tr>
            <td><?= date('M d, Y', strtotime($a['appointment_date'])) ?></td>
            <td><?= date('h:i A', strtotime($a['appointment_time'])) ?></td>
            <td><?= htmlspecialchars($a['type']) ?></td>
            <td><?= htmlspecialchars($a['patient_name']??'—') ?></td>
            <td><span class="badge <?= $statusColors[$a['status']] ?? 'badge-muted' ?>"><?= $a['status'] ?></span></td>
            <td style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($a['notes']??'—') ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>

</main>
<script src="../js/dashboard.js"></script>
</body>
</html>
