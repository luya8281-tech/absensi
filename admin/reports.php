<?php
session_start();
$page_title = "Laporan";
require_once '../config/database.php';
require_once '../includes/header.php';

checkRole(['admin']);

$database = new Database();
$db = $database->getConnection();

$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$dept_filter = isset($_GET['department']) ? $_GET['department'] : '';

list($year, $mon) = explode('-', $month);

$where = "WHERE YEAR(a.date) = :year AND MONTH(a.date) = :month";
$params = [':year' => $year, ':month' => $mon];

if(!empty($dept_filter)) {
    $where .= " AND u.department = :dept";
    $params[':dept'] = $dept_filter;
}

// Rekap per karyawan
$query = "SELECT 
            u.employee_id, u.full_name, u.department, u.position,
            COUNT(*) as total_hari,
            SUM(CASE WHEN a.status IN ('present','late') THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as terlambat,
            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as tidak_hadir,
            SUM(CASE WHEN a.status = 'leave' THEN 1 ELSE 0 END) as izin,
            SUM(CASE WHEN a.status = 'sick' THEN 1 ELSE 0 END) as sakit
          FROM attendance a
          JOIN users u ON a.user_id = u.id
          $where
          GROUP BY u.id, u.employee_id, u.full_name, u.department, u.position
          ORDER BY u.department, u.full_name";
$stmt = $db->prepare($query);
foreach($params as $key => $val) $stmt->bindValue($key, $val);
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get departments
$query = "SELECT DISTINCT department FROM users WHERE department IS NOT NULL ORDER BY department";
$stmt = $db->prepare($query);
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
        <h4><i class="fas fa-chart-bar"></i> Laporan Absensi Bulanan</h4>
        <a href="?month=<?= $month ?>&department=<?= $dept_filter ?>&export=1" 
           class="btn btn-primary">
            <i class="fas fa-download"></i> Export CSV
        </a>
    </div>
    <div class="card-body">
        <form method="GET" style="display:grid; grid-template-columns:1fr 1fr auto; gap:10px; margin-bottom:20px;">
            <div>
                <label style="font-size:13px; font-weight:600; margin-bottom:5px; display:block;">Pilih Bulan</label>
                <input type="month" name="month" class="form-control" value="<?= $month ?>">
            </div>
            <div>
                <label style="font-size:13px; font-weight:600; margin-bottom:5px; display:block;">Departemen</label>
                <select name="department" class="form-control">
                    <option value="">Semua Departemen</option>
                    <?php foreach($departments as $d): ?>
                        <option value="<?= htmlspecialchars($d['department']) ?>" 
                                <?= $dept_filter == $d['department'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['department']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex; align-items:flex-end;">
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-search"></i> Tampilkan
                </button>
            </div>
        </form>

        <?php if(isset($_GET['export'])): ?>
            <?php
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="laporan_absensi_' . $month . '.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID Karyawan','Nama','Departemen','Posisi','Total Hari','Hadir','Terlambat','Tidak Hadir','Izin','Sakit']);
            foreach($reports as $r) {
                fputcsv($output, [$r['employee_id'],$r['full_name'],$r['department'],$r['position'],$r['total_hari'],$r['hadir'],$r['terlambat'],$r['tidak_hadir'],$r['izin'],$r['sakit']]);
            }
            fclose($output);
            exit();
            ?>
        <?php endif; ?>

        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Departemen</th>
                        <th>Posisi</th>
                        <th style="text-align:center;">Total Hari</th>
                        <th style="text-align:center; color:var(--success);">Hadir</th>
                        <th style="text-align:center; color:var(--warning);">Terlambat</th>
                        <th style="text-align:center; color:var(--danger);">Tidak Hadir</th>
                        <th style="text-align:center; color:var(--primary);">Izin</th>
                        <th style="text-align:center; color:#8b5cf6;">Sakit</th>
                        <th style="text-align:center;">Kehadiran</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($reports) > 0): ?>
                        <?php foreach($reports as $r): ?>
                            <?php $pct = $r['total_hari'] > 0 ? round(($r['hadir']/$r['total_hari'])*100, 1) : 0; ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['employee_id']) ?></strong></td>
                                <td><?= htmlspecialchars($r['full_name']) ?></td>
                                <td><?= htmlspecialchars($r['department']) ?></td>
                                <td style="font-size:13px;"><?= htmlspecialchars($r['position']) ?></td>
                                <td style="text-align:center;"><?= $r['total_hari'] ?></td>
                                <td style="text-align:center;"><strong style="color:var(--success);"><?= $r['hadir'] ?></strong></td>
                                <td style="text-align:center;"><strong style="color:var(--warning);"><?= $r['terlambat'] ?></strong></td>
                                <td style="text-align:center;"><strong style="color:var(--danger);"><?= $r['tidak_hadir'] ?></strong></td>
                                <td style="text-align:center;"><strong style="color:var(--primary);"><?= $r['izin'] ?></strong></td>
                                <td style="text-align:center;"><strong style="color:#8b5cf6;"><?= $r['sakit'] ?></strong></td>
                                <td style="min-width:120px;">
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div style="flex:1; background:var(--light); height:8px; border-radius:10px; overflow:hidden;">
                                            <div style="background:<?= $pct >= 80 ? 'var(--success)' : ($pct >= 60 ? 'var(--warning)' : 'var(--danger)') ?>; height:100%; width:<?= $pct ?>%; border-radius:10px;"></div>
                                        </div>
                                        <span style="font-size:12px; font-weight:600; min-width:38px;"><?= $pct ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" style="text-align:center; padding:40px; color:var(--secondary);">
                                <i class="fas fa-chart-bar fa-3x" style="opacity:0.3; margin-bottom:10px;"></i>
                                <p>Tidak ada data untuk periode ini</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
