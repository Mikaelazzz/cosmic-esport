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

// Ambil ID pertemuan dari parameter URL
if (!isset($_GET['id'])) {
    // Jika tidak ada ID, redirect ke halaman pertemuan
    header("Location: ../admin/pertemuan.php");
    exit();
}
$id_pertemuan = $_GET['id'];

// Query untuk menghapus data pertemuan berdasarkan ID
$query = "DELETE FROM jadwal_pertemuan WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindValue(':id', $id_pertemuan, SQLITE3_INTEGER);

// Eksekusi query
if ($stmt->execute()) {
    // Jika berhasil, kembalikan respons JSON
    header("Location: ../admin/pertemuan.php");
    exit();
} else {
    // Jika gagal, kembalikan respons JSON dengan pesan error
    die("Gagal menghapus pertemuan.");
}
?>