<?php
// about.php - About page for Skill Sphere website

// Header section
function renderHeader() {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>About Us - Skill Sphere</title>
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


// About Us Hero Section
function renderAboutHero() {
    ?>
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
    <?php
}

// Quote Section
function renderQuoteSection() {
    ?>
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
    <?php
}

// Team Section
function renderTeamSection() {
    // Array of team members with their information
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
    ?>
    <section class="team-section">
        <div class="team-header">
            <h2><span class="highlight">THE</span> TEAM</h2>
        </div>
        <div class="team-members">
            <?php foreach ($teamMembers as $member): ?>
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
    <?php
}

// Main page render
renderHeader();
renderAboutHero();
renderQuoteSection();
renderTeamSection();
renderFooter();
?>

