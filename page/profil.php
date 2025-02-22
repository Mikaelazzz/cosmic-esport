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

    // Get form data
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
        $target_dir = "uploads/profile_images/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $new_image_path = $target_dir . basename($profile_image['name']);
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
    </style>
</head>
<body style="background-color: #F0F4FF; font-family: 'Poppins';">
    <div class="text-white p-5 text-left text-2xl font-bold" style="background-color: #727DB6;">Profile</div>
    <div class="container mx-auto mt-4 p-4">
        <!-- Use flex or grid with responsive ordering -->
        <div class="flex flex-col md:grid md:grid-cols-2 gap-4" style="color:#646565">
            <!-- Avatar section with camera icon, first on mobile (order-1) and second on desktop (md:order-2) -->
            <div class="text-center order-1 md:order-2">
                <div class="avatar">
                    <img id="avatarPreview" class="avatar-preview" src="<?php echo !empty($user['profile_image']) ? $user['profile_image'] : '../src/1.png'; ?>" alt="Avatar">
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
                <label class="block font-bold mt-2">Bergabung pada</label>
                <input type="text" id="created" name="created" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4" value="<?php echo htmlspecialchars($user['created']); ?>" readonly>
            </div>
        </div>
        
        <div class="border-t-2 border-black my-4"></div>
        <h5 class="font-bold text-lg" style="color:#646565">Change Password</h5>
        
        <!-- Change Password button to toggle the form -->
        <button id="togglePasswordChange" class=" text-white py-2 px-4 rounded-md mb-4" style="background-color: #727DB6;">Change Password</button>
        
        <!-- Hidden Change Password fields by default -->
        <div id="passwordFields" class="grid grid-cols-1 md:grid-cols-2 gap-4 hidden" style="color:#646565">
            <div>
                <label class="block font-bold mt-2">Password Baru</label>
                <input type="password" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4">
                <span class="absolute right-3 inset-y-0 flex items-center text-gray-500 cursor-pointer" id="togglePassword">
                    <i class="fas fa-eye-slash"></i>
                </span>
                <label class="block font-bold mt-2">Konfirmasi Password Baru</label>
                <input type="password" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4">
                <span class="absolute right-3 inset-y-0 flex items-center text-gray-500 cursor-pointer" id="togglePassword">
                    <i class="fas fa-eye-slash"></i>
                </span>
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

    <!-- Popup Notification for General Update -->
    <div id="successPopup" class="popup">
        <div class="popup-content">
            <div class="checkmark"></div>
            <h2 class="text-xl font-bold text-gray-700 mb-2">Berhasil</h2>
            <p class="text-gray-600 mb-4">Anda berhasil memperbarui Data</p>
            <button id="closePopup" class=" text-white py-2 px-4 rounded-md" style="background-color: #727DB6;">OK</button>
        </div>
    </div>

    <!-- Popup Notification for Password Update -->
    <div id="passwordPopup" class="popup">
        <div class="popup-content">
            <div class="checkmark"></div>
            <h2 class="text-xl font-bold text-gray-700 mb-2">Berhasil</h2>
            <p class="text-gray-600 mb-4">Password telah diperbarui</p>
            <button id="closePasswordPopup" class=" text-white py-2 px-4 rounded-md" style="background-color: #727DB6;">OK</button>
        </div>
    </div>

    <!-- Popup Notification for Cancel Confirmation -->
    <div id="cancelPopup" class="popup">
        <div class="popup-content">
            <div class="question-mark"></div>
            <h2 class="text-xl font-bold text-gray-700 mb-2">Konfirmasi</h2>
            <p class="text-gray-600 mb-4">Data anda belum di simpan</p>
            <div class="flex justify-center gap-4">
                <button id="discardData" class="bg-red-600 text-white py-2 px-4 rounded-md">Discard Data</button>
                <button id="goBack" class=" text-white py-2 px-4 rounded-md" style="background-color: #727DB6;">Kembali</button>
            </div>
        </div>
    </div>


    <!-- Popup Notification for Error -->
    <div id="errorPopup" class="popup">
        <div class="popup-content">
            <div class="question-mark"></div>
            <h2 class="text-xl font-bold text-gray-700 mb-2">Error</h2>
            <p id="errorMessage" class="text-gray-600 mb-4"></p>
            <button id="closeErrorPopup" class="text-white py-2 px-4 rounded-md" style="background-color: #727DB6;">OK</button>
        </div>
    </div>

    <script>
