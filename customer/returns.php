<?php
// You can include your header here if you have one, e.g.:
// include 'includes/header.php';
require '../app/config.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Return Policy | <?= APP_NAME ?></title>
  <link rel="icon" type="image/png" href="../uploads/default-product.png">
  <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Open+Sans&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f8f9fa;
      margin: 0;
    }

    .returns-container {
      max-width: 1000px;
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

    ul {
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

  <div class="returns-container">
    <h1>Returns &amp; Refunds Policy</h1>

    <h2>1. Introduction</h2>
    <p>
      At Nujora, your satisfaction is important to us. We work to ensure every order meets your expectations,
      but if you need to return a product this policy explains when returns are accepted, how refunds are handled,
      and the situations where returns or refunds may not be possible.
    </p>

    <h2>2. Return Period and Conditions</h2>
    <p>
      Most items purchased on Nujora are eligible for return within <strong>seven (7) days</strong> of delivery,
      provided they meet the conditions described in this policy. Requests made after this period will not be accepted
      except where required by law.
    </p>
    <p>You may request a return for the following reasons (subject to the exclusions listed below):</p>
    <ul>
      <li>You changed your mind about the purchase (excluding categories restricted for health, hygiene, or safety).</li>
      <li>The size is correct but the item does not fit as expected (clothing and footwear only).</li>
      <li>The product malfunctions after reasonable initial use (some categories such as consumables, clothing, and fitness items are excluded).</li>
      <li>The product arrived damaged, broken, or with seriously compromised packaging.</li>
      <li>Parts, accessories, or components were missing on delivery.</li>
      <li>The product appears used, expired, or previously opened (software is excluded).</li>
      <li>You suspect the product is counterfeit or inauthentic.</li>
      <li>You received the wrong item, size, color, or model.</li>
    </ul>

    <h2>3. Items Not Eligible for Return</h2>
    <p>
      For health, hygiene, or quality reasons, certain products cannot be returned unless they are defective, counterfeit,
      incorrect, or damaged on arrival. Non-returnable categories include, but are not limited to:
    </p>
    <ul>
      <li>Food, beverages, groceries, and other perishable goods.</li>
      <li>Medicines, health supplements, and closely related products.</li>
      <li>Skincare, makeup, haircare, fragrances, deodorants, and other personal care items.</li>
      <li>Undergarments, swimwear, and other intimate apparel.</li>
      <li>Customized or personalized products, software, and event tickets.</li>
    </ul>
    <p>
      Items damaged by the customer, showing excessive wear beyond reasonable handling, or missing original packaging
      and tags are not eligible for return.
    </p>

    <h2>4. How to Return Items</h2>
    <p>
      Returned items must be in the <strong>same condition</strong> as when you received them, including original packaging,
      labels, and tags. You are responsible for the item until it reaches us or the seller, so please pack returns securely
      to prevent damage during transit.
    </p>

    <h2>5. How to Initiate a Return</h2>
    <ol>
      <li>Contact our support team at <a href="mailto:support@nujora.com">support@nujora.com</a> with your order number and reason for return.</li>
      <li>Our support team will provide return instructions and the correct return address.</li>
      <li>Pack your item securely and ship it to the address provided.</li>
      <li>Once we receive and inspect your item, we will process your refund or exchange within <strong>5–7 business days</strong>.</li>
      <li>For additional details, please review our <a href="/nujora/customer/terms-of-service.php">Terms of Service</a> or contact our support team.</li>
    </ol>

    <h2>6. Refunds</h2>
    <p>
      If your return is approved, we will refund the purchase price according to the timelines stated on our returns page.
      For items that were defective, damaged, or incorrectly supplied, we will also refund any original delivery fees.
    </p>

    <h2>7. Rejected Returns</h2>
    <p>
      All returned items are inspected to verify the reason for return. If a return is not approved, Nujora will attempt
      redelivery twice. If those attempts fail, you will be required to collect the item within <strong>60 days</strong>.
      Items not collected after this period will be forfeited.
    </p>

    <h2>8. Exchanges</h2>
    <p>
      Nujora does not offer direct exchanges. If you want a different size, color, or item, please return the original purchase
      (where eligible) and place a new order for the replacement.
    </p>

    <p>
      For more information, please read our <a href="/nujora/customer/terms-of-service.php">Terms of Service</a> or
      <a href="/nujora/customer/contact-us.php">contact us</a>.
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