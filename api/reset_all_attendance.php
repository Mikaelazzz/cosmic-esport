<?php
session_start();

// Cek apakah pengguna sudah login dan memiliki role admin
if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki akses.']);
    exit();
}

if ($_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki akses.']);
    exit();
}

// Ambil ID atau NIM user yang akan direset kehadirannya dari request
$userId = $_POST['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'ID pengguna tidak valid.']);
    exit();
}

// Connect to database
$db = new SQLite3('../db/ukm.db');

// Query untuk menghapus semua kehadiran user tertentu berdasarkan NIM
$query = "DELETE FROM absen WHERE nim = :nim";
$stmt = $db->prepare($query);
$stmt->bindValue(':nim', $userId, SQLITE3_TEXT); // Bind NIM user yang akan direset
$result = $stmt->execute();

if ($result) {
    echo json_encode(['status' => 'success', 'message' => 'Seluruh kehadiran berhasil direset untuk user ini.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan saat mereset kehadiran.']);
}
?>