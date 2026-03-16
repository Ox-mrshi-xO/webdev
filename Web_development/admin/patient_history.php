<?php
require_once '../config/init.php';
Auth::checkAdmin();

$patientClass = new Patient();
$mrClass      = new MedicalRecord();
$msg = ''; $msgType = 'success';

$patientId = (int)($_GET['patient_id'] ?? 0);
$action    = $_GET['action'] ?? '';
$id        = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    if ($postAction === 'create_record') {
        $data = $_POST;
        $data['created_by'] = Auth::currentUser()['id'];
        $result = $mrClass->create($data);
        $msg = $result['message']; $msgType = $result['success']?'success':'danger';
        $action = '';
    } elseif ($postAction === 'update_record') {
        $result = $mrClass->update((int)$_POST['id'], $_POST);
        $msg = $result['message']; $msgType = $result['success']?'success':'danger';
        $action = '';
    }
}
if ($action === 'delete' && $id) {
    $result = $mrClass->delete($id);
    $msg = $result['message']; $msgType = $result['success']?'success':'danger';
    $action = '';
}

$patients = $patientClass->getAll();
$patient  = $patientId ? $patientClass->getById($patientId) : null;
$records  = $patientId ? $mrClass->getByPatient($patientId) : [];
$editRecord = ($action === 'edit' && $id) ? $mrClass->getById($id) : null;
if ($editRecord) $patientId = (int)$editRecord['patient_id'];

$pageTitle    = 'Medical History';
$pageSubtitle = $patient ? 'Patient: ' . $patient['full_name'] : 'View patient visit records';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Medical History – DSRRM Clinic</title>
<link rel="stylesheet" href="../css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/topbar.php'; ?>

