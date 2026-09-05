<?php
session_start();
$page_title = "Data Absensi";
require_once '../config/database.php';
require_once '../includes/header.php';

checkRole(['admin']);

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Handle Add Attendance
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if($_POST['action'] == 'add') {
        $user_id = $_POST['user_id'];
        $date = $_POST['date'];
        $check_in = $_POST['check_in'];
        $check_out = $_POST['check_out'];
        $status = $_POST['status'];
        $notes = trim($_POST['notes']);

        // Cek apakah sudah ada absensi di tanggal tersebut
        $query = "SELECT id FROM attendance WHERE user_id = :user_id AND date = :date";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':date', $date);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $error = "Karyawan ini sudah memiliki data absensi di tanggal tersebut";
        } else {
            $query = "INSERT INTO attendance (user_id, date, check_in, check_out, status, notes) 
                      VALUES (:user_id, :date, :check_in, :check_out, :status, :notes)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':date', $date);
            $stmt->bindParam(':check_in', $check_in ?: null);
            $stmt->bindParam(':check_out', $check_out ?: null);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':notes', $notes);

            if($stmt->execute()) {
                $success = "Data absensi berhasil ditambahkan";
            } else {
                $error = "Gagal menambahkan data absensi";
            }
        }
    } elseif($_POST['action'] == 'edit') {
        $att_id = $_POST['att_id'];
        $check_in = $_POST['check_in'];
        $check_out = $_POST['check_out'];
        $status = $_POST['status'];
        $notes = trim($_POST['notes']);

        $query = "UPDATE attendance SET check_in = :check_in, check_out = :check_out, 
                  status = :status, notes = :notes WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':check_in', $check_in ?: null);
        $stmt->bindParam(':check_out', $check_out ?: null);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':id', $att_id);

        if($stmt->execute()) {
            $success = "Data absensi berhasil diupdate";
        } else {
            $error = "Gagal mengupdate data absensi";
        }
    }
}

// Handle Delete
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = "DELETE FROM attendance WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    if($stmt->execute()) {
        $success = "Data absensi berhasil dihapus";
    }
}

// Filter
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where = "WHERE a.date BETWEEN :date_from AND :date_to";
$params = [':date_from' => $date_from, ':date_to' => $date_to];

if(!empty($search)) {
    $where .= " AND (u.full_name LIKE :search OR u.employee_id LIKE :search)";
    $params[':search'] = "%$search%";
}

if(!empty($status_filter)) {
    $where .= " AND a.status = :status";
    $params[':status'] = $status_filter;
}

$query = "SELECT a.*, u.full_name, u.employee_id, u.department
          FROM attendance a
          JOIN users u ON a.user_id = u.id
          $where
          ORDER BY a.date DESC, a.created_at DESC";
$stmt = $db->prepare($query);
foreach($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all users for dropdown
$query = "SELECT id, employee_id, full_name FROM users WHERE status = 'active' ORDER BY full_name";
$stmt = $db->prepare($query);
$stmt->execute();
$all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
<?php endif; ?>
<?php if($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
<?php endif; ?>

<!-- Filter -->
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
        <h4><i class="fas fa-calendar-check"></i> Data Absensi</h4>
        <button onclick="document.getElementById('modal-add').style.display='flex'" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Absensi
        </button>
    </div>
    <div class="card-body">
        <form method="GET" style="display:grid; grid-template-columns:1fr 1fr 2fr 1fr auto; gap:10px; margin-bottom:20px;">
            <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
            <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
            <input type="text" name="search" class="form-control" placeholder="Cari nama atau ID karyawan..." value="<?= htmlspecialchars($search) ?>">
            <select name="status" class="form-control">
                <option value="">Semua Status</option>
                <option value="present" <?= $status_filter=='present'?'selected':'' ?>>Hadir</option>
                <option value="late" <?= $status_filter=='late'?'selected':'' ?>>Terlambat</option>
                <option value="absent" <?= $status_filter=='absent'?'selected':'' ?>>Tidak Hadir</option>
                <option value="leave" <?= $status_filter=='leave'?'selected':'' ?>>Izin</option>
                <option value="sick" <?= $status_filter=='sick'?'selected':'' ?>>Sakit</option>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
        </form>

        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Departemen</th>
                        <th>Jam Masuk</th>
                        <th>Jam Keluar</th>
                        <th>Durasi</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($attendances) > 0): ?>
                        <?php foreach($attendances as $att): ?>
                            <?php
                            $duration = '-';
                            if($att['check_in'] && $att['check_out']) {
                                $in = new DateTime($att['check_in']);
                                $out = new DateTime($att['check_out']);
                                $diff = $in->diff($out);
                                $duration = $diff->h . 'j ' . $diff->i . 'm';
                            }
                            $status_map = [
                                'present' => ['label' => 'Hadir', 'class' => 'badge-success'],
                                'late' => ['label' => 'Terlambat', 'class' => 'badge-warning'],
                                'absent' => ['label' => 'Tidak Hadir', 'class' => 'badge-danger'],
                                'leave' => ['label' => 'Izin', 'class' => 'badge-info'],
                                'sick' => ['label' => 'Sakit', 'class' => 'badge-warning'],
                            ];
                            $s = $status_map[$att['status']] ?? ['label' => $att['status'], 'class' => 'badge-info'];
                            ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($att['date'])) ?></td>
                                <td><strong><?= htmlspecialchars($att['employee_id']) ?></strong></td>
                                <td><?= htmlspecialchars($att['full_name']) ?></td>
                                <td><?= htmlspecialchars($att['department']) ?></td>
                                <td><?= $att['check_in'] ? formatWaktu($att['check_in']) : '-' ?></td>
                                <td><?= $att['check_out'] ? formatWaktu($att['check_out']) : '-' ?></td>
                                <td><?= $duration ?></td>
                                <td><span class="badge <?= $s['class'] ?>"><?= $s['label'] ?></span></td>
                                <td style="font-size:13px; max-width:150px;"><?= htmlspecialchars($att['notes'] ?? '-') ?></td>
                                <td>
                                    <div style="display:flex; gap:5px;">
                                        <button onclick="openEditModal(<?= htmlspecialchars(json_encode($att)) ?>)" 
                                                class="btn btn-primary" style="padding:5px 10px; font-size:12px;">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete=<?= $att['id'] ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" 
                                           class="btn" style="padding:5px 10px; font-size:12px; background:var(--danger); color:white;"
                                           onclick="return confirm('Hapus data absensi ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="text-align:center; padding:40px; color:var(--secondary);">
                                <i class="fas fa-calendar-times fa-3x" style="opacity:0.3; margin-bottom:10px;"></i>
                                <p>Tidak ada data absensi</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top:15px; padding-top:15px; border-top:1px solid var(--border); color:var(--secondary); font-size:14px;">
            Total: <strong><?= count($attendances) ?></strong> record
        </div>
    </div>
