<?php
session_start();

date_default_timezone_set('Asia/Jakarta'); // Atur zona waktu

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Ambil parameter dari URL
$pertemuanId = $_GET['pertemuan_id'];
$timestamp = $_GET['timestamp'];

// Validasi apakah kode QR masih valid (belum kadaluarsa)
$waktu_sekarang = time(); // Waktu sekarang dalam timestamp
$waktu_qr = intval($timestamp); // Timestamp dari QR

// Jika selisih waktu lebih dari 10 detik, kode QR dianggap kadaluarsa
$is_expired = ($waktu_sekarang - $waktu_qr) > 5;

// Kirim respons ke client
echo json_encode([
    'success' => true,
    'is_expired' => $is_expired
]);
?>