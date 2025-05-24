<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['email']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/user_profile.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'registered_accounts');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$first_name = $_POST['first_name'] ?? '';
$mi = $_POST['mi'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$phone_number = $_POST['phone_number'] ?? '';
$address = $_POST['address'] ?? '';
$social_media = $_POST['social_media'] ?? '';
$experience = $_POST['experience'] ?? 0;

// Update user profile
$stmt = $conn->prepare("
    UPDATE users 
    SET first_name = ?, 
        mi = ?, 
        last_name = ?, 
        phone_number = ?, 
        address = ?, 
        social_media = ?, 
        experience = ? 
    WHERE email = ?
");

$stmt->bind_param("ssssssss", 
    $first_name,
    $mi,
    $last_name,
    $phone_number,
    $address,
    $social_media,
    $experience,
    $_SESSION['email']
);

if ($stmt->execute()) {
    // Add success notification
    require_once '../components/notifications_handler.php';
    addNotification($_SESSION['email'], "Profile updated successfully!", "success");
    
    header("Location: ../pages/user_profile.php");
    exit();
} else {
    addNotification($_SESSION['email'], "Error updating profile: " . $conn->error, "error");
    header("Location: ../pages/user_profile.php");
    exit();
}

$stmt->close();
$conn->close();
?> 