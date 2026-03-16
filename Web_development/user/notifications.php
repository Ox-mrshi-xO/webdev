<?php
require_once '../config/init.php';
Auth::checkUser();

$userId     = (int)$_SESSION['user_id'];
$notifClass = new Notification();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    if ($postAction === 'mark_all_read') {
        $notifClass->markAllRead($userId);
        $msg = 'All notifications marked as read.';
    } elseif ($postAction === 'mark_read' && isset($_POST['notif_id'])) {
        $notifClass->markRead((int)$_POST['notif_id'], $userId);
    } elseif ($postAction === 'delete' && isset($_POST['notif_id'])) {
        $notifClass->delete((int)$_POST['notif_id'], $userId);
        $msg = 'Notification deleted.';
    }
}

$notifications = $notifClass->getByUser($userId);
$unread        = $notifClass->getUnreadCount($userId);
$typeIcons     = ['appointment'=>'fa-calendar-check','medicine'=>'fa-pills','general'=>'fa-info-circle','alert'=>'fa-exclamation-triangle'];
$typeBg        = ['appointment'=>'appointment','medicine'=>'medicine','general'=>'general','alert'=>'alert'];

$pageTitle    = 'Notifications';
$pageSubtitle = $unread > 0 ? "$unread unread notification(s)" : 'All caught up!';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications – DSRRM Clinic</title>
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
      <h2><i class="fa fa-bell"></i> Notifications (<?= count($notifications) ?>)
        <?php if($unread>0): ?><span class="badge badge-danger" style="font-size:11px"><?= $unread ?> new</span><?php endif; ?>
      </h2>
      <?php if($unread > 0): ?>
      <form method="POST" style="display:inline">
        <input type="hidden" name="action" value="mark_all_read">
        <button type="submit" class="btn btn-outline btn-sm"><i class="fa fa-check-double"></i> Mark All Read</button>
      </form>
      <?php endif; ?>
    </div>
    <div class="card-body" style="padding:0">
      <?php if(empty($notifications)): ?>
        <div class="empty-state">
          <i class="fa fa-bell-slash"></i>
          <h4>No notifications yet</h4>
          <p>You'll receive notifications for appointments, medicine requests, and clinic updates here.</p>
        </div>
      <?php else: ?>
        <?php foreach($notifications as $n): ?>
        <div class="<?= !$n['is_read']?'notif-unread':'' ?>" style="padding:16px 22px;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;gap:16px">
          <div class="notif-icon <?= $typeBg[$n['type']] ?? 'general' ?>" style="flex-shrink:0">
            <i class="fa <?= $typeIcons[$n['type']] ?? 'fa-bell' ?>"></i>
          </div>
          <div style="flex:1">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap">
              <div>
                <h4 style="font-size:14px;font-weight:700;margin-bottom:3px">
                  <?php if(!$n['is_read']): ?><span style="display:inline-block;width:8px;height:8px;background:var(--primary);border-radius:50%;margin-right:6px;vertical-align:middle"></span><?php endif; ?>
                  <?= htmlspecialchars($n['title']) ?>
                </h4>
                <p style="font-size:13px;color:#555;line-height:1.5"><?= htmlspecialchars($n['message']) ?></p>
                <time style="font-size:11px;color:var(--muted);margin-top:4px;display:block"><?= date('F d, Y \a\t h:i A', strtotime($n['created_at'])) ?></time>
              </div>
              <div style="display:flex;gap:6px;flex-shrink:0">
                <?php if(!$n['is_read']): ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action" value="mark_read">
                  <input type="hidden" name="notif_id" value="<?= $n['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline" title="Mark as read"><i class="fa fa-check"></i></button>
                </form>
                <?php endif; ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="notif_id" value="<?= $n['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this notification?')" title="Delete"><i class="fa fa-trash"></i></button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</main>
<script src="../js/dashboard.js"></script>
</body>
</html>
