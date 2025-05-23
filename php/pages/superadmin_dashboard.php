<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: /Skill-Sphere/php/pages/home_page.php");
    exit();
}

// Direct DB connection (no db_connect.php)
$host = 'localhost';
$dbname = 'registered_accounts';
$username = 'root';
$password = '';
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Delete user
if (isset($_GET['delete_user'])) {
    $id = intval($_GET['delete_user']);
    // Prevent superadmin from deleting themselves
    if ($id == $_SESSION['user_id']) {
        echo "<script>alert('You cannot delete your own superadmin account while logged in.');window.location='/Skill-Sphere/php/pages/superadmin_dashboard.php';</script>";
        exit();
    }
    // Prevent deleting the last superadmin
    $result = $conn->query("SELECT COUNT(*) FROM users WHERE role='superadmin'");
    $superadminCount = $result ? $result->fetch_row()[0] : 0;
    $user = $conn->query("SELECT role FROM users WHERE id=$id")->fetch_assoc();
    if ($user && $user['role'] === 'superadmin' && $superadminCount <= 1) {
        echo "<script>alert('You cannot delete the last superadmin.');window.location='/Skill-Sphere/php/pages/superadmin_dashboard.php';</script>";
        exit();
    }
    $conn->query("DELETE FROM users WHERE id=$id");
    header("Location: /Skill-Sphere/php/pages/superadmin_dashboard.php");
    exit();
}

