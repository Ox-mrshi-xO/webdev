<?php
require_once '../config/init.php';
Auth::checkAdmin();

$consultClass = new ConsultationRequest();
$notifClass   = new Notification();
$apptClass    = new Appointment();
$patientClass = new Patient();
$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    if ($postAction === 'update_status') {
        $cId    = (int)$_POST['consult_id'];
        $status = $_POST['status'];
        $notes  = $_POST['admin_notes'] ?? '';
        $result = $consultClass->updateStatus($cId, $status, $notes);
        $msg = $result['message']; $msgType = 'success';
        // Notify user
        $db = Database::getInstance();
        $cr = $db->fetchOne("SELECT * FROM consultation_requests WHERE id = $cId");
        if ($cr) {
            $notifClass->send((int)$cr['user_id'], 'Consultation Request '.$status,
                'Your consultation request for '.$cr['preferred_date'].' has been '.$status.($notes?'. Note: '.$notes:''),'appointment');
            // If approved, auto-create appointment
            if ($status === 'Approved') {
                $patient = $patientClass->getByUserId((int)$cr['user_id']);
                if ($patient) {
                    $apptClass->create([
                        'patient_id'       => $patient['id'],
                        'user_id'          => $cr['user_id'],
                        'appointment_date' => $cr['preferred_date'],
                        'appointment_time' => $cr['preferred_time'],
                        'type'             => $cr['type'],
                        'status'           => 'Approved',
                        'reason'           => $cr['reason'],
                        'created_by'       => Auth::currentUser()['id'],
                    ]);
                }
            }
        }
    }
}

$requests = $consultClass->getAll();
$statusColors = ['Pending'=>'badge-warning','Approved'=>'badge-success','Rejected'=>'badge-danger','Completed'=>'badge-info'];
$pageTitle    = 'Consultation Requests';
$pageSubtitle = 'Online consultation requests from students';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Consultation Requests – DSRRM Clinic</title>
<link rel="stylesheet" href="../css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/topbar.php'; ?>

<main class="main-content">
  <?php if($msg): ?>
    <div class="alert alert-success alert-auto"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="page-card">
    <div class="card-header">
      <h2><i class="fa fa-comments-medical"></i> Consultation Requests (<?= count($requests) ?>)</h2>
      <div class="search-bar">
        <i class="fa fa-search"></i>
        <input type="text" placeholder="Search..." oninput="searchTable(this,'consultTable')">
      </div>
    </div>
    <div class="card-body" style="padding:0">
      <?php if(empty($requests)): ?>
        <div class="empty-state"><i class="fa fa-comments-medical"></i><h4>No consultation requests</h4><p>Student requests will appear here.</p></div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="consultTable">
          <thead><tr><th>#</th><th>Student</th><th>Preferred Date & Time</th><th>Type</th><th>Reason</th><th>Status</th><th>Requested On</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach($requests as $i => $r): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><div class="name-cell"><div class="patient-avatar"><?= strtoupper(substr($r['user_name']??'?',0,1)) ?></div><div><?= htmlspecialchars($r['user_name']??'-') ?><br><small style="color:var(--muted)"><?= htmlspecialchars($r['student_id']??$r['email']??'') ?></small></div></div></td>
            <td><strong><?= date('M d, Y', strtotime($r['preferred_date'])) ?></strong><br><small style="color:var(--muted)"><?= date('h:i A', strtotime($r['preferred_time'])) ?></small></td>
            <td><?= htmlspecialchars($r['type']) ?></td>
            <td style="max-width:180px"><small><?= htmlspecialchars(substr($r['reason'],0,80)) ?><?= strlen($r['reason'])>80?'...':'' ?></small></td>
            <td><span class="badge <?= $statusColors[$r['status']] ?? 'badge-muted' ?>"><?= $r['status'] ?></span></td>
            <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
            <td>
              <?php if($r['status'] === 'Pending'): ?>
              <button onclick='openConsultModal(<?= $r['id'] ?>, "<?= htmlspecialchars($r['user_name']??'') ?>")' class="btn btn-sm btn-primary"><i class="fa fa-check"></i> Review</button>
              <?php else: ?>
              <span style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($r['admin_notes']??'—') ?></span>
              <?php endif; ?>
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

<!-- Review Modal -->
<div class="modal-overlay" id="consultModal">
  <div class="modal-box" style="max-width:420px">
    <div class="modal-header"><h3 id="consultModalTitle">Review Request</h3><button class="modal-close" onclick="closeModal('consultModal')">✕</button></div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="consult_id" id="consultId">
        <div class="form-group">
          <label>Decision *</label>
          <select name="status" required>
            <option value="Approved">✅ Approve — Create appointment</option>
            <option value="Rejected">❌ Reject</option>
            <option value="Completed">✔ Mark Completed</option>
          </select>
        </div>
        <div class="form-group" style="margin-top:12px">
          <label>Admin Notes / Reason</label>
          <textarea name="admin_notes" rows="3" placeholder="Optional note to the student..."></textarea>
        </div>
        <div class="alert alert-info" style="margin-top:10px;font-size:12px"><i class="fa fa-info-circle"></i> Approving will automatically create an appointment for this student.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('consultModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Submit Decision</button>
      </div>
    </form>
  </div>
</div>

<script src="../js/dashboard.js"></script>
<script>
function searchTable(inp,tid){const v=inp.value.toLowerCase();document.querySelectorAll('#'+tid+' tbody tr').forEach(r=>r.style.display=r.textContent.toLowerCase().includes(v)?'':'none');}
function openConsultModal(id, name){ document.getElementById('consultId').value=id; document.getElementById('consultModalTitle').textContent='Review: '+name; openModal('consultModal'); }
</script>
</body>
</html>
