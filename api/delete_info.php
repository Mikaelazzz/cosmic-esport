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

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Ambil ID dari query string
    $id = $_GET['id'];

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
        exit();
    }

    // Hapus data dari database
    $stmt = $db->prepare("DELETE FROM informasi WHERE id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Informasi berhasil dihapus']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus informasi']);
    }
    exit();
}
?>