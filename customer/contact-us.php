<?php
require '../app/config.php';
// include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Contact Us  | <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" href="../uploads/default-product.png">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Open+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .contact-container {
            max-width: 700px;
            margin: 40px auto;
            background: #fff;
            padding: 32px 24px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        h1 {
            color: #222;
            margin-bottom: 18px;
        }

        p,
        li {
            color: #444;
            line-height: 1.7;
        }

        .contact-info {
            margin-bottom: 24px;
        }

        .contact-info i {
            color: #007bff;
            margin-right: 10px;
        }
    </style>
</head>

<body>
    <?php include 'includes/nav.php'; ?>
    <div class="contact-container">
        <h1>Contact Us</h1>
        <div class="contact-info">
            <p><i class="fas fa-phone"></i> <strong>Phone:</strong> <a href="tel:07012382848">07012382848</a></p>
            <p><i class="fas fa-envelope"></i> <strong>Email:</strong> <a href="mailto:support@nujora.ng">support@nujora.ng</a></p>
        </div>
        <h3>Send Us a Message</h3>
        <form method="post" action="#">
            <div class="mb-3">
                <label for="name" class="form-label">Your Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Your Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="message" class="form-label">Message</label>
                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send Message</button>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/bottomNav.php'; ?>
    <?php include 'includes/script.php'; ?>
</body>

</html>