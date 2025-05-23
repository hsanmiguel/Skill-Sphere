<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: entry/sign_in.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "registered_accounts");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = $_SESSION['email'];

// Fetch all notifications (including deleted)
$all_notifications = [];
$notifRes = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($notifRes && $notifRes->num_rows > 0) {
    $notifQ = $conn->prepare("SELECT message, type, created_at, deleted FROM notifications WHERE user_email=? ORDER BY created_at DESC");
    $notifQ->bind_param("s", $email);
    $notifQ->execute();
    $notifQ->bind_result($msg, $type, $created_at, $deleted);
    while ($notifQ->fetch()) {
        $all_notifications[] = ['message'=>$msg, 'type'=>$type, 'created_at'=>$created_at, 'deleted'=>$deleted];
    }
    $notifQ->close();
}

// Fetch all requests sent (including deleted)
$all_requests_sent = [];
$reqSentRes = $conn->query("SHOW TABLES LIKE 'requests'");
if ($reqSentRes && $reqSentRes->num_rows > 0) {
    $reqSentQ = $conn->prepare("SELECT receiver_email, service, status, created_at, deleted FROM requests WHERE sender_email=? ORDER BY created_at DESC");
    $reqSentQ->bind_param("s", $email);
    $reqSentQ->execute();
    $reqSentQ->bind_result($receiver_email, $service, $status, $created_at, $deleted);
    while ($reqSentQ->fetch()) {
        $all_requests_sent[] = ['receiver_email'=>$receiver_email, 'service'=>$service, 'status'=>$status, 'created_at'=>$created_at, 'deleted'=>$deleted];
    }
    $reqSentQ->close();
}

// Fetch all requests received (including deleted)
$all_requests_received = [];
if ($reqSentRes && $reqSentRes->num_rows > 0) {
    $reqRecvQ = $conn->prepare("SELECT sender_email, service, status, created_at, deleted FROM requests WHERE receiver_email=? ORDER BY created_at DESC");
    $reqRecvQ->bind_param("s", $email);
    $reqRecvQ->execute();
    $reqRecvQ->bind_result($sender_email, $service, $status, $created_at, $deleted);
    while ($reqRecvQ->fetch()) {
        $all_requests_received[] = ['sender_email'=>$sender_email, 'service'=>$service, 'status'=>$status, 'created_at'=>$created_at, 'deleted'=>$deleted];
    }
    $reqRecvQ->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Full History - Skill Sphere</title>
    <link rel="stylesheet" href="designs/footer.css">
    <link rel="stylesheet" href="designs/header1.css">
    <style>
        body { background: #f6f7f9; }
        .history-container { max-width: 1000px; margin: 40px auto 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); padding: 36px 32px; }
        h2 { color: #1B4D43; text-align: center; margin-bottom: 32px; }
        .history-section { margin-bottom: 36px; }
        .history-section h3 { color: #1B4D43; font-size: 1.2rem; margin-bottom: 16px; }
        ul.history-list { list-style: none; padding: 0; margin: 0; }
        .history-item { background: #f8f9fa; border-radius: 8px; margin-bottom: 12px; padding: 14px 12px; display: flex; flex-direction: column; gap: 6px; }
        .history-meta { color: #888; font-size: 0.93em; }
        .deleted-label { color: #d32f2f; font-weight: 600; margin-left: 8px; }
        .empty-msg { color: #888; text-align: center; margin: 18px 0 0 0; font-size: 1.05rem; }
    </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="history-container">
    <h2>Full History</h2>
    <div class="history-section">
        <h3>All Notifications</h3>
        <?php if (!empty($all_notifications)): ?>
            <ul class="history-list">
                <?php foreach ($all_notifications as $notif): ?>
                    <li class="history-item">
                        <div><?php echo htmlspecialchars($notif['message']); ?>
                            <?php if ($notif['deleted']): ?><span class="deleted-label">[Deleted]</span><?php endif; ?>
                        </div>
                        <div class="history-meta">Type: <?php echo htmlspecialchars($notif['type']); ?> | <?php echo htmlspecialchars($notif['created_at']); ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="empty-msg">No notifications in history.</div>
        <?php endif; ?>
    </div>
    <div class="history-section">
        <h3>All Requests Sent</h3>
        <?php if (!empty($all_requests_sent)): ?>
            <ul class="history-list">
                <?php foreach ($all_requests_sent as $req): ?>
                    <li class="history-item">
                        <div>To: <b><?php echo htmlspecialchars($req['receiver_email']); ?></b>
                            <?php if ($req['deleted']): ?><span class="deleted-label">[Deleted]</span><?php endif; ?>
                        </div>
                        <div>Service: <b><?php echo htmlspecialchars($req['service']); ?></b></div>
                        <div>Status: <?php echo htmlspecialchars($req['status']); ?></div>
                        <div class="history-meta"><?php echo htmlspecialchars($req['created_at']); ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="empty-msg">No requests sent in history.</div>
        <?php endif; ?>
    </div>
    <div class="history-section">
        <h3>All Requests Received</h3>
        <?php if (!empty($all_requests_received)): ?>
            <ul class="history-list">
                <?php foreach ($all_requests_received as $req): ?>
                    <li class="history-item">
                        <div>From: <b><?php echo htmlspecialchars($req['sender_email']); ?></b>
                            <?php if ($req['deleted']): ?><span class="deleted-label">[Deleted]</span><?php endif; ?>
                        </div>
                        <div>Service: <b><?php echo htmlspecialchars($req['service']); ?></b></div>
                        <div>Status: <?php echo htmlspecialchars($req['status']); ?></div>
                        <div class="history-meta"><?php echo htmlspecialchars($req['created_at']); ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="empty-msg">No requests received in history.</div>
        <?php endif; ?>
    </div>
</div>
<?php include 'footer.php'; ?>
</body>
</html> 