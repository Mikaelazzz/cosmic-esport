<?php
session_start();

date_default_timezone_set('Asia/Jakarta'); // Atur zona waktu

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
if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Gagal terhubung ke database']);
    exit();
}

// Ambil data dari AJAX
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

// Debugging: Catat data ke log server
error_log('Data diterima: ' . print_r($data, true));

// Validasi data
if (!isset($data['type'])) {
    echo json_encode(['success' => false, 'message' => 'Jenis permintaan tidak valid']);
    exit();
}

if ($data['type'] === 'radio') {
    // Validasi untuk radio button
    if (!isset($data['nim']) || !isset($data['status']) || !isset($data['pertemuan_id'])) {
        echo json_encode(['success' => false, 'message' => 'Data radio button tidak lengkap']);
        exit();
    }

    $pertemuanId = $data['pertemuan_id'];

    // Ambil nilai `hari` dari tabel `jadwal_pertemuan` berdasarkan `pertemuan_id`
    $queryHari = "SELECT hari FROM jadwal_pertemuan WHERE id = :pertemuan_id";
    $stmtHari = $db->prepare($queryHari);
    $stmtHari->bindValue(':pertemuan_id', $pertemuanId, SQLITE3_INTEGER);
    $resultHari = $stmtHari->execute();
    $hari = $resultHari->fetchArray(SQLITE3_ASSOC);

    if (!$hari) {
        echo json_encode(['success' => false, 'message' => 'Pertemuan tidak ditemukan']);
        exit();
    }

    $hari = $hari['hari']; // Ambil nilai hari dari hasil query
} else {
    echo json_encode(['success' => false, 'message' => 'Jenis permintaan tidak dikenali']);
    exit();
}

$waktu_sekarang = date('Y-m-d H:i:s'); // Waktu sekarang dari PHP

// Simpan atau update status kehadiran
$queryCek = "SELECT * FROM absen WHERE pertemuan_id = :pertemuan_id AND nim = :nim";
$stmtCek = $db->prepare($queryCek);
$stmtCek->bindValue(':pertemuan_id', $pertemuanId, SQLITE3_INTEGER);
$stmtCek->bindValue(':nim', $data['nim'], SQLITE3_TEXT);
$resultCek = $stmtCek->execute();
$dataCek = $resultCek->fetchArray(SQLITE3_ASSOC);

if ($dataCek) {
    // Update status jika data sudah ada
    $queryUpdate = "UPDATE absen SET status = :status, hari = :hari, jam = :jam WHERE pertemuan_id = :pertemuan_id AND nim = :nim";
    $stmtUpdate = $db->prepare($queryUpdate);
    $stmtUpdate->bindValue(':status', $data['status'], SQLITE3_TEXT);
    $stmtUpdate->bindValue(':hari', $hari, SQLITE3_TEXT);
    $stmtUpdate->bindValue(':jam', $waktu_sekarang, SQLITE3_TEXT);
    $stmtUpdate->bindValue(':pertemuan_id', $pertemuanId, SQLITE3_INTEGER);
    $stmtUpdate->bindValue(':nim', $data['nim'], SQLITE3_TEXT);
    $resultUpdate = $stmtUpdate->execute();

    if (!$resultUpdate) {
        echo json_encode(['success' => false, 'message' => 'Gagal update data: ' . $db->lastErrorMsg()]);
        exit();
    }
} else {
    // Insert data baru jika belum ada
    $queryInsert = "INSERT INTO absen (pertemuan_id, nim, status, hari, jam) VALUES (:pertemuan_id, :nim, :status, :hari, :jam)";
    $stmtInsert = $db->prepare($queryInsert);
    $stmtInsert->bindValue(':pertemuan_id', $pertemuanId, SQLITE3_INTEGER);
    $stmtInsert->bindValue(':nim', $data['nim'], SQLITE3_TEXT);
    $stmtInsert->bindValue(':status', $data['status'], SQLITE3_TEXT);
    $stmtInsert->bindValue(':hari', $hari, SQLITE3_TEXT);
    $stmtInsert->bindValue(':jam', $waktu_sekarang, SQLITE3_TEXT);
    $resultInsert = $stmtInsert->execute();

    if (!$resultInsert) {
        echo json_encode(['success' => false, 'message' => 'Gagal insert data: ' . $db->lastErrorMsg()]);
        exit();
    }
}

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

if (!$statistik) {
    echo json_encode(['success' => false, 'message' => 'Gagal mengambil statistik']);
    exit();
}

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