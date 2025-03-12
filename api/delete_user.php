<?php
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Pastikan $_SESSION['user'] adalah array
if (!is_array($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid session data. Expected an array.']);
    exit();
}

// Ambil data pengguna dari session
$user = $_SESSION['user'];

// Cek role pengguna
if ($user['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
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
        echo json_encode(['success' => true, 'message' => 'Pengguna berhasil dihapus']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus user']);
    }
} else {
    // Jika tidak ada ID, kembalikan pesan error
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
}
?>