<?php
session_start();
$page_title = "Dashboard Staff";
require_once '../config/database.php';
require_once '../includes/header.php';

checkRole(['staff']);

$database = new Database();
$db = $database->getConnection();

$today = date('Y-m-d');

// Stats
$query = "SELECT COUNT(*) as total FROM users WHERE status = 'active' AND role = 'employee'";
$stmt = $db->prepare($query);
$stmt->execute();
$total_emp = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT COUNT(*) as total FROM attendance WHERE date = :today AND status IN ('present','late')";
$stmt = $db->prepare($query);
$stmt->bindParam(':today', $today);
$stmt->execute();
$hadir = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT COUNT(*) as total FROM leave_requests WHERE status = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute();
$pending = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Absensi hari ini
$query = "SELECT a.*, u.full_name, u.employee_id, u.department
          FROM attendance a
          JOIN users u ON a.user_id = u.id
          WHERE a.date = :today
          ORDER BY a.created_at DESC LIMIT 15";
$stmt = $db->prepare($query);
$stmt->bindParam(':today', $today);
$stmt->execute();
$today_att = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Karyawan belum absen hari ini
$query = "SELECT u.employee_id, u.full_name, u.department, u.position
          FROM users u
          WHERE u.status = 'active'
          AND u.id NOT IN (SELECT user_id FROM attendance WHERE date = :today)
          ORDER BY u.department, u.full_name LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bindParam(':today', $today);
$stmt->execute();
$not_yet = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="stats-grid">
    <div class="stat-card" style="border-left-color:var(--primary);">
        <h3><i class="fas fa-users"></i> Total Karyawan</h3>
        <div class="value"><?= $total_emp ?></div>
        <small style="color:var(--secondary);">Karyawan Aktif</small>
    </div>
    <div class="stat-card" style="border-left-color:var(--success);">
        <h3><i class="fas fa-check-circle"></i> Hadir Hari Ini</h3>
        <div class="value"><?= $hadir ?></div>
        <small style="color:var(--secondary);"><?= $total_emp > 0 ? round(($hadir/$total_emp)*100,1) : 0 ?>% dari total</small>
    </div>
    <div class="stat-card" style="border-left-color:var(--danger);">
        <h3><i class="fas fa-user-times"></i> Belum Absen</h3>
        <div class="value"><?= count($not_yet) ?></div>
        <small style="color:var(--secondary);">Hari Ini</small>
    </div>
    <div class="stat-card" style="border-left-color:var(--warning);">
        <h3><i class="fas fa-file-alt"></i> Cuti Pending</h3>
        <div class="value"><?= $pending ?></div>
        <small style="color:var(--secondary);">Menunggu Persetujuan</small>
    </div>
</div>

<div style="display:grid; grid-template-columns:2fr 1fr; gap:20px;">
    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-history"></i> Absensi Hari Ini</h4>
        </div>
        <div class="card-body" style="padding:0;">
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Departemen</th>
                            <th>Masuk</th>
                            <th>Keluar</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($today_att) > 0): ?>
                            <?php foreach($today_att as $att): ?>
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
                                    <td><strong><?= htmlspecialchars($att['employee_id']) ?></strong></td>
                                    <td><?= htmlspecialchars($att['full_name']) ?></td>
                                    <td><?= htmlspecialchars($att['department']) ?></td>
                                    <td><?= $att['check_in'] ? formatWaktu($att['check_in']) : '-' ?></td>
                                    <td><?= $att['check_out'] ? formatWaktu($att['check_out']) : '-' ?></td>
                                    <td><span class="badge <?= $s[1] ?>"><?= $s[0] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding:30px; color:var(--secondary);">Belum ada absensi</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-user-times"></i> Belum Absen</h4>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if(count($not_yet) > 0): ?>
                <?php foreach($not_yet as $emp): ?>
                    <div style="padding:12px 15px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:12px;">
                        <div class="user-avatar" style="width:35px; height:35px; font-size:12px; flex-shrink:0;">
                            <?= getInitials($emp['full_name']) ?>
                        </div>
                        <div>
                            <div style="font-weight:600; font-size:14px;"><?= htmlspecialchars($emp['full_name']) ?></div>
                            <div style="font-size:12px; color:var(--secondary);"><?= htmlspecialchars($emp['department']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="padding:30px; text-align:center; color:var(--secondary);">
                    <i class="fas fa-check-circle fa-2x" style="color:var(--success); margin-bottom:10px;"></i>
                    <p>Semua karyawan sudah absen</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
