<?php
require_once '../config/init.php';
Auth::checkAdmin();

$apptClass    = new Appointment();
$patientClass = new Patient();
$notifClass   = new Notification();
$msg = ''; $msgType = 'success';
$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id'] ?? 0);
$filter = $_GET['filter'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    if ($postAction === 'create') {
        $data = $_POST;
        $data['created_by'] = Auth::currentUser()['id'];
        $result = $apptClass->create($data);
        $msg = $result['message']; $msgType = $result['success']?'success':'danger';
        // Notify user if user_id is linked
        if ($result['success'] && !empty($data['user_id'])) {
            $notifClass->send((int)$data['user_id'], 'Appointment Scheduled',
                'Your appointment on '.$data['appointment_date'].' at '.$data['appointment_time'].' has been scheduled.','appointment');
        }
        $action = '';
    } elseif ($postAction === 'update_status') {
        $apptId   = (int)$_POST['appt_id'];
        $status   = $_POST['status'];
        $notes    = $_POST['notes'] ?? '';
        $result   = $apptClass->updateStatus($apptId, $status, $notes);
        $msg = $result['message']; $msgType = 'success';
        // Notify user
        $appt = $apptClass->getById($apptId);
        if ($appt && !empty($appt['user_id'])) {
            $notifClass->send((int)$appt['user_id'], 'Appointment '.$status,
                'Your appointment on '.$appt['appointment_date'].' has been updated to: '.$status.($notes?'. Note: '.$notes:''),'appointment');
        }
    } elseif ($postAction === 'delete') {
        $result = $apptClass->delete((int)$_POST['appt_id']);
        $msg = $result['message']; $msgType = 'success';
    }
}

$appointments = $apptClass->getAll($filter);
$patients     = $patientClass->getAll();
$db           = Database::getInstance();
$users        = $db->fetchAll("SELECT id, full_name, student_id FROM users WHERE role='user' AND is_active=1 ORDER BY full_name");

$statusColors = ['Pending'=>'badge-warning','Approved'=>'badge-success','Completed'=>'badge-info','Cancelled'=>'badge-danger','No-show'=>'badge-muted'];
$pageTitle    = 'Appointments';
$pageSubtitle = 'Manage and schedule clinic appointments';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Appointments – DSRRM Clinic</title>
<link rel="stylesheet" href="../css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/topbar.php'; ?>

<main class="main-content">
  <?php if($msg): ?>
    <div class="alert alert-<?= $msgType ?> alert-auto"><i class="fa fa-info-circle"></i> <?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <!-- Filter Tabs -->
  <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
    <?php foreach([''=>'All','Pending'=>'Pending','Approved'=>'Approved','Completed'=>'Completed','Cancelled'=>'Cancelled'] as $val => $label): ?>
    <a href="appointments.php<?= $val?'?filter='.$val:'' ?>" class="btn btn-sm <?= $filter===$val?'btn-primary':'btn-outline' ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>

  <div class="page-card">
    <div class="card-header">
      <h2><i class="fa fa-calendar-check"></i> <?= $filter?htmlspecialchars($filter).' Appointments':'All Appointments' ?> (<?= count($appointments) ?>)</h2>
      <div style="display:flex;gap:10px">
        <div class="search-bar">
          <i class="fa fa-search"></i>
          <input type="text" placeholder="Search..." oninput="searchTable(this,'apptTable')">
        </div>
        <button onclick="openModal('addApptModal')" class="btn btn-primary"><i class="fa fa-plus"></i> Add Appointment</button>
      </div>
    </div>
    <div class="card-body" style="padding:0">
      <?php if(empty($appointments)): ?>
        <div class="empty-state"><i class="fa fa-calendar"></i><h4>No appointments found</h4></div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="apptTable">
          <thead>
            <tr><th>#</th><th>Patient</th><th>Date & Time</th><th>Type</th><th>Reason</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
          <?php foreach($appointments as $i => $a): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td>
              <div class="name-cell">
                <div class="patient-avatar"><?= strtoupper(substr($a['patient_name']??'?',0,1)) ?></div>
                <div><?= htmlspecialchars($a['patient_name']??'Walk-in') ?><br>
                <small style="color:var(--muted)"><?= htmlspecialchars($a['student_id']??'') ?></small></div>
              </div>
            </td>
            <td><strong><?= date('M d, Y', strtotime($a['appointment_date'])) ?></strong><br><small style="color:var(--muted)"><?= date('h:i A', strtotime($a['appointment_time'])) ?></small></td>
            <td><?= htmlspecialchars($a['type']) ?></td>
            <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars(substr($a['reason']??'',0,50)) ?></td>
            <td><span class="badge <?= $statusColors[$a['status']] ?? 'badge-muted' ?>"><?= $a['status'] ?></span></td>
            <td>
              <div style="display:flex;gap:5px">
                <button onclick='openStatusModal(<?= $a['id'] ?>, "<?= htmlspecialchars($a['patient_name']??'Walk-in') ?>", "<?= $a['status'] ?>")' class="btn btn-sm btn-outline" title="Update Status"><i class="fa fa-edit"></i></button>
                <form method="POST" style="display:inline"><input type="hidden" name="action" value="delete"><input type="hidden" name="appt_id" value="<?= $a['id'] ?>"><button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this appointment?')"><i class="fa fa-trash"></i></button></form>
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
</main>

