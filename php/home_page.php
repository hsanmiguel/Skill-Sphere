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
    <?php include 'header.php'; ?>
    <main>
        <section class="hero">
            <div class="hero-content">
                <div class="since">SINCE 2025</div>
                <h2>EXPERIENCE FINDING <span class="skilled-key">SKILLED PERSONNEL</span> WITH JUST A CLICK!</h2>
                <p>Skill Sphere is your go-to platform for finding skilled workers effortlessly. Whether you need a plumber, tutor, designer, or any service, our mission is to connect you with experienced professionals—fast, reliable, and just a click away.</p>
                <a href="entry/sign_up.php" class="get-started">
                    <div class="circle-icon">
                        <img src="assets/arrow_back.png" alt="Arrow Icon">
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
                        <img src="assets/user_icon.png" alt="User Profile">
                        <h3 class="offer-feature-title">Personalized Profiles</h3>
                        <p class="offer-feature-desc">Detailed service provider profiles with skills, contact info, and experience</p>
                    </div>
                    <div class="offer-feature">
                        <img src="assets/search.png" alt="Search">
                        <h3 class="offer-feature-title">Custom Search</h3>
                        <p class="offer-feature-desc">Customize query searches based on type of service</p>
                    </div>
                    <div class="offer-feature">
                        <img src="assets/location.png" alt="Location">
                        <h3 class="offer-feature-title">Location Based</h3>
                        <p class="offer-feature-desc">Connect with nearby service providers</p>
                    </div>
                    <div class="offer-feature">
                        <img src="assets/thumbs_up.png" alt="Communication">
                        <h3 class="offer-feature-title">Direct Communication</h3>
                        <p class="offer-feature-desc">Integrated system for direct messaging</p>
                    </div>
                    <div class="offer-feature">
                        <img src="assets/check_circle.png" alt="Feedback">
                        <h3 class="offer-feature-title">Feedback System</h3>
                        <p class="offer-feature-desc">Transparent rating and review system</p>
                    </div>
                    <div class="offer-feature">
                        <img src="assets/bell.png" alt="Tracking">
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

