<?php include ('server.php') ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Skill Sphere - Register</title>
  <link rel="stylesheet" href="../designs/sign_up1.css">
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

  <footer>
    <div class="footer-links">
      <a href="#">Security & Privacy</a>
      <a href="#">Terms & Conditions</a>
      <a href="../contact_us.php">Contact</a>
    </div>
  </footer>
</body>
</html>
