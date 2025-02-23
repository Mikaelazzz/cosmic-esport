<?php
// Proses Pendaftaran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Koneksi ke SQLite
    $db = new SQLite3('../db/ukm.db');

    // Ambil data dari form
    $nama_lengkap = $_POST['nama_lengkap'];
    $nim = $_POST['nim'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Cek apakah NIM ada di tabel admin_nim
    $query = $db->prepare("SELECT nim FROM admin_nim WHERE nim = :nim");
    $query->bindValue(':nim', $nim, SQLITE3_TEXT);
    $result = $query->execute();
    $admin_nim = $result->fetchArray(SQLITE3_ASSOC);

    // Tentukan role
    $role = ($admin_nim) ? 'admin' : 'anggota';

    // Insert data ke tabel users
    $query = $db->prepare("INSERT INTO users (nim, nama_lengkap, email, password, role) VALUES (:nim, :nama_lengkap, :email, :password, :role)");
    $query->bindValue(':nim', $nim, SQLITE3_TEXT);
    $query->bindValue(':nama_lengkap', $nama_lengkap, SQLITE3_TEXT);
    $query->bindValue(':email', $email, SQLITE3_TEXT);
    $query->bindValue(':password', $hashed_password, SQLITE3_TEXT);
    $query->bindValue(':role', $role, SQLITE3_TEXT);

    if ($query->execute()) {
        // Redirect ke halaman login setelah pendaftaran berhasil
        header("Location: ../page/login.php");
        exit();
    } else {
        echo "<script>alert('Gagal mendaftar. Silakan coba lagi.');</script>";
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Esport</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
</head>
<body>
    <section class="flex flex-col-reverse md:flex-row min-h-screen bg-blue-50" style="font-family: 'Poppins';">
        <!-- Form Pendaftaran -->
        <div class="flex-1 p-8 md:p-16 overflow-auto h-full">
            <h2 class="text-center text-2xl font-bold mb-6" style="color: #727DB6;">DAFTAR</h2>
            
            <!-- Form -->
            <form action="register.php" method="POST">
            <!-- Nama Lengkap -->
            <div class="mb-6">
                <input type="text" name="nama_lengkap" id="nama" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Nama Lengkap" required>
                <div id="namaValidation" class="mt-2 p-2 bg-red-100 text-red-700 rounded-lg text-sm">
                    <p><i class="fas fa-exclamation-circle"></i> Pastikan <strong>NAMA</strong> anda sudah benar.</p>
                    <p>Kesalahan dalam menginput nama asli dapat mempengaruhi proses klaim Point KPKK</p>
                </div>
            </div>
            
            <!-- NIM -->
            <div class="mb-6">
                <input type="text" name="nim" id="nim" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="NIM" required>
                <div id="nimValidation" class="mt-2 p-2 bg-red-100 text-red-700 rounded-lg text-sm">
                    <p><i class="fas fa-exclamation-circle"></i> Pastikan <strong>NIM</strong> anda sudah benar</p>
                    <p>Kesalahan dalam menginput NIM dapat mempengaruhi proses klaim Point KPKK</p>
                </div>
            </div>
            
            <!-- Email -->
            <div class="mb-6">
                <input type="email" name="email" id="email" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="EMAIL" required>
                <div id="emailValidation"  class="mt-2 p-2 bg-green-100 text-green-700 rounded-lg text-sm">
                    <p><i class="fas fa-check-circle"></i> Gunakan format <strong>EMAIL</strong> yang benar saat melakukan registrasi.</p>
                    <p>Contoh : johndoe@example.com</p>
                </div>
            </div>
            
            <!-- Password -->
            <div class="mb-6 relative">
                <input type="password" name="password" id="password" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="PASSWORD" required>
                <span class="absolute right-3 inset-y-0 flex items-center text-gray-500 cursor-pointer" id="togglePassword">
                    <i class="fas fa-eye-slash"></i>
                </span>
            </div>
            <div id="passwordRequirements" class="mt-2 p-2 text-green-700 rounded-lg text-sm mb-6" style="display: none;">
            <p id="capitalRequirement"><i class="fas fa-exclamation-circle"></i> Minimal 1 huruf kapital</p>
            <p id="numberRequirement"><i class="fas fa-exclamation-circle"></i> Minimal 1 angka</p>
            </div>
            
            <!-- Tombol Daftar -->
            <div class="text-center">
                <button type="submit" class="text-white py-2 px-6 rounded-lg" style="background-color: #727DB6;">Daftar</button>
            </div>
          </form>
        </div>

        <!-- Bagian Selamat Datang -->
        <div class="flex-1 flex flex-col justify-center items-center h-auto text-white p-8 md:p-16 overflow-auto" style="background-color: #727DB6;">
            <img src="../src/logo.png" alt="Logo" class="mb-8 w-72 h-72">
            <h2 class="text-2xl font-bold mb-4">Selamat Datang!</h2>
            <p class="text-justify mb-8">Lorem Ipsum has been the industry's standard dummy text ever since the 1500s...</p>
            <button class="border-2 border-white rounded px-3 py-1 hover:bg-white hover:text-[#727DB6] transition duration-300"><a href="login.php">LOGIN</a></button>
        </div>
    </section>

    <script>
        $(document).ready(function() {
            // Sembunyikan pesan validasi nama dan NIM saat halaman pertama kali dimuat
            $('#namaValidation').hide();
            $('#nimValidation').hide();
            $('#emailValidation').hide();

            // Toggle password visibility
            $('#togglePassword').click(function() {
                const passwordInput = $('#password');
                const icon = $(this).find('i');
                const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
                passwordInput.attr('type', type);
                icon.toggleClass('fa-eye fa-eye-slash');
            });

            // Tampilkan pesan persyaratan password saat pengguna mulai mengetik
            $('#password').on('input', function() {
                const password = $(this).val();
                const hasCapital = /[A-Z]/.test(password); // Cek huruf kapital
                const hasNumber = /\d/.test(password); // Cek angka

                if (password.length > 0) {
                    $('#passwordRequirements').show(); // Tampilkan pesan

                    // Update pesan untuk huruf kapital
                    if (hasCapital) {
                        $('#capitalRequirement').html('<i class="fas fa-check-circle"></i> Minimal 1 huruf kapital');
                        $('#capitalRequirement').removeClass('text-red-700').addClass('text-green-700');
                    } else {
                        $('#capitalRequirement').html('<i class="fas fa-exclamation-circle"></i> Minimal 1 huruf kapital');
                        $('#capitalRequirement').removeClass('text-green-700').addClass('text-red-700');
                    }

                    // Update pesan untuk angka
                    if (hasNumber) {
                        $('#numberRequirement').html('<i class="fas fa-check-circle"></i> Minimal 1 angka');
                        $('#numberRequirement').removeClass('text-red-700').addClass('text-green-700');
                    } else {
                        $('#numberRequirement').html('<i class="fas fa-exclamation-circle"></i> Minimal 1 angka');
                        $('#numberRequirement').removeClass('text-green-700').addClass('text-red-700');
                    }

                    // Update warna latar belakang
                    if (hasCapital && hasNumber) {
                        $('#passwordRequirements').removeClass('bg-red-100').addClass('bg-green-100');
                    } else {
                        $('#passwordRequirements').removeClass('bg-green-100').addClass('bg-red-100');
                    }
                } else {
                    $('#passwordRequirements').hide(); // Sembunyikan pesan jika input kosong
                }
            });

            // Tampilkan pesan validasi nama saat pengguna mulai mengetik
            $('#nama').on('input', function() {
                if ($(this).val().length > 0) {
                    $('#namaValidation').show(); // Tampilkan pesan
                } else {
                    $('#namaValidation').hide(); // Sembunyikan pesan jika input kosong
                }
            });

            // Tampilkan pesan validasi NIM saat pengguna mulai mengetik
            $('#nim').on('input', function() {
                if ($(this).val().length > 0) {
                    $('#nimValidation').show(); // Tampilkan pesan
                } else {
                    $('#nimValidation').hide(); // Sembunyikan pesan jika input kosong
                }
            });


            // Tampilkan pesan validasi email saat pengguna mulai mengetik
            $('#email').on('input', function() {
                const email = $(this).val();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/; // Regex untuk validasi email

                if (email.length > 0) {
                    $('#emailValidation').show(); // Tampilkan pesan format email
                    if (!emailRegex.test(email)) {
                        $('#emailError').show(); // Tampilkan pesan kesalahan jika format tidak valid
                    } else {
                        $('#emailError').hide(); // Sembunyikan pesan kesalahan jika format valid
                    }
                } else {
                    $('#emailValidation').hide(); // Sembunyikan pesan jika input kosong
                    $('#emailError').hide(); // Sembunyikan pesan kesalahan jika input kosong
                }
            });
        });
    </script>
</body>
</html>
