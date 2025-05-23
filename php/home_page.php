<?php
// home page - Main page for Skill Sphere website

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
    <title>Skill Sphere - Find Skilled Personnel</title>
    <link rel="stylesheet" href="designs/footer.css">
    <link rel="stylesheet" href="designs/home_page1.css">
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
                <li><a href="home_page.php" class="active">HOME</a></li>
                <li><a href="services.php">SERVICES</a></li>
                <li><a href="about_us.php">ABOUT</a></li>
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
        <section class="hero">
            <div class="hero-content">
                <div class="since">SINCE 2025</div>
                <h2>EXPERIENCE FINDING <span class="skilled-key">SKILLED PERSONNEL</span> WITH<br> JUST A CLICK!</h2>
                <p>Skill Sphere is your go-to platform for finding skilled workers effortlessly. Whether you need a plumber, tutor, designer, or any service, our mission is to connect you with experienced professionals—fast, reliable, and just a click away.</p>
                <a href="entry/sign_up.php" class="btn get-started">
                    <div class="circle-icon">
                        <img src="assets/arrow_back.png" alt="Arrow Icon">
                    </div>
                    <span>GET STARTED</span>
                </a>
            </div>
        </section>
        <section class="what-we-offer">
            <h2>WHAT WE OFFER</h2>
            <div class="offer-container">
                <div class="illustration">
                    <img src="assets/offerings.png" alt="Various professionals illustration">
                </div>
                <div class="services-list">
                    <?php 
                    $services = [
                        [
                            'icon' => 'fa-user',
                            'title' => 'Personalized service provider profiles with skills, contact info, and experience',
                            'image' => 'assets/user_icon.png'
                        ],
                        [
                            'icon' => 'fa-search',
                            'title' => 'Customize query searches based on type of service',
                            'image'=> 'assets/search.png'
                        ],
                        [
                            'icon' => 'fa-location-dot',
                            'title' => 'Location-based features to connect users with nearby providers',
                            'image'=> 'assets/location.png'
                        ],
                        [
                            'icon' => 'fa-comments',
                            'title' => 'Integrated system for direct communication',
                            'image'=> 'assets/thumbs_up.png'
                        ],
                        [
                            'icon' => 'fa-check-circle',
                            'title' => 'Transparent feedback system',
                            'image'=> 'assets/check_circle.png'
                        ],
                        [
                            'icon' => 'fa-bell',
                            'title' => 'Transaction tracking for easy history viewing',
                            'image'=> 'assets/bell.png'
                        ],
                        [
                            'icon' => 'fa-bookmark',
                            'title' => 'Bookmark and filter favorites for future convenience',
                            'image'=> 'assets/bookmark.png'
                        ]
                    ];
                    foreach ($services as $service): ?>
                        <div class="service-item">
                            <div class="service-icon">
                                <img src="<?php echo $service['image']; ?>" alt="Service Icon">
                            </div>
                            <div class="service-description">
                                <?php echo $service['title']; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <section class="mission-vision">
            <div class="container">
                <div class="mission">
                    <h2>MISSION</h2>
                    <p>To create a smart, easy, and searchable platform that promotes local services, supports skilled individuals, and make new connections with one another.</p>
                </div>
                <div class="vision">
                    <h2>VISION</h2>
                    <p>To become the best community-based service platform that boost every barangay by making trusted, skilled, services accessible, efficient, and inclusive such as fostering economic growth and stronger local connections.</p>
                </div>
            </div>
        </section>
    </main>
    <?php include 'footer.php'; ?>
    <?php if (isset($_SESSION['welcome_popup'])): ?>
        <div id="welcomePopup" class="profile-feedback-popup">
            <span><?php echo htmlspecialchars($_SESSION['welcome_popup']); ?></span>
            <button onclick="document.getElementById('welcomePopup').style.display='none'" class="popup-close">&times;</button>
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
            var el = document.getElementById('welcomePopup');
            if (el) el.style.display = 'none';
        }, 4000);
        </script>
        <?php unset($_SESSION['welcome_popup']); endif; ?>
</body>
</html>

