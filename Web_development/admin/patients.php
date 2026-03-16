<?php
require_once '../config/init.php';
Auth::checkAdmin();

$patientClass = new Patient();
$msg = '';
$msgType = 'success';

// Handle actions
$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    if ($postAction === 'create') {
        $data = $_POST;
        $data['created_by'] = Auth::currentUser()['id'];
        $result = $patientClass->create($data);
        $msg = $result['message']; $msgType = $result['success']?'success':'danger';
        if($result['success']) $action = '';
    } elseif ($postAction === 'update') {
        $result = $patientClass->update((int)$_POST['id'], $_POST);
        $msg = $result['message']; $msgType = $result['success']?'success':'danger';
        $action = '';
    }
}
if ($action === 'delete' && $id) {
    $result = $patientClass->delete($id);
    $msg = $result['message']; $msgType = $result['success']?'success':'danger';
    $action = '';
}

$search   = $_GET['search'] ?? '';
$patients = $patientClass->getAll($search);
$editPatient = ($action === 'edit' && $id) ? $patientClass->getById($id) : null;

$pageTitle    = 'Patient Records';
$pageSubtitle = 'Manage student patient records';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patients – DSRRM Clinic</title>
<link rel="stylesheet" href="../css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/topbar.php'; ?>

<main class="main-content">
  <?php if($msg): ?>
    <div class="alert alert-<?= $msgType ?> alert-auto"><i class="fa fa-<?= $msgType==='success'?'check-circle':'exclamation-circle' ?>"></i><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <?php if($action === 'add' || $action === 'edit'): ?>
  <!-- ADD / EDIT FORM -->
  <div class="page-card">
    <div class="card-header">
      <h2><i class="fa fa-<?= $editPatient?'user-edit':'user-plus' ?>"></i> <?= $editPatient?'Edit Patient':'Add New Patient' ?></h2>
      <a href="patients.php" class="btn btn-outline btn-sm"><i class="fa fa-arrow-left"></i> Back to List</a>
    </div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="action" value="<?= $editPatient?'update':'create' ?>">
        <?php if($editPatient): ?><input type="hidden" name="id" value="<?= $editPatient['id'] ?>"><?php endif; ?>
        <div class="form-row">
          <div class="form-group">
            <label>Student ID *</label>
            <input type="text" name="student_id" value="<?= htmlspecialchars($editPatient['student_id']??'') ?>" required placeholder="e.g. BCP-2024-0001">
          </div>
          <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($editPatient['full_name']??'') ?>" required>
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($editPatient['email']??'') ?>">
          </div>
          <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($editPatient['phone']??'') ?>">
          </div>
          <div class="form-group">
            <label>Date of Birth</label>
            <input type="date" name="date_of_birth" value="<?= htmlspecialchars($editPatient['date_of_birth']??'') ?>">
          </div>
          <div class="form-group">
            <label>Gender</label>
            <select name="gender">
              <option value="">-- Select --</option>
              <?php foreach(['Male','Female','Other'] as $g): ?>
              <option value="<?= $g ?>" <?= ($editPatient['gender']??'')===$g?'selected':'' ?>><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Blood Type</label>
            <select name="blood_type">
              <option value="">-- Unknown --</option>
              <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?>
              <option value="<?= $bt ?>" <?= ($editPatient['blood_type']??'')===$bt?'selected':'' ?>><?= $bt ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Emergency Contact Name</label>
            <input type="text" name="emergency_contact" value="<?= htmlspecialchars($editPatient['emergency_contact']??'') ?>">
          </div>
          <div class="form-group">
            <label>Emergency Contact Phone</label>
            <input type="text" name="emergency_phone" value="<?= htmlspecialchars($editPatient['emergency_phone']??'') ?>">
          </div>
        </div>
        <div class="form-row" style="margin-top:14px">
          <div class="form-group">
            <label>Address</label>
            <textarea name="address"><?= htmlspecialchars($editPatient['address']??'') ?></textarea>
          </div>
          <div class="form-group">
            <label>Known Allergies</label>
            <textarea name="allergies"><?= htmlspecialchars($editPatient['allergies']??'') ?></textarea>
          </div>
          <div class="form-group">
            <label>Notes</label>
            <textarea name="notes"><?= htmlspecialchars($editPatient['notes']??'') ?></textarea>
          </div>
        </div>
        <div style="margin-top:20px;display:flex;gap:10px">
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?= $editPatient?'Update':'Save' ?> Patient</button>
          <a href="patients.php" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <?php else: ?>
  <!-- PATIENT LIST -->
  <div class="page-card">
    <div class="card-header">
      <h2><i class="fa fa-users"></i> All Patients (<?= count($patients) ?>)</h2>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <form method="GET" style="display:flex;gap:8px;align-items:center">
          <div class="search-bar">
            <i class="fa fa-search"></i>
            <input type="text" name="search" placeholder="Search patients..." value="<?= htmlspecialchars($search) ?>" onkeyup="filterTable('searchInput','patientsTable')">
            <input type="hidden" id="searchInput" value="<?= htmlspecialchars($search) ?>">
          </div>
        </form>
        <a href="patients.php?action=add" class="btn btn-primary"><i class="fa fa-user-plus"></i> Add Patient</a>
      </div>
    </div>
    <div class="card-body" style="padding:0">
      <?php if(empty($patients)): ?>
        <div class="empty-state"><i class="fa fa-user-slash"></i><h4>No patients found</h4><p>Add your first patient record to get started.</p></div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="patientsTable">
          <thead>
            <tr>
              <th>#</th><th>Student ID</th><th>Full Name</th><th>Gender</th><th>Blood Type</th><th>Phone</th><th>Date Added</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($patients as $i => $p): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><span style="font-family:monospace;font-weight:700;color:var(--primary)"><?= htmlspecialchars($p['student_id']??'-') ?></span></td>
            <td><div class="name-cell"><div class="patient-avatar"><?= strtoupper(substr($p['full_name'],0,1)) ?></div><?= htmlspecialchars($p['full_name']) ?></div></td>
            <td><?= htmlspecialchars($p['gender']??'-') ?></td>
            <td><?= $p['blood_type'] ? '<span class="badge badge-info">'.$p['blood_type'].'</span>' : '-' ?></td>
            <td><?= htmlspecialchars($p['phone']??'-') ?></td>
            <td><?= date('M d, Y', strtotime($p['created_at'])) ?></td>
            <td>
              <div style="display:flex;gap:5px">
                <a href="patient_history.php?patient_id=<?= $p['id'] ?>" class="btn btn-sm btn-outline" title="Medical History"><i class="fa fa-history"></i></a>
                <a href="patients.php?action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-primary" title="Edit"><i class="fa fa-edit"></i></a>
                <button onclick="confirmDelete('patients.php?action=delete&id=<?= $p['id'] ?>')" class="btn btn-sm btn-danger" title="Delete"><i class="fa fa-trash"></i></button>
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
  <?php endif; ?>
</main>
<script src="../js/dashboard.js"></script>
<script>
const si=document.querySelector('.search-bar input[name="search"]');
if(si) si.addEventListener('input',function(){filterTable('dummy','patientsTable');
  document.querySelectorAll('#patientsTable tbody tr').forEach(r=>{
    r.style.display=r.textContent.toLowerCase().includes(this.value.toLowerCase())?'':'none';
  });
});
</script>
</body>
</html>
