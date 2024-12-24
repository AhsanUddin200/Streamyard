<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Make sure stream_id is set and is numeric
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$stream_id = $_GET['id'];

// Fetch stream data
$sql = "SELECT * FROM streamyard_streams WHERE stream_id = ? AND user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$stream_id, $_SESSION['user_id']]);
$stream = $stmt->fetch(PDO::FETCH_ASSOC);

// If stream doesn't exist or doesn't belong to user, redirect
if (!$stream) {
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Stream - StreamYard Clone</title>
    <style>
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .share-link {
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .share-link input {
            width: 60%;
            padding: 5px;
            margin-right: 10px;
        }
        .video-container {
            background: #000;
            aspect-ratio: 16/9;
            margin: 20px 0;
        }
        .controls button {
            padding: 10px 20px;
            margin-right: 10px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><?php echo htmlspecialchars($stream['stream_title']); ?></h2>
        
        <div class="share-link">
            Share Link: 
            <input type="text" 
                   value="<?php echo "http://{$_SERVER['HTTP_HOST']}/join_stream.php?link=" . htmlspecialchars($stream['share_link']); ?>" 
                   readonly>
            <button onclick="copyShareLink()">Copy</button>
        </div>

        <div class="video-container">
            <video id="localVideo" autoplay muted></video>
        </div>

        <div class="participants-section">
            <h3>Participants</h3>
            <ul id="participantsList"></ul>
        </div>

        <div class="controls">
            <button id="startStream">Start Stream</button>
            <button id="startRecording">Start Recording</button>
        </div>
    </div>

    <script>
        let stream;
        let mediaRecorder;
        let recordedChunks = [];

        async function startStream() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: true,
                    audio: true
                });
                document.getElementById('localVideo').srcObject = stream;
            } catch (err) {
                console.error('Error accessing media devices:', err);
                alert('Error accessing camera and microphone');
            }
        }

        function copyShareLink() {
            const linkInput = document.querySelector('.share-link input');
            linkInput.select();
            document.execCommand('copy');
            alert('Share link copied to clipboard!');
        }

        document.getElementById('startStream').addEventListener('click', startStream);
    </script>
</body>
</html> 