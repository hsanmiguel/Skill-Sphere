<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: entry/sign_in.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "registered_accounts");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = isset($_GET['email']) ? $_GET['email'] : $_SESSION['email'];
$stmt = $conn->prepare("SELECT * FROM user_profiles WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$profile) {
    echo "<h2>Profile not found.</h2>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - Skill Sphere</title>
    <link rel="stylesheet" href="designs/user_profile1.css">
    <link rel="stylesheet" href="designs/header1.css">
</head>
<body>
    <header>
        <div class="logo-container">
            <a href="home_page.php" style="text-decoration: none; font-weight: bold; color: #333;"><img src="assets/logo_ss.png" alt="Skill Sphere Logo" class="logo"></a>
            <h1>Skill Sphere</h1>
        </div>
        <nav>
            <ul>
                <li><a href="home_page.php">HOME</a></li>
                <li><a href="services.php">SERVICES</a></li>
                <li><a href="about_us.php">ABOUT</a></li>
                <li><a href="contact_us.php">CONTACT US</a></li>
            </ul>
        </nav>
        <?php if (isset($_SESSION["user_id"])): ?>
          <div class="user-info" style="margin-left:auto; display: flex; align-items: center; gap: 18px; font-weight:600; color:#1B4D43; padding-left: 20px;">
            <a href="user_profile.php?email=<?php echo urlencode($_SESSION['email']); ?>" style="color:#1B4D43; font-weight:600; text-decoration:none;">
              <?php echo htmlspecialchars(isset($_SESSION["first_name"]) ? $_SESSION["first_name"] : (isset($_SESSION["email"]) ? $_SESSION["email"] : "")); ?>
            </a>
            <form method="post" action="" style="display:inline; margin:0;">
              <button type="submit" name="logout" style="margin-left:10px; background: linear-gradient(135deg, #e53935 0%, #ffb733 100%); color: #fff; border: none; border-radius: 20px; padding: 8px 18px; font-weight: 600; cursor: pointer;">Logout</button>
            </form>
          </div>
        <?php else: ?>
          <div class="join-button">
            <a href="entry/sign_up.php" class="btn">JOIN US!</a>
          </div>
        <?php endif; ?>
    </header>
    <div id="profile-container">
        <div id="profile-header">
            <h1><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></h1>
            <img src="assets/logo_ss.png" alt="Profile Picture">
        </div>
        <div id="profile-content">
            <div id="contact-info">
                <h3>Contact Information</h3>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email']); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($profile['address']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($profile['phone_number']); ?></p>
            </div>
            <div id="skills-services">
                <h3>Skills</h3>
                <p><?php echo htmlspecialchars($profile['skills']); ?></p>
                <h3>Services</h3>
                <p><?php echo htmlspecialchars($profile['services']); ?></p>
            </div>
        </div>
    </div>
</body>
</html>
