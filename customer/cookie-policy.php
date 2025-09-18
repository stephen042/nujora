<?php
require '../app/config.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cookie Policy | <?= APP_NAME ?></title>
  <link rel="icon" type="image/png" href="../uploads/default-product.png">
  <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f8f9fa;
      margin: 0;
    }

    .cookies-container {
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

  <div class="cookies-container">
    <h1>Cookies Policy</h1>

    <p>
      This Cookies Policy explains how Nujora uses cookies and similar technologies on our website, mobile apps, and other online services. It describes what cookies are, how we use them, and how you can manage your preferences.
    </p>

    <h2>What are cookies?</h2>
    <p>
      Cookies are small text files placed on your device (computer, tablet, or mobile) when you visit websites. Similar technologies include web beacons, pixels, and local storage. These tools help us remember your preferences, analyse site usage, and improve your experience.
    </p>

    <h2>Types of cookies we use</h2>
    <ul>
      <li><strong>Strictly Necessary Cookies</strong> — Essential for the site to work. They enable functions like secure login, shopping cart operations, and storing your cookie consent. These cannot be disabled via our cookie banner.</li>
      <li><strong>Performance &amp; Analytics Cookies</strong> — Help us understand how visitors use the site (pages visited, time on site, errors). This information is used to improve performance and user experience.</li>
      <li><strong>Functionality Cookies</strong> — Remember your choices (language, region, preferences) so the site behaves consistently and conveniently for you.</li>
      <li><strong>Targeting &amp; Advertising Cookies</strong> — Used to show you relevant ads and measure ad performance. These may track your activity across websites to build a profile of interests.</li>
    </ul>

    <h2>Third-party cookies</h2>
    <p>
      Some cookies are set by third parties (for example analytics providers, advertising networks, or social media platforms). We do not control these cookies — please review the third party’s own policies for more information on how they use data.
    </p>

    <h2>Consent and cookie controls</h2>
    <p>
      When you first visit Nujora, we display a cookie banner asking for your consent to use non-essential cookies (analytics, functionality, targeting). You can:
    </p>
    <ul>
      <li>Accept all cookies.</li>
      <li>Reject non-essential cookies and keep only strictly necessary cookies.</li>
      <li>Adjust your cookie preferences via our <a href="/cookie-settings">Cookie Settings</a> (if available) or through your browser settings.</li>
    </ul>
    <p>
      Disabling or removing cookies may affect site functionality and your user experience.
    </p>

    <h2>How to manage or delete cookies</h2>
    <p>
      You can control cookies through your browser settings (delete cookies, block third-party cookies, or set preferences). For mobile devices, review the device or browser settings. Note that clearing or blocking cookies may require you to re-enter preferences or sign in again.
    </p>

    <h2>How we use information collected by cookies</h2>
    <p>
      Data collected via cookies may be used to:
    </p>
    <ul>
      <li>Improve and secure our website and services.</li>
      <li>Analyse site usage and performance.</li>
      <li>Provide personalised content, recommendations, and relevant advertising (where permitted).</li>
      <li>Detect and prevent fraud or abuse.</li>
    </ul>

    <h2>Children</h2>
    <p>
      Our services are intended for adults. We do not knowingly collect personally identifiable information from children via cookies. If you believe we have inadvertently collected data from a child, please contact us so we can take appropriate action.
    </p>

    <h2>Changes to this policy</h2>
    <p>
      We may update this Cookies Policy occasionally. When we make significant changes, we will notify you via our website or other means. The latest version will always be available on this page.
    </p>

    <h2>Contact us</h2>
    <p>
      If you have questions about this Cookies Policy or want to exercise your privacy rights, contact us at:
    </p>
    <p>
      <strong>Email:</strong> <a href="mailto:support@nujora.com">support@nujora.com</a><br>
      <strong>More info:</strong> see our <a href="/privacy-policy">Privacy Notice</a> and <a href="/terms-of-service">Terms of Service</a>.
    </p>

    <p class="policy-version"><em>Version 1 — September 2025</em></p>
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