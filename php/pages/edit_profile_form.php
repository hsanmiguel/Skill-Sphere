<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$conn = new mysqli("localhost", "root", "", "skillsphere");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$profile_email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT * FROM user_profiles WHERE email = ?");
$stmt->bind_param("s", $profile_email);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$stmt->close();

$skills = [];
$q = $conn->query("SELECT name FROM skills ORDER BY name ASC");
while ($row = $q->fetch_assoc()) {
    $skills[] = $row['name'];
}

$services = [];
$q = $conn->query("SELECT name FROM services ORDER BY name ASC");
while ($row = $q->fetch_assoc()) {
    $services[] = $row['name'];
}

$user_skills = !empty($profile['skills']) ? array_map('trim', explode(',', $profile['skills'])) : [];
$user_services = !empty($profile['services']) ? array_map('trim', explode(',', $profile['services'])) : [];

$conn->close();
?>

<form id="editProfileForm" method="POST" action="../backend/user_profile.php?email=<?php echo urlencode($profile_email); ?>" enctype="multipart/form-data">
    <h2 class="form-title">Edit Your Profile</h2>

    <div class="form-grid">
        <div>
            <label for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($profile['first_name'] ?? ''); ?>" required>
        </div>
        <div>
            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($profile['last_name'] ?? ''); ?>" required>
        </div>
    </div>

    <div>
        <label for="birthdate">Birthdate</label>
        <input type="date" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($profile['birthdate'] ?? ''); ?>">
    </div>

    <div>
        <label for="address">Address</label>
        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($profile['address'] ?? ''); ?>">
    </div>

    <div>
        <label for="phone_number">Phone Number</label>
        <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($profile['phone_number'] ?? ''); ?>">
    </div>

    <div>
        <label for="social_media">Social Media (URL)</label>
        <input type="url" id="social_media" name="social_media" placeholder="https://example.com" value="<?php echo htmlspecialchars($profile['social_media'] ?? ''); ?>">
    </div>

    <div>
        <label>Skills</label>
        <div class="checkbox-group">
            <?php foreach ($skills as $skill): ?>
                <label><input type="checkbox" name="skills[]" value="<?php echo $skill; ?>" <?php echo in_array($skill, $user_skills) ? 'checked' : ''; ?>> <?php echo $skill; ?></label>
            <?php endforeach; ?>
        </div>
    </div>

    <div>
        <label>Services</label>
        <div class="checkbox-group">
            <?php foreach ($services as $service): ?>
                <label><input type="checkbox" name="services[]" value="<?php echo $service; ?>" <?php echo in_array($service, $user_services) ? 'checked' : ''; ?>> <?php echo $service; ?></label>
            <?php endforeach; ?>
        </div>
    </div>

    <div>
        <label for="profile_picture">Profile Picture</label>
        <input type="file" id="profile_picture" name="profile_picture">
    </div>

    <div class="form-buttons">
        <button type="submit" name="save_profile" class="btn-save">Save Changes</button>
        <button type="button" onclick="closeEditProfileModal()" class="btn-cancel">Cancel</button>
    </div>
</form>

<style>
    .form-title { font-size: 1.6em; color: #1B4D43; margin-bottom: 20px; }
    #editProfileForm { display: flex; flex-direction: column; gap: 15px; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    #editProfileForm input[type='text'],
    #editProfileForm input[type='date'],
    #editProfileForm input[type='url'] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
    }
    .checkbox-group { 
        max-height: 150px; 
        overflow-y: auto; 
        border: 1px solid #ccc; 
        padding: 10px; 
        border-radius: 5px; 
    }
    .checkbox-group label { display: block; }
    .form-buttons { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; }
    .btn-save { background-color: #1B4D43; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
    .btn-cancel { background-color: #f44336; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
</style>
