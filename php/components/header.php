<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../api/notification_system.php';

// Get notifications count using NotificationSystem class
$notifications_count = 0;
if (isset($_SESSION['email'])) {
    $notificationSystem = new NotificationSystem();
    $notifications_count = $notificationSystem->getUnreadCount($_SESSION['email']);
}
?>

<link rel="stylesheet" href="../designs/header1.css">
<link rel="stylesheet" href="../designs/footer.css">
<link rel="stylesheet" href="../designs/shared.css">
<link rel="stylesheet" href="../designs/profile_dashboard.css">
<link rel="stylesheet" href="../designs/services1.css">
<link rel="stylesheet" href="../designs/about_us1.css">
<link rel="stylesheet" href="../designs/contact_us1.css">
<header>
    <div class="logo-container">
        <a href="../pages/home_page.php"><img src="../assets/logo_ss.png" alt="Skill Sphere Logo" class="logo"></a>
        <h1><a href="../pages/home_page.php" style="text-decoration: none; color: inherit;">SkillSphere</a></h1>

    </div>
    <nav>
        <ul>
            <li><a href="../pages/home_page.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'home_page.php' ? 'active' : ''; ?>">HOME</a></li>
            <li><a href="../pages/services.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : ''; ?>">SERVICES</a></li>
            <li><a href="../pages/about_us.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'about_us.php' ? 'active' : ''; ?>">ABOUT</a></li>
            <li><a href="../pages/contact_us.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'contact_us.php' ? 'active' : ''; ?>">CONTACT</a></li>
            <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "superadmin"): ?>
                <li><a href="../pages/superadmin_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'superadmin_dashboard.php' ? 'active' : ''; ?>">SUPER ADMIN</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php if (isset($_SESSION['email'])): ?>
    <div class="user-info">
        <!-- Notifications Button -->
        <div class="notifications-container">
            <button class="notifications-btn" id="notificationsBellBtn" type="button">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 8C18 6.4087 17.3679 4.88258 16.2426 3.75736C15.1174 2.63214 13.5913 2 12 2C10.4087 2 8.88258 2.63214 7.75736 3.75736C6.63214 4.88258 6 6.4087 6 8C6 15 3 17 3 17H21C21 17 18 15 18 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M13.73 21C13.5542 21.3031 13.3019 21.5547 12.9982 21.7295C12.6946 21.9044 12.3504 21.9965 12 21.9965C11.6496 21.9965 11.3054 21.9044 11.0018 21.7295C10.6982 21.5547 10.4458 21.3031 10.27 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <?php if ($notifications_count > 0): ?>
                    <span class="notification-badge"><?php echo $notifications_count; ?></span>
                <?php endif; ?>
            </button>
            
            <!-- Notifications Dropdown -->
            <div class="notifications-dropdown" id="notificationsDropdown">
                <div class="notifications-header">
                    <span>Notifications</span>
                    <button type="button" class="mark-all-read" id="markAllReadBtn">Mark all read</button>
                </div>
                <div id="notificationsList" class="notifications-list">
                    <div class="empty-notifications">Loading notifications...</div>
                </div>
            </div>
        </div>
        
        <!-- User Profile -->
        <a href="../pages/profilee.php" class="user-link">
            <span class="user-icon-name">
                <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <circle cx="11" cy="11" r="11" fill="#1B4D43"/>
                  <ellipse cx="11" cy="15.5" rx="6" ry="4.5" fill="#fff" fill-opacity="0.25"/>
                  <circle cx="11" cy="9" r="4" fill="#fff" fill-opacity="0.7"/>
                </svg>
                <span><?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
            </span>
        </a>
        
        <!-- Logout Button -->
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="logout-form">
            <button type="submit" name="logout" class="logout-btn">Logout</button>
        </form>
        <?php
        // Handle logout here to ensure full session destruction and redirect to homepage
        if (isset($_POST['logout'])) {
            session_unset();
            session_destroy();
            header("Location: ../pages/home_page.php");
            exit();
        }
        ?>
    </div>
    <?php else: ?>
        <div class="join-button">
            <a href="../auth/sign_in.php" class="btn">JOIN US!</a>
        </div>
    <?php endif; ?>
</header>

