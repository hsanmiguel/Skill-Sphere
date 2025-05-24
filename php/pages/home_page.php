<?php
// home page - Main page for Skill Sphere website

if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: /Skill-Sphere/php/entry/sign_in.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Skill Sphere</title>
    <link rel="stylesheet" href="/Skill-Sphere/php/designs/header1.css">
    <link rel="stylesheet" href="/Skill-Sphere/php/designs/shared.css">
    <link rel="stylesheet" href="/Skill-Sphere/php/designs/home_page1.css">
    <link rel="stylesheet" href="/Skill-Sphere/php/designs/footer.css">
</head>
<body>
    <?php include '../components/header.php'; ?>
    <main>
        <section class="hero">
            <div class="hero-content">
                <div class="since">SINCE 2025</div>
                <h2>EXPERIENCE FINDING <span class="skilled-key">SKILLED PERSONNEL</span> WITH JUST A CLICK!</h2>
                <p>Skill Sphere is your go-to platform for finding skilled workers effortlessly. Whether you need a plumber, tutor, designer, or any service, our mission is to connect you with experienced professionals—fast, reliable, and just a click away.</p>
                <a href="/Skill-Sphere/php/entry/sign_up.php" class="get-started">
                    <div class="circle-icon">
                        <img src="/Skill-Sphere/php/assets/arrow_back.png" alt="Arrow Icon">
                    </div>
                    <span>GET STARTED</span>
                </a>
            </div>
        </section>
        <section class="what-we-offer">
            <div class="what-we-offer-inner">
                <h2>WHAT WE OFFER</h2>
                <div class="offer-features-grid">
                    <div class="offer-feature">
                        <img src="/Skill-Sphere/php/assets/user_icon.png" alt="User Profile">
                        <h3 class="offer-feature-title">Personalized Profiles</h3>
                        <p class="offer-feature-desc">Detailed service provider profiles with skills, contact info, and experience</p>
                    </div>
                    <div class="offer-feature">
                        <img src="/Skill-Sphere/php/assets/search.png" alt="Search">
                        <h3 class="offer-feature-title">Custom Search</h3>
                        <p class="offer-feature-desc">Customize query searches based on type of service</p>
                    </div>
                    <div class="offer-feature">
                        <img src="/Skill-Sphere/php/assets/location.png" alt="Location">
                        <h3 class="offer-feature-title">Location Based</h3>
                        <p class="offer-feature-desc">Connect with nearby service providers</p>
                    </div>
                    <div class="offer-feature">
                        <img src="/Skill-Sphere/php/assets/thumbs_up.png" alt="Communication">
                        <h3 class="offer-feature-title">Direct Communication</h3>
                        <p class="offer-feature-desc">Integrated system for direct messaging</p>
                    </div>
                    <div class="offer-feature">
                        <img src="/Skill-Sphere/php/assets/check_circle.png" alt="Feedback">
                        <h3 class="offer-feature-title">Feedback System</h3>
                        <p class="offer-feature-desc">Transparent rating and review system</p>
                    </div>
                    <div class="offer-feature">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 22C13.1 22 14 21.1 14 20H10C10 21.1 10.9 22 12 22ZM18 16V11C18 7.93 16.37 5.36 13.5 4.68V4C13.5 3.17 12.83 2.5 12 2.5C11.17 2.5 10.5 3.17 10.5 4V4.68C7.64 5.36 6 7.92 6 11V16L4 18V19H20V18L18 16ZM16 17H8V11C8 8.52 9.51 6.5 12 6.5C14.49 6.5 16 8.52 16 11V17Z" fill="#1B4D43"/>
                        </svg>
                        <h3 class="offer-feature-title">Transaction Tracking</h3>
                        <p class="offer-feature-desc">Easy history viewing and management</p>
                    </div>
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
    <?php include '../components/footer.php'; ?>
    <?php if (isset($_SESSION['welcome_popup'])): ?>
        <div id="welcomePopup" class="profile-feedback-popup">
            <span><?php echo htmlspecialchars($_SESSION['welcome_popup']); ?></span>
            <button onclick="document.getElementById('welcomePopup').style.display='none'" class="popup-close">&times;</button>
        </div>
        <script>
        setTimeout(function(){
            var el = document.getElementById('welcomePopup');
            if (el) el.style.display = 'none';
        }, 4000);
        </script>
        <?php unset($_SESSION['welcome_popup']); endif; ?>
</body>
</html>

