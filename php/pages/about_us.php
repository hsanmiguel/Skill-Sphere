<?php
// about.php - About page for Skill Sphere website

if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../auth/sign_in.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Skill Sphere</title>
    <link rel="stylesheet" href="../designs/header1.css">
    <link rel="stylesheet" href="../designs/about_us1.css">
    <link rel="stylesheet" href="../designs/footer.css">
</head>
<body>
    <?php include '../components/header.php'; ?>
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
                        <img src="../assets/d_services.png" alt="People discussing services">
                    </div>
                </div>
            </div>
        </section>
        <section class="about-main">
            <div class="about-main-inner">
                <div class="quote-section">
                    <blockquote>
                        "The goal as a company is to have customer service that is not just the best, but legendary."
                        <cite>- Sam Walton</cite>
                    </blockquote>
                    <img src="../assets/planning_session.png" alt="Team planning session" class="quote-image">
                </div>
                <div class="team-section">
                    <h2><span class="highlight">THE</span> TEAM</h2>
                    <div class="team-members">
                        <?php 
                        $teamMembers = [
                            [
                                'name' => 'Larry II A. Agad',
                                'image' => '../assets/tiglarry.jpg',
                                'role' => 'Co-Founder & Developer',
                            ],
                            [
                                'name' => 'Bon Roan R. Hernandez',
                                'image' => '../assets/chambon.jpg',
                                'role' => 'Co-Founder & Designer',
                            ],
                            [
                                'name' => 'Hans Ernie V. San Miguel',
                                'image' => '../assets/hanzong.jpg',
                                'role' => 'Marketing Lead',
                            ],
                            [
                                'name' => 'Richard Adrian E. Suñas',
                                'image' => '../assets/haritz.jpg',
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
                </div>
            </div>
        </section>
    </main>
    <?php include '../components/footer.php'; ?>
</html>

