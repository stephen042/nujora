<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center text-danger">Unauthorized Access</h1>
        <p class="text-center">You do not have permission to access this page.</p>
        <a href="home.php" class="btn btn-primary d-block mx-auto" style="max-width: 200px;">Back to Home</a>
    </div>
</body>
</html>