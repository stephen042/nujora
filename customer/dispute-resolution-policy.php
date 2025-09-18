<?php
require '../app/config.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dispute Resolution Policy | <?= APP_NAME ?></title>
  <link rel="icon" type="image/png" href="../uploads/default-product.png">
  <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f8f9fa;
      margin: 0;
    }

    .dispute-resolution-container {
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

  <div class="dispute-resolution-container">
    <h1>Dispute Resolution Policy</h1>
    <h2>1. Purpose</h2>
    <p>
      At Nujora, we are committed to fair, transparent, and trustworthy transactions.
      This Dispute Resolution Policy explains how grievances between customers and Nujora,
      or between customers and sellers on our platform, can be submitted, reviewed, and resolved
      efficiently while respecting applicable Nigerian laws.
    </p>

    <h2>2. Scope</h2>
    <p>
      This policy applies to all purchases, services, and interactions conducted via Nujora’s website,
      mobile app, customer service, or any affiliated channels, including delivery and payment services.
      It covers issues such as defective products, non-delivery, damaged items, misrepresentation,
      or any other concerns arising from a purchase.
    </p>

    <h2>3. Informal Resolution First</h2>
    <p>
      Before initiating a formal dispute, we encourage you to contact our Customer Support:
    </p>
    <ul>
      <li>Email: <a href="mailto:support@nujora.com">support@nujora.com</a></li>
      <li>
        Provide your order reference number, a description of the problem, and any supporting
        documentation (photos, messages, etc.).
      </li>
      <li>
        We will acknowledge receipt of your complaint within <strong>2 business days</strong>
        and attempt to resolve it within <strong>7 business days</strong>.
      </li>
    </ul>

    <h2>4. Alternative Dispute Resolution Options</h2>
    <ul>
      <li><strong>Mediation:</strong> A neutral third party assists both you and the seller (or Nujora) to reach a mutually acceptable agreement.</li>
      <li><strong>Online Dispute Resolution (ODR):</strong> Where feasible, disputes can be handled electronically via video conferences, messaging, or other virtual tools to reduce delays and costs.</li>
    </ul>

    <h2>5. Formal Dispute Process</h2>
    <ol>
      <li>
        <strong>Submit a Formal Dispute:</strong> Fill out the Dispute Form (available via website/app)
        with all required details (order number, issue description, preferred remedy—refund, replacement, etc.)
        and include any evidence (photos, receipts, communications).
      </li>
      <li>
        <strong>Review by Nujora:</strong> Our Dispute Resolution Team will acknowledge your formal dispute within
        <strong>3 business days</strong> and propose a decision or settlement within
        <strong>14 business days</strong> of acknowledgment.
      </li>
      <li>
        <strong>Appeal:</strong> If you disagree with the decision, you may request a review.
        Appeals will be reviewed by a senior or separate resolution officer, and a final internal decision will
        be made within <strong>7 business days</strong>.
      </li>
    </ol>

    <h2>6. Remedies</h2>
    <ul>
      <li>Full or partial refund</li>
      <li>Replacement of item</li>
      <li>Credit note or voucher</li>
      <li>Delivery cost reimbursement</li>
      <li>Any other remedy that is fair and consistent with Nujora’s policies and legal obligations</li>
    </ul>

    <h2>7. Legal Rights and Regulatory Bodies</h2>
    <p>
      Nothing in this policy limits your legal rights under Nigerian law. If you are not satisfied
      with the resolution, you have the right to pursue other legal options. You may approach the
      <strong>Federal Competition and Consumer Protection Commission (FCCPC)</strong> or take the matter
      to court or arbitration in accordance with applicable laws.
    </p>

    <h2>8. Jurisdiction and Governing Law</h2>
    <p>
      This policy is governed by the laws of Nigeria. Any disputes that escalate beyond Nujora’s
      dispute resolution processes will be handled in courts or tribunals having jurisdiction in
      Nujora’s registered address location or as required by applicable laws.
    </p>

    <h2>9. Confidentiality</h2>
    <p>
      All dispute proceedings, including evidence and decisions, will be treated as confidential
      (unless disclosure is required by law). Information will only be shared with relevant parties
      as needed to resolve the dispute.
    </p>

    <h2>10. Updates to Policy</h2>
    <p>
      Nujora reserves the right to update or modify this Dispute Resolution Policy at any time.
      Changes will be posted on our website/app, and where required by law, notified to users.
      The version in effect at the time a dispute is submitted will apply.
    </p>

    <h2>11. Contact</h2>
    <p>
      If you have questions about this policy or want to submit a dispute, contact:<br>
      <strong>Dispute Resolution Team</strong><br>
      Email: <a href="mailto:support@nujora.com">support@nujora.com</a><br>

    </p>
    <p>
      <!-- Phone: <a href="tel:07012382848">07012382848</a> -->
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