<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../page/login.php");
    exit();
}

$user = $_SESSION['user'];
if ($user['role'] !== 'admin') {
    header("Location: ../page/home.php");
    exit();
}

$db = new SQLite3('../db/ukm.db');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_informasi = $_POST['nama_informasi'];
    $tanggal_publish = $_POST['tanggal_publish'];
    $tanggal_berakhir = $_POST['tanggal_berakhir'];
    $deskripsi = $_POST['deskripsi'];
    $link = $_POST['link'];

    // Handle file upload
    $gambar = null;
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/informasi/';
        $gambarName = basename($_FILES['gambar']['name']);
        $gambarPath = $uploadDir . $gambarName;
        if (move_uploaded_file($_FILES['gambar']['tmp_name'], $gambarPath)) {
            $gambar = $gambarPath;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengunggah gambar']);
            exit();
        }
    }

    // Insert data ke database
    $stmt = $db->prepare("INSERT INTO informasi (nama_informasi, gambar, tanggal_publish, tanggal_berakhir, deskripsi, link) VALUES (:nama_informasi, :gambar, :tanggal_publish, :tanggal_berakhir, :deskripsi, :link)");
    $stmt->bindValue(':nama_informasi', $nama_informasi, SQLITE3_TEXT);
    $stmt->bindValue(':gambar', $gambar, SQLITE3_TEXT);
    $stmt->bindValue(':tanggal_publish', $tanggal_publish, SQLITE3_TEXT);
    $stmt->bindValue(':tanggal_berakhir', $tanggal_berakhir, SQLITE3_TEXT);
    $stmt->bindValue(':deskripsi', $deskripsi, SQLITE3_TEXT);
    $stmt->bindValue(':link', $link, SQLITE3_TEXT);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Informasi berhasil ditambahkan']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan informasi']);
    }
    exit();
}
?>