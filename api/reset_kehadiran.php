<?php
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Ambil data pengguna dari session
$user = $_SESSION['user'];
if ($user['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
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

$nim = $data['nim'];
$pertemuanId = $data['pertemuan_id'];

// Hapus data kehadiran user untuk pertemuan tertentu
$queryDelete = "DELETE FROM absen WHERE pertemuan_id = :pertemuan_id AND nim = :nim";
$stmtDelete = $db->prepare($queryDelete);
$stmtDelete->bindValue(':pertemuan_id', $pertemuanId, SQLITE3_INTEGER);
$stmtDelete->bindValue(':nim', $nim, SQLITE3_TEXT);
$stmtDelete->execute();

// Hitung ulang statistik kehadiran
$queryStatistik = "
    SELECT 
        COUNT(*) AS total_anggota,
        SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) AS hadir,
        SUM(CASE WHEN status = 'Alpha' THEN 1 ELSE 0 END) AS alpha
    FROM 
        absen
    WHERE 
        pertemuan_id = :pertemuan_id
";
$stmtStatistik = $db->prepare($queryStatistik);
$stmtStatistik->bindValue(':pertemuan_id', $pertemuanId, SQLITE3_INTEGER);
$resultStatistik = $stmtStatistik->execute();
$statistik = $resultStatistik->fetchArray(SQLITE3_ASSOC);

$total_anggota = $statistik['total_anggota'];
$hadir = $statistik['hadir'];
$alpha = $statistik['alpha'];
$persentase_hadir = $total_anggota > 0 ? round(($hadir / $total_anggota) * 100, 2) : 0;
$persentase_alpha = $total_anggota > 0 ? round(($alpha / $total_anggota) * 100, 2) : 0;

// Kirim respons ke client
echo json_encode([
    'success' => true,
    'hadir' => $hadir,
    'persentase_hadir' => $persentase_hadir,
    'persentase_alpha' => $persentase_alpha
]);
?>