<style>
/* Notification styles */
.notifications-dropdown {
    position: absolute;
    top: 100%;
    right: -10px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(27,77,67,0.18);
    width: 320px;
    max-height: 400px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
    border: 1px solid #e0e0e0;
    margin-top: 10px;
}

.notifications-dropdown::before {
    content: '';
    position: absolute;
    top: -8px;
    right: 15px;
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-bottom: 8px solid #fff;
}

.notifications-dropdown.show {
    display: block;
    animation: fadeInDown 0.2s ease-out;
}

.notifications-header {
    padding: 12px 16px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-item {
    padding: 12px 16px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    transition: background-color 0.2s;
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #e3f2fd;
    border-left: 4px solid #1B4D43;
}

.notification-content {
    flex: 1;
}

/* Service request specific styles */
.service-request {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.requester-info {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #1B4D43;
    font-weight: 500;
}

.request-message {
    color: #666;
    font-size: 0.9em;
}

.request-time {
    color: #999;
    font-size: 0.85em;
    margin-top: 4px;
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ff4444;
    color: white;
    border-radius: 50%;
    min-width: 18px;
    height: 18px;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
    border: 2px solid #fff;
}

.empty-notifications {
    padding: 12px 16px;
    color: #999;
    text-align: center;
}

.dropdown-section {
    padding: 12px 16px;
    border-bottom: 1px solid #eee;
}

.notif-list, .request-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.notif-item {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.req-to, .req-from {
    font-weight: 500;
    color: #1B4D43;
}

.req-status {
    padding: 2px 4px;
    border-radius: 4px;
    font-size: 0.85em;
}

.req-status.pending {
    background-color: #fff3cd;
    color: #856404;
}

.req-status.completed {
    background-color: #d4edda;
    color: #155724;
}

.req-status.declined {
    background-color: #f8d7da;
    color: #721c24;
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bellBtn = document.getElementById('notificationsBellBtn');
    const dropdown = document.getElementById('notificationsDropdown');
    const notificationsList = document.getElementById('notificationsList');

    function loadNotifications() {
        fetch('../api/notification_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            let html = '';

            // Notifications
            html += '<div class="dropdown-section"><strong>Notifications</strong>';
            if (data.notifications.length) {
                html += '<ul class="notif-list">';
                data.notifications.forEach(notif => {
                    html += `<li class="notif-item notif-${notif.type}">
                        <span class="notif-message">${notif.message}</span>
                        <span class="notif-date">${notif.created_at}</span>
                    </li>`;
                });
                html += '</ul>';
            } else {
                html += '<p class="empty-msg">No notifications yet.</p>';
            }
            html += '</div>';

            // Requests Sent
            html += '<div class="dropdown-section"><strong>Requests Sent</strong>';
            if (data.requests_sent.length) {
                html += '<ul class="request-list">';
                data.requests_sent.forEach(req => {
                    html += `<li>
                        <span class="req-to">To: <b>${req.receiver_email}</b></span><br>
                        <span class="req-service">Service: <b>${req.service}</b></span><br>
                        <span class="req-status ${req.status.toLowerCase()}">Status: ${req.status}</span><br>
                        <span class="notif-date">${req.created_at}</span>
                    </li>`;
                });
                html += '</ul>';
            } else {
                html += '<p class="empty-msg">No requests sent yet.</p>';
            }
            html += '</div>';

            // Requests Received
            html += '<div class="dropdown-section"><strong>Requests Received</strong>';
            if (data.requests_received.length) {
                html += '<ul class="request-list">';
                data.requests_received.forEach(req => {
                    html += `<li>
                        <span class="req-from">From: <b>${req.sender_email}</b></span><br>
                        <span class="req-service">Service: <b>${req.service}</b></span><br>
                        <span class="req-status ${req.status.toLowerCase()}">Status: ${req.status}</span><br>
                        <span class="notif-date">${req.created_at}</span>
                    </li>`;
                });
                html += '</ul>';
            } else {
                html += '<p class="empty-msg">No requests received yet.</p>';
            }
            html += '</div>';

            notificationsList.innerHTML = html;
        });
    }

    bellBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('show');
        if (dropdown.classList.contains('show')) {
            loadNotifications();
        }
    });

    document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target) && !bellBtn.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });
});
</script>
