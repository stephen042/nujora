<?php
// You can include your header here if you have one, e.g.:
// include 'includes/header.php';
require '../app/config.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Privacy Policy| <?= APP_NAME ?></title>
  <link rel="icon" type="image/png" href="../uploads/default-product.png">
  <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f8f9fa;
      margin: 0;
    }

    .privacy-container {
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

  <div class="privacy-container">
    <h1>Nujora Privacy Notice</h1>

    <h2>1. Overview</h2>
    <p>
      This Privacy Notice explains how Nujora collects, uses, stores, and safeguards your personal information when you visit or interact with our website, mobile apps, services, or other Nujora platforms that reference this policy. It also outlines your rights and how to exercise them.
    </p>

    <h2>2. Who We Are</h2>
    <p>
      Nujora is an online marketplace that connects shoppers with trusted sellers and supports order fulfillment through integrated delivery and payment services. Our operations are managed by Ramor Logistics and Tech Services LTD in Nigeria. Depending on the service you use, your data may be controlled by Nujora or an authorized partner operating that service.
    </p>

    <h2>3. Information We Collect</h2>
    <p>
      “Personal data” is any information that can directly or indirectly identify you. We collect personal data to provide services, process orders, improve our platform, and comply with legal obligations. We may collect:
    </p>
    <ul>
      <li><strong>Information you provide:</strong> name, contact details, delivery address, payment and billing information, account credentials, and any information you submit in forms, surveys, or messages to our team.</li>
      <li><strong>Demographics and preferences:</strong> date of birth, gender, marketing preferences, and other information you volunteer.</li>
      <li><strong>Usage and device data:</strong> IP address, browser and device information, pages visited, products viewed, search queries, and timestamps.</li>
      <li><strong>Third-party data:</strong> information from payment processors, delivery partners, advertising partners, or other service providers when necessary to deliver and improve our services.</li>
    </ul>

    <h2>4. Cookies and Tracking</h2>
    <p>
      We use cookies and similar technologies to remember preferences, enhance site functionality, and analyze usage. These tools help us deliver a better browsing experience and show relevant recommendations. You can manage cookies through your browser settings. See our <a href="/cookie-policy">Cookie Policy</a> for more details.
    </p>

    <h2>5. How We Use Your Data</h2>
    <p>
      We use personal data to:
    </p>
    <ul>
      <li>Create and manage your account and process orders.</li>
      <li>Deliver products and communicate order updates or support responses.</li>
      <li>Provide personalized recommendations and, with consent where required, marketing communications.</li>
      <li>Improve our website, apps, and services through analytics and testing.</li>
      <li>Detect and prevent fraud, security incidents, or other misuse of our services.</li>
      <li>Comply with legal or regulatory obligations.</li>
    </ul>

    <h2>6. Legal Basis for Processing</h2>
    <p>
      We process personal data where we have a lawful basis, which may include:
    </p>
    <ul>
      <li>Consent (for optional marketing and certain features).</li>
      <li>Performance of a contract (to fulfil orders and provide services).</li>
      <li>Legitimate interests (for fraud prevention, analytics, and platform improvements), balanced against your privacy rights.</li>
      <li>Compliance with legal duties or regulatory obligations.</li>
    </ul>

    <h2>7. Sharing Your Data</h2>
    <p>
      We may share personal information with:
    </p>
    <ul>
      <li>Sellers and delivery partners to fulfil and deliver orders.</li>
      <li>Payment processors, analytics providers, and other trusted service partners who perform services on our behalf.</li>
      <li>Authorities or third parties when required by law, to prevent fraud, or to protect the rights, property, or safety of Nujora and others.</li>
      <li>Third parties in connection with a business transfer, merger, acquisition, or sale of assets.</li>
    </ul>
    <p>
      All partners are required to protect your data and use it only for the purposes we specify.
    </p>

    <h2>8. International Transfers</h2>
    <p>
      If your data is transferred across borders, we will ensure appropriate safeguards and legal protections are in place so your information remains protected in accordance with applicable laws.
    </p>

    <h2>9. Data Retention</h2>
    <p>
      We retain personal data only as long as necessary to provide services, meet legal obligations, resolve disputes, and for legitimate business needs. When data is no longer required, we will securely delete or anonymize it.
    </p>

    <h2>10. Data Security</h2>
    <p>
      We implement reasonable technical and organizational measures to protect personal data from unauthorized access, disclosure, alteration, or destruction. Access to your information is limited to staff and partners who need it to perform business functions and who are bound by confidentiality obligations.
    </p>

    <h2>11. Your Rights</h2>
    <p>
      Depending on your jurisdiction, you may have rights including to access, correct, delete, or restrict processing of your personal data, request portability, or withdraw consent for marketing. To exercise these rights or to close your account, contact us at <a href="mailto:support@nujora.com">support@nujora.com</a> or use the account settings <strong>Delete Account</strong> option.
    </p>
    <p>
      Note that some data may be retained where required by law or for legitimate business purposes such as fraud prevention or record-keeping.
    </p>

    <h2>12. Complaints and Regulatory Authorities</h2>
    <p>
      If you are not satisfied with our response to a data protection matter, you may file a complaint with the Nigeria Data Protection Commission (NDPC) or the relevant data protection authority in your country.
    </p>

    <h2>13. Contact</h2>
    <p>
      For questions about this Privacy Notice or to exercise your privacy rights, contact our Data Protection Officer at:
    </p>
    <p>
      <strong>Email:</strong> <a href="mailto:support@nujora.com">support@nujora.com</a>
    </p>

    <h2>14. Related Policies</h2>
    <ul>
      <li><a href="/cookie-policy">Cookie Policy</a></li>
      <li><a href="/terms-of-service">Terms of Service</a></li>
    </ul>

    <p class="policy-version">
      <em>Version 1 — September 2025</em>
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