</div>

<!-- Modal Add -->
<div id="modal-add" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:12px; width:100%; max-width:520px; max-height:90vh; overflow-y:auto;">
        <div style="padding:20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
            <h4><i class="fas fa-plus"></i> Tambah Data Absensi</h4>
            <button onclick="document.getElementById('modal-add').style.display='none'" 
                    style="background:none; border:none; font-size:20px; cursor:pointer;">&times;</button>
        </div>
        <div style="padding:20px;">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Karyawan *</label>
                    <select name="user_id" class="form-control" required>
                        <option value="">Pilih Karyawan</option>
                        <?php foreach($all_users as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['employee_id'] . ' - ' . $u['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tanggal *</label>
                    <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="form-group">
                        <label>Jam Masuk</label>
                        <input type="time" name="check_in" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Jam Keluar</label>
                        <input type="time" name="check_out" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" class="form-control" required>
                        <option value="present">Hadir</option>
                        <option value="late">Terlambat</option>
                        <option value="absent">Tidak Hadir</option>
                        <option value="leave">Izin</option>
                        <option value="sick">Sakit</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Keterangan tambahan..."></textarea>
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                    <button type="button" onclick="document.getElementById('modal-add').style.display='none'" 
                            class="btn" style="background:var(--secondary); color:white;">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div id="modal-edit" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:12px; width:100%; max-width:520px; max-height:90vh; overflow-y:auto;">
        <div style="padding:20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
            <h4><i class="fas fa-edit"></i> Edit Data Absensi</h4>
            <button onclick="document.getElementById('modal-edit').style.display='none'" 
                    style="background:none; border:none; font-size:20px; cursor:pointer;">&times;</button>
        </div>
        <div style="padding:20px;">
            <form method="POST" id="form-edit">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="att_id" id="edit-att-id">
                <div class="form-group">
                    <label>Karyawan</label>
                    <input type="text" id="edit-name" class="form-control" disabled>
                </div>
                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="text" id="edit-date-display" class="form-control" disabled>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="form-group">
                        <label>Jam Masuk</label>
                        <input type="time" name="check_in" id="edit-check-in" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Jam Keluar</label>
                        <input type="time" name="check_out" id="edit-check-out" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" id="edit-status" class="form-control" required>
                        <option value="present">Hadir</option>
                        <option value="late">Terlambat</option>
                        <option value="absent">Tidak Hadir</option>
                        <option value="leave">Izin</option>
                        <option value="sick">Sakit</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <textarea name="notes" id="edit-notes" class="form-control" rows="2"></textarea>
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
                    <button type="button" onclick="document.getElementById('modal-edit').style.display='none'" 
                            class="btn" style="background:var(--secondary); color:white;">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditModal(data) {
    document.getElementById('edit-att-id').value = data.id;
    document.getElementById('edit-name').value = data.full_name;
    document.getElementById('edit-date-display').value = data.date;
    document.getElementById('edit-check-in').value = data.check_in ? data.check_in.substring(0,5) : '';
    document.getElementById('edit-check-out').value = data.check_out ? data.check_out.substring(0,5) : '';
    document.getElementById('edit-status').value = data.status;
    document.getElementById('edit-notes').value = data.notes || '';
    document.getElementById('modal-edit').style.display = 'flex';
}
</script>

<?php require_once '../includes/footer.php'; ?>
