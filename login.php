<?php
session_start();
require_once 'config/database.php';

// Redirect jika sudah login
if(isset($_SESSION['user_id'])) {
    header("Location: " . $_SESSION['role'] . "/dashboard.php");
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if(empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT * FROM users WHERE email = :email AND status = 'active' LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['employee_id'] = $user['employee_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['department'] = $user['department'];
                $_SESSION['position'] = $user['position'];
                
                header("Location: " . $user['role'] . "/dashboard.php");
                exit();
            } else {
                $error = 'Password salah';
            }
        } else {
            $error = 'Email tidak ditemukan atau akun tidak aktif';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Absensi</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <i class="fas fa-clock fa-2x" style="margin-bottom: 10px;"></i>
                <h1>Sistem Absensi</h1>
                <p>Silakan login untuk melanjutkan</p>
            </div>
            <div class="login-body">
                <?php if($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control" 
                               placeholder="nama@perusahaan.com"
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="Masukkan password"
                               required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>
                
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border); font-size: 13px; color: var(--secondary);">
                    <strong>Akun Demo:</strong><br>
                    Admin: admin@company.com<br>
                    Staff: staff@company.com<br>
                    Employee: employee@company.com<br>
                    Password: <strong>password123</strong>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
