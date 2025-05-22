<?php
// contact.php - Contact page for Skill Sphere website

// Header section
function renderHeader() {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Contact Us - Skill Sphere</title>
        <link rel="stylesheet" href="designs/contact_us1.css">
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
                    <li><a href="contact_us.php" class="active">CONTACT US</a></li>
                </ul>
            </nav>
            <div class="join-button">
                <a href="entry/sign_up.php" class="btn">JOIN US!</a>
            </div>
        </header>
    <?php
}

// Contact Hero Section
function renderContactHero() {
    ?>
    <section class="contact-hero">
        <div class="contact-content">
            <div class="contact-header">
                <h2>CONTACT <span class="highlight">US</span></h2>
                <div class="contact-subtitle">
                    <h3>GET IN TOUCH.</h3>
                    <div class="separator"></div>
                </div>
            </div>
            
            <div class="contact-info">
                <div class="contact-card">
                    <div class="contact-icon">
                        <div class="location-icon">
                            <img src="assets/location.png" alt="Location Icon">
                        </div>
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h4>ADDRESS</h4>
                    <p>Arana St. Sta. Cruz, Naga City<br>
                       Camarines Sur 4400<br>
                       Philippines</p>
                    <p>Bagumbayan, Naga City<br>
                       Camarines Sur 4400<br>
                       Philippines</p>
                </div>
                
                <div class="contact-card">
                    <div class="contact-icon">
                    <div class="phone-icon">
                            <img src="assets/phone.png" alt="Phone Icon">
                        </div>
                        <i class="fas fa-phone"></i>
                    </div>
                    <h4>PHONE</h4>
                    <p>Phone Number<br>
                       + 94871236781</p>
                    <p>Telephone Number<br>
                       + 8681723</p>
                </div>
                
                <div class="contact-card">
                    <div class="contact-icon">
                    <div class="phone-icon">
                            <img src="assets/mail.png" alt="Email Icon">
                        </div>
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h4>EMAIL</h4>
                    <p>skillsphere.services@gmail.com</p>
                    <p>support@skill.sphere.services</p>
                </div>
            </div>
        </div>
    </section>
    <?php
}

// Message Form Section
function renderMessageForm() {
    ?>
    <section class="message-section">
        <div class="message-content">
            <div class="message-text">
                <h2>Message <span class="highlight">Us</span></h2>
                <p>We'd love to hear from you!<br>
                   Whether you have a question, feedback, or<br>
                   just want to say hi—drop us a message and<br>
                   we'll get back to you as soon as possible.</p>
                <div class="message-separator"></div>
            </div>
            
            <div class="message-form">
                <form action="process_contact.php" method="POST">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="surname">Surname</label>
                        <input type="text" id="surname" name="surname" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" placeholder="Write your message here..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="submit-btn">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
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

// Process form submission
function processContactForm() {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = $_POST["name"];
        $surname = $_POST["surname"];
        $email = $_POST["email"];
        $message = $_POST["message"];
        
        // Here you would typically add code to:
        // 1. Validate inputs
        // 2. Send an email
        // 3. Store in database
        // 4. Redirect with success message
        
        // For now, we'll just redirect back to contact page
        header("Location: contact.php?status=success");
        exit();
    }
}

// Main page render
renderHeader();
renderContactHero();
renderMessageForm();
renderFooter();

// Process form if submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    processContactForm();
}
?>