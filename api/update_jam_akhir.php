<?php
session_start();

// Cek apakah pengguna sudah login dan memiliki role admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Cek apakah pertemuan_id ada di session
if (!isset($_SESSION['pertemuan_id'])) {
    echo json_encode(['success' => false, 'message' => 'pertemuan_id not found in session']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$jamAkhir = $data['jamAkhir'];
$status = $data['status'];
$pertemuan_id = $_SESSION['pertemuan_id']; // Ambil pertemuan_id dari session

// Koneksi ke database SQLite3
$db = new SQLite3('../db/ukm.db');

// Query untuk update jam akhir
$query = "UPDATE jadwal_pertemuan SET jam_akhir = :jamAkhir, status = :status WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindValue(':jamAkhir', $jamAkhir, SQLITE3_TEXT);
$stmt->bindValue(':status', $status, SQLITE3_TEXT);
$stmt->bindValue(':id', $pertemuan_id, SQLITE3_INTEGER);

// Eksekusi query
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update jam_akhir']);
}
?>