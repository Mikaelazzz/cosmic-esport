<?php
// record_attendance.php
session_start();
header('Content-Type: application/json');

// Koneksi ke database (sesuaikan dengan konfigurasi Anda)
$conn = new mysqli("localhost", "username", "password", "database_name");

if ($conn->connect_error) {
    die(json_encode(['error' => 'Koneksi database gagal']));
}

// Ambil data dari POST
$user_id = $_POST['user_id'];
$meeting_id = $_POST['meeting_id'];
$qr_code = $_POST['qr_code'];

// Validasi data (opsional)
if (empty($user_id) || empty($meeting_id) || empty($qr_code)) {
    echo json_encode(['error' => 'Data tidak lengkap']);
    exit;
}

// Cek apakah QR code valid (misalnya, cocok dengan pertemuan)
$sql = "SELECT * FROM meetings WHERE id = ? AND qr_code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $meeting_id, $qr_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Kode QR tidak valid untuk pertemuan ini']);
    exit;
}

// Simpan absensi ke tabel attendance
$sql = "INSERT INTO attendance (user_id, meeting_id, scan_time) VALUES (?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $meeting_id);

if ($stmt->execute()) {
    // Hitung jumlah absen untuk pertemuan ini
    $sql_count = "SELECT COUNT(*) as attendance_count FROM attendance WHERE meeting_id = ?";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param("i", $meeting_id);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $row = $result_count->fetch_assoc();

    echo json_encode(['success' => true, 'attendance_count' => $row['attendance_count']]);
} else {
    echo json_encode(['error' => 'Gagal merekam absensi']);
}

$stmt->close();
$conn->close();
?>