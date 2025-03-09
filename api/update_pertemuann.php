<?php
// Mulai session
session_start();

// Cek apakah pengguna sudah login dan memiliki role admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../page/login.php");
    exit();
}

// Koneksi ke database SQLite3
$db = new SQLite3('../db/ukm.db');

// Ambil data dari form
$id = $_POST['id'];
$namaTopik = $_POST['nama_topik'];
$hari = $_POST['hari'];
$tanggal = $_POST['tanggal'];
$kelas = $_POST['kelas'];
$jamPertemuan = $_POST['jam_pertemuan'];

// Query untuk update data pertemuan
$query = "UPDATE jadwal_pertemuan SET 
            nama_topik = :nama_topik, 
            hari = :hari, 
            tanggal = :tanggal, 
            kelas = :kelas, 
            jam_pertemuan = :jam_pertemuan 
          WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindValue(':nama_topik', $namaTopik, SQLITE3_TEXT);
$stmt->bindValue(':hari', $hari, SQLITE3_TEXT);
$stmt->bindValue(':tanggal', $tanggal, SQLITE3_TEXT);
$stmt->bindValue(':kelas', $kelas, SQLITE3_TEXT);
$stmt->bindValue(':jam_pertemuan', $jamPertemuan, SQLITE3_TEXT);
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);

if ($stmt->execute()) {
    // Jika berhasil, redirect ke halaman pertemuan.php
    header("Location: ../admin/pertemuan.php?success=1");
    exit();
} else {
    // Jika gagal, redirect ke halaman pertemuan.php dengan pesan error
    header("Location: ../admin/pertemuan.php?error=1");
    exit();
}
?>