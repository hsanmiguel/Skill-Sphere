<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Skill Sphere Profile</title>
  <link rel="stylesheet" href="../designs/setup1.css">
  <link rel="stylesheet" href="../designs/header1.css">
</head>
<body>
  <header>
    <div class="logo-container">
      <a href="../home_page.php" style="text-decoration: none; font-weight: bold; color: #333;"><img src="../assets/logo_ss.png" alt="Skill Sphere Logo" class="logo"></a>
      <h1>Skill Sphere</h1>
    </div>
    <nav>
      <ul>
        <li><a href="../home_page.php">HOME</a></li>
        <li><a href="../services.php">SERVICES</a></li>
        <li><a href="../about_us.php">ABOUT</a></li>
        <li><a href="../contact_us.php">CONTACT US</a></li>
      </ul>
    </nav>
    <div class="join-button">
      <a href="sign_up.php" class="btn">JOIN US!</a>
    </div>
  </header>

  <div class="form-container">
    <h1>Almost done</h1>
    <p>Be found by clients. Create your Skill Sphere Profile.</p>
    <form action="server2.php" method="POST" onsubmit="prepareFormData()">
      <div class="row">
        <input type="text" id="first-name" name="first-name" placeholder="First name" required>
        <input type="text" id="last-name" name="last-name" placeholder="Last name" required>
        <input type="text" id="mi" name="mi" placeholder="M.I">
      </div>

      <label for="address">Address</label>
      <input type="text" id="address" name="address" style="width: 513px;" required>

      <label for="phone-number">Phone Number</label>
      <input type="text" id="phone-number" name="phone-number" style="width: 300px;" required>

      <label for="email">Email</label>
      <input type="email" id="email" name="email" style="width: 350px;" required>

      <label for="social-media">Social Media Account</label>
      <input type="text" id="social-media" name="social-media" style="width: 450px;">

      <label for="experience">Years of Experience</label>
      <div class="experience-container">
        <input type="number" id="experience" name="experience" min="0" max="50" placeholder="0" required>
        <span>years</span>
      </div>

      <div class="skills-section">
        <label for="skills">Skills & Services</label>
        <button type="button" onclick="openPopup()">ADD</button>
      </div>

      <!-- Hidden fields to store dynamic input -->
      <input type="hidden" name="skills" id="skills-hidden">
      <input type="hidden" name="selected-service" id="selected-service-hidden">

      <button type="submit">Save & Continue</button>
    </form>
  </div>

  <div class="footer-links">
    <a href="#">Security & Privacy</a>
    <a href="#">Terms & Conditions</a>
    <a href="#">Contact</a>
    <p>© 2025 Skill Sphere. All rights reserved.</p>
  </div>

  <!-- Popup -->
  <div id="popup-overlay" class="popup-overlay">
    <div class="popup-content">
      <h2>Add to your profile</h2>
      <hr>

      <div class="popup-section">
        <h3>Let's add your skills</h3>
        <p>Your listed skills help us connect you with clients who need your services.</p>
        <div style="display: flex; gap: 10px; align-items: center;">
          <input type="text" id="skill-input" placeholder="Type a skill" style="flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
          <button class="popup-button" onclick="addSkill()">+ ADD SKILL</button>
        </div>
        <ul id="skills-list" class="skills-list"></ul>
      </div>

      <div class="popup-section">
        <h3>Let's add your services</h3>
        <p>Your listed services help us connect you with clients who need your services.</p>
        <select id="services-dropdown" class="services-dropdown" multiple size="8">
          <option value="" disabled>Select one or more services</option>
          <optgroup label="🔧 Skilled Trade / Labor-Based Skills">
            <option>🔩 Plumbing</option>
            <option>🪚 Carpentry</option>
            <option>⚡ Electrical Work</option>
            <option>🎨 Painting</option>
            <option>🧱 Tiling</option>
            <option>🏠 Roofing</option>
            <option>🛠️ Masonry</option>
            <option>🔥 Welding</option>
            <option>🚗 Auto Repair</option>
            <option>🏍️ Motorcycle Repair</option>
            <option>🔌 Appliance Repair</option>
            <option>🪑 Furniture Assembly</option>
            <option>🔑 Locksmithing</option>
            <option>🔪 Glass Cutting</option>
            <option>🪵 Floor Installation</option>
            <option>🛠️ Drywall Repair</option>
            <option>❄️ HVAC Repair</option>
            <option>🧹 Gutter Cleaning</option>
            <option>🐜 Pest Control</option>
            <option>🚽 Septic Tank Cleaning</option>
          </optgroup>
          <optgroup label="🧹 Household / Cleaning Skills">
            <option>🏠 House Cleaning</option>
            <option>🧼 Deep Cleaning</option>
            <option>🪟 Window Cleaning</option>
            <option>👕 Laundry and Ironing</option>
            <option>🧽 Carpet Cleaning</option>
            <option>💦 Pressure Washing</option>
            <option>🏊 Pool Cleaning</option>
            <option>📦 Organizing (Decluttering)</option>
            <option>🗑️ Trash Removal</option>
            <option>🛋️ Upholstery Cleaning</option>
          </optgroup>
          <optgroup label="🌿 Gardening & Outdoors">
            <option>🌱 Gardening</option>
            <option>🌳 Landscaping</option>
            <option>🌾 Lawn Mowing</option>
            <option>✂️ Tree Trimming</option>
            <option>🍂 Leaf Blowing</option>
            <option>🛠️ Fence Installation</option>
            <option>🪲 Pesticide Application</option>
            <option>💧 Sprinkler Repair</option>
            <option>🎨 Outdoor Painting</option>
            <option>❄️ Snow Removal</option>
          </optgroup>
          <optgroup label="🍳 Kitchen & Culinary Skills">
            <option>🍲 Cooking</option>
          </optgroup>
        </select>
      </div>

      <hr>
      <button class="popup-close" onclick="closePopup()">Continue</button>
    </div>
  </div>

  <script>
    function openPopup() {
      document.getElementById("popup-overlay").style.display = "flex";
    }

    function closePopup() {
      document.getElementById("popup-overlay").style.display = "none";
    }

    function addSkill() {
      const skillInput = document.getElementById("skill-input");
      const skill = skillInput.value.trim();
      if (skill !== "") {
        const li = document.createElement("li");
        li.textContent = skill;
        document.getElementById("skills-list").appendChild(li);
        skillInput.value = "";
      } else {
        alert("Please enter a skill.");
      }
    }

    function prepareFormData() {
      // Collect skills from list items
      const skillItems = document.querySelectorAll("#skills-list li");
      const skills = Array.from(skillItems).map(item => item.textContent);
      document.getElementById("skills-hidden").value = skills.join(", ");

      // Collect selected services
      const serviceSelect = document.getElementById("services-dropdown");
      const selectedServices = Array.from(serviceSelect.selectedOptions).map(option => option.textContent);
      document.getElementById("selected-service-hidden").value = selectedServices.join(", ");
    }
  </script>
</body>
</html>
