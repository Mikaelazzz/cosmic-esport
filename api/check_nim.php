<?php
session_start();

// Cek apakah pengguna sudah login dan memiliki role admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['exists' => false]);
    exit();
}

// Koneksi ke database SQLite3
$db = new SQLite3('../db/ukm.db');

// Ambil NIM dari query parameter
$nim = $_GET['nim'] ?? '';

// Query untuk mengecek apakah NIM sudah digunakan
$query = "SELECT COUNT(*) as count FROM users WHERE nim = :nim";
$stmt = $db->prepare($query);
$stmt->bindValue(':nim', $nim, SQLITE3_TEXT);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);

echo json_encode(['exists' => $row['count'] > 0]);
?>