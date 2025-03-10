<?php
session_start();

// Debugging: Periksa apakah reset_nim ada di session
if (!isset($_SESSION['reset_nim'])) {
    error_log("Session reset_nim tidak ditemukan.");
    echo json_encode(['status' => 'error', 'message' => 'Akses tidak valid.']);
    exit();
}

// Debugging: Log nilai reset_nim
error_log("Nilai reset_nim: " . $_SESSION['reset_nim']);

// Proses reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Koneksi ke database
    $db = new SQLite3('../db/ukm.db');

    // Ambil data dari form
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi password
    if ($new_password !== $confirm_password) {
        echo json_encode(['status' => 'error', 'message' => 'Password dan konfirmasi password tidak cocok.']);
        exit();
    }

    // Validasi minimal 1 huruf kapital dan 1 angka
    $hasCapital = preg_match('/[A-Z]/', $new_password);
    $hasNumber = preg_match('/\d/', $new_password);
    if (!$hasCapital || !$hasNumber) {
        echo json_encode(['status' => 'error', 'message' => 'Password harus mengandung minimal 1 huruf kapital dan 1 angka.']);
        exit();
    }

    // Ambil password lama dari database
    $query = $db->prepare("SELECT password FROM users WHERE nim = :nim");
    $query->bindValue(':nim', $_SESSION['reset_nim'], SQLITE3_TEXT);
    $result = $query->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    if ($user) {
        // Cek apakah password baru sama dengan password lama
        if (password_verify($new_password, $user['password'])) {
            echo json_encode(['status' => 'error', 'message' => 'Password baru tidak boleh sama dengan password lama.']);
            exit();
        }

        // Hash password baru
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password di database
        $query = $db->prepare("UPDATE users SET password = :password WHERE nim = :nim");
        $query->bindValue(':password', $hashed_password, SQLITE3_TEXT);
        $query->bindValue(':nim', $_SESSION['reset_nim'], SQLITE3_TEXT);

        if ($query->execute()) {
            // Simpan NIM sebelum menghapus session
            $nim = $_SESSION['reset_nim'];

            // Hapus session reset_nim hanya setelah password berhasil direset
            unset($_SESSION['reset_nim']);

            // Kirim respons JSON untuk sukses
            echo json_encode([
                'status' => 'success',
                'message' => 'Password berhasil direset.',
                'nim' => $nim // Sertakan NIM dalam respons
            ]);
            exit();
        } else {
            // Kirim respons JSON untuk kesalahan umum
            echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan. Silakan coba lagi.']);
            exit();
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Pengguna tidak ditemukan.']);
        exit();
    }
}
?>