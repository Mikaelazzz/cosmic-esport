<?php
header('Content-Type: application/json'); // Set the correct content type

// Koneksi ke database SQLite3
$db = new SQLite3('../db/ukm.db');

// Ambil ID pertemuan dari parameter GET
$pertemuan_id = $_GET['pertemuan_id'];

// Query untuk mengambil status sesi
$query = "SELECT status FROM jadwal_pertemuan WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindValue(':id', $pertemuan_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);

// Kembalikan status sesi dalam format JSON
if ($row) {
    echo json_encode(['success' => true, 'status' => $row['status']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Sesi tidak ditemukan']);
}
?>