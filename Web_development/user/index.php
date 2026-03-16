<?php
require_once '../config/init.php';
Auth::checkUser();

$userId      = (int)$_SESSION['user_id'];
$notifClass  = new Notification();
$apptClass   = new Appointment();
$consultClass= new ConsultationRequest();
$medReqClass = new MedicineRequest();
$mrClass     = new MedicalRecord();
$patientClass= new Patient();

$patient       = $patientClass->getByUserId($userId);
$unread        = $notifClass->getUnreadCount($userId);
$myAppts       = $apptClass->getByUser($userId);
$myConsults    = $consultClass->getByUser($userId);
$myMedReqs     = $medReqClass->getByUser($userId);
$myRecords     = $patient ? $mrClass->getByPatient($patient['id']) : [];
$recentNotifs  = array_slice($notifClass->getByUser($userId), 0, 5);

$upcomingAppts = array_filter($myAppts, fn($a) => $a['appointment_date'] >= date('Y-m-d') && $a['status'] === 'Approved');
$pendingConsult= count(array_filter($myConsults, fn($c) => $c['status'] === 'Pending'));
$pendingMedReq = count(array_filter($myMedReqs,  fn($m) => $m['status'] === 'Pending'));

$pageTitle    = 'My Dashboard';
$pageSubtitle = 'Welcome back, ' . explode(' ', $_SESSION['user_name'])[0] . '!';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Portal – DSRRM Clinic</title>
<link rel="stylesheet" href="../css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/topbar.php'; ?>

