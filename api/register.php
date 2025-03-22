<?php
// Proses Pendaftaran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi reCAPTCHA
    $recaptcha_secret = '6LdqifoqAAAAAJUdiGG4oypswb-11ZTdYT7NB3uR'; // Ganti dengan Secret Key Anda
    $recaptcha_response = $_POST['g-recaptcha-response'];

    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => $recaptcha_secret,
        'response' => $recaptcha_response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $response = json_decode($result);

    if (!$response->success) {
        echo json_encode(['status' => 'error', 'message' => 'CAPTCHA verification failed.']);
        exit();
    }

    // Koneksi ke SQLite
    $db = new SQLite3('../db/ukm.db');

    // Ambil data dari form
    $nama_lengkap = $_POST['nama_lengkap'];
    $nim = $_POST['nim'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validasi NIM hanya angka
    if (!ctype_digit($nim)) {
        echo json_encode(['status' => 'error', 'message' => 'NIM hanya boleh mengandung angka.']);
        exit();
    }

    // Validasi panjang NIM
    if (strlen($nim) > 10) {
        echo json_encode(['status' => 'error', 'message' => 'NIM tidak boleh lebih dari 10 angka.']);
        exit();
    }

    // Cek apakah email sudah terdaftar
    $query = $db->prepare("SELECT email FROM users WHERE email = :email");
    $query->bindValue(':email', $email, SQLITE3_TEXT);
    $result = $query->execute();
    $existingEmail = $result->fetchArray(SQLITE3_ASSOC);

    // Cek apakah NIM sudah terdaftar
    $query = $db->prepare("SELECT nim FROM users WHERE nim = :nim");
    $query->bindValue(':nim', $nim, SQLITE3_TEXT);
    $result = $query->execute();
    $existingNim = $result->fetchArray(SQLITE3_ASSOC);

    if ($existingEmail) {
        // Jika email sudah terdaftar, kirim respons JSON
        echo json_encode(['status' => 'error', 'message' => 'Email sudah digunakan. Silakan gunakan email lain.']);
        exit();
    } elseif ($existingNim) {
        // Jika NIM sudah terdaftar, kirim respons JSON
        echo json_encode(['status' => 'error', 'message' => 'NIM sudah digunakan. Silakan gunakan NIM lain.']);
        exit();
    } else {
        // Jika email dan NIM belum terdaftar, lanjutkan proses pendaftaran
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Cek apakah NIM ada di tabel admin_nim
        $query = $db->prepare("SELECT nim FROM admin_nim WHERE nim = :nim");
        $query->bindValue(':nim', $nim, SQLITE3_TEXT);
        $result = $query->execute();
        $admin_nim = $result->fetchArray(SQLITE3_ASSOC);

        // Tentukan role
        $role = ($admin_nim) ? 'admin' : 'anggota';

        // Generate token
        $token = generateToken(); // Panggil fungsi generateToken

        // Insert data ke tabel users
        $query = $db->prepare("INSERT INTO users (nim, nama_lengkap, email, password, role, token) VALUES (:nim, :nama_lengkap, :email, :password, :role, :token)");
        $query->bindValue(':nim', $nim, SQLITE3_TEXT);
        $query->bindValue(':nama_lengkap', $nama_lengkap, SQLITE3_TEXT);
        $query->bindValue(':email', $email, SQLITE3_TEXT);
        $query->bindValue(':password', $hashed_password, SQLITE3_TEXT);
        $query->bindValue(':role', $role, SQLITE3_TEXT);
        $query->bindValue(':token', $token, SQLITE3_TEXT); // Simpan token ke database

        if ($query->execute()) {
            // Kirim respons JSON untuk sukses
            echo json_encode(['status' => 'success', 'message' => 'Pendaftaran berhasil!', 'token' => $token]);
            exit();
        } else {
            // Kirim respons JSON untuk kesalahan umum
            echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan. Silakan coba lagi.']);
            exit();
        }
    }
}

// Fungsi untuk generate token
function generateToken($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $token = '';
    for ($i = 0; $i < $length; $i++) {
        $token .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $token;
}
?>