<main class="main-content">
  <?php if($msg): ?>
    <div class="alert alert-<?= $msgType ?> alert-auto"><i class="fa fa-info-circle"></i><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <!-- Patient selector -->
  <div class="page-card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 22px">
      <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
        <div class="form-group" style="flex:1;min-width:220px">
          <label>Select Patient</label>
          <select name="patient_id" onchange="this.form.submit()">
            <option value="">-- Choose a patient --</option>
            <?php foreach($patients as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $p['id']==$patientId?'selected':'' ?>><?= htmlspecialchars($p['full_name']) ?> (<?= htmlspecialchars($p['student_id']??'N/A') ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php if($patient): ?>
        <button type="button" onclick="openModal('addRecordModal')" class="btn btn-primary"><i class="fa fa-plus"></i> Add Visit Record</button>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <?php if($patient): ?>
  <!-- Patient Info Summary -->
  <div class="page-card" style="margin-bottom:20px">
    <div class="card-body">
      <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
        <div class="patient-avatar" style="width:60px;height:60px;font-size:22px;border-radius:16px"><?= strtoupper(substr($patient['full_name'],0,1)) ?></div>
        <div style="flex:1">
          <h2 style="font-size:20px;color:var(--primary)"><?= htmlspecialchars($patient['full_name']) ?></h2>
          <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:6px;font-size:13px;color:var(--muted)">
            <span><i class="fa fa-id-card"></i> <?= htmlspecialchars($patient['student_id']??'N/A') ?></span>
            <span><i class="fa fa-venus-mars"></i> <?= htmlspecialchars($patient['gender']??'N/A') ?></span>
            <span><i class="fa fa-tint"></i> <?= htmlspecialchars($patient['blood_type']??'N/A') ?></span>
            <span><i class="fa fa-phone"></i> <?= htmlspecialchars($patient['phone']??'N/A') ?></span>
            <?php if($patient['allergies']): ?><span><i class="fa fa-allergies" style="color:var(--danger)"></i> Allergies: <?= htmlspecialchars($patient['allergies']) ?></span><?php endif; ?>
          </div>
        </div>
        <a href="patients.php?action=edit&id=<?= $patient['id'] ?>" class="btn btn-outline btn-sm"><i class="fa fa-edit"></i> Edit Patient</a>
      </div>
    </div>
  </div>

  <!-- Medical Records Table -->
  <div class="page-card">
    <div class="card-header">
      <h2><i class="fa fa-history"></i> Visit Records (<?= count($records) ?>)</h2>
    </div>
    <div class="card-body" style="padding:0">
      <?php if(empty($records)): ?>
        <div class="empty-state"><i class="fa fa-notes-medical"></i><h4>No medical records found</h4><p>Add a visit record using the button above.</p></div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Visit Date</th><th>Chief Complaint</th><th>Diagnosis</th><th>Treatment</th><th>Prescription</th><th>Vitals</th><th>Recorded By</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach($records as $r): ?>
          <tr>
            <td><strong><?= date('M d, Y',strtotime($r['visit_date'])) ?></strong><?= $r['visit_time']?'<br><small>'.date('h:i A',strtotime($r['visit_time'])).'</small>':'' ?></td>
            <td><?= nl2br(htmlspecialchars(substr($r['chief_complaint']??'',0,50))) ?><?= strlen($r['chief_complaint']??'')>50?'...':'' ?></td>
            <td><?= nl2br(htmlspecialchars(substr($r['diagnosis']??'',0,50))) ?><?= strlen($r['diagnosis']??'')>50?'...':'' ?></td>
            <td><?= nl2br(htmlspecialchars(substr($r['treatment']??'',0,40))) ?><?= strlen($r['treatment']??'')>40?'...':'' ?></td>
            <td><?= nl2br(htmlspecialchars(substr($r['prescription']??'',0,40))) ?><?= strlen($r['prescription']??'')>40?'...':'' ?></td>
            <td style="font-size:12px">
              <?php if($r['blood_pressure']): ?><div>BP: <?= htmlspecialchars($r['blood_pressure']) ?></div><?php endif; ?>
              <?php if($r['temperature']): ?><div>Temp: <?= htmlspecialchars($r['temperature']) ?>°C</div><?php endif; ?>
              <?php if($r['pulse_rate']): ?><div>Pulse: <?= htmlspecialchars($r['pulse_rate']) ?> bpm</div><?php endif; ?>
            </td>
            <td><?= htmlspecialchars($r['created_by_name']??'-') ?><br><small style="color:var(--muted)"><?= date('M d',strtotime($r['created_at'])) ?></small></td>
            <td>
              <div style="display:flex;gap:5px">
                <button onclick="viewRecord(<?= htmlspecialchars(json_encode($r)) ?>)" class="btn btn-sm btn-outline"><i class="fa fa-eye"></i></button>
                <a href="patient_history.php?patient_id=<?= $patientId ?>&action=edit&id=<?= $r['id'] ?>" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a>
                <button onclick="confirmDelete('patient_history.php?patient_id=<?= $patientId ?>&action=delete&id=<?= $r['id'] ?>')" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php elseif(!empty($patients)): ?>
    <div class="empty-state" style="margin-top:40px"><i class="fa fa-user-injured"></i><h4>Select a patient to view their medical history</h4></div>
  <?php endif; ?>

</main>

<!-- Add Record Modal -->
<div class="modal-overlay" id="addRecordModal">
  <div class="modal-box" style="max-width:720px">
    <div class="modal-header"><h3><i class="fa fa-plus-circle"></i> Add Visit Record</h3><button class="modal-close" onclick="closeModal('addRecordModal')">✕</button></div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" value="create_record">
        <input type="hidden" name="patient_id" value="<?= $patientId ?>">
        <div class="form-row">
          <div class="form-group"><label>Visit Date *</label><input type="date" name="visit_date" value="<?= date('Y-m-d') ?>" required></div>
          <div class="form-group"><label>Visit Time</label><input type="time" name="visit_time"></div>
        </div>
        <div class="form-row" style="margin-top:12px">
          <div class="form-group"><label>Chief Complaint</label><textarea name="chief_complaint" rows="2"></textarea></div>
          <div class="form-group"><label>Diagnosis</label><textarea name="diagnosis" rows="2"></textarea></div>
          <div class="form-group"><label>Treatment</label><textarea name="treatment" rows="2"></textarea></div>
          <div class="form-group"><label>Prescription</label><textarea name="prescription" rows="2"></textarea></div>
        </div>
        <p style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin:14px 0 10px">Vital Signs</p>
        <div class="form-row">
          <div class="form-group"><label>Blood Pressure (mmHg)</label><input type="text" name="blood_pressure" placeholder="e.g. 120/80"></div>
          <div class="form-group"><label>Temperature (°C)</label><input type="text" name="temperature" placeholder="e.g. 36.5"></div>
          <div class="form-group"><label>Pulse Rate (bpm)</label><input type="text" name="pulse_rate" placeholder="e.g. 72"></div>
          <div class="form-group"><label>Weight (kg)</label><input type="text" name="weight" placeholder="e.g. 65"></div>
          <div class="form-group"><label>Height (cm)</label><input type="text" name="height" placeholder="e.g. 165"></div>
          <div class="form-group"><label>Follow-up Date</label><input type="date" name="follow_up_date"></div>
        </div>
        <div class="form-group" style="margin-top:12px"><label>Doctor's Notes</label><textarea name="doctor_notes" rows="2"></textarea></div>
      </div>
      <div class="modal-footer"><button class="modal-close btn btn-outline" type="button" onclick="closeModal('addRecordModal')">Cancel</button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Record</button></div>
    </form>
  </div>
</div>

<!-- View Record Modal -->
<div class="modal-overlay" id="viewRecordModal">
  <div class="modal-box" style="max-width:700px">
    <div class="modal-header"><h3 id="vrTitle">Visit Record</h3><button class="modal-close" onclick="closeModal('viewRecordModal')">✕</button></div>
    <div class="modal-body" id="vrBody"></div>
    <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('viewRecordModal')">Close</button></div>
  </div>
</div>

<script src="../js/dashboard.js"></script>
<script>
function viewRecord(r) {
  document.getElementById('vrTitle').innerHTML = '<i class="fa fa-notes-medical"></i> Visit on ' + r.visit_date;
  const vt = (k,v) => v ? `<tr><td style="font-weight:700;width:40%;padding:8px 12px;border-bottom:1px solid #eee;color:var(--muted);font-size:13px">${k}</td><td style="padding:8px 12px;border-bottom:1px solid #eee;font-size:14px">${String(v).replace(/\n/g,'<br>')}</td></tr>` : '';
  document.getElementById('vrBody').innerHTML = `<table style="width:100%"><tbody>
    ${vt('Visit Date', r.visit_date)}${vt('Visit Time', r.visit_time)}
    ${vt('Chief Complaint', r.chief_complaint)}${vt('Diagnosis', r.diagnosis)}
    ${vt('Treatment', r.treatment)}${vt('Prescription', r.prescription)}
    ${vt('Blood Pressure', r.blood_pressure)}${vt('Temperature', r.temperature ? r.temperature+'°C' : '')}
    ${vt('Pulse Rate', r.pulse_rate ? r.pulse_rate+' bpm' : '')}
    ${vt('Weight', r.weight ? r.weight+' kg' : '')}${vt('Height', r.height ? r.height+' cm' : '')}
    ${vt('Follow-up Date', r.follow_up_date)}${vt("Doctor's Notes", r.doctor_notes)}
    ${vt('Recorded By', r.created_by_name)}</tbody></table>`;
  openModal('viewRecordModal');
}
<?php if($action === 'add_record'): ?>window.addEventListener('load',()=>openModal('addRecordModal'));<?php endif; ?>
</script>
</body>
</html>
