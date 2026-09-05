<?php
session_start();
$page_title = "Kelola User";
require_once '../config/database.php';
require_once '../includes/header.php';

checkRole(['admin']);

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Handle Delete
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = "DELETE FROM users WHERE id = :id AND id != :current_user";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':current_user', $_SESSION['user_id']);
    
    if($stmt->execute()) {
        $success = "User berhasil dihapus";
    } else {
        $error = "Gagal menghapus user";
    }
}

// Handle Status Toggle
if(isset($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    $query = "UPDATE users SET status = IF(status = 'active', 'inactive', 'active') WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if($stmt->execute()) {
        $success = "Status user berhasil diubah";
    }
}

// Filter & Search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where_conditions = ["1=1"];
$params = [];

if(!empty($search)) {
    $where_conditions[] = "(full_name LIKE :search OR employee_id LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}

if(!empty($role_filter)) {
    $where_conditions[] = "role = :role";
    $params[':role'] = $role_filter;
}

if(!empty($status_filter)) {
    $where_conditions[] = "status = :status";
    $params[':status'] = $status_filter;
}

$where_clause = implode(" AND ", $where_conditions);

// Get Users
$query = "SELECT * FROM users WHERE $where_clause ORDER BY created_at DESC";
$stmt = $db->prepare($query);
foreach($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h4><i class="fas fa-users"></i> Manajemen User</h4>
        <a href="user_add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah User
        </a>
    </div>
    <div class="card-body">
        <!-- Filter & Search -->
        <form method="GET" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 10px; margin-bottom: 20px;">
            <input type="text" 
                   name="search" 
                   class="form-control" 
                   placeholder="Cari nama, ID, atau email..."
                   value="<?= htmlspecialchars($search) ?>">
            
            <select name="role" class="form-control">
                <option value="">Semua Role</option>
                <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="staff" <?= $role_filter == 'staff' ? 'selected' : '' ?>>Staff</option>
                <option value="employee" <?= $role_filter == 'employee' ? 'selected' : '' ?>>Employee</option>
            </select>
            
            <select name="status" class="form-control">
                <option value="">Semua Status</option>
                <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Aktif</option>
                <option value="inactive" <?= $status_filter == 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
            </select>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Filter
            </button>
        </form>
        
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Lengkap</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Departemen</th>
                        <th>Posisi</th>
                        <th>Telepon</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($users) > 0): ?>
                        <?php foreach($users as $user): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($user['employee_id']) ?></strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="user-avatar" style="width: 35px; height: 35px; font-size: 12px;">
                                            <?= getInitials($user['full_name']) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600;"><?= htmlspecialchars($user['full_name']) ?></div>
                                            <div style="font-size: 12px; color: var(--secondary);">
                                                Bergabung: <?= date('d/m/Y', strtotime($user['join_date'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <?php
                                    $role_badges = [
                                        'admin' => 'badge-danger',
                                        'staff' => 'badge-warning',
                                        'employee' => 'badge-info'
                                    ];
                                    ?>
                                    <span class="badge <?= $role_badges[$user['role']] ?>">
                                        <?= strtoupper($user['role']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($user['department']) ?></td>
                                <td><?= htmlspecialchars($user['position']) ?></td>
                                <td><?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                                <td>
                                    <span class="badge <?= $user['status'] == 'active' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $user['status'] == 'active' ? 'Aktif' : 'Tidak Aktif' ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="user_edit.php?id=<?= $user['id'] ?>" 
                                           class="btn btn-primary" 
                                           style="padding: 5px 10px; font-size: 12px;"
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?toggle_status=<?= $user['id'] ?>" 
                                           class="btn" 
                                           style="padding: 5px 10px; font-size: 12px; background: var(--warning); color: white;"
                                           title="Toggle Status"
                                           onclick="return confirm('Ubah status user ini?')">
                                            <i class="fas fa-power-off"></i>
                                        </a>
                                        <?php if($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="?delete=<?= $user['id'] ?>" 
                                               class="btn" 
                                               style="padding: 5px 10px; font-size: 12px; background: var(--danger); color: white;"
                                               title="Hapus"
                                               onclick="return confirm('Yakin ingin menghapus user ini? Data tidak dapat dikembalikan!')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: var(--secondary);">
                                <i class="fas fa-users fa-3x" style="margin-bottom: 10px; opacity: 0.3;"></i>
                                <p>Tidak ada user ditemukan</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid var(--border); color: var(--secondary); font-size: 14px;">
            Total: <strong><?= count($users) ?></strong> user
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
