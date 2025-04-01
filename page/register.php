<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cosmic Esport</title>
    <link rel="icon" type="image/*" href="../src/logo.png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://unpkg.com/@zxing/library@latest/umd/index.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <section class="flex flex-col-reverse md:flex-row min-h-screen bg-blue-50" style="font-family: 'Poppins';">
        <!-- Form Pendaftaran -->
        <div class="flex-1 p-8 md:p-16 overflow-auto h-full">
            <h2 class="text-center text-2xl font-bold mb-6" style="color: #727DB6;">DAFTAR</h2>
            
            <!-- Form -->
            <form id="registerForm">
            <!-- Nama Lengkap -->
            <div class="mb-6">
                <input type="text" name="nama_lengkap" id="nama" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Nama Lengkap" required>
            </div>
            
            <!-- NIM -->
            <div class="mb-6">
                <input type="text" name="nim" id="nim" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="NIM" required>
            </div>
            
            <!-- Email -->
            <div class="mb-6">
                <input type="email" name="email" id="email" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="EMAIL" required>
            </div>
            
            <!-- Password -->
            <div class="mb-6 relative">
                <input type="password" name="password" id="password" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="PASSWORD" required>
                <span class="absolute right-3 inset-y-0 flex items-center text-gray-500 cursor-pointer" id="togglePassword">
                    <i class="fas fa-eye-slash"></i>
                </span>
            </div>
            <!-- reCAPTCHA -->
            <div class="mb-6">
                <div class="g-recaptcha" data-sitekey="6LdqifoqAAAAAMOfFXjs59QjYVUucyJItRjziOIy"></div>
            </div>
            <!-- Tombol Daftar -->
            <div class="text-center">
                <button type="submit" class="text-white py-2 px-6 rounded-lg" style="background-color: #727DB6;">Daftar</button>
            </div>
        </form>
        </div>

        <!-- Bagian Selamat Datang -->
        <div class="flex-1 flex flex-col justify-center items-center h-auto text-white p-8 md:p-16 overflow-auto" style="background-color: #727DB6;">
        <a href="/index.php" class="absolute top-4 right-4 text-gray-600 hover:text-gray-800 transition duration-300">
            <i class="fas fa-times text-2xl"></i>
        </a>    
        <img src="../src/logo.png" alt="Logo" class="mb-8 w-72 h-72">
            <!-- <h2 class="text-2xl font-bold mb-4">Selamat Datang!</h2>
            <p class="text-justify mb-8">Lorem Ipsum has been the industry's standard dummy text ever since the 1500s...</p> -->
            <button class="border-2 border-white rounded px-3 py-1 hover:bg-white hover:text-[#727DB6] transition duration-300"><a href="login.php">LOGIN</a></button>
        </div>
    </section>

    <script>
    $(document).ready(function() {
        // Tampilkan pop-up peringatan pertama kali
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian!',
            html: 'Pastikan Anda menggunakan <strong>Nama Lengkap</strong> dan <strong>NIM</strong> yang sesuai. Kesalahan dalam menginput data dapat mempengaruhi proses klaim Point KPKK.',
            confirmButtonText: 'Mengerti',
            confirmButtonColor: '#727DB6', // Sesuaikan warna tombol dengan tema
            allowOutsideClick: false, // Mencegah pengguna menutup pop-up dengan mengklik di luar
        });
        
        // Toggle password visibility
        $('#togglePassword').click(function() {
            const passwordInput = $('#password');
            const icon = $(this).find('i');
            const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
            passwordInput.attr('type', type);
            icon.toggleClass('fa-eye fa-eye-slash');
        });

        // Validasi NIM saat pengguna mengetik
        $('#nim').on('input', function() {
            const nim = $(this).val();

            // Cek apakah input mengandung huruf
            if (/[^0-9]/.test(nim)) {
                $(this).val(nim.replace(/[^0-9]/g, '')); // Hapus karakter non-angka
                Swal.fire({
                    icon: 'warning',
                    title: 'Perhatian!',
                    text: 'NIM hanya boleh mengandung angka.',
                });
            }

            if (nim.length > 10) {
                $(this).val(nim.slice(0, 10)); // Potong input jika melebihi 10 karakter
                Swal.fire({
                    icon: 'warning',
                    title: 'Perhatian!',
                    text: 'NIM tidak boleh lebih dari 10 angka.',
                });
            }
        });

        $('#registerForm').on('submit', function(e) {
        e.preventDefault(); // Mencegah pengiriman form default

        const nama_lengkap = $('#nama').val();
        const nim = $('#nim').val();
        const email = $('#email').val();
        const password = $('#password').val();
        const recaptchaResponse = grecaptcha.getResponse();

        if (!recaptchaResponse) {
        Swal.fire({
            icon: 'error',
            title: 'Gagal Mendaftar',
            text: 'Harap verifikasi bahwa Anda bukan robot.',
        });
        return;
    }


        // Validasi NIM hanya angka
        if (/[^0-9]/.test(nim)) {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Mendaftar',
                text: 'NIM hanya boleh mengandung angka.',
            });
            return;
        }

        // Validasi panjang NIM
        if (nim.length > 10) {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Mendaftar',
                text: 'NIM tidak boleh lebih dari 10 angka.',
            });
            return;
        }

        // Validasi password
        const hasCapital = /[A-Z]/.test(password);
        const hasNumber = /\d/.test(password);

        if (!hasCapital || !hasNumber) {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Mendaftar',
                text: 'Password harus mengandung minimal 1 huruf kapital dan 1 angka.',
            });
            return;
        }

        // Kirim data form menggunakan AJAX
        $.ajax({
            url: '../api/register.php',
            method: 'POST',
            data: {
                nama_lengkap: nama_lengkap,
                nim: nim,
                email: email,
                password: password,
                'g-recaptcha-response': recaptchaResponse
            },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: data.message,
                    }).then(() => {
                        window.location.href = '../page/login.php'; // Redirect ke halaman login
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Mendaftar',
                        text: data.message,
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Mendaftar',
                    text: 'Terjadi kesalahan. Silakan coba lagi.',
                });
            }
        });
    });
    });
</script>
</body>
</html>
