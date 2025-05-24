<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: /Skill-Sphere/php/entry/sign_in.php");
    exit();
}

// Database connection (PDO)
$host = 'localhost';
$dbname = 'registered_accounts';
$username = 'root';
$password = '';
$conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

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
$serviceProviders = $stmt->fetchAll();

// Fetch all services from the database
$servicesList = [];
$serviceResult = $conn->query("SELECT id, name, emoji FROM services ORDER BY name");
while ($row = $serviceResult->fetch(PDO::FETCH_ASSOC)) {
    $servicesList[] = $row;
}

if (isset($_POST['hire_now']) && isset($_SESSION['email'])) {
    $sender_email = $_SESSION['email'];
    $receiver_id = intval($_POST['provider_id']);
    $service = isset($_POST['service']) ? $_POST['service'] : '';
    $wage_amount = isset($_POST['wage_amount']) ? $_POST['wage_amount'] : '';
    $wage_type = isset($_POST['wage_type']) ? $_POST['wage_type'] : '';
    $hire_message = isset($_POST['hire_message']) ? $_POST['hire_message'] : '';

    // Get provider email
    $providerQ = $conn->prepare("SELECT email FROM user_profiles WHERE id=?");
    $providerQ->execute([$receiver_id]);
    $provider = $providerQ->fetch();
    $receiver_email = $provider ? $provider['email'] : null;

    if ($receiver_email) {
        // Insert request
        $stmt = $conn->prepare("INSERT INTO requests (sender_email, receiver_email, service, wage_amount, wage_type, message, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->execute([$sender_email, $receiver_email, $service, $wage_amount, $wage_type, $hire_message]);
        // Add notification for provider
        $msg = "You have a new service request from $sender_email.";
        $notif = $conn->prepare("INSERT INTO notifications (user_email, message, type) VALUES (?, ?, 'info')");
        $notif->execute([$receiver_email, $msg]);
        $successMsg = "Request sent!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - Skill Sphere</title>
    <link rel="stylesheet" href="/Skill-Sphere/php/designs/header1.css">
    <link rel="stylesheet" href="/Skill-Sphere/php/designs/services1.css">
    <link rel="stylesheet" href="/Skill-Sphere/php/designs/hire_modal.css">
    <link rel="stylesheet" href="/Skill-Sphere/php/designs/footer.css">
</head>
<body>
    <?php include '../components/header.php'; ?>

    <section class="hero services-hero">
        <div class="search-bar-container">
            <form method="GET" action="" style="display: flex; width: 100%; gap: 12px; align-items: center;">
                <input type="text" name="keywords" placeholder="Enter Keywords"
                       value="<?php echo isset($_GET['keywords']) ? htmlspecialchars($_GET['keywords']) : ''; ?>">

                <select name="category">
                    <option value="" disabled <?php if (empty($_GET['category'])) echo 'selected'; ?>>Select a Service</option>
                    <?php foreach ($servicesList as $service): ?>
                        <option value="<?php echo htmlspecialchars($service['name']); ?>" <?php if (isset($_GET['category']) && $_GET['category'] == $service['name']) echo 'selected'; ?>><?php echo htmlspecialchars($service['emoji'] . ' ' . $service['name']); ?></option>
                    <?php endforeach; ?>
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
                        <img src="<?php echo !empty($provider['profile_picture']) && file_exists(__DIR__.'/../uploads/' . $provider['profile_picture']) ? '/Skill-Sphere/php/uploads/' . htmlspecialchars($provider['profile_picture']) : '/Skill-Sphere/php/assets/logo_ss.png'; ?>" alt="Profile Picture" style="height:100px;width:100px;border-radius:50%;object-fit:cover;box-shadow:0 2px 8px rgba(0,0,0,0.08);border:2px solid #1B4D43;flex-shrink:0;">
                        <div class="details" style="flex:1;display:flex;flex-direction:column;gap:8px;">
                            <h3><?php echo htmlspecialchars($provider['first_name']); ?></h3>
                            <p>
                                <?php
                                $services = array_map('trim', explode(',', $provider['services']));
                                $serviceIcons = [];
                                foreach ($servicesList as $s) {
                                    $serviceIcons[$s['name']] = $s['emoji'];
                                }
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
                            <a href="/Skill-Sphere/php/pages/user_profile.php?id=<?php echo urlencode($provider['id']); ?>" class="view-profile">View Full Profile</a>
                            <button type="button"
                                class="hire-now"
                                onclick="openHireNowModal(
                                    '<?php echo $provider['id']; ?>',
                                    '<?php echo htmlspecialchars($provider['first_name'] . ' ' . $provider['last_name']); ?>',
                                    '<?php echo htmlspecialchars($provider['services']); ?>',
                                    '', // No description needed
                                    '<?php echo isset($provider['experience']) && $provider['experience'] !== '' ? htmlspecialchars($provider['experience']) : 0; ?>'
                                )"
                            >
                                HIRE NOW!
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:left; font-size:18px; color:#777;">No service providers found matching your search.</p>
            <?php endif; ?>
        </div>
    </section>

    <?php if (isset($successMsg)) echo '<div class="success-message">' . htmlspecialchars($successMsg) . '</div>'; ?>

    <!-- Hire Now Modal -->
    <div id="hireNowModal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Job Details</h3>
          <button type="button" class="modal-close" onclick="document.getElementById('hireNowModal').style.display='none'">&times;</button>
        </div>
        <form id="hireNowForm" method="POST">
          <input type="hidden" name="provider_id" id="modal_provider_id">
          <input type="hidden" name="service" id="modal_service">
          <div class="modal-field">
            <label class="modal-label">Service ID</label>
            <div class="modal-value" id="modal_service_id"></div>
          </div>
          <div class="modal-field">
            <label class="modal-label">Name</label>
            <div class="modal-value" id="modal_name"></div>
          </div>
          <div class="modal-field">
            <label class="modal-label">Service Name</label>
            <div class="modal-value" id="modal_service_name"></div>
          </div>
          <div class="modal-field">
            <label class="modal-label">Years of Experience</label>
            <div class="modal-value" id="modal_years_experience"></div>
          </div>
          <div class="modal-field">
            <label class="modal-label">Wage</label>
            <div class="modal-wage-row peso-input-wrapper">
              <span class="peso-sign">₱</span>
              <input type="number" name="wage_amount" id="modal_wage_amount" min="0" step="0.01" required>
              <select name="wage_type" id="modal_wage_type" required>
                <option value="per day">per DAY</option>
                <option value="per hour">per HOUR</option>
              </select>
            </div>
          </div>
          <div class="modal-field">
            <label class="modal-label">Messages (optional)</label>
            <textarea name="hire_message" id="modal_hire_message" rows="3" placeholder="Type your message here..."></textarea>
          </div>
          <button type="submit" name="hire_now">Confirm Hire</button>
        </form>
      </div>
    </div>

    <script>
    function openHireNowModal(id, name, service, desc, years_experience) {
        const modal = document.getElementById('hireNowModal');
        modal.style.display = 'flex';
        document.getElementById('modal_provider_id').value = id;
        document.getElementById('modal_service_id').textContent = id;
        document.getElementById('modal_name').textContent = name;
        document.getElementById('modal_service_name').textContent = service;
        document.getElementById('modal_service').value = service;
        document.getElementById('modal_hire_message').value = '';
        let y = parseInt(years_experience, 10);
        document.getElementById('modal_years_experience').textContent = y + ' year' + (y === 1 ? '' : 's');
        document.getElementById('modal_wage_amount').value = '';
        document.getElementById('modal_wage_type').selectedIndex = 0;
    }
    </script>

    <?php include '../components/footer.php'; ?>
</body>
</html>