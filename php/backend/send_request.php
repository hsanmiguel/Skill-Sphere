<?php
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'error' => 'You must be logged in to send a request.']);
    exit;
}

// Check if it is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// Get data from POST
$sender_email = $_SESSION['email'];
$receiver_email = $_POST['receiver_email'] ?? null;
$request_title = $_POST['request_title'] ?? null;
$service = $_POST['service'] ?? null;
$date_time = $_POST['date_time'] ?? null;
$location = $_POST['location'] ?? null;
$wage_amount = $_POST['wage_amount'] ?? null;
$wage_type = $_POST['wage_type'] ?? null;
$contact_info = $_POST['contact_info'] ?? null;
$message = $_POST['message'] ?? null;

// Validate data
$required_fields = [$receiver_email, $request_title, $service, $date_time, $location, $wage_amount, $wage_type, $contact_info, $message];
if (in_array(null, $required_fields, true)) {
    echo json_encode(['success' => false, 'error' => 'All fields are required.']);
    exit;
}

if ($sender_email === $receiver_email) {
    echo json_encode(['success' => false, 'error' => 'You cannot send a hire request to yourself.']);
    exit;
}

// Database connection
$conn = new mysqli("localhost", "root", "", "skillsphere");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    exit;
}

// Insert the request into the database
$sql = "INSERT INTO requests (sender_email, receiver_email, request_title, service, date_time, location, wage_amount, wage_type, contact_info, message, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssdsss", $sender_email, $receiver_email, $request_title, $service, $date_time, $location, $wage_amount, $wage_type, $contact_info, $message);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to send request. Please try again.']);
}

$stmt->close();
$conn->close();
?>
