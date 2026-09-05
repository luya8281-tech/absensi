<?php
// Fungsi cek role
function checkRole($roles) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit();
    }
    if (!in_array($_SESSION['role'], $roles)) {
        // Redirect ke dashboard sesuai role
        if ($_SESSION['role'] === 'admin') header('Location: ../admin/dashboard.php');
        elseif ($_SESSION['role'] === 'staff') header('Location: ../staff/dashboard.php');
        else header('Location: ../employee/dashboard.php');
        exit();
    }
}

// Format waktu HH:MM
function formatWaktu($time) {
    return $time ? date('H:i', strtotime($time)) : '-';
}

// Tentukan menu berdasarkan role
$role = $_SESSION['role'] ?? 'employee';
$base = '..';

if ($role === 'admin') {
    $menus = [
        ['icon'=>'fas fa-tachometer-alt', 'label'=>'Dashboard',       'url'=>$base.'/admin/dashboard.php'],
        ['icon'=>'fas fa-users',           'label'=>'Kelola User',     'url'=>$base.'/admin/users.php'],
        ['icon'=>'fas fa-calendar-check',  'label'=>'Data Absensi',    'url'=>$base.'/admin/attendance.php'],
        ['icon'=>'fas fa-file-alt',        'label'=>'Pengajuan Cuti',  'url'=>$base.'/admin/leave.php'],
        ['icon'=>'fas fa-chart-bar',       'label'=>'Laporan',         'url'=>$base.'/admin/reports.php'],
        ['icon'=>'fas fa-cog',             'label'=>'Pengaturan',      'url'=>$base.'/admin/settings.php'],
    ];
    $panel_label = 'Admin Panel';
} elseif ($role === 'staff') {
    $menus = [
        ['icon'=>'fas fa-tachometer-alt', 'label'=>'Dashboard',      'url'=>$base.'/staff/dashboard.php'],
        ['icon'=>'fas fa-calendar-check', 'label'=>'Data Absensi',   'url'=>$base.'/staff/attendance.php'],
        ['icon'=>'fas fa-file-alt',       'label'=>'Pengajuan Cuti', 'url'=>$base.'/staff/leave.php'],
    ];
    $panel_label = 'Staff Panel';
} else {
    $menus = [
        ['icon'=>'fas fa-tachometer-alt', 'label'=>'Dashboard',      'url'=>$base.'/employee/dashboard.php'],
        ['icon'=>'fas fa-clock',          'label'=>'Absensi Saya',   'url'=>$base.'/employee/absensi.php'],
        ['icon'=>'fas fa-file-alt',       'label'=>'Ajukan Cuti',    'url'=>$base.'/employee/leave_request.php'],
        ['icon'=>'fas fa-user',           'label'=>'Profil Saya',    'url'=>$base.'/employee/profile.php'],
    ];
    $panel_label = 'Employee Panel';
}

$current_url = basename($_SERVER['PHP_SELF']);
$initials = strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Absensi' ?> - Sistem Absensi</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div>
                <h2>⏰ Absensi</h2>
                <p><?= $panel_label ?></p>
            </div>
            <button class="sidebar-close" id="sidebarClose" aria-label="Tutup menu">✕</button>
        </div>
        <nav class="sidebar-menu">
            <?php foreach ($menus as $menu): ?>
            <a href="<?= $menu['url'] ?>"
               class="menu-item <?= (basename($menu['url']) === $current_url) ? 'active' : '' ?>">
                <i class="<?= $menu['icon'] ?>"></i>
                <?= $menu['label'] ?>
            </a>
            <?php endforeach; ?>
            <a href="<?= $base ?>/logout.php" class="menu-item" style="margin-top:20px; color:#f87171;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <header class="top-navbar">
            <div class="navbar-left">
                <button class="hamburger" id="hamburgerBtn" aria-label="Buka menu">
                    <i class="fas fa-bars"></i>
                </button>
                <h3><?= $page_title ?? 'Dashboard' ?></h3>
            </div>
            <div class="navbar-right">
                <div class="user-info">
                    <div>
                        <div class="user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></div>
                        <div class="user-role"><?= htmlspecialchars($_SESSION['position'] ?? ucfirst($role)) ?></div>
                    </div>
                    <div class="user-avatar"><?= $initials ?></div>
                    <a href="<?= $base ?>/logout.php"
                       style="color:var(--danger); font-size:13px; font-weight:600; text-decoration:none; white-space:nowrap;">
                        <i class="fas fa-sign-out-alt"></i>
                        <span style="display:none;" class="logout-label"> Logout</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <main class="content-area">

<script>
// Hamburger toggle
const hamburger = document.getElementById('hamburgerBtn');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
const closeBtn = document.getElementById('sidebarClose');

function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

hamburger.addEventListener('click', openSidebar);
closeBtn.addEventListener('click', closeSidebar);
overlay.addEventListener('click', closeSidebar);

// Realtime clock
function updateClock() {
    const now = new Date();
    const hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const str = hari[now.getDay()] + ', ' + now.getDate() + ' ' + bulan[now.getMonth()] + ' ' + now.getFullYear()
        + ' pukul '
        + String(now.getHours()).padStart(2,'0') + '.'
        + String(now.getMinutes()).padStart(2,'0') + '.'
        + String(now.getSeconds()).padStart(2,'0');
    const el = document.getElementById('realtime-clock');
    if (el) el.textContent = str;
}
setInterval(updateClock, 1000);
updateClock();
</script>
