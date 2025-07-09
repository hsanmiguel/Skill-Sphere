
<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Get email from query string
$profile_email = isset($_GET['email']) ? $_GET['email'] : null;
if (!$profile_email) {
    echo '<div style="padding:40px;text-align:center;font-size:1.2em;color:#d32f2f;">No user specified.</div>';
    exit;
}

// Database connection
$conn = new mysqli("localhost", "root", "", "skillsphere");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Fetch user profile
$stmt = $conn->prepare("SELECT * FROM user_profiles WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $profile_email);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$stmt->close();

if (!$profile) {
    echo '<div style="padding:40px;text-align:center;font-size:1.2em;color:#d32f2f;">User not found.</div>';
    $conn->close();
    exit;
}


$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Profile - Skill Sphere</title>
    <link rel="stylesheet" href="../designs/header1.css">
    <link rel="stylesheet" href="../designs/user_profile1.css">
    <link rel="stylesheet" href="../designs/footer.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body { background: #f7f8fa; }
        #profile-container { max-width: 1100px; margin: 40px auto 0 auto; padding: 0 24px 32px 24px; background: #fff; border-radius: 24px; box-shadow: 0 8px 32px rgba(27,77,67,0.13); }
        #profile-header { display: flex; align-items: flex-start; gap: 32px; margin-bottom: 24px; }
        .profile-avatar { flex-shrink:0; }
        .profile-avatar img { height: 90px; width: 90px; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        @media (max-width: 900px) { #profile-container { padding: 8px 2px; } }
        @media (max-width: 600px) { #profile-container { padding: 2px 1px; } }

        /* Hire Modal Styles */
        .hire-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; justify-content: center; align-items: center; z-index: 1001; }
        .hire-modal-content { background: #fff; padding: 20px; border-radius: 14px; width: 90%; max-width: 420px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); position: relative; }
        .hire-modal-close { position: absolute; top: 10px; right: 14px; font-size: 28px; font-weight: bold; color: #888; cursor: pointer; border: none; background: none; }
        .hire-modal-title { font-size: 1.3em; font-weight: 700; color: #1B4D43; margin-bottom: 16px; }
        .hire-modal-form { display: flex; flex-direction: column; gap: 10px; }
        .hire-modal-form .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .hire-modal-form label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; font-size: 0.9em; }
        .hire-modal-form input, .hire-modal-form select, .hire-modal-form textarea { width: 100%; padding: 8px; border-radius: 6px; border: 1.5px solid #ccc; font-family: 'Inter', sans-serif; font-size: 0.95em; box-sizing: border-box; }
        .hire-modal-form textarea { min-height: 100px; }
        .hire-modal-btns { display: flex; justify-content: flex-end; gap: 12px; margin-top: 12px; }
        .hire-modal-btn { padding: 10px 22px; border-radius: 8px; font-weight: 600; font-size: 1em; border: none; cursor: pointer; }
        .hire-modal-btn.submit { background: #1B4D43; color: #fff; }
        .hire-modal-btn.cancel { background: #f1f1f1; color: #333; }
        @media (max-width: 600px) { .hire-modal-form .form-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<?php include '../components/header.php'; ?>
<div style="max-width:1100px;margin:32px auto 0 auto;padding:0 24px 0 24px;">
    <a href="services.php" style="display:inline-flex;align-items:center;gap:4px;background:#fff;color:#1B4D43;border:1.5px solid #1B4D43;border-radius:999px;padding:4px 14px;font-size:0.98em;font-weight:600;cursor:pointer;box-shadow:0 1px 4px rgba(27,77,67,0.07);transition:background 0.18s;text-decoration:none;min-height:28px;min-width:0;float:left;">
        <span class="material-icons" style="font-size:1em;vertical-align:middle;margin-right:2px;">arrow_back</span>
        <span style="font-size:0.98em;">Back</span>
    </a>
</div>
<div id="profile-container">
    <div id="profile-header">
        <div class="profile-avatar">
            <img src="<?php echo (!empty($profile['profile_picture']) && file_exists(__DIR__ . '/../uploads/' . $profile['profile_picture'])) ? '../uploads/' . htmlspecialchars($profile['profile_picture']) : '../assets/logo_ss.png'; ?>" alt="Profile Picture">
        </div>
        <div style="flex:1; display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <div style="font-size: 1.35rem; font-weight: 700; color: #223; letter-spacing: 0.01em;">
                    <?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?>
                </div>
                <div style="color: #666; font-size: 1.05em; display: flex; align-items: center; gap: 8px;">
                    <span class="material-icons" style="font-size:1.1em;vertical-align:middle;margin-right:4px;color:#1B4D43;">mail</span>
                    <?php echo htmlspecialchars($profile['email']); ?>
                </div>
            </div>
            <?php if (isset($_SESSION['email']) && $_SESSION['email'] !== $profile_email): ?>
            <div>
                <button id="hireNowBtn" style="background:#1B4D43;color:#fff;padding:10px 24px;border-radius:8px;font-weight:600;font-size:1.05em;border:none;cursor:pointer;">Hire Now</button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div style="display: flex; flex-wrap: wrap; gap: 32px; margin-bottom: 18px;">
        <div style="flex:1 1 320px; min-width:260px; background:#fff; border-radius:18px; box-shadow:0 2px 12px rgba(27,77,67,0.08); padding: 24px 18px 18px 18px; margin-bottom:0; display:flex; flex-direction:column; gap:8px;">
            <div style="font-weight:700; color:#1B4D43; margin-bottom:8px; display:flex; align-items:center; gap:6px;"><span class="material-icons" style="font-size:1.1em;vertical-align:middle;">info</span> Contact Information</div>
            <div style="margin-bottom:4px;"><span style="color:#888;font-weight:500;"><span class="material-icons" style="font-size:1em;vertical-align:middle;">cake</span> Birthdate:</span> <span style="color:#223;font-weight:600;"> <?php echo isset($profile['birthdate']) ? htmlspecialchars($profile['birthdate']) : 'N/A'; ?></span></div>
            <div style="margin-bottom:4px;"><span style="color:#888;font-weight:500;"><span class="material-icons" style="font-size:1em;vertical-align:middle;">location_on</span> Address:</span> <span style="color:#223;font-weight:600;"> <?php echo htmlspecialchars($profile['address']); ?></span></div>
            <div style="margin-bottom:4px;"><span style="color:#888;font-weight:500;"><span class="material-icons" style="font-size:1em;vertical-align:middle;">school</span> Years of Experience:</span> <span style="color:#223;font-weight:600;"> <?php echo isset($profile['experience']) ? htmlspecialchars($profile['experience']) : 'N/A'; ?></span></div>
            <div><span style="color:#888;font-weight:500;"><span class="material-icons" style="font-size:1em;vertical-align:middle;">call</span> Phone:</span> <span style="color:#223;font-weight:600;"> <?php echo htmlspecialchars($profile['phone_number']); ?></span></div>
        </div>
        <div style="flex:2 1 420px; min-width:260px; background:#fff; border-radius:18px; box-shadow:0 2px 12px rgba(27,77,67,0.08); padding: 24px 18px 18px 18px; margin-bottom:0; display:flex; flex-direction:column; gap:8px;">
            <div style="font-weight:700; color:#1B4D43; margin-bottom:8px; display:flex; align-items:center; gap:6px;"><span class="material-icons" style="font-size:1.1em;vertical-align:middle;">star</span> Skills</div>
            <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:12px;">
                <?php
                if (!empty($profile['skills'])) {
                    $user_skills = array_map('trim', explode(',', $profile['skills']));
                    foreach ($user_skills as $skill) {
                        if (empty($skill)) continue;
                        echo '<span style="background:#f0f7ff;color:#1976d2;padding:6px 12px;border-radius:6px;font-size:0.97em;font-weight:500;">'.htmlspecialchars($skill).'</span>';
                    }
                } else {
                    echo '<span style="color:#888;font-size:0.98em;">No skills listed.</span>';
                }
                ?>
            </div>
            <div style="font-weight:700; color:#1B4D43; margin-bottom:8px; display:flex; align-items:center; gap:6px;"><span class="material-icons" style="font-size:1.1em;vertical-align:middle;">handshake</span> Services</div>
            <div style="display:flex; flex-wrap:wrap; gap:8px;">
                <?php
                if (!empty($profile['services'])) {
                    $user_services = array_map('trim', explode(',', $profile['services']));
                    foreach ($user_services as $service) {
                        if (empty($service)) continue;
                        echo '<span style="background:#fff3e0;color:#f57c00;padding:6px 12px;border-radius:6px;font-size:0.97em;font-weight:500;">'.htmlspecialchars($service).'</span>';
                    }
                } else {
                    echo '<span style="color:#888;font-size:0.98em;">No services listed.</span>';
                }
                ?>
            </div>
        </div>
        <!-- Ratings & Feedback Section -->
        <div style="flex:1 1 320px; min-width:260px; background:#fff; border-radius:18px; box-shadow:0 2px 12px rgba(27,77,67,0.08); padding: 24px 18px 18px 18px; margin-bottom:0; display:flex; flex-direction:column; gap:10px; align-items:flex-start;">
            <div style="font-weight:700; color:#1B4D43; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                <span class="material-icons" style="font-size:1.1em;vertical-align:middle;">rate_review</span> Ratings & Feedback
            </div>
            <?php
            // Unified feedback logic: fetch and display worker reviews in the same style as profilee.php
            $conn2 = new mysqli("localhost", "root", "", "skillsphere");
            $feedbacks = [];
            if (!$conn2->connect_error) {
                $stmt2 = $conn2->prepare("SELECT client_review, client_rating, worker_review, worker_rating, sender_email, created_at FROM requests WHERE receiver_email = ? ORDER BY created_at DESC");
                $stmt2->bind_param("s", $profile_email);
                $stmt2->execute();
                $res2 = $stmt2->get_result();
                $unique_feedbacks = [];
                while ($row = $res2->fetch_assoc()) {
                    // Only show feedback if sender provided a review or rating
                    if (!empty($row['client_review']) || !empty($row['client_rating']) || !empty($row['worker_review']) || !empty($row['worker_rating'])) {
                        // Only keep the latest feedback from each sender_email
                        if (!isset($unique_feedbacks[$row['sender_email']])) {
                            $unique_feedbacks[$row['sender_email']] = $row;
                        }
                    }
                }
                $feedbacks = array_values($unique_feedbacks);
                $stmt2->close();
                $conn2->close();
            }
            if (count($feedbacks) === 0) {
                echo '<div style="color:#888;font-size:0.98em;">No ratings or feedback yet.</div>';
            } else {
                echo '<div style="display:flex;flex-direction:column;gap:12px;width:100%;">';
                foreach ($feedbacks as $fb) {
                    // Fetch reviewer's name from user_profiles
                    $reviewer_name = $fb['sender_email'];
                    $conn_lookup = new mysqli("localhost", "root", "", "skillsphere");
                    if (!$conn_lookup->connect_error) {
                        $stmt_lookup = $conn_lookup->prepare("SELECT first_name, last_name FROM user_profiles WHERE email = ? LIMIT 1");
                        $stmt_lookup->bind_param("s", $fb['sender_email']);
                        $stmt_lookup->execute();
                        $res_lookup = $stmt_lookup->get_result();
                        if ($row_lookup = $res_lookup->fetch_assoc()) {
                            $reviewer_name = $row_lookup['first_name'] . ' ' . $row_lookup['last_name'];
                        }
                        $stmt_lookup->close();
                        $conn_lookup->close();
                    }
                    // Show client review if present
                    if (!empty($fb['client_review']) || !empty($fb['client_rating'])) {
                        echo "<div class='req-feedback' style='background:#f7f8fa;border-radius:10px;padding:10px 14px;box-shadow:0 1px 4px rgba(27,77,67,0.04);margin-bottom:6px;'>";
                        echo "<b>".htmlspecialchars($reviewer_name)."</b>: ".htmlspecialchars($fb['client_review'] ?? '');
                        if (!empty($fb['client_rating'])) echo " <span class='req-rating' style='color:#ff9800;font-weight:600;'>Rating: ".intval($fb['client_rating'])."/5</span>";
                        echo "</div>";
                    }
                    // Show worker review if present
                    if (!empty($fb['worker_review']) || !empty($fb['worker_rating'])) {
                        echo "<div class='req-feedback' style='background:#f7f8fa;border-radius:10px;padding:10px 14px;box-shadow:0 1px 4px rgba(27,77,67,0.04);margin-bottom:6px;'>";
                        echo "<b>".htmlspecialchars($reviewer_name)."</b>: ".htmlspecialchars($fb['worker_review'] ?? '');
                        if (!empty($fb['worker_rating'])) echo " <span class='req-rating' style='color:#ff9800;font-weight:600;'>Rating: ".intval($fb['worker_rating'])."/5</span>";
                        echo "</div>";
                    }
                }
                echo '</div>';
            }
            ?>
        </div>
</div>
<!-- End of feedback display, review submission is handled elsewhere -->

    </div>
</div>
<?php include '../components/footer.php'; ?>

<!-- Hire Now Modal -->
<div id="hireModal" class="hire-modal-overlay">
    <div class="hire-modal-content">
        <button class="hire-modal-close" id="closeHireModal">&times;</button>
        <div class="hire-modal-title">Send Hire Request</div>
        <form id="hireForm" class="hire-modal-form">
            <div>
                <label for="request_title">Request Title</label>
                <input type="text" id="request_title" name="request_title" placeholder="e.g., Fix Leaky Faucet" required>
            </div>
            <div>
                <label for="service">Service</label>
                <select id="service" name="service" required>
                    <option value="" disabled selected>Select a service...</option>
                    <?php 
                        $user_services = !empty($profile['services']) ? array_map('trim', explode(',', $profile['services'])) : [];
                        foreach ($user_services as $service) {
                            echo '<option value="' . htmlspecialchars($service) . '">' . htmlspecialchars($service) . '</option>';
                        }
                    ?>
                </select>
            </div>
            <div>
                <label for="date_time">Date & Time</label>
                <input type="datetime-local" id="date_time" name="date_time" required>
            </div>
            <div class="form-grid">
                <div>
                    <label for="wage_amount">Wage Amount (PHP)</label>
                    <input type="number" id="wage_amount" name="wage_amount" placeholder="e.g., 500" step="0.01" required>
                </div>
                <div>
                    <label for="wage_type">Wage Type</label>
                    <select id="wage_type" name="wage_type" required>
                        <option>Per Hour</option>
                        <option>Fixed Rate</option>
                        <option>Negotiable</option>
                    </select>
                </div>
            </div>
            <div>
                <label for="location">Location</label>
                <input type="text" id="location" name="location" placeholder="e.g., Your Address" required>
            </div>
            <div>
                <label for="contact_info">Your Contact Info</label>
                <input type="text" id="contact_info" name="contact_info" placeholder="Phone number or email" required>
            </div>
            <div>
                <label for="hireMessage">Message</label>
                <textarea name="message" id="hireMessage" placeholder="Provide additional details about the job..." required></textarea>
            </div>
            <div class="hire-modal-btns">
                <button type="button" class="hire-modal-btn cancel" id="cancelHireModal">Cancel</button>
                <button type="submit" class="hire-modal-btn submit">Send Request</button>
            </div>
        </form>
    </div>
</div>

<script>
// Hire Modal Logic
const hireModal = document.getElementById('hireModal');
const openHireModalBtn = document.getElementById('hireNowBtn');
const closeHireModalBtn = document.getElementById('closeHireModal');
const cancelHireModalBtn = document.getElementById('cancelHireModal');

if (openHireModalBtn) {
    openHireModalBtn.onclick = function() {
        hireModal.style.display = 'flex';
    };
}

if (closeHireModalBtn) closeHireModalBtn.onclick = function() { hireModal.style.display = 'none'; };
if (cancelHireModalBtn) cancelHireModalBtn.onclick = function() { hireModal.style.display = 'none'; };
hireModal.onclick = function(e) { if (e.target === hireModal) hireModal.style.display = 'none'; };

document.getElementById('hireForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('receiver_email', <?php echo json_encode($profile_email); ?>);

    fetch('../backend/send_request.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Hiring request sent successfully!');
            hireModal.style.display = 'none';
        } else {
            alert(data.error || 'Failed to send hiring request.');
        }
    });
};
</script>

</body>
</html>
