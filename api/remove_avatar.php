<?php
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// Cek apakah permintaan untuk menghapus avatar
if (isset($_POST['remove_avatar'])) {
    // Path gambar default
    $default_image = '../src/1.png';

    // Update database untuk menghapus gambar profil
    $db = new SQLite3('../db/ukm.db');
    $stmt = $db->prepare('UPDATE users SET profile_image = :profile_image WHERE id = :id');
    $stmt->bindValue(':profile_image', $default_image);
    $stmt->bindValue(':id', $_SESSION['user']['id']);
    $stmt->execute();

    // Update session dengan gambar default
    $_SESSION['user']['profile_image'] = $default_image;

    // Beri respons ke client
    echo json_encode(['status' => 'success', 'message' => 'Avatar removed successfully']);
    exit();
}

// Jika tidak ada permintaan yang valid
echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
exit();
?>