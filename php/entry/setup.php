<?php
include_once '../service_categories.php';
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Skill Sphere Profile</title>
  <link rel="stylesheet" href="../designs/shared.css">
  <link rel="stylesheet" href="../designs/footer.css">
  <link rel="stylesheet" href="../designs/setup1.css">
  <link rel="stylesheet" href="../designs/header1.css">
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
  </style>
</head>
<body>
  <?php include '../header.php'; ?>
  <main class="section">
    <div class="container">
      <div class="card" style="max-width: 700px; margin: 0 auto;">
        <h1 class="section-title" style="margin-bottom: 8px;">Almost done</h1>
        <p style="text-align:center; margin-bottom: 32px; color: var(--text-light);">Be found by clients. Create your Skill Sphere Profile.</p>
        <form action="server2.php" method="POST" onsubmit="prepareFormData()">
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
            <button type="button" class="btn btn-accent" onclick="openPopup()">ADD</button>
          </div>

          <!-- Hidden fields to store dynamic input -->
          <input type="hidden" name="skills" id="skills-hidden">
          <input type="hidden" name="selected-service" id="selected-service-hidden">

          <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: var(--spacing-lg);">Save & Continue</button>
        </form>
      </div>
    </div>
  </main>

  <?php include '../footer.php'; ?>

  <!-- Popup -->
  <div id="popup-overlay" class="popup-overlay" style="display:none; align-items: center; justify-content: center;">
    <div class="popup-content card" style="width: 380px; max-width: 96vw; max-height: 80vh; min-width: 0; position: relative; padding: 18px 18px 0 18px; margin: 0 auto; border-radius: 18px; overflow-y: auto; box-shadow: 0 8px 32px rgba(0,0,0,0.12);">
      <h2 class="section-title" style="font-size: var(--text-2xl); margin-bottom: 0;">Add to your profile</h2>
      <hr style="margin: var(--spacing-lg) 0;">

      <div class="popup-section">
        <h3 style="font-size: var(--text-xl); font-weight: var(--font-bold);">Let's add your skills</h3>
        <p style="color: var(--text-light);">Your listed skills help us connect you with clients who need your services.</p>
        <div id="skills-checkboxes" style="display: flex; flex-direction: column; gap: 10px; max-height: 220px; overflow-y: auto; border: 1px solid #bbb; border-radius: 8px; padding: 10px; background: var(--surface-hover);">
          <label><input type="checkbox" value="Plumbing"> 🔩 Plumbing</label>
          <label><input type="checkbox" value="Carpentry"> 🪚 Carpentry</label>
          <label><input type="checkbox" value="Electrical Work"> ⚡ Electrical Work</label>
          <label><input type="checkbox" value="Painting"> 🎨 Painting</label>
          <label><input type="checkbox" value="Tiling"> 🧱 Tiling</label>
          <label><input type="checkbox" value="Roofing"> 🏠 Roofing</label>
          <label><input type="checkbox" value="Masonry"> 🛠️ Masonry</label>
          <label><input type="checkbox" value="Welding"> 🔥 Welding</label>
          <label><input type="checkbox" value="Auto Repair"> 🚗 Auto Repair</label>
          <label><input type="checkbox" value="Motorcycle Repair"> 🏍️ Motorcycle Repair</label>
          <label><input type="checkbox" value="Appliance Repair"> 🔌 Appliance Repair</label>
          <label><input type="checkbox" value="Furniture Assembly"> 🪑 Furniture Assembly</label>
          <label><input type="checkbox" value="Locksmithing"> 🔑 Locksmithing</label>
          <label><input type="checkbox" value="Glass Cutting"> 🔪 Glass Cutting</label>
          <label><input type="checkbox" value="Floor Installation"> 🪵 Floor Installation</label>
          <label><input type="checkbox" value="Drywall Repair"> 🛠️ Drywall Repair</label>
          <label><input type="checkbox" value="HVAC Repair"> ❄️ HVAC Repair</label>
          <label><input type="checkbox" value="Gutter Cleaning"> 🧹 Gutter Cleaning</label>
          <label><input type="checkbox" value="Pest Control"> 🐜 Pest Control</label>
          <label><input type="checkbox" value="Septic Tank Cleaning"> 🚽 Septic Tank Cleaning</label>
          <label><input type="checkbox" value="House Cleaning"> 🏠 House Cleaning</label>
          <label><input type="checkbox" value="Deep Cleaning"> 🧼 Deep Cleaning</label>
          <label><input type="checkbox" value="Window Cleaning"> 🪟 Window Cleaning</label>
          <label><input type="checkbox" value="Laundry and Ironing"> 👕 Laundry and Ironing</label>
          <label><input type="checkbox" value="Carpet Cleaning"> 🧽 Carpet Cleaning</label>
          <label><input type="checkbox" value="Pressure Washing"> 💦 Pressure Washing</label>
          <label><input type="checkbox" value="Pool Cleaning"> 🏊 Pool Cleaning</label>
          <label><input type="checkbox" value="Organizing (Decluttering)"> 📦 Organizing (Decluttering)</label>
          <label><input type="checkbox" value="Trash Removal"> 🗑️ Trash Removal</label>
          <label><input type="checkbox" value="Upholstery Cleaning"> 🛋️ Upholstery Cleaning</label>
          <label><input type="checkbox" value="Gardening"> 🌱 Gardening</label>
          <label><input type="checkbox" value="Landscaping"> 🌳 Landscaping</label>
          <label><input type="checkbox" value="Lawn Mowing"> 🌾 Lawn Mowing</label>
          <label><input type="checkbox" value="Tree Trimming"> ✂️ Tree Trimming</label>
          <label><input type="checkbox" value="Leaf Blowing"> 🍂 Leaf Blowing</label>
          <label><input type="checkbox" value="Fence Installation"> 🛠️ Fence Installation</label>
          <label><input type="checkbox" value="Pesticide Application"> 🪲 Pesticide Application</label>
          <label><input type="checkbox" value="Sprinkler Repair"> 💧 Sprinkler Repair</label>
          <label><input type="checkbox" value="Outdoor Painting"> 🎨 Outdoor Painting</label>
          <label><input type="checkbox" value="Snow Removal"> ❄️ Snow Removal</label>
          <label><input type="checkbox" value="Cooking"> 🍳 Cooking</label>
          <label><input type="checkbox" value="Baking"> 🧁 Baking</label>
          <label><input type="checkbox" value="Catering"> 🍽️ Catering</label>
          <label><input type="checkbox" value="Food Plating"> 🍲 Food Plating</label>
          <label><input type="checkbox" value="Kitchen Cleaning"> 🧽 Kitchen Cleaning</label>
          <label><input type="checkbox" value="Barbecuing"> 🍖 Barbecuing</label>
          <label><input type="checkbox" value="Meal Prep"> 🥗 Meal Prep</label>
          <label><input type="checkbox" value="Juice/Smoothie Making"> 🥤 Juice/Smoothie Making</label>
          <label><input type="checkbox" value="Butchering"> 🔪 Butchering</label>
          <label><input type="checkbox" value="Inventory Management (Kitchen)"> 📦 Inventory Management (Kitchen)</label>
          <label><input type="checkbox" value="Sewing"> 🧵 Sewing</label>
          <label><input type="checkbox" value="Embroidery"> 🪡 Embroidery</label>
          <label><input type="checkbox" value="Crochet"> 🧶 Crochet</label>
          <label><input type="checkbox" value="Knitting"> 🧶 Knitting</label>
          <label><input type="checkbox" value="Jewelry Repair"> 💍 Jewelry Repair</label>
          <label><input type="checkbox" value="Shoe Repair"> 👞 Shoe Repair</label>
          <label><input type="checkbox" value="Toy Repair"> 🧸 Toy Repair</label>
          <label><input type="checkbox" value="Candle Making"> 🕯️ Candle Making</label>
          <label><input type="checkbox" value="Pottery"> 🏺 Pottery</label>
          <label><input type="checkbox" value="DIY Woodwork"> 🪵 DIY Woodwork</label>
          <label><input type="checkbox" value="Basic Computer Repair"> 💻 Basic Computer Repair</label>
          <label><input type="checkbox" value="Printer Setup"> 🖨️ Printer Setup</label>
          <label><input type="checkbox" value="Wi-Fi Setup"> 📶 Wi-Fi Setup</label>
          <label><input type="checkbox" value="Router Troubleshooting"> 📡 Router Troubleshooting</label>
          <label><input type="checkbox" value="Smart TV Setup"> 📺 Smart TV Setup</label>
          <label><input type="checkbox" value="CCTV Installation"> 📹 CCTV Installation</label>
          <label><input type="checkbox" value="Alarm System Setup"> 🚨 Alarm System Setup</label>
          <label><input type="checkbox" value="Cable Management"> 🔌 Cable Management</label>
          <label><input type="checkbox" value="Gadget Troubleshooting"> 🔧 Gadget Troubleshooting</label>
          <label><input type="checkbox" value="Software Installation"> 💾 Software Installation</label>
          <label><input type="checkbox" value="Childcare"> 👶 Childcare</label>
          <label><input type="checkbox" value="Elderly Care"> 🧓 Elderly Care</label>
          <label><input type="checkbox" value="Special Needs Assistance"> ♿ Special Needs Assistance</label>
          <label><input type="checkbox" value="Basic First Aid"> ⛑️ Basic First Aid</label>
          <label><input type="checkbox" value="Medication Reminders"> 💊 Medication Reminders</label>
          <label><input type="checkbox" value="Feeding Assistance"> 🍽️ Feeding Assistance</label>
          <label><input type="checkbox" value="Companion Care"> 🤝 Companion Care</label>
          <label><input type="checkbox" value="Diaper Changing"> 🧷 Diaper Changing</label>
          <label><input type="checkbox" value="Bathing Assistance"> 🛁 Bathing Assistance</label>
          <label><input type="checkbox" value="Bedside Support"> 🛏️ Bedside Support</label>
          <label><input type="checkbox" value="Grocery Shopping"> 🛒 Grocery Shopping</label>
          <label><input type="checkbox" value="Running Errands"> 🏃 Running Errands</label>
          <label><input type="checkbox" value="Pet Walking"> 🐕 Pet Walking</label>
          <label><input type="checkbox" value="Pet Bathing"> 🛁 Pet Bathing</label>
          <label><input type="checkbox" value="Cooking for Elders"> 🍲 Cooking for Elders</label>
          <label><input type="checkbox" value="House Sitting"> 🏠 House Sitting</label>
          <label><input type="checkbox" value="Plant Watering"> 💧 Plant Watering</label>
          <label><input type="checkbox" value="Mail Sorting"> 📬 Mail Sorting</label>
          <label><input type="checkbox" value="Light Decoration (Holidays)"> 🎉 Light Decoration (Holidays)</label>
          <label><input type="checkbox" value="Delivery Assistance"> 📦 Delivery Assistance</label>
          <label><input type="checkbox" value="Sign Painting"> 🖌️ Sign Painting</label>
          <label><input type="checkbox" value="Basic Graphic Design"> 🖥️ Basic Graphic Design</label>
          <label><input type="checkbox" value="Poster Making"> 📰 Poster Making</label>
          <label><input type="checkbox" value="Event Setup"> 🎪 Event Setup</label>
          <label><input type="checkbox" value="Balloon Arrangement"> 🎈 Balloon Arrangement</label>
          <label><input type="checkbox" value="Face Painting"> 🎭 Face Painting</label>
          <label><input type="checkbox" value="Sound System Setup"> 🔊 Sound System Setup</label>
          <label><input type="checkbox" value="Stage Decoration"> 🎤 Stage Decoration</label>
          <label><input type="checkbox" value="Costume Repair"> 👗 Costume Repair</label>
          <label><input type="checkbox" value="Recycling Management"> ♻️ Recycling Management</label>
        </div>
      </div>

      <div class="popup-section" style="margin-top: var(--spacing-xl);">
        <h3 style="font-size: var(--text-xl); font-weight: var(--font-bold);">Let's add your services</h3>
        <p style="color: var(--text-light);">Your listed services help us connect you with clients who need your services.</p>
        <div id="services-checkboxes" style="display: flex; flex-direction: column; gap: 10px; max-height: 220px; overflow-y: auto; border: 1px solid #bbb; border-radius: 8px; padding: 10px; background: var(--surface-hover);">
          <?php foreach ($categories as $label => $services): ?>
              <strong><?php echo htmlspecialchars($label); ?></strong>
              <?php foreach ($services as $service): ?>
                  <label><input type="checkbox" value="<?php echo htmlspecialchars($service); ?>"> <?php echo htmlspecialchars($service); ?></label>
              <?php endforeach; ?>
          <?php endforeach; ?>
        </div>
      </div>

      <hr style="margin: var(--spacing-lg) 0;">
      <div class="popup-footer" style="display: flex; justify-content: flex-end; background: none; border-top: none;">
        <button class="btn btn-accent popup-close" onclick="closePopup()">Continue</button>
      </div>
    </div>
  </div>

  <script>
    function openPopup() {
      document.getElementById("popup-overlay").style.display = "flex";
    }

    function closePopup() {
      document.getElementById("popup-overlay").style.display = "none";
    }

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
  </script>
</body>
</html>
