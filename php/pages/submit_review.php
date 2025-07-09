<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'error' => 'You must be logged in to submit a review.']);
    exit;
}

$user_email = $_SESSION['email'];
$request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
$role = isset($_POST['role']) ? $_POST['role'] : '';
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$review = isset($_POST['review_message']) ? trim($_POST['review_message']) : '';

if (!$request_id || !$role || !$rating) {
    echo json_encode(['success' => false, 'error' => 'Request ID, role, and rating are required.']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'skillsphere');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    exit;
}

if ($role === 'worker') {
    $stmt = $conn->prepare("UPDATE requests SET worker_review = ?, worker_rating = ?, status = 'Done' WHERE id = ?");
    $stmt->bind_param('sii', $review, $rating, $request_id);
} else if ($role === 'client') {
    $stmt = $conn->prepare("UPDATE requests SET client_review = ?, client_rating = ? WHERE id = ?");
    $stmt->bind_param('sii', $review, $rating, $request_id);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid role.']);
    $conn->close();
    exit;
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save review.']);
}
$stmt->close();
$conn->close();
