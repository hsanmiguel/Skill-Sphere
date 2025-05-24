<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once __DIR__ . '/notifications_handler.php';

// Centralized logout logic
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: /Skill-Sphere/php/entry/sign_in.php");
    exit();
}

// Fetch unread notifications count for the logged-in user
$unread_count = 0;
if (isset($_SESSION['email'])) {
    $unread_count = getUnreadCount($_SESSION['email']);
}
?>
<link rel="stylesheet" href="/Skill-Sphere/php/designs/notifications.css">

<header>
    <div class="logo-container">
        <a href="/Skill-Sphere/php/pages/home_page.php"><img src="/Skill-Sphere/php/assets/logo_ss.png" alt="Skill Sphere Logo" class="logo"></a>
        <h1>Skill Sphere</h1>
    </div>
    <nav>
        <ul>
            <li><a href="/Skill-Sphere/php/pages/home_page.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'home_page.php' ? 'active' : ''; ?>">HOME</a></li>
            <li><a href="/Skill-Sphere/php/pages/services.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : ''; ?>">SERVICES</a></li>
            <li><a href="/Skill-Sphere/php/pages/about_us.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'about_us.php' ? 'active' : ''; ?>">ABOUT</a></li>
            <li><a href="/Skill-Sphere/php/pages/contact_us.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'contact_us.php' ? 'active' : ''; ?>">CONTACT</a></li>
            <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "superadmin"): ?>
                <li><a href="/Skill-Sphere/php/pages/superadmin_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'superadmin_dashboard.php' ? 'active' : ''; ?>">SUPER ADMIN</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php if (isset($_SESSION['email'])): ?>
    <div class="header-user-row">
        <!-- Notification Icon -->
        <div class="header-notification">
            <a href="#" id="notificationToggle" title="Notifications">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 22C13.1 22 14 21.1 14 20H10C10 21.1 10.9 22 12 22ZM18 16V11C18 7.93 16.37 5.36 13.5 4.68V4C13.5 3.17 12.83 2.5 12 2.5C11.17 2.5 10.5 3.17 10.5 4V4.68C7.64 5.36 6 7.92 6 11V16L4 18V19H20V18L18 16ZM16 17H8V11C8 8.52 9.51 6.5 12 6.5C14.49 6.5 16 8.52 16 11V17Z" fill="#1B4D43"/>
                </svg>
                <?php if ($unread_count > 0): ?>
                    <span class="notification-badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
            <!-- Notifications Dropdown -->
            <div class="notifications-dropdown" id="notificationsDropdown">
                <div class="notifications-header">
                    <h3>Notifications</h3>
                    <button type="button" class="mark-all-read" id="markAllRead">Mark all read</button>
                </div>
                <div class="notifications-list" id="notificationsList">
                    <div class="empty-notifications">Loading notifications...</div>
                </div>
            </div>
        </div>
        <!-- User Profile Link -->
        <a href="/Skill-Sphere/php/pages/user_profile.php" class="user-link">
            <span class="user-icon-name">
                <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="11" cy="11" r="11" fill="#1B4D43"/>
                    <ellipse cx="11" cy="15.5" rx="6" ry="4.5" fill="#fff" fill-opacity="0.25"/>
                    <circle cx="11" cy="9" r="4" fill="#fff" fill-opacity="0.7"/>
                </svg>
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['email']); ?></span>
            </span>
        </a>
        <form method="POST" action="" class="logout-form">
            <button type="submit" name="logout" class="logout-btn">
                Logout
            </button>
        </form>
    </div>
    <?php else: ?>
        <div class="join-button">
            <a href="/Skill-Sphere/php/entry/sign_up.php" class="btn">JOIN US!</a>
        </div>
    <?php endif; ?>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const notificationToggle = document.getElementById('notificationToggle');
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    const notificationsList = document.getElementById('notificationsList');
    const markAllRead = document.getElementById('markAllRead');

    // Toggle dropdown
    notificationToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        notificationsDropdown.classList.toggle('show');
        if (notificationsDropdown.classList.contains('show')) {
            loadNotifications();
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!notificationsDropdown.contains(e.target) && !notificationToggle.contains(e.target)) {
            notificationsDropdown.classList.remove('show');
        }
    });

    // Mark all as read
    markAllRead.addEventListener('click', function() {
        fetch('/Skill-Sphere/php/components/notifications_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=mark_all_read'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadNotifications();
                const badge = document.querySelector('.notification-badge');
                if (badge) badge.style.display = 'none';
            }
        });
    });

    // Load notifications
    function loadNotifications() {
        fetch('/Skill-Sphere/php/components/notifications_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_notifications'
        })
        .then(response => response.json())
        .then(notifications => {
            if (notifications.length === 0) {
                notificationsList.innerHTML = '<div class="empty-notifications">No notifications</div>';
                return;
            }

            notificationsList.innerHTML = notifications.map(notification => `
                <div class="notification-item ${notification.is_read ? '' : 'unread'}" data-id="${notification.id}">
                    <div class="notification-icon ${notification.type}">
                        ${getNotificationIcon(notification.type)}
                    </div>
                    <div class="notification-content">
                        <div class="notification-message">${notification.message}</div>
                        <div class="notification-time">${notification.time_ago}</div>
                    </div>
                </div>
            `).join('');

            // Add click handlers for individual notifications
            document.querySelectorAll('.notification-item').forEach(item => {
                item.addEventListener('click', function() {
                    const id = this.dataset.id;
                    if (!this.classList.contains('unread')) return;

                    fetch('/Skill-Sphere/php/components/notifications_handler.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=mark_read&notification_id=${id}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.classList.remove('unread');
                            loadNotifications();
                        }
                    });
                });
            });
        });
    }

    function getNotificationIcon(type) {
        switch (type) {
            case 'success':
                return '<svg width="16" height="16" viewBox="0 0 24 24"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>';
            case 'warning':
                return '<svg width="16" height="16" viewBox="0 0 24 24"><path fill="currentColor" d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>';
            case 'error':
                return '<svg width="16" height="16" viewBox="0 0 24 24"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>';
            default:
                return '<svg width="16" height="16" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>';
        }
    }
});
</script>