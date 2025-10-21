<?php

require '../app/config.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Frequently Asked Questions| <?= APP_NAME ?></title>
  <link rel="icon" type="image/png" href="../uploads/default-product.png">
  <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f8f9fa;
      margin: 0;
    }

    .faqs-container {
      max-width: 950px;
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
    li,
    dl,
    dd,
    dt,
    ol,
    ul {
      color: #444;
      line-height: 1.7;
      text-align: justify;
    }

    ul,
    ol {
      margin-left: 18px;
    }
  </style>
</head>

<body>
  <!-- Navigation -->
  <?php include 'includes/nav.php'; ?>

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="container text-center">
      <?php if (isset($_SESSION['user_name'])): ?>
        <h1 class="display-5 fw-bold mb-3">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h1>
      <?php endif; ?>
      <!-- <p class="lead mb-4">Shop from trusted sellers in your community</p> -->
    </div>
  </section>

  <div class="faqs-container">
    <h1>Frequently Asked Questions (FAQs)</h1>
    <p>
      Welcome to Nujora’s FAQ page. Below are answers to some of the most common questions from our Buyers and Sellers.
      If you need additional help, please <a href="https://nujora.ng/customer/contact-us.php">contact our support team</a>.
    </p>

    <h2>For Buyers</h2>
    <h3>1. How do I create an account?</h3>
    <p>
      Click on the “Sign Up” button at the top of the homepage, provide your email, phone number, and password,
      then verify your details via the confirmation email or SMS.
    </p>

    <h3>2. How do I place an order?</h3>
    <p>
      Browse products, select your preferred item, choose quantity or options if available, and click “Add to Cart.”
      Proceed to checkout, provide your delivery details, and complete payment.
    </p>

    <h3>3. What payment methods do you accept?</h3>
    <p>
      We accept bank transfers, debit/credit cards, mobile money, and other local payment methods available in Nigeria.
    </p>

    <h3>4. How can I track my order?</h3>
    <p>
      Log in to your account, go to “My Orders,” and click on the order to view tracking updates provided by the Seller or Nujora.
    </p>

    <h3>5. Can I return or exchange a product?</h3>
    <p>
      Yes, most items can be returned within 7–14 days of receipt if they are unused, in original packaging, and meet
      our <a href="https://nujora.ng/customer/returns.php">Returns Policy</a>.
    </p>

    <h3>6. What should I do if I have a dispute with a Seller?</h3>
    <p>
      First, contact the Seller through Nujora’s messaging system. If unresolved within 48 hours, escalate through our
      <a href="https://nujora.ng/customer/dispute-resolution-policy.php">Dispute Resolution Policy</a>.
    </p>

    <h2>For Sellers</h2>
    <h3>7. How do I register as a Seller?</h3>
    <p>
      Sign up on our platform, select “Seller” during registration, and provide your business or shop name, contact
      details, and all other informaion required. upon regisrration your account will be pending meaning you can not access your accoont, upon successful review by our team your account will be approved and you can start sellting on nujora .
    </p>

    <h3>8. Are there any fees for listing products?</h3>
    <p>
      Listing products is free. Nujora charges a commission on each completed sale based on product category.
      See our <a href="https://nujora.ng/customer/selling-on-nujora.php">Seller Terms of Service</a> for details.
    </p>

    <h3>9. How quickly must I ship orders?</h3>
    <p>
      Orders should be shipped within 24 hours for deliveries within Kano and within 2–5 days for other locations.
    </p>

    <h3>10. When will I receive payment for my sales?</h3>
    <p>
      Payouts are processed immeidately and include Net Sale Proceeds after deducting commissions, refunds, or chargebacks.
    </p>

    <h3>11. What happens if my rating drops or I cancel too many orders?</h3>
    <p>
      Consistently low ratings or high cancellations may result in warnings, temporary restrictions, or deactivation of your Seller account.
    </p>

    <h3>12. How can I grow my sales on Nujora?</h3>
    <p>
      Maintain high-quality listings with accurate descriptions and images, respond to customer inquiries promptly,
      offer competitive pricing, and participate in Nujora promotions or training sessions.
    </p>

    <h2>General</h2>
    <h3>13. How does Nujora protect my data?</h3>
    <p>
      We prioritize your privacy. Your personal information is handled according to our
      <a href="https://nujora.ng/customer/privacy-policy.php">Privacy Policy</a> and <a href="https://nujora.ng/customer/cookie-policy.php">Cookies Policy</a>.
    </p>

    <h3>14. Can Nujora suspend or close my account?</h3>
    <p>
      Yes, accounts may be suspended or closed for violations of our <a href="https://nujora.ng/customer/terms-of-service.php">Terms of Service</a>,
      fraudulent activity, or harmful conduct.
    </p>

    <h3>15. How can I contact customer support?</h3>
    <p>
      Email us at <a href="mailto:support@nujora.ng">support@nujora.ng</a> or use the
      <a href="https://nujora.ng/customer/contact-us.php">Contact Us</a> page on our website.
    </p>
  </div>


  <!-- Footer -->
  <?php include 'includes/footer.php'; ?>

  <!-- Bottom Navigation -->
  <?php include 'includes/bottomNav.php'; ?>

  <!-- Script -->
  <?php include 'includes/script.php'; ?>
  <script>
    function scrollCategory(val) {
      document.getElementById('categoryScroll').scrollBy({
        left: val,
        behavior: 'smooth'
      });
    }
  </script>
</body>

</html>