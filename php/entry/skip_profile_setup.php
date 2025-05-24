<?php
session_start();
include_once '../components/db_connect.php';
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header('Location: sign_in.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'registered_accounts');
if ($conn->connect_error) exit('DB error');

$email = $_SESSION['email'];
$user_id = $_SESSION['user_id'];
$first_name = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : '';
$last_name = isset($_SESSION['last_name']) ? $_SESSION['last_name'] : '';

// Check if profile exists
$stmt = $conn->prepare('SELECT id FROM user_profiles WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    // Create minimal profile
    $stmt->close();
    $stmt = $conn->prepare('INSERT INTO user_profiles (user_id, email, first_name, last_name) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $user_id, $email, $first_name, $last_name);
    $stmt->execute();
}
$stmt->close();
$conn->close();
header('Location: /Skill_Sphere/php/user_profile.php');
exit(); 