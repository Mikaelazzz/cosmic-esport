<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

// Get user data from session
$user = $_SESSION['user'];

// Handle form submission (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json'); // Set JSON header

    // Check if this is a password update request
    if (isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate passwords
        if (empty($new_password) || empty($confirm_password)) {
            echo json_encode(['status' => 'error', 'message' => 'Both password fields are required.']);
            exit();
        }
        if ($new_password !== $confirm_password) {
            echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
            exit();
        }

        // Connect to database
        $db = new SQLite3('../db/ukm.db');

        // Update password in database (assuming passwords are hashed)
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT); // Hash the password
        $stmt = $db->prepare('UPDATE users SET password = :password WHERE id = :id');
        $stmt->bindValue(':password', $hashed_password, SQLITE3_TEXT);
        $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Password updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update password']);
        }
        exit();
    }

    // Handle profile update (existing logic)
    $nama_lengkap = $_POST['nama_lengkap'] ?? '';
    $nim = $_POST['nim'] ?? '';
    $profile_image = $_FILES['profile_image'] ?? null;

    // Validate NIM: Must be 9-10 digits
    if (!preg_match('/^\d{9,10}$/', $nim)) {
        echo json_encode(['status' => 'error', 'message' => 'NIM must be 9-10 digits.']);
        exit();
    }

    // Connect to database
    $db = new SQLite3('../db/ukm.db');

    // Check if NIM is already used by another user
    $checkNIM = $db->prepare('SELECT id FROM users WHERE nim = :nim AND id != :id');
    $checkNIM->bindValue(':nim', $nim, SQLITE3_TEXT);
    $checkNIM->bindValue(':id', $user['id'], SQLITE3_INTEGER);
    $result = $checkNIM->execute()->fetchArray(SQLITE3_ASSOC);

    if ($result) {
        echo json_encode(['status' => 'error', 'message' => 'NIM is already used by another user.']);
        exit();
    }

    // Sanitize inputs
    $nama_lengkap = SQLite3::escapeString($nama_lengkap);
    $nim = SQLite3::escapeString($nim);

// Handle file upload
$new_image_path = $user['profile_image']; // Default to existing image
if ($profile_image && $profile_image['error'] === UPLOAD_ERR_OK) {
    $target_dir = "../img/profile/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Get NIM from the form
    $nim = $_POST['nim'];

    // Find all files with the same NIM prefix
    $files = glob($target_dir . $nim . '-*.*'); // Get all files matching [NIM]-*

    // Find the highest number used for this NIM
    $file_count = 1; // Start from 1 if no files exist
    if (!empty($files)) {
        // Extract numbers from filenames
        $numbers = [];
        foreach ($files as $file) {
            // Extract the number from the filename (e.g., "123456789-2.jpg" -> 2)
            if (preg_match('/' . preg_quote($nim, '/') . '-(\d+)\./', $file, $matches)) {
                $numbers[] = (int)$matches[1];
            }
        }
        // Find the highest number and increment by 1
        if (!empty($numbers)) {
            $file_count = max($numbers) + 1;
        }
    }

    // Generate the new file name
    $file_extension = pathinfo($profile_image['name'], PATHINFO_EXTENSION);
    $new_image_path = $target_dir . $nim . '-' . $file_count . '.' . $file_extension;

    // Move the uploaded file to the new location
    if (!move_uploaded_file($profile_image['tmp_name'], $new_image_path)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to upload image.']);
        exit();
    }
}

    // Update database
    $stmt = $db->prepare('UPDATE users SET nama_lengkap = :nama_lengkap, nim = :nim, profile_image = :profile_image WHERE id = :id');
    $stmt->bindValue(':nama_lengkap', $nama_lengkap, SQLITE3_TEXT);
    $stmt->bindValue(':nim', $nim, SQLITE3_TEXT);
    $stmt->bindValue(':profile_image', $new_image_path, SQLITE3_TEXT);
    $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);

    if ($stmt->execute()) {
        // Update session
        $_SESSION['user']['nama_lengkap'] = $nama_lengkap;
        $_SESSION['user']['nim'] = $nim;
        $_SESSION['user']['profile_image'] = $new_image_path;

        echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update profile']);
    }
    exit(); // Ensure no further output
}

