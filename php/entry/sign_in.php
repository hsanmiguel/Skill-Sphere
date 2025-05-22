<?php
session_start();

// DB connection
$conn = new mysqli("localhost", "root", "", "registered_accounts");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$emailErr = $passwordErr = $loginErr = "";
$email = $password = "";

// Process form on POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Email validation
    if (empty($_POST["email"])) {
        $emailErr = "Email is required.";
    } else {
        $email = htmlspecialchars(trim($_POST["email"]));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email format.";
        }
    }

    // Password validation
    if (empty($_POST["password"])) {
        $passwordErr = "Password is required.";
    } else {
        $password = $_POST["password"];
    }

    // If no errors, verify credentials
    if (empty($emailErr) && empty($passwordErr)) {
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($user_id, $hashedPassword);
            $stmt->fetch();

            if (password_verify($password, $hashedPassword)) {
                // Save session variables
                $_SESSION["user_id"] = $user_id;
                $_SESSION["email"] = $email;
            
                // Use PHP header for redirection instead of JavaScript
                header("Location: ../entry/setup.php"); // Adjust path as needed
                exit();
            } else {
                $loginErr = "Incorrect password.";
            }
            
        } else {
            $loginErr = "Email not found.";
        }

        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skill Sphere - Sign in</title>
    <link rel="stylesheet" href="../designs/sign_in1.css?v=1.2">
</head>
<body>
<header>
  <div class="logo-container">
    <a href="../home_page.php" style="text-decoration: none; font-weight: bold; color: #333;"><img src="../assets/logo_ss.png" alt="Skill Sphere Logo" class="logo"></a>
    <h1>Skill Sphere</h1>
  </div>
  <nav>
    <ul>
      <li><a href="../home_page.php">HOME</a></li>
      <li><a href="../services.php">SERVICES</a></li>
      <li><a href="../about_us.php">ABOUT</a></li>
      <li><a href="../contact_us.php">CONTACT US</a></li>
    </ul>
  </nav>
  <div class="join-button">
    <a href="sign_up.php" class="btn">JOIN US!</a>
  </div>
</header>


<div class="form-container">
    <h2>Sign in</h2>
    <form method="POST">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
        <div class="error-message"><?php echo isset($emailErr) ? $emailErr : ''; ?></div>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
        <div class="error-message"><?php echo isset($passwordErr) ? $passwordErr : ''; ?></div>

        <div class="error-message"><?php echo isset($loginErr) ? $loginErr : ''; ?></div>

        <button type="submit">Sign in account</button>
    </form>
    <div class="footer-text">
        Don't have an account? <a href="sign_up.php">Register</a>
    </div>
</div>

<div class="footer-links">
    <a href="#">Security & Privacy</a>
    <a href="#">Terms & Conditions</a>
    <a href="../contact_us.php">Contact</a>
</div>
</body>
</html>
