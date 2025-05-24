// JS for setup.php (moved from inline)
// openPopup, closePopup, prepareFormData, updateSelectedDisplay, removeSelectedSkill, removeSelectedService, DOMContentLoaded event, etc. 

// --- Skill category mapping for JS (mirrors PHP $skill_to_category) ---
const skillToCategory = window.skillToCategory || {};
const skillCategories = window.skillCategories || [];
const serviceCategories = window.serviceCategories || [];
const serviceToCategory = window.serviceToCategory || {};

function openPopup() {
  document.getElementById("popup-overlay").style.display = "flex";
}

function closePopup() {
  document.getElementById("popup-overlay").style.display = "none";
  updateSelectedDisplay();
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

// --- Display selected skills and services, categorized ---
function updateSelectedDisplay() {
  // Skills
  const skillCheckboxes = document.querySelectorAll('#skills-checkboxes input[type="checkbox"]:checked');
  const selectedSkills = Array.from(skillCheckboxes).map(cb => cb.value);

  // Categorize skills
  const skillsByCat = {};
  skillCategories.forEach(cat => skillsByCat[cat] = []);
  selectedSkills.forEach(skill => {
    const cat = skillToCategory[skill] || "Other";
    if (!skillsByCat[cat]) skillsByCat[cat] = [];
    skillsByCat[cat].push(skill);
  });

  // Build HTML for skills
  let skillsHTML = '';
  let hasSkills = false;
  for (const cat of skillCategories) {
    if (skillsByCat[cat] && skillsByCat[cat].length > 0) {
      hasSkills = true;
      skillsHTML += `<div class="selected-list-category"><strong>${cat}</strong><ul>`;
      skillsByCat[cat].forEach(skill => {
        skillsHTML += `<li><span class="selected-chip">${skill}<button type="button" class="remove-chip" title="Remove" onclick="removeSelectedSkill('${skill.replace(/'/g,"\\'")}')">&times;</button></span></li>`;
      });
      skillsHTML += `</ul></div>`;
    }
  }
  if (!hasSkills) {
    skillsHTML = `<span class="selected-list-empty">No skills selected.</span>`;
  }
  document.getElementById('selected-skills-list').innerHTML = `<h4>Selected Skills</h4>${skillsHTML}`;

  // Services
  const serviceCheckboxes = document.querySelectorAll('#services-checkboxes input[type="checkbox"]:checked');
  const selectedServices = Array.from(serviceCheckboxes).map(cb => cb.value);

  // Categorize services
  const servicesByCat = {};
  serviceCategories.forEach(cat => servicesByCat[cat] = []);
  selectedServices.forEach(service => {
    const cat = serviceToCategory[service] || "Other";
    if (!servicesByCat[cat]) servicesByCat[cat] = [];
    servicesByCat[cat].push(service);
  });

  // Build HTML for services
  let servicesHTML = '';
  let hasServices = false;
  for (const cat of serviceCategories) {
    if (servicesByCat[cat] && servicesByCat[cat].length > 0) {
      hasServices = true;
      servicesHTML += `<div class="selected-list-category"><strong>${cat}</strong><ul>`;
      servicesByCat[cat].forEach(service => {
        servicesHTML += `<li><span class="selected-chip">${service}<button type="button" class="remove-chip" title="Remove" onclick="removeSelectedService('${service.replace(/'/g,"\\'")}')">&times;</button></span></li>`;
      });
      servicesHTML += `</ul></div>`;
    }
  }
  if (!hasServices) {
    servicesHTML = `<span class="selected-list-empty">No services selected.</span>`;
  }
  document.getElementById('selected-services-list').innerHTML = `<h4>Selected Services</h4>${servicesHTML}`;

  // Show/hide section
  document.getElementById('selected-list-section').style.display = (hasSkills || hasServices) ? 'block' : 'none';
}

// Remove skill chip and uncheck the corresponding checkbox
function removeSelectedSkill(skill) {
  const skillCheckboxes = document.querySelectorAll('#skills-checkboxes input[type="checkbox"]');
  for (const cb of skillCheckboxes) {
    if (cb.value === skill) {
      cb.checked = false;
      break;
    }
  }
  updateSelectedDisplay();
}

// Remove service chip and uncheck the corresponding checkbox
function removeSelectedService(service) {
  const serviceCheckboxes = document.querySelectorAll('#services-checkboxes input[type="checkbox"]');
  for (const cb of serviceCheckboxes) {
    if (cb.value === service) {
      cb.checked = false;
      break;
    }
  }
  updateSelectedDisplay();
}

// Update display when popup closes and on page load
document.addEventListener('DOMContentLoaded', function() {
  updateSelectedDisplay();
  // Also update when checkboxes change
  document.getElementById('skills-checkboxes').addEventListener('change', updateSelectedDisplay);
  document.getElementById('services-checkboxes').addEventListener('change', updateSelectedDisplay);
}); 