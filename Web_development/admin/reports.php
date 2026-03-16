<?php
require_once '../config/init.php';
Auth::checkAdmin();

$db = Database::getInstance();

// Date range
$startDate = $_GET['start'] ?? date('Y-m-01');
$endDate   = $_GET['end']   ?? date('Y-m-d');
$sd = $db->escape($startDate);
$ed = $db->escape($endDate);

// Summary stats
$totalVisits      = $db->fetchCount("SELECT COUNT(*) FROM medical_records WHERE visit_date BETWEEN '$sd' AND '$ed'");
$totalAppts       = $db->fetchCount("SELECT COUNT(*) FROM appointments WHERE appointment_date BETWEEN '$sd' AND '$ed'");
$completedAppts   = $db->fetchCount("SELECT COUNT(*) FROM appointments WHERE appointment_date BETWEEN '$sd' AND '$ed' AND status='Completed'");
$newPatients      = $db->fetchCount("SELECT COUNT(*) FROM patients WHERE DATE(created_at) BETWEEN '$sd' AND '$ed'");
$medDispensed     = $db->fetchCount("SELECT COUNT(*) FROM medicine_requests WHERE DATE(updated_at) BETWEEN '$sd' AND '$ed' AND status='Released'");
$consultRequests  = $db->fetchCount("SELECT COUNT(*) FROM consultation_requests WHERE DATE(created_at) BETWEEN '$sd' AND '$ed'");

