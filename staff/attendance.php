<?php
session_start();
$page_title = "Data Absensi";
require_once '../config/database.php';
require_once '../includes/header.php';

checkRole(['staff']);

$database = new Database();
$db = $database->getConnection();

$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where = "WHERE a.date BETWEEN :date_from AND :date_to";
$params = [':date_from' => $date_from, ':date_to' => $date_to];

if(!empty($search)) {
    $where .= " AND (u.full_name LIKE :search OR u.employee_id LIKE :search)";
    $params[':search'] = "%$search%";
}
if(!empty($status_filter)) {
    $where .= " AND a.status = :status";
    $params[':status'] = $status_filter;
}

$query = "SELECT a.*, u.full_name, u.employee_id, u.department
          FROM attendance a JOIN users u ON a.user_id = u.id
          $where ORDER BY a.date DESC, a.created_at DESC";
$stmt = $db->prepare($query);
foreach($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->execute();
$attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-calendar-check"></i> Data Absensi Karyawan</h4>
    </div>
    <div class="card-body">
        <form method="GET" style="display:grid; grid-template-columns:1fr 1fr 2fr 1fr auto; gap:10px; margin-bottom:20px;">
            <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
            <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
            <input type="text" name="search" class="form-control" placeholder="Cari nama atau ID..." value="<?= htmlspecialchars($search) ?>">
            <select name="status" class="form-control">
                <option value="">Semua Status</option>
                <option value="present" <?= $status_filter=='present'?'selected':'' ?>>Hadir</option>
                <option value="late" <?= $status_filter=='late'?'selected':'' ?>>Terlambat</option>
                <option value="absent" <?= $status_filter=='absent'?'selected':'' ?>>Tidak Hadir</option>
                <option value="leave" <?= $status_filter=='leave'?'selected':'' ?>>Izin</option>
                <option value="sick" <?= $status_filter=='sick'?'selected':'' ?>>Sakit</option>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
        </form>

        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th><th>ID</th><th>Nama</th><th>Departemen</th>
                        <th>Masuk</th><th>Keluar</th><th>Status</th><th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($attendances) > 0): ?>
                        <?php foreach($attendances as $att): ?>
                            <?php
                            $status_map = [
                                'present'=>['Hadir','badge-success'],
                                'late'=>['Terlambat','badge-warning'],
                                'absent'=>['Tidak Hadir','badge-danger'],
                                'leave'=>['Izin','badge-info'],
                                'sick'=>['Sakit','badge-warning'],
                            ];
                            $s = $status_map[$att['status']] ?? [$att['status'],'badge-info'];
                            ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($att['date'])) ?></td>
                                <td><strong><?= htmlspecialchars($att['employee_id']) ?></strong></td>
                                <td><?= htmlspecialchars($att['full_name']) ?></td>
                                <td><?= htmlspecialchars($att['department']) ?></td>
                                <td><?= $att['check_in'] ? formatWaktu($att['check_in']) : '-' ?></td>
                                <td><?= $att['check_out'] ? formatWaktu($att['check_out']) : '-' ?></td>
                                <td><span class="badge <?= $s[1] ?>"><?= $s[0] ?></span></td>
                                <td style="font-size:13px;"><?= htmlspecialchars($att['notes'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:40px; color:var(--secondary);">
                                Tidak ada data absensi
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
