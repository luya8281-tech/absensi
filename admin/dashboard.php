<?php
session_start();
$page_title = "Dashboard Admin";
require_once '../config/database.php';
require_once '../includes/header.php';

checkRole(['admin']);

$database = new Database();
$db = $database->getConnection();

// Statistik Total
$stats = [];

// Total Karyawan
$query = "SELECT COUNT(*) as total FROM users WHERE status = 'active'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total Hadir Hari Ini
$today = date('Y-m-d');
$query = "SELECT COUNT(*) as total FROM attendance WHERE date = :today AND status IN ('present', 'late')";
$stmt = $db->prepare($query);
$stmt->bindParam(':today', $today);
$stmt->execute();
$stats['present_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total Izin/Sakit Hari Ini
$query = "SELECT COUNT(*) as total FROM attendance WHERE date = :today AND status IN ('leave', 'sick')";
$stmt = $db->prepare($query);
$stmt->bindParam(':today', $today);
$stmt->execute();
$stats['leave_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pengajuan Cuti Pending
$query = "SELECT COUNT(*) as total FROM leave_requests WHERE status = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['pending_leaves'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Absensi Terbaru Hari Ini
$query = "SELECT a.*, u.full_name, u.employee_id, u.department 
          FROM attendance a
          JOIN users u ON a.user_id = u.id
          WHERE a.date = :today
          ORDER BY a.created_at DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bindParam(':today', $today);
$stmt->execute();
$recent_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pengajuan Cuti Terbaru
$query = "SELECT lr.*, u.full_name, u.employee_id, u.department
          FROM leave_requests lr
          JOIN users u ON lr.user_id = u.id
          WHERE lr.status = 'pending'
          ORDER BY lr.created_at DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$pending_leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Data untuk grafik - Absensi 7 hari terakhir
$query = "SELECT 
            DATE(date) as tanggal,
            COUNT(CASE WHEN status IN ('present', 'late') THEN 1 END) as hadir,
            COUNT(CASE WHEN status = 'absent' THEN 1 END) as tidak_hadir,
            COUNT(CASE WHEN status IN ('leave', 'sick') THEN 1 END) as izin
          FROM attendance
          WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
          GROUP BY DATE(date)
          ORDER BY date ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Departemen dengan absensi terbanyak hari ini
$query = "SELECT u.department, COUNT(*) as total
          FROM attendance a
          JOIN users u ON a.user_id = u.id
          WHERE a.date = :today AND a.status IN ('present', 'late')
          GROUP BY u.department
          ORDER BY total DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':today', $today);
$stmt->execute();
$dept_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="stats-grid">
    <div class="stat-card" style="border-left-color: var(--primary);">
        <h3><i class="fas fa-users"></i> Total Karyawan</h3>
        <div class="value"><?= $stats['total_users'] ?></div>
        <small style="color: var(--secondary);">Karyawan Aktif</small>
    </div>
    
    <div class="stat-card" style="border-left-color: var(--success);">
        <h3><i class="fas fa-check-circle"></i> Hadir Hari Ini</h3>
        <div class="value"><?= $stats['present_today'] ?></div>
        <small style="color: var(--secondary);">
            <?= $stats['total_users'] > 0 ? round(($stats['present_today']/$stats['total_users'])*100, 1) : 0 ?>% dari total
        </small>
    </div>
    
    <div class="stat-card" style="border-left-color: var(--warning);">
        <h3><i class="fas fa-calendar-times"></i> Izin/Sakit</h3>
        <div class="value"><?= $stats['leave_today'] ?></div>
        <small style="color: var(--secondary);">Hari Ini</small>
    </div>
    
    <div class="stat-card" style="border-left-color: var(--danger);">
        <h3><i class="fas fa-clock"></i> Pengajuan Cuti</h3>
        <div class="value"><?= $stats['pending_leaves'] ?></div>
        <small style="color: var(--secondary);">Menunggu Persetujuan</small>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px;">
    <!-- Grafik Absensi -->
    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-chart-line"></i> Statistik Absensi 7 Hari Terakhir</h4>
        </div>
        <div class="card-body">
            <canvas id="attendanceChart" style="max-height: 300px;"></canvas>
        </div>
    </div>
    
    <!-- Departemen Stats -->
    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-building"></i> Absensi per Departemen</h4>
        </div>
        <div class="card-body">
            <?php if(count($dept_stats) > 0): ?>
                <?php foreach($dept_stats as $dept): ?>
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span style="font-weight: 600; font-size: 14px;"><?= htmlspecialchars($dept['department']) ?></span>
                            <span style="color: var(--primary); font-weight: 700;"><?= $dept['total'] ?></span>
                        </div>
                        <div style="background: var(--light); height: 8px; border-radius: 10px; overflow: hidden;">
                            <div style="background: var(--primary); height: 100%; width: <?= ($dept['total']/$stats['present_today'])*100 ?>%; border-radius: 10px;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: var(--secondary); padding: 20px;">Belum ada data</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Absensi Terbaru -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h4><i class="fas fa-history"></i> Absensi Terbaru Hari Ini</h4>
        <span style="background: var(--light); padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600;">
            <i class="fas fa-calendar"></i> <?= formatTanggal($today) ?>
        </span>
    </div>
    <div class="card-body" style="padding: 0;">
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID Karyawan</th>
                        <th>Nama</th>
                        <th>Departemen</th>
                        <th>Jam Masuk</th>
                        <th>Jam Keluar</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($recent_attendance) > 0): ?>
                        <?php foreach($recent_attendance as $att): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($att['employee_id']) ?></strong></td>
                                <td><?= htmlspecialchars($att['full_name']) ?></td>
                                <td><?= htmlspecialchars($att['department']) ?></td>
                                <td>
                                    <?php if($att['check_in']): ?>
                                        <i class="fas fa-clock"></i> <?= formatWaktu($att['check_in']) ?>
                                    <?php else: ?>
                                        <span style="color: var(--secondary);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($att['check_out']): ?>
                                        <i class="fas fa-clock"></i> <?= formatWaktu($att['check_out']) ?>
                                    <?php else: ?>
                                        <span style="color: var(--secondary);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $badge_class = 'badge-info';
                                    $status_text = $att['status'];
                                    if($att['status'] == 'present') {
                                        $badge_class = 'badge-success';
                                        $status_text = 'Hadir';
                                    } elseif($att['status'] == 'late') {
                                        $badge_class = 'badge-warning';
                                        $status_text = 'Terlambat';
                                    } elseif($att['status'] == 'absent') {
                                        $badge_class = 'badge-danger';
                                        $status_text = 'Tidak Hadir';
                                    } elseif($att['status'] == 'leave') {
                                        $badge_class = 'badge-info';
                                        $status_text = 'Izin';
                                    } elseif($att['status'] == 'sick') {
                                        $badge_class = 'badge-warning';
                                        $status_text = 'Sakit';
                                    }
                                    ?>
                                    <span class="badge <?= $badge_class ?>"><?= $status_text ?></span>
                                </td>
                                <td style="font-size: 13px; color: var(--secondary);">
                                    <?= $att['notes'] ? htmlspecialchars($att['notes']) : '-' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: var(--secondary);">
                                <i class="fas fa-inbox fa-3x" style="margin-bottom: 10px; opacity: 0.3;"></i>
                                <p>Belum ada data absensi hari ini</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pengajuan Cuti Pending -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h4><i class="fas fa-file-alt"></i> Pengajuan Cuti Menunggu Persetujuan</h4>
        <a href="leave_requests.php" class="btn btn-primary" style="padding: 6px 12px; font-size: 13px;">
            Lihat Semua <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    <div class="card-body" style="padding: 0;">
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID Karyawan</th>
                        <th>Nama</th>
                        <th>Departemen</th>
                        <th>Jenis Cuti</th>
                        <th>Tanggal</th>
                        <th>Durasi</th>
                        <th>Alasan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($pending_leaves) > 0): ?>
                        <?php foreach($pending_leaves as $leave): ?>
                            <?php
                            $start = new DateTime($leave['start_date']);
                            $end = new DateTime($leave['end_date']);
                            $days = $start->diff($end)->days + 1;
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($leave['employee_id']) ?></strong></td>
                                <td><?= htmlspecialchars($leave['full_name']) ?></td>
                                <td><?= htmlspecialchars($leave['department']) ?></td>
                                <td>
                                    <?php
                                    $type_labels = [
                                        'sick' => 'Sakit',
                                        'annual' => 'Tahunan',
                                        'emergency' => 'Darurat',
                                        'other' => 'Lainnya'
                                    ];
                                    ?>
                                    <span class="badge badge-info"><?= $type_labels[$leave['leave_type']] ?></span>
                                </td>
                                <td style="font-size: 13px;">
                                    <?= date('d/m/Y', strtotime($leave['start_date'])) ?> - 
                                    <?= date('d/m/Y', strtotime($leave['end_date'])) ?>
                                </td>
                                <td><strong><?= $days ?></strong> hari</td>
                                <td style="max-width: 200px; font-size: 13px;">
                                    <?= htmlspecialchars(substr($leave['reason'], 0, 50)) ?>
                                    <?= strlen($leave['reason']) > 50 ? '...' : '' ?>
                                </td>
                                <td>
                                    <a href="leave_requests.php?action=approve&id=<?= $leave['id'] ?>" 
                                       class="btn btn-primary" 
                                       style="padding: 5px 10px; font-size: 12px; margin-right: 5px;"
                                       onclick="return confirm('Setujui pengajuan cuti ini?')">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <a href="leave_requests.php?action=reject&id=<?= $leave['id'] ?>" 
                                       class="btn" 
                                       style="padding: 5px 10px; font-size: 12px; background: var(--danger); color: white;"
                                       onclick="return confirm('Tolak pengajuan cuti ini?')">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: var(--secondary);">
                                <i class="fas fa-check-circle fa-3x" style="margin-bottom: 10px; opacity: 0.3;"></i>
                                <p>Tidak ada pengajuan cuti yang menunggu persetujuan</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart Absensi
const ctx = document.getElementById('attendanceChart').getContext('2d');
const chartData = <?= json_encode($chart_data) ?>;

const labels = chartData.map(d => {
    const date = new Date(d.tanggal);
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
});

const hadirData = chartData.map(d => d.hadir);
const tidakHadirData = chartData.map(d => d.tidak_hadir);
const izinData = chartData.map(d => d.izin);

new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Hadir',
            data: hadirData,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Tidak Hadir',
            data: tidakHadirData,
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Izin/Sakit',
            data: izinData,
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245, 158, 11, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
