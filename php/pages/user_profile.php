<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Redirect if not logged in
if (!isset($_SESSION['email'])) {
    header("Location: /Skill-Sphere/php/entry/sign_in.php");
    exit();
}

// Include notifications handler
require_once '../components/notifications_handler.php';

// Database connection
$conn = new mysqli('localhost', 'root', '', 'registered_accounts');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch skills and services with null handling
$skills = !empty($user['skills']) ? explode(", ", $user['skills']) : [];
$services = !empty($user['selected_service']) ? explode(", ", $user['selected_service']) : [];

$stmt->close();
$conn->close();

// Display any pending notifications
$notifications = getNotifications();
foreach ($notifications as $notification) {
    displayNotification($notification['message'], $notification['type']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Skill Sphere</title>
    <link rel="stylesheet" href="/Skill-Sphere/php/designs/header1.css">
    <link rel="stylesheet" href="/Skill-Sphere/php/designs/shared.css">
    <link rel="stylesheet" href="/Skill-Sphere/php/designs/user_profile1.css">
    <link rel="stylesheet" href="/Skill-Sphere/php/designs/footer.css">
</head>
<body>
    <?php include '../components/header.php'; ?>
    
    <main class="dashboard-container">
        <div class="dashboard-header">
            <h1>Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</h1>
            <p>Manage your profile and view your skills and services</p>
        </div>

        <div class="dashboard-grid">
            <!-- Profile Details Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>Profile Details</h2>
                    <button type="button" class="edit-btn" onclick="openEditProfileModal()">
                        Edit Profile
                    </button>
                </div>
                <div class="profile-details">
                    <div class="detail-group">
                        <div class="detail-label">Full Name</div>
                        <div class="detail-value"><?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['mi'] ? $user['mi'] . '. ' : '') . $user['last_name']); ?></div>
                    </div>
                    <div class="detail-group">
                        <div class="detail-label">Email</div>
                        <div class="detail-value"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div class="detail-group">
                        <div class="detail-label">Phone</div>
                        <div class="detail-value"><?php echo htmlspecialchars($user['phone_number'] ?? 'Not provided'); ?></div>
                    </div>
                    <div class="detail-group">
                        <div class="detail-label">Address</div>
                        <div class="detail-value"><?php echo htmlspecialchars($user['address'] ?? 'Not provided'); ?></div>
                    </div>
                    <div class="detail-group">
                        <div class="detail-label">Social Media</div>
                        <div class="detail-value"><?php echo htmlspecialchars($user['social_media'] ?? 'Not provided'); ?></div>
                    </div>
                    <div class="detail-group">
                        <div class="detail-label">Experience</div>
                        <div class="detail-value">
                            <span class="experience-badge">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z" fill="currentColor"/>
                                </svg>
                                <?php echo htmlspecialchars($user['experience'] ?? '0'); ?> years
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Skills & Services Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>Skills & Services</h2>
                    <button type="button" class="edit-btn" onclick="openSkillsModal()">
                        Edit Skills
                    </button>
                </div>
                <div class="skills-services-grid">
                    <div>
                        <h3 class="section-title">Skills</h3>
                        <div class="skills-list">
                            <?php if (!empty($skills)): ?>
                                <?php foreach ($skills as $skill): ?>
                                    <?php if (trim($skill)): ?>
                                        <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="empty-message">No skills added yet</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <h3 class="section-title">Services</h3>
                        <div class="services-list">
                            <?php if (!empty($services)): ?>
                                <?php foreach ($services as $service): ?>
                                    <?php if (trim($service)): ?>
                                        <span class="service-tag"><?php echo htmlspecialchars(trim($service)); ?></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="empty-message">No services added yet</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../components/footer.php'; ?>
    <?php include '../components/edit_profile_modal.php'; ?>

    <script>
    function openEditProfileModal() {
        document.getElementById('editProfileModalOverlay').style.display = 'flex';
    }

    function openSkillsModal() {
        // Reuse the same popup from setup.php for skills editing
        document.getElementById('popup-overlay').style.display = 'flex';
    }
    </script>
</body>
</html>