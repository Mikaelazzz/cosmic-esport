<?php
session_start();

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

$pertemuanId = $data['pertemuan_id'];
$timestamp = $data['timestamp'];
$qrCode = "pertemuan:{$pertemuanId}:{$timestamp}";

// Koneksi ke database
$db = new SQLite3('../db/ukm.db');

// Update QR code dan timestamp di database
$query = "UPDATE jadwal_pertemuan SET qr_code = :qr_code, qr_timestamp = :timestamp WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindValue(':qr_code', $qrCode, SQLITE3_TEXT);
$stmt->bindValue(':timestamp', $timestamp, SQLITE3_TEXT);
$stmt->bindValue(':id', $pertemuanId, SQLITE3_INTEGER);

if ($stmt->execute()) {
    // Hapus QR codes lama dari database
    $deleteOldQR = "UPDATE jadwal_pertemuan SET qr_code = NULL, qr_timestamp = NULL 
                    WHERE id = :id 
                    AND qr_timestamp < :expire_time";
    $stmtDelete = $db->prepare($deleteOldQR);
    $expireTime = $timestamp - 5000; // 5 detik dalam milliseconds
    $stmtDelete->bindValue(':id', $pertemuanId, SQLITE3_INTEGER);
    $stmtDelete->bindValue(':expire_time', $expireTime, SQLITE3_TEXT);
    $stmtDelete->execute();

    echo json_encode(['success' => true, 'message' => 'QR Code updated']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update QR Code']);
}
?>