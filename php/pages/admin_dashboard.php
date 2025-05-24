<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: /Skill-Sphere/php/pages/home_page.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "registered_accounts");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Handle service CRUD
if (isset($_POST['add_service'])) {
    $name = trim($_POST['service_name']);
    $emoji = trim($_POST['service_emoji']);
    if ($name !== '') {
        $stmt = $conn->prepare("INSERT INTO services (name, emoji) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $emoji);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_dashboard.php"); exit();
}
if (isset($_POST['delete_service'])) {
    $id = intval($_POST['service_id']);
    $stmt = $conn->prepare("DELETE FROM services WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php"); exit();
}
if (isset($_POST['edit_service'])) {
    $id = intval($_POST['service_id']);
    $name = trim($_POST['service_name']);
    $emoji = trim($_POST['service_emoji']);
    $stmt = $conn->prepare("UPDATE services SET name=?, emoji=? WHERE id=?");
    $stmt->bind_param("ssi", $name, $emoji, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php"); exit();
}
// Handle user delete
if (isset($_POST['delete_user'])) {
    $id = intval($_POST['user_id']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php"); exit();
}
// Handle user role update
if (isset($_POST['update_role'])) {
    $id = intval($_POST['user_id']);
    $role = $_POST['role'];
    if ($id !== $_SESSION['user_id'] && $role !== 'superadmin') {
        $stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
        $stmt->bind_param("si", $role, $id);
        $stmt->execute();
        $stmt->close();
        // Log action
        $conn->query("INSERT INTO admin_logs (admin_id, action, details) VALUES (".$_SESSION['user_id'].", 'update_role', 'User ID $id role changed to $role')");
    }
    header("Location: admin_dashboard.php"); exit();
}
// Fetch all services
$services = [];
$res = $conn->query("SELECT * FROM services ORDER BY name");
while ($row = $res->fetch_assoc()) $services[] = $row;
// Fetch all users
$user_search = isset($_GET['user_search']) ? trim($_GET['user_search']) : '';
$user_query = "SELECT * FROM users";
if ($user_search !== '') {
    $user_query .= " WHERE email LIKE '%".$conn->real_escape_string($user_search)."%'";
}
$user_query .= " ORDER BY id";
$res = $conn->query($user_query);
$users = [];
while ($row = $res->fetch_assoc()) $users[] = $row;
// Fetch admin logs
$logs = [];
if ($conn->query("SHOW TABLES LIKE 'admin_logs'")->num_rows) {
    $logres = $conn->query("SELECT * FROM admin_logs ORDER BY created_at DESC LIMIT 10");
    while ($row = $logres->fetch_assoc()) $logs[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="/Skill-Sphere/php/designs/header1.css">
    <link rel="stylesheet" href="/Skill-Sphere/php/designs/footer.css">
    <style>
        body { background: #f6f7f9; }
        .admin-section { max-width: 1100px; margin: 40px auto; background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); padding: 36px 32px; }
        h2 { color: #1B4D43; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 32px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        tr:nth-child(even) { background: #fafafa; }
        .action-btn { padding: 4px 14px; border-radius: 12px; font-size: 1em; font-weight: 600; border: none; cursor: pointer; margin: 0 2px; }
        .edit-btn { background: #4CAF50; color: #fff; }
        .delete-btn { background: #e53935; color: #fff; }
        .add-form { display: flex; gap: 10px; margin-bottom: 18px; }
        .add-form input { padding: 6px 10px; border-radius: 6px; border: 1px solid #bbb; }
    </style>
</head>
<body>
<?php include '../components/header.php'; ?>
<div class="admin-section">
    <h2>Admin Dashboard</h2>
    <div style="display:flex;gap:32px;margin-bottom:24px;">
        <div><b>Total Users:</b> <?php echo count($users); ?></div>
        <div><b>Total Services:</b> <?php echo count($services); ?></div>
        <form method="GET" style="margin-left:auto;display:flex;gap:8px;align-items:center;">
            <input type="text" name="user_search" placeholder="Search user email" value="<?php echo htmlspecialchars($user_search); ?>">
            <button type="submit">Search</button>
        </form>
        <form method="POST" action="" style="margin-left:16px;"><button type="submit" name="logout" class="action-btn delete-btn">Logout</button></form>
    </div>
    <h2>Service Management</h2>
    <form method="POST" class="add-form">
        <input type="text" name="service_name" placeholder="Service Name" required>
        <input type="text" name="service_emoji" placeholder="Emoji" maxlength="2">
        <button type="submit" name="add_service" class="action-btn edit-btn">Add Service</button>
    </form>
    <table>
        <tr><th>ID</th><th>Name</th><th>Emoji</th><th>Actions</th></tr>
        <?php foreach ($services as $service): ?>
        <tr>
            <form method="POST">
                <td><?php echo $service['id']; ?><input type="hidden" name="service_id" value="<?php echo $service['id']; ?>"></td>
                <td><input type="text" name="service_name" value="<?php echo htmlspecialchars($service['name']); ?>" required></td>
                <td><input type="text" name="service_emoji" value="<?php echo htmlspecialchars($service['emoji']); ?>" maxlength="2"></td>
                <td>
                    <button type="submit" name="edit_service" class="action-btn edit-btn">Save</button>
                    <button type="submit" name="delete_service" class="action-btn delete-btn" onclick="return confirm('Delete this service?')">Delete</button>
                </td>
            </form>
        </tr>
        <?php endforeach; ?>
    </table>
    <h2>User Management</h2>
    <table>
        <tr><th>ID</th><th>Email</th><th>Role</th><th>Actions</th></tr>
        <?php foreach ($users as $user): ?>
        <tr>
            <form method="POST">
                <td><?php echo $user['id']; ?><input type="hidden" name="user_id" value="<?php echo $user['id']; ?>"></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td>
                    <?php if ($user['role'] !== 'superadmin' && $user['id'] !== $_SESSION['user_id']): ?>
                        <select name="role">
                            <option value="user" <?php if($user['role']=='user') echo 'selected'; ?>>User</option>
                            <option value="admin" <?php if($user['role']=='admin') echo 'selected'; ?>>Admin</option>
                        </select>
                        <button type="submit" name="update_role" class="action-btn edit-btn">Update</button>
                    <?php else: ?>
                        <?php echo htmlspecialchars($user['role']); ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($user['role'] !== 'superadmin'): ?>
                        <button type="submit" name="delete_user" class="action-btn delete-btn" onclick="return confirm('Delete this user?')">Delete</button>
                        <a href="reset_password.php?user_id=<?php echo $user['id']; ?>" class="action-btn edit-btn">Reset Password</a>
                    <?php endif; ?>
                </td>
            </form>
        </tr>
        <?php endforeach; ?>
    </table>
    <h2>Recent Admin Actions</h2>
    <table>
        <tr><th>Time</th><th>Admin ID</th><th>Action</th><th>Details</th></tr>
        <?php foreach ($logs as $log): ?>
        <tr>
            <td><?php echo htmlspecialchars($log['created_at']); ?></td>
            <td><?php echo htmlspecialchars($log['admin_id']); ?></td>
            <td><?php echo htmlspecialchars($log['action']); ?></td>
            <td><?php echo htmlspecialchars($log['details']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php include '../components/footer.php'; ?>
</body>
</html>
<?php $conn->close(); ?> 