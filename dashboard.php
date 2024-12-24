<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch user's streams
$sql = "SELECT * FROM streamyard_streams WHERE user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$streams = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - StreamYard Clone</title>
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .stream-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .stream-card {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
        }
        .create-btn {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
        }
        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
            <div>
                <a href="create_stream.php" class="create-btn">Create New Stream</a>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <div class="stream-grid">
            <?php foreach ($streams as $stream): ?>
                <div class="stream-card">
                    <h3><?php echo htmlspecialchars($stream['stream_title']); ?></h3>
                    <p>Stream Key: <?php echo htmlspecialchars($stream['stream_key']); ?></p>
                    <p>Status: <?php echo $stream['is_live'] ? 'Live' : 'Offline'; ?></p>
                    <a href="stream.php?id=<?php echo $stream['stream_id']; ?>">
                        <?php echo $stream['is_live'] ? 'Join Stream' : 'Start Stream'; ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html> 