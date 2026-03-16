<?php
require_once '../config/init.php';
Auth::checkUser();

$userId       = (int)$_SESSION['user_id'];
$medReqClass  = new MedicineRequest();
$medicineClass= new Medicine();
$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'user_id'            => $userId,
        'medicine_id'        => (int)$_POST['medicine_id'],
        'quantity_requested' => max(1,(int)$_POST['quantity_requested']),
        'reason'             => $_POST['reason'] ?? '',
    ];
    $result = $medReqClass->create($data);
    $msg = $result['message']; $msgType = $result['success']?'success':'danger';
}

$medicines    = $medicineClass->getAll();
$myRequests   = $medReqClass->getByUser($userId);
$statusColors = ['Pending'=>'badge-warning','Approved'=>'badge-info','Released'=>'badge-success','Rejected'=>'badge-danger'];
$pageTitle    = 'Medicine Request';
$pageSubtitle = 'Request available medicines based on clinic policy';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Medicine Request – DSRRM Clinic</title>
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

  <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:24px;align-items:start">

    <!-- Request Form -->
    <div class="page-card">
      <div class="card-header"><h2><i class="fa fa-hand-holding-medical"></i> Request Medicine</h2></div>
      <div class="card-body">
        <div class="alert alert-warning" style="margin-bottom:16px;font-size:13px"><i class="fa fa-exclamation-triangle"></i> Medicine is subject to availability and clinic policy. Maximum 1 request per medicine per day.</div>
        <form method="POST">
          <div class="form-group">
            <label>Select Medicine *</label>
            <select name="medicine_id" required onchange="updateStock(this)">
              <option value="">-- Choose Medicine --</option>
              <?php foreach($medicines as $m): ?>
              <?php if($m['quantity'] > 0): ?>
              <option value="<?= $m['id'] ?>" data-stock="<?= $m['quantity'] ?>" data-unit="<?= htmlspecialchars($m['unit']) ?>">
                <?= htmlspecialchars($m['name']) ?> (<?= $m['quantity'] ?> <?= htmlspecialchars($m['unit']) ?> available)
              </option>
              <?php endif; ?>
              <?php endforeach; ?>
            </select>
          </div>
          <div id="stockInfo" style="display:none;margin-top:8px;padding:10px 14px;background:var(--bg);border-radius:8px;font-size:13px;color:var(--muted)">
            <i class="fa fa-info-circle"></i> Available: <strong id="stockQty"></strong>
          </div>
          <div class="form-group" style="margin-top:14px">
            <label>Quantity Requested *</label>
            <input type="number" name="quantity_requested" min="1" value="1" required id="qtyInput">
          </div>
          <div class="form-group" style="margin-top:14px">
            <label>Reason / Justification *</label>
            <textarea name="reason" rows="4" placeholder="Brief description of why you need this medicine..." required></textarea>
          </div>
          <button type="submit" class="btn btn-primary" style="margin-top:14px;width:100%"><i class="fa fa-paper-plane"></i> Submit Request</button>
        </form>
      </div>
    </div>

    <!-- My Requests -->
    <div class="page-card">
      <div class="card-header"><h2><i class="fa fa-list"></i> My Requests (<?= count($myRequests) ?>)</h2></div>
      <div class="card-body" style="padding:0">
        <?php if(empty($myRequests)): ?>
          <div class="empty-state"><i class="fa fa-pills"></i><h4>No requests yet</h4><p>Submit a medicine request to get started.</p></div>
        <?php else: ?>
        <?php foreach($myRequests as $r): ?>
        <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px">
            <div style="flex:1">
              <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px">
                <strong style="font-size:14px"><?= htmlspecialchars($r['medicine_name']??'—') ?></strong>
                <span style="font-size:12px;color:var(--muted)"><?= $r['quantity_requested'] ?> <?= htmlspecialchars($r['unit']??'') ?></span>
              </div>
              <p style="font-size:12px;color:var(--muted)"><?= htmlspecialchars(substr($r['reason']??'',0,80)) ?><?= strlen($r['reason']??'')>80?'...':'' ?></p>
              <?php if($r['admin_notes']): ?>
              <div style="margin-top:6px;background:var(--bg);padding:6px 10px;border-radius:6px;font-size:12px;color:var(--muted)">
                <i class="fa fa-comment-medical"></i> <?= htmlspecialchars($r['admin_notes']) ?>
              </div>
              <?php endif; ?>
              <?php if($r['status']==='Released'): ?>
              <div class="alert alert-success" style="margin-top:8px;font-size:12px;padding:8px 12px"><i class="fa fa-check-circle"></i> <strong>Ready for pick-up at the clinic!</strong></div>
              <?php endif; ?>
            </div>
            <div style="text-align:right;flex-shrink:0">
              <span class="badge <?= $statusColors[$r['status']] ?? 'badge-muted' ?>"><?= $r['status'] ?></span>
              <div style="font-size:11px;color:var(--muted);margin-top:4px"><?= date('M d, Y', strtotime($r['created_at'])) ?></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

  </div>
</main>
<script src="../js/dashboard.js"></script>
<script>
function updateStock(sel){
  const opt=sel.options[sel.selectedIndex];
  const stock=parseInt(opt.dataset.stock||0);
  const unit=opt.dataset.unit||'';
  const info=document.getElementById('stockInfo');
  const qty=document.getElementById('qtyInput');
  if(sel.value){
    document.getElementById('stockQty').textContent=stock+' '+unit;
    info.style.display='block';
    qty.max=stock;
  } else {
    info.style.display='none';
    qty.removeAttribute('max');
  }
}
</script>
</body>
</html>
