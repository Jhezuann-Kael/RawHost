<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../models/Database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$file     = $_FILES['avatar'];
$maxBytes = 2 * 1024 * 1024; // 2 MB

if ($file['size'] > $maxBytes) {
    echo json_encode(['success' => false, 'message' => 'File exceeds 2 MB limit']);
    exit;
}

$allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon'];
$finfo       = new finfo(FILEINFO_MIME_TYPE);
$mime        = $finfo->file($file['tmp_name']);

if (!in_array($mime, $allowedMime, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP']);
    exit;
}

$extMap = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];
$ext      = $extMap[$mime];
$userId   = (int)$_SESSION['user_id'];
$filename = $userId . '.' . $ext;
$dir      = __DIR__ . '/../../uploads/avatars/';
$destPath = $dir . $filename;

// Remove old avatars with different extension
foreach ($extMap as $oldExt) {
    $old = $dir . $userId . '.' . $oldExt;
    if ($old !== $destPath && file_exists($old)) {
        unlink($old);
    }
}

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit;
}

$relativePath = '/uploads/avatars/' . $filename;

try {
    $db   = new Database();
    $conn = $db->connect();
    $stmt = $conn->prepare("UPDATE users SET profile_picture = :pic WHERE id = :id");
    $stmt->execute([':pic' => $relativePath, ':id' => $userId]);

    echo json_encode(['success' => true, 'url' => $relativePath]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
