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
$profile_image = $_FILES['profile_image'] ?? null;

if (!$id || !$nama_lengkap || !$nim) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit();
}

// Update nama_lengkap dan nim
$query = "UPDATE users SET nama_lengkap = :nama_lengkap, nim = :nim WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindValue(':nama_lengkap', $nama_lengkap, SQLITE3_TEXT);
$stmt->bindValue(':nim', $nim, SQLITE3_TEXT);
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

echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully']);
?>