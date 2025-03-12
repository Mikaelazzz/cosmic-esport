<?php
session_start();

// Cek apakah pengguna sudah login dan memiliki role admin
if (!isset($_SESSION['user'])) {
    header("Location: ../page/login.php");
    exit();
}

$user = $_SESSION['user'];
if ($user['role'] !== 'admin') {
    header("Location: ../page/home.php");
    exit();
}

// Koneksi ke database SQLite3
$db = new SQLite3('../db/ukm.db');

// Ambil data dari request POST
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

$nama_lengkap = $data['nama_lengkap'];
$nim = $data['nim'];
$email = $data['email'];
$password = password_hash($data['password'], PASSWORD_DEFAULT); // Hash password
$role = $data['role'];
$jabatan = $data['jabatan'];
$token = $data['token']; // Ambil token dari form

// Validasi NIM: panjang 9-10 karakter dan hanya angka
if (!preg_match('/^\d{9,10}$/', $nim)) {
    echo json_encode(['success' => false, 'message' => 'NIM harus terdiri dari 9-10 angka']);
    exit();
}

// Query untuk menambahkan user baru
$query = "INSERT INTO users (nama_lengkap, nim, email, password, role, jabatan, token) VALUES (:nama_lengkap, :nim, :email, :password, :role, :jabatan, :token)";
$stmt = $db->prepare($query);
$stmt->bindValue(':nama_lengkap', $nama_lengkap, SQLITE3_TEXT);
$stmt->bindValue(':nim', $nim, SQLITE3_TEXT);
$stmt->bindValue(':email', $email, SQLITE3_TEXT);
$stmt->bindValue(':password', $password, SQLITE3_TEXT);
$stmt->bindValue(':role', $role, SQLITE3_TEXT);
$stmt->bindValue(':jabatan', $jabatan, SQLITE3_TEXT);
$stmt->bindValue(':token', $token, SQLITE3_TEXT); // Simpan token ke database

// Eksekusi query
if ($stmt->execute()) {
    // Jika role adalah BPH, tambahkan NIM ke tabel admin_nim
    if ($role === 'BPH') {
        $queryAdmin = "INSERT INTO admin_nim (nim) VALUES (:nim)";
        $stmtAdmin = $db->prepare($queryAdmin);
        $stmtAdmin->bindValue(':nim', $nim, SQLITE3_TEXT);
        $stmtAdmin->execute();
    }

    echo json_encode(['success' => true, 'message' => 'User berhasil ditambahkan']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menambahkan user']);
}
?>