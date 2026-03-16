<?php
require_once '../config/init.php';
Auth::checkAdmin();

$userMgr = new UserManager();
$msg = ''; $msgType = 'success';
$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    if ($postAction === 'create') {
        $data = $_POST;
        $data['created_by'] = Auth::currentUser()['id'];
        $result = $userMgr->create($data);
        $msg = $result['message']; $msgType = $result['success']?'success':'danger';
        $action = '';
    } elseif ($postAction === 'update') {
        $result = $userMgr->update((int)$_POST['id'], $_POST);
        $msg = $result['message']; $msgType = $result['success']?'success':'danger';
        $action = '';
    } elseif ($postAction === 'toggle') {
        $result = $userMgr->toggleActive((int)$_POST['id']);
        $msg = $result['message']; $msgType = 'success';
    }
}

$search  = $_GET['search'] ?? '';
$users   = $userMgr->getAll($search);
$editUser = ($action === 'edit' && $id) ? $userMgr->getById($id) : null;
$pageTitle    = 'User Management';
$pageSubtitle = 'Manage student and staff accounts';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Management – DSRRM Clinic</title>
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

  <?php if($action === 'add' || $action === 'edit'): ?>
  <div class="page-card">
    <div class="card-header">
      <h2><i class="fa fa-<?= $editUser?'user-edit':'user-plus' ?>"></i> <?= $editUser?'Edit User':'Add New User' ?></h2>
      <a href="users.php" class="btn btn-outline btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="action" value="<?= $editUser?'update':'create' ?>">
        <?php if($editUser): ?><input type="hidden" name="id" value="<?= $editUser['id'] ?>"><?php endif; ?>
        <div class="form-row">
          <div class="form-group"><label>Student ID</label><input type="text" name="student_id" value="<?= htmlspecialchars($editUser['student_id']??'') ?>" placeholder="BCP-2024-0001"></div>
          <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" value="<?= htmlspecialchars($editUser['full_name']??'') ?>" required></div>
          <div class="form-group"><label>Email *</label><input type="email" name="email" value="<?= htmlspecialchars($editUser['email']??'') ?>" required></div>
          <div class="form-group">
            <label>Password <?= $editUser?'(leave blank to keep)':'' ?> *</label>
            <input type="password" name="password" <?= !$editUser?'required':'' ?> placeholder="Min. 8 characters">
          </div>
          <div class="form-group">
            <label>Role</label>
            <select name="role">
              <option value="user" <?= ($editUser['role']??'')==='user'?'selected':'' ?>>Student / User</option>
              <option value="admin" <?= ($editUser['role']??'')==='admin'?'selected':'' ?>>Admin / Staff</option>
            </select>
          </div>
          <div class="form-group">
            <label>Gender</label>
            <select name="gender">
              <option value="">-- Select --</option>
              <?php foreach(['Male','Female','Other'] as $g): ?>
              <option value="<?= $g ?>" <?= ($editUser['gender']??'')===$g?'selected':'' ?>><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= htmlspecialchars($editUser['phone']??'') ?>"></div>
          <div class="form-group"><label>Date of Birth</label><input type="date" name="date_of_birth" value="<?= htmlspecialchars($editUser['date_of_birth']??'') ?>"></div>
          <div class="form-group">
            <label>Blood Type</label>
            <select name="blood_type">
              <option value="">-- Unknown --</option>
              <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?>
              <option value="<?= $bt ?>" <?= ($editUser['blood_type']??'')===$bt?'selected':'' ?>><?= $bt ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Emergency Contact</label><input type="text" name="emergency_contact" value="<?= htmlspecialchars($editUser['emergency_contact']??'') ?>"></div>
          <div class="form-group"><label>Emergency Phone</label><input type="text" name="emergency_phone" value="<?= htmlspecialchars($editUser['emergency_phone']??'') ?>"></div>
          <?php if($editUser): ?>
          <div class="form-group">
            <label>Account Status</label>
            <select name="is_active">
              <option value="1" <?= ($editUser['is_active']??1)?'selected':'' ?>>Active</option>
              <option value="0" <?= !($editUser['is_active']??1)?'selected':'' ?>>Disabled</option>
            </select>
          </div>
          <?php endif; ?>
        </div>
        <div class="form-group" style="margin-top:14px"><label>Address</label><textarea name="address"><?= htmlspecialchars($editUser['address']??'') ?></textarea></div>
        <?php if(!$editUser): ?>
        <div class="alert alert-info" style="margin-top:14px"><i class="fa fa-info-circle"></i> A corresponding patient record will be automatically created for student users.</div>
        <?php endif; ?>
        <div style="margin-top:20px;display:flex;gap:10px">
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?= $editUser?'Update':'Create' ?> User</button>
          <a href="users.php" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <?php else: ?>
  <div class="page-card">
    <div class="card-header">
      <h2><i class="fa fa-users-cog"></i> All Users (<?= count($users) ?>)</h2>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <div class="search-bar">
          <i class="fa fa-search"></i>
          <input type="text" placeholder="Search users..." oninput="searchTable(this,'usersTable')">
        </div>
        <a href="users.php?action=add" class="btn btn-primary"><i class="fa fa-user-plus"></i> Add User</a>
      </div>
    </div>
    <div class="card-body" style="padding:0">
      <?php if(empty($users)): ?>
        <div class="empty-state"><i class="fa fa-users"></i><h4>No users found</h4></div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="usersTable">
          <thead><tr><th>#</th><th>Student ID</th><th>Name</th><th>Email</th><th>Role</th><th>Gender</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach($users as $i => $u): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><span style="font-family:monospace;font-weight:700;color:var(--primary)"><?= htmlspecialchars($u['student_id']??'-') ?></span></td>
            <td><div class="name-cell"><div class="patient-avatar"><?= strtoupper(substr($u['full_name'],0,1)) ?></div><?= htmlspecialchars($u['full_name']) ?></div></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="badge <?= $u['role']==='admin'?'badge-purple':'badge-info' ?>"><?= ucfirst($u['role']) ?></span></td>
            <td><?= htmlspecialchars($u['gender']??'-') ?></td>
            <td>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <button type="submit" class="badge <?= $u['is_active']?'badge-success':'badge-danger' ?>" style="border:none;cursor:pointer;padding:4px 10px">
                  <?= $u['is_active']?'Active':'Disabled' ?>
                </button>
              </form>
            </td>
            <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
            <td>
              <a href="users.php?action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a>
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
<script src="../js/dashboard.js"></script>
<script>
function searchTable(inp,tid){const v=inp.value.toLowerCase();document.querySelectorAll('#'+tid+' tbody tr').forEach(r=>r.style.display=r.textContent.toLowerCase().includes(v)?'':'none');}
</script>
</body>
</html>
