<?php
$baseUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
header('Location: ' . $baseUrl . '/index.php');
exit;