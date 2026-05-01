<?php
// nav.php — include this at the top of every PHP/HTML page
$current = basename($_SERVER['PHP_SELF']);
function nav_active($file) {
    global $current;
    return $current === $file ? 'active' : '';
}
?>
<header class="site-header">
  <div class="logo">&#x1F3C6; <span>Sports</span> Academy</div>
  <nav class="site-nav">
    <a href="home.html"         class="<?= nav_active('home.html') ?>">&#x1F3E0; Home</a>
    <a href="sports.php"        class="<?= nav_active('sports.php') ?>">&#x26BD; Sports</a>
    <a href="coaches.php"       class="<?= nav_active('coaches.php') ?>">&#x1F3C6; Coaches</a>
    <a href="attendance.php"    class="<?= nav_active('attendance.php') ?>">&#x1F4CB; Attendance</a>
    <a href="feedback.php"      class="<?= nav_active('feedback.php') ?>">&#x1F4AC; Feedback</a>
    <a href="payment.php"       class="<?= nav_active('payment.php') ?>">&#x1F4B3; Payment</a>
    <a href="admin.php"         class="<?= nav_active('admin.php') ?>">&#x1F512; Admin</a>
    <a href="registration.html" class="btn-nav <?= nav_active('registration.html') ?>">&#x270F; Register</a>
  </nav>
</header>
