<?php
require_once '../config/init.php';
Auth::checkAdmin();

$medicineClass = new Medicine();
$msg = ''; $msgType = 'success';
$action = $_GET['action'] ?? '';
$filter = $_GET['filter'] ?? '';
$id     = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    if ($postAction === 'create') {
        $data = $_POST;
        $data['created_by'] = Auth::currentUser()['id'];
        $result = $medicineClass->create($data);
        $msg = $result['message']; $msgType = $result['success']?'success':'danger';
        $action = '';
    } elseif ($postAction === 'update') {
        $data = $_POST;
        $data['updated_by'] = Auth::currentUser()['id'];
        $result = $medicineClass->update((int)$_POST['id'], $data);
        $msg = $result['message']; $msgType = $result['success']?'success':'danger';
        $action = '';
    }
}
if ($action === 'delete' && $id) {
    $result = $medicineClass->delete($id);
    $msg = $result['message']; $msgType = $result['success']?'success':'danger';
    $action = '';
}

$search = $_GET['search'] ?? '';
if ($filter === 'low') $medicines = $medicineClass->getLowStock();
elseif ($filter === 'expiring') $medicines = $medicineClass->getExpiringSoon(30);
else $medicines = $medicineClass->getAll($search);

$editMedicine = ($action === 'edit' && $id) ? $medicineClass->getById($id) : null;
$lowCount     = $medicineClass->countLowStock();

