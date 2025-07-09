<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'notification_system.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['email'])) {
    $userEmail = $_SESSION['email'];
    $conn = new mysqli("localhost", "root", "", "skillsphere");

    $notifications = [];
    $requests_sent = [];
    $requests_received = [];

    // Fetch notifications
    $notifQ = $conn->prepare("SELECT id, message, type, created_at FROM notifications WHERE user_email=? AND deleted = 0 ORDER BY created_at DESC LIMIT 5");
    $notifQ->bind_param("s", $userEmail);
    $notifQ->execute();
    $notifQ->bind_result($id, $msg, $type, $created_at);
    while ($notifQ->fetch()) {
        $notifications[] = ['id' => $id, 'message' => $msg, 'type' => $type, 'created_at' => $created_at];
    }
    $notifQ->close();

    // Fetch requests sent
    $reqSentQ = $conn->prepare("SELECT id, receiver_email, service, status, created_at FROM requests WHERE sender_email=? AND deleted = 0 ORDER BY created_at DESC LIMIT 5");
    $reqSentQ->bind_param("s", $userEmail);
    $reqSentQ->execute();
    $reqSentQ->bind_result($id, $receiver_email, $service, $status, $created_at);
    while ($reqSentQ->fetch()) {
        $requests_sent[] = ['id' => $id, 'receiver_email' => $receiver_email, 'service' => $service, 'status' => $status, 'created_at' => $created_at];
    }
    $reqSentQ->close();

    // Fetch requests received
    $reqRecvQ = $conn->prepare("SELECT id, sender_email, service, status, created_at FROM requests WHERE receiver_email=? AND deleted = 0 ORDER BY created_at DESC LIMIT 5");
    $reqRecvQ->bind_param("s", $userEmail);
    $reqRecvQ->execute();
    $reqRecvQ->bind_result($id, $sender_email, $service, $status, $created_at);
    while ($reqRecvQ->fetch()) {
        $requests_received[] = ['id' => $id, 'sender_email' => $sender_email, 'service' => $service, 'status' => $status, 'created_at' => $created_at];
    }
    $reqRecvQ->close();

    $conn->close();

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'requests_sent' => $requests_sent,
        'requests_received' => $requests_received
    ]);
    exit();
}
