<?php
session_start();
$page_title = "Pengajuan Cuti";
require_once '../config/database.php';
require_once '../includes/header.php';

checkRole(['employee']);

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle Submit
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $leave_type = $_POST['leave_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = trim($_POST['reason']);

    if(empty($leave_type) || empty($start_date) || empty($end_date) || empty($reason)) {
        $error = "Semua field wajib diisi";
    } elseif($end_date < $start_date) {
        $error = "Tanggal selesai tidak boleh sebelum tanggal mulai";
    } else {
        $query = "INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, reason) 
                  VALUES (:user_id, :leave_type, :start_date, :end_date, :reason)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':leave_type', $leave_type);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->bindParam(':reason', $reason);

        if($stmt->execute()) {
            $success = "Pengajuan cuti berhasil dikirim, menunggu persetujuan";
        } else {
            $error = "Gagal mengajukan cuti";
        }
    }
}

// Get my leave history
$query = "SELECT lr.*, a.full_name as approved_name
          FROM leave_requests lr
          LEFT JOIN users a ON lr.approved_by = a.id
          WHERE lr.user_id = :user_id
          ORDER BY lr.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$my_leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
<?php endif; ?>
<?php if($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:1fr 2fr; gap:20px;">
    <!-- Form Pengajuan -->
    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-paper-plane"></i> Ajukan Cuti Baru</h4>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label>Jenis Cuti *</label>
                    <select name="leave_type" class="form-control" required>
                        <option value="">Pilih Jenis Cuti</option>
                        <option value="annual">Cuti Tahunan</option>
                        <option value="sick">Cuti Sakit</option>
                        <option value="emergency">Cuti Darurat</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tanggal Mulai *</label>
                    <input type="date" name="start_date" class="form-control" 
                           min="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Tanggal Selesai *</label>
                    <input type="date" name="end_date" class="form-control" 
                           min="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Alasan *</label>
                    <textarea name="reason" class="form-control" rows="4" 
                              placeholder="Jelaskan alasan pengajuan cuti..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-paper-plane"></i> Kirim Pengajuan
                </button>
            </form>
        </div>
    </div>

    <!-- Riwayat Cuti -->
    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-history"></i> Riwayat Pengajuan Cuti</h4>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if(count($my_leaves) > 0): ?>
                <?php
                $type_labels = ['sick'=>'Sakit','annual'=>'Tahunan','emergency'=>'Darurat','other'=>'Lainnya'];
                $status_map = [
                    'pending'=>['Menunggu','badge-warning'],
                    'approved'=>['Disetujui','badge-success'],
                    'rejected'=>['Ditolak','badge-danger'],
                ];
                ?>
                <?php foreach($my_leaves as $leave): ?>
                    <?php
                    $s = $status_map[$leave['status']];
                    $start = new DateTime($leave['start_date']);
                    $end = new DateTime($leave['end_date']);
                    $days = $start->diff($end)->days + 1;
                    ?>
                    <div style="padding:16px 20px; border-bottom:1px solid var(--border);">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
                            <div>
                                <span style="font-weight:700; font-size:15px;"><?= $type_labels[$leave['leave_type']] ?></span>
                                <span class="badge <?= $s[1] ?>" style="margin-left:8px;"><?= $s[0] ?></span>
                            </div>
                            <span style="font-size:12px; color:var(--secondary);"><?= $days ?> hari</span>
                        </div>
                        <div style="font-size:13px; color:var(--secondary); margin-bottom:6px;">
                            <i class="fas fa-calendar"></i>
                            <?= date('d/m/Y', strtotime($leave['start_date'])) ?> - 
                            <?= date('d/m/Y', strtotime($leave['end_date'])) ?>
                        </div>
                        <div style="font-size:13px; margin-bottom:6px;"><?= htmlspecialchars($leave['reason']) ?></div>
                        <?php if($leave['approved_name']): ?>
                            <div style="font-size:12px; color:var(--secondary);">
                                Diproses oleh: <strong><?= htmlspecialchars($leave['approved_name']) ?></strong>
                                <?php if($leave['approved_at']): ?>
                                    pada <?= date('d/m/Y H:i', strtotime($leave['approved_at'])) ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="padding:40px; text-align:center; color:var(--secondary);">
                    <i class="fas fa-file-alt fa-3x" style="opacity:0.3; margin-bottom:10px;"></i>
                    <p>Belum ada pengajuan cuti</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
