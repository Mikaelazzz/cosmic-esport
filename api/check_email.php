<?php
session_start();

// Cek apakah pengguna sudah login dan memiliki role admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['exists' => false]);
    exit();
}

// Koneksi ke database SQLite3
$db = new SQLite3('../db/ukm.db');

// Ambil email dari query parameter
$email = $_GET['email'] ?? '';

// Query untuk mengecek apakah email sudah digunakan
$query = "SELECT COUNT(*) as count FROM users WHERE email = :email";
$stmt = $db->prepare($query);
$stmt->bindValue(':email', $email, SQLITE3_TEXT);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);

echo json_encode(['exists' => $row['count'] > 0]);
?>