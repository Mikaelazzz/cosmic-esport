<?php
session_start();

if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

try {
    $db = new SQLite3('../db/ukm.db');
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

$id = $_POST['id'] ?? null;
$nama_lengkap = $_POST['nama_lengkap'] ?? null;
$nim = $_POST['nim'] ?? null;
$role = $_POST['role'] ?? null;
$jabatan = $_POST['jabatan'] ?? null;
$profile_image = $_FILES['profile_image'] ?? null;

if (!$id || !$nama_lengkap || !$nim || !$role || !$jabatan) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit();
}

// Cek apakah user yang diedit adalah dirinya sendiri
$isEditingSelf = $id == $_SESSION['user']['id'];

// Cek apakah role diubah dari admin ke anggota
$isChangingToAnggota = $isEditingSelf && $role === 'anggota' && $_SESSION['user']['role'] === 'admin';

// Update nama_lengkap, nim, role, dan jabatan
$query = "UPDATE users SET nama_lengkap = :nama_lengkap, nim = :nim, role = :role, jabatan = :jabatan WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindValue(':nama_lengkap', $nama_lengkap, SQLITE3_TEXT);
$stmt->bindValue(':nim', $nim, SQLITE3_TEXT);
$stmt->bindValue(':role', $role, SQLITE3_TEXT);
$stmt->bindValue(':jabatan', $jabatan, SQLITE3_TEXT);
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);

if (!$stmt->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update profile']);
    exit();
}

// Update profile image jika ada
if ($profile_image && $profile_image['error'] === UPLOAD_ERR_OK) {
    $target_dir = "../uploads/";
    $target_file = $target_dir . basename($profile_image["name"]);
    
    if (!move_uploaded_file($profile_image["tmp_name"], $target_file)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to upload image']);
        exit();
    }

    $query = "UPDATE users SET profile_image = :profile_image WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':profile_image', $target_file, SQLITE3_TEXT);
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);

    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update profile image']);
        exit();
    }
}

// Jika role diubah dari admin ke anggota, update session
if ($isChangingToAnggota) {
    $_SESSION['user']['role'] = 'anggota';
}

echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully']);
?>