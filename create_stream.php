<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $stream_key = uniqid('stream_', true);
    $share_link = bin2hex(random_bytes(16)); // Generate unique share link
    
    $sql = "INSERT INTO streamyard_streams (user_id, stream_title, stream_key, share_link) 
            VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id'], $title, $stream_key, $share_link]);
    
    header('Location: dashboard.php');
    exit();
}
?>

<!-- HTML remains same as before --> 