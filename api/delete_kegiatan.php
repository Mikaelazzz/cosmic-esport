<?php
session_start();

// Cek apakah pengguna sudah login dan memiliki role admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../page/login.php");
    exit();
}

// Koneksi ke database SQLite3
$db = new SQLite3('../db/ukm.db');

// Ambil ID kegiatan dari parameter URL
if (!isset($_GET['id'])) {
    header("Location: ../admin/kegiatan.php");
    exit();
}
$id_kegiatan = $_GET['id'];

// Query untuk menghapus kegiatan
$query = "DELETE FROM kegiatan WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindValue(':id', $id_kegiatan, SQLITE3_INTEGER);

if ($stmt->execute()) {
    header("Location: ../admin/kegiatan.php?success=1");
} else {
    header("Location: ../admin/kegiatan.php?error=1");
}
?>