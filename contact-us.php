<?php
// Include configuration (once)
require_once __DIR__ . '/app/config.php';
include 'includes/nav.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" href="uploads/default-product.png">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #ff6600;
            /* Nujora brand orange */
            --secondary-color: #6c757d;
            --accent-color: #ff8800;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }

        body {
            background-color: #fff7f0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .contact-hero {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 60px 0;
            text-align: center;
            margin-bottom: 40px;
        }

        .contact-container {
            max-width: 1200px;
            margin: 0 auto 40px;
            padding: 0 15px;
        }

        .contact-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .contact-info {
            background: linear-gradient(to bottom right, var(--primary-color), var(--accent-color));
            color: white;
            padding: 40px;
        }

        .contact-info i {
            background: rgba(255, 255, 255, 0.2);
            width: 50px;
            height: 50px;
            line-height: 50px;
            text-align: center;
            border-radius: 50%;
            margin-right: 15px;
            font-size: 20px;
        }

        .contact-form {
            padding: 40px;
        }

        .form-control {
            border: 1px solid #e1e1e1;
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(255, 102, 0, 0.15);
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 12px 25px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background: #e65c00;
            transform: translateY(-2px);
        }

        .contact-method {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }

        .social-links {
            margin-top: 30px;
        }

        .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-radius: 50%;
            margin-right: 10px;
            transition: all 0.3s;
        }

        .social-links a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
        }

        .map-container {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-top: 40px;
        }

        .contact-title {
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--dark-color);
        }

        @media (max-width: 768px) {

            .contact-info,
            .contact-form {
                padding: 25px;
            }
        }
    </style>
</head>

<body>
    <header class="contact-hero">
        <div class="container">
            <h1 class="display-4 fw-bold">Get in Touch</h1>
            <p class="lead">We'd love to hear from you. Here's how you can reach us.</p>
        </div>
    </header>

    <div class="contact-container">
        <div class="row">
            <div class="col-lg-7">
                <div class="contact-card">
                    <div class="contact-form">
                        <h2 class="contact-title">Send us a Message</h2>
                        <form method="post" action="#">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Your Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Your Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" required>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="contact-info rounded">
                    <h2 class="contact-title text-white">Contact Information</h2>

                    <div class="contact-method">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h5 class="mb-1">Phone</h5>
                            <p class="mb-0"><a href="tel:07012382848" class="text-white">07012382848</a></p>
                        </div>
                    </div>

                    <div class="contact-method">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h5 class="mb-1">Email</h5>
                            <p class="mb-0"><a href="mailto:support@nujora.ng" class="text-white">support@nujora.ng</a></p>
                        </div>
                    </div>

                    <!-- <div class="contact-method">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h5 class="mb-1">Address</h5>
                            <p class="mb-0">123 Education Street, Academic District, Nigeria</p>
                        </div>
                    </div> -->

                    <div class="contact-method">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h5 class="mb-1">Working Hours</h5>
                            <p class="mb-0">Monday - Friday: 8:00 AM - 5:00 PM</p>
                            <p class="mb-0">Saturday: 9:00 AM - 1:00 PM</p>
                        </div>
                    </div>

                    <div class="social-links">
                        <a href="https://www.facebook.com/nujora"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="map-container">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126845.0220093667!2d3.34148455!3d6.5488881499999995!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x103b8ed7c7e30c0d%3A0x6ac5e94ab554b1a1!2sLagos%2C%20Nigeria!5e0!3m2!1sen!2sus!4v1651234567890!5m2!1sen!2sus" width="100%" height="350" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/bottomNav.php'; ?>
    <?php include 'includes/script.php'; ?>
</body>

</html>