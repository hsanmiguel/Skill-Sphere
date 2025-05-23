<?php
// about.php - About page for Skill Sphere website

if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: entry/sign_in.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Skill Sphere</title>
    <link rel="stylesheet" href="designs/footer.css">
    <link rel="stylesheet" href="designs/about_us1.css">
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
                <li><a href="about_us.php" class="active">ABOUT</a></li>
                <li><a href="contact_us.php">CONTACT US</a></li>
                <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "superadmin"): ?>
                  <li><a href="superadmin_dashboard.php">SUPER ADMIN</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php if (isset($_SESSION["user_id"])): ?>
          <div class="user-info" style="margin-left:auto; display: flex; align-items: center; gap: 18px; font-weight:600; color:#1B4D43; padding-left: 20px;">
            <a href="user_profile.php?email=<?php echo urlencode($_SESSION['email']); ?>" style="color:#1B4D43; font-weight:600; text-decoration:none; display: flex; align-items: center; gap: 6px;">
              <span style="display:inline-flex; align-items:center;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" style="vertical-align:middle; margin-right:6px;" xmlns="http://www.w3.org/2000/svg">
                  <circle cx="10" cy="7" r="4" fill="#1B4D43"/>
                  <ellipse cx="10" cy="15" rx="7" ry="4" fill="#1B4D43"/>
                </svg>
                <?php echo htmlspecialchars(isset($_SESSION["first_name"]) ? $_SESSION["first_name"] : (isset($_SESSION["email"]) ? $_SESSION["email"] : "")); ?>
              </span>
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
    <main>
        <section class="about-hero">
            <div class="about-content">
                <h2>ABOUT <span class="highlight">US</span></h2>
                <div class="about-description">
                    <div class="about-text">
                        <p>We are a group of passionate students from Naga City, Camarines Sur that focuses on helping local communities thrive for success by making services more accessible and efficient. Skill Sphere is designed to connect people who offer services such as carpentry, baking, crocheting, and more, with community who need them.</p>
                        <p>Our portal gives individuals a space to showcase their expertise through detailed profiles, while allowing people in the community to easily search, communicate, and book services nearby. This aims to add another solution with easy access of finding reliable help while giving service providers more visibility and job opportunities.</p>
                    </div>
                    <div class="about-image">
                        <img src="assets/d_services.png" alt="People discussing services">
                    </div>
                </div>
            </div>
        </section>
        <section class="quote-section">
            <div class="quote-container">
                <div class="quote-text">
                    <blockquote>
                        "The goal as a company is to have customer service that is not just the best, but legendary."
                        <cite>- Sam Walton</cite>
                    </blockquote>
                </div>
                <div class="quote-image">
                    <img src="assets/planning_session.png" alt="Team planning session">
                </div>
            </div>
        </section>
        <section class="team-section">
            <div class="team-header">
                <h2><span class="highlight">THE</span> TEAM</h2>
            </div>
            <div class="team-members">
                <?php 
                $teamMembers = [
                    [
                        'name' => 'Larry II A. Agad',
                        'image' => 'assets/tiglarry.jpg',
                        'role' => 'Co-Founder & Developer',
                    ],
                    [
                        'name' => 'Bon Roan R. Hernandez',
                        'image' => 'assets/chambon.jpg',
                        'role' => 'Co-Founder & Designer',
                    ],
                    [
                        'name' => 'Hans Ernie V. San Miguel',
                        'image' => 'assets/hanzong.jpg',
                        'role' => 'Marketing Lead',
                    ],
                    [
                        'name' => 'Richard Adrian E. Suñas',
                        'image' => 'assets/haritz.jpg',
                        'role' => 'Product Manager',
                    ],
                ];
                foreach ($teamMembers as $member): ?>
                    <div class="member">
                        <div class="member-image">
                            <img src="<?php echo $member['image']; ?>" alt="<?php echo $member['name']; ?>">
                        </div>
                        <div class="member-info">
                            <h3><?php echo $member['name']; ?></h3>
                            <?php if (isset($member['role'])): ?>
                            <p class="role"><?php echo $member['role']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>

