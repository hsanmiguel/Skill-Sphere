<?php 
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "registered_accounts";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Debug: Print all POST data to see what's being received
echo "<pre>POST Data: ";
print_r($_POST);
echo "</pre>";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    function test_input($data) {
        return htmlspecialchars(stripslashes(trim($data)));
    }
    
    $first_name = test_input($_POST['first-name']);
    $last_name = test_input($_POST['last-name']);
    $mi = test_input($_POST['mi']);
    $address = test_input($_POST['address']);
    $phone_number = test_input($_POST['phone-number']);
    $email = test_input($_POST['email']);
    $social_media = test_input($_POST['social-media']);
    $experience = isset($_POST['experience']) ? intval($_POST['experience']) : 0;
    
    // Fix: Get skills and services from the hidden fields
    $skills = isset($_POST['skills']) ? test_input($_POST['skills']) : '';
    $services = isset($_POST['selected-service']) ? test_input($_POST['selected-service']) : '';
    
    // Debug: Print specific variables to verify their values
    echo "<p>Skills: '$skills'</p>";
    echo "<p>Services: '$services'</p>";
    
    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO user_profiles 
        (first_name, last_name, middle_initial, address, phone_number, email, social_media, experience, skills, services)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Check if preparation was successful
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    // Bind parameters - all as strings for simplicity
    $stmt->bind_param("ssssssssss", 
        $first_name, 
        $last_name, 
        $mi, 
        $address, 
        $phone_number, 
        $email, 
        $social_media, 
        $experience, 
        $skills, 
        $services
    );
    
    // Execute and check result
    if ($stmt->execute()) {
        $_SESSION['message'] = "Profile saved successfully!";
        header("Location: ../home_page.php"); // Redirect to a success page
        exit();
    } else {
        echo "Execute failed: " . $stmt->error;
    }
    
    $stmt->close();
}

$conn->close();
?>