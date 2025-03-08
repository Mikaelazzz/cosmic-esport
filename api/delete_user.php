<?php
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../page/login.php");
    exit();
}

// Pastikan $_SESSION['user'] adalah array
if (!is_array($_SESSION['user'])) {
    die("Invalid session data. Expected an array.");
}

// Ambil data pengguna dari session
$user = $_SESSION['user'];

// Cek role pengguna
if ($user['role'] !== 'admin') {
    header("Location: ../page/home.php");
    exit();
}

// Koneksi ke database SQLite3
$db = new SQLite3('../db/ukm.db');

// Ambil ID user dari parameter URL
if (isset($_GET['id'])) {
    $userId = $_GET['id'];

    // Query untuk menghapus user
    $query = "DELETE FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);

    // Eksekusi query
    if ($stmt->execute()) {
        // Redirect kembali ke halaman anggota.php setelah penghapusan
        header("Location: ../admin/anggota.php");
        exit();
    } else {
        die("Gagal menghapus user.");
    }
} else {
    // Jika tidak ada ID, redirect ke halaman anggota.php
    header("Location: ../admin/anggota.php");
    exit();
}
?>