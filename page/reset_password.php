<?php
session_start();

// Cek apakah pengguna sudah melewati validasi NIM dan token
if (!isset($_SESSION['reset_nim'])) {
    header('Location: lupa_password.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cosmic Esport</title>
    <link rel="icon" type="image/*" href="../src/logo.png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .custom-bg-left {
            background-color: #727DB6;
        }
        .custom-bg-right {
            background-color: #F0F4FF;
        }
        .custom-input {
            background-color: #E0E7FF;
        }

        .custom-input {
            width: 100%;
            padding: 1rem;
            border-radius: 0.5rem;
            background-color: #E0E7FF;
            border: none;
            outline: none;
            transition: all 0.3s ease;
        }

        .custom-input:focus {
            box-shadow: 0 0 0 2px #727DB6;
        }
    </style>
</head>
<body>
    <section class="h-screen flex flex-col md:flex-row" style="font-family: 'Poppins';">
        <!-- Left -->
        <div class="flex flex-col justify-center items-center w-full md:w-1/2 custom-bg-left p-8 text-slate-200">
            <img src="../src/logo.png" alt="Colorful logo with overlapping shapes" class="w-72 h-72 mb-3">
            <!-- <h1 class="text-3xl font-bold mb-4">Selamat Datang!</h1>
            <p class="text-lg mb-6 text-justify">Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.</p> -->
            <button onclick="window.history.back()" class="border-2 border-white rounded px-3 py-1 hover:bg-white hover:text-[#727DB6] transition duration-300">Kembali</button>
        </div>

        <!-- Right -->
        <div class="flex flex-col justify-center items-center w-full md:w-1/2 custom-bg-right p-8">
            <h2 class="text-2xl font-bold mb-6" style="color: #727DB6;">RESET PASSWORD</h2>
            <form id="resetForm" class="w-full">
                <!-- Input Password Baru -->
                <div class="relative w-full mb-4">
                    <input type="password" name="new_password" id="new_password" placeholder="Password Baru" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <span class="absolute right-3 inset-y-0 flex items-center text-gray-500 cursor-pointer" id="toggleNewPassword">
                        <i class="fas fa-eye-slash"></i>
                    </span>
                </div>
                
                <!-- Input Konfirmasi Password Baru -->
                <div class="relative w-full mb-4">
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Konfirmasi Password Baru" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <span class="absolute right-3 inset-y-0 flex items-center text-gray-500 cursor-pointer" id="toggleConfirmPassword">
                        <i class="fas fa-eye-slash"></i>
                    </span>
                </div>
                
                <!-- Tombol Reset Password -->
                <div class="flex flex-col items-center w-full">
                    <button type="submit" class="text-white px-3 py-1 rounded" style="background-color: #727DB6;">RESET PASSWORD</button>
                </div>
            </form>
        </div>
    </section>

    <script>
        $(document).ready(function() {
            // Tampilkan SweetAlert2 jika ada pesan kesalahan
            <?php if (isset($_SESSION['error'])): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: '<?php echo $_SESSION['error']; ?>',
                });
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            // Tampilkan SweetAlert2 jika ada pesan sukses
            <?php if (isset($_SESSION['success'])): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Sukses!',
                    text: '<?php echo $_SESSION['success']; ?>',
                });
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            // Toggle password visibility
            $('#toggleNewPassword').click(function() {
                const input = $('#new_password');
                const icon = $(this).find('i');
                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                }
            });

            $('#toggleConfirmPassword').click(function() {
                const input = $('#confirm_password');
                const icon = $(this).find('i');
                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                }
            });

            // Handle form submission
$('#resetForm').submit(function(e) {
    e.preventDefault();

    const newPassword = $('#new_password').val();
    const confirmPassword = $('#confirm_password').val();

    // Validasi apakah password dan konfirmasi password sama
    if (newPassword !== confirmPassword) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Password dan konfirmasi password tidak cocok.',
        });
        return; // Hentikan proses jika password tidak sama
    }

    // Validasi minimal 1 huruf kapital dan 1 angka
    const hasCapital = /[A-Z]/.test(newPassword);
    const hasNumber = /\d/.test(newPassword);
    if (!hasCapital || !hasNumber) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Password harus mengandung minimal 1 huruf kapital dan 1 angka.',
        });
        return;
    }

    // Kirim data ke server
    $.ajax({
        url: '../api/proses_reset_password.php',
        method: 'POST',
        data: {
            new_password: newPassword,
            confirm_password: confirmPassword
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Sukses!',
                    text: `Password dari ${response.nim} berhasil direset.`, // Tampilkan NIM di sini
                    confirmButtonColor: '#727DB6',
                }).then(() => {
                    window.location.href = 'login.php';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message,
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Terjadi kesalahan. Silakan coba lagi.',
            });
        }
    });
});
});
    </script>
</body>
</html>