<?php
require 'app/config.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Terms Of Services | <?= APP_NAME ?></title>
  <link rel="icon" type="image/png" href="uploads/default-product.png">
  <link href="assets/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f8f9fa;
      margin: 0;
    }

    .terms-container {
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

  <div class="terms-container">
    <h1>Nujora Terms of Service</h1>
    <p><strong>Effective Date:</strong> 1st September 2025</p>

    <h2>1. Definitions</h2>
    <p>These definitions apply whenever used in these Terms:</p>
    <ul>
      <li><strong>User:</strong> Any person using Nujora, whether as Buyer, Seller, or visitor.</li>
      <li><strong>Buyer:</strong> A User who purchases goods via Nujora.</li>
      <li><strong>Seller:</strong> A User who lists goods for sale on Nujora.</li>
      <li><strong>Listing:</strong> Product information provided by a Seller, including title, description, pricing, and photos.</li>
      <li><strong>Order:</strong> A confirmed sale transaction initiated by a Buyer and fulfilled by a Seller.</li>
      <li><strong>Net Sale Proceeds:</strong> The amount paid to a Seller after Nujora deducts commissions, fees, refunds, or chargebacks.</li>
    </ul>

    <h2>2. Account Eligibility & User Obligations</h2>
    <ul>
      <li>Users must be at least 18 years old.</li>
      <li>Buyers and Sellers must provide accurate, current, and verifiable information.</li>
      <li>Users are responsible for maintaining the security of their accounts and credentials.</li>
    </ul>

    <h2>3. Listings, Products & Content (For Sellers)</h2>
    <ul>
      <li>Sellers must ensure they are authorized to sell listed products.</li>
      <li>Listings must be accurate, non-misleading, and comply with applicable laws and Nujora policies.</li>
      <li>Illegal, counterfeit, or prohibited goods are not allowed.</li>
      <li>Nujora may edit, remove, or suppress Listings that violate standards.</li>
    </ul>

    <h2>4. Orders, Fulfillment & Buyer Commitments</h2>
    <ul>
      <li>Sellers must fulfill Orders within the specified timeframe.</li>
      <li>Buyers must pay for orders using approved methods and provide accurate delivery details.</li>
      <li>Both Sellers and Buyers must act in good faith to ensure smooth transactions.</li>
    </ul>

    <h2>5. Fees, Payments & Refunds</h2>
    <ul>
      <li>Sellers pay commissions or platform charges per sale. Fees may change with notice.</li>
      <li>Payments to Sellers are based on Net Sale Proceeds after deductions.</li>
      <li>Refunds are available to Buyers for defective, damaged, or undelivered items.</li>
    </ul>

    <h2>6. Returns, Refunds & Dispute Resolution</h2>
    <ul>
      <li>Nujora provides a structured returns/refunds process which Sellers must follow.</li>
      <li>Sellers must respond to complaints within 48 hours; otherwise, Nujora may step in to resolve disputes.</li>
      <li>Buyers must provide evidence (e.g., photos) for refund claims.</li>
    </ul>

    <h2>7. Warranties & Legal Compliance</h2>
    <ul>
      <li>Sellers warrant that their goods are legal, safe, and compliant with all regulations.</li>
      <li>Sellers must not infringe third-party intellectual property rights.</li>
      <li>Buyers are responsible for lawful use of purchased goods.</li>
    </ul>

    <h2>8. Intellectual Property & Licensing</h2>
    <ul>
      <li>Sellers retain ownership of their product content but grant Nujora a non-exclusive license to use it for platform operations and promotion.</li>
      <li>Buyers may not reproduce or misuse content belonging to Sellers or Nujora.</li>
    </ul>

    <h2>9. Confidentiality & Privacy</h2>
    <ul>
      <li>Users must keep confidential any non-public information obtained through Nujora.</li>
      <li>User data is handled under Nujora’s Privacy Notice and Cookies Policy in line with applicable laws.</li>
    </ul>

    <h2>10. Liability, Indemnity & Limitation</h2>
    <ul>
      <li>Users agree to indemnify Nujora against claims, damages, or losses arising from misuse of the platform or policy violations.</li>
      <li>Nujora’s liability is limited to direct damages up to the fees paid by the User in the last 3 months. Indirect or consequential damages are excluded where permitted by law.</li>
    </ul>

    <h2>11. Termination & Suspension</h2>
    <ul>
      <li>Nujora may suspend or terminate accounts for breaches, fraud, or harmful conduct.</li>
      <li>Users may close their accounts but must fulfill outstanding obligations like pending orders or refunds.</li>
    </ul>

    <h2>12. Governing Law & Dispute Resolution</h2>
    <ul>
      <li>These Terms are governed by the laws of Nigeria.</li>
      <li>Disputes should follow Nujora’s Dispute Resolution Policy before litigation.</li>
      <li>Nigerian courts shall have jurisdiction over unresolved disputes.</li>
    </ul>

    <h2>13. Changes to the Terms</h2>
    <p>
      Nujora may modify these Terms from time to time. Significant changes will be communicated via email or platform notice at least 14 days before taking effect. Continued use of the platform constitutes acceptance of the revised Terms.
    </p>

    <h2>14. Contact Information</h2>
    <p>
      For support or questions regarding these Terms:<br>
      <strong>Email:</strong> support@nujora.ng<br>
      <!-- <strong>Address:</strong> [Your Company Address] -->
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