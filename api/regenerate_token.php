<?php
session_start();

// Koneksi ke database
$db = new SQLite3('../db/ukm.db');

// Fungsi untuk generate token 8 digit
function generateShortToken() {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $token = '';
    $length = 8;

    for ($i = 0; $i < $length; $i++) {
        $token .= $characters[rand(0, strlen($characters)) - 1];
    }

    return $token;
}

// Pastikan pengguna sudah login
if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

// Ambil ID pengguna dari session
$user_id = $_SESSION['user']['id'];

// Generate token baru
$new_token = generateShortToken();

// Update token di database
$stmt = $db->prepare('UPDATE users SET token = :token WHERE id = :id');
$stmt->bindValue(':token', $new_token, SQLITE3_TEXT);
$stmt->bindValue(':id', $user_id, SQLITE3_INTEGER);

if ($stmt->execute()) {
    // Update session dengan token baru
    $_SESSION['user']['token'] = $new_token;

    // Kirim respons JSON
    echo json_encode(['status' => 'success', 'token' => $new_token]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update token']);
}
?>