// Serve HTML only for GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Form</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .avatar {
            width: 150px;
            height: 150px;
            background-color: #D9D9D9;
            border-radius: 50%;
            display: block;
            margin: 20px auto;
            position: relative;
            cursor: pointer;
        }

        .avatar input[type="file"] {
            display: none; /* Hide the file input */
        }

        .camera-icon {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background-color: #727DB6;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            cursor: pointer;
        }

        .avatar-preview {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        /* Custom styles for the popup */
        .popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 50;
            display: flex; /* Use flex to center content */
            justify-content: center; /* Center horizontally */
            align-items: center; /* Center vertically */
        }

        .popup-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            width: 300px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .checkmark, .question-mark {
            width: 60px;
            height: 60px;
            border: 4px solid #ffeb3b; /* Yellow border for question mark */
            border-radius: 50%;
            display: inline-block;
            margin-bottom: 20px;
            position: relative;
        }

        .checkmark::after {
            content: "✓";
            font-size: 30px;
            color: #28a745;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .question-mark::after {
            content: "?";
            font-size: 30px;
            color: #f57c00; /* Orange color for question mark */
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        /* Animation for fade-in effect */
        @layer utilities {
            .animate-fade-in {
                animation: fadeIn 0.5s ease-in-out;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        /* Ensure the input has enough padding on the right for the icon */
        input[type="password"] {
            padding-right: 3rem; /* pr-12 in Tailwind, adjusted here for clarity */
        }

        /* Style the toggle icon */
        .password-toggle {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3rem; /* Matches w-12 in Tailwind */
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
    </style>
</head>
<body style="background-color: #F0F4FF; font-family: 'Poppins';">
    
    <header class="bg-[#727DB6] text-white p-4 flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <span class="text-xl">Profile</span>
        </div>
        <a href="javascript:void(0)" id="closeButton" class="text-white p-3 border-2 rounded-full hover:bg-[#5c6491] border-white w-10 h-10 flex items-center justify-center flex-col space-y-0">
            <span class="text-lg">×</span>
        </a>
    </header>


    <div class="container mx-auto mt-4 p-4">
        <!-- Use flex or grid with responsive ordering -->
        <div class="flex flex-col md:grid md:grid-cols-2 gap-4" style="color:#646565">
            <!-- Avatar section with camera icon, first on mobile (order-1) and second on desktop (md:order-2) -->
            <div class="text-center order-1 md:order-2">
                <div class="avatar">
                    <img id="avatarPreview" class="avatar-preview" src="<?php echo !empty($user['profile_image']) ? $user['profile_image'] : '../src/default.png'; ?>" alt="Avatar">
                    <input type="file" id="avatarInput" accept="image/*">
                    <span class="camera-icon"><i class="fas fa-camera"></i></span>
                </div>
                <button class="text-slate-200 font-semibold py-2 px-4 rounded-md mt-2" style="background-color: #727DB6;">Remove Avatar</button>
            </div>
            
            <!-- Profile information will be second on mobile (order-2) and first on desktop (md:order-1) -->
            <div class="order-2 md:order-1">
                <label class="block font-bold mt-2">Nama</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4" value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>">
                <label class="block font-bold mt-2">NIM</label>
                <input type="text" id="nim" name="nim" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4" value="<?php echo htmlspecialchars($user['nim']); ?>">
                <label class="block font-bold mt-2">EMAIL</label>
                <input type="email" id="email" name="email" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                <!-- Tambahkan baris ini untuk menampilkan token dan tombol download -->
                <label class="block font-bold mt-2">TOKEN</label>
                <div class="flex items-center">
                    <input type="text" id="token" name="token" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4" value="<?php echo htmlspecialchars($user['token']); ?>" readonly>
                </div>
                <div class="mb-4">
                    <button id="downloadToken" class="text-white py-2 px-4 rounded-md" style="background-color: #727DB6;">Download</button>
                    <button id="regenerateToken" class="ml-2 text-white py-2 px-4 rounded-md" style="background-color: #727DB6;">Regenerate</button>
                </div>
                <label class="block font-bold mt-2">Bergabung pada</label>
                <input type="text" id="created" name="created" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4" value="<?php echo htmlspecialchars($user['created']); ?>" readonly>
            </div>
        </div>
        
        <div class="border-t-2 border-black my-4"></div>
        <!-- Change Password button to toggle the form -->
        <button id="togglePasswordChange" class=" text-white py-2 px-4 rounded-md mb-4" style="background-color: #727DB6;">Change Password</button>
        
        <div id="passwordFields" class="grid grid-cols-1 md:grid-cols-2 gap-4 hidden" style="color:#646565">
        <div>
                <label class="block font-bold mt-2">Password Baru</label>
                <div class="relative">
                    <input type="password" id="new_password" class="w-full p-4 pr-12 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter new password">
                    <span class="absolute inset-y-0 right-0 flex items-center justify-center w-12 h-full cursor-pointer" id="toggleNewPassword">
                        <i class="fas fa-eye-slash text-gray-500"></i>
                    </span>
                </div>
                <label class="block font-bold mt-2">Konfirmasi Password Baru</label>
                <div class="relative">
                    <input type="password" id="confirm_password" class="w-full p-4 pr-12 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Confirm new password">
                    <span class="absolute inset-y-0 right-0 flex items-center justify-center w-12 h-full cursor-pointer" id="toggleConfirmPassword">
                        <i class="fas fa-eye-slash text-gray-500"></i>
                    </span>
                </div>
                <div id="passwordRequirements" class="mt-2 p-2 rounded-lg text-sm" style="display: none;">
                    <p id="capitalRequirement" class="text-red-700"><i class="fas fa-exclamation-circle"></i> Minimal 1 huruf kapital</p>
                    <p id="numberRequirement" class="text-red-700"><i class="fas fa-exclamation-circle"></i> Minimal 1 angka</p>
                </div>
            </div>
        </div>
        
        <!-- Submit button (hidden by default) - Now with id="passwordButtons" -->
        <div id="passwordButtons" class="mt-4 hidden">
            <button id="submitPassword" class=" text-white py-2 px-4 rounded-md mr-2" style="background-color: #727DB6;">Submit</button>
        </div>
        
        <!-- Cancel and Save buttons (visible by default) - Now with id="submitButton" -->
        <div id="submitButton" class="mt-4">
            <button id="cancelButton" class=" text-white py-2 px-4 rounded-md mr-2" style="background-color: #727DB6;">Cancel</button>
            <button id="saveButton" class=" text-white py-2 px-4 rounded-md" style="background-color: #727DB6;">Save</button>
        </div>
    </div>


    <script>
$(document).ready(function() {


    // Close Button History
    // Get the close button (now an <a> element with id="closeButton")
    const closeButton = document.getElementById('closeButton');

    // Add click event listener to navigate back to the previous page
    closeButton.addEventListener('click', function() {
        window.history.back(); // This navigates to the previous page in the browser history
    });

    // Simpan nilai default ke dalam variabel JavaScript
    const defaultData = {
    nama_lengkap: "<?php echo htmlspecialchars($user['nama_lengkap']); ?>",
    nim: "<?php echo htmlspecialchars($user['nim']); ?>",
    profile_image: "<?php echo !empty($user['profile_image']) ? $user['profile_image'] : '../src/default.png'; ?>"
    };

    // Fungsi untuk mengembalikan nilai ke default
    function resetToDefault() {
    $('#nama_lengkap').val(defaultData.nama_lengkap); // Set nilai nama_lengkap ke default
    $('#nim').val(defaultData.nim); // Set nilai NIM ke default
    $('#avatarPreview').attr('src', defaultData.profile_image); // Set gambar profil ke default
    $('.avatar').css('background-color', 'transparent'); // Reset latar belakang avatar
    $('#avatarInput').val(''); // Reset input file
}


    // Fungsi untuk menangani download token
    $('#downloadToken').click(function() {
        const token = $('#token').val();
        if (token) {
            const blob = new Blob([token], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'token_ukm.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            // Tampilkan SweetAlert sukses
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Token berhasil diunduh!',
                confirmButtonColor: '#727DB6',
            });
        } else {
            // Tampilkan SweetAlert error
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Token tidak tersedia.',
                confirmButtonColor: '#727DB6',
            });
        }
    });

    $('#regenerateToken').click(function() {
        Swal.fire({
            title: 'Regenerate Token',
            text: 'Apakah Anda yakin ingin mengenerate ulang token? Token lama akan dihapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#727DB6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Generate Ulang',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../api/regenerate_token.php', // Endpoint untuk regenerate token
                    method: 'POST',
                    dataType: 'json',
                    success: function(data) {
                        if (data.status === 'success') {
                            $('#token').val(data.token); // Update token di halaman

                            // Tampilkan SweetAlert sukses
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: 'Token berhasil digenerate ulang: ' + data.token,
                                confirmButtonColor: '#727DB6',
                            });
                        } else {
                            // Tampilkan SweetAlert error
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Gagal mengenerate ulang token.',
                                confirmButtonColor: '#727DB6',
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);

                        // Tampilkan SweetAlert error
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Terjadi kesalahan saat mengenerate ulang token.',
                            confirmButtonColor: '#727DB6',
                        });
                    }
                });
            }
        });
    });

    // Toggle password change fields, submit button, and buttons
    $('#togglePasswordChange').click(function() {
        $('#passwordFields').toggleClass('hidden');
        $('#passwordButtons').toggleClass('hidden');
        if ($('#passwordFields').hasClass('hidden')) {
            $(this).text('Change Password');
            $('#passwordRequirements').hide();
        } else {
            $(this).text('Hide Password Change');
            $('#passwordRequirements').show();
        }
    });

    // Save Button
    $('#saveButton').click(function() {
        const nama_lengkap = $('#nama_lengkap').val();
        const nim = $('#nim').val();
        const profile_image = $('#avatarInput')[0].files[0];

        // Validasi NIM
        const nimPattern = /^\d{9,10}$/;
        if (!nimPattern.test(nim)) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'NIM harus 9-10 digit.',
                confirmButtonColor: '#727DB6',
            });
            return;
        }

        const formData = new FormData();
        formData.append('nama_lengkap', nama_lengkap);
        formData.append('nim', nim);
        if (profile_image) {
            formData.append('profile_image', profile_image);
        }

        $.ajax({
            url: 'profil.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(data) {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: 'Profil berhasil diperbarui!',
                        confirmButtonColor: '#727DB6',
                    }).then(() => {
                        window.location.href = '../page/index.php'; // Redirect setelah sukses
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        confirmButtonColor: '#727DB6',
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Terjadi kesalahan saat menyimpan data.',
                    confirmButtonColor: '#727DB6',
                });
            }
        });
    });


    // Cancel Button
    $('#cancelButton').click(function() {
        Swal.fire({
            title: 'Konfirmasi',
            text: 'Data Anda belum disimpan. Apakah Anda yakin ingin membatalkan?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#727DB6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Batalkan',
            cancelButtonText: 'Kembali',
        }).then((result) => {
            if (result.isConfirmed) {
                resetToDefault(); // Reset form ke nilai default
                window.location.href = '../page/index.php'; // Redirect ke halaman admin
            }
        });
    });

    // Real-time password validation
    $('#new_password').on('input', function() {
        const password = $(this).val();

        // Check for at least one capital letter
        const hasCapital = /[A-Z]/.test(password);
        if (hasCapital) {
            $('#capitalRequirement')
                .removeClass('text-red-700')
                .addClass('text-green-700')
                .html('<i class="fas fa-check-circle"></i> Contains at least 1 capital letter');
        } else {
            $('#capitalRequirement')
                .removeClass('text-green-700')
                .addClass('text-red-700')
                .html('<i class="fas fa-exclamation-circle"></i> Minimal 1 huruf kapital');
        }

        // Check for at least one number
        const hasNumber = /\d/.test(password);
        if (hasNumber) {
            $('#numberRequirement')
                .removeClass('text-red-700')
                .addClass('text-green-700')
                .html('<i class="fas fa-check-circle"></i> Contains at least 1 number');
        } else {
            $('#numberRequirement')
                .removeClass('text-green-700')
                .addClass('text-red-700')
                .html('<i class="fas fa-exclamation-circle"></i> Minimal 1 angka');
        }
    });

    $('#submitPassword').click(function() {
        const new_password = $('#new_password').val();
        const confirm_password = $('#confirm_password').val();

        if (!new_password || !confirm_password) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Kedua kolom password harus diisi.',
                confirmButtonColor: '#727DB6',
            });
            return;
        }
        if (new_password !== confirm_password) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Password tidak cocok.',
                confirmButtonColor: '#727DB6',
            });
            return;
        }

        // Validasi tambahan (huruf kapital dan angka)
        const hasCapital = /[A-Z]/.test(new_password);
        const hasNumber = /\d/.test(new_password);
        if (!hasCapital || !hasNumber) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Password harus mengandung minimal 1 huruf kapital dan 1 angka.',
                confirmButtonColor: '#727DB6',
            });
            return;
        }

        $.ajax({
            url: 'profil.php',
            method: 'POST',
            data: {
                new_password: new_password,
                confirm_password: confirm_password
            },
            dataType: 'json',
            success: function(data) {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: 'Password berhasil diganti!',
                        confirmButtonColor: '#727DB6',
                    }).then(() => {
                        $('#new_password').val(''); // Clear fields
                        $('#confirm_password').val('');
                        $('#passwordFields').addClass('hidden');
                        $('#passwordButtons').addClass('hidden');
                        $('#togglePasswordChange').text('Change Password');
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        confirmButtonColor: '#727DB6',
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Terjadi kesalahan saat mengganti password.',
                    confirmButtonColor: '#727DB6',
                });
            }
        });
    });

    // Toggle password visibility
    $('#toggleNewPassword').click(function() {
        const $input = $('#new_password');
        const $icon = $(this).find('i');
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $icon.removeClass('fa-eye-slash').addClass('fa-eye');
        } else {
            $input.attr('type', 'password');
            $icon.removeClass('fa-eye').addClass('fa-eye-slash');
        }
    });

    $('#toggleConfirmPassword').click(function() {
        const $input = $('#confirm_password');
        const $icon = $(this).find('i');
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $icon.removeClass('fa-eye-slash').addClass('fa-eye');
        } else {
            $input.attr('type', 'password');
            $icon.removeClass('fa-eye').addClass('fa-eye-slash');
        }
    });


    // Handle avatar image upload
    $('#avatarInput').change(function(e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#avatarPreview').attr('src', e.target.result).show();
                $('.avatar').css('background-color', 'transparent');
            };
            reader.readAsDataURL(file);
        } else {
            alert('Please select an image file.');
        }
    });

    // Trigger file input when camera icon is clicked
    $('.camera-icon').click(function() {
        $('#avatarInput').trigger('click');
    });

    // Remove avatar functionality
    $('button:contains("Remove Avatar")').click(function() {
        Swal.fire({
            title: 'Reset Foto Profil',
            text: 'Apakah Anda yakin ingin mereset foto profil?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#727DB6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Reset',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../api/remove_avatar.php',
                    method: 'POST',
                    data: { remove_avatar: true },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            // Update tampilan avatar
                            $('#avatarPreview').attr('src', '../src/default.png').show();
                            $('.avatar').css('background-color', 'transparent');
                            $('#avatarInput').val('');

                            // Tampilkan SweetAlert sukses
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: 'Foto profil berhasil direset!',
                                confirmButtonColor: '#727DB6',
                            });
                        } else {
                            // Tampilkan SweetAlert error
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Gagal mereset foto profil.',
                                confirmButtonColor: '#727DB6',
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Terjadi kesalahan saat mereset foto profil.',
                            confirmButtonColor: '#727DB6',
                        });
                    }
                });
            }
        });
    }); 
}); 
    </script>
</body>
</html>
<?php
}
?>