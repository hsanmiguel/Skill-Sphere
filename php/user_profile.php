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

$conn = new mysqli("localhost", "root", "", "registered_accounts");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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
    $skills = isset($_POST['skills']) ? implode(', ', array_map('trim', $_POST['skills'])) : '';
    $services = isset($_POST['services']) ? implode(', ', array_map('trim', $_POST['services'])) : '';
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
        $stmt->bind_param("sssssssss", $first_name, $last_name, $birthdate, $address, $phone_number, $skills, $services, $profile_picture_param, $profile_email);
    } else {
        $stmt = $conn->prepare("UPDATE user_profiles SET first_name=?, last_name=?, birthdate=?, address=?, phone_number=?, skills=?, services=? WHERE email=?");
        $stmt->bind_param("ssssssss", $first_name, $last_name, $birthdate, $address, $phone_number, $skills, $services, $profile_email);
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
    $reqSentQ = $conn->prepare("SELECT id, receiver_email, service, status, created_at FROM requests WHERE sender_email=? AND deleted = 0 ORDER BY created_at DESC LIMIT 5");
    $reqSentQ->bind_param("s", $user_email);
    $reqSentQ->execute();
    $reqSentQ->bind_result($id, $receiver_email, $service, $status, $created_at);
    while ($reqSentQ->fetch()) {
        $requests_sent[] = ['id'=>$id, 'receiver_email'=>$receiver_email, 'service'=>$service, 'status'=>$status, 'created_at'=>$created_at];
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
    $reqRecvQ = $conn->prepare("SELECT id, sender_email, service, status, created_at FROM requests WHERE receiver_email=? AND deleted = 0 ORDER BY created_at DESC LIMIT 5");
    $reqRecvQ->bind_param("s", $user_email);
    $reqRecvQ->execute();
    $reqRecvQ->bind_result($req_id, $sender_email, $service, $status, $created_at);
    while ($reqRecvQ->fetch()) {
        $requests_received[] = ['id'=>$req_id, 'sender_email'=>$sender_email, 'service'=>$service, 'status'=>$status, 'created_at'=>$created_at];
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
    <link rel="stylesheet" href="designs/header1.css">
    <link rel="stylesheet" href="designs/user_profile1.css">
    <link rel="stylesheet" href="designs/footer.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <div id="profile-container">
        <div id="profile-header" style="display: flex; align-items: center; gap: 40px;">
            <div style="display: flex; align-items: center;">
                <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile Picture" style="height:120px;width:120px;border-radius:50%;object-fit:cover;box-shadow:0 4px 15px rgba(0,0,0,0.08);border:3px solid #222;">
                </div>
            <div style="flex:1; display: flex; align-items: center;">
                <h1 style="margin:0;font-size:2.2rem;font-weight:700;color:#223;"><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></h1>
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
                $all_skills = [
                    'Plumbing', 'Carpentry', 'Electrical Work', 'Painting', 'Tiling', 'Roofing', 'Masonry',
                    'Welding', 'Auto Repair', 'Motorcycle Repair', 'Appliance Repair', 'Furniture Assembly',
                    'Locksmithing', 'Glass Cutting', 'Floor Installation', 'Drywall Repair', 'HVAC Repair',
                    'Gutter Cleaning', 'Pest Control', 'Septic Tank Cleaning', 'House Cleaning', 'Deep Cleaning',
                    'Window Cleaning', 'Laundry and Ironing', 'Carpet Cleaning', 'Pressure Washing', 'Pool Cleaning',
                    'Organizing (Decluttering)', 'Trash Removal', 'Upholstery Cleaning', 'Gardening', 'Landscaping',
                    'Lawn Mowing', 'Tree Trimming', 'Leaf Blowing', 'Fence Installation', 'Pesticide Application',
                    'Sprinkler Repair', 'Outdoor Painting', 'Snow Removal', 'Cooking', 'Baking', 'Catering',
                    'Food Plating', 'Kitchen Cleaning', 'Barbecuing', 'Meal Prep', 'Juice/Smoothie Making',
                    'Butchering', 'Inventory Management (Kitchen)', 'Sewing', 'Embroidery', 'Crochet', 'Knitting',
                    'Jewelry Repair', 'Shoe Repair', 'Toy Repair', 'Candle Making', 'Pottery', 'DIY Woodwork',
                    'Basic Computer Repair', 'Printer Setup', 'Wi-Fi Setup', 'Router Troubleshooting', 'Smart TV Setup',
                    'CCTV Installation', 'Alarm System Setup', 'Cable Management', 'Gadget Troubleshooting',
                    'Software Installation', 'Childcare', 'Elderly Care', 'Special Needs Assistance', 'Basic First Aid',
                    'Medication Reminders', 'Feeding Assistance', 'Companion Care', 'Diaper Changing', 'Bathing Assistance',
                    'Bedside Support', 'Grocery Shopping', 'Running Errands', 'Pet Walking', 'Pet Bathing',
                    'Cooking for Elders', 'House Sitting', 'Plant Watering', 'Mail Sorting', 'Light Decoration (Holidays)',
                    'Delivery Assistance', 'Sign Painting', 'Basic Graphic Design', 'Poster Making', 'Event Setup',
                    'Balloon Arrangement', 'Face Painting', 'Sound System Setup', 'Stage Decoration', 'Costume Repair',
                    'Recycling Management'
                ];
                $all_services = $all_skills; // For simplicity, use the same list for services
                $user_skills = array_map('trim', explode(',', $profile['skills']));
                $user_services = array_map('trim', explode(',', $profile['services']));
                ?>
                <label>Skills:
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; max-height: 160px; overflow-y: auto; border: 1px solid #bbb; border-radius: 8px; padding: 10px; background: #f8f9fa;">
                        <?php
                        $skill_emojis = [
                            'Plumbing' => '🔩', 'Carpentry' => '🪚', 'Electrical Work' => '⚡', 'Painting' => '🎨', 'Tiling' => '🧱', 'Roofing' => '🏠', 'Masonry' => '🛠️',
                            'Welding' => '🔥', 'Auto Repair' => '🚗', 'Motorcycle Repair' => '🏍️', 'Appliance Repair' => '🔌', 'Furniture Assembly' => '🪑',
                            'Locksmithing' => '🔑', 'Glass Cutting' => '🔪', 'Floor Installation' => '🪵', 'Drywall Repair' => '🛠️', 'HVAC Repair' => '❄️',
                            'Gutter Cleaning' => '🧹', 'Pest Control' => '🐜', 'Septic Tank Cleaning' => '🚽', 'House Cleaning' => '🏠', 'Deep Cleaning' => '🧼',
                            'Window Cleaning' => '🪟', 'Laundry and Ironing' => '👕', 'Carpet Cleaning' => '🧽', 'Pressure Washing' => '💦', 'Pool Cleaning' => '🏊',
                            'Organizing (Decluttering)' => '📦', 'Trash Removal' => '🗑️', 'Upholstery Cleaning' => '🛋️', 'Gardening' => '🌱', 'Landscaping' => '🌳',
                            'Lawn Mowing' => '🌾', 'Tree Trimming' => '✂️', 'Leaf Blowing' => '🍂', 'Fence Installation' => '🛠️', 'Pesticide Application' => '🪲',
                            'Sprinkler Repair' => '💧', 'Outdoor Painting' => '🎨', 'Snow Removal' => '❄️', 'Cooking' => '🍳', 'Baking' => '🧁', 'Catering' => '🍽️',
                            'Food Plating' => '🍲', 'Kitchen Cleaning' => '🧽', 'Barbecuing' => '🍖', 'Meal Prep' => '🥗', 'Juice/Smoothie Making' => '🥤',
                            'Butchering' => '🔪', 'Inventory Management (Kitchen)' => '📦', 'Sewing' => '🧵', 'Embroidery' => '🪡', 'Crochet' => '🧶', 'Knitting' => '🧶',
                            'Jewelry Repair' => '💍', 'Shoe Repair' => '👞', 'Toy Repair' => '🧸', 'Candle Making' => '🕯️', 'Pottery' => '🏺', 'DIY Woodwork' => '🪵',
                            'Basic Computer Repair' => '💻', 'Printer Setup' => '🖨️', 'Wi-Fi Setup' => '📶', 'Router Troubleshooting' => '📡', 'Smart TV Setup' => '📺',
                            'CCTV Installation' => '📹', 'Alarm System Setup' => '🚨', 'Cable Management' => '🔌', 'Gadget Troubleshooting' => '🔧',
                            'Software Installation' => '💾', 'Childcare' => '👶', 'Elderly Care' => '🧓', 'Special Needs Assistance' => '♿', 'Basic First Aid' => '⛑️',
                            'Medication Reminders' => '💊', 'Feeding Assistance' => '🍽️', 'Companion Care' => '🤝', 'Diaper Changing' => '🧷', 'Bathing Assistance' => '🛁',
                            'Bedside Support' => '🛏️', 'Grocery Shopping' => '🛒', 'Running Errands' => '🏃', 'Pet Walking' => '🐕', 'Pet Bathing' => '🛁',
                            'Cooking for Elders' => '🍲', 'House Sitting' => '🏠', 'Plant Watering' => '💧', 'Mail Sorting' => '📬', 'Light Decoration (Holidays)' => '🎉',
                            'Delivery Assistance' => '📦', 'Sign Painting' => '🖌️', 'Basic Graphic Design' => '🖥️', 'Poster Making' => '📰', 'Event Setup' => '🎪',
                            'Balloon Arrangement' => '🎈', 'Face Painting' => '🎭', 'Sound System Setup' => '🔊', 'Stage Decoration' => '🎤', 'Costume Repair' => '👗',
                            'Recycling Management' => '♻️'
                        ];
                        foreach ($all_skills as $skill):
                            $emoji = isset($skill_emojis[$skill]) ? $skill_emojis[$skill] : '';
                        ?>
                            <label style="min-width: 180px; display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                                <input type="checkbox" name="skills[]" value="<?php echo htmlspecialchars($skill); ?>" <?php if (in_array($skill, $user_skills)) echo 'checked'; ?>>
                                <span><?php echo $emoji . ' ' . htmlspecialchars($skill); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </label><br>
                <label>Services:
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; max-height: 160px; overflow-y: auto; border: 1px solid #bbb; border-radius: 8px; padding: 10px; background: #f8f9fa;">
                        <?php
                        foreach ($all_services as $service):
                            $emoji = isset($skill_emojis[$service]) ? $skill_emojis[$service] : '';
                        ?>
                            <label style="min-width: 180px; display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                                <input type="checkbox" name="services[]" value="<?php echo htmlspecialchars($service); ?>" <?php if (in_array($service, $user_services)) echo 'checked'; ?>>
                                <span><?php echo $emoji . ' ' . htmlspecialchars($service); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </label><br>
                <button type="submit" name="save_profile" class="edit-profile-btn">Save</button>
                <a href="user_profile.php?email=<?php echo urlencode($profile_email); ?>" style="margin-left:10px;">Cancel</a>
            </form>
            <?php else: ?>
            <div id="contact-info">
                <h3>Contact Information</h3>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email']); ?></p>
                <p><strong>Birthdate:</strong> <?php echo isset($profile['birthdate']) ? htmlspecialchars($profile['birthdate']) : 'N/A'; ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($profile['address']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($profile['phone_number']); ?></p>
            </div>
            <div id="skills-services">
                <h3>Skills</h3>
                <p><?php echo htmlspecialchars($profile['skills']); ?></p>
                <h3>Services</h3>
                <p><?php echo htmlspecialchars($profile['services']); ?></p>
            </div>
            <?php if ($isOwner): ?>
            <div style="margin-top:20px; display: flex; gap: 14px; justify-content: flex-start; align-items: center;">
                <button type="button" class="edit-profile-btn edit-btn" id="openEditProfileModal">✏️ Edit Profile</button>
                <button type="button" class="edit-profile-btn trash-btn" id="openTrashModal">🗑️ Trash / History</button>
                <button type="button" class="edit-profile-btn history-btn" id="openHistoryModal">📜 Full History</button>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php if (trim(strtolower($profile_email)) === trim(strtolower($user_email))): ?>
        <div id="profile-dashboard">
            <div class="dashboard-inner">
                <div class="dashboard-col">
                    <div class="card notif-card" style="background: #e3f2fd;">
                        <h3>Notifications</h3>
                        <?php if (!empty($notifications)): ?>
                            <ul class="notif-list">
                                <?php foreach ($notifications as $notif): ?>
                                    <li class="notif-item notif-<?php echo htmlspecialchars($notif['type']); ?>">
                                        <span class="notif-message"><?php echo htmlspecialchars($notif['message']); ?></span>
                                        <span class="notif-date"><?php echo htmlspecialchars($notif['created_at']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="empty-msg">No notifications yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="dashboard-col">
                    <div class="card sent-card" style="background: #f1f8e9;">
                        <h3>Requests Sent</h3>
                        <?php if (!empty($requests_sent)): ?>
                            <ul class="request-list">
                                <?php foreach ($requests_sent as $req): ?>
                                    <li style="display: flex; align-items: center; gap: 12px;">
                                        <div class="avatar" style="width:36px;height:36px;border-radius:50%;background:#1B4D43;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1em;">
                                            <?php echo strtoupper(substr($req['receiver_email'],0,1)); ?>
                                        </div>
                                        <div>
                                            <span class="req-to">To: <b><?php echo htmlspecialchars($req['receiver_email']); ?></b></span><br>
                                            <span class="req-service">Service: <b><?php echo htmlspecialchars($req['service']); ?></b></span><br>
                                            <span class="req-status <?php echo strtolower($req['status']); ?>">Status: <?php echo htmlspecialchars($req['status']); ?></span><br>
                                            <span class="notif-date"><?php echo htmlspecialchars($req['created_at']); ?></span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="empty-msg">No requests sent yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="dashboard-col">
                    <div class="card recv-card" style="background: #fffde7;">
                        <h3>Requests Received</h3>
                        <?php if (!empty($requests_received)): ?>
                            <ul class="request-list">
                                <?php foreach ($requests_received as $req): ?>
                                    <li style="display: flex; align-items: center; gap: 12px;">
                                        <div class="avatar" style="width:36px;height:36px;border-radius:50%;background:#ffa500;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1em;">
                                            <?php echo strtoupper(substr($req['sender_email'],0,1)); ?>
                                        </div>
                                        <div>
                                            <span class="req-from">From: <b><?php echo htmlspecialchars($req['sender_email']); ?></b></span><br>
                                            <span class="req-service">Service: <b><?php echo htmlspecialchars($req['service']); ?></b></span><br>
                                            <span class="req-status <?php echo strtolower($req['status']); ?>">Status: <?php echo htmlspecialchars($req['status']); ?></span><br>
                                            <span class="notif-date"><?php echo htmlspecialchars($req['created_at']); ?></span>
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
    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <button class="modal-close" id="closeEditProfileModal">&times;</button>
            <div id="editProfileModalBody">Loading...</div>
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
    <style>
    body {
        background: #f6f7f9;
    }
    #profile-dashboard {
        margin-top: 40px;
        width: 100%;
        display: flex;
        justify-content: center;
        background: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        padding: 0 !important;
        border: none !important;
    }
    .dashboard-inner {
        display: flex;
        gap: 32px;
        width: 100%;
        max-width: 1200px;
        justify-content: center;
        align-items: stretch;
        background: none;
        box-shadow: none;
        border-radius: 0;
        padding: 0;
    }
    .dashboard-col {
        flex: 1 1 0;
        min-width: 320px;
        display: flex;
        flex-direction: column;
        align-items: stretch;
    }
    .card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        padding: 28px 24px 20px 24px;
        margin-bottom: 0;
        min-height: 340px;
        display: flex;
        flex-direction: column;
        align-items: stretch;
    }
    .card h3 {
        margin-top: 0;
        margin-bottom: 18px;
        color: #1B4D43;
        font-size: 1.3rem;
        font-weight: 700;
        letter-spacing: 1px;
        text-align: center;
    }
    .notif-list, .request-list {
        list-style: none;
        padding: 0;
        margin: 0;
        width: 100%;
    }
    .notif-item, .request-list li {
        padding: 12px 10px;
        margin-bottom: 10px;
        border-radius: 8px;
        background: #f8f9fa;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
        font-size: 1.01rem;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        word-break: break-word;
    }
    .notif-item > *, .request-list li > * {
        margin-bottom: 4px;
        max-width: 100%;
    }
    .notif-item > form,
    .request-list li > form {
        margin-bottom: 0;
    }
    .notif-message, .req-to, .req-from, .req-service, .req-status, .notif-date {
        display: block;
        margin-bottom: 2px;
        max-width: 100%;
    }
    .req-status.accepted { color: #388e3c; font-weight: 600; }
    .req-status.declined { color: #d32f2f; font-weight: 600; }
    .req-status.pending { color: #fbc02d; font-weight: 600; }
    .empty-msg {
        color: #888;
        text-align: center;
        margin: 30px 0 0 0;
        font-size: 1.05rem;
    }
    .inline-form {
        display: inline;
        margin: 0;
    }
    .delete-btn {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1.2em;
        padding: 2px 5px;
        transition: transform 0.2s;
        color: #d32f2f;
    }
    .delete-btn:hover {
        transform: scale(1.2);
        color: #b71c1c;
    }
    .edit-profile-btn.accept-btn {
        background: #4CAF50;
        color: white;
        border: none;
        padding: 5px 15px;
        border-radius: 4px;
        cursor: pointer;
        margin-right: 4px;
        font-size: 1em;
    }
    .edit-profile-btn.decline-btn {
        background: #f44336;
        color: white;
        border: none;
        padding: 5px 15px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 1em;
    }
    .restore-btn {
        background: none;
        border: none;
        color: #388e3c;
        font-size: 1em;
        cursor: pointer;
        margin-left: 6px;
        padding: 2px 8px;
        border-radius: 4px;
        transition: background 0.2s, color 0.2s;
    }
    .restore-btn:hover {
        background: #e8f5e9;
        color: #1B4D43;
    }
    .history-section {
        margin-top: 18px;
        padding-top: 10px;
        border-top: 1px solid #e0e0e0;
    }
    .history-section h4 {
        margin: 0 0 8px 0;
        font-size: 1.05rem;
        color: #888;
        font-weight: 600;
    }
    @media (max-width: 1100px) {
        .dashboard-inner {
            flex-direction: column;
            gap: 24px;
            max-width: 98vw;
        }
        .dashboard-col {
            min-width: 0;
        }
    }
    .edit-profile-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #1B4D43 0%, #4CAF50 100%);
        color: #fff;
        border: none;
        border-radius: 999px;
        padding: 4px 22px;
        font-weight: 500;
        cursor: pointer;
        font-size: 0.98em;
        transition: background 0.2s, color 0.2s;
        min-width: 140px;
        min-height: 36px;
        box-sizing: border-box;
        gap: 7px;
        text-decoration: none;
    }
    .edit-profile-btn.edit-btn {
        background: linear-gradient(135deg, #1B4D43 0%, #4CAF50 100%);
        color: #fff;
    }
    .edit-profile-btn.edit-btn:hover {
        background: linear-gradient(135deg, #388e3c 0%, #1B4D43 100%);
        color: #fff;
    }
    .edit-profile-btn.trash-btn {
        background: linear-gradient(135deg, #fbc02d 0%, #ffe082 100%);
        color: #222;
    }
    .edit-profile-btn.trash-btn:hover {
        background: linear-gradient(135deg, #ffe082 0%, #fbc02d 100%);
        color: #222;
    }
    .edit-profile-btn.history-btn {
        background: linear-gradient(135deg, #90caf9 0%, #e3f2fd 100%);
        color: #1B4D43;
    }
    .edit-profile-btn.history-btn:hover {
        background: linear-gradient(135deg, #e3f2fd 0%, #90caf9 100%);
        color: #1B4D43;
    }
    .modal-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.32);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: opacity 0.2s;
    }
    .modal-content {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 4px 32px rgba(0,0,0,0.18);
        padding: 32px 28px 24px 28px;
        max-width: 700px;
        width: 98vw;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
        animation: modalIn 0.18s;
    }
    @keyframes modalIn {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    .modal-close {
        position: absolute;
        top: 12px;
        right: 18px;
        background: none;
        border: none;
        font-size: 2rem;
        color: #888;
        cursor: pointer;
        z-index: 2;
        transition: color 0.2s;
    }
    .modal-close:hover { color: #d32f2f; }
    </style>
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
    // Edit Profile Modal
    function closeEditProfileModal() {
        document.getElementById('editProfileModal').style.display = 'none';
        // Optionally reload page to show updated info
        window.location.reload();
    }
    document.getElementById('openEditProfileModal').onclick = function() {
        document.getElementById('editProfileModal').style.display = 'flex';
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
    </script>
</body>
</html>