// Top diagnoses
$topDiagnoses = $db->fetchAll("SELECT diagnosis, COUNT(*) as count FROM medical_records
    WHERE visit_date BETWEEN '$sd' AND '$ed' AND diagnosis IS NOT NULL AND diagnosis != ''
    GROUP BY diagnosis ORDER BY count DESC LIMIT 10");

// Daily visits chart data
$dailyVisits = $db->fetchAll("SELECT visit_date, COUNT(*) as count FROM medical_records
    WHERE visit_date BETWEEN '$sd' AND '$ed' GROUP BY visit_date ORDER BY visit_date ASC");

// Appointment status breakdown
$apptStatus = $db->fetchAll("SELECT status, COUNT(*) as count FROM appointments
    WHERE appointment_date BETWEEN '$sd' AND '$ed' GROUP BY status");

// Top medicines dispensed
$topMeds = $db->fetchAll("SELECT m.name, SUM(mr.quantity_requested) as total
    FROM medicine_requests mr JOIN medicines m ON mr.medicine_id = m.id
    WHERE mr.status = 'Released' AND DATE(mr.updated_at) BETWEEN '$sd' AND '$ed'
    GROUP BY mr.medicine_id ORDER BY total DESC LIMIT 8");

// Recent visits
$recentVisits = $db->fetchAll("SELECT mr.visit_date, p.full_name AS patient, p.student_id,
    mr.chief_complaint, mr.diagnosis, mr.created_by
    FROM medical_records mr LEFT JOIN patients p ON mr.patient_id = p.id
    WHERE mr.visit_date BETWEEN '$sd' AND '$ed' ORDER BY mr.visit_date DESC LIMIT 20");

$pageTitle    = 'Clinic Reports';
$pageSubtitle = 'Analytics & summary for ' . date('M d', strtotime($startDate)) . ' – ' . date('M d, Y', strtotime($endDate));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports – DSRRM Clinic</title>
<link rel="stylesheet" href="../css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
</head>
<body>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/topbar.php'; ?>

<main class="main-content">
  <!-- Date Filter -->
  <div class="page-card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 22px">
      <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
        <div class="form-group"><label>From Date</label><input type="date" name="start" value="<?= htmlspecialchars($startDate) ?>"></div>
        <div class="form-group"><label>To Date</label><input type="date" name="end" value="<?= htmlspecialchars($endDate) ?>"></div>
        <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Apply Filter</button>
        <a href="reports.php" class="btn btn-outline"><i class="fa fa-redo"></i> This Month</a>
        <a href="reports.php?start=<?= date('Y-m-d', strtotime('-7 days')) ?>&end=<?= date('Y-m-d') ?>" class="btn btn-outline">Last 7 Days</a>
        <a href="reports.php?start=<?= date('Y-01-01') ?>&end=<?= date('Y-m-d') ?>" class="btn btn-outline">This Year</a>
      </form>
    </div>
  </div>

  <!-- Summary Stats -->
  <div class="stats-grid">
    <div class="stat-card blue"><div class="stat-icon"><i class="fa fa-stethoscope"></i></div><div class="stat-info"><h3><?= $totalVisits ?></h3><p>Clinic Visits</p></div></div>
    <div class="stat-card green"><div class="stat-icon"><i class="fa fa-calendar-check"></i></div><div class="stat-info"><h3><?= $totalAppts ?></h3><p>Appointments</p></div></div>
    <div class="stat-card orange"><div class="stat-icon"><i class="fa fa-check-circle"></i></div><div class="stat-info"><h3><?= $completedAppts ?></h3><p>Completed Appts</p></div></div>
    <div class="stat-card teal"><div class="stat-icon"><i class="fa fa-user-plus"></i></div><div class="stat-info"><h3><?= $newPatients ?></h3><p>New Patients</p></div></div>
    <div class="stat-card green"><div class="stat-icon"><i class="fa fa-pills"></i></div><div class="stat-info"><h3><?= $medDispensed ?></h3><p>Medicines Released</p></div></div>
    <div class="stat-card blue"><div class="stat-icon"><i class="fa fa-comments-medical"></i></div><div class="stat-info"><h3><?= $consultRequests ?></h3><p>Consult Requests</p></div></div>
  </div>

  <!-- Charts Row -->
  <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-bottom:24px">
    <div class="page-card">
      <div class="card-header"><h2><i class="fa fa-chart-line"></i> Daily Clinic Visits</h2></div>
      <div class="card-body"><canvas id="visitsChart" height="120"></canvas></div>
    </div>
    <div class="page-card">
      <div class="card-header"><h2><i class="fa fa-chart-pie"></i> Appointment Status</h2></div>
      <div class="card-body"><canvas id="apptChart" height="160"></canvas></div>
    </div>
  </div>

  <!-- Top Diagnoses & Top Medicines -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px">
    <div class="page-card">
      <div class="card-header"><h2><i class="fa fa-diagnoses"></i> Top Diagnoses</h2></div>
      <div class="card-body" style="padding:0">
        <?php if(empty($topDiagnoses)): ?>
          <div class="empty-state"><i class="fa fa-notes-medical"></i><h4>No data available</h4></div>
        <?php else: ?>
        <?php $maxCount = max(array_column($topDiagnoses,'count')); ?>
        <?php foreach($topDiagnoses as $i => $d): ?>
        <div style="padding:10px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px">
          <span style="font-size:13px;font-weight:800;color:var(--muted);width:20px"><?= $i+1 ?></span>
          <div style="flex:1">
            <div style="font-size:13px;font-weight:700;margin-bottom:4px"><?= htmlspecialchars($d['diagnosis']) ?></div>
            <div style="height:6px;background:var(--bg);border-radius:3px"><div style="height:6px;background:var(--primary);border-radius:3px;width:<?= ($d['count']/$maxCount*100) ?>%"></div></div>
          </div>
          <span class="badge badge-info"><?= $d['count'] ?></span>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="page-card">
      <div class="card-header"><h2><i class="fa fa-pills"></i> Top Medicines Dispensed</h2></div>
      <div class="card-body" style="padding:0">
        <?php if(empty($topMeds)): ?>
          <div class="empty-state"><i class="fa fa-pills"></i><h4>No data available</h4></div>
        <?php else: ?>
        <?php $maxMed = max(array_column($topMeds,'total')); ?>
        <?php foreach($topMeds as $i => $m): ?>
        <div style="padding:10px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px">
          <span style="font-size:13px;font-weight:800;color:var(--muted);width:20px"><?= $i+1 ?></span>
          <div style="flex:1">
            <div style="font-size:13px;font-weight:700;margin-bottom:4px"><?= htmlspecialchars($m['name']) ?></div>
            <div style="height:6px;background:var(--bg);border-radius:3px"><div style="height:6px;background:var(--success);border-radius:3px;width:<?= ($m['total']/$maxMed*100) ?>%"></div></div>
          </div>
          <span class="badge badge-success"><?= $m['total'] ?></span>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Recent Visit Log -->
  <div class="page-card">
    <div class="card-header"><h2><i class="fa fa-history"></i> Recent Visit Log (<?= count($recentVisits) ?>)</h2></div>
    <div class="card-body" style="padding:0">
      <?php if(empty($recentVisits)): ?>
        <div class="empty-state"><i class="fa fa-history"></i><h4>No visits recorded in this period</h4></div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Date</th><th>Patient</th><th>Student ID</th><th>Chief Complaint</th><th>Diagnosis</th></tr></thead>
          <tbody>
          <?php foreach($recentVisits as $v): ?>
          <tr>
            <td><?= date('M d, Y', strtotime($v['visit_date'])) ?></td>
            <td><div class="name-cell"><div class="patient-avatar"><?= strtoupper(substr($v['patient']??'?',0,1)) ?></div><?= htmlspecialchars($v['patient']??'—') ?></div></td>
            <td><span style="font-family:monospace;font-size:12px"><?= htmlspecialchars($v['student_id']??'—') ?></span></td>
            <td><?= htmlspecialchars(substr($v['chief_complaint']??'',0,50)) ?><?= strlen($v['chief_complaint']??'')>50?'...':'' ?></td>
            <td><?= htmlspecialchars(substr($v['diagnosis']??'—',0,50)) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<script src="../js/dashboard.js"></script>
<script>
// Daily Visits Chart
const dvData = <?= json_encode(array_map(fn($r)=>['date'=>date('M d',strtotime($r['visit_date'])),'count'=>(int)$r['count']], $dailyVisits)) ?>;
if(dvData.length > 0){
  new Chart(document.getElementById('visitsChart'), {
    type:'line',
    data:{labels:dvData.map(d=>d.date),datasets:[{label:'Visits',data:dvData.map(d=>d.count),borderColor:'#003d8f',backgroundColor:'rgba(0,61,143,.12)',fill:true,tension:.4,pointRadius:4,pointBackgroundColor:'#003d8f'}]},
    options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}
  });
} else {
  document.getElementById('visitsChart').parentElement.innerHTML = '<div class="empty-state"><i class="fa fa-chart-line"></i><h4>No visit data</h4></div>';
}

// Appointment Status Chart
const asData = <?= json_encode($apptStatus) ?>;
if(asData.length > 0){
  const colors = {Pending:'#f39c12',Approved:'#2ecc71',Completed:'#3498db',Cancelled:'#e74c3c','No-show':'#95a5a6'};
  new Chart(document.getElementById('apptChart'), {
    type:'doughnut',
    data:{labels:asData.map(d=>d.status),datasets:[{data:asData.map(d=>d.count),backgroundColor:asData.map(d=>colors[d.status]||'#ddd'),borderWidth:2,borderColor:'#fff'}]},
    options:{responsive:true,plugins:{legend:{position:'bottom',labels:{font:{size:11}}}}}
  });
} else {
  document.getElementById('apptChart').parentElement.innerHTML = '<div class="empty-state"><i class="fa fa-chart-pie"></i><h4>No appointment data</h4></div>';
}
</script>
</body>
</html>
