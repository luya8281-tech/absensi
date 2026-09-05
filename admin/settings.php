<?php
session_start();
$page_title = "Pengaturan";
require_once '../config/database.php';
require_once '../includes/header.php';

checkRole(['admin']);

$database = new Database();
$db = $database->getConnection();

// Buat tabel settings jika belum ada
$db->exec("CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Insert default settings
$defaults = [
    'company_name' => 'PT. Perusahaan Indonesia',
    'work_start' => '08:00',
    'work_end' => '17:00',
    'late_tolerance' => '15',
    'company_address' => 'Jl. Contoh No. 1, Jakarta',
    'company_phone' => '021-1234567',
];

foreach($defaults as $key => $value) {
    $db->exec("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('$key', '$value')");
}

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach($_POST as $key => $value) {
        if(substr($key, 0, 8) == 'setting_') {
            $setting_key = substr($key, 8);
            $setting_value = trim($value);
            $query = "INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)
                      ON DUPLICATE KEY UPDATE setting_value = :value2";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':key', $setting_key);
            $stmt->bindParam(':value', $setting_value);
            $stmt->bindParam(':value2', $setting_value);
            $stmt->execute();
        }
    }
    $success = "Pengaturan berhasil disimpan";
}

$query = "SELECT * FROM settings";
$stmt = $db->prepare($query);
$stmt->execute();
$settings_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
$settings = [];
foreach($settings_raw as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}
?>

<?php if($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-cog"></i> Pengaturan Sistem</h4>
    </div>
    <div class="card-body">
        <form method="POST">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <div>
                    <h5 style="margin-bottom:15px; padding-bottom:10px; border-bottom:2px solid var(--primary); color:var(--primary);">
                        <i class="fas fa-building"></i> Informasi Perusahaan
                    </h5>
                    <div class="form-group">
                        <label>Nama Perusahaan</label>
                        <input type="text" name="setting_company_name" class="form-control" 
                               value="<?= htmlspecialchars($settings['company_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Alamat Perusahaan</label>
                        <textarea name="setting_company_address" class="form-control" rows="3"><?= htmlspecialchars($settings['company_address'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Telepon Perusahaan</label>
                        <input type="text" name="setting_company_phone" class="form-control" 
                               value="<?= htmlspecialchars($settings['company_phone'] ?? '') ?>">
                    </div>
                </div>

                <div>
                    <h5 style="margin-bottom:15px; padding-bottom:10px; border-bottom:2px solid var(--primary); color:var(--primary);">
                        <i class="fas fa-clock"></i> Pengaturan Jam Kerja
                    </h5>
                    <div class="form-group">
                        <label>Jam Masuk</label>
                        <input type="time" name="setting_work_start" class="form-control" 
                               value="<?= htmlspecialchars($settings['work_start'] ?? '08:00') ?>">
                    </div>
                    <div class="form-group">
                        <label>Jam Keluar</label>
                        <input type="time" name="setting_work_end" class="form-control" 
                               value="<?= htmlspecialchars($settings['work_end'] ?? '17:00') ?>">
                    </div>
                    <div class="form-group">
                        <label>Toleransi Keterlambatan (menit)</label>
                        <input type="number" name="setting_late_tolerance" class="form-control" 
                               value="<?= htmlspecialchars($settings['late_tolerance'] ?? '15') ?>" min="0" max="60">
                        <small style="color:var(--secondary); font-size:12px;">Karyawan akan ditandai terlambat jika melewati toleransi ini</small>
                    </div>
                </div>
            </div>

            <div style="margin-top:20px; padding-top:20px; border-top:1px solid var(--border);">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
