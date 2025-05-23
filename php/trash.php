<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: entry/sign_in.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "registered_accounts");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = $_SESSION['email'];

// Handle restore and permanent delete actions
if (isset($_POST['action']) && isset($_POST['item_id']) && isset($_POST['item_type'])) {
    $item_id = intval($_POST['item_id']);
    $item_type = $_POST['item_type'];
    $action = $_POST['action'];
    if ($item_type === 'notification') {
        if ($action === 'restore') {
            $stmt = $conn->prepare("UPDATE notifications SET deleted = 0 WHERE id = ? AND user_email = ?");
            $stmt->bind_param("is", $item_id, $email);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'delete_permanent') {
            $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_email = ?");
            $stmt->bind_param("is", $item_id, $email);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($item_type === 'request') {
        // For requests, user can only delete/restore their own sent or received requests
        if ($action === 'restore') {
            $stmt = $conn->prepare("UPDATE requests SET deleted = 0 WHERE id = ? AND (sender_email = ? OR receiver_email = ?)");
            $stmt->bind_param("iss", $item_id, $email, $email);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'delete_permanent') {
            $stmt = $conn->prepare("DELETE FROM requests WHERE id = ? AND (sender_email = ? OR receiver_email = ?)");
            $stmt->bind_param("iss", $item_id, $email, $email);
            $stmt->execute();
            $stmt->close();
        }
    }
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if ($isAjax) {
        ob_start();
        ?>
        <div class="trash-container">
            <h2>Trash / History</h2>
            <?php if ($modal_feedback): ?>
                <div class="modal-feedback"><?php echo htmlspecialchars($modal_feedback); ?></div>
            <?php endif; ?>
            <div class="trash-section">
                <h3>Deleted Notifications</h3>
                <?php if (!empty($deleted_notifications)): ?>
                    <ul class="trash-list">
                        <?php foreach ($deleted_notifications as $notif): ?>
                            <li class="trash-item">
                                <div><?php echo htmlspecialchars($notif['message']); ?></div>
                                <div class="trash-meta">Type: <?php echo htmlspecialchars($notif['type']); ?> | <?php echo htmlspecialchars($notif['created_at']); ?></div>
                                <div class="trash-actions">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="item_id" value="<?php echo $notif['id']; ?>">
                                        <input type="hidden" name="item_type" value="notification">
                                        <button type="submit" name="action" value="restore" class="restore-btn">⟲ Restore</button>
                                        <button type="submit" name="action" value="delete_permanent" class="delete-perm-btn">🗑️ Delete Permanently</button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-msg">No deleted notifications.</div>
                <?php endif; ?>
            </div>
            <div class="trash-section">
                <h3>Deleted Requests Sent</h3>
                <?php if (!empty($deleted_requests_sent)): ?>
                    <ul class="trash-list">
                        <?php foreach ($deleted_requests_sent as $req): ?>
                            <li class="trash-item">
                                <div>To: <b><?php echo htmlspecialchars($req['receiver_email']); ?></b></div>
                                <div>Service: <b><?php echo htmlspecialchars($req['service']); ?></b></div>
                                <div>Status: <?php echo htmlspecialchars($req['status']); ?></div>
                                <div class="trash-meta"><?php echo htmlspecialchars($req['created_at']); ?></div>
                                <div class="trash-actions">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="item_id" value="<?php echo $req['id']; ?>">
                                        <input type="hidden" name="item_type" value="request">
                                        <button type="submit" name="action" value="restore" class="restore-btn">⟲ Restore</button>
                                        <button type="submit" name="action" value="delete_permanent" class="delete-perm-btn">🗑️ Delete Permanently</button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-msg">No deleted requests sent.</div>
                <?php endif; ?>
            </div>
            <div class="trash-section">
                <h3>Deleted Requests Received</h3>
                <?php if (!empty($deleted_requests_received)): ?>
                    <ul class="trash-list">
                        <?php foreach ($deleted_requests_received as $req): ?>
                            <li class="trash-item">
                                <div>From: <b><?php echo htmlspecialchars($req['sender_email']); ?></b></div>
                                <div>Service: <b><?php echo htmlspecialchars($req['service']); ?></b></div>
                                <div>Status: <?php echo htmlspecialchars($req['status']); ?></div>
                                <div class="trash-meta"><?php echo htmlspecialchars($req['created_at']); ?></div>
                                <div class="trash-actions">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="item_id" value="<?php echo $req['id']; ?>">
                                        <input type="hidden" name="item_type" value="request">
                                        <button type="submit" name="action" value="restore" class="restore-btn">⟲ Restore</button>
                                        <button type="submit" name="action" value="delete_permanent" class="delete-perm-btn">🗑️ Delete Permanently</button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-msg">No deleted requests received.</div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        echo ob_get_clean();
        exit();
    } else {
        header("Location: trash.php");
        exit();
    }
}

// Fetch deleted notifications
$deleted_notifications = [];
$notifRes = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($notifRes && $notifRes->num_rows > 0) {
    $notifQ = $conn->prepare("SELECT id, message, type, created_at FROM notifications WHERE user_email=? AND deleted = 1 ORDER BY created_at DESC");
    $notifQ->bind_param("s", $email);
    $notifQ->execute();
    $notifQ->bind_result($id, $msg, $type, $created_at);
    while ($notifQ->fetch()) {
        $deleted_notifications[] = ['id'=>$id, 'message'=>$msg, 'type'=>$type, 'created_at'=>$created_at];
    }
    $notifQ->close();
}

// Fetch deleted requests sent
$deleted_requests_sent = [];
$reqSentRes = $conn->query("SHOW TABLES LIKE 'requests'");
if ($reqSentRes && $reqSentRes->num_rows > 0) {
    $reqSentQ = $conn->prepare("SELECT id, receiver_email, service, status, created_at FROM requests WHERE sender_email=? AND deleted = 1 ORDER BY created_at DESC");
    $reqSentQ->bind_param("s", $email);
    $reqSentQ->execute();
    $reqSentQ->bind_result($id, $receiver_email, $service, $status, $created_at);
    while ($reqSentQ->fetch()) {
        $deleted_requests_sent[] = ['id'=>$id, 'receiver_email'=>$receiver_email, 'service'=>$service, 'status'=>$status, 'created_at'=>$created_at];
    }
    $reqSentQ->close();
}

// Fetch deleted requests received
$deleted_requests_received = [];
if ($reqSentRes && $reqSentRes->num_rows > 0) {
    $reqRecvQ = $conn->prepare("SELECT id, sender_email, service, status, created_at FROM requests WHERE receiver_email=? AND deleted = 1 ORDER BY created_at DESC");
    $reqRecvQ->bind_param("s", $email);
    $reqRecvQ->execute();
    $reqRecvQ->bind_result($req_id, $sender_email, $service, $status, $created_at);
    while ($reqRecvQ->fetch()) {
        $deleted_requests_received[] = ['id'=>$req_id, 'sender_email'=>$sender_email, 'service'=>$service, 'status'=>$status, 'created_at'=>$created_at];
    }
    $reqRecvQ->close();
}

$conn->close();

// Feedback message for modal actions
$modal_feedback = '';
if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'restore') {
            $modal_feedback = 'Item restored!';
        } elseif ($_POST['action'] === 'delete_permanent') {
            $modal_feedback = 'Item deleted permanently!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trash - Skill Sphere</title>
    <link rel="stylesheet" href="designs/header1.css">
    <link rel="stylesheet" href="designs/footer.css">
    <style>
        body { background: #f6f7f9; }
        .trash-container { max-width: 900px; margin: 40px auto 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); padding: 36px 32px; }
        h2 { color: #1B4D43; text-align: center; margin-bottom: 32px; }
        .trash-section { margin-bottom: 36px; }
        .trash-section h3 { color: #1B4D43; font-size: 1.2rem; margin-bottom: 16px; }
        ul.trash-list { list-style: none; padding: 0; margin: 0; }
        .trash-item { background: #f8f9fa; border-radius: 8px; margin-bottom: 12px; padding: 14px 12px; display: flex; flex-direction: column; gap: 6px; }
        .trash-meta { color: #888; font-size: 0.93em; }
        .trash-actions { margin-top: 4px; }
        .restore-btn, .delete-perm-btn {
            background: none;
            border: none;
            font-size: 1em;
            cursor: pointer;
            padding: 5px 18px;
            border-radius: 999px;
            margin-right: 8px;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            font-weight: 600;
            outline: none;
        }
        .restore-btn {
            color: #fff;
            background: linear-gradient(135deg, #43a047 0%, #81c784 100%);
            box-shadow: 0 2px 8px rgba(67,160,71,0.08);
        }
        .restore-btn:hover {
            background: linear-gradient(135deg, #388e3c 0%, #43a047 100%);
            color: #fff;
        }
        .delete-perm-btn {
            color: #fff;
            background: linear-gradient(135deg, #e53935 0%, #ffb733 100%);
            box-shadow: 0 2px 8px rgba(229,57,53,0.08);
        }
        .delete-perm-btn:hover {
            background: linear-gradient(135deg, #b71c1c 0%, #e53935 100%);
            color: #fff;
        }
        .modal-feedback {
            background: #e3f2fd;
            color: #1B4D43;
            border-radius: 8px;
            padding: 10px 18px;
            margin-bottom: 18px;
            text-align: center;
            font-weight: 600;
            font-size: 1.05em;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .empty-msg { color: #888; text-align: center; margin: 18px 0 0 0; font-size: 1.05rem; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="trash-container">
        <h2>Trash / History</h2>
        <?php if ($modal_feedback): ?>
            <div class="modal-feedback"><?php echo htmlspecialchars($modal_feedback); ?></div>
        <?php endif; ?>
        <div class="trash-section">
            <h3>Deleted Notifications</h3>
            <?php if (!empty($deleted_notifications)): ?>
                <ul class="trash-list">
                    <?php foreach ($deleted_notifications as $notif): ?>
                        <li class="trash-item">
                            <div><?php echo htmlspecialchars($notif['message']); ?></div>
                            <div class="trash-meta">Type: <?php echo htmlspecialchars($notif['type']); ?> | <?php echo htmlspecialchars($notif['created_at']); ?></div>
                            <div class="trash-actions">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="item_id" value="<?php echo $notif['id']; ?>">
                                    <input type="hidden" name="item_type" value="notification">
                                    <button type="submit" name="action" value="restore" class="restore-btn">⟲ Restore</button>
                                    <button type="submit" name="action" value="delete_permanent" class="delete-perm-btn">🗑️ Delete Permanently</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-msg">No deleted notifications.</div>
            <?php endif; ?>
        </div>
        <div class="trash-section">
            <h3>Deleted Requests Sent</h3>
            <?php if (!empty($deleted_requests_sent)): ?>
                <ul class="trash-list">
                    <?php foreach ($deleted_requests_sent as $req): ?>
                        <li class="trash-item">
                            <div>To: <b><?php echo htmlspecialchars($req['receiver_email']); ?></b></div>
                            <div>Service: <b><?php echo htmlspecialchars($req['service']); ?></b></div>
                            <div>Status: <?php echo htmlspecialchars($req['status']); ?></div>
                            <div class="trash-meta"><?php echo htmlspecialchars($req['created_at']); ?></div>
                            <div class="trash-actions">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="item_id" value="<?php echo $req['id']; ?>">
                                    <input type="hidden" name="item_type" value="request">
                                    <button type="submit" name="action" value="restore" class="restore-btn">⟲ Restore</button>
                                    <button type="submit" name="action" value="delete_permanent" class="delete-perm-btn">🗑️ Delete Permanently</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-msg">No deleted requests sent.</div>
            <?php endif; ?>
        </div>
        <div class="trash-section">
            <h3>Deleted Requests Received</h3>
            <?php if (!empty($deleted_requests_received)): ?>
                <ul class="trash-list">
                    <?php foreach ($deleted_requests_received as $req): ?>
                        <li class="trash-item">
                            <div>From: <b><?php echo htmlspecialchars($req['sender_email']); ?></b></div>
                            <div>Service: <b><?php echo htmlspecialchars($req['service']); ?></b></div>
                            <div>Status: <?php echo htmlspecialchars($req['status']); ?></div>
                            <div class="trash-meta"><?php echo htmlspecialchars($req['created_at']); ?></div>
                            <div class="trash-actions">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="item_id" value="<?php echo $req['id']; ?>">
                                    <input type="hidden" name="item_type" value="request">
                                    <button type="submit" name="action" value="restore" class="restore-btn">⟲ Restore</button>
                                    <button type="submit" name="action" value="delete_permanent" class="delete-perm-btn">🗑️ Delete Permanently</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-msg">No deleted requests received.</div>
            <?php endif; ?>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html> 