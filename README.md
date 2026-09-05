# Sistem Absensi Karyawan

Aplikasi web untuk mengelola absensi karyawan berbasis PHP & MySQL.

## Fitur

### Admin
- Dashboard dengan statistik lengkap & grafik
- Kelola user (tambah, edit, hapus, toggle status)
- Manajemen data absensi
- Persetujuan pengajuan cuti
- Laporan bulanan dengan export CSV
- Pengaturan sistem

### Staff
- Dashboard monitoring absensi
- Lihat data absensi semua karyawan
- Proses pengajuan cuti
- Direktori karyawan

### Employee
- Dashboard pribadi dengan statistik
- Check in & Check out real-time
- Riwayat absensi
- Pengajuan cuti

## Instalasi

### Requirement
- PHP 7.4+
- MySQL 5.7+
- Web Server (Apache/Nginx)

### Langkah Instalasi

1. Clone repositori
   git clone https://github.com/username/attendance-system.git

2. Buat database
   mysql -u root -p < database.sql

3. Konfigurasi database
   Edit file config/database.php sesuaikan dengan konfigurasi database Anda

4. Jalankan di web server
   - Untuk XAMPP: letakkan di folder htdocs
   - Akses: http://localhost/attendance-system

## Akun Default

| Role     | Email                    | Password    |
|----------|--------------------------|-------------|
| Admin    | admin@company.com        | password123 |
| Staff    | staff@company.com        | password123 |
| Employee | employee@company.com     | password123 |

## Struktur Folder

attendance-system/
├── admin/
│   ├── dashboard.php
│   ├── users.php
│   ├── user_add.php
│   ├── user_edit.php
│   ├── attendance.php
│   ├── leave_requests.php
│   ├── reports.php
│   └── settings.php
├── staff/
│   ├── dashboard.php
│   ├── attendance.php
│   ├── leave_requests.php
│   ├── employees.php
│   └── profile.php
├── employee/
│   ├── dashboard.php
│   ├── my_attendance.php
│   ├── leave_request.php
│   └── profile.php
├── config/
│   └── database.php
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── auth_check.php
├── assets/
│   ├── css/style.css
│   └── js/main.js
├── index.php
├── login.php
├── logout.php
└── database.sql

## Upload ke GitHub

git init
git add .
git commit -m "Initial commit: Sistem Absensi Karyawan"
git remote add origin https://github.com/username/attendance-system.git
git push -u origin main
