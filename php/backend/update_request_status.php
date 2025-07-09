<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['email'])) {
    echo json_encode(['success'=>false, 'error'=>'Not logged in.']);
    exit();
}

if (
    isset($_POST['review_request_id']) &&
    isset($_POST['review_action']) &&
    isset($_POST['review_rating']) &&
    isset($_POST['review_text']) &&
    isset($_POST['review_role'])
) {
    $conn = new mysqli('localhost', 'root', '', 'skillsphere');
    if ($conn->connect_error) {
        echo json_encode(['success'=>false, 'error'=>'DB connection failed.']);
        exit();
    }
    $action = $_POST['review_action'];
    $rating = intval($_POST['review_rating']);
    $review = $_POST['review_text'];
    $role = $_POST['review_role'];
    $request_id = intval($_POST['review_request_id']);

    // Fetch request info
    $stmt = $conn->prepare('SELECT sender_email, receiver_email, status FROM requests WHERE id=?');
    $stmt->bind_param('i', $request_id);
    $stmt->execute();
    $stmt->bind_result($sender_email, $receiver_email, $current_status);
    $stmt->fetch();
    $stmt->close();
    $user_email = $_SESSION['email'];

    if ($role === 'worker' && $user_email === $receiver_email && strtolower($current_status) === 'accepted' && $action === 'Done') {
        $stmt = $conn->prepare('UPDATE requests SET status="Done", worker_rating=?, worker_review=? WHERE id=?');
        $stmt->bind_param('isi', $rating, $review, $request_id);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        echo json_encode(['success'=>true]);
        exit();
    } elseif ($role === 'client' && $user_email === $sender_email && strtolower($current_status) === 'done' && $action === 'Confirmed') {
        $stmt = $conn->prepare('UPDATE requests SET status="Confirmed", client_rating=?, client_review=? WHERE id=?');
        $stmt->bind_param('isi', $rating, $review, $request_id);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        echo json_encode(['success'=>true]);
        exit();
    } else {
        $conn->close();
        echo json_encode(['success'=>false, 'error'=>'Unauthorized or invalid status transition.']);
        exit();
    }
}

if (!isset($_POST['request_id']) || !isset($_POST['new_status'])) {
    echo json_encode(['success'=>false, 'error'=>'Missing parameters.']);
    exit();
}
$request_id = intval($_POST['request_id']);
$new_status = $_POST['new_status'];
$conn = new mysqli('localhost', 'root', '', 'skillsphere');
if ($conn->connect_error) {
    echo json_encode(['success'=>false, 'error'=>'DB connection failed.']);
    exit();
}
// Fetch request info
$stmt = $conn->prepare('SELECT sender_email, receiver_email, status FROM requests WHERE id=?');
$stmt->bind_param('i', $request_id);
$stmt->execute();
$stmt->bind_result($sender_email, $receiver_email, $current_status);
$stmt->fetch();
$stmt->close();
$user_email = $_SESSION['email'];
if ($receiver_email !== $user_email) {
    echo json_encode(['success'=>false, 'error'=>'Unauthorized.']);
    exit();
}
// Only allow valid transitions
$allowed = false;
if (strtolower($current_status) === 'pending' && in_array($new_status, ['Accepted','Declined'])) $allowed = true;
if (strtolower($current_status) === 'accepted' && $new_status === 'Cancelled') $allowed = true;
if (isset($_POST['sent']) && $_POST['sent'] == '1') {
    // Sender (client) cancels their own request
    if ($sender_email !== $user_email) {
        echo json_encode(['success'=>false, 'error'=>'Unauthorized.']);
        exit();
    }
    // Only allow cancel if status is Pending or Accepted
    if (!in_array(strtolower($current_status), ['pending','accepted']) || $new_status !== 'Cancelled') {
        echo json_encode(['success'=>false, 'error'=>'Invalid status transition.']);
        exit();
    }
    $stmt = $conn->prepare('UPDATE requests SET status=? WHERE id=?');
    $stmt->bind_param('si', $new_status, $request_id);
    $stmt->execute();
    $stmt->close();
    // Notify the worker (receiver)
    $notif_msg = 'A request sent to you has been cancelled by the client.';
    $stmt = $conn->prepare('INSERT INTO notifications (user_email, message, type, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->bind_param('sss', $receiver_email, $notif_msg, $new_status);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    echo json_encode(['success'=>true, 'message'=>'Request cancelled.']);
    exit();
}
if (!$allowed) {
    echo json_encode(['success'=>false, 'error'=>'Invalid status transition.']);
    exit();
}
$stmt = $conn->prepare('UPDATE requests SET status=? WHERE id=?');
$stmt->bind_param('si', $new_status, $request_id);
$stmt->execute();
$stmt->close();
// Insert notification for client
$notif_msg = '';
switch ($new_status) {
    case 'Accepted': $notif_msg = 'Your request has been accepted.'; break;
    case 'Declined': $notif_msg = 'Your request has been declined.'; break;
    case 'Cancelled': $notif_msg = 'Your accepted request has been cancelled.'; break;
}
if ($notif_msg) {
    $stmt = $conn->prepare('INSERT INTO notifications (user_email, message, type, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->bind_param('sss', $sender_email, $notif_msg, $new_status);
    $stmt->execute();
    $stmt->close();
}
$conn->close();
echo json_encode(['success'=>true, 'message'=>'Status updated.']); 