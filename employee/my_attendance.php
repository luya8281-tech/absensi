<?php
session_start();
$page_title = "Absensi Saya";
require_once '../config/database.php';
require_once '../includes/header.php';

checkRole(['employee']);

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$success = '';
$error = '';

// Handle Check In / Check Out
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    
    if($action == 'checkin') {
        // Cek sudah absen belum
        $query = "SELECT * FROM attendance WHERE user_id = :user_id AND date = :today";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':today', $today);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $error = "Anda sudah melakukan check in hari ini";
        } else {
            $check_in_time = date('H:i:s');
            $work_start = '08:00:00';
            $status = strtotime($check_in_time) > strtotime($work_start) ? 'late' : 'present';
            $notes = $_POST['notes'] ?? '';

            $query = "INSERT INTO attendance (user_id, date, check_in, status, notes) VALUES (:user_id, :today, :check_in, :status, :notes)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':today', $today);
            $stmt->bindParam(':check_in', $check_in_time);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':notes', $notes);

            if($stmt->execute()) {
                $success = "Check in berhasil pada " . date('H:i');
            }
        }
    } elseif($action == 'checkout') {
        $query = "SELECT * FROM attendance WHERE user_id = :user_id AND date = :today";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        $att = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$att) {
            $error = "Anda belum melakukan check in hari ini";
        } elseif($att['check_out']) {
            $error = "Anda sudah melakukan check out hari ini";
        } else {
            $check_out_time = date('H:i:s');
            $query = "UPDATE attendance SET check_out = :check_out WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':check_out', $check_out_time);
            $stmt->bindParam(':id', $att['id']);

            if($stmt->execute()) {
                $success = "Check out berhasil pada " . date('H:i');
            }
        }
    }
}

// Get today attendance
$query = "SELECT * FROM attendance WHERE user_id = :user_id AND date = :today";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':today', $today);
$stmt->execute();
$today_att = $stmt->fetch(PDO::FETCH_ASSOC);

// Filter riwayat
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
list($y, $m) = explode('-', $month);

$query = "SELECT * FROM attendance WHERE user_id = :user_id AND YEAR(date) = :year AND MONTH(date) = :month ORDER BY date DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':year', $y);
$stmt->bindParam(':month', $m);
$stmt->execute();
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
<?php endif; ?>
<?php if($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
<?php endif; ?>

<!-- Check In/Out Card -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <h4><i class="fas fa-fingerprint"></i> Absensi Hari Ini - <?= formatTanggal($today) ?></h4>
    </div>
    <div class="card-body">
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:20px; align-items:center;">
            <div style="text-align:center; padding:20px; background:var(--light); border-radius:10px;">
                <div style="font-size:13px; color:var(--secondary); margin-bottom:8px;">JAM MASUK</div>
                <div style="font-size:32px; font-weight:700; color:<?= $today_att && $today_att['check_in'] ? 'var(--success)' : 'var(--secondary)' ?>;">
                    <?= $today_att && $today_att['check_in'] ? formatWaktu($today_att['check_in']) : '--:--' ?>
                </div>
                <?php if($today_att && $today_att['status'] == 'late'): ?>
                    <span class="badge badge-warning" style="margin-top:8px;">Terlambat</span>
                <?php elseif($today_att && $today_att['status'] == 'present'): ?>
                    <span class="badge badge-success" style="margin-top:8px;">Tepat Waktu</span>
                <?php endif; ?>
            </div>

            <div style="text-align:center; padding:20px; background:var(--light); border-radius:10px;">
                <div style="font-size:13px; color:var(--secondary); margin-bottom:8px;">JAM KELUAR</div>
                <div style="font-size:32px; font-weight:700; color:<?= $today_att && $today_att['check_out'] ? 'var(--danger)' : 'var(--secondary)' ?>;">
                    <?= $today_att && $today_att['check_out'] ? formatWaktu($today_att['check_out']) : '--:--' ?>
                </div>
                <?php if($today_att && $today_att['check_out']): ?>
                    <span class="badge badge-info" style="margin-top:8px;">Sudah Check Out</span>
                <?php endif; ?>
            </div>

            <div style="text-align:center;">
                <?php if(!$today_att): ?>
                    <form method="POST" style="margin-bottom:12px;">
                        <input type="hidden" name="action" value="checkin">
                        <div class="form-group" style="margin-bottom:10px;">
                            <textarea name="notes" class="form-control" rows="2" placeholder="Keterangan (opsional)"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%; padding:14px;" 
                                onclick="return confirm('Konfirmasi Check In sekarang?')">
                            <i class="fas fa-sign-in-alt"></i> CHECK IN SEKARANG
                        </button>
                    </form>
                <?php elseif($today_att && !$today_att['check_out']): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="btn" style="width:100%; padding:14px; background:var(--danger); color:white; font-weight:700;"
                                onclick="return confirm('Konfirmasi Check Out sekarang?')">
                            <i class="fas fa-sign-out-alt"></i> CHECK OUT SEKARANG
                        </button>
                    </form>
                    <p style="margin-top:10px; font-size:13px; color:var(--secondary);">Anda sudah check in</p>
                <?php else: ?>
                    <div style="padding:20px; background:#d1fae5; border-radius:10px;">
                        <i class="fas fa-check-circle fa-2x" style="color:var(--success); margin-bottom:8px;"></i>
                        <p style="color:var(--success); font-weight:600;">Absensi Selesai</p>
                        <p style="font-size:13px; color:var(--secondary);">Sampai jumpa besok!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Riwayat -->
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
        <h4><i class="fas fa-history"></i> Riwayat Absensi</h4>
        <form method="GET" style="display:flex; gap:10px; align-items:center;">
            <input type="month" name="month" class="form-control" value="<?= $month ?>" style="width:auto;">
            <button type="submit" class="btn btn-primary" style="padding:8px 16px; font-size:13px;">Tampilkan</button>
        </form>
    </div>
    <div class="card-body" style="padding:0;">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jam Masuk</th>
                    <th>Jam Keluar</th>
                    <th>Durasi</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($history) > 0): ?>
                    <?php foreach($history as $att): ?>
                        <?php
                        $duration = '-';
                        if($att['check_in'] && $att['check_out']) {
                            $in = new DateTime($att['check_in']);
                            $out = new DateTime($att['check_out']);
                            $diff = $in->diff($out);
                            $duration = $diff->h . 'j ' . $diff->i . 'm';
                        }
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
                            <td><?= $duration ?></td>
                            <td><span class="badge <?= $s[1] ?>"><?= $s[0] ?></span></td>
                            <td style="font-size:13px; color:var(--secondary);"><?= htmlspecialchars($att['notes'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:40px; color:var(--secondary);">
                            <i class="fas fa-calendar-times fa-3x" style="opacity:0.3; margin-bottom:10px;"></i>
                            <p>Tidak ada data absensi bulan ini</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
