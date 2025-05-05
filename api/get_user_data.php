<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

// Check if user has admin role
$user = $_SESSION['user'];
if ($user['role'] !== 'admin') {
    echo json_encode(['error' => 'Not authorized']);
    exit();
}

// Connect to database
$db = new SQLite3('../db/ukm.db');

// Get pagination parameters
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Get filter parameters if available
$filterBPH = isset($_GET['filterBPH']) ? ($_GET['filterBPH'] === 'true') : false;
$filterAnggota = isset($_GET['filterAnggota']) ? ($_GET['filterAnggota'] === 'true') : false;
$searchText = isset($_GET['search']) ? $_GET['search'] : '';

// Build WHERE clause for filtering
$whereClause = "";
$params = [];

if ($searchText) {
    $whereClause .= " WHERE (u.nama_lengkap LIKE :search OR u.nim LIKE :search OR u.email LIKE :search)";
    $params[':search'] = "%{$searchText}%";
}

if ($filterBPH && !$filterAnggota) {
    $whereClause .= ($whereClause ? " AND" : " WHERE");
    $whereClause .= " (u.jabatan IN ('Ketua', 'Wakil', 'Sekretaris', 'Bendahara', 'Acara', 'PDD'))";
} elseif (!$filterBPH && $filterAnggota) {
    $whereClause .= ($whereClause ? " AND" : " WHERE");
    $whereClause .= " (u.jabatan = 'Anggota')";
}

// Query for counting total users (with filters)
$totalQuery = "SELECT COUNT(*) as total FROM users u" . $whereClause;
$stmt = $db->prepare($totalQuery);

// Bind parameters if they exist
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$totalResult = $stmt->execute();
$totalUsers = $totalResult->fetchArray(SQLITE3_ASSOC)['total'];

// Query for retrieving user data with filters and pagination
$query = "
    SELECT u.id, u.nim, u.nama_lengkap, u.email, u.jabatan, u.profile_image, COUNT(a.nim) AS total_absen
    FROM users u
    LEFT JOIN absen a ON u.nim = a.nim
    {$whereClause}
    GROUP BY u.id
    LIMIT :limit OFFSET :offset
";

$stmt = $db->prepare($query);
$stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
$stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);

// Bind other parameters if they exist
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$result = $stmt->execute();

// Build response array
$users = [];
$no = $offset + 1; // Start numbering from the offset + 1
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $row['no'] = $no++;
    $row['profile_image'] = !empty($row['profile_image']) ? $row['profile_image'] : '../src/default.png';
    $users[] = $row;
}

// Return response as JSON
echo json_encode([
    'users' => $users,
    'totalUsers' => $totalUsers,
    'page' => $page,
    'limit' => $limit,
    'totalPages' => ceil($totalUsers / $limit)
]);

// Close database connection
$db->close();
?>