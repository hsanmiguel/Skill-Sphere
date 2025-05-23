<?php
session_start();
if (!isset($_SESSION['user_id'])) exit('Not logged in.');
$conn = new mysqli("localhost", "root", "", "registered_accounts");
if ($conn->connect_error) exit('DB error');
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT * FROM user_profiles WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$stmt->close();
$profilePic = (!empty($profile['profile_picture']) && file_exists('uploads/' . $profile['profile_picture'])) ? 'uploads/' . $profile['profile_picture'] : 'assets/logo_ss.png';
$all_skills = [
    'Plumbing', 'Carpentry', 'Electrical Work', 'Painting', 'Tiling', 'Roofing', 'Masonry',
    'Welding', 'Auto Repair', 'Motorcycle Repair', 'Appliance Repair', 'Furniture Assembly',
    'Locksmithing', 'Glass Cutting', 'Floor Installation', 'Drywall Repair', 'HVAC Repair',
    'Gutter Cleaning', 'Pest Control', 'Septic Tank Cleaning', 'House Cleaning', 'Deep Cleaning',
    'Window Cleaning', 'Laundry and Ironing', 'Carpet Cleaning', 'Pressure Washing', 'Pool Cleaning',
    'Organizing (Decluttering)', 'Trash Removal', 'Upholstery Cleaning', 'Gardening', 'Landscaping',
    'Lawn Mowing', 'Tree Trimming', 'Leaf Blowing', 'Fence Installation', 'Pesticide Application',
    'Sprinkler Repair', 'Outdoor Painting', 'Snow Removal', 'Cooking', 'Baking', 'Catering',
    'Food Plating', 'Kitchen Cleaning', 'Barbecuing', 'Meal Prep', 'Juice/Smoothie Making',
    'Butchering', 'Inventory Management (Kitchen)', 'Sewing', 'Embroidery', 'Crochet', 'Knitting',
    'Jewelry Repair', 'Shoe Repair', 'Toy Repair', 'Candle Making', 'Pottery', 'DIY Woodwork',
    'Basic Computer Repair', 'Printer Setup', 'Wi-Fi Setup', 'Router Troubleshooting', 'Smart TV Setup',
    'CCTV Installation', 'Alarm System Setup', 'Cable Management', 'Gadget Troubleshooting',
    'Software Installation', 'Childcare', 'Elderly Care', 'Special Needs Assistance', 'Basic First Aid',
    'Medication Reminders', 'Feeding Assistance', 'Companion Care', 'Diaper Changing', 'Bathing Assistance',
    'Bedside Support', 'Grocery Shopping', 'Running Errands', 'Pet Walking', 'Pet Bathing',
    'Cooking for Elders', 'House Sitting', 'Plant Watering', 'Mail Sorting', 'Light Decoration (Holidays)',
    'Delivery Assistance', 'Sign Painting', 'Basic Graphic Design', 'Poster Making', 'Event Setup',
    'Balloon Arrangement', 'Face Painting', 'Sound System Setup', 'Stage Decoration', 'Costume Repair',
    'Recycling Management'
];

