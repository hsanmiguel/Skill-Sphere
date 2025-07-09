<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: entry/sign_in.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: entry/sign_in.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "skillsphere");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- Load skills and services from DB (skills.sql, services.sql) ---
$skills = [];
$skillsRes = $conn->query("SHOW TABLES LIKE 'skills'");
if ($skillsRes && $skillsRes->num_rows > 0) {
    $q = $conn->query("SELECT name FROM skills ORDER BY name ASC");
    while ($row = $q->fetch_assoc()) {
        $skills[] = $row['name'];
    }
}

$services = [];
$servicesRes = $conn->query("SHOW TABLES LIKE 'services'");
if ($servicesRes && $servicesRes->num_rows > 0) {
    $q = $conn->query("SELECT name FROM services ORDER BY name ASC");
    while ($row = $q->fetch_assoc()) {
        $services[] = $row['name'];
    }
}

$profile_email = isset($_GET['email']) ? $_GET['email'] : $_SESSION['email'];
$user_email = $_SESSION['email'];
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Debug: output emails being compared
echo "<!-- profile_email: $profile_email | user_email: $user_email -->";

// Handle profile update
if (isset($_POST['save_profile']) && isset($_SESSION['email']) && $_SESSION['email'] === $profile_email) {
    $feedback = '';
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $birthdate = isset($_POST['birthdate']) ? $_POST['birthdate'] : null;
    $address = trim($_POST['address']);
    $phone_number = trim($_POST['phone_number']);
    // Save as comma separated values
    $skills_selected = isset($_POST['skills']) ? array_map('trim', $_POST['skills']) : [];
    $services_selected = isset($_POST['services']) ? array_map('trim', $_POST['services']) : [];
    $skills_str = implode(', ', $skills_selected);
    $services_str = implode(', ', $services_selected);
    $profile_picture_sql = '';
    $profile_picture_param = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['profile_picture']['tmp_name'];
            $fileName = basename($_FILES['profile_picture']['name']);
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($fileExt, $allowed)) {
                $newFileName = 'profile_' . md5($profile_email) . '.' . $fileExt;
                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
                $uploadPath = $uploadDir . $newFileName;
                if (move_uploaded_file($fileTmp, $uploadPath)) {
                    $profile_picture_sql = ', profile_picture=?';
                    $profile_picture_param = $newFileName;
                } else {
                    $feedback = 'Failed to upload profile picture.';
                }
            } else {
                $feedback = 'Invalid image type. Only JPG, JPEG, PNG, GIF allowed.';
            }
        } else {
            $feedback = 'Error uploading file.';
        }
    }
    if ($profile_picture_sql) {
        $stmt = $conn->prepare("UPDATE user_profiles SET first_name=?, last_name=?, birthdate=?, address=?, phone_number=?, skills=?, services=? $profile_picture_sql WHERE email=?");
        $stmt->bind_param("sssssssss", $first_name, $last_name, $birthdate, $address, $phone_number, $skills_str, $services_str, $profile_picture_param, $profile_email);
    } else {
        $stmt = $conn->prepare("UPDATE user_profiles SET first_name=?, last_name=?, birthdate=?, address=?, phone_number=?, skills=?, services=? WHERE email=?");
        $stmt->bind_param("ssssssss", $first_name, $last_name, $birthdate, $address, $phone_number, $skills_str, $services_str, $profile_email);
    }
    $stmt->execute();
    $stmt->close();
    $_SESSION['first_name'] = $first_name;
    if ($feedback) {
        $_SESSION['profile_feedback'] = $feedback;
        header("Location: user_profile.php?email=" . urlencode($profile_email));
        exit();
    } else {
        $_SESSION['profile_feedback'] = 'Profile updated successfully!';
        header("Location: user_profile.php?email=" . urlencode($profile_email));
        exit();
    }
}

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM user_profiles WHERE id = ?");
    $stmt->bind_param("i", $id);
} else {
    $stmt = $conn->prepare("SELECT * FROM user_profiles WHERE email = ?");
    $stmt->bind_param("s", $profile_email);
}
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$stmt->close();

if (!$profile) {
    echo "<h2>Profile not found.</h2>";
    // Debugging: Show the email used for the query if it fails
    echo "<!-- DEBUG: Query for profile with email '$profile_email' returned no results. -->";
    exit();
}
// Debugging: Print the value of profile_picture to an HTML comment
echo "<!-- DEBUG: Raw profile_picture value from DB: '" . (isset($profile['profile_picture']) ? $profile['profile_picture'] : 'Not Set') . "' -->";

$profile_picture_filename = !empty($profile['profile_picture']) ? htmlspecialchars($profile['profile_picture']) : '';

$isOwner = (isset($_SESSION['email']) && $profile['email'] === $_SESSION['email']);
$editing = ($isOwner && isset($_GET['edit']));

// Handle Accept/Decline actions for received requests
if (isset($_POST['request_action']) && isset($_POST['request_id']) && $isOwner) {
    $action = $_POST['request_action'];
    $request_id = intval($_POST['request_id']);
    $new_status = ($action === 'accept') ? 'Accepted' : 'Declined';
    
    $stmt = $conn->prepare("UPDATE requests SET status=? WHERE id=?");
    $stmt->bind_param("si", $new_status, $request_id);
    $stmt->execute();
    $stmt->close();
    
    // Notify sender with personalized message
    $senderQ = $conn->prepare("SELECT sender_email, service FROM requests WHERE id=?");
    $senderQ->bind_param("i", $request_id);
    $senderQ->execute();
    $senderQ->bind_result($sender_email_notify, $service_name_notify);
    $senderQ->fetch();
    $senderQ->close();
    
    $notif = $conn->prepare("INSERT INTO notifications (user_email, message, type) VALUES (?, ?, ?)");
    if ($new_status === 'Accepted') {
        $msg = "Your request for '$service_name_notify' was accepted!";
        $type = 'success';
    } else {
        $msg = "Your request for '$service_name_notify' was declined.";
        $type = 'error';
    }
    $notif->bind_param("sss", $sender_email_notify, $msg, $type);
    $notif->execute();
    $notif->close();
    
    header("Location: user_profile.php?email=".urlencode($profile_email));
    exit();
}

