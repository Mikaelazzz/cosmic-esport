<?php
// Pastikan tidak ada output sebelum header
if (ob_get_level()) ob_clean();

header('Content-Type: application/json');
session_start();

// Validasi session dan role
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    die(json_encode(['status' => 'error', 'message' => 'Session expired, please login again']));
}

if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Admin access required']));
}

// Handle CORS jika diperlukan
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Hanya terima request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['status' => 'error', 'message' => 'Method not allowed']));
}

// Ambil data dari input JSON
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    die(json_encode(['status' => 'error', 'message' => 'Invalid JSON data']));
}

// Validasi input
$required = ['user_id', 'new_password'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        die(json_encode(['status' => 'error', 'message' => "$field is required"]));
    }
}

// Koneksi database
try {
    $db = new SQLite3('../db/ukm.db');
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
}

// Update password
try {
    $stmt = $db->prepare('UPDATE users SET password = :password WHERE id = :id');
    $hashed = password_hash($input['new_password'], PASSWORD_DEFAULT);
    $stmt->bindValue(':password', $hashed);
    $stmt->bindValue(':id', $input['user_id']);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Password updated']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Update failed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

$db->close();
?>