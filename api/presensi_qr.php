<?php
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Koneksi ke database SQLite3
$db = new SQLite3('../db/ukm.db');

// Ambil data dari AJAX
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

$nim = $data['nim']; // NIM dari user yang melakukan presensi
$pertemuanId = $data['pertemuan_id']; // ID pertemuan dari QR
$timestamp = $data['timestamp']; // Timestamp dari QR

// Validasi kode QR
if (!isset($_SESSION['qr_active']) || 
    $_SESSION['qr_active']['pertemuan_id'] !== $pertemuanId || 
    $_SESSION['qr_active']['timestamp'] !== $timestamp) {
    echo json_encode(['success' => false, 'message' => 'Kode QR tidak valid.']);
    exit();
}

// Simpan atau update status kehadiran
$queryCek = "SELECT * FROM absen WHERE pertemuan_id = :pertemuan_id AND nim = :nim";
$stmtCek = $db->prepare($queryCek);
$stmtCek->bindValue(':pertemuan_id', $pertemuanId, SQLITE3_INTEGER);
$stmtCek->bindValue(':nim', $nim, SQLITE3_TEXT);
$resultCek = $stmtCek->execute();
$dataCek = $resultCek->fetchArray(SQLITE3_ASSOC);

if ($dataCek) {
    // Update status jika data sudah ada
    $queryUpdate = "UPDATE absen SET status = 'Hadir' WHERE pertemuan_id = :pertemuan_id AND nim = :nim";
    $stmtUpdate = $db->prepare($queryUpdate);
    $stmtUpdate->bindValue(':pertemuan_id', $pertemuanId, SQLITE3_INTEGER);
    $stmtUpdate->bindValue(':nim', $nim, SQLITE3_TEXT);
    $stmtUpdate->execute();
} else {
    // Insert data baru jika belum ada
    $queryInsert = "INSERT INTO absen (pertemuan_id, nim, status) VALUES (:pertemuan_id, :nim, 'Hadir')";
    $stmtInsert = $db->prepare($queryInsert);
    $stmtInsert->bindValue(':pertemuan_id', $pertemuanId, SQLITE3_INTEGER);
    $stmtInsert->bindValue(':nim', $nim, SQLITE3_TEXT);
    $stmtInsert->execute();
}

// Kirim respons ke client
echo json_encode([
    'success' => true,
    'message' => 'Presensi berhasil dicatat.'
]);
?>