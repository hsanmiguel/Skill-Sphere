<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: entry/sign_in.php");
    exit();
}

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

if (isset($_POST['hire_now']) && isset($_SESSION['email'])) {
    $sender_email = $_SESSION['email'];
    $receiver_id = intval($_POST['provider_id']);
    $service = isset($_POST['service']) ? $_POST['service'] : '';
    $conn = new mysqli("localhost", "root", "", "registered_accounts");
    $providerQ = $conn->prepare("SELECT email FROM user_profiles WHERE id=?");
    $providerQ->bind_param("i", $receiver_id);
    $providerQ->execute();
    $providerQ->bind_result($receiver_email);
    $providerQ->fetch();
    $providerQ->close();
    if ($receiver_email) {
        $stmt = $conn->prepare("INSERT INTO requests (sender_email, receiver_email, service, status) VALUES (?, ?, ?, 'Pending')");
        $stmt->bind_param("sss", $sender_email, $receiver_email, $service);
        $stmt->execute();
        $stmt->close();
        // Add notification for provider
        $notif = $conn->prepare("INSERT INTO notifications (user_email, message, type) VALUES (?, ?, 'info')");
        $msg = "You have a new service request from $sender_email.";
        $notif->bind_param("ss", $receiver_email, $msg);
        $notif->execute();
        $notif->close();
        $successMsg = "Request sent!";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skill Sphere</title>
    <link rel="stylesheet" href="designs/footer.css">
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

    <section class="hero services-hero">
        <div class="search-bar-container">
            <form method="GET" action="" style="display: flex; width: 100%; gap: 12px; align-items: center;">
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
                <button type="button" onclick="window.location.href='services.php'">CLEAR</button>
            </form>
        </div>
    </section>

    <section class="what-we-offer services-section">
        <div class="section-header" style="margin-left: 40px;">
            <h2>Service Providers</h2>
        </div>
        <div class="service-list">
            <?php if (count($serviceProviders) > 0): ?>
                <?php foreach ($serviceProviders as $provider): ?>
                    <div class="service-item" style="display:flex;align-items:center;gap:32px;">
                        <img src="<?php echo !empty($provider['profile_picture']) && file_exists('uploads/' . $provider['profile_picture']) ? 'uploads/' . htmlspecialchars($provider['profile_picture']) : 'assets/logo_ss.png'; ?>" alt="Profile Picture" style="height:100px;width:100px;border-radius:50%;object-fit:cover;box-shadow:0 2px 8px rgba(0,0,0,0.08);border:2px solid #1B4D43;flex-shrink:0;">
                        <div class="details" style="flex:1;display:flex;flex-direction:column;gap:8px;">
                            <h3><?php echo htmlspecialchars($provider['first_name']); ?></h3>
                            <p>
                                <?php
                                $services = array_map('trim', explode(',', $provider['services']));
                                $serviceIcons = [
                                    'Sprinkler Repair' => '💧',
                                    'Snow Removal' => '❄️',
                                ];
                                $serviceParts = [];
                                foreach ($services as $service) {
                                    $icon = isset($serviceIcons[$service]) ? $serviceIcons[$service] : '🛠️';
                                    $serviceParts[] = '<span style="color:#2196f3;">' . $icon . '</span> ' . htmlspecialchars($service);
                                }
                                echo implode(', ', $serviceParts);
                                ?>
                            </p>
                            <p class="location"><span class="icon" style="color:#e53935;">📍</span> <b><?php echo htmlspecialchars($provider['address']); ?></b></p>
                        </div>
                        <div class="actions">
                            <a href="user_profile.php?id=<?php echo urlencode($provider['id']); ?>" class="view-profile">View Full Profile</a>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="provider_id" value="<?php echo $provider['id']; ?>">
                                <input type="hidden" name="service" value="<?php echo htmlspecialchars($provider['services']); ?>">
                                <button type="submit" name="hire_now" class="hire-now">HIRE NOW!</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:left; font-size:18px; color:#777;">No service providers found matching your search.</p>
            <?php endif; ?>
        </div>
    </section>

    <?php if (isset($successMsg)) echo '<div class="success-message">' . htmlspecialchars($successMsg) . '</div>'; ?>

    <?php include 'footer.php'; ?>
</body>
</html>
