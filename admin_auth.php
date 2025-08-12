<!-- <?php
session_start();

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location:login.php");
    exit;
}

// Verify admin role if you have different user levels
if ($_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit;
}
?> -->