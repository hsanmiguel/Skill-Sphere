<?php include ('../entry/server.php') ?>
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: sign_in.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Skill Sphere - Register</title>
  <link rel="stylesheet" href="../designs/footer.css">
  <link rel="stylesheet" href="../designs/sign_up1.css">
  <link rel="stylesheet" href="../designs/header1.css">
</head>
<body>
<header>
  <div class="logo-container">
    <a href="../pages/home_page.php" style="text-decoration: none; font-weight: bold; color: #333;"><img src="../assets/logo_ss.png" alt="Skill Sphere Logo" class="logo"></a>
    <h1>Skill Sphere</h1>
  </div>
  <nav>
    <ul>
      <li><a href="../pages/home_page.php">HOME</a></li>
      <li><a href="../pages/services.php">SERVICES</a></li>
      <li><a href="../pages/about_us.php">ABOUT</a></li>
      <li><a href="../pages/contact_us.php">CONTACT</a></li>
      <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "superadmin"): ?>
        <li><a href="../superadmin_dashboard.php">SUPER ADMIN</a></li>
      <?php endif; ?>
    </ul>
  </nav>
  <?php if (isset($_SESSION["user_id"])): ?>
    <div class="user-info" style="margin-left:auto; display: flex; align-items: center; gap: 18px; font-weight:600; color:#1B4D43; padding-left: 20px;">
      <a href="../user_profile.php?email=<?php echo urlencode($_SESSION['email']); ?>" style="color:#1B4D43; font-weight:600; text-decoration:none;">
        <?php echo htmlspecialchars(isset($_SESSION["first_name"]) ? $_SESSION["first_name"] : (isset($_SESSION["email"]) ? $_SESSION["email"] : "")); ?>
      </a>
      <form method="post" action="" style="display:inline; margin:0;">
        <button type="submit" name="logout" style="margin-left:10px; background: linear-gradient(135deg, #e53935 0%, #ffb733 100%); color: #fff; border: none; border-radius: 20px; padding: 8px 18px; font-weight: 600; cursor: pointer;">Logout</button>
      </form>
    </div>
  <?php else: ?>
    <div class="join-button">
      <a href="sign_in.php" class="btn">JOIN US!</a>
    </div>
  <?php endif; ?>
</header>


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
</body>
</html>
