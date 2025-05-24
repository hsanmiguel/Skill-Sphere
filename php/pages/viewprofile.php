<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../components/header.php';

// Connect to database
$conn = new mysqli("localhost", "root", "", "registered_accounts");
if ($conn->connect_error) {
    echo '<h2>Database connection error.</h2>';
    include '../components/footer.php';
    exit();
}

// Get user by id or email
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$email = isset($_GET['email']) ? $_GET['email'] : '';

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM user_profiles WHERE id = ?");
    $stmt->bind_param("i", $id);
} elseif (!empty($email)) {
    $stmt = $conn->prepare("SELECT * FROM user_profiles WHERE email = ?");
    $stmt->bind_param("s", $email);
} else {
    echo '<h2>No user specified.</h2>';
    include '../components/footer.php';
    exit();
}

$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$profile) {
    echo '<h2>User profile not found.</h2>';
    include '../components/footer.php';
    exit();
}

$profilePic = !empty($profile['profile_picture']) && file_exists(__DIR__.'/../uploads/' . $profile['profile_picture'])
    ? '/Skill-Sphere/php/uploads/' . htmlspecialchars($profile['profile_picture'])
    : '/Skill-Sphere/php/assets/logo_ss.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Profile - <?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></title>
    <link rel="stylesheet" href="/Skill-Sphere/php/designs/header1.css">
    <link rel="stylesheet" href="/Skill-Sphere/php/designs/user_profile1.css">
    <link rel="stylesheet" href="/Skill-Sphere/php/designs/footer.css">
    <style>
        .view-profile-container { max-width: 700px; margin: 40px auto; background: #fff; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,0.07); padding: 40px 32px; }
        .view-profile-header { display: flex; align-items: center; gap: 32px; margin-bottom: 32px; }
        .view-profile-header img { height: 120px; width: 120px; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border: 3px solid #222; }
        .view-profile-header h1 { margin: 0; font-size: 2.2rem; font-weight: 700; color: #223; }
        .view-profile-section { margin-bottom: 18px; }
        .view-profile-section h3 { color: #1B4D43; font-size: 1.15rem; font-weight: 700; margin-bottom: 8px; }
        .view-profile-section p { color: #333; margin: 0 0 8px 0; }
    </style>
</head>
<body>
<div class="view-profile-container">
    <div class="view-profile-header">
        <img src="<?php echo $profilePic; ?>" alt="Profile Picture">
        <h1><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></h1>
    </div>
    <div class="view-profile-section">
        <h3>Contact Information</h3>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email']); ?></p>
        <p><strong>Address:</strong> <?php echo htmlspecialchars($profile['address']); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($profile['phone_number']); ?></p>
    </div>
    <div class="view-profile-section">
        <h3>Skills</h3>
        <p><?php echo htmlspecialchars($profile['skills']); ?></p>
    </div>
    <div class="view-profile-section">
        <h3>Services</h3>
        <p><?php echo htmlspecialchars($profile['services']); ?></p>
    </div>
    <?php if (isset($profile['experience']) && $profile['experience'] !== ''): ?>
    <div class="view-profile-section">
        <h3>Years of Experience</h3>
        <p><?php echo htmlspecialchars($profile['experience']); ?> year<?php echo ($profile['experience'] == 1 ? '' : 's'); ?></p>
    </div>
    <?php endif; ?>
</div>
<?php include '../components/footer.php'; ?>
</body>
</html>
