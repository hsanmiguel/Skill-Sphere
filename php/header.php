<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<header>
    <div class="logo-container">
        <a href="home_page.php"><img src="assets/logo_ss.png" alt="Skill Sphere Logo" class="logo"></a>
        <h1>Skill Sphere</h1>
    </div>
    <nav>
        <ul>
            <li><a href="home_page.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'home_page.php' ? 'active' : ''; ?>">HOME</a></li>
            <li><a href="services.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : ''; ?>">SERVICES</a></li>
            <li><a href="about_us.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'about_us.php' ? 'active' : ''; ?>">ABOUT</a></li>
            <li><a href="contact_us.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'contact_us.php' ? 'active' : ''; ?>">CONTACT</a></li>
            <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "superadmin"): ?>
                <li><a href="superadmin_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'superadmin_dashboard.php' ? 'active' : ''; ?>">SUPER ADMIN</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php if (isset($_SESSION['email'])): ?>
    <div class="user-info">
        <a href="user_profile.php" class="user-link">
            <span class="user-icon-name">
                <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <circle cx="11" cy="11" r="11" fill="#1B4D43"/>
                  <ellipse cx="11" cy="15.5" rx="6" ry="4.5" fill="#fff" fill-opacity="0.25"/>
                  <circle cx="11" cy="9" r="4" fill="#fff" fill-opacity="0.7"/>
                </svg>
                <?php echo htmlspecialchars($_SESSION['email']); ?>
            </span>
        </a>
        <form method="POST" action="" class="logout-form">
            <button type="submit" name="logout" class="logout-btn">Logout</button>
        </form>
    </div>
    <?php else: ?>
        <div class="join-button">
            <a href="entry/sign_up.php" class="btn">JOIN US!</a>
        </div>
    <?php endif; ?>
</header> 