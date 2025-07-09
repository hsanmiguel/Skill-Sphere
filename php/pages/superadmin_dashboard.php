<?php
session_start();
// Security check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: ../pages/home_page.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "skillsphere");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle user deletion
if (isset($_GET['delete_user'])) {
    $id = intval($_GET['delete_user']);
    // Prevent superadmin from deleting themselves
    if ($id == $_SESSION['user_id']) {
        echo "<script>alert('You cannot delete your own superadmin account.');window.location='superadmin_dashboard.php';</script>";
        exit();
    }
    // Check superadmin count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role='superadmin'");
    $stmt->execute();
    $superadminCount = $stmt->get_result()->fetch_row()[0];
    $stmt->close();

    // Check user role
    $stmt = $conn->prepare("SELECT role FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user && $user['role'] === 'superadmin' && $superadminCount <= 1) {
        echo "<script>alert('Cannot delete the last superadmin.');window.location='superadmin_dashboard.php';</script>";
        exit();
    }

    // Delete user
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: superadmin_dashboard.php");
    exit();
}

// Handle role and email updates
if (isset($_POST['update_role']) || isset($_POST['update_user'])) {
    $id = intval($_POST['user_id']);
    $role = isset($_POST['role']) ? $_POST['role'] : null;
    $new_email = isset($_POST['email']) ? trim($_POST['email']) : null;

    // Prevent self-demotion
    if ($id == $_SESSION['user_id'] && $role && $role !== 'superadmin') {
        echo "<script>alert('You cannot demote your own superadmin account.');window.location='superadmin_dashboard.php';</script>";
        exit();
    }

    // Check superadmin count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role='superadmin'");
    $stmt->execute();
    $superadminCount = $stmt->get_result()->fetch_row()[0];
    $stmt->close();

    // Check user role
    $stmt = $conn->prepare("SELECT role FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user && $user['role'] === 'superadmin' && $role && $role !== 'superadmin' && $superadminCount <= 1) {
        echo "<script>alert('Cannot demote the last superadmin.');window.location='superadmin_dashboard.php';</script>";
        exit();
    }

    // Update email if changed and not empty
    if ($new_email && filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        // Check for duplicate email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=? AND id!=?");
        $stmt->bind_param("si", $new_email, $id);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($exists) {
            echo "<script>alert('Email address already in use by another user.');window.location='superadmin_dashboard.php';</script>";
            exit();
        }
        // Update in users table
        $stmt = $conn->prepare("UPDATE users SET email=? WHERE id=?");
        $stmt->bind_param("si", $new_email, $id);
        $stmt->execute();
        $stmt->close();
        // Update in user_profiles table if exists
        $stmt = $conn->prepare("UPDATE user_profiles SET email=? WHERE email=(SELECT email FROM users WHERE id=?)");
        $stmt->bind_param("si", $new_email, $id);
        $stmt->execute();
        $stmt->close();
    }

    // Update role if set
    if ($role) {
        $stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
        $stmt->bind_param("si", $role, $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: superadmin_dashboard.php");
    exit();
}

// Get statistics
$stats = [
    'users' => $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0],
    'providers' => $conn->query("SELECT COUNT(*) FROM user_profiles")->fetch_row()[0],
    'requests' => $conn->query("SHOW TABLES LIKE 'requests'")->num_rows ? 
                 $conn->query("SELECT COUNT(*) FROM requests")->fetch_row()[0] : 0
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Skill Sphere Super Admin Dashboard">
    <title>Super Admin Dashboard | Skill Sphere</title>
    <link rel="stylesheet" href="../designs/header1.css">
    <link rel="stylesheet" href="../designs/footer.css">
    <link rel="stylesheet" href="../designs/superadmin_dashboard.css">
</head>
<body>
    <?php include '../components/header.php'; ?>
    
    <main>
        <div class="dashboard-header">
            <h1>Super Admin Dashboard</h1>
            <p class="dashboard-intro">Welcome to the system management interface</p>
        </div>

        <!-- Statistics Overview -->
        <div class="stats-box">
            <div class="stat">
                <h2><?php echo number_format($stats['users']); ?></h2>
                <p>Total Users</p>
            </div>
            <div class="stat">
                <h2><?php echo number_format($stats['providers']); ?></h2>
                <p>Service Providers</p>
            </div>
            <div class="stat">
                <h2><?php echo number_format($stats['requests']); ?></h2>
                <p>Service Requests</p>
            </div>
        </div>

        <!-- User Management Section -->
        <section class="data-section">
            <h2 class="section-title">User Management</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $users = $conn->query("SELECT id, email, role FROM users ORDER BY id");
                        while ($user = $users->fetch_assoc()):
                        ?>
                        <tr>
                            <td data-label="ID"><?php echo htmlspecialchars($user['id']); ?></td>
                            <td data-label="Email"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td data-label="Role"><?php echo htmlspecialchars($user['role']); ?></td>
                            <td data-label="Actions">
                                <button type="button" class="action-btn edit-btn open-edit-user-modal"
                                        data-user-id="<?php echo $user['id']; ?>"
                                        data-user-email="<?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?>"
                                        data-user-role="<?php echo $user['role']; ?>">
                                    Edit
                                </button>
                                <a href="?delete_user=<?php echo $user['id']; ?>" 
                                   class="action-btn delete-btn"
                                   onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Styled popup overlay for editing user (email/role) -->
            <div id="editUserModal" class="edit-profile-modal-overlay">
                <div class="edit-profile-modal-content" style="max-width: 420px;">
                    <button class="edit-profile-modal-close" id="closeEditUserModal">&times;</button>
                    <form method="post" id="editUserForm" class="edit-modal-form" style="box-shadow:none;max-width:unset;">
                        <h3 style="margin-bottom:10px;">Edit User</h3>
                        <div class="form-group">
                            <label for="modalEditUserEmail" style="font-weight:600;">Email:</label>
                            <input type="email" name="email" id="modalEditUserEmail" required>
                        </div>
                        <div class="form-group">
                            <label for="modalEditUserRole">Role:</label>
                            <select name="role" id="modalEditUserRole">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                                <option value="superadmin">Superadmin</option>
                            </select>
                        </div>
                        <input type="hidden" name="user_id" id="modalEditUserId">
                        <div class="btn-row">
                            <button type="submit" name="update_user" class="edit-profile-btn">Save</button>
                            <button type="button" class="edit-profile-btn cancel" id="cancelEditUserModal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <style>
            .edit-profile-modal-overlay {
                display: none;
                position: fixed;
                z-index: 99999;
                left: 0; top: 0; right: 0; bottom: 0;
                background: rgba(30,40,90,0.18);
                align-items: center;
                justify-content: center;
            }
            .edit-profile-modal-overlay.active {
                display: flex !important;
            }
            .edit-profile-modal-content {
                background: #fff;
                border-radius: 18px;
                box-shadow: 0 4px 32px rgba(30,40,90,0.18);
                padding: 32px 32px 24px 32px;
                max-width: 420px;
                width: 95vw;
                position: relative;
                display: flex;
                flex-direction: column;
                align-items: stretch;
                margin: auto;
                animation: fadeInPop 0.25s;
            }
            .edit-profile-modal-close {
                position: absolute;
                top: 12px;
                right: 18px;
                background: none;
                border: none;
                font-size: 2em;
                color: #888;
                cursor: pointer;
                z-index: 2;
            }
            .edit-modal-form {
                width: 100%;
                margin: 0 auto;
                display: flex;
                flex-direction: column;
                gap: 18px;
            }
            .edit-modal-form label {
                font-weight: 500;
                color: #1B4D43;
                margin-bottom: 2px;
                display: block;
            }
            .edit-modal-form select, .edit-modal-form input[type="email"] {
                width: 100%;
                padding: 7px 10px;
                border-radius: 8px;
                border: 1px solid #bbb;
                font-size: 1em;
                margin-top: 4px;
                margin-bottom: 2px;
                background: #f8f9fa;
            }
            .btn-row {
                display: flex;
                gap: 16px;
                margin-top: 10px;
            }
            .edit-profile-btn {
                border: none;
                border-radius: 999px;
                padding: 8px 28px;
                font-size: 1em;
                font-weight: 600;
                cursor: pointer;
                transition: background 0.2s, color 0.2s;
                background: linear-gradient(135deg, #1B4D43 0%, #4CAF50 100%);
                color: #fff;
                box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            }
            .edit-profile-btn.cancel {
                background: #eee;
                color: #222;
                box-shadow: none;
            }
            .edit-profile-btn:hover:not(.cancel) {
                background: linear-gradient(135deg, #388e3c 0%, #1B4D43 100%);
            }
            .edit-profile-btn.cancel:hover {
                background: #e0e0e0;
            }
            @keyframes fadeInPop {
                from { opacity: 0; transform: translateY(-20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            </style>
            <script>
            // Modal logic for Edit User (email/role)
            document.addEventListener('DOMContentLoaded', function() {
                const editUserModal = document.getElementById('editUserModal');
                const closeEditUserModal = document.getElementById('closeEditUserModal');
                const cancelEditUserModal = document.getElementById('cancelEditUserModal');
                // Open modal on button click
                document.querySelectorAll('.open-edit-user-modal').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        document.getElementById('modalEditUserId').value = btn.getAttribute('data-user-id');
                        document.getElementById('modalEditUserEmail').value = btn.getAttribute('data-user-email');
                        document.getElementById('modalEditUserRole').value = btn.getAttribute('data-user-role');
                        editUserModal.classList.add('active');
                    });
                });
                // Close modal on X or Cancel
                closeEditUserModal.onclick = function() {
                    editUserModal.classList.remove('active');
                };
                cancelEditUserModal.onclick = function() {
                    editUserModal.classList.remove('active');
                };
                // Close modal when clicking outside the content
                editUserModal.onclick = function(event) {
                    if (event.target === editUserModal) {
                        editUserModal.classList.remove('active');
                    }
                };
            });
            </script>
        </section>

        <!-- Service Providers Section -->
        <section class="data-section">
            <h2 class="section-title">Service Provider Profiles</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Phone</th>
                            <th>Skills</th>
                            <th>Services</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $profiles = $conn->query("SELECT * FROM user_profiles ORDER BY id");
                        while ($profile = $profiles->fetch_assoc()):
                        ?>
                        <tr>
                            <td data-label="ID"><?php echo htmlspecialchars($profile['id']); ?></td>
                            <td data-label="Name"><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></td>
                            <td data-label="Email"><?php echo htmlspecialchars($profile['email']); ?></td>
                            <td data-label="Address"><?php echo htmlspecialchars($profile['address']); ?></td>
                            <td data-label="Phone"><?php echo htmlspecialchars($profile['phone_number']); ?></td>
                            <td data-label="Skills"><?php echo htmlspecialchars($profile['skills']); ?></td>
                            <td data-label="Services"><?php echo htmlspecialchars($profile['services']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Service Requests Section -->
        <section class="data-section">
            <h2 class="section-title">Service Requests</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Sender</th>
                            <th>Receiver</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Message</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($conn->query("SHOW TABLES LIKE 'requests'")->num_rows) {
                            $requests = $conn->query("SELECT * FROM requests ORDER BY created_at DESC LIMIT 100");
                            while ($request = $requests->fetch_assoc()):
                            ?>
                            <tr>
                                <td data-label="ID"><?php echo htmlspecialchars($request['id']); ?></td>
                                <td data-label="Sender"><?php echo htmlspecialchars($request['sender_email']); ?></td>
                                <td data-label="Receiver"><?php echo htmlspecialchars($request['receiver_email']); ?></td>
                                <td data-label="Service"><?php echo htmlspecialchars($request['service']); ?></td>
                                <td data-label="Status"><?php echo htmlspecialchars($request['status']); ?></td>
                                <td data-label="Message"><?php echo htmlspecialchars($request['message']); ?></td>
                                <td data-label="Created"><?php echo htmlspecialchars($request['created_at']); ?></td>
                            </tr>
                            <?php 
                            endwhile;
                        } else {
                            echo '<tr><td colspan="7">No requests found</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <?php include '../components/footer.php'; ?>
    <?php $conn->close(); ?>
</body>
</html>