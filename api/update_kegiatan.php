<?php
session_start();

// Cek apakah pengguna sudah login dan memiliki role admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../page/login.php");
    exit();
}

// Koneksi ke database SQLite3
$db = new SQLite3('../db/ukm.db');

// Ambil data dari form
$id = $_POST['id'];
$nama_kegiatan = $_POST['nama_kegiatan'];
$tanggal_pelaksanaan = $_POST['tanggal_pelaksanaan'];
$deskripsi = $_POST['deskripsi'];
$syarat_dan_ketentuan = $_POST['syarat_dan_ketentuan'];
$link_pendaftaran = $_POST['link_pendaftaran'];

// Handle upload gambar baru
$gambar = null;
if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/kegiatan/';
    $uploadFile = $uploadDir . basename($_FILES['gambar']['name']);
    if (move_uploaded_file($_FILES['gambar']['tmp_name'], $uploadFile)) {
        $gambar = $uploadFile;
    }
}

// Update data ke database
$query = "UPDATE kegiatan 
          SET nama_kegiatan = :nama_kegiatan, 
              gambar = COALESCE(:gambar, gambar), 
              tanggal_pelaksanaan = :tanggal_pelaksanaan, 
              deskripsi = :deskripsi, 
              syarat_dan_ketentuan = :syarat_dan_ketentuan, 
              link_pendaftaran = :link_pendaftaran 
          WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindValue(':nama_kegiatan', $nama_kegiatan, SQLITE3_TEXT);
$stmt->bindValue(':gambar', $gambar, SQLITE3_TEXT);
$stmt->bindValue(':tanggal_pelaksanaan', $tanggal_pelaksanaan, SQLITE3_TEXT);
$stmt->bindValue(':deskripsi', $deskripsi, SQLITE3_TEXT);
$stmt->bindValue(':syarat_dan_ketentuan', $syarat_dan_ketentuan, SQLITE3_TEXT);
$stmt->bindValue(':link_pendaftaran', $link_pendaftaran, SQLITE3_TEXT);
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);

if ($stmt->execute()) {
    header("Location: ../admin/kegiatan.php?success=1");
} else {
    header("Location: ../admin/kegiatan.php?error=1");
}
?>