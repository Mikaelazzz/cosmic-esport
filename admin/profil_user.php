<?php
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../page/login.php");
    exit();
}

// Set waktu aktivitas terakhir jika belum ada
if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
}

// Cek jika waktu tidak aktif melebihi 1 jam (3600 detik)
if (time() - $_SESSION['last_activity'] > 3600) {
    // Hapus session dan redirect ke halaman login
    session_unset();
    session_destroy();
    header("Location: ../page/login.php");
    exit();
}

// Perbarui waktu aktivitas terakhir
$_SESSION['last_activity'] = time();

// Ambil data pengguna dari session
$user = $_SESSION['user'];
if ($user['role'] !== 'admin') {
    header("Location: ../page/home.php");
    exit();
}

// Connect to database
$db = new SQLite3('../db/ukm.db');


// Ambil ID pengguna dari URL
$userId = $_GET['id'] ?? null;

if (!$userId) {
    header("Location: ../admin/index.php");
    exit();
}

// Query untuk mengambil data pengguna yang akan diedit
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
$result = $stmt->execute();
$userData = $result->fetchArray(SQLITE3_ASSOC);

if (!$userData) {
    header("Location: ../admin/index.php");
    exit();
}

// Query untuk mengambil data kehadiran
$query = "
    SELECT 
        j.id AS pertemuan_id,
        j.nama_topik,
        j.hari,
        j.tanggal,
        j.jam_pertemuan,
        a.status AS status_absen
    FROM 
        jadwal_pertemuan j
    LEFT JOIN 
        absen a ON j.id = a.pertemuan_id AND a.nim = :nim
    ORDER BY 
        j.tanggal DESC, j.jam_pertemuan DESC;
";
$stmt = $db->prepare($query);
$stmt->bindValue(':nim', $userData['nim'], SQLITE3_TEXT);
$result = $stmt->execute();

// Inisialisasi nomor urut
$no = 1;

