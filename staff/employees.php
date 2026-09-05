<?php
session_start();
$page_title = "Data Karyawan";
require_once '../config/database.php';
require_once '../includes/header.php';

checkRole(['staff']);

$database = new Database();
$db = $database->getConnection();

$search = isset($_GET['search']) ? $_GET['search'] : '';
$dept_filter = isset($_GET['department']) ? $_GET['department'] : '';

$where = "WHERE status = 'active'";
$params = [];

if(!empty($search)) {
    $where .= " AND (full_name LIKE :search OR employee_id LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}
if(!empty($dept_filter)) {
    $where .= " AND department = :dept";
    $params[':dept'] = $dept_filter;
}

$query = "SELECT * FROM users $where ORDER BY department, full_name";
$stmt = $db->prepare($query);
foreach($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT DISTINCT department FROM users WHERE department IS NOT NULL ORDER BY department";
$stmt = $db->prepare($query);
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-users"></i> Data Karyawan</h4>
    </div>
    <div class="card-body">
        <form method="GET" style="display:grid; grid-template-columns:2fr 1fr auto; gap:10px; margin-bottom:20px;">
            <input type="text" name="search" class="form-control" placeholder="Cari nama, ID, email..." value="<?= htmlspecialchars($search) ?>">
            <select name="department" class="form-control">
                <option value="">Semua Departemen</option>
                <?php foreach($departments as $d): ?>
                    <option value="<?= htmlspecialchars($d['department']) ?>" <?= $dept_filter==$d['department']?'selected':'' ?>>
                        <?= htmlspecialchars($d['department']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
        </form>

        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:15px;">
            <?php if(count($employees) > 0): ?>
                <?php foreach($employees as $emp): ?>
                    <div style="border:1px solid var(--border); border-radius:10px; padding:18px; background:white;">
                        <div style="display:flex; align-items:center; gap:14px; margin-bottom:14px;">
                            <div class="user-avatar" style="width:50px; height:50px; font-size:16px; flex-shrink:0;">
                                <?= getInitials($emp['full_name']) ?>
                            </div>
                            <div>
                                <div style="font-weight:700; font-size:15px;"><?= htmlspecialchars($emp['full_name']) ?></div>
                                <div style="font-size:12px; color:var(--secondary);"><?= htmlspecialchars($emp['position']) ?></div>
                            </div>
                        </div>
                        <div style="font-size:13px; display:grid; gap:6px;">
                            <div style="display:flex; gap:8px;">
                                <i class="fas fa-id-badge" style="color:var(--primary); width:16px;"></i>
                                <span><?= htmlspecialchars($emp['employee_id']) ?></span>
                            </div>
                            <div style="display:flex; gap:8px;">
                                <i class="fas fa-building" style="color:var(--primary); width:16px;"></i>
                                <span><?= htmlspecialchars($emp['department']) ?></span>
                            </div>
                            <div style="display:flex; gap:8px;">
                                <i class="fas fa-envelope" style="color:var(--primary); width:16px;"></i>
                                <span><?= htmlspecialchars($emp['email']) ?></span>
                            </div>
                            <?php if($emp['phone']): ?>
                            <div style="display:flex; gap:8px;">
                                <i class="fas fa-phone" style="color:var(--primary); width:16px;"></i>
                                <span><?= htmlspecialchars($emp['phone']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top:12px; padding-top:12px; border-top:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                            <span class="badge <?= $emp['role']=='admin'?'badge-danger':($emp['role']=='staff'?'badge-warning':'badge-info') ?>">
                                <?= strtoupper($emp['role']) ?>
                            </span>
                            <span style="font-size:12px; color:var(--secondary);">
                                Bergabung <?= $emp['join_date'] ? date('d/m/Y', strtotime($emp['join_date'])) : '-' ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column:1/-1; text-align:center; padding:40px; color:var(--secondary);">
                    Tidak ada karyawan ditemukan
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