<main class="main-content">

  <!-- Welcome Banner -->
  <div style="background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:16px;padding:28px 32px;color:#fff;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px">
    <div>
      <h2 style="font-size:22px;font-weight:800;margin-bottom:6px">Hello, <?= htmlspecialchars(explode(' ',$_SESSION['user_name'])[0]) ?>! 👋</h2>
      <p style="opacity:.85;font-size:14px"><?= $patient ? 'Student ID: '.htmlspecialchars($patient['student_id']??'N/A') : 'Your health records are managed here.' ?></p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <a href="consultation.php" class="btn" style="background:rgba(255,255,255,.25);color:#fff;backdrop-filter:blur(4px);border:1px solid rgba(255,255,255,.3)"><i class="fa fa-plus-circle"></i> Request Consultation</a>
      <a href="medical_record.php" class="btn" style="background:rgba(255,255,255,.15);color:#fff;backdrop-filter:blur(4px);border:1px solid rgba(255,255,255,.3)"><i class="fa fa-notes-medical"></i> View My Records</a>
    </div>
  </div>

  <!-- Quick Stats -->
  <div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr))">
    <div class="stat-card blue">
      <div class="stat-icon"><i class="fa fa-calendar-alt"></i></div>
      <div class="stat-info"><h3><?= count($myAppts) ?></h3><p>My Appointments</p></div>
    </div>
    <div class="stat-card green">
      <div class="stat-icon"><i class="fa fa-notes-medical"></i></div>
      <div class="stat-info"><h3><?= count($myRecords) ?></h3><p>Visit Records</p></div>
    </div>
    <div class="stat-card orange">
      <div class="stat-icon"><i class="fa fa-comments-medical"></i></div>
      <div class="stat-info"><h3><?= $pendingConsult ?></h3><p>Pending Consultations</p></div>
    </div>
    <div class="stat-card red">
      <div class="stat-icon"><i class="fa fa-bell"></i></div>
      <div class="stat-info"><h3><?= $unread ?></h3><p>Unread Notifications</p></div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;flex-wrap:wrap">

    <!-- Upcoming Appointments -->
    <div class="page-card">
      <div class="card-header">
        <h2><i class="fa fa-calendar-check"></i> Upcoming Appointments</h2>
        <a href="appointments.php" class="btn btn-outline btn-sm">View All</a>
      </div>
      <div class="card-body" style="padding:0">
        <?php $upcoming = array_values($upcomingAppts); ?>
        <?php if(empty($upcoming)): ?>
          <div class="empty-state"><i class="fa fa-calendar"></i><h4>No upcoming appointments</h4><p><a href="consultation.php" style="color:var(--primary)">Request a consultation</a> to get started.</p></div>
        <?php else: ?>
        <?php foreach(array_slice($upcoming,0,5) as $a): ?>
        <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:14px">
          <div style="width:46px;height:46px;border-radius:12px;background:var(--bg);display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;flex-shrink:0">
            <span style="font-size:18px;font-weight:800;color:var(--primary);line-height:1"><?= date('d',strtotime($a['appointment_date'])) ?></span>
            <span style="font-size:9px;font-weight:700;color:var(--muted);text-transform:uppercase"><?= date('M',strtotime($a['appointment_date'])) ?></span>
          </div>
          <div style="flex:1">
            <div style="font-size:13px;font-weight:700"><?= htmlspecialchars($a['type']) ?></div>
            <div style="font-size:12px;color:var(--muted)"><?= date('h:i A', strtotime($a['appointment_time'])) ?></div>
          </div>
          <span class="badge badge-success"><?= $a['status'] ?></span>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Recent Notifications -->
    <div class="page-card">
      <div class="card-header">
        <h2><i class="fa fa-bell"></i> Recent Notifications <?php if($unread>0): ?><span class="badge badge-danger" style="font-size:11px"><?= $unread ?> new</span><?php endif; ?></h2>
        <a href="notifications.php" class="btn btn-outline btn-sm">View All</a>
      </div>
      <div class="card-body" style="padding:0">
        <?php if(empty($recentNotifs)): ?>
          <div class="empty-state"><i class="fa fa-bell-slash"></i><h4>No notifications yet</h4></div>
        <?php else: ?>
        <?php $typeIcons=['appointment'=>'fa-calendar-check','medicine'=>'fa-pills','general'=>'fa-info-circle','alert'=>'fa-exclamation-triangle']; ?>
        <?php foreach($recentNotifs as $n): ?>
        <div class="notif-item <?= !$n['is_read']?'notif-unread':'' ?>" style="padding:12px 18px">
          <div class="notif-icon <?= $n['type'] ?>"><i class="fa <?= $typeIcons[$n['type']]??'fa-bell' ?>"></i></div>
          <div class="notif-content">
            <h4><?= htmlspecialchars($n['title']) ?></h4>
            <p><?= htmlspecialchars(substr($n['message'],0,70)) ?><?= strlen($n['message'])>70?'...':'' ?></p>
            <time><?= date('M d, Y · h:i A', strtotime($n['created_at'])) ?></time>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <!-- Patient Info Card (if exists) -->
  <?php if($patient): ?>
  <div class="page-card" style="margin-top:24px">
    <div class="card-header"><h2><i class="fa fa-id-card"></i> My Health Profile</h2></div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px">
        <?php $fields=[['fa-id-card','Student ID',$patient['student_id']??'N/A'],['fa-tint','Blood Type',$patient['blood_type']??'Unknown'],['fa-venus-mars','Gender',$patient['gender']??'N/A'],['fa-birthday-cake','Date of Birth',$patient['date_of_birth']?date('M d, Y',strtotime($patient['date_of_birth'])):'N/A'],['fa-phone','Phone',$patient['phone']??'N/A'],['fa-allergies','Allergies',$patient['allergies']??'None known']]; ?>
        <?php foreach($fields as [$icon,$label,$value]): ?>
        <div style="background:var(--bg);border-radius:10px;padding:14px">
          <div style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px"><i class="fa <?= $icon ?>"></i> <?= $label ?></div>
          <div style="font-size:14px;font-weight:700"><?= htmlspecialchars($value) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

</main>
<script src="../js/dashboard.js"></script>
</body>
</html>
