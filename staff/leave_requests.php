<?php
session_start();
$page_title = "Pengajuan Cuti";
require_once '../config/database.php';

// Reuse admin leave_requests
$_SESSION['role'] = 'staff';
include '../admin/leave_requests.php';
?>
