<?php
if(!defined('INCLUDED')) {
    define('INCLUDED', true);
}

if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Fungsi untuk cek role
function checkRole($allowed_roles) {
    if(!in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: ../login.php");
        exit();
    }
}

// Fungsi untuk mendapatkan inisial nama
function getInitials($name) {
    $words = explode(' ', $name);
    if(count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

// Fungsi untuk format tanggal Indonesia
function formatTanggal($date) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $split = explode('-', $date);
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

// Fungsi untuk format waktu
function formatWaktu($time) {
    return date('H:i', strtotime($time));
}
?>
