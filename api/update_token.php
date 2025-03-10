<?php
// Koneksi ke database
$db = new SQLite3('../db/ukm.db');

// Fungsi untuk generate token
function generateToken() {
    return bin2hex(random_bytes(16));
}

// Ambil semua user yang token-nya NULL atau kosong
$result = $db->query('SELECT id FROM users WHERE token IS NULL OR token = ""');

// Loop melalui setiap user dan update token-nya
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $id = $row['id'];
    $token = generateToken(); // Generate token baru

    // Update token di database
    $stmt = $db->prepare('UPDATE users SET token = :token WHERE id = :id');
    $stmt->bindValue(':token', $token, SQLITE3_TEXT);
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();
}

echo "Token berhasil diupdate untuk semua user yang token-nya kosong.";
?>