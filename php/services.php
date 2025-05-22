<?php
// Database connection
$host = 'localhost';
$dbname = 'registered_accounts';
$username = 'root';
$password = '';
$conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

// Default query
$query = "SELECT * FROM user_profiles WHERE 1=1";
$params = [];

// Keyword filter
if (!empty($_GET['keywords'])) {
    $query .= " AND (first_name LIKE :keywords OR skills LIKE :keywords OR services LIKE :keywords)";
    $params[':keywords'] = '%' . $_GET['keywords'] . '%';
}

// Category filter
if (!empty($_GET['category'])) {
    $query .= " AND services LIKE :category";
    $params[':category'] = '%' . $_GET['category'] . '%';
}

$stmt = $conn->prepare($query);
$stmt->execute($params);
$serviceProviders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skill Sphere</title>
    <link rel="stylesheet" href="designs/services1.css">
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
                <li><a href="services.php" class="active">SERVICES</a></li>
                <li><a href="about_us.php">ABOUT</a></li>
                <li><a href="contact_us.php">CONTACT US</a></li>
            </ul>
        </nav>
        <div class="join-button">
            <a href="entry/sign_up.php" class="btn">JOIN US!</a>
        </div>
    </header>


    <div class="search-container">
        <form method="GET" action="">
            <input type="text" name="keywords" placeholder="Enter Keywords"
                   value="<?php echo isset($_GET['keywords']) ? htmlspecialchars($_GET['keywords']) : ''; ?>">

            <select name="category">
                <option value="" disabled <?php if (empty($_GET['category'])) echo 'selected'; ?>>Select a Service</option>
                <?php
                // Define service categories and items
                $categories = [
                    "🔧 Skilled Trade / Labor-Based Skills" => [
                        "Plumbing", "Carpentry", "Electrical Work", "Painting", "Tiling", "Roofing", "Masonry",
                        "Welding", "Auto Repair", "Motorcycle Repair", "Appliance Repair", "Furniture Assembly",
                        "Locksmithing", "Glass Cutting", "Floor Installation", "Drywall Repair", "HVAC Repair",
                        "Gutter Cleaning", "Pest Control", "Septic Tank Cleaning"
                    ],
                    "🧹 Household / Cleaning Skills" => [
                        "House Cleaning", "Deep Cleaning", "Window Cleaning", "Laundry and Ironing", "Carpet Cleaning",
                        "Pressure Washing", "Pool Cleaning", "Organizing (Decluttering)", "Trash Removal", "Upholstery Cleaning"
                    ],
                    "🌿 Gardening & Outdoors" => [
                        "Gardening", "Landscaping", "Lawn Mowing", "Tree Trimming", "Leaf Blowing", "Fence Installation",
                        "Pesticide Application", "Sprinkler Repair", "Outdoor Painting", "Snow Removal"
                    ],
                    "🍳 Kitchen & Culinary Skills" => [
                        "Cooking", "Baking", "Catering", "Food Plating", "Kitchen Cleaning", "Barbecuing", "Meal Prep",
                        "Juice/Smoothie Making", "Butchering", "Inventory Management (Kitchen)"
                    ],
                    "🧵 Crafts, Repairs, and DIY" => [
                        "Sewing", "Embroidery", "Crochet", "Knitting", "Jewelry Repair", "Shoe Repair", "Toy Repair",
                        "Candle Making", "Pottery", "DIY Woodwork"
                    ],
                    "🖥️ Basic Tech & Appliance Skills" => [
                        "Basic Computer Repair", "Printer Setup", "Wi-Fi Setup", "Router Troubleshooting", "Smart TV Setup",
                        "CCTV Installation", "Alarm System Setup", "Cable Management", "Gadget Troubleshooting", "Software Installation"
                    ],
                    "👶 Caregiving & Support" => [
                        "Childcare", "Elderly Care", "Special Needs Assistance", "Basic First Aid", "Medication Reminders",
                        "Feeding Assistance", "Companion Care", "Diaper Changing", "Bathing Assistance", "Bedside Support"
                    ],
                    "🛒 Errands & Domestic Services" => [
                        "Grocery Shopping", "Running Errands", "Pet Walking", "Pet Bathing", "Cooking for Elders",
                        "House Sitting", "Plant Watering", "Mail Sorting", "Light Decoration (Holidays)", "Delivery Assistance"
                    ],
                    "🎨 Extra / Niche Skills" => [
                        "Sign Painting", "Basic Graphic Design", "Poster Making", "Event Setup", "Balloon Arrangement",
                        "Face Painting", "Sound System Setup", "Stage Decoration", "Costume Repair", "Recycling Management"
                    ]
                ];

                foreach ($categories as $label => $services) {
                    echo "<optgroup label=\"$label\">";
                    foreach ($services as $service) {
                        $selected = (isset($_GET['category']) && $_GET['category'] == $service) ? 'selected' : '';
                        echo "<option value=\"$service\" $selected>$service</option>";
                    }
                    echo "</optgroup>";
                }
                ?>
            </select>

            <button type="submit">SEARCH</button>
            <a href="services.php" style="margin-left: 10px; text-decoration: none;">
                <button type="button">CLEAR</button>
            </a>
        </form>
    </div>


    <div class="service-providers-container" style="background-color: #e9eaeb; padding: 20px 0;">
        <h2 style="font-size: 24px; font-weight: bold; margin: 0 auto; color: #333; max-width: 900px;">Service Providers</h2>
        <div class="service-list">
            <?php if (count($serviceProviders) > 0): ?>
                <?php foreach ($serviceProviders as $provider): ?>
                    <div class="service-item">
                        <div class="details">
                            <h3><?php echo htmlspecialchars($provider['first_name']); ?></h3>
                            <p><?php echo htmlspecialchars($provider['services']); ?></p>
                            <p><?php echo htmlspecialchars($provider['skills']); ?></p>
                            <p>📍 <?php echo htmlspecialchars($provider['address']); ?> </p>
                        </div>
                        <div class="actions">
                            <a href="user_profile.php?id=<?php echo urlencode($provider['id']); ?>" class="view-profile">View Full Profile</a>
                            <button class="hire-now">HIRE NOW!</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; font-size:18px; color:#777;">No service providers found matching your search.</p>
            <?php endif; ?>
        </div>
    </div>


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
</body>
</html>
