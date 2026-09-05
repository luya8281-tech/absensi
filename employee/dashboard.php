<?php
session_start();
$page_title = "Dashboard";
require_once '../config/database.php';
require_once '../includes/header.php';

checkRole(['employee']);

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$month_start = date('Y-m-01');

// Absensi hari ini
$query = "SELECT * FROM attendance WHERE user_id = :user_id AND date = :today";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':today', $today);
$stmt->execute();
$today_att = $stmt->fetch(PDO::FETCH_ASSOC);

// Statistik bulan ini
$query = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as terlambat,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as tidak_hadir,
            SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as izin,
            SUM(CASE WHEN status = 'sick' THEN 1 ELSE 0 END) as sakit
          FROM attendance
          WHERE user_id = :user_id AND date BETWEEN :month_start AND :today";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':month_start', $month_start);
$stmt->bindParam(':today', $today);
$stmt->execute();
$month_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Riwayat absensi terbaru
$query = "SELECT * FROM attendance WHERE user_id = :user_id ORDER BY date DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Status cuti terbaru
$query = "SELECT * FROM leave_requests WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$my_leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Clock & Greeting -->
<div style="background:linear-gradient(135deg, var(--primary), var(--primary-dark)); color:white; border-radius:12px; padding:25px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h2 style="font-size:22px; margin-bottom:5px;">
            Selamat Datang, <?= htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]) ?>! 👋
        </h2>
        <p style="opacity:0.9; font-size:14px;"><?= htmlspecialchars($_SESSION['position']) ?> - <?= htmlspecialchars($_SESSION['department']) ?></p>
        <p id="realtime-clock" style="margin-top:8px; font-size:13px; opacity:0.85;"></p>
    </div>
    <div style="text-align:right;">
        <?php if($today_att): ?>
            <?php if($today_att['check_in'] && !$today_att['check_out']): ?>
                <div style="background:rgba(255,255,255,0.15); border-radius:10px; padding:15px 20px;">
                    <div style="font-size:12px; opacity:0.8; margin-bottom:4px;">Jam Masuk</div>
                    <div style="font-size:24px; font-weight:700;"><?= formatWaktu($today_att['check_in']) ?></div>
                    <div style="font-size:12px; margin-top:4px; opacity:0.8;">Belum Check Out</div>
                </div>
            <?php elseif($today_att['check_in'] && $today_att['check_out']): ?>
                <div style="background:rgba(255,255,255,0.15); border-radius:10px; padding:15px 20px;">
                    <div style="font-size:12px; opacity:0.8; margin-bottom:4px;">Sudah Check In & Out</div>
                    <div style="font-size:18px; font-weight:700;"><?= formatWaktu($today_att['check_in']) ?> - <?= formatWaktu($today_att['check_out']) ?></div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div style="background:rgba(255,255,255,0.15); border-radius:10px; padding:15px 20px; text-align:center;">
                <i class="fas fa-clock fa-2x" style="margin-bottom:8px;"></i>
                <p style="font-size:14px;">Belum Absen Hari Ini</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Stats Bulan Ini -->
<div class="stats-grid" style="margin-bottom:20px;">
    <div class="stat-card" style="border-left-color:var(--success);">
        <h3><i class="fas fa-check-circle"></i> Hadir Bulan Ini</h3>
        <div class="value"><?= $month_stats['hadir'] ?? 0 ?></div>
        <small style="color:var(--secondary);">Hari</small>
    </div>
    <div class="stat-card" style="border-left-color:var(--warning);">
        <h3><i class="fas fa-clock"></i> Terlambat</h3>
        <div class="value"><?= $month_stats['terlambat'] ?? 0 ?></div>
        <small style="color:var(--secondary);">Kali Terlambat</small>
    </div>
    <div class="stat-card" style="border-left-color:var(--danger);">
        <h3><i class="fas fa-times-circle"></i> Tidak Hadir</h3>
        <div class="value"><?= $month_stats['tidak_hadir'] ?? 0 ?></div>
        <small style="color:var(--secondary);">Hari</small>
    </div>
    <div class="stat-card" style="border-left-color:var(--primary);">
        <h3><i class="fas fa-umbrella-beach"></i> Izin/Sakit</h3>
        <div class="value"><?= ($month_stats['izin'] ?? 0) + ($month_stats['sakit'] ?? 0) ?></div>
        <small style="color:var(--secondary);">Hari</small>
    </div>
</div>

<div style="display:grid; grid-template-columns:2fr 1fr; gap:20px;">
    <!-- Riwayat Absensi -->
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h4><i class="fas fa-history"></i> Riwayat Absensi Terbaru</h4>
            <a href="my_attendance.php" style="font-size:13px; color:var(--primary);">Lihat Semua →</a>
        </div>
        <div class="card-body" style="padding:0;">
            <table>
                <thead>
                    <tr><th>Tanggal</th><th>Masuk</th><th>Keluar</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php if(count($recent) > 0): ?>
                        <?php foreach($recent as $att): ?>
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
                                <td><?= $att['check_in'] ? formatWaktu($att['check_in']) : '-' ?></td>
                                <td><?= $att['check_out'] ? formatWaktu($att['check_out']) : '-' ?></td>
                                <td><span class="badge <?= $s[1] ?>"><?= $s[0] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; padding:30px; color:var(--secondary);">Belum ada riwayat</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Status Cuti -->
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h4><i class="fas fa-file-alt"></i> Pengajuan Cuti</h4>
            <a href="leave_request.php" style="font-size:13px; color:var(--primary);">Ajukan →</a>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if(count($my_leaves) > 0): ?>
                <?php foreach($my_leaves as $leave): ?>
                    <?php
                    $st = ['pending'=>['Menunggu','badge-warning'],'approved'=>['Disetujui','badge-success'],'rejected'=>['Ditolak','badge-danger']];
                    $s = $st[$leave['status']];
                    $type = ['sick'=>'Sakit','annual'=>'Tahunan','emergency'=>'Darurat','other'=>'Lainnya'];
                    ?>
                    <div style="padding:12px 15px; border-bottom:1px solid var(--border);">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                            <div>
                                <div style="font-weight:600; font-size:14px;"><?= $type[$leave['leave_type']] ?></div>
                                <div style="font-size:12px; color:var(--secondary); margin-top:3px;">
                                    <?= date('d/m/Y', strtotime($leave['start_date'])) ?> - 
                                    <?= date('d/m/Y', strtotime($leave['end_date'])) ?>
                                </div>
                            </div>
                            <span class="badge <?= $s[1] ?>"><?= $s[0] ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="padding:30px; text-align:center; color:var(--secondary); font-size:14px;">
                    Belum ada pengajuan cuti
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
