<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_FILES['recording'])) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$stream_id = $_POST['stream_id'];
$upload_dir = 'recordings/';

// Create directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$filename = uniqid('rec_') . '.webm';
$filepath = $upload_dir . $filename;

if (move_uploaded_file($_FILES['recording']['tmp_name'], $filepath)) {
    // Save recording info to database
    $sql = "INSERT INTO streamyard_recordings (stream_id, file_path) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$stream_id, $filepath]);
    
    echo json_encode(['success' => true, 'filepath' => $filepath]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save recording']);
}
?> 