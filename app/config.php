<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('APP_NAME')) {
    define('APP_NAME', 'Nujora');
}
include 'db.php';
include 'generalFunctions.php';