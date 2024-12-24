<?php
session_start();
require_once 'db.php';

// Check if share link exists
if (!isset($_GET['link'])) {
    die("Invalid link");
}

$share_link = $_GET['link'];

// Get stream details
$sql = "SELECT * FROM streamyard_streams WHERE share_link = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$share_link]);
$stream = $stmt->fetch();

if (!$stream) {
    die("Stream not found or has ended");
}

// Add participant to database if logged in
if (isset($_SESSION['user_id'])) {
    $sql = "INSERT INTO streamyard_participants (stream_id, user_id) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE joined_at = CURRENT_TIMESTAMP";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$stream['stream_id'], $_SESSION['user_id']]);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Join Stream - StreamYard</title>
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .stream-container {
            display: grid;
            grid-template-columns: 3fr 1fr;
            gap: 20px;
        }
        .video-container {
            background: #000;
            aspect-ratio: 16/9;
            position: relative;
        }
        #remoteVideo {
            width: 100%;
            height: 100%;
        }
        .chat-container {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            height: 500px;
            display: flex;
            flex-direction: column;
        }
        .chat-messages {
            flex-grow: 1;
            overflow-y: auto;
            margin-bottom: 10px;
            padding: 10px;
        }
        .chat-input {
            display: flex;
            gap: 10px;
        }
        .chat-input input {
            flex-grow: 1;
            padding: 8px;
        }
        .controls {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        .control-btn {
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            background: #007bff;
            color: white;
        }
        .control-btn.mute {
            background: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><?php echo htmlspecialchars($stream['stream_title']); ?></h2>
        
        <div class="stream-container">
            <div class="video-container">
                <video id="remoteVideo" autoplay></video>
                <video id="localVideo" autoplay muted style="position: absolute; bottom: 10px; right: 10px; width: 200px;"></video>
            </div>
            
            <div class="chat-container">
                <div class="chat-messages" id="chatMessages"></div>
                <div class="chat-input">
                    <input type="text" id="messageInput" placeholder="Type your message...">
                    <button onclick="sendMessage()" class="control-btn">Send</button>
                </div>
            </div>
        </div>

        <div class="controls">
            <button class="control-btn" id="toggleVideo">Turn Video On/Off</button>
            <button class="control-btn" id="toggleAudio">Turn Audio On/Off</button>
            <button class="control-btn" id="leaveStream">Leave Stream</button>
        </div>
    </div>

    <script>
        let peerConnection;
        let localStream;
        let remoteStream;

        // WebRTC configuration
        const configuration = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' }
            ]
        };

        async function initializeStream() {
            try {
                // Get local media stream
                localStream = await navigator.mediaDevices.getUserMedia({
                    video: true,
                    audio: true
                });
                
                document.getElementById('localVideo').srcObject = localStream;

                // Initialize WebSocket connection
                connectToSignalingServer();

            } catch (err) {
                console.error('Error accessing media devices:', err);
                alert('Could not access camera or microphone');
            }
        }

        function connectToSignalingServer() {
            // Create WebSocket connection
            const ws = new WebSocket('ws://' + window.location.hostname + ':8080');
            
            ws.onopen = () => {
                console.log('Connected to signaling server');
                // Send join message with stream ID
                ws.send(JSON.stringify({
                    type: 'join',
                    streamId: '<?php echo $stream['stream_id']; ?>'
                }));
            };

            ws.onmessage = async (event) => {
                const message = JSON.parse(event.data);
                handleSignalingMessage(message);
            };
        }

        async function handleSignalingMessage(message) {
            switch (message.type) {
                case 'offer':
                    await handleOffer(message.offer);
                    break;
                case 'answer':
                    await handleAnswer(message.answer);
                    break;
                case 'ice-candidate':
                    await handleIceCandidate(message.candidate);
                    break;
            }
        }

        function toggleVideo() {
            if (localStream) {
                const videoTrack = localStream.getVideoTracks()[0];
                videoTrack.enabled = !videoTrack.enabled;
                document.getElementById('toggleVideo').textContent = 
                    videoTrack.enabled ? 'Turn Video Off' : 'Turn Video On';
            }
        }

        function toggleAudio() {
            if (localStream) {
                const audioTrack = localStream.getAudioTracks()[0];
                audioTrack.enabled = !audioTrack.enabled;
                document.getElementById('toggleAudio').textContent = 
                    audioTrack.enabled ? 'Turn Audio Off' : 'Turn Audio On';
            }
        }

        function leaveStream() {
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
            }
            if (peerConnection) {
                peerConnection.close();
            }
            window.location.href = 'dashboard.php';
        }

        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (message) {
                const chatMessages = document.getElementById('chatMessages');
                const messageElement = document.createElement('div');
                messageElement.textContent = `You: ${message}`;
                chatMessages.appendChild(messageElement);
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Clear input
                input.value = '';
                
                // Send message through WebSocket
                // Add your WebSocket message sending code here
            }
        }

        // Add event listeners
        document.getElementById('toggleVideo').addEventListener('click', toggleVideo);
        document.getElementById('toggleAudio').addEventListener('click', toggleAudio);
        document.getElementById('leaveStream').addEventListener('click', leaveStream);
        document.getElementById('messageInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });

        // Initialize when page loads
        initializeStream();
    </script>
</body>
</html> 