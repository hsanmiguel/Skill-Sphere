<?php
// home page - Main page for Skill Sphere website

// Header section
function renderHeader() {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Skill Sphere - Find Skilled Personnel</title>
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
                </ul>
            </nav>
            <div class="join-button">
                <a href="entry/sign_up.php" class="btn">JOIN US!</a>
            </div>
        </header>
    <?php
}

// Footer section
function renderFooter() {
    ?>
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
        <script src="js/main.js"></script>
    </body>
    </html>
    <?php
}

// Hero section
function renderHero() {
    ?>
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
    <?php
}

// What We Offer section
function renderWhatWeOffer() {
    // Array of services with their icons and descriptions
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
    ?>
    <section class="what-we-offer">
    <h2>WHAT WE OFFER</h2>
    <div class="offer-container">
        <div class="illustration">
            <img src="assets/offerings.png" alt="Various professionals illustration">
        </div>
        <div class="services-list">
            <?php foreach ($services as $service): ?>
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

    <?php
}

// Mission and Vision section
function renderMissionVision() {
    ?>
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
    <?php
}

// Main page render
renderHeader();
renderHero();
renderWhatWeOffer();
renderMissionVision();
renderFooter();
?>