<!-- Add Appointment Modal -->
<div class="modal-overlay" id="addApptModal">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fa fa-calendar-plus"></i> Add Appointment</h3><button class="modal-close" onclick="closeModal('addApptModal')">✕</button></div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" value="create">
        <div class="form-row">
          <div class="form-group">
            <label>Patient *</label>
            <select name="patient_id" required>
              <option value="">-- Select Patient --</option>
              <?php foreach($patients as $p): ?>
              <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['full_name']) ?> (<?= htmlspecialchars($p['student_id']??'N/A') ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Date *</label>
            <input type="date" name="appointment_date" min="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label>Time *</label>
            <input type="time" name="appointment_time" required>
          </div>
          <div class="form-group">
            <label>Type</label>
            <select name="type">
              <?php foreach(['Check-up','Follow-up','Consultation','Emergency','Vaccination','Other'] as $t): ?>
              <option value="<?= $t ?>"><?= $t ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="status">
              <?php foreach(['Pending','Approved','Completed'] as $s): ?>
              <option value="<?= $s ?>"><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group" style="margin-top:12px"><label>Reason / Notes</label><textarea name="reason" rows="3" placeholder="Reason for appointment..."></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addApptModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Appointment</button>
      </div>
    </form>
  </div>
</div>

<!-- Update Status Modal -->
<div class="modal-overlay" id="statusModal">
  <div class="modal-box" style="max-width:420px">
    <div class="modal-header"><h3 id="statusModalTitle">Update Appointment Status</h3><button class="modal-close" onclick="closeModal('statusModal')">✕</button></div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="appt_id" id="statusApptId">
        <div class="form-group">
          <label>New Status</label>
          <select name="status" id="statusSelect">
            <?php foreach(['Pending','Approved','Completed','Cancelled','No-show'] as $s): ?>
            <option value="<?= $s ?>"><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin-top:12px">
          <label>Notes (optional)</label>
          <textarea name="notes" rows="2" placeholder="Additional notes..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('statusModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> Update Status</button>
      </div>
    </form>
  </div>
</div>

<script src="../js/dashboard.js"></script>
<script>
function searchTable(inp, tid) {
  const v = inp.value.toLowerCase();
  document.querySelectorAll('#'+tid+' tbody tr').forEach(r => r.style.display = r.textContent.toLowerCase().includes(v)?'':'none');
}
function openStatusModal(id, name, currentStatus) {
  document.getElementById('statusApptId').value = id;
  document.getElementById('statusModalTitle').textContent = 'Update Status — ' + name;
  document.getElementById('statusSelect').value = currentStatus;
  openModal('statusModal');
}
<?php if($action==='add'): ?>window.addEventListener('load',()=>openModal('addApptModal'));<?php endif; ?>
</script>
</body>
</html>
