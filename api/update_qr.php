<?php
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Ambil data dari AJAX
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

$pertemuanId = $data['pertemuan_id'];
$timestamp = $data['timestamp'];

// Simpan kode QR yang aktif di session
$_SESSION['qr_active'] = [
    'pertemuan_id' => $pertemuanId,
    'timestamp' => $timestamp
];

// Kirim respons ke client
echo json_encode([
    'success' => true,
    'message' => 'Kode QR berhasil diperbarui.'
]);
?>