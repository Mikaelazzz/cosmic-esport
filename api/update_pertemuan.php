<?php
// update_pertemuan.php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../page/login.php");
    exit();
}

$db = new SQLite3('../db/ukm.db');

$pertemuanId = $_GET['id'];
$jamMulai = $_GET['jam_mulai'] ?? null;
$jamAkhir = $_GET['jam_akhir'] ?? null;

if ($jamMulai) {
    $query = "UPDATE jadwal_pertemuan SET jam_mulai = :jam_mulai WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':jam_mulai', $jamMulai, SQLITE3_TEXT);
    $stmt->bindValue(':id', $pertemuanId, SQLITE3_INTEGER);
} elseif ($jamAkhir) {
    $query = "UPDATE jadwal_pertemuan SET jam_akhir = :jam_akhir WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':jam_akhir', $jamAkhir, SQLITE3_TEXT);
    $stmt->bindValue(':id', $pertemuanId, SQLITE3_INTEGER);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Pertemuan berhasil diupdate']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal mengupdate pertemuan']);
}
?>