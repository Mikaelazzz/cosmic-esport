<?php
// add_pertemuan.php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../page/login.php");
    exit();
}

$db = new SQLite3('../db/ukm.db');

$data = json_decode(file_get_contents('php://input'), true);

$namaTopik = $data['nama_topik'];
$hari = $data['hari'];
$tanggal = $data['tanggal'];
$kelas = $data['kelas'];
$jamPertemuan = $data['jam_pertemuan'];
$jamMulai = $data['jam_mulai'];
$jamAkhir = $data['jam_akhir'];

// Format nama topik
$formattedNamaTopik = "Pertemuan Rutin"; // Default
if (!empty($namaTopik)) {
    $formattedNamaTopik .= " - " . $namaTopik; // Tambahkan topik acara jika ada
}

// Query untuk menambahkan pertemuan baru
$query = "INSERT INTO jadwal_pertemuan (nama_topik, hari, tanggal, kelas, jam_pertemuan, jam_mulai, jam_akhir, qr_code) VALUES (:nama_topik, :hari, :tanggal, :kelas, :jam_pertemuan, :jam_mulai, :jam_akhir, :qr_code)";
$stmt = $db->prepare($query);
$stmt->bindValue(':nama_topik', $formattedNamaTopik, SQLITE3_TEXT);
$stmt->bindValue(':hari', $hari, SQLITE3_TEXT);
$stmt->bindValue(':tanggal', $tanggal, SQLITE3_TEXT);
$stmt->bindValue(':kelas', $kelas, SQLITE3_TEXT);
$stmt->bindValue(':jam_pertemuan', $jamPertemuan, SQLITE3_TEXT);
$stmt->bindValue(':jam_mulai', $jamMulai, SQLITE3_TEXT);
$stmt->bindValue(':jam_akhir', $jamAkhir, SQLITE3_TEXT);
$stmt->bindValue(':qr_code', '', SQLITE3_TEXT); // QR code kosong

if ($stmt->execute()) {
    // Ambil ID yang baru saja di-generate
    $lastInsertId = $db->lastInsertRowID();

    // Update nama topik dengan ID yang benar
    $formattedNamaTopik = "Pertemuan Rutin " . $lastInsertId . $namaTopik;

    // Update nama topik di database
    $updateQuery = "UPDATE jadwal_pertemuan SET nama_topik = :nama_topik WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindValue(':nama_topik', $formattedNamaTopik, SQLITE3_TEXT);
    $updateStmt->bindValue(':id', $lastInsertId, SQLITE3_INTEGER);
    $updateStmt->execute();

    echo json_encode(['success' => true, 'message' => 'Pertemuan berhasil ditambahkan']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menambahkan pertemuan']);
}
?>