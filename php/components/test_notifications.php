<?php
include_once 'notifications_handler.php';

if (isset($_SESSION['email'])) {
    // Add some test notifications
    addNotification($_SESSION['email'], "Welcome to Skill Sphere! Complete your profile to get started.", "info");
    addNotification($_SESSION['email'], "Your profile has been viewed 5 times this week!", "success");
    addNotification($_SESSION['email'], "New service request from john@example.com", "warning");
    addNotification($_SESSION['email'], "Please verify your email address", "error");
}
?> 