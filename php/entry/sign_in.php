<?php
if (session_status() === PHP_SESSION_NONE) session_start();

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
        $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($user_id, $hashedPassword, $role);
            $stmt->fetch();

            if (password_verify($password, $hashedPassword)) {
                // Save session variables
                $_SESSION["user_id"] = $user_id;
                $_SESSION["email"] = $email;
                $_SESSION["role"] = $role;
                $_SESSION['welcome_popup'] = 'Welcome back! You have successfully signed in.';
            
                // Fetch first name from user_profiles
                $firstName = "";
                $profileStmt = $conn->prepare("SELECT first_name FROM user_profiles WHERE email = ?");
                $profileStmt->bind_param("s", $email);
                $profileStmt->execute();
                $profileStmt->bind_result($firstName);
                $profileStmt->fetch();
                $_SESSION["first_name"] = $firstName ? $firstName : $email; // fallback to email if no name
                $profileStmt->close();

                // Check if user has completed setup (has a profile)
                $profileCheckStmt = $conn->prepare("SELECT id FROM user_profiles WHERE email = ?");
                $profileCheckStmt->bind_param("s", $email);
                $profileCheckStmt->execute();
                $profileCheckStmt->store_result();

                if ($profileCheckStmt->num_rows > 0) {
                    if ($role === 'superadmin') {
                        header("Location: ../superadmin_dashboard.php");
                    } else {
                        header("Location: ../home_page.php");
                    }
                    exit();
                } else {
                    if ($role === 'superadmin') {
                        header("Location: ../superadmin_dashboard.php");
                    } else {
                        header("Location: ../entry/setup.php");
                    }
                    exit();
                }
            } else {
                $loginErr = "Incorrect password.";
            }
            
        } else {
            $loginErr = "Email not found.";
        }

        $stmt->close();
    }
}

// Handle logout
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: sign_in.php");
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skill Sphere - Sign in</title>
    <link rel="stylesheet" href="../designs/footer.css">
    <link rel="stylesheet" href="../designs/sign_in1.css?v=1.2">
    <link rel="stylesheet" href="../designs/header1.css">
    <style>
      body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
      }
      .main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
      }
    </style>
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
      <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "superadmin"): ?>
        <li><a href="../superadmin_dashboard.php">SUPER ADMIN</a></li>
      <?php endif; ?>
    </ul>
  </nav>
  <?php if (isset($_SESSION["user_id"])): ?>
    <div class="user-info" style="margin-left:auto; display: flex; align-items: center; gap: 18px; font-weight:600; color:#1B4D43; padding-left: 20px;">
      <a href="../user_profile.php?email=<?php echo urlencode($_SESSION['email']); ?>" style="color:#1B4D43; font-weight:600; text-decoration:none; display: flex; align-items: center; gap: 6px;">
        <span style="display:inline-flex; align-items:center;">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" style="vertical-align:middle; margin-right:6px;" xmlns="http://www.w3.org/2000/svg">
            <circle cx="10" cy="7" r="4" fill="#1B4D43"/>
            <ellipse cx="10" cy="15" rx="7" ry="4" fill="#1B4D43"/>
          </svg>
          <?php echo htmlspecialchars(isset($_SESSION["first_name"]) ? $_SESSION["first_name"] : (isset($_SESSION["email"]) ? $_SESSION["email"] : "")); ?>
        </span>
      </a>
      <form method="post" action="" style="display:inline; margin:0;">
        <button type="submit" name="logout" style="margin-left:10px; background: linear-gradient(135deg, #e53935 0%, #ffb733 100%); color: #fff; border: none; border-radius: 20px; padding: 8px 18px; font-weight: 600; cursor: pointer;">Logout</button>
      </form>
    </div>
  <?php else: ?>
    <div class="join-button">
      <a href="sign_up.php" class="btn">JOIN US!</a>
    </div>
  <?php endif; ?>
</header>

<div class="main-content">
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
</div>

<?php include '../footer.php'; ?>
</body>
</html>