$(document).ready(function() {
    // Ensure all popups are hidden on page load
    $('#successPopup').hide();
    $('#passwordPopup').hide();
    $('#cancelPopup').hide();
    $('#errorPopup').hide();

    // Simpan nilai default ke dalam variabel JavaScript
    const defaultData = {
    nama_lengkap: "<?php echo htmlspecialchars($user['nama_lengkap']); ?>",
    nim: "<?php echo htmlspecialchars($user['nim']); ?>",
    profile_image: "<?php echo !empty($user['profile_image']) ? $user['profile_image'] : '../src/1.png'; ?>"
    };

    // Fungsi untuk mengembalikan nilai ke default
    function resetToDefault() {
    $('#nama_lengkap').val(defaultData.nama_lengkap); // Set nilai nama_lengkap ke default
    $('#nim').val(defaultData.nim); // Set nilai NIM ke default
    $('#avatarPreview').attr('src', defaultData.profile_image); // Set gambar profil ke default
    $('.avatar').css('background-color', 'transparent'); // Reset latar belakang avatar
    $('#avatarInput').val(''); // Reset input file
}

    // Toggle password change fields, submit button, and buttons
    $('#togglePasswordChange').click(function() {
        $('#passwordFields').toggleClass('hidden');
        $('#passwordButtons').toggleClass('hidden');
        if ($('#passwordFields').hasClass('hidden')) {
            $(this).text('Change Password');
        } else {
            $(this).text('Hide Password Change');
        }
    });

    // Show cancel confirmation popup when Cancel button is clicked
    $('#cancelButton').click(function() {
        $('#cancelPopup').fadeIn(500).addClass('animate-fade-in');
    });

    // Handle Cancel Popup actions
    $('#discardData').click(function() {
        window.location.href = '../page/home.php';
        $('#passwordFields').addClass('hidden');
        $('#passwordButtons').addClass('hidden');
        $('#togglePasswordChange').text('Change Password');
        $('#cancelPopup').fadeOut(500, function() {
            $(this).removeClass('animate-fade-in');
        });
    });

    $('#goBack').click(function() {
        $('#cancelPopup').fadeOut(500, function() {
            $(this).removeClass('animate-fade-in');
        });
    });

    $('#saveButton').click(function() {
            const nama_lengkap = $('#nama_lengkap').val();
            const nim = $('#nim').val();
            const profile_image = $('#avatarInput')[0].files[0];

            // Validate NIM: Must be 9-10 digits
            const nimPattern = /^\d{9,10}$/;
            if (!nimPattern.test(nim)) {
                showErrorPopup('NIM must be 9-10 digits.');
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
                dataType: 'json', // Explicitly expect JSON
                success: function(data) { // Response is already parsed
                    if (data.status === 'success') {
                        $('#successPopup').fadeIn(500).addClass('animate-fade-in');
                        setTimeout(function() {
                            window.location.href = '../page/home.php'; // Redirect on success
                        }, 2000);
                    } else {
                        showErrorPopup(data.message); // Show error message from server
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error saving data:', error);
                    showErrorPopup('Failed to save data: ' + error);
                }
            });
        });

    // Show password update popup when Submit button is clicked
    $('#submitPassword').click(function() {
        $('#passwordPopup').fadeIn(500).addClass('animate-fade-in');
    });

    // Close general update popup when OK is clicked
    $('#closePopup').click(function() {
        $('#successPopup').fadeOut(500, function() {
            $(this).removeClass('animate-fade-in');
        });
    });

    // Close password update popup when OK is clicked
    $('#closePasswordPopup').click(function() {
        $('#passwordPopup').fadeOut(500, function() {
            $(this).removeClass('animate-fade-in');
            window.location.href = '../page/home.php';
        });
    });

    // Fungsi untuk menampilkan popup error
    function showErrorPopup(message) {
        $('#errorMessage').text(message); // Set pesan error
        $('#errorPopup').fadeIn(500).addClass('animate-fade-in');
    }

    // Fungsi untuk menutup popup error
    $('#closeErrorPopup').click(function() {
        $('#errorPopup').fadeOut(500, function() {
            $(this).removeClass('animate-fade-in');
        });
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
        $('#avatarPreview').attr('src', '../src/default.png').show();
        $('.avatar').css('background-color', 'transparent');
        $('#avatarInput').val('');

        $.ajax({
            url: 'remove_avatar.php',
            method: 'POST',
            data: { remove_avatar: true },
            success: function(response) {
                console.log('Avatar removed successfully');
            },
            error: function(xhr, status, error) {
                console.error('Error removing avatar:', error);
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