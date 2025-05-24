<?php
include_once '../components/service_categories.php';
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile Setup - Skill Sphere</title>
  <link rel="stylesheet" href="/Skill-Sphere/php/designs/header1.css">
  <link rel="stylesheet" href="/Skill-Sphere/php/designs/shared.css">
  <link rel="stylesheet" href="/Skill-Sphere/php/designs/setup1.css">
  <link rel="stylesheet" href="/Skill-Sphere/php/designs/footer.css">
</head>
<body>
  <?php include '../components/header.php'; ?>
  <main class="section">
    <div class="container">
      <div class="card" style="max-width: 700px; margin: 0 auto;">
        <h1 class="section-title" style="margin-bottom: 8px;">Almost done</h1>
        <p style="text-align:center; margin-bottom: 32px; color: var(--text-light);">Be found by clients. Create your Skill Sphere Profile.</p>
        <form action="server2.php" method="POST" onsubmit="prepareFormData()">
          <div class="row">
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

          <div class="form-group" style="display: flex; align-items: center; gap: 16px;">
            <label for="experience" style="margin-bottom: 0;">Years of Experience</label>
            <input class="form-control" style="max-width: 100px;" type="number" id="experience" name="experience" min="0" max="50" placeholder="0" required>
            <span>years</span>
          </div>

          <div class="form-group skills-section">
            <label for="skills">Skills & Services</label>
            <button type="button" class="btn btn-accent" onclick="openPopup()">Add</button>
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

          <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 24px;">Save & Continue</button>
        </form>
      </div>
    </div>
  </main>

  <?php include '../components/footer.php'; ?>

  <!-- Popup -->
  <div id="popup-overlay" class="popup-overlay" style="display:none; align-items: center; justify-content: center;">
    <div class="popup-content card" style="width: 420px; max-width: 96vw; max-height: 80vh; min-width: 0; position: relative; padding: 18px 18px 0 18px; margin: 0 auto; border-radius: 18px; overflow-y: auto; box-shadow: 0 8px 32px rgba(0,0,0,0.12);">
      <h2 class="section-title" style="font-size: 1.3rem; margin-bottom: 0;">Add to your profile</h2>
      <hr style="margin: 18px 0;">

      <div class="popup-section">
        <h3 style="font-size: 1.1rem; font-weight: 700;">Skills</h3>
        <input type="text" id="skill-search" placeholder="Search skills..." style="width:100%;margin-bottom:10px;padding:8px;border-radius:6px;border:1px solid #bbb;">
        <div id="skills-checkboxes" style="display: flex; flex-direction: column; gap: 8px; max-height: 120px; overflow-y: auto; border: 1px solid #bbb; border-radius: 8px; padding: 10px; background: #f8f9fa;">
          <!-- Skills checkboxes will be rendered here -->
        </div>
      </div>

      <div class="popup-section" style="margin-top: 18px;">
        <h3 style="font-size: 1.1rem; font-weight: 700;">Services</h3>
        <input type="text" id="service-search" placeholder="Search services..." style="width:100%;margin-bottom:10px;padding:8px;border-radius:6px;border:1px solid #bbb;">
        <div id="services-checkboxes" style="display: flex; flex-direction: column; gap: 8px; max-height: 120px; overflow-y: auto; border: 1px solid #bbb; border-radius: 8px; padding: 10px; background: #f8f9fa;">
          <!-- Services checkboxes will be rendered here -->
        </div>
      </div>

      <hr style="margin: 18px 0;">
      <div class="popup-footer" style="display: flex; justify-content: flex-end;">
        <button class="btn btn-accent popup-close" onclick="closePopup()">Continue</button>
      </div>
    </div>
  </div>

  <script>
    // --- Data ---
    const skillsList = [
      "Plumbing", "Carpentry", "Electrical Work", "Painting", "Tiling", "Roofing", "Masonry", "Welding", "Auto Repair", "Motorcycle Repair", "Appliance Repair", "Furniture Assembly", "Locksmithing", "Glass Cutting", "Floor Installation", "Drywall Repair", "HVAC Repair", "Gutter Cleaning", "Pest Control", "Septic Tank Cleaning", "House Cleaning", "Deep Cleaning", "Window Cleaning", "Laundry and Ironing", "Carpet Cleaning", "Pressure Washing", "Pool Cleaning", "Organizing (Decluttering)", "Trash Removal", "Upholstery Cleaning", "Gardening", "Landscaping", "Lawn Mowing", "Tree Trimming", "Leaf Blowing", "Fence Installation", "Pesticide Application", "Sprinkler Repair", "Outdoor Painting", "Snow Removal", "Cooking", "Baking", "Catering", "Food Plating", "Kitchen Cleaning", "Barbecuing", "Meal Prep", "Juice/Smoothie Making", "Butchering", "Inventory Management (Kitchen)", "Sewing", "Embroidery", "Crochet", "Knitting", "Jewelry Repair", "Shoe Repair", "Toy Repair", "Candle Making", "Pottery", "DIY Woodwork", "Basic Computer Repair", "Printer Setup", "Wi-Fi Setup", "Router Troubleshooting", "Smart TV Setup", "CCTV Installation", "Alarm System Setup", "Cable Management", "Gadget Troubleshooting", "Software Installation", "Childcare", "Elderly Care", "Special Needs Assistance", "Basic First Aid", "Medication Reminders", "Feeding Assistance", "Companion Care", "Diaper Changing", "Bathing Assistance", "Bedside Support", "Grocery Shopping", "Running Errands", "Pet Walking", "Pet Bathing", "Cooking for Elders", "House Sitting", "Plant Watering", "Mail Sorting", "Light Decoration (Holidays)", "Delivery Assistance", "Sign Painting", "Basic Graphic Design", "Poster Making", "Event Setup", "Balloon Arrangement", "Face Painting", "Sound System Setup", "Stage Decoration", "Costume Repair", "Recycling Management"
    ];

    // You can generate this from PHP if you want dynamic categories
    const servicesList = [
      <?php
        $allServices = [];
        foreach ($categories as $cat => $services) {
          foreach ($services as $service) {
            $allServices[] = addslashes($service);
          }
        }
        echo '"' . implode('","', $allServices) . '"';
      ?>
    ];

    // --- Render Checkboxes ---
    function renderCheckboxes(list, containerId, searchValue) {
      const container = document.getElementById(containerId);
      container.innerHTML = '';
      list.filter(item => item.toLowerCase().includes(searchValue.toLowerCase()))
        .forEach(item => {
          const label = document.createElement('label');
          label.style.display = 'flex';
          label.style.alignItems = 'center';
          label.style.gap = '8px';
          const checkbox = document.createElement('input');
          checkbox.type = 'checkbox';
          checkbox.value = item;
          checkbox.checked = (containerId === 'skills-checkboxes')
            ? selectedSkillsSet.has(item)
            : selectedServicesSet.has(item);
          checkbox.onchange = function() {
            if (containerId === 'skills-checkboxes') {
              if (checkbox.checked) selectedSkillsSet.add(item);
              else selectedSkillsSet.delete(item);
              updateSelectedLists();
            } else {
              if (checkbox.checked) selectedServicesSet.add(item);
              else selectedServicesSet.delete(item);
              updateSelectedLists();
            }
          };
          label.appendChild(checkbox);
          label.appendChild(document.createTextNode(item));
          container.appendChild(label);
        });
    }

    // --- State ---
    const selectedSkillsSet = new Set();
    const selectedServicesSet = new Set();

    // --- Search Handlers ---
    document.addEventListener('DOMContentLoaded', function() {
      renderCheckboxes(skillsList, 'skills-checkboxes', '');
      renderCheckboxes(servicesList, 'services-checkboxes', '');

      document.getElementById('skill-search').addEventListener('input', function() {
        renderCheckboxes(skillsList, 'skills-checkboxes', this.value);
      });
      document.getElementById('service-search').addEventListener('input', function() {
        renderCheckboxes(servicesList, 'services-checkboxes', this.value);
      });

      // Initial update for selected lists
      updateSelectedLists();
    });

    // --- Update Selected Lists in Main Form ---
    function updateSelectedLists() {
      // Skills
      const skillsArr = Array.from(selectedSkillsSet);
      document.getElementById('selected-skills-list').innerHTML = '';
      skillsArr.forEach(skill => {
        const item = document.createElement('span');
        item.className = 'selected-item';
        item.textContent = skill;
        const removeBtn = document.createElement('button');
        removeBtn.className = 'remove-btn';
        removeBtn.type = 'button';
        removeBtn.title = 'Remove';
        removeBtn.innerHTML = '&times;';
        removeBtn.onclick = function() {
          selectedSkillsSet.delete(skill);
          // Uncheck in popup if open
          const checkboxes = document.querySelectorAll('#skills-checkboxes input[type="checkbox"]');
          checkboxes.forEach(cb => { if (cb.value === skill) cb.checked = false; });
          updateSelectedLists();
        };
        item.appendChild(removeBtn);
        document.getElementById('selected-skills-list').appendChild(item);
      });

      // Services
      const servicesArr = Array.from(selectedServicesSet);
      document.getElementById('selected-services-list').innerHTML = '';
      servicesArr.forEach(service => {
        const item = document.createElement('span');
        item.className = 'selected-item';
        item.textContent = service;
        const removeBtn = document.createElement('button');
        removeBtn.className = 'remove-btn';
        removeBtn.type = 'button';
        removeBtn.title = 'Remove';
        removeBtn.innerHTML = '&times;';
        removeBtn.onclick = function() {
          selectedServicesSet.delete(service);
          // Uncheck in popup if open
          const checkboxes = document.querySelectorAll('#services-checkboxes input[type="checkbox"]');
          checkboxes.forEach(cb => { if (cb.value === service) cb.checked = false; });
          updateSelectedLists();
        };
        item.appendChild(removeBtn);
        document.getElementById('selected-services-list').appendChild(item);
      });

      // Update hidden fields for form submission
      document.getElementById("skills-hidden").value = skillsArr.join(", ");
      document.getElementById("selected-service-hidden").value = servicesArr.join(", ");
    }

    function openPopup() {
      document.getElementById("popup-overlay").style.display = "flex";
      // Render checkboxes with current selections
      renderCheckboxes(skillsList, 'skills-checkboxes', document.getElementById('skill-search').value || '');
      renderCheckboxes(servicesList, 'services-checkboxes', document.getElementById('service-search').value || '');
    }

    function closePopup() {
      document.getElementById("popup-overlay").style.display = "none";
      updateSelectedLists();
    }

    function prepareFormData() {
      // Hidden fields are already updated by updateSelectedLists
    }
  </script>
</body>
</html>