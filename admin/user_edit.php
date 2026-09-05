<?php
session_start();
$page_title = "Edit User";
require_once '../config/database.php';
require_once '../includes/header.php';

checkRole(['admin']);

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';
$user_id = isset($_GET['id']) ? $_GET['id'] : 0;

// Get user data
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $user_id);
$stmt->execute();

if($stmt->rowCount() == 0) {
    header("Location: users.php");
    exit();
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

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
    $status = $_POST['status'];
    
    if(empty($employee_id) || empty($full_name) || empty($email) || empty($role)) {
        $error = "Field yang bertanda * wajib diisi";
    } else {
        // Cek duplikat employee_id
        $query = "SELECT id FROM users WHERE employee_id = :employee_id AND id != :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id);
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $error = "ID Karyawan sudah digunakan";
        } else {
            // Cek duplikat email
            $query = "SELECT id FROM users WHERE email = :email AND id != :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $error = "Email sudah terdaftar";
            } else {
                // Update user
                if(!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $query = "UPDATE users SET 
                              employee_id = :employee_id, 
                              full_name = :full_name, 
                              email = :email, 
                              password = :password,
                              role = :role, 
                              department = :department, 
                              position = :position, 
                              phone = :phone, 
                              address = :address, 
                              join_date = :join_date,
                              status = :status
                              WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':password', $hashed_password);
                } else {
                    $query = "UPDATE users SET 
                              employee_id = :employee_id, 
                              full_name = :full_name, 
                              email = :email, 
                              role = :role, 
                              department = :department, 
                              position = :position, 
                              phone = :phone, 
                              address = :address, 
                              join_date = :join_date,
                              status = :status
                              WHERE id = :id";
                    $stmt = $db->prepare($query);
                }
                
                $stmt->bindParam(':employee_id', $employee_id);
                $stmt->bindParam(':full_name', $full_name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':role', $role);
                $stmt->bindParam(':department', $department);
                $stmt->bindParam(':position', $position);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':address', $address);
                $stmt->bindParam(':join_date', $join_date);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':id', $user_id);
                
                if($stmt->execute()) {
                    $success = "Data user berhasil diupdate";
                    // Refresh user data
                    $query = "SELECT * FROM users WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $user_id);
                    $stmt->execute();
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = "Gagal mengupdate user";
                }
            }
        }
    }
}
?>

<?php if($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?= $success ?>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-user-edit"></i> Edit User: <?= htmlspecialchars($user['full_name']) ?></h4>
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
                           value="<?= htmlspecialchars($user['employee_id']) ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="full_name">Nama Lengkap *</label>
                    <input type="text" 
                           id="full_name" 
                           name="full_name" 
                           class="form-control" 
                           value="<?= htmlspecialchars($user['full_name']) ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           value="<?= htmlspecialchars($user['email']) ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password (Kosongkan jika tidak diubah)</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="Minimal 6 karakter"
                           minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="role">Role *</label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="staff" <?= $user['role'] == 'staff' ? 'selected' : '' ?>>Staff</option>
                        <option value="employee" <?= $user['role'] == 'employee' ? 'selected' : '' ?>>Employee</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="active" <?= $user['status'] == 'active' ? 'selected' : '' ?>>Aktif</option>
                        <option value="inactive" <?= $user['status'] == 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="department">Departemen</label>
                    <input type="text" 
                           id="department" 
                           name="department" 
                           class="form-control" 
                           value="<?= htmlspecialchars($user['department']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="position">Posisi</label>
                    <input type="text" 
                           id="position" 
                           name="position" 
                           class="form-control" 
                           value="<?= htmlspecialchars($user['position']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">Telepon</label>
                    <input type="tel" 
                           id="phone" 
                           name="phone" 
                           class="form-control" 
                           value="<?= htmlspecialchars($user['phone']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="join_date">Tanggal Bergabung</label>
                    <input type="date" 
                           id="join_date" 
                           name="join_date" 
                           class="form-control"
                           value="<?= $user['join_date'] ?>">
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="address">Alamat</label>
                    <textarea id="address" 
                              name="address" 
                              class="form-control" 
                              rows="3"><?= htmlspecialchars($user['address']) ?></textarea>
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border);">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update User
                </button>
                <a href="users.php" class="btn" style="background: var(--secondary); color: white;">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
