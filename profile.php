<?php
session_start();
$page_title = "Profil Saya";
require_once '../config/database.php';
require_once '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get user data
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    if($action == 'update_profile') {
        $full_name = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);

        $query = "UPDATE users SET full_name = :full_name, phone = :phone, address = :address WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':id', $user_id);

        if($stmt->execute()) {
            $_SESSION['full_name'] = $full_name;
            $success = "Profil berhasil diupdate";

            $query = "SELECT * FROM users WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Gagal mengupdate profil";
        }
    } elseif($action == 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if(!password_verify($current_password, $user['password'])) {
            $error = "Password saat ini tidak benar";
        } elseif(strlen($new_password) < 6) {
            $error = "Password baru minimal 6 karakter";
        } elseif($new_password !== $confirm_password) {
            $error = "Konfirmasi password tidak cocok";
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET password = :password WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':password', $hashed);
            $stmt->bindParam(':id', $user_id);

            if($stmt->execute()) {
                $success = "Password berhasil diubah";
            } else {
                $error = "Gagal mengubah password";
            }
        }
    }
}
?>

<?php if($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
<?php endif; ?>
<?php if($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:1fr 2fr; gap:20px;">
    <!-- Profile Card -->
    <div class="card" style="height:fit-content;">
        <div class="card-body" style="text-align:center; padding:30px 20px;">
            <div style="width:90px; height:90px; border-radius:50%; background:var(--primary); color:white; display:flex; align-items:center; justify-content:center; font-size:30px; font-weight:700; margin:0 auto 15px;">
                <?= getInitials($user['full_name']) ?>
            </div>
            <h3 style="font-size:18px; margin-bottom:5px;"><?= htmlspecialchars($user['full_name']) ?></h3>
            <p style="color:var(--secondary); font-size:14px; margin-bottom:15px;"><?= htmlspecialchars($user['position']) ?></p>
            <span class="badge <?= $user['role']=='admin'?'badge-danger':($user['role']=='staff'?'badge-warning':'badge-info') ?>">
                <?= strtoupper($user['role']) ?>
            </span>
            
            <div style="margin-top:20px; padding-top:20px; border-top:1px solid var(--border); text-align:left; font-size:14px;">
                <div style="display:flex; gap:10px; margin-bottom:12px; align-items:flex-start;">
                    <i class="fas fa-id-badge" style="color:var(--primary); width:16px; margin-top:2px;"></i>
                    <div>
                        <div style="font-size:11px; color:var(--secondary); text-transform:uppercase;">ID Karyawan</div>
                        <div style="font-weight:600;"><?= htmlspecialchars($user['employee_id']) ?></div>
                    </div>
                </div>
                <div style="display:flex; gap:10px; margin-bottom:12px; align-items:flex-start;">
                    <i class="fas fa-building" style="color:var(--primary); width:16px; margin-top:2px;"></i>
                    <div>
                        <div style="font-size:11px; color:var(--secondary); text-transform:uppercase;">Departemen</div>
                        <div style="font-weight:600;"><?= htmlspecialchars($user['department']) ?></div>
                    </div>
                </div>
                <div style="display:flex; gap:10px; margin-bottom:12px; align-items:flex-start;">
                    <i class="fas fa-envelope" style="color:var(--primary); width:16px; margin-top:2px;"></i>
                    <div>
                        <div style="font-size:11px; color:var(--secondary); text-transform:uppercase;">Email</div>
                        <div style="font-weight:600; word-break:break-all;"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                </div>
                <div style="display:flex; gap:10px; margin-bottom:12px; align-items:flex-start;">
                    <i class="fas fa-calendar" style="color:var(--primary); width:16px; margin-top:2px;"></i>
                    <div>
                        <div style="font-size:11px; color:var(--secondary); text-transform:uppercase;">Bergabung</div>
                        <div style="font-weight:600;"><?= $user['join_date'] ? date('d/m/Y', strtotime($user['join_date'])) : '-' ?></div>
                    </div>
                </div>
                <div style="display:flex; gap:10px; align-items:flex-start;">
                    <i class="fas fa-circle" style="color:var(--success); width:16px; margin-top:4px; font-size:10px;"></i>
                    <div>
                        <div style="font-size:11px; color:var(--secondary); text-transform:uppercase;">Status</div>
                        <div style="font-weight:600; color:var(--success);">Aktif</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div>
        <!-- Edit Profile -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <h4><i class="fas fa-user-edit"></i> Edit Profil</h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background:#f8fafc;">
                        <small style="color:var(--secondary); font-size:12px;">Email tidak dapat diubah</small>
                    </div>
                    <div class="form-group">
                        <label>Telepon</label>
                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-lock"></i> Ubah Password</h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label>Password Saat Ini</label>
                        <input type="password" name="current_password" class="form-control" placeholder="Masukkan password saat ini" required>
                    </div>
                    <div class="form-group">
                        <label>Password Baru</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Minimal 6 karakter" minlength="6" required>
                    </div>
                    <div class="form-group">
                        <label>Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password baru" required>
                    </div>
                    <button type="submit" class="btn" style="background:var(--warning); color:white;">
                        <i class="fas fa-key"></i> Ubah Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
