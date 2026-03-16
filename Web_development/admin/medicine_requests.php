<?php
require_once '../config/init.php';
Auth::checkAdmin();

$medReqClass  = new MedicineRequest();
$medicineClass= new Medicine();
$notifClass   = new Notification();
$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    if ($postAction === 'update_status') {
        $reqId  = (int)$_POST['req_id'];
        $status = $_POST['status'];
        $notes  = $_POST['admin_notes'] ?? '';

        $db  = Database::getInstance();
        $req = $db->fetchOne("SELECT mr.*, m.name AS med_name FROM medicine_requests mr LEFT JOIN medicines m ON mr.medicine_id = m.id WHERE mr.id = $reqId");

        if ($req && $status === 'Released') {
            $result = $medicineClass->dispense((int)$req['medicine_id'], (int)$req['quantity_requested'], $reqId, Auth::currentUser()['id']);
            if (!$result['success']) {
                $msg = $result['message']; $msgType = 'danger';
            } else {
                $medReqClass->updateStatus($reqId, $status, $notes);
                $notifClass->send((int)$req['user_id'], 'Medicine Ready for Pick-up',
                    $req['quantity_requested'].' '.$req['med_name'].' is ready for pick-up at the clinic.',  'medicine');
                $msg = 'Medicine dispensed and request updated.'; $msgType = 'success';
            }
        } elseif ($req) {
            $medReqClass->updateStatus($reqId, $status, $notes);
            $notifClass->send((int)$req['user_id'], 'Medicine Request '.$status,
                'Your request for '.$req['med_name'].' has been '.$status.($notes?'. Note: '.$notes:''), 'medicine');
            $msg = 'Request updated.'; $msgType = 'success';
        }
    }
}

$requests     = $medReqClass->getAll();
$statusColors = ['Pending'=>'badge-warning','Approved'=>'badge-info','Released'=>'badge-success','Rejected'=>'badge-danger'];
$pageTitle    = 'Medicine Requests';
$pageSubtitle = 'Student medicine request management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Medicine Requests – DSRRM Clinic</title>
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

  <div class="page-card">
    <div class="card-header">
      <h2><i class="fa fa-hand-holding-medical"></i> Medicine Requests (<?= count($requests) ?>)</h2>
      <div class="search-bar">
        <i class="fa fa-search"></i>
        <input type="text" placeholder="Search..." oninput="searchTable(this,'medReqTable')">
      </div>
    </div>
    <div class="card-body" style="padding:0">
      <?php if(empty($requests)): ?>
        <div class="empty-state"><i class="fa fa-hand-holding-medical"></i><h4>No medicine requests</h4></div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="medReqTable">
          <thead><tr><th>#</th><th>Student</th><th>Medicine</th><th>Qty</th><th>Reason</th><th>Status</th><th>Requested</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach($requests as $i => $r): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><div class="name-cell"><div class="patient-avatar"><?= strtoupper(substr($r['user_name']??'?',0,1)) ?></div><div><?= htmlspecialchars($r['user_name']??'-') ?><br><small style="color:var(--muted)"><?= htmlspecialchars($r['student_id']??'') ?></small></div></div></td>
            <td><strong><?= htmlspecialchars($r['medicine_name']??'-') ?></strong></td>
            <td><?= $r['quantity_requested'] ?> <?= htmlspecialchars($r['unit']??'') ?></td>
            <td style="max-width:160px"><small><?= htmlspecialchars(substr($r['reason']??'',0,60)) ?><?= strlen($r['reason']??'')>60?'...':'' ?></small></td>
            <td><span class="badge <?= $statusColors[$r['status']] ?? 'badge-muted' ?>"><?= $r['status'] ?></span></td>
            <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
            <td>
              <?php if(in_array($r['status'],['Pending','Approved'])): ?>
              <button onclick='openMedReqModal(<?= $r['id'] ?>, "<?= htmlspecialchars($r['medicine_name']??'') ?>", "<?= htmlspecialchars($r['user_name']??'') ?>", "<?= $r['status'] ?>")' class="btn btn-sm btn-primary"><i class="fa fa-check"></i> Review</button>
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
<div class="modal-overlay" id="medReqModal">
  <div class="modal-box" style="max-width:420px">
    <div class="modal-header"><h3 id="medReqTitle">Review Medicine Request</h3><button class="modal-close" onclick="closeModal('medReqModal')">✕</button></div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="req_id" id="medReqId">
        <div class="form-group">
          <label>Decision *</label>
          <select name="status" id="medReqStatus" required>
            <option value="Approved">✅ Approve Request</option>
            <option value="Released">📦 Approve & Release (Dispense now)</option>
            <option value="Rejected">❌ Reject</option>
          </select>
        </div>
        <div class="form-group" style="margin-top:12px">
          <label>Notes to Student</label>
          <textarea name="admin_notes" rows="2" placeholder="Optional note..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('medReqModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Submit</button>
      </div>
    </form>
  </div>
</div>

<script src="../js/dashboard.js"></script>
<script>
function searchTable(inp,tid){const v=inp.value.toLowerCase();document.querySelectorAll('#'+tid+' tbody tr').forEach(r=>r.style.display=r.textContent.toLowerCase().includes(v)?'':'none');}
function openMedReqModal(id, medName, userName, currentStatus){
  document.getElementById('medReqId').value=id;
  document.getElementById('medReqTitle').textContent='Review: '+medName+' for '+userName;
  document.getElementById('medReqStatus').value=currentStatus==='Approved'?'Released':'Approved';
  openModal('medReqModal');
}
</script>
</body>
</html>