// Serve HTML only for GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
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
            <span class="text-xl">Edit Profile</span>
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
                    <img id="avatarPreview" class="avatar-preview" src="<?php echo !empty($userData['profile_image']) ? $userData['profile_image'] : '../src/default.png'; ?>" alt="Avatar">
                    <input type="file" id="avatarInput" accept="image/*">
                </div>
                <button class="text-slate-200 font-semibold py-2 px-4 rounded-md mt-2" style="background-color: #727DB6;">Remove Avatar</button>
            </div>
            
            <!-- Profile information will be second on mobile (order-2) and first on desktop (md:order-1) -->
            <div class="order-2 md:order-1">
                <label class="block font-bold mt-2">Nama</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4" value="<?php echo htmlspecialchars($userData['nama_lengkap'] ?? ''); ?>">
                <label class="block font-bold mt-2">NIM</label>
                <input type="text" id="nim" name="nim" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4" value="<?php echo htmlspecialchars($userData['nim'] ?? ''); ?>">
                <label class="block font-bold mt-2">EMAIL</label>
                <input type="email" id="email" name="email" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4" value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" readonly>
                <!-- Tambahkan baris ini untuk menampilkan token dan tombol download -->
                <label class="block font-bold mt-2">TOKEN</label>
                <div class="flex items-center">
                <input type="text" id="token" name="token" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4" value="<?php echo htmlspecialchars($userData['token'] ?? ''); ?>" readonly>
                </div>
                <div class="mb-4">
                    <button id="downloadToken" class="text-white py-2 px-4 rounded-md" style="background-color: #727DB6;">Download</button>
                    <button id="regenerateToken" class="ml-2 text-white py-2 px-4 rounded-md" style="background-color: #727DB6;">Regenerate</button>
                </div>
                <label class="block font-bold mt-2">Bergabung pada</label>
                <input type="text" id="created" name="created" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4" value="<?php echo htmlspecialchars($userData['created'] ?? ''); ?>" readonly>
                <!-- Tambahkan di bawah "Bergabung Pada" -->
                <label class="block font-bold mt-2">Role</label>
                    <select id="role" name="role" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4">
                        <option value="anggota" <?php echo ($userData['role'] === 'anggota') ? 'selected' : ''; ?>>Anggota</option>
                        <option value="admin" <?php echo ($userData['role'] === 'admin') ? 'selected' : ''; ?>>BPH (Admin)</option>
                    </select>

                    <label class="block font-bold mt-2">Jabatan</label>
                    <select id="jabatan" name="jabatan" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4">
                        <?php
                        // Tampilkan opsi jabatan berdasarkan role
                        if ($userData['role'] === 'anggota') {
                            echo '<option value="Anggota" selected>Anggota</option>';
                        } else {
                            // Daftar jabatan untuk BPH (Admin)
                            $jabatanOptions = ['Ketua', 'Wakil', 'Sekretaris', 'Bendahara', 'Acara', 'PDD'];
                            foreach ($jabatanOptions as $jabatan) {
                                $selected = ($userData['jabatan'] === $jabatan) ? 'selected' : '';
                                echo "<option value='$jabatan' $selected>$jabatan</option>";
                            }
                        }
                        ?>
                    </select>
            </div>
        </div>
        
        <div class="border-t-2 border-black my-4"></div>
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-gray-700 text-lg">List Kehadiran Pertemuan</h3>
            <button id="resetAllAttendance" class="text-white py-2 px-4 rounded-md" style="background-color: #727DB6;">Reset Seluruh Kehadiran</button>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
    <table class="w-full text-left">
        <thead>
            <tr class="bg-gray-200">
                <th class="p-2">No</th>
                <th class="p-2">Nama Topik</th>
                <th class="p-2 text-center">Date</th>
                <th class="p-2 text-center">Jam Pertemuan</th>
                <th class="p-2 text-center">Status Absen</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetchArray(SQLITE3_ASSOC)): ?>
                <tr class="<?php echo $no % 2 === 0 ? 'bg-blue-100' : 'bg-blue-200'; ?>">
                    <td class="p-2"><?php echo $no++; ?></td>
                    <td class="p-2"><?php echo htmlspecialchars($row['nama_topik']); ?></td>
                    <td class="p-2 text-center">
                    <?php 
                        // Format tanggal ke DD-MM-YYYY
                        $tanggal = DateTime::createFromFormat('Y-m-d', $row['tanggal']);
                        $formattedDate = $tanggal ? $tanggal->format('d-m-Y') : 'Invalid date';
                        
                        echo htmlspecialchars($row['hari']) . ', ' . 
                             $formattedDate
                        ?>
                    </td>
                    <td class="p-2 text-center">
                    <?php                
                    echo htmlspecialchars($row['jam_pertemuan']);
                        ?>
                    </td>
                    <td class="p-2 text-center">
                        <?php 
                        if ($row['status_absen'] === 'Hadir') {
                            echo '<span class="text-green-600">Hadir</span>';
                        } else {
                            echo '<span class="text-red-600">Tidak Hadir</span>';
                        }
                        ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
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
            <button id="cancelButton" class=" text-white py-2 px-4 rounded-md mr-2" style="background-color: #727DB6;">Discard</button>
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

    // Fungsi untuk mengubah opsi jabatan berdasarkan role
    function updateJabatanOptions() {
        const role = $('#role').val();
        const jabatanSelect = $('#jabatan');

        if (role === 'anggota') {
            jabatanSelect.html('<option value="Anggota">Anggota</option>');
        } else if (role === 'admin') {
            const jabatanOptions = ['Ketua', 'Wakil', 'Sekretaris', 'Bendahara', 'Acara', 'PDD'];
            let options = '';
            jabatanOptions.forEach(jabatan => {
                // Cek apakah jabatan ini adalah jabatan yang sudah dipilih sebelumnya
                const selected = jabatan === "<?php echo $userData['jabatan']; ?>" ? 'selected' : '';
                options += `<option value="${jabatan}" ${selected}>${jabatan}</option>`;
            });
            jabatanSelect.html(options);
        }
    }

    // Panggil fungsi saat role berubah
    $('#role').change(updateJabatanOptions);

    // Panggil fungsi saat halaman dimuat
    updateJabatanOptions();

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
        const role = $('#role').val();
        const jabatan = $('#jabatan').val();
        const profile_image = $('#avatarInput')[0].files[0];

        // Cek apakah user yang diedit adalah dirinya sendiri
        const isEditingSelf = "<?php echo $userId; ?>" === "<?php echo $_SESSION['user']['id']; ?>";

        // Cek apakah role diubah dari admin ke anggota
        const isChangingToAnggota = isEditingSelf && role === 'anggota' && "<?php echo $userData['role']; ?>" === 'admin';

        if (isChangingToAnggota) {
            // Tampilkan popup konfirmasi
            Swal.fire({
                title: 'Konfirmasi',
                text: 'Apakah Anda yakin ingin mengubah diri Anda menjadi anggota?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#727DB6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Ubah',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    // Lanjutkan proses update
                    updateProfile(nama_lengkap, nim, role, jabatan, profile_image, true);
                }
            });
        } else {
            // Lanjutkan proses update tanpa konfirmasi
            updateProfile(nama_lengkap, nim, role, jabatan, profile_image, false);
        }
    });

    // Fungsi untuk mengupdate profil
    function updateProfile(nama_lengkap, nim, role, jabatan, profile_image, redirectAfterUpdate) {
        const formData = new FormData();
        formData.append('id', "<?php echo $userId; ?>");
        formData.append('nama_lengkap', nama_lengkap);
        formData.append('nim', nim);
        formData.append('role', role);
        formData.append('jabatan', jabatan);
        if (profile_image) {
            formData.append('profile_image', profile_image);
        }

        $.ajax({
            url: '../api/update_profile.php',
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
                        if (redirectAfterUpdate) {
                            // Tampilkan hitung mundur 5 detik
                            let timer = 5;
                            const timerInterval = setInterval(() => {
                                Swal.fire({
                                    title: 'Anda akan dialihkan dalam...',
                                    html: `<b>${timer}</b> detik`,
                                    timer: 1000, // Update setiap 1 detik
                                    timerProgressBar: true,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    },
                                    willClose: () => {
                                        clearInterval(timerInterval);
                                    }
                                });
                                timer--;
                            }, 1000);

                            // Redirect ke page/index.php setelah 5 detik
                            setTimeout(() => {
                                window.location.href = '../page/index.php';
                            }, 5000);
                        } else {
                            window.location.href = '../admin/anggota.php';
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Terjadi kesalahan saat menyimpan data.',
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
    }

    // Reset Seluruh Absen pada User Profile
    $('#resetAllAttendance').click(function() {
        Swal.fire({
            title: 'Reset Seluruh Kehadiran',
            text: 'Apakah Anda yakin ingin mereset seluruh kehadiran untuk user ini? Tindakan ini tidak dapat dibatalkan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#727DB6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Reset',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../api/reset_all_attendance.php',
                    method: 'POST',
                    data: { 
                        user_id: "<?php echo $userData['nim']; ?>" // Kirim NIM user yang akan direset
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: data.message || 'Seluruh kehadiran berhasil direset untuk user ini!',
                                confirmButtonColor: '#727DB6',
                            }).then(() => {
                                location.reload(); // Reload halaman untuk memperbarui tampilan
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Terjadi kesalahan saat mereset kehadiran.',
                                confirmButtonColor: '#727DB6',
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Terjadi kesalahan saat mereset kehadiran.',
                            confirmButtonColor: '#727DB6',
                        });
                    }
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
                window.location.href = '../admin/anggota.php'; // Redirect ke halaman admin
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
            url: 'profil_user.php',
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