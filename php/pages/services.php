<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "skillsphere");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Fetch all workers, including phone_number as phone for JS compatibility
$sql = "SELECT *, phone_number AS phone FROM user_profiles";
$result = $conn->query($sql);

$workers = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // For backward compatibility, also set 'phone' key if not already set
        if (!isset($row['phone']) && isset($row['phone_number'])) {
            $row['phone'] = $row['phone_number'];
        }
        $workers[] = $row;
    }
}

// Emoji logic removed as requested.

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - Skill Sphere</title>
    <link rel="stylesheet" href="designs/header1.css">
    <link rel="stylesheet" href="designs/services1.css">
    <link rel="stylesheet" href="designs/footer.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:400,600,700&display=swap">
    <style>
        /* ... styles unchanged ... */
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Inter', Arial, sans-serif;
            background: #f7f8fa;
        }
        body {
            height: 100vh;
            overflow: hidden;
        }
        #container {
            display: flex;
            height: calc(100vh - 60px);
            margin-top: 5px;
            overflow: hidden;
            background: #f7f8fa;
        }
        #sidebar {
            width: 500px; /* Increased width from 370px to 500px */
            background: #fff;
            border-right: 1.5px solid #ececec;
            overflow-y: auto;
            padding: 0;
            height: 100%;
            position: sticky;
            top: 60px;
            flex-shrink: 0;
            box-sizing: border-box;
            box-shadow: 2px 0 12px rgba(27,77,67,0.03);
            display: flex;
            flex-direction: column;
        }
        #sidebar-header {
            padding: 18px 32px 10px 32px;
            border-bottom: 1.5px solid #ececec;
            background: #fafbfc;
        }
        #sidebar-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: #23272f;
            letter-spacing: -0.5px;
        }
        #workerList {
            padding: 10px 18px 10px 18px;
            flex: 1;
            overflow-y: auto;
        }
        .worker-card {
            background: #fff;
            border: 1.2px solid #ececec;
            border-radius: 14px;
            margin-bottom: 12px;
            padding: 12px 16px 12px 16px;
            cursor: pointer;
            transition: box-shadow 0.18s, border 0.18s, background 0.18s;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 1px 4px rgba(27,77,67,0.03);
            position: relative;
            min-height: 62px;
        }
        .worker-card:hover {
            box-shadow: 0 4px 16px rgba(27,77,67,0.07);
            background: #f4f6f8;
            border: 1.2px solid #bfc8d1;
        }
        .worker-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            background: #e9ecef;
            border: 1.5px solid #e9ecef;
            box-shadow: none;
            flex-shrink: 0;
        }
        .worker-info {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .worker-name {
            font-weight: 600;
            font-size: 1.05em;
            color: #23272f;
            margin-bottom: 0;
            letter-spacing: -0.5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .worker-barangay {
            color: #7a7f87;
            font-size: 0.97em;
            font-weight: 500;
            margin-bottom: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .worker-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin: 4px 0 0 0;
        }
        .worker-tag {
            background: #f1f3f6;
            color: #4a5057;
            font-size: 0.91em;
            border-radius: 7px;
            padding: 2px 8px;
            font-weight: 500;
            max-width: 110px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            border: none;
        }
        .worker-tag.service {
            background: #e9ecef;
            color: #23272f;
        }
        .worker-email {
            font-size: 0.92em;
            color: #b0b4bb;
            margin-bottom: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .view-profile-icon {
            position: absolute;
            right: 14px;
            top: 12px;
            background: transparent;
            border: none;
            padding: 0;
            cursor: pointer;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.18s;
            border-radius: 50%;
        }
        .view-profile-icon:hover {
            background: #e9ecef;
        }
        .view-profile-icon svg {
            width: 22px;
            height: 22px;
            fill: #23272f;
            transition: fill 0.18s;
        }
        .view-profile-icon:hover svg {
            fill: #1B4D43;
        }
        #map {
            flex: 1;
            height: 100%;
            min-width: 0;
            border-radius: 0 0 0 0;
            box-shadow: 0 4px 24px rgba(27,77,67,0.04);
            position: relative;
        }
        #map-searchbar-container {
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1002;
            width: 100%;
            max-width: 420px;
            display: flex;
            justify-content: center;
            pointer-events: none;
        }
        #searchbar {
            width: 340px;
            padding: 10px 18px;
            border-radius: 22px;
            border: 1.2px solid #ececec;
            font-size: 1.08em;
            box-shadow: 0 2px 10px rgba(27,77,67,0.10);
            outline: none;
            transition: box-shadow 0.18s, border 0.18s;
            background: #fff;
            color: #23272f;
            pointer-events: auto;
        }
        #searchbar:focus {
            box-shadow: 0 4px 18px rgba(27,77,67,0.13);
            border: 1.2px solid #bfc8d1;
        }
        /* Near Me Button Styles */
        #nearme-btn {
            margin-left: 10px;
            padding: 10px 18px;
            border-radius: 22px;
            border: 1.2px solid #1B4D43;
            background: #fff;
            color: #1B4D43;
            font-size: 1.08em;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(27,77,67,0.10);
            transition: background 0.18s, color 0.18s, border 0.18s;
            pointer-events: auto;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        #nearme-btn:hover {
            background: #1B4D43;
            color: #fff;
        }
        #nearme-btn .loc-icon {
            width: 18px;
            height: 18px;
            display: inline-block;
            vertical-align: middle;
        }
        @media (max-width: 1100px) {
            #sidebar {
                width: 260px;
            }
            .worker-card {
                padding: 8px 6px 8px 6px;
            }
            #map-searchbar-container {
                max-width: 320px;
                top: 8px;
            }
            #searchbar {
                width: 220px;
            }
        }
        @media (max-width: 900px) {
            #sidebar {
                width: 180px;
            }
            #container {
                flex-direction: column;
                margin-top: 50px;
            }
            #sidebar {
                width: 100vw;
                height: 200px;
                position: static;
                border-right: none;
                border-bottom: 1.5px solid #ececec;
                box-shadow: none;
                top: 0;
            }
            #workerList {
                padding: 6px 4px 6px 4px;
            }
            #map {
                height: calc(100vh - 220px);
            }
            #map-searchbar-container {
                top: 6px;
                max-width: 95vw;
            }
            #searchbar {
                width: 90vw;
                max-width: 350px;
            }
        }
        @media (max-width: 600px) {
            #container {
                flex-direction: column;
                margin-top: 40px;
            }
            #sidebar {
                width: 100vw;
                height: 140px;
                position: static;
                border-right: none;
                border-bottom: 1.5px solid #ececec;
                top: 0;
            }
            #map {
                height: calc(100vh - 180px);
            }
            #map-searchbar-container {
                top: 4px;
                max-width: 98vw;
            }
            #searchbar {
                width: 98vw;
                max-width: 98vw;
                font-size: 1em;
            }
            .worker-card {
                min-height: 50px;
            }
            #nearme-btn {
                padding: 8px 10px;
                font-size: 0.98em;
            }
        }
        #workerList::-webkit-scrollbar {
            width: 7px;
        }
        #workerList::-webkit-scrollbar-thumb {
            background: #ececec;
            border-radius: 8px;
        }
        /* --- Profile Modal Simple Improved Styles --- */
        #profileModalContent {
            background: #fff;
            border-radius: 12px;
            padding: 0;
            min-width: 320px;
            max-width: 95vw;
            box-shadow: 0 4px 24px rgba(27,77,67,0.10);
            position: relative;
            font-family: 'Inter', Arial, sans-serif;
            border: 1.5px solid #1B4D43;
            animation: modalPop 0.18s cubic-bezier(.4,1.4,.6,1) 1;
        }
        @keyframes modalPop {
            0% { transform: scale(0.95); opacity: 0.5; }
            100% { transform: scale(1); opacity: 1; }
        }
        .modal-header {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 24px 24px 12px 24px;
            border-bottom: 1px solid #e3e7ed;
            background: #fff;
            border-radius: 12px 12px 0 0;
        }
        .modal-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #1B4D43;
            background: #fff;
            box-shadow: 0 1px 6px rgba(27,77,67,0.08);
        }
        .modal-main-info {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .modal-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin: 6px 0 0 0;
        }
        .modal-tag {
            background: #e3e7ed;
            color: #1B4D43;
            font-size: 0.98em;
            border-radius: 6px;
            padding: 3px 10px;
            font-weight: 500;
            max-width: 120px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            border: none;
            box-shadow: 0 1px 3px rgba(27,77,67,0.05);
        }
        .modal-tag.service {
            background: #1B4D43;
            color: #fff;
        }
        .modal-section {
            padding: 18px 24px 18px 24px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: #f7f8fa;
            border-radius: 0 0 12px 12px;
        }
        .modal-row {
            display: flex;
            align-items: flex-start;
            margin-bottom: 6px;
        }
        .modal-label {
            min-width: 80px;
            color: #1B4D43;
            font-weight: 600;
            font-size: 1em;
            margin-right: 10px;
        }
        .modal-value {
            color: #23272f;
            font-size: 1em;
            font-weight: 500;
            flex: 1;
            word-break: break-all;
        }
        .modal-close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #fff;
            color: #1B4D43;
            border: 1.5px solid #1B4D43;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            font-size: 1.1em;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 1px 4px rgba(27,77,67,0.07);
            transition: background 0.18s, color 0.18s, border 0.18s;
            z-index: 2;
        }
        .modal-close-btn:hover {
            background: #1B4D43;
            color: #fff;
            border: 1.5px solid #1B4D43;
        }
        #hireNowBtn {
            background: #1B4D43 !important;
            color: #fff !important;
            border: none !important;
            border-radius: 6px !important;
            padding: 10px 24px !important;
            font-size: 1em !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            box-shadow: 0 1px 4px rgba(27,77,67,0.10) !important;
            transition: background 0.18s !important;
        }
        #hireNowBtn:hover {
            background: #14513a !important;
        }
        @media (max-width: 600px) {
            #profileModalContent {
                min-width: 0;
                padding: 0;
            }
            .modal-header, .modal-section {
                padding: 12px 6px 10px 6px;
            }
        }
    </style>
