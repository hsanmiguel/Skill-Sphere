<?php
include('server.php');
if (session_status() === PHP_SESSION_NONE) session_start();
// Direct DB connection (no db_connect.php)
$host = 'localhost';
$dbname = 'registered_accounts';
$username = 'root';
$password = '';
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: /Skill-Sphere/php/entry/sign_in.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Skill Sphere</title>
    <link rel="stylesheet" href="/Skill-Sphere/php/designs/header1.css">
    <link rel="stylesheet" href="/Skill-Sphere/php/designs/sign_up1.css">
    <link rel="stylesheet" href="/Skill-Sphere/php/designs/footer.css">
</head>
<body>
    <?php include '../components/header.php'; ?>

    <div class="form-container">
        <h2>Register</h2>
        <form method="POST">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>">
            <div class="error-message"><?php echo isset($emailErr) ? $emailErr : ''; ?></div>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <div class="error-message"><?php echo isset($passwordErr) ? $passwordErr : ''; ?></div>

            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            <div class="error-message"><?php echo isset($confirmPasswordErr) ? $confirmPasswordErr : ''; ?></div>

            <div class="error-message"><?php echo isset($generalErr) ? $generalErr : ''; ?></div>

            <button type="submit" name="register">Register account</button>
        </form>
        <div class="footer-text">
            Already have an account? <a href="sign_in.php">Sign in</a>
        </div>
    </div>

    <?php
    // After successful registration, set welcome_popup session variable
    if (isset($_SESSION['welcome_popup'])) {
        echo '<script>alert("' . $_SESSION['welcome_popup'] . '");</script>';
        unset($_SESSION['welcome_popup']);
    }
    ?>

    <?php include '../components/footer.php'; ?>

<?php
// Close the database connection
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
</body>
</html>
