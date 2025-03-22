<?php
session_start();

date_default_timezone_set('Asia/Jakarta');

// Fungsi untuk logging
function logDebug($message) {
    error_log(date('Y-m-d H:i:s') . " - DEBUG: " . $message);
}

// Fungsi untuk validasi QR code
function validateQRCode($qrData, $currentTime) {
    $qrParts = explode(':', $qrData);
    logDebug("QR Parts: " . print_r($qrParts, true));

    if (count($qrParts) !== 3) {
        throw new Exception("Format QR code tidak valid");
    }

    $prefix = $qrParts[0];
    $pertemuanId = $qrParts[1];
    $qrTimestamp = (int)$qrParts[2];

    logDebug("Current Time: " . $currentTime);
    logDebug("QR Timestamp: " . $qrTimestamp);
    logDebug("Time Difference: " . ($currentTime - $qrTimestamp) . "ms");

    // Validasi timestamp (5 detik)
    if (($currentTime - $qrTimestamp) > 5000) {
        throw new Exception("QR Code telah kadaluarsa");
    }

    return [
        'prefix' => $prefix,
        'pertemuan_id' => $pertemuanId,
        'timestamp' => $qrTimestamp
    ];
}

$isTransactionActive = false; // Set flag ke false setelah commit
try {
    // Koneksi database
    $db = new SQLite3('../db/ukm.db');
    if (!$db) {
        throw new Exception("Gagal terhubung ke database");
    }

    // Parse input data
    $rawData = file_get_contents('php://input');
    logDebug("Raw data received: " . $rawData);
    
    $data = json_decode($rawData, true);
    if (!$data) {
        throw new Exception("Invalid JSON data");
    }

    // Validasi input
    if (!isset($data['qr_data']) || !isset($data['nim']) || !isset($data['status'])) {
        throw new Exception("Data tidak lengkap");
    }

    // Validasi QR code
    $currentTime = round(microtime(true) * 1000);
    $qrInfo = validateQRCode($data['qr_data'], $currentTime);
    $pertemuanId = $qrInfo['pertemuan_id'];

    // Flag untuk menandai apakah transaksi sedang berlangsung
    $isTransactionActive = false;

    // Mulai transaction
    $db->exec('BEGIN');
    $isTransactionActive = true; // Set flag ke true

    // Cek status pertemuan
    $queryPertemuan = "SELECT status, hari FROM jadwal_pertemuan WHERE id = :id";
    $stmtPertemuan = $db->prepare($queryPertemuan);
    if (!$stmtPertemuan) {
        throw new Exception("Database error: " . $db->lastErrorMsg());
    }

    $stmtPertemuan->bindValue(':id', $pertemuanId, SQLITE3_INTEGER);
    $resultPertemuan = $stmtPertemuan->execute();
    $pertemuan = $resultPertemuan->fetchArray(SQLITE3_ASSOC);

    if (!$pertemuan) {
        throw new Exception("Pertemuan tidak ditemukan");
    }

    if ($pertemuan['status'] !== 'berlangsung') {
        throw new Exception("Sesi pertemuan tidak aktif");
    }

    // Cek duplikasi absensi
    $queryCek = "SELECT id FROM absen WHERE pertemuan_id = :pertemuan_id AND nim = :nim";
    $stmtCek = $db->prepare($queryCek);
    $stmtCek->bindValue(':pertemuan_id', $pertemuanId, SQLITE3_INTEGER);
    $stmtCek->bindValue(':nim', $data['nim'], SQLITE3_TEXT);
    
    $resultCek = $stmtCek->execute();
    if ($resultCek->fetchArray(SQLITE3_ASSOC)) {
        throw new Exception("Anda sudah melakukan absensi");
    }

    // Insert absensi
    $waktu_sekarang = date('Y-m-d H:i:s');
    $queryInsert = "INSERT INTO absen (pertemuan_id, nim, status, qr_code, hari, jam) 
                    VALUES (:pertemuan_id, :nim, :status, :qr_code, :hari, :jam)";
    
    $stmtInsert = $db->prepare($queryInsert);
    if (!$stmtInsert) {
        throw new Exception("Gagal mempersiapkan query: " . $db->lastErrorMsg());
    }

    $stmtInsert->bindValue(':pertemuan_id', $pertemuanId, SQLITE3_INTEGER);
    $stmtInsert->bindValue(':nim', $data['nim'], SQLITE3_TEXT);
    $stmtInsert->bindValue(':status', $data['status'], SQLITE3_TEXT);
    $stmtInsert->bindValue(':qr_code', $data['qr_data'], SQLITE3_TEXT);
    $stmtInsert->bindValue(':hari', $pertemuan['hari'], SQLITE3_TEXT);
    $stmtInsert->bindValue(':jam', $waktu_sekarang, SQLITE3_TEXT);

    if (!$stmtInsert->execute()) {
        throw new Exception("Gagal menyimpan data: " . $db->lastErrorMsg());
    }

    // Commit transaction
    $db->exec('COMMIT');
    $isTransactionActive = false; // Set flag ke false setelah commit
    logDebug("Absensi berhasil dicatat");

    // Response sukses
    echo json_encode([
        'success' => true,
        'message' => 'Absensi berhasil dicatat',
        'nim' => $data['nim'],
        'waktu' => $waktu_sekarang,
        'status' => 'success'
    ]);

} catch (Exception $e) {
    logDebug("Error: " . $e->getMessage());
    
    // Rollback hanya jika transaksi sedang aktif
    if (isset($db) && $isTransactionActive) {
        $db->exec('ROLLBACK');
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'status' => 'invalid'
    ]);
}
?>