// Update user role
if (isset($_POST['update_role'])) {
    $id = intval($_POST['user_id']);
    $role = $_POST['role'];
    // Prevent superadmin from demoting themselves
    if ($id == $_SESSION['user_id'] && $role !== 'superadmin') {
        echo "<script>alert('You cannot demote your own superadmin account while logged in.');window.location='/Skill-Sphere/php/pages/superadmin_dashboard.php';</script>";
        exit();
    }
    // Prevent demoting the last superadmin
    $result = $conn->query("SELECT COUNT(*) FROM users WHERE role='superadmin'");
    $superadminCount = $result ? $result->fetch_row()[0] : 0;
    $user = $conn->query("SELECT role FROM users WHERE id=$id")->fetch_assoc();
    if ($user && $user['role'] === 'superadmin' && $role !== 'superadmin' && $superadminCount <= 1) {
        echo "<script>alert('You cannot demote the last superadmin.');window.location='/Skill-Sphere/php/pages/superadmin_dashboard.php';</script>";
        exit();
    }
    $stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
    $stmt->bind_param("si", $role, $id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['role_update_success'] = 'User role updated successfully!';
    header("Location: /Skill-Sphere/php/pages/superadmin_dashboard.php");
    exit();
}

// Close the database connection
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Dashboard</title>
    <link rel="stylesheet" href="/Skill-Sphere/php/designs/header1.css">
    <link rel="stylesheet" href="/Skill-Sphere/php/designs/superadmin_dashboard.css">
    <link rel="stylesheet" href="/Skill-Sphere/php/designs/footer.css">
    <style>
        table { border-collapse: collapse; width: 100%; margin-bottom: 40px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        tr:nth-child(even) { background: #fafafa; }
        .section-title { color: #1B4D43; margin-top: 40px; }
        .stats-box { display: flex; gap: 32px; margin-bottom: 32px; }
        .stat { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); padding: 24px 32px; text-align: center; flex: 1; }
        .stat h2 { margin: 0 0 8px 0; color: #1B4D43; font-size: 2.2rem; }
        .stat p { margin: 0; color: #555; font-size: 1.1rem; }
        .action-btn { display: inline-block; padding: 6px 18px; border-radius: 20px; font-size: 1rem; font-weight: 600; border: none; cursor: pointer; margin: 0 2px; transition: background 0.2s, color 0.2s; text-decoration: none; }
        .edit-btn { background: linear-gradient(135deg, #43a047 0%, #aee571 100%); color: #fff; }
        .edit-btn:hover { background: linear-gradient(135deg, #388e3c 0%, #8bc34a 100%); }
        .delete-btn { background: linear-gradient(135deg, #e53935 0%, #ffb733 100%); color: #fff; }
        .delete-btn:hover { background: linear-gradient(135deg, #b71c1c 0%, #ff9800 100%); }
        .update-btn { background: linear-gradient(135deg, #1B4D43 0%, #2a6a5d 100%); color: #fff; }
        .update-btn:hover { background: linear-gradient(135deg, #2a6a5d 0%, #1B4D43 100%); }
    </style>
</head>
<body>
    <?php include '../components/header.php'; ?>
    <main style="max-width:1100px;margin:40px auto;padding:40px;background:#fff;border-radius:18px;box-shadow:0 4px 24px rgba(0,0,0,0.07);">
        <h1 style="color:#1B4D43;">Welcome, Super Admin!</h1>
        <p style="font-size:1.2rem;">You have full access to all system management features.</p>

        <!-- Site Statistics -->
        <div class="stats-box">
            <?php
            $userCount = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
            $profileCount = $conn->query("SELECT COUNT(*) FROM user_profiles")->fetch_row()[0];
            $requestCount = $conn->query("SHOW TABLES LIKE 'requests'")->num_rows ? $conn->query("SELECT COUNT(*) FROM requests")->fetch_row()[0] : 0;
            ?>
            <div class="stat"><h2><?php echo $userCount; ?></h2><p>Total Users</p></div>
            <div class="stat"><h2><?php echo $profileCount; ?></h2><p>Service Provider Profiles</p></div>
            <div class="stat"><h2><?php echo $requestCount; ?></h2><p>Total Requests</p></div>
        </div>

        <!-- User Management -->
        <h2 class="section-title">All Users</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
            <?php
            if (isset($_SESSION['role_update_success'])) {
                echo '<div style="background:#e3f2fd;color:#1B4D43;padding:12px 24px;border-radius:8px;margin-bottom:18px;text-align:center;font-weight:600;">'.$_SESSION['role_update_success'].'</div>';
                unset($_SESSION['role_update_success']);
            }
            $result = $conn->query("SELECT id, email, role FROM users");
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>".htmlspecialchars($row['id'])."</td>";
                echo "<td>".htmlspecialchars($row['email'])."</td>";
                echo "<td>";
                if (isset($_GET['edit_user']) && intval($_GET['edit_user']) === intval($row['id'])) {
                    echo '<form method="post" style="display:inline;">';
                    echo '<input type="hidden" name="user_id" value="'.htmlspecialchars($row['id']).'">';
                    echo '<select name="role">';
                    foreach (["user","admin","superadmin"] as $roleOpt) {
                        $selected = ($row['role'] === $roleOpt) ? 'selected' : '';
                        echo '<option value="'.$roleOpt.'" '.$selected.'>'.ucfirst($roleOpt).'</option>';
                    }
                    echo '</select> ';
                    echo '<button type="submit" name="update_role" class="action-btn update-btn">Update</button>';
                    echo '<a href="/Skill-Sphere/php/pages/superadmin_dashboard.php" class="action-btn delete-btn" style="margin-left:8px;">Cancel</a>';
                    echo '</form>';
                } else {
                    echo htmlspecialchars($row['role']);
                }
                echo "</td>";
                echo '<td>';
                echo '<a href="/Skill-Sphere/php/pages/superadmin_dashboard.php?edit_user=' . $row['id'] . '" class="action-btn edit-btn">Edit</a> | ';
                echo '<a href="/Skill-Sphere/php/pages/superadmin_dashboard.php?delete_user=' . $row['id'] . '" class="action-btn delete-btn" onclick="return confirm(\'Delete this user?\')">Delete</a>';
                echo '</td>';
                echo "</tr>";
            }
            ?>
        </table>
        <?php
        if (isset($_GET['edit_user'])) {
            $id = intval($_GET['edit_user']);
            $result = $conn->query("SELECT id, email, role FROM users WHERE id=$id");
            $user = $result->fetch_assoc();
            ?>
            <h3>Edit User Role</h3>
            <form method="post" style="margin-bottom:32px;">
                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                <label>Email: <?php echo htmlspecialchars($user['email']); ?></label><br>
                <label>Role:
                    <select name="role">
                        <option value="user" <?php if($user['role']=='user') echo 'selected'; ?>>User</option>
                        <option value="admin" <?php if($user['role']=='admin') echo 'selected'; ?>>Admin</option>
                        <option value="superadmin" <?php if($user['role']=='superadmin') echo 'selected'; ?>>Superadmin</option>
                    </select>
                </label>
                <button type="submit" name="update_role" class="action-btn update-btn">Update Role</button>
            </form>
            <hr>
            <?php
        }
        ?>

        <!-- Service Provider Profiles -->
        <h2 class="section-title">All Service Provider Profiles</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Address</th>
                <th>Phone</th>
                <th>Skills</th>
                <th>Services</th>
            </tr>
            <?php
            $result = $conn->query("SELECT id, first_name, last_name, email, address, phone_number, skills, services FROM user_profiles");
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>".htmlspecialchars($row['id'])."</td>";
                echo "<td>".htmlspecialchars($row['first_name'].' '.$row['last_name'])."</td>";
                echo "<td>".htmlspecialchars($row['email'])."</td>";
                echo "<td>".htmlspecialchars($row['address'])."</td>";
                echo "<td>".htmlspecialchars($row['phone_number'])."</td>";
                echo "<td>".htmlspecialchars($row['skills'])."</td>";
                echo "<td>".htmlspecialchars($row['services'])."</td>";
                echo "</tr>";
            }
            ?>
        </table>

        <!-- Requests -->
        <h2 class="section-title">All Requests</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Sender</th>
                <th>Receiver</th>
                <th>Service</th>
                <th>Status</th>
                <th>Message</th>
                <th>Created At</th>
            </tr>
            <?php
            if ($conn->query("SHOW TABLES LIKE 'requests'")->num_rows) {
                $result = $conn->query("SELECT id, sender_email, receiver_email, service, status, message, created_at FROM requests ORDER BY created_at DESC LIMIT 100");
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>".htmlspecialchars($row['id'])."</td>";
                    echo "<td>".htmlspecialchars($row['sender_email'])."</td>";
                    echo "<td>".htmlspecialchars($row['receiver_email'])."</td>";
                    echo "<td>".htmlspecialchars($row['service'])."</td>";
                    echo "<td>".htmlspecialchars($row['status'])."</td>";
                    echo "<td>".htmlspecialchars($row['message'])."</td>";
                    echo "<td>".htmlspecialchars($row['created_at'])."</td>";
                    echo "</tr>";
                }
            } else {
                echo '<tr><td colspan="7">No requests table found.</td></tr>';
            }
            ?>
        </table>
    </main>
    <?php include '../components/footer.php'; ?>
</body>
</html> 