$pageTitle = 'Medicine Stock';
$pageSubtitle = 'Inventory management & tracking';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Medicine Stock – DSRRM Clinic</title>
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

  <?php if($lowCount > 0 && !$action): ?>
  <div class="low-stock-banner">
    <i class="fa fa-exclamation-triangle"></i>
    <div><strong><?= $lowCount ?> medicine(s)</strong> at or below minimum stock level.
      <a href="medicines.php?filter=low" style="color:#7c5800;font-weight:800;margin-left:8px">View Low Stock →</a>
    </div>
  </div>
  <?php endif; ?>

  <?php if($action === 'add' || $action === 'edit'): ?>
  <div class="page-card">
    <div class="card-header">
      <h2><i class="fa fa-<?= $editMedicine?'edit':'plus-circle' ?>"></i> <?= $editMedicine?'Edit Medicine':'Add New Medicine' ?></h2>
      <a href="medicines.php" class="btn btn-outline btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="action" value="<?= $editMedicine?'update':'create' ?>">
        <?php if($editMedicine): ?><input type="hidden" name="id" value="<?= $editMedicine['id'] ?>"><?php endif; ?>
        <div class="form-row">
          <div class="form-group"><label>Brand Name *</label><input type="text" name="name" value="<?= htmlspecialchars($editMedicine['name']??'') ?>" required></div>
          <div class="form-group"><label>Generic Name</label><input type="text" name="generic_name" value="<?= htmlspecialchars($editMedicine['generic_name']??'') ?>"></div>
          <div class="form-group">
            <label>Category</label>
            <select name="category">
              <option value="">-- Select --</option>
              <?php foreach(['Analgesic/Antipyretic','Antibiotic','Antihistamine/Decongestant','Antacid','Anti-inflammatory','NSAID','Antiseptic','Electrolyte','Vitamins/Supplements','First Aid Supplies','Other Supplies','Other'] as $cat): ?>
              <option value="<?= $cat ?>" <?= ($editMedicine['category']??'')===$cat?'selected':'' ?>><?= $cat ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Dosage / Strength</label><input type="text" name="dosage" value="<?= htmlspecialchars($editMedicine['dosage']??'') ?>" placeholder="e.g. 500mg"></div>
          <div class="form-group"><label>Current Quantity *</label><input type="number" name="quantity" value="<?= htmlspecialchars($editMedicine['quantity']??0) ?>" required min="0"></div>
          <div class="form-group">
            <label>Unit</label>
            <select name="unit">
              <?php foreach(['tablets','capsules','sachets','bottles','vials','ampules','pcs','rolls','boxes','packs'] as $u): ?>
              <option value="<?= $u ?>" <?= ($editMedicine['unit']??'')===$u?'selected':'' ?>><?= $u ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Minimum Stock Level</label><input type="number" name="min_stock" value="<?= htmlspecialchars($editMedicine['min_stock']??10) ?>" min="0"></div>
          <div class="form-group"><label>Expiry Date</label><input type="date" name="expiry_date" value="<?= htmlspecialchars($editMedicine['expiry_date']??'') ?>"></div>
          <div class="form-group"><label>Supplier</label><input type="text" name="supplier" value="<?= htmlspecialchars($editMedicine['supplier']??'') ?>"></div>
        </div>
        <div class="form-group" style="margin-top:14px"><label>Description / Notes</label><textarea name="description"><?= htmlspecialchars($editMedicine['description']??'') ?></textarea></div>
        <div style="margin-top:20px;display:flex;gap:10px">
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?= $editMedicine?'Update':'Save' ?> Medicine</button>
          <a href="medicines.php" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <?php else: ?>
  <!-- Filter tabs -->
  <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
    <a href="medicines.php" class="btn <?= !$filter?'btn-primary':'btn-outline' ?> btn-sm">All Medicines</a>
    <a href="medicines.php?filter=low" class="btn <?= $filter==='low'?'btn-warning':'btn-outline' ?> btn-sm"><i class="fa fa-exclamation-triangle"></i> Low Stock (<?= $lowCount ?>)</a>
    <a href="medicines.php?filter=expiring" class="btn <?= $filter==='expiring'?'btn-danger':'btn-outline' ?> btn-sm"><i class="fa fa-calendar-times"></i> Expiring Soon</a>
  </div>

  <div class="page-card">
    <div class="card-header">
      <h2><i class="fa fa-pills"></i>
        <?= $filter==='low'?'Low Stock Medicines':($filter==='expiring'?'Expiring Soon':'All Medicines') ?>
        (<?= count($medicines) ?>)
      </h2>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <?php if(!$filter): ?>
        <div class="search-bar">
          <i class="fa fa-search"></i>
          <input type="text" placeholder="Search medicines..." oninput="searchTable(this,'medTable')">
        </div>
        <?php endif; ?>
        <a href="medicines.php?action=add" class="btn btn-primary"><i class="fa fa-plus"></i> Add Medicine</a>
      </div>
    </div>
    <div class="card-body" style="padding:0">
      <?php if(empty($medicines)): ?>
        <div class="empty-state"><i class="fa fa-pills"></i><h4>No medicines found</h4><p>Add your first medicine to the inventory.</p></div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="medTable">
          <thead>
            <tr><th>#</th><th>Brand Name</th><th>Generic Name</th><th>Category</th><th>Dosage</th><th>Stock</th><th>Min Stock</th><th>Expiry</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
          <?php foreach($medicines as $i => $m): ?>
          <?php
            $stockStatus = 'OK';
            $badgeClass  = 'badge-success';
            if ($m['quantity'] == 0) { $stockStatus = 'Out of Stock'; $badgeClass = 'badge-danger'; }
            elseif ($m['quantity'] <= $m['min_stock']) { $stockStatus = 'Low Stock'; $badgeClass = 'badge-warning'; }
            $expiryBadge = '';
            if ($m['expiry_date']) {
              $daysLeft = (int)((strtotime($m['expiry_date'])-time())/86400);
              if ($daysLeft < 0) $expiryBadge = '<span class="badge badge-danger">Expired</span>';
              elseif ($daysLeft <= 30) $expiryBadge = '<span class="badge badge-warning">'.date('M d, Y',strtotime($m['expiry_date'])).'</span>';
              else $expiryBadge = '<span style="font-size:13px">'.date('M d, Y',strtotime($m['expiry_date'])).'</span>';
            } else $expiryBadge = '<span style="color:var(--muted);font-size:12px">N/A</span>';
          ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
            <td style="color:var(--muted);font-size:13px"><?= htmlspecialchars($m['generic_name']??'-') ?></td>
            <td><?= htmlspecialchars($m['category']??'-') ?></td>
            <td><?= htmlspecialchars($m['dosage']??'-') ?></td>
            <td><strong style="font-size:16px"><?= $m['quantity'] ?></strong> <span style="color:var(--muted);font-size:12px"><?= htmlspecialchars($m['unit']) ?></span></td>
            <td style="color:var(--muted)"><?= $m['min_stock'] ?></td>
            <td><?= $expiryBadge ?></td>
            <td><span class="badge <?= $badgeClass ?>"><?= $stockStatus ?></span></td>
            <td>
              <div style="display:flex;gap:5px">
                <a href="medicines.php?action=edit&id=<?= $m['id'] ?>" class="btn btn-sm btn-primary" title="Edit"><i class="fa fa-edit"></i></a>
                <button onclick="confirmDelete('medicines.php?action=delete&id=<?= $m['id'] ?>')" class="btn btn-sm btn-danger" title="Delete"><i class="fa fa-trash"></i></button>
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
function searchTable(inp, tableId) {
  const v = inp.value.toLowerCase();
  document.querySelectorAll('#'+tableId+' tbody tr').forEach(r => {
    r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none';
  });
}
</script>
</body>
</html>
