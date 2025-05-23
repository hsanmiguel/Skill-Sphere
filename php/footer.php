<?php
$footer_prefix = (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__) || strpos($_SERVER['SCRIPT_NAME'], '/entry/') !== false) ? '../' : '';
?>
<footer>
    <div class="footer-links">
        <a href="<?php echo $footer_prefix; ?>security-privacy.php">Security & Privacy</a>
        <a href="<?php echo $footer_prefix; ?>terms.php">Terms & Conditions</a>
        <a href="<?php echo $footer_prefix; ?>contact_us.php">Contact</a>
    </div>
    <div class="copyright">
        &copy; <?php echo date('Y'); ?> Skill Sphere. All rights reserved.
    </div>
    <script src="<?php echo $footer_prefix; ?>js/main.js"></script>
</footer> 