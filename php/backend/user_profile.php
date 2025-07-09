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
    $social_media = isset($_POST['social_media']) ? trim($_POST['social_media']) : null;

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
                    $profile_data['profile_picture'] = $newFileName; 
                    $profile_data['profile_picture_url'] = '../backend/uploads/' . $newFileName;
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
        $stmt = $conn->prepare("UPDATE user_profiles SET first_name=?, last_name=?, birthdate=?, address=?, phone_number=?, social_media=?, skills=?, services=? $profile_picture_sql WHERE email=?");
        $stmt->bind_param("ssssssssss", $first_name, $last_name, $birthdate, $address, $phone_number, $social_media, $skills_str, $services_str, $profile_picture_param, $profile_email);
    } else {
        $stmt = $conn->prepare("UPDATE user_profiles SET first_name=?, last_name=?, birthdate=?, address=?, phone_number=?, social_media=?, skills=?, services=? WHERE email=?");
        $stmt->bind_param("sssssssss", $first_name, $last_name, $birthdate, $address, $phone_number, $social_media, $skills_str, $services_str, $profile_email);
    }
    $stmt->execute();
    $stmt->close();
    $_SESSION['first_name'] = $first_name;
    if ($feedback) {
        $_SESSION['profile_feedback'] = $feedback;
        header("Location: ../pages/profilee.php?email=" . urlencode($profile_email));
        exit();
    } else {
        $_SESSION['profile_feedback'] = 'Profile updated successfully!';
        header("Location: ../pages/profilee.php?email=" . urlencode($profile_email));
        exit();
    }
}

