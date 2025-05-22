<?php
// Start the session
session_start();

// Database connection variables
$servername = "localhost"; // Adjust to your server
$username = "root"; // Your database username
$password = ""; // Your database password
$dbname = "registered_accounts"; // Your database name

// Create a connection to the MySQL database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if the connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables for error messages
$emailErr = $passwordErr = $generalErr = "";
$email = $password = "";
$confirmPasswordErr = "";

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Sanitize and validate email input
    if (empty($_POST["email"])) {
        $emailErr = "Email is required.";
    } else {
        $email = test_input($_POST["email"]);
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email format.";
        }
    }

    // Sanitize and validate password input
    if (empty($_POST["password"])) {
        $passwordErr = "Password is required.";
    } else {
        $password = test_input($_POST["password"]);
        // You can add additional password validation rules here if needed
    }

    // Sanitize and validate confirm password input
    $confirm_password = isset($_POST["confirm_password"]) ? test_input($_POST["confirm_password"]) : "";
    if (empty($confirm_password)) {
        $confirmPasswordErr = "Please confirm your password.";
    } elseif ($password !== $confirm_password) {
        $confirmPasswordErr = "Passwords do not match.";
    }

    // If no errors, check if user already exists
    if (empty($emailErr) && empty($passwordErr) && empty($confirmPasswordErr)) {

        $checkQuery = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        // If email already exists
        if ($stmt->num_rows > 0) {
            $emailErr = "This email is already registered. Try logging in.";
        } else {
            // Hash the password and insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $insertQuery = "INSERT INTO users (email, password) VALUES (?, ?)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("ss", $email, $hashed_password);

            if ($insertStmt->execute()) {
                $_SESSION['message'] = "Registration successful! You can now log in.";
                header("Location: sign_in.php");
                exit();
            } else {
                $generalErr = "Something went wrong. Please try again.";
            }

            $insertStmt->close();
        }

        $stmt->close();
    }
}

// Function to sanitize input
function test_input($data) {
    $data = trim($data);  
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Close the database connection
$conn->close();
?>
