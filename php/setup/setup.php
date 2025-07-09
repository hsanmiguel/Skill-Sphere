<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Connect to your database
$conn = new mysqli("localhost", "root", "", "skillsphere");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch skills
$skills_result = $conn->query("SELECT name, emoji FROM skills ORDER BY name ASC");
$all_skills = [];
while ($row = $skills_result->fetch_assoc()) {
    $all_skills[] = $row;
}

// Fetch services (no category column)
$services_result = $conn->query("SELECT name, emoji FROM services ORDER BY name ASC");
$all_services = [];
$serviceToCategory = [];
while ($row = $services_result->fetch_assoc()) {
    $all_services[] = $row;
    $serviceToCategory[$row['name']] = 'Other';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Skill Sphere Profile</title>
  <link rel="stylesheet" href="/Skill-Sphere/php/designs/shared.css">
  <link rel="stylesheet" href="/Skill-Sphere/php/designs/footer.css">
  <link rel="stylesheet" href="/Skill-Sphere/php/designs/setup1.css">
  <link rel="stylesheet" href="/Skill-Sphere/php/designs/header1.css">
  <style>
    .popup-content {
      max-width: 600px;
      width: 90vw;
      min-width: 320px;
      position: relative;
      padding-bottom: 0;
    }
    #skills-checkboxes, #services-checkboxes {
      min-width: 0;
    }
    .popup-footer {
      position: static;
      padding: 0;
      background: none;
      border-top: none;
      justify-content: flex-end;
    }
    .popup-close {
      min-width: 100px;
      font-size: 1em;
      font-weight: 600;
      background: #f0ad4e;
      color: #1c4f47;
      border: none;
      border-radius: 8px;
      padding: 8px 20px;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(0,0,0,0.07);
      transition: background 0.2s;
    }
    .popup-close:hover {
      background: #ec971f;
    }
    /* Fallback/fix for header user info and logout button */
    .user-info {
      margin-left: auto;
      display: flex;
      align-items: center;
      gap: 18px;
      font-weight: 600;
      color: #1B4D43;
      padding-left: 20px;
    }
    .user-link {
      color: #1B4D43;
      font-weight: 600;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .user-icon-name {
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .logout-form {
      display: inline;
      margin: 0;
    }
    .logout-btn {
      margin-left: 10px;
      background: linear-gradient(135deg, #e53935 0%, #ffb733 100%);
      color: #fff;
      border: none;
      border-radius: 20px;
      padding: 8px 18px;
      font-weight: 600;
      cursor: pointer;
      font-size: 1.1rem;
      transition: background 0.2s, color 0.2s;
    }
    .logout-btn:hover {
      background: linear-gradient(135deg, #ffb733 0%, #e53935 100%);
      color: #fff;
    }

    /* New styles for selected skills/services view */
    .selected-list {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 8px;
      margin-bottom: 8px;
      min-height: 32px;
    }
    .selected-item {
      background: #e6f7f1;
      color: #1c4f47;
      border-radius: 16px;
      padding: 4px 12px 4px 10px;
      font-size: 0.98em;
      display: flex;
      align-items: center;
      gap: 4px;
      border: 1px solid #b2e0d2;
      transition: background 0.2s;
    }
    .selected-item .remove-btn {
      background: none;
      border: none;
      color: #e53935;
      font-size: 1.1em;
      cursor: pointer;
      margin-left: 2px;
      padding: 0 2px;
      line-height: 1;
      border-radius: 50%;
      transition: background 0.15s;
    }
    .selected-item .remove-btn:hover {
      background: #ffeaea;
    }
    .skills-section .btn-accent {
      margin-left: 10px;
    }
    .popup-section h3 {
      margin-bottom: 4px;
    }
    .popup-section p {
      margin-bottom: 10px;
    }
    .popup-section {
      margin-bottom: 18px;
    }
    .popup-section strong {
      margin-top: 8px;
      display: block;
      color: #1c4f47;
      font-size: 1.05em;
      margin-bottom: 2px;
    }
    /* Hide scrollbars for a cleaner look */
    #skills-checkboxes, #services-checkboxes {
      scrollbar-width: thin;
      scrollbar-color: #b2e0d2 #f7faf9;
    }
    #skills-checkboxes::-webkit-scrollbar, #services-checkboxes::-webkit-scrollbar {
      width: 6px;
      background: #f7faf9;
    }
    #skills-checkboxes::-webkit-scrollbar-thumb, #services-checkboxes::-webkit-scrollbar-thumb {
      background: #b2e0d2;
      border-radius: 6px;
    }
    /* Category label style for services */
    .service-category-label {
      font-weight: bold;
      color: #1c4f47;
      margin-top: 10px;
      margin-bottom: 2px;
      font-size: 1.04em;
      display: block;
      padding-left: 2px;
    }
  </style>
</head>
<body>
  <?php include '../components/header.php'; ?>
  <main class="section">
    <div class="container">
      <div class="card" style="max-width: 700px; margin: 0 auto;">
        <h1 class="section-title" style="margin-bottom: 8px;">Almost done</h1>
        <p style="text-align:center; margin-bottom: 32px; color: var(--text-light);">Be found by clients. Create your Skill Sphere Profile.</p>
        <form id="setupForm" action="../entry/server2.php" method="POST">
          <div class="row" style="display: flex; gap: var(--spacing-lg); margin-bottom: var(--spacing-lg);">
            <input class="form-control" type="text" id="first-name" name="first-name" placeholder="First name" required>
            <input class="form-control" type="text" id="last-name" name="last-name" placeholder="Last name" required>
            <input class="form-control" type="text" id="mi" name="mi" placeholder="M.I">
          </div>

          <div class="form-group">
            <label for="birthdate">Birthdate</label>
            <input class="form-control" type="date" id="birthdate" name="birthdate" required>
          </div>

          <div class="form-group">
            <label for="address">Address</label>
            <input class="form-control" type="text" id="address" name="address" required>
          </div>

          <div class="form-group">
            <label for="phone-number">Phone Number</label>
            <input class="form-control" type="text" id="phone-number" name="phone-number" required>
          </div>

          <div class="form-group">
            <label for="email">Email</label>
            <input class="form-control" type="email" id="email" name="email" required>
          </div>

          <div class="form-group">
            <label for="social-media">Social Media Account</label>
            <input class="form-control" type="text" id="social-media" name="social-media">
          </div>

          <div class="form-group" style="display: flex; align-items: center; gap: var(--spacing-md);">
            <label for="experience" style="margin-bottom: 0;">Years of Experience</label>
            <input class="form-control" style="max-width: 100px;" type="number" id="experience" name="experience" min="0" max="50" placeholder="0" required>
            <span>years</span>
          </div>

          <div class="form-group skills-section">
            <label for="skills">Skills & Services</label>
            <button type="button" class="btn btn-accent" id="openSkillsServicesModal">Add Skills & Services</button>
            <div style="margin-top: 8px;">
              <div>
                <span style="font-weight: 600; color: #1c4f47;">Skills:</span>
                <div id="selected-skills-list" class="selected-list"></div>
              </div>
              <div>
                <span style="font-weight: 600; color: #1c4f47;">Services:</span>
                <div id="selected-services-list" class="selected-list"></div>
              </div>
            </div>
          </div>

          <!-- Hidden fields to store dynamic input -->
          <input type="hidden" name="skills" id="skills-hidden">
          <input type="hidden" name="selected-service" id="selected-service-hidden">

          <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: var(--spacing-lg);">Save & Continue</button>
        </form>
      </div>
    </div>
  </main>

  <?php include '../components/footer.php'; ?>

  <!-- Popup -->
  <div id="popup-overlay" class="popup-overlay" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.25); align-items: center; justify-content: center; z-index:999;">
    <div class="popup-content card" style="width: 420px; max-width: 96vw; max-height: 80vh; min-width: 0; position: relative; padding: 18px 18px 0 18px; margin: 0 auto; border-radius: 18px; overflow-y: auto; box-shadow: 0 8px 32px rgba(0,0,0,0.12);">
      <h2 class="section-title" style="font-size: var(--text-2xl); margin-bottom: 0;">Add to your profile</h2>
      <button type="button" id="closeSkillsServicesModal" style="position:absolute; top:10px; right:10px; background:none; border:none; font-size:1.8em; color:#888; cursor:pointer;">&times;</button>
      <hr style="margin: var(--spacing-lg) 0;">

      <div class="popup-section">
        <h3 style="font-size: var(--text-xl); font-weight: var(--font-bold);">Let's add your skills</h3>
        <p style="color: var(--text-light);">Your listed skills help us connect you with clients who need your services.</p>
        <div id="skills-checkboxes" style="display: flex; flex-direction: column; gap: 10px; max-height: 220px; overflow-y: auto; border: 1px solid #bbb; border-radius: 8px; padding: 10px; background: var(--surface-hover);">
          <?php foreach ($all_skills as $skill): ?>
            <label>
              <input type="checkbox" value="<?php echo htmlspecialchars($skill['name']); ?>">
              <?php echo htmlspecialchars($skill['emoji'] . ' ' . $skill['name']); ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="popup-section" style="margin-top: var(--spacing-xl);">
        <h3 style="font-size: var(--text-xl); font-weight: var(--font-bold);">Let's add your services</h3>
        <p style="color: var(--text-light);">Your listed services help us connect you with clients who need your services.</p>
        <div id="services-checkboxes" style="display: flex; flex-direction: column; gap: 10px; max-height: 220px; overflow-y: auto; border: 1px solid #bbb; border-radius: 8px; padding: 10px; background: var(--surface-hover);">
          <?php foreach ($all_services as $service): ?>
            <label>
              <input type="checkbox" value="<?php echo htmlspecialchars($service['name']); ?>">
              <?php echo htmlspecialchars($service['emoji'] . ' ' . $service['name']); ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <hr style="margin: var(--spacing-lg) 0;">
      <div class="popup-footer" style="display: flex; justify-content: flex-end; background: none; border-top: none;">
        <button class="btn btn-accent popup-close" id="modalContinueBtn">Continue</button>
      </div>
    </div>
  </div>

  <script>
    // Helper to update the visible selected skills/services lists
    function updateSelectedLists() {
      // Skills
      const skillCheckboxes = document.querySelectorAll('#skills-checkboxes input[type="checkbox"]');
      const selectedSkills = [];
      document.getElementById('selected-skills-list').innerHTML = '';
      skillCheckboxes.forEach(cb => {
        if (cb.checked) {
          selectedSkills.push(cb.value);
          const item = document.createElement('span');
          item.className = 'selected-item';
          item.textContent = cb.parentElement.textContent.trim();
          // Remove button
          const removeBtn = document.createElement('button');
          removeBtn.className = 'remove-btn';
          removeBtn.type = 'button';
          removeBtn.title = 'Remove';
          removeBtn.innerHTML = '&times;';
          removeBtn.onclick = function() {
            cb.checked = false;
            updateSelectedLists();
          };
          item.appendChild(removeBtn);
          document.getElementById('selected-skills-list').appendChild(item);
        }
      });

      // Services
      const serviceCheckboxes = document.querySelectorAll('#services-checkboxes input[type="checkbox"]');
      const selectedServices = [];
      document.getElementById('selected-services-list').innerHTML = '';
      // Group selected services by category
      const categoryMap = {};
      // Prepare a JS object mapping service to category for grouping
      const serviceToCategory = <?php echo json_encode($serviceToCategory); ?>;
      serviceCheckboxes.forEach(cb => { 
        if (cb.checked) {
          const service = cb.value;
          const category = serviceToCategory[service] || "Other";
          if (!categoryMap[category]) categoryMap[category] = [];
          categoryMap[category].push(cb);
        }
      });
      // Render selected services grouped by category
      for (const [category, cbs] of Object.entries(categoryMap)) {
        const catLabel = document.createElement('span');
        catLabel.className = 'service-category-label';
        catLabel.textContent = category;
        document.getElementById('selected-services-list').appendChild(catLabel);
        cbs.forEach(cb => {
          const item = document.createElement('span');
          item.className = 'selected-item';
          item.textContent = cb.parentElement.textContent.trim();
          // Remove button
          const removeBtn = document.createElement('button');
          removeBtn.className = 'remove-btn';
          removeBtn.type = 'button';
          removeBtn.title = 'Remove';
          removeBtn.innerHTML = '&times;';
          removeBtn.onclick = function() {
            cb.checked = false;
            updateSelectedLists();
          };
          item.appendChild(removeBtn);
          document.getElementById('selected-services-list').appendChild(item);
        });
      }
    }

    // Modal open/close logic
    document.getElementById('openSkillsServicesModal').onclick = function() {
      document.getElementById("popup-overlay").style.display = "flex";
      updateSelectedLists();
    };
    document.getElementById('closeSkillsServicesModal').onclick = function() {
      document.getElementById("popup-overlay").style.display = "none";
      updateSelectedLists();
    };
    document.getElementById('modalContinueBtn').onclick = function() {
      document.getElementById("popup-overlay").style.display = "none";
      updateSelectedLists();
    };


    function prepareFormData() {
      // Collect checked skills
      const skillCheckboxes = document.querySelectorAll('#skills-checkboxes input[type="checkbox"]:checked');
      const skills = Array.from(skillCheckboxes).map(cb => cb.value);
      document.getElementById("skills-hidden").value = skills.join(", ");

      // Collect checked services
      const serviceCheckboxes = document.querySelectorAll('#services-checkboxes input[type="checkbox"]:checked');
      const selectedServices = Array.from(serviceCheckboxes).map(cb => cb.value);
      document.getElementById("selected-service-hidden").value = selectedServices.join(", ");
    }


    // On submit, prepare form data before sending
    document.addEventListener('DOMContentLoaded', function() {
      document.getElementById('setupForm').addEventListener('submit', function(e) {
        prepareFormData();
      });
    });

    // Attach event listeners to checkboxes for live update
    document.addEventListener('DOMContentLoaded', function() {
      // Skills
      document.querySelectorAll('#skills-checkboxes input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', updateSelectedLists);
      });
      // Services
      document.querySelectorAll('#services-checkboxes input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', updateSelectedLists);
      });
      // Initial update
      updateSelectedLists();
    });
  </script>
  <?php $conn->close(); ?>
</body>
</html>