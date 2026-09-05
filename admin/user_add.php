<?php
session_start();
$page_title = "Tambah User";
require_once '../config/database.php';
require_once '../includes/header.php';

checkRole(['admin']);

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = trim($_POST['employee_id']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $department = trim($_POST['department']);
    $position = trim($_POST['position']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $join_date = $_POST['join_date'];
    
    // Validasi
    if(empty($employee_id) || empty($full_name) || empty($email) || empty($password) || empty($role)) {
        $error = "Field yang bertanda * wajib diisi";
    } else {
        // Cek duplikat employee_id
        $query = "SELECT id FROM users WHERE employee_id = :employee_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $error = "ID Karyawan sudah digunakan";
        } else {
            // Cek duplikat email
            $query = "SELECT id FROM users WHERE email = :email";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $error = "Email sudah terdaftar";
            } else {
                // Insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $query = "INSERT INTO users (employee_id, full_name, email, password, role, department, position, phone, address, join_date) 
                          VALUES (:employee_id, :full_name, :email, :password, :role, :department, :position, :phone, :address, :join_date)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':employee_id', $employee_id);
                $stmt->bindParam(':full_name', $full_name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':role', $role);
                $stmt->bindParam(':department', $department);
                $stmt->bindParam(':position', $position);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':address', $address);
                $stmt->bindParam(':join_date', $join_date);
                
                if($stmt->execute()) {
                    header("Location: users.php");
                    exit();
                } else {
                    $error = "Gagal menambahkan user";
                }
            }
        }
    }
}
?>

<?php if($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-user-plus"></i> Tambah User Baru</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="employee_id">ID Karyawan *</label>
                    <input type="text" 
                           id="employee_id" 
                           name="employee_id" 
                           class="form-control" 
                           placeholder="Contoh: EMP001"
                           value="<?= isset($_POST['employee_id']) ? htmlspecialchars($_POST['employee_id']) : '' ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="full_name">Nama Lengkap *</label>
                    <input type="text" 
                           id="full_name" 
                           name="full_name" 
                           class="form-control" 
                           placeholder="Nama lengkap"
                           value="<?= isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : '' ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           placeholder="email@perusahaan.com"
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="Minimal 6 karakter"
                           minlength="6"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="role">Role *</label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="">Pilih Role</option>
                        <option value="admin" <?= isset($_POST['role']) && $_POST['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="staff" <?= isset($_POST['role']) && $_POST['role'] == 'staff' ? 'selected' : '' ?>>Staff</option>
                        <option value="employee" <?= isset($_POST['role']) && $_POST['role'] == 'employee' ? 'selected' : '' ?>>Employee</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="department">Departemen</label>
                    <input type="text" 
                           id="department" 
                           name="department" 
                           class="form-control" 
                           placeholder="Contoh: IT, HR, Sales"
                           value="<?= isset($_POST['department']) ? htmlspecialchars($_POST['department']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="position">Posisi</label>
                    <input type="text" 
                           id="position" 
                           name="position" 
                           class="form-control" 
                           placeholder="Contoh: Manager, Staff"
                           value="<?= isset($_POST['position']) ? htmlspecialchars($_POST['position']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">Telepon</label>
                    <input type="tel" 
                           id="phone" 
                           name="phone" 
                           class="form-control" 
                           placeholder="08xxxxxxxxxx"
                           value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="join_date">Tanggal Bergabung</label>
                    <input type="date" 
                           id="join_date" 
                           name="join_date" 
                           class="form-control"
                           value="<?= isset($_POST['join_date']) ? $_POST['join_date'] : date('Y-m-d') ?>">
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="address">Alamat</label>
                    <textarea id="address" 
                              name="address" 
                              class="form-control" 
                              rows="3" 
                              placeholder="Alamat lengkap"><?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '' ?></textarea>
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border);">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan User
                </button>
                <a href="users.php" class="btn" style="background: var(--secondary); color: white;">
                    <i class="fas fa-times"></i> Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