// Handle delete/restore actions
if (isset($_POST['action']) && isset($_POST['item_id']) && isset($_POST['item_type']) && $isOwner) {
    $item_id = intval($_POST['item_id']);
    $item_type = $_POST['item_type'];
    $action = $_POST['action'];
    
    if ($item_type === 'notification') {
        $stmt = $conn->prepare("UPDATE notifications SET deleted = ? WHERE id = ?");
        $deleted = ($action === 'delete') ? 1 : 0;
        $stmt->bind_param("ii", $deleted, $item_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($item_type === 'request') {
        $stmt = $conn->prepare("UPDATE requests SET deleted = ? WHERE id = ?");
        $deleted = ($action === 'delete') ? 1 : 0;
        $stmt->bind_param("ii", $deleted, $item_id);
        $stmt->execute();
        $stmt->close();
    }
    
    header("Location: user_profile.php?email=".urlencode($profile_email));
    exit();
}

// Notifications
$notifications = [];
$notifRes = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($notifRes && $notifRes->num_rows > 0) {
    $notifQ = $conn->prepare("SELECT id, message, type, created_at FROM notifications WHERE user_email=? AND deleted = 0 ORDER BY created_at DESC LIMIT 5");
    $notifQ->bind_param("s", $user_email);
    $notifQ->execute();
    $notifQ->bind_result($id, $msg, $type, $created_at);
    while ($notifQ->fetch()) {
        $notifications[] = ['id'=>$id, 'message'=>$msg, 'type'=>$type, 'created_at'=>$created_at];
    }
    $notifQ->close();
}

// Fetch deleted notifications
$deleted_notifications = [];
if ($notifRes && $notifRes->num_rows > 0) {
    $notifQ = $conn->prepare("SELECT id, message, type, created_at FROM notifications WHERE user_email=? AND deleted = 1 ORDER BY created_at DESC LIMIT 10");
    $notifQ->bind_param("s", $user_email);
    $notifQ->execute();
    $notifQ->bind_result($id, $msg, $type, $created_at);
    while ($notifQ->fetch()) {
        $deleted_notifications[] = ['id'=>$id, 'message'=>$msg, 'type'=>$type, 'created_at'=>$created_at];
    }
    $notifQ->close();
}

// Requests Sent
$requests_sent = [];
$reqSentRes = $conn->query("SHOW TABLES LIKE 'requests'");
if ($reqSentRes && $reqSentRes->num_rows > 0) {

// Expanded query to fetch all new fields
$reqSentQ = $conn->prepare("SELECT id, receiver_email, service, status, created_at, request_title, date_time, location, wage_amount, wage_type, contact_info, message, worker_review, worker_rating, client_review, client_rating FROM requests WHERE sender_email=? AND deleted = 0 ORDER BY created_at DESC LIMIT 5");
$reqSentQ->bind_param("s", $user_email);
$reqSentQ->execute();
$reqSentQ->bind_result($id, $receiver_email, $service, $status, $created_at, $request_title, $date_time, $location, $wage_amount, $wage_type, $contact_info, $message, $worker_review, $worker_rating, $client_review, $client_rating);
while ($reqSentQ->fetch()) {
    $requests_sent[] = [
        'id'=>$id,
        'receiver_email'=>$receiver_email,
        'service'=>$service,
        'status'=>$status,
        'created_at'=>$created_at,
        'request_title'=>$request_title,
        'date_time'=>$date_time,
        'location'=>$location,
        'wage_amount'=>$wage_amount,
        'wage_type'=>$wage_type,
        'contact_info'=>$contact_info,
        'message'=>$message,
        'worker_review'=>$worker_review,
        'worker_rating'=>$worker_rating,
        'client_review'=>$client_review,
        'client_rating'=>$client_rating
    ];
}
$reqSentQ->close();
}

// Fetch deleted requests sent
$deleted_requests_sent = [];
if ($reqSentRes && $reqSentRes->num_rows > 0) {
    $reqSentQ = $conn->prepare("SELECT id, receiver_email, service, status, created_at FROM requests WHERE sender_email=? AND deleted = 1 ORDER BY created_at DESC LIMIT 10");
    $reqSentQ->bind_param("s", $user_email);
    $reqSentQ->execute();
    $reqSentQ->bind_result($id, $receiver_email, $service, $status, $created_at);
    while ($reqSentQ->fetch()) {
        $deleted_requests_sent[] = ['id'=>$id, 'receiver_email'=>$receiver_email, 'service'=>$service, 'status'=>$status, 'created_at'=>$created_at];
    }
    $reqSentQ->close();
}

// Requests Received
$requests_received = [];
if ($reqSentRes && $reqSentRes->num_rows > 0) {

// Expanded query to fetch all new fields
$reqRecvQ = $conn->prepare("SELECT id, sender_email, service, status, created_at, request_title, date_time, location, wage_amount, wage_type, contact_info, message, worker_review, worker_rating, client_review, client_rating FROM requests WHERE receiver_email=? AND deleted = 0 ORDER BY created_at DESC LIMIT 5");
$reqRecvQ->bind_param("s", $user_email);
$reqRecvQ->execute();
$reqRecvQ->bind_result($req_id, $sender_email, $service, $status, $created_at, $request_title, $date_time, $location, $wage_amount, $wage_type, $contact_info, $message, $worker_review, $worker_rating, $client_review, $client_rating);
while ($reqRecvQ->fetch()) {
    $requests_received[] = [
        'id'=>$req_id,
        'sender_email'=>$sender_email,
        'service'=>$service,
        'status'=>$status,
        'created_at'=>$created_at,
        'request_title'=>$request_title,
        'date_time'=>$date_time,
        'location'=>$location,
        'wage_amount'=>$wage_amount,
        'wage_type'=>$wage_type,
        'contact_info'=>$contact_info,
        'message'=>$message,
        'worker_review'=>$worker_review,
        'worker_rating'=>$worker_rating,
        'client_review'=>$client_review,
        'client_rating'=>$client_rating
    ];
}
$reqRecvQ->close();
}

// Fetch deleted requests received
$deleted_requests_received = [];
if ($reqSentRes && $reqSentRes->num_rows > 0) {
    $reqRecvQ = $conn->prepare("SELECT id, sender_email, service, status, created_at FROM requests WHERE receiver_email=? AND deleted = 1 ORDER BY created_at DESC LIMIT 10");
    $reqRecvQ->bind_param("s", $user_email);
    $reqRecvQ->execute();
    $reqRecvQ->bind_result($req_id, $sender_email, $service, $status, $created_at);
    while ($reqRecvQ->fetch()) {
        $deleted_requests_received[] = ['id'=>$req_id, 'sender_email'=>$sender_email, 'service'=>$service, 'status'=>$status, 'created_at'=>$created_at];
    }
    $reqRecvQ->close();
}

// Close the database connection at the very end
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Skill Sphere</title>
    <link rel="stylesheet" href="../php/designs/header1.css">
    <style>
    /* --- View Details Button --- */
    .request-btn.details-btn {
        background: linear-gradient(90deg, #4caf50 0%, #2196f3 100%);
        color: #fff;
        border: none;
        border-radius: 22px;
        font-size: 1em;
        font-weight: 600;
        padding: 8px 22px;
        margin-top: 8px;
        cursor: pointer;
        box-shadow: 0 2px 10px #0001;
        transition: background 0.2s, transform 0.13s;
        outline: none;
    }
    .request-btn.details-btn:hover {
        background: linear-gradient(90deg, #2196f3 0%, #4caf50 100%);
        transform: translateY(-2px) scale(1.04);
        box-shadow: 0 4px 16px #2196f344;
    }
    /* --- Details Panel --- */
    #requestDetailsPanel {
        display: none;
        position: fixed;
        top: 90px;
        right: 40px;
        width: 420px;
        max-width: 95vw;
        background: #fff;
        border: 2px solid #2196f3;
        border-radius: 16px;
        box-shadow: 0 8px 32px #2196f355;
        padding: 36px 32px 32px 32px;
        z-index: 9999;
        min-height: 320px;
        animation: detailsPanelIn 0.23s cubic-bezier(.4,1.6,.4,1) 1;
        font-family: 'Segoe UI', Arial, sans-serif;
    }
    @keyframes detailsPanelIn {
        from { opacity: 0; transform: translateX(40px) scale(0.97); }
        to   { opacity: 1; transform: translateX(0) scale(1); }
    }
    #requestDetailsPanel h2 {
        font-size: 1.35em;
        margin-bottom: 12px;
        color: #1976d2;
        font-weight: 700;
    }
    #requestDetailsPanel .close {
        float: right;
        cursor: pointer;
        font-size: 2.1em;
        color: #888;
        transition: color 0.18s, transform 0.13s;
        margin-top: -18px;
        margin-right: -12px;
        font-weight: 400;
    }
    #requestDetailsPanel .close:hover {
        color: #1976d2;
        transform: scale(1.18) rotate(8deg);
    }
    #requestDetailsContent {
        margin-top: 12px;
    }
    #requestDetailsPanel b {
        color: #222;
    }
    #requestDetailsPanel .req-status {
        padding: 2px 10px;
        border-radius: 10px;
        font-size: 0.98em;
        font-weight: 600;
        margin-left: 4px;
        background: #e3f2fd;
        color: #1976d2;
    }
    #requestDetailsPanel .req-rating {
        color: #ff9800;
        font-weight: 700;
        margin-left: 6px;
    }
    #requestDetailsPanel .req-feedback {
        background: #f8f9fa;
        border-left: 4px solid #2196f3;
        margin: 8px 0 8px 0;
        padding: 7px 12px;
        border-radius: 7px;
        font-size: 0.98em;
    }
    #requestDetailsPanel .action-btns {
        margin-top: 22px;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    #requestDetailsPanel .request-btn {
        border-radius: 18px;
        padding: 7px 20px;
        font-size: 1em;
        font-weight: 600;
        border: none;
        outline: none;
        cursor: pointer;
        box-shadow: 0 2px 10px #0001;
        transition: background 0.18s, transform 0.13s;
    }
    #requestDetailsPanel .accept-btn { background: linear-gradient(90deg,#43e97b,#38f9d7); color:#222; }
    #requestDetailsPanel .accept-btn:hover { background: linear-gradient(90deg,#38f9d7,#43e97b); }
    #requestDetailsPanel .decline-btn { background: linear-gradient(90deg,#f85032,#e73827); color:#fff; }
    #requestDetailsPanel .decline-btn:hover { background: linear-gradient(90deg,#e73827,#f85032); }
    #requestDetailsPanel .done-btn { background: linear-gradient(90deg,#ffd200,#f7971e); color:#222; }
    #requestDetailsPanel .done-btn:hover { background: linear-gradient(90deg,#f7971e,#ffd200); }
    #requestDetailsPanel .confirm-btn { background: linear-gradient(90deg,#2196f3,#4caf50); color:#fff; }
    #requestDetailsPanel .confirm-btn:hover { background: linear-gradient(90deg,#4caf50,#2196f3); }
    </style>
    <link rel="stylesheet" href="../php/designs/user_profile1.css">
    <link rel="stylesheet" href="../php/designs/footer.css">
    <!-- Modern UI: Google Fonts & Material Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
    body, html {
        font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
        background: #f4f6fb;
        color: #222;
        margin: 0;
        padding: 0;
    }
    #profile-container {
        max-width: 1200px; /* Increased width to match the desired layout */
        margin: 100px auto 40px auto; /* Added bottom margin */
        background: #fff;
        border-radius: 24px;
        box-shadow: 0 6px 32px rgba(30,40,90,0.10), 0 1.5px 6px rgba(30,40,90,0.04);
        padding: 0 0 32px 0;
    }
    #profile-header {
        display: flex;
        align-items: center;
        gap: 36px;
        padding: 48px 48px 24px 48px;
        background: linear-gradient(90deg, #e3f2fd 0%, #f1f8e9 100%);
        border-bottom: 1.5px solid #e0e7ef;
        position: relative;
    }
    .profile-avatar {
        position: relative;
        width: 140px;
        height: 140px;
        border-radius: 50%;
        overflow: hidden;
        box-shadow: 0 4px 24px rgba(30,40,90,0.10);
        border: 4px solid #fff;
        background: #f6f7f9;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        display: block;
    }
    .profile-main-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 10px;
        min-width: 0;
    }
    .profile-name {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1B4D43;
        margin: 0 0 6px 0;
        letter-spacing: 0.5px;
        line-height: 1.1;
        word-break: break-word;
    }
    .profile-email {
        font-size: 1.08rem;
        color: #4a4a4a;
        background: #f8fafc;
        border-radius: 8px;
        padding: 4px 14px;
        display: inline-block;
        font-weight: 500;
        margin-bottom: 0;
        margin-top: 0;
        letter-spacing: 0.1px;
    }
    .profile-actions {
        display: flex;
        flex-direction: column;
        gap: 12px;
        position: absolute;
        right: 48px;
        top: 50%;
        transform: translateY(-50%);
        width: 220px;
        align-items: stretch;
        justify-content: center;
    }
    .edit-profile-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        color: #1B4D43;
        border: 2px solid #1B4D43;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1.07em;
        padding: 10px 0;
        width: 100%;
        box-shadow: none;
        margin: 0;
        transition: background 0.18s, color 0.18s, border 0.18s;
        cursor: pointer;
        gap: 8px;
    }
    .edit-profile-btn.edit-btn {
        background: linear-gradient(135deg, #1B4D43 0%, #4CAF50 100%);
        color: #fff;
        border: none;
    }
    .edit-profile-btn.trash-btn {
        background: linear-gradient(135deg, #ff9800 0%, #ffd54f 100%);
        color: #fff;
        border: none;
    }
    .edit-profile-btn.history-btn {
        background: linear-gradient(135deg, #90caf9 0%, #b3e5fc 100%);
        color: #1B4D43;
        border: none;
    }
    .edit-profile-btn:hover {
        filter: brightness(0.97);
        box-shadow: 0 2px 8px rgba(30,40,90,0.08);
    }
        border-radius: 999px;
        padding: 7px 28px;
        font-weight: 600;
        cursor: pointer;
        font-size: 1.05em;
        transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        min-width: 140px;
        min-height: 40px;
        box-sizing: border-box;
        gap: 8px;
        text-decoration: none;
        box-shadow: 0 2px 8px rgba(30,40,90,0.07);
    }
    .edit-profile-btn.edit-btn {
        background: linear-gradient(135deg, #1B4D43 0%, #4CAF50 100%);
        color: #fff;
    }
    .edit-profile-btn.edit-btn:hover {
        background: linear-gradient(135deg, #388e3c 0%, #1B4D43 100%);
        color: #fff;
    }

    .edit-profile-btn.history-btn {
        background: linear-gradient(135deg, #90caf9 0%, #e3f2fd 100%);
        color: #1B4D43;
    }
    .edit-profile-btn.history-btn:hover {
        background: linear-gradient(135deg, #e3f2fd 0%, #90caf9 100%);
        color: #1B4D43;
    }
    .edit-profile-btn.hire-btn {
        background: linear-gradient(135deg, #ff9800 0%, #ffd54f 100%);
        color: #fff;
        font-weight: 700;
    }
    .edit-profile-btn.hire-btn:hover {
        background: linear-gradient(135deg, #ffa726 0%, #ff9800 100%);
        color: #fff;
    }
    #profile-dashboard {
        padding: 36px 48px;
        display: flex; /* Use flexbox for centering */
        justify-content: center; /* Center the inner container */
    }
    .dashboard-inner {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: center;
    }
    #profile-content {
        padding: 36px 48px 0 48px;
        display: flex;
        flex-wrap: wrap;
        gap: 48px;
        justify-content: flex-start;
        align-items: flex-start;
    }
    .profile-section {
        flex: 1 1 calc(50% - 24px); /* Create a 2-column layout */
        min-width: 300px;
        background: #f8fafc;
        border-radius: 16px;
        box-shadow: 0 1.5px 8px rgba(30,40,90,0.04);
        padding: 28px 28px 18px 28px;
        margin-bottom: 0;
        margin-right: 0;
        margin-top: 0;
    }
    .profile-section h3 {
        margin-top: 0;
        margin-bottom: 18px;
        color: #1B4D43;
        font-size: 1.18rem;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    .profile-section .info-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
        font-size: 1.05em;
    }
    .profile-section .info-label {
        color: #888;
        min-width: 90px;
        font-weight: 500;
    }
    .profile-section .info-value {
        color: #222;
        font-weight: 500;
        word-break: break-word;
    }
    .profile-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 0;
        margin-top: 0;
    }
    .chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #e3f2fd;
        color: #1B4D43;
        border-radius: 999px;
        padding: 6px 16px;
        font-size: 1.01em;
        font-weight: 500;
        box-shadow: 0 1px 4px rgba(30,40,90,0.04);
        margin-bottom: 6px;
        transition: background 0.18s, color 0.18s;
        user-select: none;
    }
    .chip.service {
        background: #f1f8e9;
        color: #388e3c;
    }
    @media (max-width: 900px) {
        #profile-container {
            max-width: 99vw;
            border-radius: 0;
            box-shadow: none;
        }
        #profile-header, #profile-content {
            padding-left: 18px;
            padding-right: 18px;
        }
        #profile-content {
            gap: 24px;
        }
    }
    @media (max-width: 700px) {
        #profile-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 18px;
            padding: 32px 10px 18px 10px;
        }
        #profile-content {
            flex-direction: column;
            gap: 18px;
            padding: 24px 10px 0 10px;
        }
        .profile-section {
            padding: 18px 10px 12px 10px;
        }
    }
    /* Modal for Hire Now */
    .modal-overlay.hire-modal {
        display: none;
        position: fixed;
        z-index: 99999;
        left: 0; top: 0; right: 0; bottom: 0;
        background: rgba(30,40,90,0.18);
        align-items: center;
        justify-content: center;
    }
    .modal-overlay.hire-modal[style*="display: flex"] {
        display: flex !important;
    }
    .modal-content.hire-modal-content {
        margin: auto;
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 4px 32px rgba(30,40,90,0.18);
        padding: 32px 32px 24px 32px;
        max-width: 420px;
        width: 95vw;
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: stretch;
    }
    .modal-close.hire-modal-close {
        position: absolute;
        top: 12px;
        right: 18px;
        background: none;
        border: none;
        font-size: 2em;
        color: #888;
        cursor: pointer;
        z-index: 2;
    }
    .hire-modal-title {
        font-size: 1.3em;
        font-weight: 700;
        color: #1B4D43;
        margin-bottom: 18px;
        text-align: center;
    }
    .hire-modal-label {
        font-weight: 600;
        margin-bottom: 6px;
        color: #222;
    }
    .hire-modal-textarea {
        width: 100%;
        min-height: 80px;
        border-radius: 8px;
        border: 1.2px solid #bbb;
        padding: 10px;
        font-size: 1.05em;
        margin-bottom: 18px;
        resize: vertical;
        background: #f8fafc;
    }
    .hire-modal-btns {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 8px;
    }
    .hire-modal-btn {
        background: linear-gradient(135deg, #1B4D43 0%, #4CAF50 100%);
        color: #fff;
        border: none;
        border-radius: 999px;
        padding: 7px 28px;
        font-weight: 600;
        cursor: pointer;
        font-size: 1.05em;
        transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        min-width: 120px;
        min-height: 40px;
        box-sizing: border-box;
        gap: 8px;
        text-decoration: none;
        box-shadow: 0 2px 8px rgba(30,40,90,0.07);
    }
    .hire-modal-btn.cancel {
        background: #eee;
        color: #222;
        border: 1px solid #bbb;
    }
    .hire-modal-btn.cancel:hover {
        background: #f8fafc;
        color: #1B4D43;
    }
    .hire-modal-btn.submit {
        background: linear-gradient(135deg, #ff9800 0%, #ffd54f 100%);
        color: #fff;
        font-weight: 700;
    }
    .hire-modal-btn.submit:hover {
        background: linear-gradient(135deg, #ffa726 0%, #ff9800 100%);
        color: #fff;
    }
    .hire-modal-success {
        color: #388e3c;
        font-weight: 600;
        margin-top: 10px;
        text-align: center;
    }
    .hire-modal-error {
        color: #d32f2f;
        font-weight: 600;
        margin-top: 10px;
        text-align: center;
    }
    /* --- Universal Modal for HIRE NOW --- */
    .hire-modal-overlay {
        display: none;
        position: fixed;
        z-index: 99999;
        left: 0; top: 0; right: 0; bottom: 0;
        background: rgba(30,40,90,0.18);
        align-items: center;
        justify-content: center;
    }
    .hire-modal-overlay.active {
        display: flex !important;
    }
    .hire-modal-content {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 4px 32px rgba(30,40,90,0.18);
        padding: 32px 32px 24px 32px;
        max-width: 420px;
        width: 95vw;
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: stretch;
        margin: auto;
    }
    .hire-modal-close {
        position: absolute;
        top: 12px;
        right: 18px;
        background: none;
        border: none;
        font-size: 2em;
        color: #888;
        cursor: pointer;
        z-index: 2;
    }
    .hire-modal-btns {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 8px;
    }
    .hire-modal-btn {
        background: linear-gradient(135deg, #1B4D43 0%, #4CAF50 100%);
        color: #fff;
        border: none;
        border-radius: 999px;
        padding: 7px 28px;
        font-weight: 600;
        cursor: pointer;
        font-size: 1.05em;
        transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        min-width: 120px;
        min-height: 40px;
        box-sizing: border-box;
        gap: 8px;
        text-decoration: none;
        box-shadow: 0 2px 8px rgba(30,40,90,0.07);
    }
    .hire-modal-btn.cancel {
        background: #eee;
        color: #222;
        border: 1px solid #bbb;
    }
    .hire-modal-btn.cancel:hover {
        background: #f8fafc;
        color: #1B4D43;
    }
    .hire-modal-btn.submit {
        background: linear-gradient(135deg, #ff9800 0%, #ffd54f 100%);
        color: #fff;
        font-weight: 700;
    }
    .hire-modal-btn.submit:hover {
        background: linear-gradient(135deg, #ffa726 0%, #ff9800 100%);
        color: #fff;
    }
    .hire-modal-success {
        color: #388e3c;
        font-weight: 600;
        margin-top: 10px;
        text-align: center;
    }
    .hire-modal-error {
        color: #d32f2f;
        font-weight: 600;
        margin-top: 10px;
        text-align: center;
    }

    /* --- Edit Profile Modal Styles --- */
    .edit-profile-modal-overlay {
        display: none;
        position: fixed;
        z-index: 99999;
        left: 0; top: 0; right: 0; bottom: 0;
        background: rgba(30,40,90,0.18);
        align-items: center;
        justify-content: center;
    }
    .edit-profile-modal-overlay.active {
        display: flex !important;
    }
    .edit-profile-modal-content {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 4px 32px rgba(30,40,90,0.18);
        padding: 32px 32px 24px 32px;
        max-width: 520px;
        width: 95vw;
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: stretch;
        margin: auto;
        animation: fadeInPop 0.25s;
    }
    .edit-profile-modal-close {
        position: absolute;
        top: 12px;
        right: 18px;
        background: none;
        border: none;
        font-size: 2em;
        color: #888;
        cursor: pointer;
        z-index: 2;
    }

    /* --- Dashboard Styles --- */
    #profile-dashboard {
        max-width: 1400px; /* Further increased width */
        margin: 0 auto;
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        background: none; /* Removed extra background */
        box-shadow: none; /* Removed extra box shadow */
    }
    .dashboard-inner {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 18px;
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
    }
    .dashboard-col {
        flex: 1 1 520px;
        min-width: 420px;
        max-width: 600px;
        margin: 0 8px;
    }
    .card.sent-card, .card.recv-card {
        width: 100%;
        min-width: 420px;
        max-width: 600px;
        margin: 0 auto;
    }
    .card {
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        background: #fff; /* Ensured cards have a clean background */
    }
    </style>
</head>
<body>
    <?php include '../components/header.php'; ?>
    <div id="profile-container">
        <div id="profile-header">
            <div class="profile-avatar">
                <img src="/Skill-SphereUpdated/php/uploads/<?php echo $profile_picture_filename; ?>" 
     alt="Profile Picture" 
     onerror="this.onerror=null; this.src='../assets/logo_ss.png';">
            </div>
            <div class="profile-main-info">
                <div class="profile-name">
                    <?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?>
                </div>
                <div class="profile-email">
                    <span class="material-icons" style="font-size:1.1em;vertical-align:middle;margin-right:4px;color:#1B4D43;">mail</span>
                    <?php echo htmlspecialchars($profile['email']); ?>
                </div>
                <div class="profile-actions">
                    <?php if ($isOwner): ?>
                        <button type="button" class="edit-profile-btn edit-btn" id="openEditProfileModal">
                            <span class="material-icons" style="font-size:1.2em;">edit</span> Edit Profile
                        </button>
                        <button type="button" class="edit-profile-btn trash-btn" id="openTrashModal">
                            <span class="material-icons" style="font-size:1.2em;">delete</span> Trash / History
                        </button>
                        <button type="button" class="edit-profile-btn history-btn" id="openHistoryModal">
                            <span class="material-icons" style="font-size:1.2em;">history</span> Full History
                        </button>
                    <?php elseif (isset($_SESSION['email']) && $_SESSION['email'] !== $profile['email']): ?>
                        <button type="button" class="edit-profile-btn hire-btn open-hire-modal" id="openHireModal">
                            <span class="material-icons" style="font-size:1.2em;">work</span> HIRE NOW!
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div id="profile-content">
            <?php if ($editing): ?>
            <form method="post" enctype="multipart/form-data" style="width:100%;max-width:600px;">
                <label>Profile Picture:<br>
                    <input type="file" name="profile_picture" accept="image/*">
                </label><br><br>
                <label>First Name:<input type="text" name="first_name" value="<?php echo htmlspecialchars($profile['first_name']); ?>" required></label><br><br>
                <label>Last Name:<input type="text" name="last_name" value="<?php echo htmlspecialchars($profile['last_name']); ?>" required></label><br><br>
                <label>Birthdate:<input type="date" name="birthdate" value="<?php echo htmlspecialchars($profile['birthdate']); ?>" required></label><br><br>
                <label>Address:<input type="text" name="address" value="<?php echo htmlspecialchars($profile['address']); ?>" required></label><br><br>
                <label>Phone Number:<input type="text" name="phone_number" value="<?php echo htmlspecialchars($profile['phone_number']); ?>" required></label><br><br>
                <?php
                $user_skills = array_map('trim', explode(',', $profile['skills']));
                $user_services = array_map('trim', explode(',', $profile['services']));
                ?>
                <label>Skills:
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; max-height: 160px; overflow-y: auto; border: 1px solid #bbb; border-radius: 8px; padding: 10px; background: #f8f9fa;">
                        <?php foreach ($skills as $skill): ?>
                            <label style="min-width: 180px; display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                                <input type="checkbox" name="skills[]" value="<?php echo htmlspecialchars($skill); ?>" <?php if (in_array($skill, $user_skills)) echo 'checked'; ?>>
                                <span><?php echo htmlspecialchars($skill); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </label><br>
                <label style="font-weight:600;color:#1B4D43;">Services:
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; max-height: 160px; overflow-y: auto; border: 1px solid #bbb; border-radius: 8px; padding: 10px; background: #fff;">
                        <?php foreach ($services as $service): ?>
                            <label style="min-width: 180px; display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                                <input type="checkbox" name="services[]" value="<?php echo htmlspecialchars($service); ?>" <?php if (in_array($service, $user_services)) echo 'checked'; ?>>
                                <span><?php echo htmlspecialchars($service); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </label><br>
                <button type="submit" name="save_profile" class="edit-profile-btn">Save</button>
                <a href="../backend/user_profile.php?email=<?php echo urlencode($profile_email); ?>" style="margin-left:10px;">Cancel</a>
            </form>
            <?php else: ?>
            <div class="profile-section">
                <h3><span class="material-icons" style="font-size:1.1em;vertical-align:middle;margin-right:4px;">info</span> Contact Information</h3>
                <div class="info-row">
                    <span class="info-label"><span class="material-icons" style="font-size:1em;vertical-align:middle;">cake</span> Birthdate:</span>
                    <span class="info-value"><?php echo isset($profile['birthdate']) ? htmlspecialchars($profile['birthdate']) : 'N/A'; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><span class="material-icons" style="font-size:1em;vertical-align:middle;">location_on</span> Address:</span>
                    <span class="info-value"><?php echo htmlspecialchars($profile['address']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><span class="material-icons" style="font-size:1em;vertical-align:middle;">call</span> Phone:</span>
                    <span class="info-value"><?php echo htmlspecialchars($profile['phone_number']); ?></span>
                </div>
                <?php if (!empty($profile['social_media'])) : ?>
                <div class="info-row">
                    <span class="info-label"><span class="material-icons" style="font-size:1em;vertical-align:middle;">public</span> Social:</span>
                    <span class="info-value"><a href="<?php echo htmlspecialchars($profile['social_media']); ?>" target="_blank"><?php echo htmlspecialchars($profile['social_media']); ?></a></span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label"><span class="material-icons" style="font-size:1em;vertical-align:middle;">work_history</span> Experience:</span>
                    <span class="info-value"><?php echo isset($profile['experience']) ? htmlspecialchars($profile['experience']) . ' years' : 'N/A'; ?></span>
                </div>
            </div>
            <div class="profile-section">
                <h3><span class="material-icons" style="font-size:1.1em;vertical-align:middle;margin-right:4px;">star</span> Skills</h3>
                <div class="profile-chips">
                <?php
                    $user_skills = array_map('trim', explode(',', $profile['skills']));
                    foreach ($user_skills as $skill) {
                        $skill = trim($skill);
                        if ($skill === '') continue;
                        $emoji = isset($skills_emojis[$skill]) ? $skills_emojis[$skill] : '';
                        echo '<span class="chip">'.htmlspecialchars($emoji . ' ' . $skill).'</span>';
                    }
                ?>
                </div>
                <h3 style="margin-top:28px;"><span class="material-icons" style="font-size:1.1em;vertical-align:middle;margin-right:4px;">handshake</span> Services</h3>
                <div class="profile-chips">
                <?php
                    $user_services = array_map('trim', explode(',', $profile['services']));
                    foreach ($user_services as $service) {
                        $service = trim($service);
                        if ($service === '') continue;
                        $emoji = isset($services_emojis[$service]) ? $services_emojis[$service] : '';
                        echo '<span class="chip service">'.htmlspecialchars($emoji . ' ' . $service).'</span>';
                    }
                ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php if ($isOwner): ?>
        <div id="profile-dashboard">
            <div class="dashboard-inner">
        
                <div class="dashboard-col">
                    <div class="card sent-card" style="background: #f1f8e9;">
                        <h3>Requests Sent</h3>
                        <?php if (!empty($requests_sent)): ?>
                            <ul class="request-list">
                        <?php foreach ($requests_sent as $req): ?>
    <li style="display: flex; align-items: flex-start; gap: 12px;">
    <div class="avatar" style="width:36px;height:36px;border-radius:50%;background:#1B4D43;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1em;">
        <?php echo strtoupper(substr($req['receiver_email'],0,1)); ?>
    </div>
    <div style="flex:1;">
        <span class="req-to">To: <b><?php echo htmlspecialchars($req['receiver_email']); ?></b></span><br>
        <span class="req-service">Service: <b><?php echo htmlspecialchars($req['service']); ?></b></span><br>
        <span class="req-title">Title: <b><?php echo htmlspecialchars($req['request_title']); ?></b></span><br>
        <span class="req-status <?php echo strtolower($req['status']); ?>">Status: <?php echo htmlspecialchars($req['status']); ?></span><br>
        <button class="request-btn details-btn" onclick='showRequestDetails(<?php echo json_encode($req, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>, "sent")'>View Details</button>
    </div>
</li>
<?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="empty-msg">No requests sent yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Details Panel -->
                <div id="requestDetailsPanel">
    <span class="close" onclick="closeRequestDetails()">&times;</span>
    <div id="requestDetailsContent"></div>
</div>

                <div class="dashboard-col">
                    <div class="card recv-card" style="background: #fffde7;">
                        <h3>Requests Received</h3>
                        <?php if (!empty($requests_received)): ?>
                            <ul class="request-list">
                        <?php foreach ($requests_received as $req): ?>
    <li style="display: flex; align-items: flex-start; gap: 12px;">
    <div class="avatar" style="width:36px;height:36px;border-radius:50%;background:#ffa500;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1em;">
        <?php echo strtoupper(substr($req['sender_email'],0,1)); ?>
    </div>
    <div style="flex:1;">
        <span class="req-from">From: <b><?php echo htmlspecialchars($req['sender_email']); ?></b></span><br>
        <span class="req-service">Service: <b><?php echo htmlspecialchars($req['service']); ?></b></span><br>
        <span class="req-title">Title: <b><?php echo htmlspecialchars($req['request_title']); ?></b></span><br>
        <span class="req-status <?php echo strtolower($req['status']); ?>">Status: <?php echo htmlspecialchars($req['status']); ?></span><br>
        <button class="request-btn details-btn" onclick='showRequestDetails(<?php echo json_encode($req, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>, "received")'>View Details</button>
    </div>
</li>
<?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="empty-msg">No requests received yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div style="clear:both;"></div>
    <?php include '../components/footer.php'; ?>
    <!-- Trash Modal -->
    <div id="trashModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <button class="modal-close" id="closeTrashModal">&times;</button>
            <div id="trashModalBody">Loading...</div>
        </div>
    </div>
    <!-- History Modal -->
    <div id="historyModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <button class="modal-close" id="closeHistoryModal">&times;</button>
            <div id="historyModalBody">Loading...</div>
        </div>
    </div>
    <!-- Edit Profile Modal (centered popup) -->
    <div id="editProfileModal" class="edit-profile-modal-overlay">
        <div class="edit-profile-modal-content">
            <button class="edit-profile-modal-close" id="closeEditProfileModal">&times;</button>
            <div id="editProfileModalBody">Loading...</div>
        </div>
    </div>
    <!-- Hire Now Modal -->
    <div id="hireNowModal" class="hire-modal-overlay">
        <div class="hire-modal-content">
            <button class="modal-close hire-modal-close" id="closeHireNowModal">&times;</button>
            <div class="hire-modal-title">Send Job Request</div>
            <form id="hireNowForm" method="post" autocomplete="off">
                <label class="hire-modal-label" for="hireNowService">Service</label>
                <select id="hireNowService" name="service" required style="margin-bottom:12px;width:100%;padding:7px 10px;border-radius:7px;border:1.2px solid #bbb;font-size:1.05em;background:#f8fafc;">
                    <option value="" disabled selected>Select a service</option>
                    <?php
                    $user_services = array_map('trim', explode(',', $profile['services']));
                    foreach ($user_services as $service) {
                        $service = trim($service);
                        if ($service === '') continue;
                        $emoji = isset($services_emojis[$service]) ? $services_emojis[$service] : '';
                        echo '<option value="'.htmlspecialchars($service).'">'.htmlspecialchars($emoji . ' ' . $service).'</option>';
                    }
                    ?>
                </select>
                <label class="hire-modal-label" for="hireNowDescription">Job Description</label>
                <textarea id="hireNowDescription" name="description" class="hire-modal-textarea" placeholder="Describe the job you want done..." required></textarea>
                <div class="hire-modal-btns" style="display:flex;gap:12px;justify-content:flex-end;margin-top:8px;">
                    <button type="button" class="hire-modal-btn cancel" id="cancelHireNowModal">Cancel</button>
                    <button type="submit" class="hire-modal-btn submit">Send Request</button>
                </div>
                <div id="hireNowModalMsg"></div>
            </form>
        </div>
    </div>
    <!-- Show feedback message as a pop-up notification if set -->
    <?php if (isset($_SESSION['profile_feedback'])): ?>
        <div id="profileFeedbackPopup" class="profile-feedback-popup">
            <span><?php echo htmlspecialchars($_SESSION['profile_feedback']); ?></span>
            <button onclick="document.getElementById('profileFeedbackPopup').style.display='none'" class="popup-close">&times;</button>
        </div>
        <style>
        .profile-feedback-popup {
            position: fixed;
            top: 32px;
            right: 32px;
            background: #e3f2fd;
            color: #1B4D43;
            font-weight: 600;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.10);
            padding: 16px 32px 16px 22px;
            z-index: 99999;
            min-width: 220px;
            display: flex;
            align-items: center;
            gap: 18px;
            font-size: 1.08em;
            animation: fadeInPop 0.3s;
        }
        @keyframes fadeInPop {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .popup-close {
            background: none;
            border: none;
            color: #1B4D43;
            font-size: 1.5em;
            cursor: pointer;
            margin-left: 8px;
            transition: color 0.2s;
        }
        .popup-close:hover { color: #d32f2f; }
        </style>
        <script>
        setTimeout(function(){
            var el = document.getElementById('profileFeedbackPopup');
            if (el) el.style.display = 'none';
        }, 4000);
        </script>
    <?php unset($_SESSION['profile_feedback']); endif; ?>
<!-- Review Modal -->
<div id="reviewModal" class="review-modal-overlay" style="display:none;z-index:99999;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(33,33,33,0.25);align-items:center;justify-content:center;">
  <div class="review-modal-content" style="background:#fff;padding:32px 28px 24px 28px;border-radius:12px;max-width:410px;width:95vw;box-shadow:0 2px 24px rgba(0,0,0,0.11);position:relative;">
    <button class="review-modal-close" onclick="closeReviewModal()" style="position:absolute;top:16px;right:16px;background:none;border:none;font-size:1.7em;color:#888;cursor:pointer;">&times;</button>
    <div class="review-modal-title" style="font-size:1.25em;font-weight:600;margin-bottom:16px;">Leave a Review</div>
    <form id="reviewForm" class="review-modal-form" method="post" autocomplete="off">
      <input type="hidden" id="reviewRequestId" name="request_id">
      <input type="hidden" id="reviewRole" name="role">
      <label for="reviewRating">Rating:</label>
      <div id="starRating" style="display:flex;gap:4px;margin-bottom:10px;">
        <span class="material-icons star" tabindex="0" data-value="1">star_border</span>
        <span class="material-icons star" tabindex="0" data-value="2">star_border</span>
        <span class="material-icons star" tabindex="0" data-value="3">star_border</span>
        <span class="material-icons star" tabindex="0" data-value="4">star_border</span>
        <span class="material-icons star" tabindex="0" data-value="5">star_border</span>
      </div>
      <textarea id="reviewMessage" name="review_message" rows="3" maxlength="500" placeholder="Write your review (optional)" style="width:100%;padding:8px;border-radius:7px;border:1.2px solid #bbb;font-size:1.07em;"></textarea>
      <div style="margin-top:12px;display:flex;gap:10px;justify-content:flex-end;">
        <button type="button" class="review-modal-btn cancel" onclick="closeReviewModal()" style="background:#eee;color:#444;padding:7px 18px;border-radius:7px;font-weight:600;font-size:1em;border:none;cursor:pointer;">Cancel</button>
        <button type="submit" class="review-modal-btn submit" style="background:#388e3c;color:#fff;padding:7px 18px;border-radius:7px;font-weight:600;font-size:1em;border:none;cursor:pointer;">Submit</button>
      </div>
    </form>
  </div>
</div>
<!-- End Review Modal -->
    <script>
    // --- Modal AJAX for Trash/History ---
    function reloadTrashModal() {
        fetch('trash.php')
            .then(r => r.text())
            .then(html => {
                const temp = document.createElement('div');
                temp.innerHTML = html;
                const content = temp.querySelector('.trash-container');
                document.getElementById('trashModalBody').innerHTML = content ? content.outerHTML : 'Failed to load.';
                attachTrashModalFormHandlers();
            });
    }
    function attachTrashModalFormHandlers() {
        const forms = document.querySelectorAll('#trashModalBody .trash-actions form');
        forms.forEach(form => {
            form.onsubmit = function(e) {
                e.preventDefault();
                const formData = new FormData(form);
                fetch('trash.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(() => {
                    reloadTrashModal();
                });
            };
        });
    }
    // Patch openTrashModal to use new reload logic
    document.getElementById('openTrashModal').onclick = function() {
        document.getElementById('trashModal').style.display = 'flex';
        reloadTrashModal();
    };
    // Also call attachTrashModalFormHandlers after initial load (in case modal is opened by other means)
    if (document.getElementById('trashModalBody')) attachTrashModalFormHandlers();
    // History Modal
    document.getElementById('openHistoryModal').onclick = function() {
        document.getElementById('historyModal').style.display = 'flex';
        fetch('history.php')
            .then(r => r.text())
            .then(html => {
                // Extract only the .history-container content
                const temp = document.createElement('div');
                temp.innerHTML = html;
                const content = temp.querySelector('.history-container');
                document.getElementById('historyModalBody').innerHTML = content ? content.outerHTML : 'Failed to load.';
            });
    };
    document.getElementById('closeHistoryModal').onclick = function() {
        document.getElementById('historyModal').style.display = 'none';
    };
    // Close modal on overlay click
    document.getElementById('trashModal').onclick = function(e) {
        if (e.target === this) this.style.display = 'none';
    };
    document.getElementById('historyModal').onclick = function(e) {
        if (e.target === this) this.style.display = 'none';
    };

    // --- Edit Profile Modal logic (centered popup) ---
    function closeEditProfileModal() {
        document.getElementById('editProfileModal').classList.remove('active');
        // Optionally reload page to show updated info
        window.location.reload();
    }
    document.getElementById('openEditProfileModal').onclick = function() {
        var modal = document.getElementById('editProfileModal');
        modal.classList.add('active');
        fetch('edit_profile_form.php')
            .then(r => r.text())
            .then(html => {
                document.getElementById('editProfileModalBody').innerHTML = html;
            });
    };
    document.getElementById('closeEditProfileModal').onclick = function() {
        closeEditProfileModal();
    };
    document.getElementById('editProfileModal').onclick = function(e) {
        if (e.target === this) closeEditProfileModal();
    };

    // --- HIRE NOW Modal logic ---
    <?php if (isset($_SESSION['email']) && $_SESSION['email'] !== $profile['email']): ?>
    document.addEventListener('DOMContentLoaded', function() {
        function openHireModal() {
            document.getElementById('hireNowModal').classList.add('active');
            document.getElementById('hireNowModalMsg').innerHTML = '';
            document.getElementById('hireNowForm').reset();
        }
        function closeHireModal() {
            document.getElementById('hireNowModal').classList.remove('active');
        }
        document.getElementById('openHireModal').onclick = openHireModal;
        document.getElementById('closeHireNowModal').onclick = closeHireModal;
        document.getElementById('cancelHireNowModal').onclick = closeHireModal;
        document.getElementById('hireNowModal').onclick = function(e) {
            if (e.target === this) closeHireModal();
        };
        // AJAX submit for hire form
        document.getElementById('hireNowForm').onsubmit = function(e) {
            e.preventDefault();
            var form = this;
            var msgDiv = document.getElementById('hireNowModalMsg');
            msgDiv.innerHTML = '';
            var formData = new FormData(form);
            formData.append('receiver_email', '<?php echo addslashes($profile['email']); ?>');
            fetch('send_hire_request.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    msgDiv.innerHTML = '<div class="hire-modal-success">Request sent successfully!</div>';
                    setTimeout(closeHireModal, 1500);
                } else {
                    msgDiv.innerHTML = '<div class="hire-modal-error">' + (data.error || 'Failed to send request.') + '</div>';
                }
            })
            .catch(() => {
                msgDiv.innerHTML = '<div class="hire-modal-error">Failed to send request.</div>';
            });
        };
    });
    <?php endif; ?>
    </script>
<script>
// --- Request Status Update ---
function updateRequestStatus(requestId, newStatus) {
    console.log('updateRequestStatus called with:', requestId, newStatus);
    fetch('../backend/update_request_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `request_id=${requestId}&new_status=${newStatus}`
    })
    .then(response => response.text())
    .then(text => {
        console.log('Raw updateRequestStatus response:', text);
        let data = {};
        try {
            data = JSON.parse(text);
        } catch (e) {
            alert('Response is not valid JSON. See console for details.');
            console.error('Invalid JSON:', text);
            throw e;
        }
        if (data.success) {
            alert(data.message || 'Status updated!');
            window.location.reload();
        } else {
            alert(data.error || 'Failed to update status.');
        }
    })
    .catch(err => {
        console.error('AJAX error:', err);
        alert('AJAX error: ' + err);
    });
}

// --- Review Modal Logic ---
let reviewStars = 0;
function openReviewModal(requestId, role) {
    document.getElementById('reviewModal').style.display = 'block';
    document.getElementById('reviewRequestId').value = requestId;
    document.getElementById('reviewRole').value = role;
    document.getElementById('reviewMessage').value = '';
    setStarRating(0);
}
function closeReviewModal() {
    document.getElementById('reviewModal').style.display = 'none';
}
function setStarRating(stars) {
    reviewStars = stars;
    document.querySelectorAll('#reviewModal .star').forEach(function(el, idx) {
        el.textContent = (idx < stars) ? 'star' : 'star_border';
    });
}
document.querySelectorAll('#reviewModal .star').forEach(function(el) {
    el.addEventListener('click', function() {
        setStarRating(parseInt(this.getAttribute('data-value')));
    });
});
document.getElementById('reviewForm').onsubmit = function(e) {
    e.preventDefault();
    const requestId = document.getElementById('reviewRequestId').value;
    const role = document.getElementById('reviewRole').value;
    const message = document.getElementById('reviewMessage').value;
    if (reviewStars === 0) {
        alert('Please select a star rating.');
        return;
    }
    fetch('submit_review.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `request_id=${requestId}&role=${role}&rating=${reviewStars}&review_message=${encodeURIComponent(message)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Review submitted!');
            closeReviewModal();
            window.location.reload();
        } else {
            alert(data.error || 'Failed to submit review.');
        }
    });
};
// --- Request Details Panel Logic ---
function showRequestDetails(req, type) {
    let html = '';
    html += `<h2>Request Details</h2>`;
    html += `<div style='font-size:1.1em;margin-bottom:10px;'><b>${type === 'sent' ? 'To' : 'From'}:</b> ${type === 'sent' ? req.receiver_email : req.sender_email}</div>`;
    html += `<div><b>Service:</b> ${req.service}</div>`;
    html += `<div><b>Title:</b> ${req.request_title}</div>`;
    if (req.date_time) html += `<div><b>Date/Time:</b> ${req.date_time}</div>`;
    if (req.location) html += `<div><b>Location:</b> ${req.location}</div>`;
    if (req.wage_amount) html += `<div><b>Wage:</b> ₱${req.wage_amount} ${req.wage_type}</div>`;
    if (req.contact_info) html += `<div><b>Contact:</b> ${req.contact_info}</div>`;
    if (req.message) html += `<div><b>Message:</b> ${req.message}</div>`;
    html += `<div><b>Status:</b> <span class='req-status ${req.status.toLowerCase()}'>${req.status}</span></div>`;
    html += `<div style='color:#888;font-size:0.95em;margin-bottom:10px;'>${req.created_at}</div>`;
    // Reviews
    if (req.client_review || req.client_rating) {
        html += `<div class='req-feedback'><b>Your Review:</b> ${req.client_review || ''}`;
        if (req.client_rating) html += ` <span class='req-rating'>Rating: ${req.client_rating}/5</span>`;
        html += `</div>`;
    }
    if (req.worker_review || req.worker_rating) {
        html += `<div class='req-feedback'><b>Worker's Review:</b> ${req.worker_review || ''}`;
        if (req.worker_rating) html += ` <span class='req-rating'>Rating: ${req.worker_rating}/5</span>`;
        html += `</div>`;
    }
    // Action buttons
    html += `<div class='action-btns'>`;
    if (type === 'received') {
        if (req.status && req.status.toLowerCase() === 'pending') {
            html += `<button class='request-btn accept-btn' onclick='updateRequestStatus(${req.id}, "Accepted")'>Accept</button> `;
            html += `<button class='request-btn decline-btn' onclick='updateRequestStatus(${req.id}, "Declined")'>Decline</button>`;
        } else if (req.status && req.status.toLowerCase() === 'accepted') {
            html += `<button class='request-btn done-btn' onclick='openReviewModal(${req.id}, "worker")'>Mark as Done & Review</button>`;
        }
    } else if (type === 'sent') {
        if (req.status && req.status.toLowerCase() === 'done') {
            html += `<button class='request-btn confirm-btn' onclick='openReviewModal(${req.id}, "client")'>Confirm & Review</button>`;
        }
    }
    html += `</div>`;
    document.getElementById('requestDetailsContent').innerHTML = html;
    document.getElementById('requestDetailsPanel').style.display = 'block';
}
function closeRequestDetails() {
    document.getElementById('requestDetailsPanel').style.display = 'none';
}

