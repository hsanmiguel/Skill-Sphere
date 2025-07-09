<?php
// process_contact.php - Process the contact form submissions

// Start session to pass messages between pages
session_start();

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and sanitize inputs
    $name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : '';
    $surname = isset($_POST['surname']) ? htmlspecialchars(trim($_POST['surname'])) : '';
    $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
    $message = isset($_POST['message']) ? htmlspecialchars(trim($_POST['message'])) : '';
    
    // Basic validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($surname)) {
        $errors[] = "Surname is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($message)) {
        $errors[] = "Message is required";
    }
    
    // If there are validation errors
    if (!empty($errors)) {
        $_SESSION['contact_errors'] = $errors;
        $_SESSION['contact_form_data'] = [
            'name' => $name,
            'surname' => $surname,
            'email' => $email,
            'message' => $message
        ];
        
        // Redirect back to contact form
        header("Location: contact.php");
        exit;
    }
    
    // No errors, process the form
    
    // Email configuration
    $to = "skillsphere.services@gmail.com"; // Replace with your email
    $subject = "New Contact Form Submission from $name $surname";
    
    // Prepare email content
    $email_content = "Name: $name $surname\n";
    $email_content .= "Email: $email\n\n";
    $email_content .= "Message:\n$message\n";
    
    // Email headers
    $headers = "From: $email\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Send email
    $mail_sent = mail($to, $subject, $email_content, $headers);
    
    // Alternative: Save to database instead of sending email
    // You would need to set up a database connection and create a table for messages
    /*
    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "skillsphere";
    
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Prepare and execute statement
    $stmt = $conn->prepare("INSERT INTO contact_messages (name, surname, email, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $surname, $email, $message);
    $db_saved = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    */
    
    // Set success message
    if ($mail_sent) {
        $_SESSION['contact_success'] = "Thank you for your message! We'll get back to you soon.";
    } else {
        // If email fails, store error
        $_SESSION['contact_errors'] = ["There was a problem sending your message. Please try again later."];
        $_SESSION['contact_form_data'] = [
            'name' => $name,
            'surname' => $surname,
            'email' => $email,
            'message' => $message
        ];
    }
    
    // Redirect back to contact page
    header("Location: contact.php");
    exit;
} else {
    // If someone tries to access this file directly, redirect to contact page
    header("Location: contact.php");
    exit;
}

// Do not include footer or any output here; this is a pure backend processor.
// All redirects are handled above.
exit;