if (isset($_POST['job_action']) && isset($_POST['request_id'])) {
    $action = $_POST['job_action'];
    $request_id = intval($_POST['request_id']);

    if ($action === 'start') {
        // Provider marks as In Progress
        $stmt = $conn->prepare("UPDATE requests SET job_status='In Progress' WHERE id=?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'complete') {
        // Provider marks as Completed
        $stmt = $conn->prepare("UPDATE requests SET job_status='Completed' WHERE id=?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'confirm') {
        // Client confirms completion and can leave review/rating
        $review = isset($_POST['review']) ? $_POST['review'] : null;
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : null;

        // Check current status
        $stmt = $conn->prepare("SELECT job_status FROM requests WHERE id=?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $stmt->bind_result($current_status);
        $stmt->fetch();
        $stmt->close();

        if (strtolower($current_status) !== 'done') {
            $stmt = $conn->prepare("UPDATE requests SET job_status='Confirmed', review=?, rating=? WHERE id=?");
            $stmt->bind_param("sii", $review, $rating, $request_id);
            $stmt->execute();
            $stmt->close();
        } else {
            // Just update review/rating, don't change status
            $stmt = $conn->prepare("UPDATE requests SET review=?, rating=? WHERE id=?");
            $stmt->bind_param("sii", $review, $rating, $request_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: ".$_SERVER['REQUEST_URI']);
    exit();
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
    exit();
}

$profilePic = (!empty($profile['profile_picture']) && file_exists('uploads/' . $profile['profile_picture']))
    ? 'uploads/' . $profile['profile_picture']
    : 'assets/logo_ss.png';

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
    
    // No notification logic here
    
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
// $notifRes = $conn->query("SHOW TABLES LIKE 'notifications'");
// if ($notifRes && $notifRes->num_rows > 0) {
//     $notifQ = $conn->prepare("SELECT id, message, type, created_at FROM notifications WHERE user_email=? AND deleted = 0 ORDER BY created_at DESC LIMIT 5");
//     $notifQ->bind_param("s", $user_email);
//     $notifQ->execute();
//     $notifQ->bind_result($id, $msg, $type, $created_at);
//     while ($notifQ->fetch()) {
//         $notifications[] = ['id'=>$id, 'message'=>$msg, 'type'=>$type, 'created_at'=>$created_at];
//     }
//     $notifQ->close();
// }

// Fetch deleted notifications
$deleted_notifications = [];
// if ($notifRes && $notifRes->num_rows > 0) {
//     $notifQ = $conn->prepare("SELECT id, message, type, created_at FROM notifications WHERE user_email=? AND deleted = 1 ORDER BY created_at DESC LIMIT 10");
//     $notifQ->bind_param("s", $user_email);
//     $notifQ->execute();
//     $notifQ->bind_result($id, $msg, $type, $created_at);
//     while ($notifQ->fetch()) {
//         $deleted_notifications[] = ['id'=>$id, 'message'=>$msg, 'type'=>$type, 'created_at'=>$created_at];
//     }
//     $notifQ->close();
// }

// Requests Sent (fetch all for My Job tab, now also fetch all fields for modal)
$requests_sent = [];
$reqSentRes = $conn->query("SHOW TABLES LIKE 'requests'");
if ($reqSentRes && $reqSentRes->num_rows > 0) {
    $reqSentQ = $conn->prepare("SELECT id, receiver_email, service, status, created_at, request_title, date_time, location, wage_amount, wage_type, contact_info, message FROM requests WHERE sender_email=? AND deleted = 0 ORDER BY created_at DESC");
    $reqSentQ->bind_param("s", $user_email);
    $reqSentQ->execute();
    $reqSentQ->bind_result($id, $receiver_email, $service, $status, $created_at, $request_title, $date_time, $location, $wage_amount, $wage_type, $contact_info, $message);
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
            'message'=>$message
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

// Requests Received (fetch all for My Job tab, now also fetch all fields for modal)
$requests_received = [];
if ($reqSentRes && $reqSentRes->num_rows > 0) {
    $reqRecvQ = $conn->prepare("SELECT r.id, r.sender_email, up.first_name, up.last_name, r.service, r.status, r.created_at, r.request_title, r.date_time, r.location, r.wage_amount, r.wage_type, r.contact_info, r.message FROM requests r LEFT JOIN user_profiles up ON r.sender_email = up.email WHERE r.receiver_email=? AND r.deleted = 0 ORDER BY r.created_at DESC");
    $reqRecvQ->bind_param("s", $user_email);
    $reqRecvQ->execute();
    $reqRecvQ->bind_result($req_id, $sender_email, $sender_first_name, $sender_last_name, $service, $status, $created_at, $request_title, $date_time, $location, $wage_amount, $wage_type, $contact_info, $message);
    while ($reqRecvQ->fetch()) {
        $requests_received[] = [
            'id'=>$req_id,
            'sender_email'=>$sender_email,
            'sender_first_name'=>$sender_first_name,
            'sender_last_name'=>$sender_last_name,
            'service'=>$service,
            'status'=>$status,
            'created_at'=>$created_at,
            'request_title'=>$request_title,
            'date_time'=>$date_time,
            'location'=>$location,
            'wage_amount'=>$wage_amount,
            'wage_type'=>$wage_type,
            'contact_info'=>$contact_info,
            'message'=>$message
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

// For progress bar: count total and completed jobs
function get_progress($requests) {
    $total = count($requests);
    $completed = 0;
    foreach ($requests as $req) {
        if (isset($req['status']) && strtolower($req['status']) === 'accepted') $completed++;
    }
    return [$completed, $total];
}

// Handle status update POST
if (isset($_POST['update_status_request_id']) && isset($_POST['update_status_value'])) {
    $request_id = intval($_POST['update_status_request_id']);
    $new_status = $_POST['update_status_value'];
    // Fetch current request
    $conn = new mysqli("localhost", "root", "", "skillsphere");
    $stmt = $conn->prepare("SELECT sender_email, receiver_email, status FROM requests WHERE id=?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $stmt->bind_result($sender_email, $receiver_email, $current_status);
    $stmt->fetch();
    $stmt->close();
    $user_email = $_SESSION['email'];
    $is_sent = ($sender_email === $user_email);
    $is_received = ($receiver_email === $user_email);
    $allowed = false;
    // Constraints for sent requests (client)
    if ($is_sent) {
        if (strtolower($current_status) === 'confirmed' && $new_status === 'Confirmed') {
            $allowed = true;
        }
    }
    // Constraints for received requests (worker)
    if ($is_received) {
        $allowed_statuses = ['Pending','Accepted','Done'];
        if (in_array($current_status, $allowed_statuses) && in_array($new_status, $allowed_statuses)) {
            $allowed = true;
        }
    }
    if ($allowed) {
        $stmt = $conn->prepare("UPDATE requests SET status=? WHERE id=?");
        $stmt->bind_param("si", $new_status, $request_id);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
    $conn->close();
}

if (
    isset($_POST['review_request_id']) &&
    isset($_POST['review_action']) &&
    isset($_POST['review_rating']) &&
    isset($_POST['review_text']) &&
    isset($_POST['review_role'])
) {
    $request_id = intval($_POST['review_request_id']);
    $rating = intval($_POST['review_rating']);
    $review = $_POST['review_text'];
    $role = $_POST['review_role']; // 'worker' or 'client'

    // Fetch current ratings and status
    $stmt = $conn->prepare("SELECT worker_rating, client_rating FROM requests WHERE id=?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $stmt->bind_result($worker_rating, $client_rating);
    $stmt->fetch();
    $stmt->close();

    if ($role === 'worker') {
        // Save worker's review/rating
        $stmt = $conn->prepare("UPDATE requests SET worker_review=?, worker_rating=? WHERE id=?");
        $stmt->bind_param("sii", $review, $rating, $request_id);
        $stmt->execute();
        $stmt->close();
        $worker_rating = $rating; // update local variable
    } elseif ($role === 'client') {
        // Save client's review/rating
        $stmt = $conn->prepare("UPDATE requests SET client_review=?, client_rating=? WHERE id=?");
        $stmt->bind_param("sii", $review, $rating, $request_id);
        $stmt->execute();
        $stmt->close();
        $client_rating = $rating; // update local variable
    }

    // If both ratings are set and not null/zero, update status to Done
    if (!empty($worker_rating) && !empty($client_rating)) {
        // Set both status and job_status to Done
        $stmt = $conn->prepare("UPDATE requests SET status='Done', job_status='Done' WHERE id=?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(['success' => true]);
    exit();
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
    <link rel="stylesheet" href="../php/designs/user_profile1.css">
    <link rel="stylesheet" href="../php/designs/footer.css">
    <!-- Modern UI: Google Fonts & Material Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
    /* --- Modern Dashboard Redesign (Updated) --- */
    .dashboard-gradient-bar {
        width: 100%;
        height: 60px;
        background: linear-gradient(90deg, #1B4D43 0%, #43AA8B 50%, #FFC857 100%);
        margin-bottom: -32px;
        box-shadow: 0 6px 24px rgba(27,77,67,0.10);
        position: relative;
        z-index: 2;
        border-radius: 0 0 32px 32px;
        animation: fadeInPop 0.7s;
    }
    .professional-dashboard-container {
        background: #fff;
        border-radius: 24px;
        box-shadow: 0 8px 32px rgba(27,77,67,0.13);
        max-width: 1200px;
        margin: 0 auto 48px auto;
        padding: 40px 28px 32px 28px;
        position: relative;
        top: -32px;
        transition: box-shadow 0.2s;
    }
    .professional-dashboard-container:hover {
        box-shadow: 0 12px 40px rgba(27,77,67,0.18);
    }
    .modern-dashboard {
        margin-top: 0;
    }
    .modern-dashboard-inner {
        background: #f8fafc;
        border-radius: 20px;
        box-shadow: 0 2px 16px rgba(27,77,67,0.08);
        padding: 36px 22px 22px 22px;
    }
    .modern-dashboard-card {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 6px 28px rgba(27,77,67,0.10);
        padding: 28px 22px 22px 22px;
        margin-bottom: 0;
    }
    .dashboard-section-title {
        font-size: 1.22rem;
        font-weight: 800;
        color: #1B4D43;
        margin-bottom: 18px;
        letter-spacing: 0.01em;
        display: flex;
        align-items: center;
        gap: 8px;
        text-shadow: 0 1px 0 #fff, 0 2px 8px #e3f2fd;
    }
    .dashboard-object-card {
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(27,77,67,0.09);
        border-left: 6px solid #43AA8B;
        background: #fff;
        transition: box-shadow 0.18s, border 0.18s;
        margin-bottom: 8px;
    }
    .dashboard-object-card:hover {
        box-shadow: 0 8px 32px rgba(27,77,67,0.16);
        border-left: 6px solid #FFC857;
    }
    .myjob-tabs.modern-tabs {
        background: #f8fafc;
        border-radius: 14px 14px 0 0;
        box-shadow: 0 2px 10px rgba(27,77,67,0.05);
        padding: 0;
        margin-bottom: 2px;
    }
    .myjob-tab-btn {
        font-size: 1.12em;
        font-weight: 700;
        color: #7B8A8B;
        background: none;
        border: none;
        padding: 16px 36px 14px 36px;
        border-bottom: 3px solid transparent;
        border-radius: 14px 14px 0 0;
        transition: color 0.18s, border 0.18s, background 0.18s;
        outline: none;
    }
    .myjob-tab-btn.active {
        color: #1B4D43;
        border-bottom: 3px solid #FFC857;
        background: #fff;
        box-shadow: 0 2px 8px rgba(27,77,67,0.04);
    }
    @media (max-width: 900px) {
        .professional-dashboard-container {
            padding: 18px 4px;
        }
        .modern-dashboard-inner {
            padding: 10px 2px;
        }
        .modern-dashboard-card {
            padding: 10px 2px;
        }
        .dashboard-section-title {
            font-size: 1.08rem;
        }
    }
    @media (max-width: 600px) {
        .dashboard-gradient-bar { height: 36px; border-radius: 0 0 18px 18px; }
        .professional-dashboard-container { padding: 6px 1px; }
        .modern-dashboard-inner, .modern-dashboard-card { padding: 4px 1px; }
        .dashboard-section-title { font-size: 0.98rem; }
        .myjob-tab-btn { padding: 10px 10px 8px 10px; font-size: 1em; }
    }
    /* --- End Modern Dashboard Redesign (Updated) --- */
    .request-view-btn {
        background: #fff;
        color: #1B4D43;
        border: 1.5px solid #1B4D43;
        border-radius: 8px;
        padding: 4px 16px;
        font-size: 1em;
        font-weight: 600;
        cursor: pointer;
        margin-top: 6px;
        margin-bottom: 2px;
        transition: background 0.18s, color 0.18s, border 0.18s;
    }
    .request-view-btn:hover {
        background: #e3f2fd;
        color: #388e3c;
        border-color: #388e3c;
    }
    .dashboard-object-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .dashboard-object-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(30,40,90,0.08);
        padding: 18px 20px 14px 20px;
        display: flex;
        align-items: flex-start;
        gap: 18px;
        border-left: 5px solid #1B4D43;
        position: relative;
        transition: box-shadow 0.18s;
    }
    .dashboard-object-card:hover {
        box-shadow: 0 4px 18px rgba(30,40,90,0.13);
    }
    .dashboard-object-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #e3f2fd;
        color: #1B4D43;
        font-size: 1.7em;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: 2px;
    }
    .dashboard-object-content {
        flex: 1;
        min-width: 0;
    }
    .dashboard-object-title {
        font-weight: 600;
        font-size: 1.08em;
        margin-bottom: 2px;
        color: #1B4D43;
        word-break: break-word;
    }
    .dashboard-object-meta {
        font-size: 0.98em;
        color: #666;
        margin-bottom: 4px;
        word-break: break-word;
    }
    .dashboard-object-status {
        font-size: 0.98em;
        font-weight: 600;
        margin-right: 8px;
    }
    .dashboard-object-status.accepted { color: #388e3c; }
    .dashboard-object-status.declined { color: #d32f2f; }
    .dashboard-object-status.pending { color: #888; }
    .dashboard-object-date {
        font-size: 0.93em;
        color: #aaa;
        margin-top: 2px;
    }
    .dashboard-object-actions {
        margin-top: 8px;
        display: flex;
        gap: 8px;
    }
    .notif-success { border-left-color: #388e3c !important; }
    .notif-error { border-left-color: #d32f2f !important; }
    .notif-info { border-left-color: #1B4D43 !important; }
    .notif-warning { border-left-color: #fbc02d !important; }
    .dashboard-object-message {
        font-size: 1.05em;
        color: #222;
        margin-bottom: 2px;
        word-break: break-word;
    }
    .dashboard-object-label {
        font-weight: 500;
        color: #888;
        margin-right: 4px;
    }
    .dashboard-object-service {
        color: #1B4D43;
        font-weight: 600;
    }
    .dashboard-object-btn {
        background: #e3f2fd;
        color: #1B4D43;
        border: none;
        border-radius: 7px;
        padding: 5px 16px;
        font-size: 1em;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.18s, color 0.18s;
    }
    .dashboard-object-btn:hover {
        background: #b3e5fc;
        color: #388e3c;
    }
    .dashboard-object-empty {
        color: #888;
        font-size: 1.05em;
        padding: 12px 0 0 0;
        text-align: center;
    }

    /* --- My Job Tab Styles --- */
    .myjob-tabs {
        display: flex;
        gap: 0;
        margin-bottom: 18px;
        border-bottom: 2px solid #e0e0e0;
    }
    .myjob-tab-btn {
        background: none;
        border: none;
        font-size: 1.08em;
        font-weight: 600;
        color: #888;
        padding: 12px 28px 10px 28px;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: color 0.18s, border 0.18s;
        outline: none;
    }
    .myjob-tab-btn.active {
        color: #1B4D43;
        border-bottom: 3px solid #1B4D43;
        background: #f8f9fa;
    }
    .myjob-tab-content {
        display: none;
        animation: fadeInPop 0.25s;
    }
    .myjob-tab-content.active {
        display: block;
    }
    .scrollable-list-container {
        max-height: 400px;
        overflow-y: auto;
        padding-right: 2px;
    }
    @media (max-width: 900px) {
        .scrollable-list-container {
            max-height: 300px;
        }
    }
    @media (max-width: 600px) {
        .myjob-tab-btn { padding: 10px 10px 8px 10px; font-size: 1em; }
        .scrollable-list-container { max-height: 200px; }
    }
    /* --- End My Job Tab Styles --- */

    /* --- Request Details Modal Centered Styles --- */
    .request-modal-overlay {
        display: none;
        position: fixed;
        z-index: 99999;
        left: 0; top: 0; right: 0; bottom: 0;
        background: rgba(30,40,90,0.18);
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
    }
    .request-modal-overlay.active,
    .request-modal-overlay[style*="flex"] {
        display: flex !important;
    }
    .request-modal-content {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 6px 32px rgba(30,40,90,0.18);
        max-width: 420px;
        width: 95%;
        padding: 32px 28px 24px 28px;
        position: relative;
        animation: fadeInPop 0.25s;
        display: flex;
        flex-direction: column;
        align-items: stretch;
    }
    .request-modal-title {
        font-size: 1.25em;
        font-weight: 700;
        color: #1B4D43;
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .request-modal-row {
        display: flex;
        align-items: flex-start;
        margin-bottom: 10px;
        gap: 8px;
    }
    .request-modal-label {
        font-weight: 600;
        color: #1B4D43;
        min-width: 90px;
        display: inline-block;
    }
    .request-modal-value {
        color: #222;
        word-break: break-word;
        flex: 1;
    }
    .request-modal-status.accepted { background: #e8f5e9; color: #388e3c; }
    .request-modal-status.declined { background: #ffebee; color: #d32f2f; }
    .request-modal-status.pending { background: #f5f5f5; color: #888; }
    .request-modal-status.done { background: #e0e0e0; color: #888; }
    .request-modal-status.confirmed { background: #e0e0e0; color: #388e3c; }
    .request-modal-description {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 10px 12px;
        color: #333;
        font-size: 1.05em;
        margin-bottom: 14px;
        word-break: break-word;
        min-height: 40px;
    }
    .request-modal-close {
        position: absolute;
        top: 12px;
        right: 16px;
        background: none;
        border: none;
        color: #1B4D43;
        font-size: 2em;
        font-weight: 700;
        cursor: pointer;
        z-index: 2;
        transition: color 0.2s;
    }
    .request-modal-close:hover { color: #d32f2f; }
    @keyframes fadeInPop {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @media (max-width: 600px) {
        .request-modal-content { padding: 18px 6vw 14px 6vw; }
        .request-modal-title { font-size: 1.08em; }
    }

    /* --- Edit Profile Modal Centered Styles --- */
    .edit-profile-modal-overlay {
        display: none;
        position: fixed;
        z-index: 99999;
        left: 0; top: 0; right: 0; bottom: 0;
        background: rgba(30,40,90,0.18);
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
    }
    .edit-profile-modal-overlay.active {
        display: flex;
    }
    .edit-profile-modal-content {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 6px 32px rgba(30,40,90,0.18);
        max-width: 520px;
        width: 95%;
        padding: 32px 28px 24px 28px;
        position: relative;
        animation: fadeInPop 0.25s;
        display: flex;
        flex-direction: column;
        align-items: stretch;
    }
    .edit-profile-modal-close {
        position: absolute;
        top: 12px;
        right: 16px;
        background: none;
        border: none;
        color: #1B4D43;
        font-size: 2em;
        font-weight: 700;
        cursor: pointer;
        z-index: 2;
        transition: color 0.2s;
    }
    .edit-profile-modal-close:hover { color: #d32f2f; }
    @media (max-width: 600px) {
        .edit-profile-modal-content { padding: 18px 6vw 14px 6vw; }
    }
    .dashboard-object-btn.confirm-btn[disabled] {
        background: #eee !important;
        color: #bbb !important;
        cursor: not-allowed !important;
        pointer-events: none !important;
        border: none !important;
        opacity: 0.7;
    }
    .dashboard-object-btn.confirm-btn:not([disabled]):hover {
        background: #e3f2fd;
        color: #1976d2;
        border: 1.5px solid #1976d2;
    }

    /* --- Review Modal Centered Styles --- */
    .review-modal-overlay {
        display: none;
        position: fixed;
        z-index: 99999;
        left: 0; top: 0; right: 0; bottom: 0;
        background: rgba(30,40,90,0.18);
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
    }
    .review-modal-overlay.active {
        display: flex;
    }
    .review-modal-content {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 6px 32px rgba(30,40,90,0.18);
        max-width: 420px;
        width: 95%;
        padding: 32px 28px 24px 28px;
        position: relative;
        animation: fadeInPop 0.25s;
        display: flex;
        flex-direction: column;
        align-items: stretch;
    }
    .review-modal-title {
        font-size: 1.25em;
        font-weight: 700;
        color: #1B4D43;
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .review-modal-close {
        position: absolute;
        top: 12px;
        right: 16px;
        background: none;
        border: none;
        color: #1B4D43;
        font-size: 2em;
        font-weight: 700;
        cursor: pointer;
        z-index: 2;
        transition: color 0.2s;
    }
    .review-modal-close:hover { color: #d32f2f; }
    .review-modal-form label {
        font-weight: 600;
        color: #1B4D43;
        margin-top: 8px;
        margin-bottom: 2px;
        display: block;
    }
    .review-modal-form .star-rating {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        font-size: 2em;
        margin-bottom: 12px;
        margin-top: 2px;
    }
    .review-modal-form .star-rating input[type="radio"] {
        display: none;
    }
    .review-modal-form .star-rating label {
        color: #bbb;
        cursor: pointer;
        transition: color 0.18s;
        margin-left: 2px;
    }
    .review-modal-form .star-rating input[type="radio"]:checked ~ label,
    .review-modal-form .star-rating label:hover,
    .review-modal-form .star-rating label:hover ~ label {
        color: #FFD600;
    }
    .review-modal-form textarea {
        width: 100%;
        margin-bottom: 12px;
        border-radius: 7px;
        border: 1.2px solid #bbb;
        font-size: 1.05em;
        background: #f8fafc;
        padding: 7px 10px;
        min-height: 60px;
        resize: vertical;
    }
    .review-modal-btns {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 10px;
    }
    .review-modal-btn {
        background: #e3f2fd;
        color: #1B4D43;
        border: none;
        border-radius: 7px;
        padding: 7px 18px;
        font-size: 1em;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.18s, color 0.18s;
    }
    .review-modal-btn.submit { background: #e8f5e9; color: #388e3c; }
    .review-modal-btn.cancel { background: #f5f5f5; color: #888; }
    .review-modal-btn:hover { opacity: 0.92; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div id="profile-container" style="max-width: 1100px; margin: 40px auto 0 auto; padding: 0 24px 32px 24px; background: #fff; border-radius: 24px; box-shadow: 0 8px 32px rgba(27,77,67,0.13);">
        <div id="profile-header" style="display: flex; align-items: flex-start; gap: 32px; margin-bottom: 24px;">
            <div class="profile-avatar" style="flex-shrink:0;">
                <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile Picture" style="height: 90px; width: 90px; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
            </div>
            <div style="flex:1; display: flex; flex-direction: column; gap: 8px;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                    <div style="font-size: 1.35rem; font-weight: 700; color: #223; letter-spacing: 0.01em;">
                        <?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?>
                    </div>
                    <div style="display: flex; gap: 16px;">
                        <?php if ($isOwner): ?>
                            <button type="button" class="edit-profile-btn edit-btn" id="openEditProfileModal" style="background: #1B4D43; color: #fff; border-radius: 999px; padding: 10px 28px; font-size: 1.08em; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: none; display: flex; align-items: center; gap: 6px; transition: background 0.2s;">
                                <span class="material-icons" style="font-size:1.2em;">edit</span> Edit Profile
                            </button>
                            <button type="button" class="edit-profile-btn trash-btn" id="openTrashModal" style="background: #fff; color: #1B4D43; border: 2px solid #1B4D43; border-radius: 999px; padding: 10px 28px; font-size: 1.08em; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; align-items: center; gap: 6px; transition: background 0.2s;">
                                <span class="material-icons" style="font-size:1.2em;">delete</span> Trash / History
                            </button>
                            <button type="button" class="edit-profile-btn history-btn" id="openHistoryModal" style="background: #fff; color: #1B4D43; border: 2px solid #1B4D43; border-radius: 999px; padding: 10px 28px; font-size: 1.08em; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; align-items: center; gap: 6px; transition: background 0.2s; margin-top:">
                                <span class="material-icons" style="font-size:1.2em;">history</span> Full History
                            </button>
                        <?php elseif (isset($_SESSION['email']) && $_SESSION['email'] !== $profile['email']): ?>
                            <button type="button" class="edit-profile-btn hire-btn open-hire-modal" id="openHireModal" style="background: #43AA8B; color: #fff; border-radius: 999px; padding: 10px 28px; font-size: 1.08em; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: none; display: flex; align-items: center; gap: 6px; transition: background 0.2s;">
                                <span class="material-icons" style="font-size:1.2em;">work</span> HIRE NOW!
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="color: #666; font-size: 1.05em; display: flex; align-items: center; gap: 8px;">
                    <span class="material-icons" style="font-size:1em;vertical-align:middle;margin-right:4px;color:#1B4D43;">mail</span>
                    <?php echo htmlspecialchars($profile['email']); ?>
                </div>
            </div>
        </div>
        <div id="profile-content">
            <?php if ($editing): ?>
            <form method="post" enctype="multipart/form-data" style="width:100%;max-width:600px;background:#f8fafc;padding:32px 24px 24px 24px;border-radius:18px;box-shadow:0 2px 12px rgba(27,77,67,0.08);margin:0 auto;">
                <label style="font-weight:600;color:#1B4D43;">Profile Picture:<br>
                    <input type="file" name="profile_picture" accept="image/*" style="margin-top:6px;">
                </label><br><br>
                <label style="font-weight:600;color:#1B4D43;">First Name:<input type="text" name="first_name" value="<?php echo htmlspecialchars($profile['first_name']); ?>" required style="width:100%;padding:8px 10px;border-radius:8px;border:1px solid #bbb;margin-top:4px;"></label><br><br>
                <label style="font-weight:600;color:#1B4D43;">Last Name:<input type="text" name="last_name" value="<?php echo htmlspecialchars($profile['last_name']); ?>" required style="width:100%;padding:8px 10px;border-radius:8px;border:1px solid #bbb;margin-top:4px;"></label><br><br>
                <label style="font-weight:600;color:#1B4D43;">Birthdate:<input type="date" name="birthdate" value="<?php echo htmlspecialchars($profile['birthdate']); ?>" required style="width:100%;padding:8px 10px;border-radius:8px;border:1px solid #bbb;margin-top:4px;"></label><br><br>
                <label style="font-weight:600;color:#1B4D43;">Address:<input type="text" name="address" value="<?php echo htmlspecialchars($profile['address']); ?>" required style="width:100%;padding:8px 10px;border-radius:8px;border:1px solid #bbb;margin-top:4px;"></label><br><br>
                <label style="font-weight:600;color:#1B4D43;">Phone Number:<input type="text" name="phone_number" value="<?php echo htmlspecialchars($profile['phone_number']); ?>" required style="width:100%;padding:8px 10px;border-radius:8px;border:1px solid #bbb;margin-top:4px;"></label><br><br>
                <?php
                $user_skills = array_map('trim', explode(',', $profile['skills']));
                $user_services = array_map('trim', explode(',', $profile['services']));
                ?>
                <label style="font-weight:600;color:#1B4D43;">Skills:
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; max-height: 160px; overflow-y: auto; border: 1px solid #bbb; border-radius: 8px; padding: 10px; background: #fff;">
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
                <button type="submit" name="save_profile" class="edit-profile-btn" style="margin-top:10px;width:100%;padding:12px 0;font-size:1.1rem;background:linear-gradient(90deg,#1B4D43,#43AA8B);color:#fff;border:none;border-radius:8px;font-weight:700;">Save</button>
                <a href="user_profile.php?email=<?php echo urlencode($profile_email); ?>" style="margin-left:10px;color:#d32f2f;text-decoration:none;font-weight:600;">Cancel</a>
            </form>
            <?php else: ?>
            <div style="display: flex; flex-wrap: wrap; gap: 32px; margin-bottom: 18px;">
                <div style="flex:1 1 320px; min-width:260px; background:#fff; border-radius:18px; box-shadow:0 2px 12px rgba(27,77,67,0.08); padding: 24px 18px 18px 18px; margin-bottom:0; display:flex; flex-direction:column; gap:8px;">
                    <div style="font-weight:700; color:#1B4D43; margin-bottom:8px; display:flex; align-items:center; gap:6px;"><span class="material-icons" style="font-size:1.1em;vertical-align:middle;">info</span> Contact Information</div>
                    <div style="margin-bottom:4px;"><span style="color:#888;font-weight:500;"><span class="material-icons" style="font-size:1em;vertical-align:middle;">cake</span> Birthdate:</span> <span style="color:#223;font-weight:600;"> <?php echo isset($profile['birthdate']) ? htmlspecialchars($profile['birthdate']) : 'N/A'; ?></span></div>
                    <div style="margin-bottom:4px;"><span style="color:#888;font-weight:500;"><span class="material-icons" style="font-size:1em;vertical-align:middle;">location_on</span> Address:</span> <span style="color:#223;font-weight:600;"> <?php echo htmlspecialchars($profile['address']); ?></span></div>
                    <div><span style="color:#888;font-weight:500;"><span class="material-icons" style="font-size:1em;vertical-align:middle;">call</span> Phone:</span> <span style="color:#223;font-weight:600;"> <?php echo htmlspecialchars($profile['phone_number']); ?></span></div>
                </div>
                <div style="flex:2 1 420px; min-width:260px; background:#fff; border-radius:18px; box-shadow:0 2px 12px rgba(27,77,67,0.08); padding: 24px 18px 18px 18px; margin-bottom:0; display:flex; flex-direction:column; gap:8px;">
                    <div style="font-weight:700; color:#1B4D43; margin-bottom:8px; display:flex; align-items:center; gap:6px;"><span class="material-icons" style="font-size:1.1em;vertical-align:middle;">star</span> Skills</div>
                    <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:12px;">
                        <?php
                        $user_skills = array_map('trim', explode(',', $profile['skills']));
                        foreach ($user_skills as $skill) {
                            $skill = trim($skill);
                            if ($skill === '') continue;
                            $emoji = isset($skills_emojis[$skill]) ? $skills_emojis[$skill] : '';
                            echo '<span style="background:#f0f7ff;color:#1976d2;padding:6px 12px;border-radius:6px;font-size:0.97em;font-weight:500;">'.htmlspecialchars($emoji . ' ' . $skill).'</span>';
                        }
                        ?>
                    </div>
                    <div style="font-weight:700; color:#1B4D43; margin-bottom:8px; display:flex; align-items:center; gap:6px;"><span class="material-icons" style="font-size:1.1em;vertical-align:middle;">handshake</span> Services</div>
                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        <?php
                        $user_services = array_map('trim', explode(',', $profile['services']));
                        foreach ($user_services as $service) {
                            $service = trim($service);
                            if ($service === '') continue;
                            $emoji = isset($services_emojis[$service]) ? $services_emojis[$service] : '';
                            echo '<span style="background:#fff3e0;color:#f57c00;padding:6px 12px;border-radius:6px;font-size:0.97em;font-weight:500;">'.htmlspecialchars($emoji . ' ' . $service).'</span>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php if ($isOwner): ?>
        <div id="profile-dashboard" class="modern-dashboard">
                <!-- My Job Tabbed Section -->
                <div class="dashboard-col" style="flex:1 1 100%;max-width:100%;">
                    <div class="dashboard-card-container modern-dashboard-card">
                        <div class="myjob-tabs modern-tabs" id="myJobTabs">
                            <button class="myjob-tab-btn active" data-tab="sent">Hiring Requests Sent</button>
                            <button class="myjob-tab-btn" data-tab="received">Hiring Requests Received</button>
                        </div>
                        <div class="myjob-tab-content active" id="myjob-tab-sent">
                            <h3 class="dashboard-section-title"><span class="material-icons" style="font-size:1.1em;vertical-align:middle;margin-right:4px;">send</span> Requests Sent</h3>
                            <div class="scrollable-list-container">
                            <?php if (!empty($requests_sent)): ?>
                                <ul class="dashboard-object-list">
                                    <?php foreach ($requests_sent as $req): ?>
                                        <li data-status="<?php echo htmlspecialchars($req['status']); ?>">
                                            <div class="dashboard-object-card">
                                                <div class="dashboard-object-avatar" style="background:#1B4D43;color:#fff;">
                                                    <?php echo strtoupper(substr($req['receiver_email'],0,1)); ?>
                                                </div>
                                                <div class="dashboard-object-content">
                                                    <div class="dashboard-object-title">
                                                        <span class="dashboard-object-label">To:</span>
                                                        <?php echo htmlspecialchars($req['receiver_email']); ?>
                                                    </div>
                                                    <div class="dashboard-object-meta">
                                                        <span class="dashboard-object-label">Service:</span>
                                                        <span class="dashboard-object-service"><?php echo htmlspecialchars($req['service']); ?></span>
                                                    </div>
                                                    <div class="dashboard-object-meta">
                                                        <span class="dashboard-object-label">Status:</span>
                                                        <?php
                                                        $status = $req['status'];
                                                        $statusClass = '';
                                                        if (strtolower($status) === 'pending') $statusClass = 'pending';
                                                        else if (strtolower($status) === 'accepted') $statusClass = 'accepted';
                                                        else if (strtolower($status) === 'done') $statusClass = 'done';
                                                        else if (strtolower($status) === 'confirmed') $statusClass = 'confirmed';
                                                        else if (strtolower($status) === 'declined') $statusClass = 'declined';
                                                        ?>
                                                        <span class="request-modal-status <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                                                        <div class="dashboard-object-actions">
                                                            <?php if (in_array(strtolower($status), ['pending','accepted'])): ?>
                                                                <button class="dashboard-object-btn cancel-btn" onclick="cancelSentRequest(<?php echo $req['id']; ?>)">Cancel</button>
                                                            <?php endif; ?>
                                                            <?php if (strtolower($req['status']) === 'done'): ?>
                                                                <button class="dashboard-object-btn confirm-btn" onclick="openReviewModal(<?php echo $req['id']; ?>, 'Confirmed', 'client')">Confirm</button>
                                                            <?php else: ?>
                                                                <button class="dashboard-object-btn confirm-btn" disabled title="Waiting for worker to mark as done">Confirm Done</button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="dashboard-object-date"><?php echo htmlspecialchars($req['created_at']); ?></div>
                                                    <div class="dashboard-object-actions">
                                                        <button class="dashboard-object-btn request-view-btn-sent"
                                                            data-request-id="<?php echo $req['id']; ?>"
                                                            data-receiver-email="<?php echo htmlspecialchars($req['receiver_email']); ?>"
                                                            data-service="<?php echo htmlspecialchars($req['service']); ?>"
                                                            data-status="<?php echo htmlspecialchars($req['status']); ?>"
                                                            data-created-at="<?php echo htmlspecialchars($req['created_at']); ?>"
                                                            data-request-title="<?php echo isset($req['request_title']) ? htmlspecialchars($req['request_title']) : ''; ?>"
                                                            data-date-time="<?php echo isset($req['date_time']) ? htmlspecialchars($req['date_time']) : ''; ?>"
                                                            data-location="<?php echo isset($req['location']) ? htmlspecialchars($req['location']) : ''; ?>"
                                                            data-wage-amount="<?php echo isset($req['wage_amount']) ? htmlspecialchars($req['wage_amount']) : ''; ?>"
                                                            data-wage-type="<?php echo isset($req['wage_type']) ? htmlspecialchars($req['wage_type']) : ''; ?>"
                                                            data-contact-info="<?php echo isset($req['contact_info']) ? htmlspecialchars($req['contact_info']) : ''; ?>"
                                                            data-message="<?php echo isset($req['message']) ? htmlspecialchars($req['message']) : ''; ?>"
                                                        >View</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="dashboard-object-empty">No requests sent yet.</div>
                            <?php endif; ?>
                            </div>
                        </div>
                        <div class="myjob-tab-content" id="myjob-tab-received">
                            <h3 class="dashboard-section-title"><span class="material-icons" style="font-size:1.1em;vertical-align:middle;margin-right:4px;">inbox</span> Requests Received</h3>
                            <div class="scrollable-list-container">
                            <?php if (!empty($requests_received)): ?>
                                <ul class="dashboard-object-list">
                                    <?php foreach ($requests_received as $req): ?>
                                        <li data-status="<?php echo htmlspecialchars($req['status']); ?>">
                                            <div class="dashboard-object-card">
                                                <div class="dashboard-object-avatar" style="background:#ffa500;color:#fff;">
                                                    <?php echo strtoupper(substr($req['sender_email'],0,1)); ?>
                                                </div>
                                                <div class="dashboard-object-content">
                                                    <div class="dashboard-object-title">
                                                        <span class="dashboard-object-label">From:</span>
                                                        <?php echo htmlspecialchars($req['sender_email']); ?>
                                                    </div>
                                                    <div class="dashboard-object-meta">
                                                        <span class="dashboard-object-label">Service:</span>
                                                        <span class="dashboard-object-service"><?php echo htmlspecialchars($req['service']); ?></span>
                                                    </div>
                                                    <div class="dashboard-object-meta">
                                                        <span class="dashboard-object-label">Status:</span>
                                                        <?php
                                                        $status = $req['status'];
                                                        $statusClass = '';
                                                        if (strtolower($status) === 'pending') $statusClass = 'pending';
                                                        else if (strtolower($status) === 'accepted') $statusClass = 'accepted';
                                                        else if (strtolower($status) === 'done') $statusClass = 'done';
                                                        else if (strtolower($status) === 'confirmed') $statusClass = 'confirmed';
                                                        else if (strtolower($status) === 'declined') $statusClass = 'declined';
                                                        ?>
                                                        <span class="request-modal-status <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                                                        <div class="dashboard-object-actions">
                                                            <?php if (strtolower($status) === 'pending'): ?>
                                                                <button class="dashboard-object-btn accept-btn" onclick="updateRequestStatus(<?php echo $req['id']; ?>, 'Accepted')">Accept</button>
                                                                <button class="dashboard-object-btn decline-btn" onclick="updateRequestStatus(<?php echo $req['id']; ?>, 'Declined')">Decline</button>
                                                            <?php elseif (strtolower($status) === 'accepted'): ?>
                                                                <button class="dashboard-object-btn done-btn" onclick="openReviewModal(<?php echo $req['id']; ?>, 'Done', 'worker')">Mark as Done</button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="dashboard-object-date"><?php echo htmlspecialchars($req['created_at']); ?></div>
                                                    <div class="dashboard-object-actions">
                                                        <button class="dashboard-object-btn request-view-btn-received"
                                                            data-request-id="<?php echo $req['id']; ?>"
                                                            data-sender-email="<?php echo htmlspecialchars($req['sender_email']); ?>"
                                                            data-sender-first-name="<?php echo htmlspecialchars($req['sender_first_name']); ?>"
                                                            data-sender-last-name="<?php echo htmlspecialchars($req['sender_last_name']); ?>"
                                                            data-service="<?php echo htmlspecialchars($req['service']); ?>"
                                                            data-status="<?php echo htmlspecialchars($req['status']); ?>"
                                                            data-created-at="<?php echo htmlspecialchars($req['created_at']); ?>"
                                                            data-request-title="<?php echo htmlspecialchars($req['request_title']); ?>"
                                                            data-date-time="<?php echo htmlspecialchars($req['date_time']); ?>"
                                                            data-location="<?php echo htmlspecialchars($req['location']); ?>"
                                                            data-wage-amount="<?php echo htmlspecialchars($req['wage_amount']); ?>"
                                                            data-wage-type="<?php echo htmlspecialchars($req['wage_type']); ?>"
                                                            data-contact-info="<?php echo htmlspecialchars($req['contact_info']); ?>"
                                                            data-message="<?php echo htmlspecialchars($req['message']); ?>"
                                                        >View</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="dashboard-object-empty">No requests received yet.</div>
                            <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End My Job Tabbed Section -->
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div style="clear:both;"></div>
    <?php include 'footer.php'; ?>

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

    <!-- Request Details Modal (centered container for request details) -->
    <div id="requestDetailsModal" class="request-modal-overlay">
        <div class="request-modal-content">
            <button class="request-modal-close" id="closeRequestDetailsModal">&times;</button>
            <div id="requestDetailsModalBody">
                <!-- Populated by JS -->
            </div>
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

    <!-- Review/Rating Modal (centered, not in footer) -->
    <div id="reviewModal" class="review-modal-overlay">
        <div class="review-modal-content">
            <button class="review-modal-close" id="closeReviewModal">&times;</button>
            <div class="review-modal-title" id="reviewModalTitle">Leave a Review</div>
            <form id="reviewForm" class="review-modal-form" method="post">
                <input type="hidden" name="review_request_id" id="reviewRequestId">
                <input type="hidden" name="review_action" id="reviewAction">
                <input type="hidden" name="review_role" id="reviewRole">
                <label for="reviewRating">Rating:</label>
                <div class="star-rating" id="starRating">
                    <input type="radio" id="star5" name="review_rating" value="5" /><label for="star5" title="5 stars">&#9733;</label>
                    <input type="radio" id="star4" name="review_rating" value="4" /><label for="star4" title="4 stars">&#9733;</label>
                    <input type="radio" id="star3" name="review_rating" value="3" /><label for="star3" title="3 stars">&#9733;</label>
                    <input type="radio" id="star2" name="review_rating" value="2" /><label for="star2" title="2 stars">&#9733;</label>
                    <input type="radio" id="star1" name="review_rating" value="1" /><label for="star1" title="1 star">&#9733;</label>
                </div>
                <label for="reviewText">Review:</label>
                <textarea name="review_text" id="reviewText" rows="3" maxlength="500" required></textarea>
                <div class="review-modal-btns">
                    <button type="button" class="review-modal-btn cancel" id="cancelReviewModal">Cancel</button>
                    <button type="submit" class="review-modal-btn submit">Submit</button>
                </div>
            </form>
        </div>
    </div>

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
            .then(response => response.json())
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

    // --- My Job Tab logic ---
    document.addEventListener('DOMContentLoaded', function() {
        var tabBtns = document.querySelectorAll('.myjob-tab-btn');
        var tabContents = {
            sent: document.getElementById('myjob-tab-sent'),
            received: document.getElementById('myjob-tab-received')
        };
        tabBtns.forEach(function(btn) {
            btn.onclick = function() {
                tabBtns.forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');
                for (var key in tabContents) {
                    if (btn.getAttribute('data-tab') === key) {
                        tabContents[key].classList.add('active');
                    } else {
                        tabContents[key].classList.remove('active');
                    }
                }
            };
        });
    });

    // --- Request Details Modal logic (centered container) ---
    document.addEventListener('DOMContentLoaded', function() {
        // For Requests Sent
        document.querySelectorAll('.request-view-btn-sent').forEach(function(btn) {
            btn.onclick = function() {
                var id = btn.getAttribute('data-request-id');
                var receiver = btn.getAttribute('data-receiver-email');
                var service = btn.getAttribute('data-service');
                var status = btn.getAttribute('data-status');
                var created = btn.getAttribute('data-created-at');
                var requestTitle = btn.getAttribute('data-request-title') || '';
                var dateTime = btn.getAttribute('data-date-time') || '';
                var location = btn.getAttribute('data-location') || '';
                var wageAmount = btn.getAttribute('data-wage-amount') || '';
                var wageType = btn.getAttribute('data-wage-type') || '';
                var contactInfo = btn.getAttribute('data-contact-info') || '';
                var message = btn.getAttribute('data-message') || '';
                var statusClass = 'pending';
                if (status && status.toLowerCase() === 'accepted') statusClass = 'accepted';
                else if (status && status.toLowerCase() === 'declined') statusClass = 'declined';
                else if (status && status.toLowerCase() === 'done') statusClass = 'done';
                else if (status && status.toLowerCase() === 'confirmed') statusClass = 'confirmed';

                var modalBody = document.getElementById('requestDetailsModalBody');
                modalBody.innerHTML = `
                    <div class="request-modal-title">
                        <span class="material-icons" style="font-size:1.2em;vertical-align:middle;">visibility</span>
                        Request Details
                    </div>
                    <div class="request-modal-row">
                        <span class="request-modal-label">To:</span>
                        <span class="request-modal-value">${receiver}</span>
                    </div>
                    <div class="request-modal-row">
                        <span class="request-modal-label">Service:</span>
                        <span class="request-modal-value">${service || ''}</span>
                    </div>
                    <div class="request-modal-row">
                        <span class="request-modal-label">Request Title:</span>
                        <span class="request-modal-value">${requestTitle || '<i>None</i>'}</span>
                    </div>
                    <div class="request-modal-row">
                        <span class="request-modal-label">Date & Time:</span>
                        <span class="request-modal-value">${dateTime || '<i>None</i>'}</span>
                    </div>
                    <div class="request-modal-row">
                        <span class="request-modal-label">Location:</span>
                        <span class="request-modal-value">${location || '<i>None</i>'}</span>
                    </div>
                    <div class="request-modal-row">
                        <span class="request-modal-label">Wage:</span>
                        <span class="request-modal-value">${wageAmount ? '₱' + wageAmount : '<i>None</i>'} ${wageType ? ('(' + wageType + ')') : ''}</span>
                    </div>
                    <div class="request-modal-row">
                        <span class="request-modal-label">Contact Info:</span>
                        <span class="request-modal-value">${contactInfo || '<i>None</i>'}</span>
                    </div>
                    <div class="request-modal-row">
                        <span class="request-modal-label">Status:</span>
                        <span class="request-modal-value request-modal-status ${statusClass}">${status}</span>
                    </div>
                    <div class="request-modal-row">
                        <span class="request-modal-label">Message:</span>
                    </div>
                    <div class="request-modal-description">${message ? message.replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/\n/g,"<br>") : '<i>No message provided.</i>'}</div>
                `;
                document.getElementById('requestDetailsModal').style.display = 'flex';

                // Attach close handler
                document.getElementById('closeRequestDetailsModal').onclick = function() {
                    document.getElementById('requestDetailsModal').style.display = 'none';
                };
            };
        });

        // Close modal on overlay click
        document.getElementById('requestDetailsModal').onclick = function(e) {
            if (e.target === this) this.style.display = 'none';
        };

        // For Requests Received
        document.querySelectorAll('.request-view-btn-received').forEach(function(btn) {
            btn.onclick = function() {
                var id = btn.getAttribute('data-request-id');
                var sender = btn.getAttribute('data-sender-email');
                var senderFirst = btn.getAttribute('data-sender-first-name');
                var senderLast = btn.getAttribute('data-sender-last-name');
                var service = btn.getAttribute('data-service');
                var status = btn.getAttribute('data-status');
                var created = btn.getAttribute('data-created-at');
                var requestTitle = btn.getAttribute('data-request-title') || '';
                var dateTime = btn.getAttribute('data-date-time') || '';
                var location = btn.getAttribute('data-location') || '';
                var wageAmount = btn.getAttribute('data-wage-amount') || '';
                var wageType = btn.getAttribute('data-wage-type') || '';
                var contactInfo = btn.getAttribute('data-contact-info') || '';
                var message = btn.getAttribute('data-message') || '';
                var statusClass = 'pending';
                if (status && status.toLowerCase() === 'accepted') statusClass = 'accepted';
                else if (status && status.toLowerCase() === 'declined') statusClass = 'declined';
                else if (status && status.toLowerCase() === 'done') statusClass = 'done';
                else if (status && status.toLowerCase() === 'confirmed') statusClass = 'confirmed';

                var modalBody = document.getElementById('requestDetailsModalBody');
                modalBody.innerHTML = `
                    <div class="request-modal-title">
                        <span class="material-icons" style="font-size:1.2em;vertical-align:middle;">visibility</span>
                        Request Details
                    </div>
                    <div class="request-modal-row">
                        <span class="request-modal-label">From:</span>
                        <span class="request-modal-value">${senderFirst} ${senderLast} (${sender})</span>
                    </div>
                    <div class="request-modal-row">
                        <span class="request-modal-label">Service:</span>
                        <span class="request-modal-value">${service || ''}</span>
                    </div>
                    <div class="request-modal-row">
                        <span class="request-modal-label">Request Title:</span>
                        <span class="request-modal-value">${requestTitle || '<i>None</i>'}</span>
                    </div>
                    <div class="request-modal-row">
                        <span class="request-modal-label">Date & Time:</span>
                        <span class="request-modal-value">${dateTime || '<i>None</i>'}</span>
                    </div>
                    <div class="request-modal-row">
                        <span class="request-modal-label">Location:</span>
                        <span class="request-modal-value">${location || '<i>None</i>'}</span>
                    </div>
                    <div class="request-modal-row">
                        <span class="request-modal-label">Wage:</span>
                        <span class="request-modal-value">${wageAmount ? '₱' + wageAmount : '<i>None</i>'} ${wageType ? ('(' + wageType + ')') : ''}</span>
                    </div>
                    <div class="request-modal-row">
                        <span class="request-modal-label">Contact Info:</span>
                        <span class="request-modal-value">${contactInfo || '<i>None</i>'}</span>
                    </div>
                    <div class="request-modal-row">
                        <span class="request-modal-label">Status:</span>
                        <span class="request-modal-value request-modal-status ${statusClass}">${status}</span>
                    </div>
                    <div class="request-modal-row">
                        <span class="request-modal-label">Message:</span>
                    </div>
                    <div class="request-modal-description">${message ? message.replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/\n/g,"<br>") : '<i>No message provided.</i>'}</div>
                `;
                document.getElementById('requestDetailsModal').style.display = 'flex';

                // Attach close handler
                document.getElementById('closeRequestDetailsModal').onclick = function() {
                    document.getElementById('requestDetailsModal').style.display = 'none';
                };
            };
        });
    });

    function updateRequestStatus(requestId, newStatus) {
        fetch('update_request_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `request_id=${requestId}&new_status=${newStatus}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Status updated!');
                window.location.reload();
            } else {
                alert(data.error || 'Failed to update status.');
            }
        });
    }

    function cancelSentRequest(requestId) {
        if (!confirm('Are you sure you want to cancel this request?')) return;
        fetch('update_request_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `request_id=${requestId}&new_status=Cancelled&sent=1`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Request cancelled!');
                window.location.reload();
            } else {
                alert(data.error || 'Failed to cancel request.');
            }
        });
    }

    function openReviewModal(requestId, action, role) {
        document.getElementById('reviewRequestId').value = requestId;
        document.getElementById('reviewAction').value = action;
        document.getElementById('reviewRole').value = role; // 'worker' or 'client'
        document.getElementById('reviewModalTitle').innerText = (action === 'Done') ? 'Mark as Done & Leave a Review' : 'Confirm & Leave a Review';
        document.getElementById('reviewModal').classList.add('active');
        // Reset star rating
        var stars = document.querySelectorAll('.star-rating input[type="radio"]');
        stars.forEach(function(star) { star.checked = false; });
    }
    document.getElementById('closeReviewModal').onclick = function() {
        document.getElementById('reviewModal').classList.remove('active');
    };
    document.getElementById('cancelReviewModal').onclick = function() {
        document.getElementById('reviewModal').classList.remove('active');
    };
    document.getElementById('reviewForm').onsubmit = function(e) {
        e.preventDefault();
        // Ensure a star is selected
        var ratingChecked = false;
        var stars = document.querySelectorAll('.star-rating input[type="radio"]');
        stars.forEach(function(star) { if (star.checked) ratingChecked = true; });
        if (!ratingChecked) {
            alert('Please select a rating.');
            return;
        }
        var formData = new FormData(this);
        fetch('update_request_status.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Review submitted!');
                window.location.reload();
            } else {
                alert(data.error || 'Failed to submit review.');
            }
        });
    };
    </script>
</body>
</html>