// --- Improved Review Modal Logic ---
document.addEventListener('DOMContentLoaded', function() {
    console.log('[ReviewModal] DOMContentLoaded - Attaching review modal logic');
    window.openReviewModal = function(requestId, role) {
        console.log('[ReviewModal] openReviewModal called', {requestId, role});
        document.getElementById('reviewModal').style.display = 'block';
        document.getElementById('reviewRequestId').value = requestId;
        document.getElementById('reviewRole').value = role;
        document.getElementById('reviewMessage').value = '';
        setStarRating(0);
        setTimeout(function() {
            let stars = document.querySelectorAll('#reviewModal .star');
            if (stars.length > 0) stars[0].focus();
        }, 100);
    }
    window.closeReviewModal = function() {
        document.getElementById('reviewModal').style.display = 'none';
    }
    let reviewStars = 0;
    function setStarRating(stars) {
        reviewStars = stars;
        document.querySelectorAll('#reviewModal .star').forEach(function(el, idx) {
            el.textContent = (idx < stars) ? 'star' : 'star_border';
            el.classList.toggle('selected', idx < stars);
        });
    }
    document.querySelectorAll('#reviewModal .star').forEach(function(el) {
        el.addEventListener('click', function() {
            setStarRating(parseInt(this.getAttribute('data-value')));
        });
        el.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                setStarRating(parseInt(this.getAttribute('data-value')));
            }
        });
    });
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        console.log('[ReviewModal] reviewForm found, attaching submit handler');
        reviewForm.onsubmit = function(e) {
            e.preventDefault();
            const requestId = document.getElementById('reviewRequestId').value;
            const role = document.getElementById('reviewRole').value;
            const message = document.getElementById('reviewMessage').value;
            console.log('[ReviewModal] Submit clicked', {requestId, role, reviewStars, message});
            if (reviewStars === 0) {
                alert('Please select a star rating.');
                return;
            }
            fetch('submit_review.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `request_id=${requestId}&role=${role}&rating=${reviewStars}&review_message=${encodeURIComponent(message)}`
            })
            .then(response => {
                console.log('[ReviewModal] Got response', response);
                return response.json();
            })
            .then(data => {
                console.log('[ReviewModal] Response JSON', data);
                if (data.success) {
                    alert('Review submitted!');
                    window.closeReviewModal();
                    window.location.reload();
                } else {
                    alert(data.error || 'Failed to submit review.');
                }
            })
            .catch(error => {
                console.error('[ReviewModal] AJAX error', error);
                alert('An error occurred submitting your review.');
            });
        };
    } else {
        console.error('[ReviewModal] reviewForm not found in DOM');
    }
});
</script>
</body>
</html>