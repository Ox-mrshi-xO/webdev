<?php
require_once '../config/init.php';
Auth::checkUser();

$userId       = (int)$_SESSION['user_id'];
$consultClass = new ConsultationRequest();
$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    $data['user_id'] = $userId;
    $result = $consultClass->create($data);
    $msg = $result['message']; $msgType = $result['success']?'success':'danger';
}

$requests     = $consultClass->getByUser($userId);
$statusColors = ['Pending'=>'badge-warning','Approved'=>'badge-success','Rejected'=>'badge-danger','Completed'=>'badge-info'];
$pageTitle    = 'Online Consultation';
$pageSubtitle = 'Request clinic consultations or follow-up appointments';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Consultation – DSRRM Clinic</title>
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

  <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:24px;align-items:start;flex-wrap:wrap">

    <!-- Request Form -->
    <div class="page-card">
      <div class="card-header"><h2><i class="fa fa-plus-circle"></i> New Request</h2></div>
      <div class="card-body">
        <div class="alert alert-info" style="margin-bottom:16px"><i class="fa fa-info-circle"></i> Fill out the form below. The clinic will review and approve your request.</div>
        <form method="POST">
          <div class="form-group">
            <label>Consultation Type *</label>
            <select name="type" required>
              <option value="">-- Select Type --</option>
              <?php foreach(['Consultation','Follow-up','Check-up','Other'] as $t): ?>
              <option value="<?= $t ?>"><?= $t ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin-top:12px">
            <label>Preferred Date *</label>
            <input type="date" name="preferred_date" min="<?= date('Y-m-d',strtotime('+1 day')) ?>" required>
          </div>
          <div class="form-group" style="margin-top:12px">
            <label>Preferred Time *</label>
            <select name="preferred_time" required>
              <option value="">-- Select Time --</option>
              <?php
              $times = ['08:00','08:30','09:00','09:30','10:00','10:30','11:00','11:30','13:00','13:30','14:00','14:30','15:00','15:30','16:00'];
              foreach($times as $t): ?>
              <option value="<?= $t ?>"><?= date('h:i A',strtotime($t)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin-top:12px">
            <label>Reason / Concern *</label>
            <textarea name="reason" rows="4" placeholder="Describe your health concern or reason for consultation..." required></textarea>
          </div>
          <button type="submit" class="btn btn-primary" style="margin-top:14px;width:100%"><i class="fa fa-paper-plane"></i> Submit Request</button>
        </form>
      </div>
    </div>

    <!-- My Requests -->
    <div class="page-card">
      <div class="card-header"><h2><i class="fa fa-list"></i> My Requests (<?= count($requests) ?>)</h2></div>
      <div class="card-body" style="padding:0">
        <?php if(empty($requests)): ?>
          <div class="empty-state"><i class="fa fa-comments-medical"></i><h4>No requests yet</h4><p>Submit your first consultation request.</p></div>
        <?php else: ?>
        <?php foreach($requests as $r): ?>
        <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px">
            <div style="flex:1">
              <div style="font-size:14px;font-weight:700;margin-bottom:4px"><?= htmlspecialchars($r['type']) ?></div>
              <div style="font-size:12px;color:var(--muted);margin-bottom:6px">
                <i class="fa fa-calendar"></i> <?= date('M d, Y', strtotime($r['preferred_date'])) ?> &nbsp;
                <i class="fa fa-clock"></i> <?= date('h:i A', strtotime($r['preferred_time'])) ?>
              </div>
              <p style="font-size:13px;color:#555"><?= htmlspecialchars(substr($r['reason'],0,100)) ?><?= strlen($r['reason'])>100?'...':'' ?></p>
              <?php if($r['admin_notes']): ?>
              <div style="margin-top:8px;background:var(--bg);border-radius:8px;padding:8px 12px;font-size:12px;color:var(--muted)">
                <i class="fa fa-comment-medical"></i> <strong>Clinic note:</strong> <?= htmlspecialchars($r['admin_notes']) ?>
              </div>
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
</body>
</html>
