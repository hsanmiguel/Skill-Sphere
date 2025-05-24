<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Get current user data
$conn = new mysqli('localhost', 'root', '', 'registered_accounts');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<div id="editProfileModalOverlay" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <button type="button" class="close-btn" onclick="document.getElementById('editProfileModalOverlay').style.display='none'">&times;</button>
        <h2>Edit Profile</h2>
        <form action="/Skill-Sphere/php/handlers/update_profile.php" method="POST" class="edit-profile-form">
            <div class="form-group">
                <label for="first-name">First Name</label>
                <input type="text" id="first-name" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="mi">Middle Initial</label>
                <input type="text" id="mi" name="mi" value="<?php echo htmlspecialchars($user['mi'] ?? ''); ?>" maxlength="1">
            </div>

            <div class="form-group">
                <label for="last-name">Last Name</label>
                <input type="text" id="last-name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="social-media">Social Media (Optional)</label>
                <input type="text" id="social-media" name="social_media" value="<?php echo htmlspecialchars($user['social_media'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="experience">Years of Experience</label>
                <input type="number" id="experience" name="experience" value="<?php echo htmlspecialchars($user['experience'] ?? '0'); ?>" min="0" max="50" required>
            </div>

            <div class="button-group">
                <button type="submit" class="save-btn">Save Changes</button>
                <button type="button" class="cancel-btn" onclick="document.getElementById('editProfileModalOverlay').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: #fff;
    border-radius: 16px;
    padding: 32px;
    width: 90%;
    max-width: 500px;
    position: relative;
    animation: modalIn 0.3s ease-out;
}

.close-btn {
    position: absolute;
    top: 16px;
    right: 16px;
    background: none;
    border: none;
    font-size: 24px;
    color: #666;
    cursor: pointer;
    padding: 4px;
    line-height: 1;
}

.close-btn:hover {
    color: #d32f2f;
}

.modal-content h2 {
    color: #1B4D43;
    font-size: 1.5rem;
    margin-bottom: 24px;
    text-align: center;
}

.edit-profile-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.form-group label {
    color: #1B4D43;
    font-weight: 500;
    font-size: 0.9rem;
}

.form-group input {
    padding: 10px 12px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.form-group input:focus {
    border-color: #1B4D43;
    outline: none;
    box-shadow: 0 0 0 2px rgba(27,77,67,0.1);
}

.button-group {
    display: flex;
    gap: 12px;
    margin-top: 8px;
}

.save-btn, .cancel-btn {
    flex: 1;
    padding: 12px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.save-btn {
    background: #1B4D43;
    color: white;
}

.save-btn:hover {
    background: #2a6a5d;
    transform: translateY(-1px);
}

.cancel-btn {
    background: #f5f5f5;
    color: #666;
}

.cancel-btn:hover {
    background: #e0e0e0;
}

@keyframes modalIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style> 