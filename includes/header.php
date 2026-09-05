<?php
if(!isset($_SESSION)) {
    session_start();
}
require_once dirname(__DIR__) . '/includes/auth_check.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Dashboard' ?> - Sistem Absensi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-clock"></i> Absensi</h2>
                <p><?= ucfirst($_SESSION['role']) ?> Panel</p>
            </div>
            
            <nav class="sidebar-menu">
                <?php
                $current_page = basename($_SERVER['PHP_SELF']);
                $role = $_SESSION['role'];
                
                // Menu untuk Admin
                if($role == 'admin') {
                    $menus = [
                        'dashboard.php' => ['icon' => 'tachometer-alt', 'label' => 'Dashboard'],
                        'users.php' => ['icon' => 'users', 'label' => 'Kelola User'],
                        'attendance.php' => ['icon' => 'calendar-check', 'label' => 'Data Absensi'],
                        'leave_requests.php' => ['icon' => 'file-alt', 'label' => 'Pengajuan Cuti'],
                        'reports.php' => ['icon' => 'chart-bar', 'label' => 'Laporan'],
                        'settings.php' => ['icon' => 'cog', 'label' => 'Pengaturan'],
                    ];
                }
                // Menu untuk Staff
                elseif($role == 'staff') {
                    $menus = [
                        'dashboard.php' => ['icon' => 'tachometer-alt', 'label' => 'Dashboard'],
                        'attendance.php' => ['icon' => 'calendar-check', 'label' => 'Data Absensi'],
                        'leave_requests.php' => ['icon' => 'file-alt', 'label' => 'Pengajuan Cuti'],
                        'employees.php' => ['icon' => 'users', 'label' => 'Data Karyawan'],
                        'profile.php' => ['icon' => 'user', 'label' => 'Profil Saya'],
                    ];
                }
                // Menu untuk Employee
                else {
                    $menus = [
                        'dashboard.php' => ['icon' => 'tachometer-alt', 'label' => 'Dashboard'],
                        'my_attendance.php' => ['icon' => 'calendar-check', 'label' => 'Absensi Saya'],
                        'leave_request.php' => ['icon' => 'file-alt', 'label' => 'Ajukan Cuti'],
                        'profile.php' => ['icon' => 'user', 'label' => 'Profil Saya'],
                    ];
                }
                
                foreach($menus as $file => $menu) {
                    $active = ($current_page == $file) ? 'active' : '';
                    echo '<a href="'.$file.'" class="menu-item '.$active.'">
                            <i class="fas fa-'.$menu['icon'].'"></i> '.$menu['label'].'
                          </a>';
                }
                ?>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Navbar -->
            <nav class="top-navbar">
                <div class="navbar-left">
                    <h3><?= $page_title ?? 'Dashboard' ?></h3>
                </div>
                <div class="navbar-right">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?= getInitials($_SESSION['full_name']) ?>
                        </div>
                        <div>
                            <div style="font-weight: 600; font-size: 14px;"><?= $_SESSION['full_name'] ?></div>
                            <div style="font-size: 12px; color: var(--secondary);"><?= $_SESSION['position'] ?></div>
                        </div>
                    </div>
                    <a href="../logout.php" class="btn btn-primary" style="padding: 8px 16px; font-size: 13px;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </nav>
            
            <!-- Content Area -->
            <div class="content-area">
