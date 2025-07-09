<?php
/**
 * Complete Notification System for Skill Sphere
 * Handles DB operations, AJAX endpoints, and notification management.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
class DatabaseConfig {
    const HOST = 'localhost';
    const DBNAME = 'skillsphere';
    const USERNAME = 'root';
    const PASSWORD = '';
}

class NotificationSystem {
    private $pdo;

    public function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host=" . DatabaseConfig::HOST . ";dbname=" . DatabaseConfig::DBNAME,
                DatabaseConfig::USERNAME,
                DatabaseConfig::PASSWORD
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->createNotificationsTable();
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            // Show the real error message for debugging
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    // Create notifications table if it doesn't exist
    private function createNotificationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_email VARCHAR(255) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            read_at TIMESTAMP NULL,
            deleted BOOLEAN DEFAULT FALSE,
            INDEX idx_user_email (user_email),
            INDEX idx_is_read (is_read),
            INDEX idx_created_at (created_at)
        )";
        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            error_log("Failed to create notifications table: " . $e->getMessage());
        }
    }

    // Create a new notification
    public function createNotification($userEmail, $title, $message, $type = 'info') {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (user_email, title, message, type) 
                VALUES (?, ?, ?, ?)
            ");
            $result = $stmt->execute([$userEmail, $title, $message, $type]);
            if ($result) {
                return $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Failed to create notification: " . $e->getMessage());
            return false;
        }
    }

    // Get notifications for a user
    public function getUserNotifications($userEmail, $limit = 20, $unreadOnly = false) {
        try {
            $sql = "SELECT id, user_email, title, message, type, is_read, created_at, read_at 
                    FROM notifications WHERE user_email = ? AND deleted = 0";
            $params = [$userEmail];
            if ($unreadOnly) {
                $sql .= " AND is_read = 0";
            }
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = $limit;
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get notifications: " . $e->getMessage());
            return [];
        }
    }

    // Get all notifications and requests for a user
    public function getAllNotificationsAndRequests($userEmail, $limit = 20) {
        try {
            // Get notifications
            $notifSql = "SELECT 
                'notification' as item_type,
                id,
                message,
                type,
                created_at,
                is_read
                FROM notifications 
                WHERE user_email = ? AND deleted = 0";

            // Get sent requests
            $sentSql = "SELECT 
                'request_sent' as item_type,
                id,
                CONCAT('Service request for ''', service, ''' to ', receiver_email) as message,
                CASE 
                    WHEN status = 'Accepted' THEN 'success'
                    WHEN status = 'Declined' THEN 'error'
                    ELSE 'info'
                END as type,
                created_at,
                CASE WHEN status != 'Pending' THEN 1 ELSE 0 END as is_read
                FROM requests 
                WHERE sender_email = ? AND deleted = 0";

            // Get received requests
            $receivedSql = "SELECT 
                'request_received' as item_type,
                id,
                CONCAT('New service request for ''', service, ''' from ', sender_email) as message,
                'warning' as type,
                created_at,
                CASE WHEN status != 'Pending' THEN 1 ELSE 0 END as is_read
                FROM requests 
                WHERE receiver_email = ? AND deleted = 0";

            // Combine all queries
            $sql = "($notifSql) UNION ALL ($sentSql) UNION ALL ($receivedSql) 
                   ORDER BY created_at DESC LIMIT ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userEmail, $userEmail, $userEmail, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get all notifications and requests: " . $e->getMessage());
            return [];
        }
    }

    // Get unread notifications count
    public function getUnreadCount($userEmail) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_email = ? AND is_read = 0 AND deleted = 0");
            $stmt->execute([$userEmail]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Failed to get unread count: " . $e->getMessage());
            return 0;
        }
    }

    // Get combined unread count for notifications and pending requests
    public function getCombinedUnreadCount($userEmail) {
        try {
            $sql = "SELECT 
                (SELECT COUNT(*) FROM notifications WHERE user_email = ? AND is_read = 0 AND deleted = 0) +
                (SELECT COUNT(*) FROM requests WHERE receiver_email = ? AND status = 'Pending' AND deleted = 0) +
                (SELECT COUNT(*) FROM requests WHERE sender_email = ? AND status = 'Pending' AND deleted = 0) as total";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userEmail, $userEmail, $userEmail]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Failed to get combined unread count: " . $e->getMessage());
            return 0;
        }
    }

    // Mark notification as read
    public function markAsRead($notificationId, $userEmail) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = CURRENT_TIMESTAMP 
                WHERE id = ? AND user_email = ?
            ");
            return $stmt->execute([$notificationId, $userEmail]);
        } catch (PDOException $e) {
            error_log("Failed to mark notification as read: " . $e->getMessage());
            return false;
        }
    }

    // Mark all notifications as read for a user
    public function markAllAsRead($userEmail) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = CURRENT_TIMESTAMP 
                WHERE user_email = ? AND is_read = 0
            ");
            return $stmt->execute([$userEmail]);
        } catch (PDOException $e) {
            error_log("Failed to mark all notifications as read: " . $e->getMessage());
            return false;
        }
    }

    // Delete a notification
    public function deleteNotification($notificationId, $userEmail) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM notifications 
                WHERE id = ? AND user_email = ?
            ");
            return $stmt->execute([$notificationId, $userEmail]);
        } catch (PDOException $e) {
            error_log("Failed to delete notification: " . $e->getMessage());
            return false;
        }
    }

    // Delete old notifications (older than specified days)
    public function cleanupOldNotifications($days = 30) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM notifications 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            return $stmt->execute([$days]);
        } catch (PDOException $e) {
            error_log("Failed to cleanup old notifications: " . $e->getMessage());
            return false;
        }
    }

    // Send notification to multiple users
    public function broadcastNotification($userEmails, $title, $message, $type = 'info') {
        $successCount = 0;
        foreach ($userEmails as $email) {
            if ($this->createNotification($email, $title, $message, $type)) {
                $successCount++;
            }
        }
        return $successCount;
    }

    // Send notification to all users (admin function)
    public function notifyAllUsers($title, $message, $type = 'info') {
        try {
            $stmt = $this->pdo->prepare("SELECT DISTINCT email FROM users WHERE email IS NOT NULL");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $this->broadcastNotification($users, $title, $message, $type);
        } catch (PDOException $e) {
            error_log("Failed to notify all users: " . $e->getMessage());
            return 0;
        }
    }

    // Method to mark notification or request as read
    public function markItemAsRead($id, $type, $userEmail) {
        try {
            if ($type === 'notification') {
                $sql = "UPDATE notifications SET is_read = 1, read_at = CURRENT_TIMESTAMP 
                        WHERE id = ? AND user_email = ?";
            } else if ($type === 'request_received' || $type === 'request_sent') {
                $sql = "UPDATE requests SET status = CASE WHEN status = 'Pending' THEN 'Viewed' ELSE status END 
                        WHERE id = ? AND (sender_email = ? OR receiver_email = ?)";
            }
            
            $stmt = $this->pdo->prepare($sql);
            if ($type === 'notification') {
                return $stmt->execute([$id, $userEmail]);
            } else {
                return $stmt->execute([$id, $userEmail, $userEmail]);
            }
        } catch (PDOException $e) {
            error_log("Failed to mark item as read: " . $e->getMessage());
            return false;
        }
    }
}

// Initialize notification system
$notificationSystem = new NotificationSystem();

// AJAX handling moved to notification_ajax.php

// Helper functions for easy use in other files
function createNotification($userEmail, $title, $message, $type = 'info') {
    global $notificationSystem;
    return $notificationSystem->createNotification($userEmail, $title, $message, $type);
}
function getNotificationCount($userEmail) {
    global $notificationSystem;
    return $notificationSystem->getUnreadCount($userEmail);
}

// Predefined notification templates
class NotificationTemplates {
    public static function welcome($userEmail, $userName) {
        return createNotification(
            $userEmail, 
            "Welcome to Skill Sphere!", 
            "Hi $userName! Welcome to Skill Sphere. We're excited to have you on board!", 
            'success'
        );
    }
    public static function profileUpdate($userEmail) {
        return createNotification(
            $userEmail, 
            "Profile Updated", 
            "Your profile has been successfully updated.", 
            'success'
        );
    }
    public static function serviceBooked($userEmail, $serviceName) {
        return createNotification(
            $userEmail, 
            "Service Booked", 
            "You have successfully booked: $serviceName", 
            'success'
        );
    }
    public static function serviceReminder($userEmail, $serviceName, $date) {
        return createNotification(
            $userEmail, 
            "Service Reminder", 
            "Reminder: You have $serviceName scheduled for $date", 
            'info'
        );
    }
    public static function systemMaintenance($userEmail) {
        return createNotification(
            $userEmail, 
            "System Maintenance", 
            "Our system will be under maintenance tonight from 2 AM to 4 AM. Please save your work.", 
            'warning'
        );
    }
    public static function accountSecurity($userEmail) {
        return createNotification(
            $userEmail, 
            "Security Alert", 
            "We detected a login from a new device. If this wasn't you, please secure your account.", 
            'error'
        );
    }
}

// Example usage and admin functions
if (isset($_GET['demo']) && $_GET['demo'] === 'true' && isset($_SESSION['email'])) {
    $userEmail = $_SESSION['email'];
    NotificationTemplates::welcome($userEmail, "User");
    createNotification($userEmail, "Test Notification", "This is a test notification", 'info');
    createNotification($userEmail, "Success Message", "Something completed successfully!", 'success');
    createNotification($userEmail, "Warning Alert", "Please be aware of this warning", 'warning');
    echo "<script>alert('Demo notifications created!'); window.location.href = window.location.pathname;</script>";
}
?>