</head>
<body>
<?php
include '../components/header.php'; // Include the header with navigation and user info
?>
    <div id="container">
        <div id="sidebar">
            <div id="sidebar-header">
                <h3>Service Providers</h3>
            </div>
            <div id="workerList"></div>
        </div>
        <div id="map">
            <div id="map-searchbar-container">
                <input id="searchbar" type="text" placeholder="Search by name, barangay, skill, or service...">
                <button id="nearme-btn" title="Show workers near me">
                    <span class="loc-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <circle cx="12" cy="12" r="4"></circle>
                            <line x1="12" y1="2" x2="12" y2="6"></line>
                            <line x1="12" y1="18" x2="12" y2="22"></line>
                            <line x1="2" y1="12" x2="6" y2="12"></line>
                            <line x1="18" y1="12" x2="22" y2="12"></line>
                        </svg>
                    </span>
                    Near Me
                </button>
            </div>
        </div>
    </div>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>


    // PHP to JS: workers array
    const workers = <?php echo json_encode($workers); ?>;
    // For mapping: store marker references
    const workerMarkers = [];

    // Store geocoded lat/lng for each worker (to avoid repeated geocoding)
    const workerLatLngs = new Array(workers.length);

    // Helper: get avatar (display the exact profile picture if available)
    function getAvatarUrl(worker) {
        if (worker.profile_picture && worker.profile_picture.trim() !== "") {
            return '../uploads/' + worker.profile_picture;
        }
        return '../assets/logo_ss.png';
    }

    // Helper: Render tags for skills/services (show up to 2 skills and 1 service as tags)
    function renderTags(worker) {
        let tags = [];
        if (worker.skills) {
            let skillsArr = worker.skills.split(',').map(s => s.trim()).filter(Boolean);
            skillsArr.slice(0,2).forEach(skill => {
                tags.push(`<span class="worker-tag" title="${skill}">${skill.length > 16 ? skill.slice(0,14)+'…' : skill}</span>`);
            });
        }
        if (worker.services) {
            let servicesArr = worker.services.split(',').map(s => s.trim()).filter(Boolean);
            if (servicesArr.length > 0) {
                tags.push(`<span class="worker-tag service" title="${servicesArr[0]}">${servicesArr[0].length > 16 ? servicesArr[0].slice(0,14)+'…' : servicesArr[0]}</span>`);
            }
        }
        return tags.length ? `<div class="worker-tags">${tags.join('')}</div>` : '';
    }

    // Helper: Render tags for modal (same as above but with modal-tag class)
    function renderModalTags(worker) {
        let tags = [];
        if (worker.skills) {
            let skillsArr = worker.skills.split(',').map(s => s.trim()).filter(Boolean);
            skillsArr.slice(0,2).forEach(skill => {
                let emoji = skillEmoji[skill] || '';
                tags.push(`<span class="modal-tag" title="${skill}">${emoji} ${skill.length > 16 ? skill.slice(0,14)+'…' : skill}</span>`);
            });
        }
        if (worker.services) {
            let servicesArr = worker.services.split(',').map(s => s.trim()).filter(Boolean);
            if (servicesArr.length > 0) {
                let emoji = serviceEmoji[servicesArr[0]] || '';
                tags.push(`<span class="modal-tag service" title="${servicesArr[0]}">${emoji} ${servicesArr[0].length > 16 ? servicesArr[0].slice(0,14)+'…' : servicesArr[0]}</span>`);
            }
        }
        return tags.length ? `<div class="modal-tags">${tags.join('')}</div>` : '';
    }

    // Initialize map centered in Camarines Sur
    const map = L.map('map').setView([13.6, 123.3], 10);

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Custom icon using logo_ss.png
    const workerIcon = L.icon({
        iconUrl: '../assets/logo_ss.png',
        iconSize: [40, 40],
        iconAnchor: [20, 40],
        popupAnchor: [0, -40]
    });

    // Helper: Geocode address to lat/lon using Nominatim
    function geocode(address) {
        return fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`)
            .then(res => res.json())
            .then(data => (data && data.length > 0) ? [parseFloat(data[0].lat), parseFloat(data[0].lon)] : null);
    }

    // Render worker list in sidebar (minimal, less clustered)
    function renderWorkerList(filteredWorkers) {
        const list = document.getElementById('workerList');
        list.innerHTML = '';
        filteredWorkers.forEach((worker, idx) => {
            const div = document.createElement('div');
            div.className = 'worker-card';
            div.innerHTML = `
                <img class="worker-avatar" src="${getAvatarUrl(worker)}" alt="Avatar">
                <div class="worker-info">
                    <div class="worker-name">${worker.first_name} ${worker.middle_initial || ''} ${worker.last_name}</div>
                    <div class="worker-barangay">${worker.address || ''}</div>
                    ${renderTags(worker)}
                </div>
                <button class="view-profile-icon" onclick="event.stopPropagation(); window.location.href='viewprofile.php?email=' + encodeURIComponent(workers[${idx}].email);" title="View Profile">
                    <img src="../assets/user_icon.png" alt="View Profile" style="width: 22px; height: 22px;">
                </button>
            `;
            div.onclick = () => {
                // Zoom to marker and open popup
                if (workerMarkers[idx]) {
                    map.setView(workerMarkers[idx].getLatLng(), 15);
                    workerMarkers[idx].openPopup();
                }
            };
            list.appendChild(div);
        });
    }

    // Profile Modal (Simple, improved, user-friendly)
    function showProfileModal(idx) {
        const worker = workers[idx];
        let modal = document.getElementById('profileModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'profileModal';
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100vw';
            modal.style.height = '100vh';
            modal.style.background = 'rgba(27,77,67,0.10)';
            modal.style.display = 'flex';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
            modal.style.zIndex = '9999';
            modal.innerHTML = `
                <div id="profileModalContent">
                    <button id="closeProfileModal" class="modal-close-btn" title="Close">&times;</button>
                    <div class="modal-header">
                        <img src="" id="modalAvatar" class="modal-avatar" alt="Avatar">
                        <div class="modal-main-info">
                            <div id="modalName" style="font-size:1.12em;font-weight:700;color:#1B4D43;margin-bottom:2px;letter-spacing:-0.5px;"></div>
                            <div id="modalNameAddress" style="color:#4a5057;font-size:0.98em;font-weight:500;margin-bottom:0;"></div>
                        </div>
                    </div>
                    <div class="modal-section">
                        <div class="modal-row">
                            <span class="modal-label">Email:</span>
                            <span class="modal-value" id="modalEmail"></span>
                        </div>
                        <div class="modal-row">
                            <span class="modal-label">Phone:</span>
                            <span class="modal-value" id="modalPhone"></span>
                        </div>
                        <div class="modal-row">
                            <span class="modal-label">Birthdate:</span>
                            <span class="modal-value" id="modalBirthdate"></span>
                        </div>
                        <div class="modal-row">
                            <span class="modal-label">Address:</span>
                            <span class="modal-value" id="modalAddress"></span>
                        </div>
                        <div class="modal-row">
                            <span class="modal-label">Skills:</span>
                            <span class="modal-value" id="modalSkills"></span>
                        </div>
                        <div class="modal-row">
                            <span class="modal-label">Services:</span>
                            <span class="modal-value" id="modalServices"></span>
                        </div>
                        <div style="margin-top:16px;display:flex;justify-content:flex-end;">
                            <button id="hireNowBtn">Hire Now</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            document.getElementById('closeProfileModal').onclick = () => {
                modal.remove();
            };
            modal.onclick = (e) => {
                if (e.target === modal) modal.remove();
            };
        }
        // Fill modal content (show name, skills, services, email, birthdate, address, phone)
        document.getElementById('modalAvatar').src = getAvatarUrl(worker);
        // Show name in header
        document.getElementById('modalName').textContent = `${worker.first_name} ${worker.middle_initial ? worker.middle_initial + ' ' : ''}${worker.last_name}`;
        // Show address below name in header
        document.getElementById('modalNameAddress').textContent = worker.address ? worker.address : '';

        // Show email
        document.getElementById('modalEmail').textContent = worker.email && worker.email.trim() !== "" ? worker.email : 'None';
        // Show phone (now using .phone, which is mapped from phone_number in PHP)
        document.getElementById('modalPhone').textContent = worker.phone && worker.phone.trim() !== "" ? worker.phone : 'None';
        // Show birthdate
        document.getElementById('modalBirthdate').textContent = worker.birthdate && worker.birthdate.trim() !== "" ? worker.birthdate : 'None';
        // Show address
        document.getElementById('modalAddress').textContent = worker.address && worker.address.trim() !== "" ? worker.address : 'None';

        // Show all skills/services as tags in the modal-section-content, with emoji from database
        document.getElementById('modalSkills').innerHTML = worker.skills
            ? worker.skills.split(',').map(s => {
                s = s.trim();
                let emoji = skillEmoji[s] || '';
                return `<span class="modal-tag">${emoji} ${s}</span>`;
            }).join(' ')
            : '<span style="color:#bbb">None</span>';
        document.getElementById('modalServices').innerHTML = worker.services
            ? worker.services.split(',').map(s => {
                s = s.trim();
                let emoji = serviceEmoji[s] || '';
                return `<span class="modal-tag service">${emoji} ${s}</span>`;
            }).join(' ')
            : '<span style="color:#bbb">None</span>';

        // Optionally, you can add a click handler for the Hire Now button
        document.getElementById('hireNowBtn').onclick = function() {
            alert('Hire Now functionality coming soon!');
        };

        modal.style.display = 'flex';
    }

    // Initial rendering: geocode all workers and plot
    async function plotWorkers(workersToPlot) {
        // Remove old markers
        workerMarkers.forEach(marker => {
            if (marker) map.removeLayer(marker);
        });
        workerMarkers.length = 0;

        for (let i = 0; i < workersToPlot.length; i++) {
            const worker = workersToPlot[i];
            if (!worker.address) {
                workerMarkers[i] = null;
                workerLatLngs[i] = null;
                continue;
            }
            try {
                const latlng = await geocode(worker.address);
                if (latlng) {
                    workerLatLngs[i] = latlng;
                    const popupHtml = `
                        <div class="popup-profile" style="font-family:Inter,Arial,sans-serif;min-width:220px;">
                            <div style="display:flex;align-items:center;gap:14px;">
                                <img class="popup-avatar" src="${getAvatarUrl(worker)}" alt="Avatar" style="width:44px;height:44px;border-radius:50%;object-fit:cover;background:#e9ecef;border:1.5px solid #e9ecef;">
                                <div>
                                    <div class="popup-name" style="font-weight:600;font-size:1.08em;color:#23272f;">${worker.first_name} ${worker.middle_initial || ''} ${worker.last_name}</div>
                                    <div class="popup-barangay" style="color:#7a7f87;font-size:0.97em;font-weight:500;">${worker.address || ''}</div>
                                </div>
                            </div>
                            <div style="margin-top:8px;">
                                <div class="popup-skills" style="font-size:0.97em;color:#4a5057;"><b>Skills:</b> ${
                                    worker.skills
                                        ? worker.skills.split(',').map(s => s.trim()).join(', ')
                                        : '<span style="color:#bbb">None</span>'
                                }</div>
                                <div class="popup-services" style="font-size:0.97em;color:#4a5057;"><b>Services:</b> ${
                                    worker.services
                                        ? worker.services.split(',').map(s => s.trim()).join(', ')
                                        : '<span style="color:#bbb">None</span>'
                                }</div>
                            </div>
                            <div style="margin-top:12px;display:flex;justify-content:space-between;align-items:center;">
                                <button onclick="window.location.href='viewprofile.php?email=' + encodeURIComponent(workers[${i}].email);" style="background:#1B4D43;border:2px solid #1B4D43;border-radius:7px;padding:7px 18px;font-size:1em;font-weight:700;color:#fff;cursor:pointer;transition:background 0.18s,border 0.18s;">View Full Profile</button>
                                <button style="background:#fff;color:#1B4D43;border:2px solid #1B4D43;border-radius:7px;padding:7px 18px;font-size:1em;font-weight:700;cursor:pointer;box-shadow:0 2px 8px rgba(27,77,67,0.10);transition:background 0.18s;">Hire Now</button>
                            </div>
                        </div>
                    `;
                    const marker = L.marker(latlng, {icon: workerIcon})
                        .addTo(map)
                        .bindPopup(popupHtml, {maxWidth: 350});
                    workerMarkers[i] = marker;
                } else {
                    workerMarkers[i] = null;
                    workerLatLngs[i] = null;
                }
            } catch (e) {
                workerMarkers[i] = null;
                workerLatLngs[i] = null;
            }
        }
    }
 
    // Initial render
    renderWorkerList(workers);
    plotWorkers(workers);

    // Search functionality
    document.getElementById('searchbar').addEventListener('input', async function() {
        const q = this.value.trim().toLowerCase();
        const filtered = workers.filter(w =>
            (w.first_name + ' ' + (w.middle_initial || '') + ' ' + w.last_name).toLowerCase().includes(q) ||
            (w.address || '').toLowerCase().includes(q) ||
            (w.skills || '').toLowerCase().includes(q) ||
            (w.services || '').toLowerCase().includes(q) ||
            (w.email || '').toLowerCase().includes(q)
        );
        renderWorkerList(filtered);
        plotWorkers(filtered);
    });

    // --- NEAR ME FEATURE ---
    // Helper: Haversine distance in km
    function haversine(lat1, lon1, lat2, lon2) {
        function toRad(x) { return x * Math.PI / 180; }
        const R = 6371; // km
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    // Near Me button logic
    document.getElementById('nearme-btn').addEventListener('click', async function() {
        const nearMeBtn = this;
        if (!navigator.geolocation) {
            alert("Geolocation is not supported by your browser.");
            return;
        }
        nearMeBtn.disabled = true;
        nearMeBtn.textContent = "Locating...";

        // Use high accuracy and longer timeout for better results
        navigator.geolocation.getCurrentPosition(
            async function(position) {
                const userLat = position.coords.latitude;
                const userLon = position.coords.longitude;

                // Wait for all workers to be geocoded (if not already)
                for (let i = 0; i < workers.length; i++) {
                    if (!workerLatLngs[i] && workers[i].address) {
                        try {
                            workerLatLngs[i] = await geocode(workers[i].address);
                        } catch (e) {
                            workerLatLngs[i] = null;
                        }
                    }
                }

                // Find workers within 10km (change as needed)
                const NEAR_RADIUS_KM = 10;
                const nearWorkers = [];
                for (let i = 0; i < workers.length; i++) {
                    const latlng = workerLatLngs[i];
                    if (latlng) {
                        const dist = haversine(userLat, userLon, latlng[0], latlng[1]);
                        if (dist <= NEAR_RADIUS_KM) {
                            nearWorkers.push(workers[i]);
                        }
                    }
                }

                if (nearWorkers.length === 0) {
                    alert("No workers found within 10km of your location.");
                    renderWorkerList([]);
                    plotWorkers([]);
                } else {
                    renderWorkerList(nearWorkers);
                    plotWorkers(nearWorkers);
                    // Zoom map to user location and show a marker
                    map.setView([userLat, userLon], 13);
                    // Add a marker for the user
                    if (window._userMarker) map.removeLayer(window._userMarker);
                    window._userMarker = L.marker([userLat, userLon], {
                        icon: L.icon({
                            iconUrl: "https://cdn-icons-png.flaticon.com/512/64/64113.png",
                            iconSize: [36, 36],
                            iconAnchor: [18, 36],
                            popupAnchor: [0, -36]
                        })
                    }).addTo(map).bindPopup("<b>You are here</b>").openPopup();
                }
                nearMeBtn.disabled = false;
                nearMeBtn.innerHTML = `<span class="loc-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <circle cx="12" cy="12" r="4"></circle>
                                <line x1="12" y1="2" x2="12" y2="6"></line>
                                <line x1="12" y1="18" x2="12" y2="22"></line>
                                <line x1="2" y1="12" x2="6" y2="12"></line>
                                <line x1="18" y1="12" x2="22" y2="12"></line>
                            </svg>
                        </span> Near Me`;
            },
            function(error) {
                let message = "Unable to retrieve your location.";
                // Provide more specific error messages
                if (error.code === error.PERMISSION_DENIED) {
                    message = "Location permission denied. Please allow location access in your browser settings.";
                } else if (error.code === error.POSITION_UNAVAILABLE) {
                    message = "Location information is unavailable. Try moving to an area with better signal or check your device settings.";
                } else if (error.code === error.TIMEOUT) {
                    message = "Location request timed out. Please try again.";
                }
                alert(message);
                nearMeBtn.disabled = false;
                nearMeBtn.innerHTML = `<span class="loc-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <circle cx="12" cy="12" r="4"></circle>
                                <line x1="12" y1="2" x2="12" y2="6"></line>
                                <line x1="12" y1="18" x2="12" y2="22"></line>
                                <line x1="2" y1="12" x2="6" y2="12"></line>
                                <line x1="18" y1="12" x2="22" y2="12"></line>
                            </svg>
                        </span> Near Me`;
            },
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            }
        );
    });
    </script>
</body>
</html>
<?php
// Handle logout if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: /Skill_Sphere/php/pages/home_page.php");
    exit;
}
?>