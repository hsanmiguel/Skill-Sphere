<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['email'])) {
    echo json_encode(['success'=>false, 'error'=>'Not logged in.']);
    exit();
}
// Required fields
$required = ['receiver_email','service','description','request_title','date_time','location','wage_amount','wage_type','contact_info'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success'=>false, 'error'=>'Missing required field: '.$field]);
        exit();
    }
}
$sender_email = $_SESSION['email'];
$receiver_email = $_POST['receiver_email'];
$service = $_POST['service'];
$request_title = $_POST['request_title'];
$date_time = $_POST['date_time'];
$location = $_POST['location'];
$wage_amount = $_POST['wage_amount'];
$wage_type = $_POST['wage_type'];
$contact_info = $_POST['contact_info'];
$message = isset($_POST['message']) ? $_POST['message'] : '';
$description = $_POST['description'];
if ($sender_email === $receiver_email) {
    echo json_encode(['success'=>false, 'error'=>'You cannot hire yourself.']);
    exit();
}
$conn = new mysqli('localhost', 'root', '', 'skillsphere');
if ($conn->connect_error) {
    echo json_encode(['success'=>false, 'error'=>'DB connection failed.']);
    exit();
}
$stmt = $conn->prepare("INSERT INTO requests (sender_email, receiver_email, service, request_title, date_time, location, wage_amount, wage_type, contact_info, message, description, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
$stmt->bind_param("ssssssdssss", $sender_email, $receiver_email, $service, $request_title, $date_time, $location, $wage_amount, $wage_type, $contact_info, $message, $description);
if ($stmt->execute()) {
    // Notify provider
    $notif_msg = "You have a new service request from $sender_email.";
    $notif = $conn->prepare("INSERT INTO notifications (user_email, message, type, created_at) VALUES (?, ?, 'info', NOW())");
    $notif->bind_param("ss", $receiver_email, $notif_msg);
    $notif->execute();
    $notif->close();
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false, 'error'=>'Failed to send request.']);
}
$stmt->close();
$conn->close();
