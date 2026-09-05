<?php
session_start();
$page_title = "Pengajuan Cuti";
require_once '../config/database.php';
require_once '../includes/header.php';

checkRole(['admin', 'staff']);

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Handle approve/reject
if(isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];

    if($action == 'approve' || $action == 'reject') {
        $new_status = $action == 'approve' ? 'approved' : 'rejected';
        $query = "UPDATE leave_requests SET status = :status, approved_by = :approved_by, approved_at = NOW() WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':approved_by', $_SESSION['user_id']);
        $stmt->bindParam(':id', $id);

        if($stmt->execute()) {
            $success = "Pengajuan cuti berhasil " . ($action == 'approve' ? 'disetujui' : 'ditolak');
        }
    }
}

// Filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

$where = "WHERE lr.created_at BETWEEN :date_from AND DATE_ADD(:date_to, INTERVAL 1 DAY)";
$params = [':date_from' => $date_from, ':date_to' => $date_to];

if(!empty($status_filter)) {
    $where .= " AND lr.status = :status";
    $params[':status'] = $status_filter;
}
if(!empty($search)) {
    $where .= " AND (u.full_name LIKE :search OR u.employee_id LIKE :search)";
    $params[':search'] = "%$search%";
}

$query = "SELECT lr.*, u.full_name, u.employee_id, u.department,
          a.full_name as approved_name
          FROM leave_requests lr
          JOIN users u ON lr.user_id = u.id
          LEFT JOIN users a ON lr.approved_by = a.id
          $where
          ORDER BY lr.created_at DESC";
$stmt = $db->prepare($query);
foreach($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
<?php endif; ?>

<!-- Summary Cards -->
<?php
$pending_count = count(array_filter($leaves, fn($l) => $l['status'] == 'pending'));
$approved_count = count(array_filter($leaves, fn($l) => $l['status'] == 'approved'));
$rejected_count = count(array_filter($leaves, fn($l) => $l['status'] == 'rejected'));
?>
<div class="stats-grid" style="margin-bottom:20px;">
    <div class="stat-card" style="border-left-color:var(--warning);">
        <h3><i class="fas fa-clock"></i> Menunggu</h3>
        <div class="value"><?= $pending_count ?></div>
    </div>
    <div class="stat-card" style="border-left-color:var(--success);">
        <h3><i class="fas fa-check-circle"></i> Disetujui</h3>
        <div class="value"><?= $approved_count ?></div>
    </div>
    <div class="stat-card" style="border-left-color:var(--danger);">
        <h3><i class="fas fa-times-circle"></i> Ditolak</h3>
        <div class="value"><?= $rejected_count ?></div>
    </div>
    <div class="stat-card" style="border-left-color:var(--primary);">
        <h3><i class="fas fa-list"></i> Total</h3>
        <div class="value"><?= count($leaves) ?></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-file-alt"></i> Daftar Pengajuan Cuti</h4>
    </div>
    <div class="card-body">
        <form method="GET" style="display:grid; grid-template-columns:1fr 1fr 2fr 1fr auto; gap:10px; margin-bottom:20px;">
            <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
            <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
            <input type="text" name="search" class="form-control" placeholder="Cari nama atau ID..." value="<?= htmlspecialchars($search) ?>">
            <select name="status" class="form-control">
                <option value="">Semua Status</option>
                <option value="pending" <?= $status_filter=='pending'?'selected':'' ?>>Menunggu</option>
                <option value="approved" <?= $status_filter=='approved'?'selected':'' ?>>Disetujui</option>
                <option value="rejected" <?= $status_filter=='rejected'?'selected':'' ?>>Ditolak</option>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
        </form>

        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Departemen</th>
                        <th>Jenis</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th>Durasi</th>
                        <th>Alasan</th>
                        <th>Status</th>
                        <th>Diproses Oleh</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($leaves) > 0): ?>
                        <?php foreach($leaves as $leave): ?>
                            <?php
                            $start = new DateTime($leave['start_date']);
                            $end = new DateTime($leave['end_date']);
                            $days = $start->diff($end)->days + 1;
                            $type_labels = ['sick'=>'Sakit','annual'=>'Tahunan','emergency'=>'Darurat','other'=>'Lainnya'];
                            $status_map = [
                                'pending' => ['label'=>'Menunggu','class'=>'badge-warning'],
                                'approved' => ['label'=>'Disetujui','class'=>'badge-success'],
                                'rejected' => ['label'=>'Ditolak','class'=>'badge-danger'],
                            ];
                            $s = $status_map[$leave['status']];
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($leave['employee_id']) ?></strong></td>
                                <td><?= htmlspecialchars($leave['full_name']) ?></td>
                                <td><?= htmlspecialchars($leave['department']) ?></td>
                                <td><span class="badge badge-info"><?= $type_labels[$leave['leave_type']] ?></span></td>
                                <td><?= date('d/m/Y', strtotime($leave['start_date'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($leave['end_date'])) ?></td>
                                <td><strong><?= $days ?></strong> hari</td>
                                <td style="font-size:13px; max-width:180px;">
                                    <?= htmlspecialchars(substr($leave['reason'], 0, 60)) ?><?= strlen($leave['reason']) > 60 ? '...' : '' ?>
                                </td>
                                <td><span class="badge <?= $s['class'] ?>"><?= $s['label'] ?></span></td>
                                <td style="font-size:13px;">
                                    <?= $leave['approved_name'] ? htmlspecialchars($leave['approved_name']) : '-' ?>
                                    <?php if($leave['approved_at']): ?>
                                        <br><small style="color:var(--secondary);"><?= date('d/m/Y', strtotime($leave['approved_at'])) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($leave['status'] == 'pending'): ?>
                                        <div style="display:flex; gap:5px;">
                                            <a href="?action=approve&id=<?= $leave['id'] ?>&status=<?= $status_filter ?>" 
                                               class="btn btn-primary" style="padding:5px 10px; font-size:12px;"
                                               onclick="return confirm('Setujui pengajuan cuti ini?')">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="?action=reject&id=<?= $leave['id'] ?>&status=<?= $status_filter ?>" 
                                               class="btn" style="padding:5px 10px; font-size:12px; background:var(--danger); color:white;"
                                               onclick="return confirm('Tolak pengajuan cuti ini?')">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span style="font-size:12px; color:var(--secondary);">Sudah diproses</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" style="text-align:center; padding:40px; color:var(--secondary);">
                                <i class="fas fa-inbox fa-3x" style="opacity:0.3; margin-bottom:10px;"></i>
                                <p>Tidak ada data pengajuan cuti</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
