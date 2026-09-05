<?php
session_start();
$page_title = "Absensi";
require_once '../config/database.php';
require_once '../includes/header.php';
checkRole(['employee']);

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$now = date('H:i:s');
$msg = '';
$msg_type = '';

// Proses Check In / Check Out
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Cek absensi hari ini
    $q = $db->prepare("SELECT * FROM attendance WHERE user_id = :uid AND date = :today");
    $q->execute([':uid' => $user_id, ':today' => $today]);
    $att = $q->fetch(PDO::FETCH_ASSOC);

    if ($action === 'checkin') {
        if ($att) {
            $msg = 'Anda sudah check in hari ini.';
            $msg_type = 'warning';
        } else {
            // Tentukan status: terlambat jika > 08:00
            $batas = '08:00:00';
            $status = ($now > $batas) ? 'late' : 'present';
            $ins = $db->prepare("INSERT INTO attendance (user_id, date, check_in, status) VALUES (:uid, :date, :cin, :status)");
            $ins->execute([':uid' => $user_id, ':date' => $today, ':cin' => $now, ':status' => $status]);
            $msg = 'Check In berhasil pukul ' . date('H:i', strtotime($now)) . ($status === 'late' ? ' (Terlambat)' : '') . '.';
            $msg_type = 'success';
        }
    } elseif ($action === 'checkout') {
        if (!$att) {
            $msg = 'Anda belum check in hari ini.';
            $msg_type = 'warning';
        } elseif ($att['check_out']) {
            $msg = 'Anda sudah check out hari ini.';
            $msg_type = 'warning';
        } else {
            $upd = $db->prepare("UPDATE attendance SET check_out = :cout WHERE user_id = :uid AND date = :today");
            $upd->execute([':cout' => $now, ':uid' => $user_id, ':today' => $today]);
            $msg = 'Check Out berhasil pukul ' . date('H:i', strtotime($now)) . '.';
            $msg_type = 'success';
        }
    }
}

// Ambil status absensi hari ini (refresh setelah POST)
$q = $db->prepare("SELECT * FROM attendance WHERE user_id = :uid AND date = :today");
$q->execute([':uid' => $user_id, ':today' => $today]);
$today_att = $q->fetch(PDO::FETCH_ASSOC);

// Riwayat 10 hari terakhir
$q2 = $db->prepare("SELECT * FROM attendance WHERE user_id = :uid ORDER BY date DESC LIMIT 10");
$q2->execute([':uid' => $user_id]);
$history = $q2->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Status Hari Ini -->
<div class="absen-hero">
    <div>
        <h2>Absensi Hari Ini 👋</h2>
        <p><?= date('l, d F Y') ?></p>
        <p id="realtime-clock" style="font-size:28px; font-weight:700; margin-top:8px;"></p>
    </div>
    <div class="absen-status-box">
        <?php if ($today_att): ?>
            <?php if ($today_att['check_in'] && !$today_att['check_out']): ?>
                <div style="color:#10b981;">✅ Sudah Check In</div>
                <div style="font-size:22px; font-weight:700;"><?= substr($today_att['check_in'],0,5) ?></div>
                <div style="font-size:12px; opacity:0.8;">Belum Check Out</div>
            <?php elseif ($today_att['check_in'] && $today_att['check_out']): ?>
                <div style="color:#10b981;">✅ Selesai</div>
                <div style="font-size:16px; font-weight:700;"><?= substr($today_att['check_in'],0,5) ?> – <?= substr($today_att['check_out'],0,5) ?></div>
            <?php endif; ?>
        <?php else: ?>
            <div style="font-size:14px;">Belum Absen</div>
            <i class="fas fa-clock fa-2x" style="margin-top:8px;"></i>
        <?php endif; ?>
    </div>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg_type === 'success' ? 'success' : ($msg_type === 'warning' ? 'warning' : 'danger') ?>" style="margin-bottom:20px;">
    <?= $msg ?>
</div>
<?php endif; ?>

<!-- Tombol Check In / Check Out -->
<div class="absen-btn-wrap">
    <?php if (!$today_att || (!$today_att['check_in'])): ?>
        <form method="POST">
            <input type="hidden" name="action" value="checkin">
            <button type="submit" class="btn-absen btn-checkin">
                <i class="fas fa-sign-in-alt"></i> CHECK IN
            </button>
        </form>
    <?php elseif ($today_att['check_in'] && !$today_att['check_out']): ?>
        <form method="POST">
            <input type="hidden" name="action" value="checkout">
            <button type="submit" class="btn-absen btn-checkout">
                <i class="fas fa-sign-out-alt"></i> CHECK OUT
            </button>
        </form>
    <?php else: ?>
        <div class="btn-absen btn-done">
            <i class="fas fa-check-circle"></i> Absensi Selesai
        </div>
    <?php endif; ?>
</div>

<!-- Riwayat -->
<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-history"></i> Riwayat Absensi</h4>
    </div>
    <div class="card-body" style="padding:0; overflow-x:auto;">
        <table>
            <thead>
                <tr><th>Tanggal</th><th>Masuk</th><th>Keluar</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php if (count($history) > 0): ?>
                <?php foreach ($history as $a):
                    $sm = ['present'=>['Hadir','badge-success'],'late'=>['Terlambat','badge-warning'],'absent'=>['Tidak Hadir','badge-danger'],'leave'=>['Izin','badge-info'],'sick'=>['Sakit','badge-warning']];
                    $s = $sm[$a['status']] ?? [$a['status'],'badge-info'];
                ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($a['date'])) ?></td>
                    <td><?= $a['check_in'] ? substr($a['check_in'],0,5) : '-' ?></td>
                    <td><?= $a['check_out'] ? substr($a['check_out'],0,5) : '-' ?></td>
                    <td><span class="badge <?= $s[1] ?>"><?= $s[0] ?></span></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align:center;padding:30px;color:#64748b;">Belum ada riwayat</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function updateClock() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2,'0');
    const m = String(now.getMinutes()).padStart(2,'0');
    const s = String(now.getSeconds()).padStart(2,'0');
    const el = document.getElementById('realtime-clock');
    if (el) el.textContent = h + ':' + m + ':' + s;
}
setInterval(updateClock, 1000);
updateClock();
</script>

<?php require_once '../includes/footer.php'; ?>
