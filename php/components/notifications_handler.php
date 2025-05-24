<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Database connection function
function getPDOConnection() {
    static $conn = null;
    if ($conn === null) {
        $host = 'localhost';
        $dbname = 'registered_accounts';
        $username = 'root';
        $password = '';
        try {
            $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("Connection failed: " . $e->getMessage());
            return null;
        }
    }
    return $conn;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_email = $_SESSION['email'] ?? '';
    $conn = getPDOConnection();

    switch ($action) {
        case 'mark_read':
            $notification_id = $_POST['notification_id'] ?? 0;
            if ($notification_id) {
                $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_email = ?");
                $stmt->execute([$notification_id, $user_email]);
                echo json_encode(['success' => true]);
            }
            break;

        case 'mark_all_read':
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_email = ? AND is_read = 0");
            $stmt->execute([$user_email]);
            echo json_encode(['success' => true]);
            break;

        case 'get_notifications':
            $stmt = $conn->prepare("
                SELECT id, message, type, is_read, created_at 
                FROM notifications 
                WHERE user_email = ? AND deleted = 0 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute([$user_email]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format timestamps
            foreach ($notifications as &$notification) {
                $timestamp = strtotime($notification['created_at']);
                $now = time();
                $diff = $now - $timestamp;
                
                if ($diff < 60) {
                    $notification['time_ago'] = "Just now";
                } elseif ($diff < 3600) {
                    $mins = floor($diff / 60);
                    $notification['time_ago'] = $mins . "m ago";
                } elseif ($diff < 86400) {
                    $hours = floor($diff / 3600);
                    $notification['time_ago'] = $hours . "h ago";
                } else {
                    $days = floor($diff / 86400);
                    $notification['time_ago'] = $days . "d ago";
                }
            }
            
            echo json_encode($notifications);
            break;
    }
    exit;
}

// Get unread count
function getUnreadCount($email) {
    $conn = getPDOConnection();
    if (!$conn) return 0;
    
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_email = ? AND is_read = 0 AND deleted = 0");
        $stmt->execute([$email]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error getting unread count: " . $e->getMessage());
        return 0;
    }
}

// Add a new notification
function addNotification($user_email, $message, $type = 'info') {
    $conn = getPDOConnection();
    if (!$conn) {
        // Fallback to session storage if database connection fails
        if (!isset($_SESSION['notifications'])) {
            $_SESSION['notifications'] = [];
        }
        $_SESSION['notifications'][] = [
            'message' => $message,
            'type' => $type,
            'timestamp' => time()
        ];
        return;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO notifications (user_email, message, type, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_email, $message, $type]);
    } catch (PDOException $e) {
        error_log("Error adding notification: " . $e->getMessage());
        // Fallback to session storage
        if (!isset($_SESSION['notifications'])) {
            $_SESSION['notifications'] = [];
        }
        $_SESSION['notifications'][] = [
            'message' => $message,
            'type' => $type,
            'timestamp' => time()
        ];
    }
}

function getNotifications() {
    $notifications = $_SESSION['notifications'] ?? [];
    // Clear notifications after retrieving
    $_SESSION['notifications'] = [];
    return $notifications;
}

function displayNotification($message, $type) {
    $bgColors = [
        'success' => '#43AA8B',
        'error' => '#FF6B6B',
        'warning' => '#FFB347',
        'info' => '#2196F3'
    ];
    
    $bgColor = $bgColors[$type] ?? $bgColors['info'];
    
    echo "
    <div class='notification-toast' style='display:flex; background-color: {$bgColor};'>
        <div class='notification-message'>{$message}</div>
    </div>
    <style>
    .notification-toast {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        animation: slideIn 0.3s ease-out forwards;
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const toast = document.querySelector('.notification-toast');
            if (toast) {
                toast.style.animation = 'slideOut 0.3s ease-in forwards';
                setTimeout(() => toast.remove(), 300);
            }
        }, 3000);
    });
    </script>
    ";
}
?> 