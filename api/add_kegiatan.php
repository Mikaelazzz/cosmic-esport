<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Cek apakah pengguna sudah login dan memiliki role admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../page/login.php");
    exit();
}

// Koneksi ke database SQLite3
$db = new SQLite3('../db/ukm.db');

// Debug: Tulis data POST dan FILES ke log
error_log(print_r($_POST, true));
error_log(print_r($_FILES, true));

// Ambil data dari form
$nama_kegiatan = $_POST['nama_kegiatan'];
$tanggal_awal = $_POST['tanggal_awal'];
$tanggal_akhir = $_POST['tanggal_akhir'];
$tanggal_pelaksanaan = $_POST['tanggal_pelaksanaan'];
$deskripsi = $_POST['deskripsi'];
$syarat_dan_ketentuan = $_POST['syarat_ketentuan']; // Sesuaikan dengan nama di form
$link_pendaftaran = $_POST['link_pendaftaran'];

// Handle upload gambar
$gambar = null;
if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/kegiatan/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $uploadFile = $uploadDir . basename($_FILES['gambar']['name']);
    if (move_uploaded_file($_FILES['gambar']['tmp_name'], $uploadFile)) {
        $gambar = $uploadFile;
    } else {
        // Jika gagal upload gambar, kirim respons error
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Gagal mengupload gambar.']);
        exit();
    }
}

// Simpan data ke database
$query = "INSERT INTO kegiatan (nama_kegiatan, gambar, tanggal_awal, tanggal_akhir, tanggal_pelaksanaan, deskripsi, syarat_dan_ketentuan, link_pendaftaran) 
          VALUES (:nama_kegiatan, :gambar, :tanggal_awal, :tanggal_akhir, :tanggal_pelaksanaan, :deskripsi, :syarat_dan_ketentuan, :link_pendaftaran)";
$stmt = $db->prepare($query);

if (!$stmt) {
    // Jika prepare gagal, kirim respons error
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Gagal menyiapkan query: ' . $db->lastErrorMsg()]);
    exit();
}

// Binding parameter
$stmt->bindValue(':nama_kegiatan', $nama_kegiatan, SQLITE3_TEXT);
$stmt->bindValue(':gambar', $gambar, SQLITE3_TEXT);
$stmt->bindValue(':tanggal_awal', $tanggal_awal, SQLITE3_TEXT);
$stmt->bindValue(':tanggal_akhir', $tanggal_akhir, SQLITE3_TEXT);
$stmt->bindValue(':tanggal_pelaksanaan', $tanggal_pelaksanaan, SQLITE3_TEXT);
$stmt->bindValue(':deskripsi', $deskripsi, SQLITE3_TEXT);
$stmt->bindValue(':syarat_dan_ketentuan', $syarat_dan_ketentuan, SQLITE3_TEXT); // Sesuaikan dengan nama kolom di tabel
$stmt->bindValue(':link_pendaftaran', $link_pendaftaran, SQLITE3_TEXT);

if ($stmt->execute()) {
    // Set header untuk mengindikasikan respons JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    // Set header untuk mengindikasikan respons JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data: ' . $db->lastErrorMsg()]);
}
?>