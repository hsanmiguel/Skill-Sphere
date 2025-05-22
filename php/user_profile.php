<?php
// Fetch user profile from the database using the id from the GET parameter
$host = 'localhost';
$dbname = 'registered_accounts';
$username = 'root';
$password = '';
$conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM user_profiles WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $profile = false;
}

if ($profile) {
    $firstName = $profile['first_name'];
    $lastName = $profile['last_name'];
    $address = $profile['address'];
    $rating = isset($profile['rating']) ? $profile['rating'] : 'N/A';
    $phoneNumber = isset($profile['phone_number']) ? $profile['phone_number'] : '';
    $email = isset($profile['email']) ? $profile['email'] : '';
    $socialMedia = isset($profile['social_media']) ? $profile['social_media'] : '';
    $yearsOfExperience = isset($profile['years_of_experience']) ? $profile['years_of_experience'] : '';
    $skills = isset($profile['skills']) ? explode(',', $profile['skills']) : [];
    // You can fetch reviews from another table if needed
    $reviews = [];
} else {
    $firstName = $lastName = $address = $rating = $phoneNumber = $email = $socialMedia = $yearsOfExperience = '';
    $skills = [];
    $reviews = [];
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skill Sphere - Profile</title>
    <link rel="stylesheet" href="designs/header1.css">
    <link rel="stylesheet" href="designs/profile_dashboard.css">
</head>
<body>
    <header class="dashboard-header">
        <div class="logo-container">
            <a href="home_page.php" style="text-decoration: none; font-weight: bold; color: #fff;"><img src="assets/logo_ss.png" alt="Skill Sphere Logo" class="logo" style="background: none;"></a>
            <h1 style="color: #fff;">Skill Sphere</h1>
        </div>
        <nav>
            <ul>
                <li><a href="home_page.php">Home</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="user_profile.php" class="active">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    <main class="dashboard-main">
        <div class="dashboard-col dashboard-profile">
            <div class="profile-card">
                <div class="profile-avatar"><img src="assets/profile_default.png" alt="Profile" /></div>
                <h2 class="profile-name"><?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></h2>
                <div class="profile-location"><span class="icon">📍</span> <?php echo htmlspecialchars($address); ?></div>
                <div class="profile-services"><b>Services:</b> <?php echo htmlspecialchars(implode(', ', $skills)); ?></div>
                <a href="edit_profile.php" class="edit-profile-btn">Edit Profile</a>
            </div>
        </div>
        <div class="dashboard-col dashboard-requests">
            <div class="card">
                <h2 class="card-title">Requests I Received</h2>
                <div class="request-item" style="background:#f7f8fa;">
                    <div><b>From:</b> Maria Santos</div>
                    <div><b>Service:</b> Plumbing</div>
                    <div><b>Date:</b> May 22, 2025</div>
                    <div><b>Message:</b> Urgent pipe leak. Can you come today?</div>
                    <div class="request-actions">
                        <button class="accept-btn">✔ Accept</button>
                        <button class="decline-btn">✖ Decline</button>
                    </div>
                </div>
            </div>
            <div class="card">
                <h2 class="card-title">Requests I Sent</h2>
                <div class="request-item" style="background:#f7f8fa;">
                    <div><b>To:</b> Mark Reyes</div>
                    <div><b>Service:</b> Electrical Repair</div>
                    <div><b>Status:</b> Pending</div>
                </div>
            </div>
        </div>
        <div class="dashboard-col dashboard-notifications">
            <div class="card">
                <h2 class="card-title">Notifications</h2>
                <div class="notification-item success">✔ Juan hired you!</div>
                <div class="notification-item error">✖ Mark declined your request</div>
                <div class="notification-item info">✏️ Reminder: Add service location</div>
            </div>
        </div>
    </main>
    <footer>
        <div class="footer-links">
            <a href="security-privacy.php">Security & Privacy</a>
            <a href="terms.php">Terms & Conditions</a>
            <a href="contact_us.php">Contact</a>
        </div>
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> Skill Sphere. All rights reserved.
        </div>
    </footer>
</body>
</html>
