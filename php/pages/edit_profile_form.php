<?php
session_start();
if (!isset($_SESSION['user_id'])) exit('Not logged in.');
$conn = new mysqli("localhost", "root", "", "registered_accounts");
if ($conn->connect_error) exit('DB error');
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT * FROM user_profiles WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$stmt->close();
$profilePic = (!empty($profile['profile_picture']) && file_exists(__DIR__.'/../uploads/' . $profile['profile_picture'])) ? '/Skill-Sphere/php/uploads/' . htmlspecialchars($profile['profile_picture']) : '/Skill-Sphere/php/assets/logo_ss.png';

// Fetch all skills from the database
defined('SKILLS_TABLE_EXISTS') or define('SKILLS_TABLE_EXISTS', $conn->query("SHOW TABLES LIKE 'skills'")->num_rows > 0);
$skillsList = [];
if (SKILLS_TABLE_EXISTS) {
    $skillResult = $conn->query("SELECT id, name, emoji FROM skills ORDER BY name");
    while ($row = $skillResult->fetch_assoc()) {
        $skillsList[] = $row;
    }
}
// Fetch all services from the database
$servicesList = [];
$serviceResult = $conn->query("SELECT id, name, emoji FROM services ORDER BY name");
while ($row = $serviceResult->fetch_assoc()) {
    $servicesList[] = $row;
}
// Initialize user_skills and user_services as arrays
$user_skills = array_map('trim', explode(',', $profile['skills']));
$user_services = array_map('trim', explode(',', $profile['services']));
?>
<button onclick="document.getElementById('editProfileModalOverlay').style.display='none'" class="close-btn">&times;</button>
<div class="profile-pic-group" style="text-align:center;">
  <img src="<?php echo $profilePic; ?>" alt="Profile Picture" style="width:90px;height:90px;border-radius:50%;object-fit:cover;">
</div>
<form method="post" enctype="multipart/form-data" action="user_profile.php" class="edit-modal-form">
  <label style="text-align:center; font-size:1.2em; font-weight:600; color:#222; margin-bottom:10px; width:100%;">Change Picture:
    <input type="file" name="profile_picture" accept="image/*" style="margin-top:4px;">
  </label>
  <label>First Name:
    <input type="text" name="first_name" value="<?php echo htmlspecialchars($profile['first_name']); ?>" required>
  </label>
  <label>Last Name:
    <input type="text" name="last_name" value="<?php echo htmlspecialchars($profile['last_name']); ?>" required>
  </label>
  <label>Birthdate:
    <input type="date" name="birthdate" value="<?php echo isset($profile['birthdate']) ? htmlspecialchars($profile['birthdate']) : ''; ?>" required>
  </label>
  <label>Address:
    <input type="text" name="address" value="<?php echo htmlspecialchars($profile['address']); ?>" required>
  </label>
  <label>Phone Number:
    <input type="text" name="phone_number" value="<?php echo htmlspecialchars($profile['phone_number']); ?>" required>
  </label>
  <label>Skills:
    <input type="text" id="skillSearch" placeholder="Search skills...">
    <div id="skillsCheckboxes">
      <?php foreach ($skillsList as $skill): ?>
        <label>
          <input type="checkbox" name="skills[]" value="<?php echo htmlspecialchars($skill['name']); ?>" <?php if (in_array($skill['name'], $user_skills)) echo 'checked'; ?>>
          <span><?php echo htmlspecialchars($skill['emoji'] . ' ' . $skill['name']); ?></span>
        </label>
      <?php endforeach; ?>
    </div>
  </label>
  <label>Services:
    <input type="text" id="serviceSearch" placeholder="Search services...">
    <div id="servicesCheckboxes">
      <?php foreach ($servicesList as $service): ?>
        <label>
          <input type="checkbox" name="services[]" value="<?php echo htmlspecialchars($service['name']); ?>" <?php if (in_array($service['name'], $user_services)) echo 'checked'; ?>>
          <span><?php echo htmlspecialchars($service['emoji'] . ' ' . $service['name']); ?></span>
        </label>
      <?php endforeach; ?>
    </div>
  </label>
  <div class="btn-row" style="display:flex;gap:12px;justify-content:center;">
    <button type="submit" name="save_profile" class="edit-profile-btn">Save</button>
    <button type="button" onclick="document.getElementById('editProfileModalOverlay').style.display='none'" class="edit-profile-btn cancel">Cancel</button>
  </div>
</form>
<script>
function filterCheckboxes(inputId, containerId) {
  const input = document.getElementById(inputId);
  const container = document.getElementById(containerId);
  input.addEventListener('input', function() {
    const val = input.value.toLowerCase();
    Array.from(container.querySelectorAll('label')).forEach(label => {
      const text = label.textContent.toLowerCase();
      label.style.display = text.includes(val) ? '' : 'none';
    });
  });
}
filterCheckboxes('skillSearch', 'skillsCheckboxes');
filterCheckboxes('serviceSearch', 'servicesCheckboxes');
</script>
<link rel="stylesheet" href="/Skill-Sphere/php/designs/edit_profile_modal.css">