// Include the shared categories array
require_once 'service_categories.php';
// Initialize user_skills as an array
$user_skills = [];
if (!empty($profile['skills'])) {
    $user_skills = array_map('trim', explode(',', $profile['skills']));
}
$user_services = array_map('trim', explode(',', $profile['services']));
?>
<style>
.edit-modal-form {
    width: 100%;
    max-width: 480px;
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
.edit-modal-form input[type="text"],
.edit-modal-form select {
    width: 100%;
    padding: 7px 10px;
    border-radius: 8px;
    border: 1px solid #bbb;
    font-size: 1em;
    margin-top: 4px;
    margin-bottom: 2px;
    background: #f8f9fa;
}
.edit-modal-form select[multiple] {
    min-height: 80px;
    font-size: 0.98em;
}
.edit-modal-form .profile-pic-group {
    display: flex;
    align-items: center;
    gap: 18px;
    margin-bottom: 8px;
}
.edit-modal-form .profile-pic-group img {
    height: 80px;
    width: 80px;
    border-radius: 50%;
    object-fit: cover;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    border: 2px solid #1B4D43;
}
.edit-modal-form .btn-row {
    display: flex;
    gap: 16px;
    margin-top: 10px;
}
.edit-modal-form .edit-profile-btn {
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
.edit-modal-form .edit-profile-btn.cancel {
    background: #eee;
    color: #222;
    box-shadow: none;
}
.edit-modal-form .edit-profile-btn:hover:not(.cancel) {
    background: linear-gradient(135deg, #388e3c 0%, #1B4D43 100%);
}
.edit-modal-form .edit-profile-btn.cancel:hover {
    background: #e0e0e0;
}
.edit-modal-form input[type="date"] {
    width: 100%;
    padding: 7px 10px;
    border-radius: 8px;
    border: 1px solid #bbb;
    font-size: 1em;
    margin-top: 4px;
    margin-bottom: 2px;
    background: #f8f9fa;
    color: #1B4D43;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.edit-modal-form input[type="date"]:focus {
    border-color: #1B4D43;
    box-shadow: 0 0 0 2px #e0f2f1;
    outline: none;
}
</style>
<form method="post" enctype="multipart/form-data" action="user_profile.php" class="edit-modal-form">
    <div class="profile-pic-group">
        <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile Picture">
        <div>
            <label style="margin-bottom:4px;">Change Picture:
                <input type="file" name="profile_picture" accept="image/*" style="margin-top:4px;">
            </label>
        </div>
    </div>
    <label>First Name:
        <input type="text" name="first_name" value="<?php echo htmlspecialchars($profile['first_name']); ?>" required>
    </label>
    <label>Last Name:
        <input type="text" name="last_name" value="<?php echo htmlspecialchars($profile['last_name']); ?>" required>
    </label>
    <label>Birthdate:
        <input type="date" name="birthdate" value="<?php echo isset($profile['birthdate']) ? htmlspecialchars($profile['birthdate']) : ''; ?>" required>
    </label>
    <label>Address:
        <input type="text" name="address" value="<?php echo htmlspecialchars($profile['address']); ?>" required>
    </label>
    <label>Phone Number:
        <input type="text" name="phone_number" value="<?php echo htmlspecialchars($profile['phone_number']); ?>" required>
    </label>
    <label>Skills:
        <div style="display: flex; flex-wrap: wrap; gap: 10px; max-height: 160px; overflow-y: auto; border: 1px solid #bbb; border-radius: 8px; padding: 10px; background: #f8f9fa;">
            <?php
            $skill_emojis = [
                'Plumbing' => '🔩', 'Carpentry' => '🪚', 'Electrical Work' => '⚡', 'Painting' => '🎨', 'Tiling' => '🧱', 'Roofing' => '🏠', 'Masonry' => '🛠️',
                'Welding' => '🔥', 'Auto Repair' => '🚗', 'Motorcycle Repair' => '🏍️', 'Appliance Repair' => '🔌', 'Furniture Assembly' => '🪑',
                'Locksmithing' => '🔑', 'Glass Cutting' => '🔪', 'Floor Installation' => '🪵', 'Drywall Repair' => '🛠️', 'HVAC Repair' => '❄️',
                'Gutter Cleaning' => '🧹', 'Pest Control' => '🐜', 'Septic Tank Cleaning' => '🚽', 'House Cleaning' => '🏠', 'Deep Cleaning' => '🧼',
                'Window Cleaning' => '🪟', 'Laundry and Ironing' => '👕', 'Carpet Cleaning' => '🧽', 'Pressure Washing' => '💦', 'Pool Cleaning' => '🏊',
                'Organizing (Decluttering)' => '📦', 'Trash Removal' => '🗑️', 'Upholstery Cleaning' => '🛋️', 'Gardening' => '🌱', 'Landscaping' => '🌳',
                'Lawn Mowing' => '🌾', 'Tree Trimming' => '✂️', 'Leaf Blowing' => '🍂', 'Fence Installation' => '🛠️', 'Pesticide Application' => '🪲',
                'Sprinkler Repair' => '💧', 'Outdoor Painting' => '🎨', 'Snow Removal' => '❄️', 'Cooking' => '🍳', 'Baking' => '🧁', 'Catering' => '🍽️',
                'Food Plating' => '🍲', 'Kitchen Cleaning' => '🧽', 'Barbecuing' => '🍖', 'Meal Prep' => '🥗', 'Juice/Smoothie Making' => '🥤',
                'Butchering' => '🔪', 'Inventory Management (Kitchen)' => '📦', 'Sewing' => '🧵', 'Embroidery' => '🪡', 'Crochet' => '🧶', 'Knitting' => '🧶',
                'Jewelry Repair' => '💍', 'Shoe Repair' => '👞', 'Toy Repair' => '🧸', 'Candle Making' => '🕯️', 'Pottery' => '🏺', 'DIY Woodwork' => '🪵',
                'Basic Computer Repair' => '💻', 'Printer Setup' => '🖨️', 'Wi-Fi Setup' => '📶', 'Router Troubleshooting' => '📡', 'Smart TV Setup' => '📺',
                'CCTV Installation' => '📹', 'Alarm System Setup' => '🚨', 'Cable Management' => '🔌', 'Gadget Troubleshooting' => '🔧',
                'Software Installation' => '💾', 'Childcare' => '👶', 'Elderly Care' => '🧓', 'Special Needs Assistance' => '♿', 'Basic First Aid' => '⛑️',
                'Medication Reminders' => '💊', 'Feeding Assistance' => '🍽️', 'Companion Care' => '🤝', 'Diaper Changing' => '🧷', 'Bathing Assistance' => '🛁',
                'Bedside Support' => '🛏️', 'Grocery Shopping' => '🛒', 'Running Errands' => '🏃', 'Pet Walking' => '🐕', 'Pet Bathing' => '🛁',
                'Cooking for Elders' => '🍲', 'House Sitting' => '🏠', 'Plant Watering' => '💧', 'Mail Sorting' => '📬', 'Light Decoration (Holidays)' => '🎉',
                'Delivery Assistance' => '📦', 'Sign Painting' => '🖌️', 'Basic Graphic Design' => '🖥️', 'Poster Making' => '📰', 'Event Setup' => '🎪',
                'Balloon Arrangement' => '🎈', 'Face Painting' => '🎭', 'Sound System Setup' => '🔊', 'Stage Decoration' => '🎤', 'Costume Repair' => '👗',
                'Recycling Management' => '♻️'
            ];
            foreach ($all_skills as $skill):
                $emoji = isset($skill_emojis[$skill]) ? $skill_emojis[$skill] : '';
            ?>
                <label style="min-width: 180px; display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                    <input type="checkbox" name="skills[]" value="<?php echo htmlspecialchars($skill); ?>" <?php if (in_array($skill, $user_skills)) echo 'checked'; ?>>
                    <span><?php echo $emoji . ' ' . htmlspecialchars($skill); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </label>
    <label>Services:
        <div style="display: flex; flex-direction: column; gap: 10px; max-height: 160px; overflow-y: auto; border: 1px solid #bbb; border-radius: 8px; padding: 10px; background: #f8f9fa;">
            <?php foreach ($categories as $label => $services): ?>
                <strong><?php echo htmlspecialchars($label); ?></strong>
                <?php foreach ($services as $service): ?>
                    <?php $emoji = isset($skill_emojis[$service]) ? $skill_emojis[$service] : ''; ?>
                    <label style="min-width: 180px; display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                        <input type="checkbox" name="services[]" value="<?php echo htmlspecialchars($service); ?>" <?php if (in_array($service, $user_services)) echo 'checked'; ?>>
                        <span><?php echo $emoji . ' ' . htmlspecialchars($service); ?></span>
                    </label>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </label>
    <div class="btn-row">
        <button type="submit" name="save_profile" class="edit-profile-btn">Save</button>
        <button type="button" onclick="window.parent.closeEditProfileModal()" class="edit-profile-btn cancel">Cancel</button>
    </div>
</form> 