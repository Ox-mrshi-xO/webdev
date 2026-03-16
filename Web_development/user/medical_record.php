<?php
require_once '../config/init.php';
Auth::checkUser();

$userId       = (int)$_SESSION['user_id'];
$patientClass = new Patient();
$mrClass      = new MedicalRecord();

$patient = $patientClass->getByUserId($userId);
$records = $patient ? $mrClass->getByPatient($patient['id']) : [];

$pageTitle    = 'My Medical Record';
$pageSubtitle = 'Your past check-ups and prescriptions';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Medical Record – DSRRM Clinic</title>
<link rel="stylesheet" href="../css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/topbar.php'; ?>

<main class="main-content">

  <?php if(!$patient): ?>
  <div class="alert alert-info"><i class="fa fa-info-circle"></i> Your patient record hasn't been set up yet. Please visit the clinic or contact the admin.</div>
  <?php else: ?>

  <!-- Health Profile -->
  <div class="page-card" style="margin-bottom:24px">
    <div class="card-header"><h2><i class="fa fa-id-card-alt"></i> Health Profile</h2></div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px">
        <?php $fields=[['fa-id-card','Student ID',$patient['student_id']??'N/A'],['fa-user','Full Name',$patient['full_name']],['fa-venus-mars','Gender',$patient['gender']??'N/A'],['fa-tint','Blood Type',$patient['blood_type']??'Unknown'],['fa-birthday-cake','Date of Birth',$patient['date_of_birth']?date('M d, Y',strtotime($patient['date_of_birth'])):'N/A'],['fa-phone','Phone',$patient['phone']??'N/A'],['fa-exclamation-circle','Allergies',$patient['allergies']??'None known'],['fa-user-friends','Emergency Contact',$patient['emergency_contact']??'N/A']]; ?>
        <?php foreach($fields as [$icon,$label,$value]): ?>
        <div style="background:var(--bg);border-radius:10px;padding:14px">
          <div style="font-size:10px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px"><i class="fa <?= $icon ?>"></i> <?= $label ?></div>
          <div style="font-size:14px;font-weight:700"><?= htmlspecialchars($value) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Visit History -->
  <div class="page-card">
    <div class="card-header">
      <h2><i class="fa fa-history"></i> Visit History (<?= count($records) ?> records)</h2>
      <div class="search-bar">
        <i class="fa fa-search"></i>
        <input type="text" placeholder="Search records..." oninput="searchTable(this,'recTable')">
      </div>
    </div>
    <div class="card-body" style="padding:0">
      <?php if(empty($records)): ?>
        <div class="empty-state"><i class="fa fa-notes-medical"></i><h4>No visit records yet</h4><p>Your medical history will appear here after clinic visits.</p></div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="recTable">
          <thead><tr><th>Date</th><th>Chief Complaint</th><th>Diagnosis</th><th>Prescription</th><th>Vitals</th><th>Follow-up</th><th>Details</th></tr></thead>
          <tbody>
          <?php foreach($records as $r): ?>
          <tr>
            <td><strong><?= date('M d, Y', strtotime($r['visit_date'])) ?></strong><?= $r['visit_time']?'<br><small style="color:var(--muted)">'.date('h:i A',strtotime($r['visit_time'])).'</small>':'' ?></td>
            <td><?= htmlspecialchars(substr($r['chief_complaint']??'—',0,50)) ?><?= strlen($r['chief_complaint']??'')>50?'...':'' ?></td>
            <td><?= htmlspecialchars(substr($r['diagnosis']??'—',0,50)) ?><?= strlen($r['diagnosis']??'')>50?'...':'' ?></td>
            <td><?= htmlspecialchars(substr($r['prescription']??'—',0,40)) ?><?= strlen($r['prescription']??'')>40?'...':'' ?></td>
            <td style="font-size:12px">
              <?php if($r['blood_pressure']): ?><div>BP: <?= htmlspecialchars($r['blood_pressure']) ?></div><?php endif; ?>
              <?php if($r['temperature']): ?><div>Temp: <?= htmlspecialchars($r['temperature']) ?>°C</div><?php endif; ?>
            </td>
            <td><?= $r['follow_up_date']?'<span class="badge badge-warning">'.date('M d, Y',strtotime($r['follow_up_date'])).'</span>':'<span style="color:var(--muted)">—</span>' ?></td>
            <td>
              <button onclick='viewRecord(<?= htmlspecialchars(json_encode($r)) ?>)' class="btn btn-sm btn-outline"><i class="fa fa-eye"></i> View</button>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <?php endif; ?>
</main>

<!-- View Record Modal -->
<div class="modal-overlay" id="viewRecordModal">
  <div class="modal-box" style="max-width:680px">
    <div class="modal-header"><h3 id="vrTitle"><i class="fa fa-notes-medical"></i> Visit Details</h3><button class="modal-close" onclick="closeModal('viewRecordModal')">✕</button></div>
    <div class="modal-body" id="vrBody"></div>
    <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('viewRecordModal')">Close</button></div>
  </div>
</div>

<script src="../js/dashboard.js"></script>
<script>
function searchTable(inp,tid){const v=inp.value.toLowerCase();document.querySelectorAll('#'+tid+' tbody tr').forEach(r=>r.style.display=r.textContent.toLowerCase().includes(v)?'':'none');}
function viewRecord(r){
  document.getElementById('vrTitle').innerHTML='<i class="fa fa-notes-medical"></i> Visit on '+r.visit_date;
  const vt=(k,v)=>v?`<tr><td style="font-weight:700;width:38%;padding:9px 14px;border-bottom:1px solid #eee;color:var(--muted);font-size:13px">${k}</td><td style="padding:9px 14px;border-bottom:1px solid #eee;font-size:14px">${String(v).replace(/\n/g,'<br>')}</td></tr>`:'';
  document.getElementById('vrBody').innerHTML=`<table style="width:100%"><tbody>
    ${vt('Visit Date',r.visit_date)}${vt('Visit Time',r.visit_time)}
    ${vt('Chief Complaint',r.chief_complaint)}${vt('Diagnosis',r.diagnosis)}
    ${vt('Treatment',r.treatment)}${vt('Prescription',r.prescription)}
    ${vt('Blood Pressure',r.blood_pressure)}${vt('Temperature',r.temperature?r.temperature+'°C':'')}
    ${vt('Pulse Rate',r.pulse_rate?r.pulse_rate+' bpm':'')}
    ${vt('Weight',r.weight?r.weight+' kg':'')}${vt('Height',r.height?r.height+' cm':'')}
    ${vt('Follow-up Date',r.follow_up_date)}${vt("Doctor's Notes",r.doctor_notes)}</tbody></table>`;
  openModal('viewRecordModal');
}
</script>
</body>
</html>
