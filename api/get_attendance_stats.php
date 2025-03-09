<?php
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Koneksi ke database SQLite3
$db = new SQLite3('../db/ukm.db');

// Ambil ID pertemuan dari parameter URL
$pertemuanId = $_GET['pertemuan_id'];

// Query untuk mengambil statistik kehadiran
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

if (!$statistik) {
    echo json_encode(['success' => false, 'message' => 'Gagal mengambil statistik']);
    exit();
}

$total_anggota = $statistik['total_anggota'];
$hadir = $statistik['hadir'];
$alpha = $statistik['alpha'];
$persentase_hadir = $total_anggota > 0 ? round(($hadir / $total_anggota) * 100, 2) : 0;
$persentase_alpha = $total_anggota > 0 ? round(($alpha / $total_anggota) * 100, 2) : 0;

// Query untuk mengambil daftar anggota dan status kehadiran
$queryAnggota = "
    SELECT 
        u.nama_lengkap, 
        u.nim, 
        a.status 
    FROM 
        users u
    LEFT JOIN 
        absen a ON u.nim = a.nim AND a.pertemuan_id = :pertemuan_id
";
$stmtAnggota = $db->prepare($queryAnggota);
$stmtAnggota->bindValue(':pertemuan_id', $pertemuanId, SQLITE3_INTEGER);
$resultAnggota = $stmtAnggota->execute();

$anggota = [];
while ($row = $resultAnggota->fetchArray(SQLITE3_ASSOC)) {
    $anggota[] = $row;
}

// Kirim respons ke client
echo json_encode([
    'success' => true,
    'hadir' => $hadir,
    'persentase_hadir' => $persentase_hadir,
    'persentase_alpha' => $persentase_alpha,
    'anggota' => $anggota
]);
?>