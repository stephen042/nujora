<?php

require '../app/config.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Selling on <?= APP_NAME ?> | <?= APP_NAME ?></title>
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

    .seller-tos-container,
    .seller-onboarding-container {
      max-width: 950px;
      margin: 40px auto;
      background: #fff;
      padding: 32px 24px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
      text-align: justify;
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

  <div class="seller-tos-container">
    <h1>Nujora Seller Terms of Service (TOS)</h1>

    <h2 id="definitions">1. Definitions</h2>
    <dl>
      <dt>Seller</dt>
      <dd>A person or shop listing and selling items on Nujora.</dd>

      <dt>Buyer</dt>
      <dd>A customer who places an order on the platform.</dd>

      <dt>Listing</dt>
      <dd>The product details a Seller uploads (title, price, photos, description, etc.).</dd>

      <dt>Order</dt>
      <dd>A confirmed purchase placed by a Buyer.</dd>

      <dt>Commission</dt>
      <dd>The fee Nujora charges the Seller for each successful sale.</dd>

      <dt>Payout</dt>
      <dd>The amount remitted to a Seller after Nujora deducts applicable fees.</dd>

      <dt>Return</dt>
      <dd>When a Buyer sends back a product for refund or replacement.</dd>

      <dt>Dispute</dt>
      <dd>A complaint raised about an Order, Listing, or transaction between Buyer and Seller.</dd>

      <dt>TOS (Terms of Service)</dt>
      <dd>The legal rules Sellers agree to when using Nujora.</dd>

      <dt>ID Verification</dt>
      <dd>Checking a Seller’s identity (e.g., NIN, BVN, national ID or other local ID).</dd>

      <dt>Onboarding</dt>
      <dd>Steps to become an active Seller (account creation, profile setup, listing products, etc.).</dd>
    </dl>

    <h2 id="eligibility">1.1 Seller Eligibility &amp; Account</h2>
    <ul>
      <li>Sellers must be at least 18 years old.</li>
      <li>No formal business registration is required. Acceptable identity includes national ID, NIN/BVN, phone number, or a business/shop name.</li>
      <li>Sellers must provide accurate contact and payment details and keep them up to date.</li>
      <li>By registering, Sellers agree to these Terms of Service via a clickwrap acceptance.</li>
    </ul>

    <h2 id="listings">1.2 Listings &amp; Content</h2>
    <ul>
      <li>Sellers warrant they own or are authorized to sell the items listed and that listings are accurate, current, and non-misleading.</li>
      <li>Products must comply with Nujora policies and applicable law. Counterfeit, prohibited, or illegal items are not allowed.</li>
      <li>Nujora reserves the right to edit, suspend, or remove listings that breach quality, legal, or content standards.</li>
    </ul>

    <h2 id="performance">1.3 Performance &amp; Service Level</h2>
    <ul>
      <li>Sellers commit to timely order fulfilment and shipment. Target shipment window: within <strong>2–5 days</strong> (see Onboarding for local Kano timing).</li>
      <li>Sellers must respond to Buyer inquiries within <strong>24 hours</strong> to maintain customer satisfaction.</li>
      <li>Poor performance (low ratings, high cancellations) may result in warnings, operational limits, or account deactivation.</li>
    </ul>

    <h2 id="fees">1.4 Fees &amp; Payments</h2>
    <ul>
      <li>Commission fees are configurable by category and applied per successful sale.</li>
      <li>Payouts are processed regularly (e.g., weekly) and disbursed via bank transfer, mobile money, or other local payment methods.</li>
      <li>Refunds, chargebacks, or penalties due to Buyer claims will be deducted from Seller payouts as required.</li>
    </ul>

    <h2 id="returns">1.5 Returns &amp; Refunds</h2>
    <ul>
      <li>Sellers must maintain a returns policy with a minimum <strong>7-day</strong> return window from the Buyer’s receipt of goods (or comply with a longer platform policy where specified).</li>
      <li>If a Seller does not resolve a valid complaint within <strong>48 hours</strong>, Nujora may mediate and resolve the claim on behalf of the Buyer.</li>
    </ul>

    <h2 id="warranties">1.6 Warranties &amp; Compliance</h2>
    <ul>
      <li>Sellers represent that goods comply with Nigerian law and any relevant standards or certifications.</li>
      <li>Goods must be free from third-party claims, liens, or encumbrances.</li>
      <li>Sellers must provide truthful titles, descriptions, images, and certificates where required.</li>
    </ul>

    <h2 id="ip">1.7 Intellectual Property &amp; Licensing</h2>
    <ul>
      <li>Sellers retain ownership of their intellectual property but grant Nujora a non-exclusive, royalty-free license to display, reproduce, and promote Listing content on the platform and related channels.</li>
      <li>Sellers must not list products that infringe third-party intellectual property rights.</li>
    </ul>

    <h2 id="confidentiality">1.8 Confidentiality</h2>
    <p>
      Both parties must protect confidential information obtained through the business relationship. Confidentiality obligations survive termination for a period of <strong>one (1) year</strong> unless otherwise required by law.
    </p>

    <h2 id="liability">1.9 Liability &amp; Indemnity</h2>
    <ul>
      <li>Sellers agree to indemnify, defend, and hold Nujora harmless from claims, losses, damages, liabilities, and expenses arising from their products, Listings, or conduct.</li>
      <li>Nujora’s liability to Sellers is limited to direct damages and excludes indirect, incidental, consequential, or punitive damages to the fullest extent permitted by law.</li>
    </ul>

    <h2 id="termination">1.10 Termination</h2>
    <ul>
      <li>Nujora may suspend or terminate Seller accounts for material breaches, policy violations, repeated late responses, fraud, or other misconduct.</li>
      <li>Sellers may terminate by providing <strong>14 days’</strong> written notice. Pending obligations (orders, refunds, fees) must be fulfilled despite termination.</li>
    </ul>

    <h2 id="governing-law">1.11 Governing Law &amp; Disputes</h2>
    <p>
      These Terms are governed by the laws of Nigeria (unless another jurisdiction is agreed). Parties should seek mediation or arbitration for disputes in line with Nujora’s Dispute Resolution Policy before pursuing court action.
    </p>

    <h2 id="amendments">1.12 Amendments</h2>
    <p>
      Nujora may update these Terms with <strong>14 days’</strong> notice. Continued use of the platform after notice constitutes acceptance of the changes.
    </p>

    <h2 id="contact">1.13 Contact</h2>
    <p>
      For account help, policy questions, or support contact:<br>
      <strong>Email:</strong> <a href="mailto:support@nujora.ng">support@nujora.ng</a>
    </p>
  </div>

  <!-- Seller Onboarding Guide -->
  <div class="seller-onboarding-container">
    <h1>Seller Onboarding Guide — Nujora</h1>

    <h2>Overview</h2>
    <p>
      This step-by-step guide helps new Sellers register, list products, manage orders, and start selling on Nujora.
    </p>

    <h2>Step 1 — Sign-up &amp; Verification</h2>
    <ol>
      <li>Register with your email or phone number.</li>
      <li>Provide a business/shop name (formal or informal is acceptable).</li>
      <li>Complete identity verification (NIN, BVN, national ID, or other accepted ID).</li>
      <li>Accept the Terms of Service via the provided clickwrap checkbox.</li>
    </ol>

    <h2>Step 2 — Profile Setup</h2>
    <ol>
      <li>Enter contact details: address, phone number, and any required business info.</li>
      <li>Provide payout details (bank or mobile money) and preferred payout schedule.</li>
      <li>Optionally upload a shop logo or banner.</li>
    </ol>

    <h2>Step 3 — Listing Your First Product</h2>
    <ol>
      <li>Choose the appropriate category and subcategory.</li>
      <li>Create a clear title and informative description; include accurate attributes (size, colour, model).</li>
      <li>Upload good quality photos showing product from multiple angles.</li>
      <li>Set pricing, stock quantity, and shipping options.</li>
      <li>Review the listing against platform standards (no illegal content, no IP infringement).</li>
    </ol>

    <h2>Step 4 — Manage Orders &amp; Shipment</h2>
    <ol>
      <li>Sellers are notified when orders are placed. Confirm and process orders promptly.</li>
      <li>Local (Kano) target shipping: confirm shipment within <strong>24 hours</strong>. Outside Kano: ship within <strong>2–5 days</strong>.</li>
      <li>Mark shipments as dispatched in the Seller dashboard so Buyers receive tracking updates.</li>
    </ol>

    <h2>Step 5 — Deals, Returns &amp; Disputes</h2>
    <ol>
      <li>Run promotions or deals via the Seller dashboard where available.</li>
      <li>Handle returns according to your stated returns policy (minimum 7 days) and platform rules.</li>
      <li>If a Buyer escalates a dispute, respond within <strong>48 hours</strong> or Nujora will intervene.</li>
    </ol>

    <h2>Step 6 — Performance Monitoring</h2>
    <ol>
      <li>Use the dashboard to monitor orders, ratings, cancellations, and response times.</li>
      <li>Address warnings and improve metrics to avoid penalties or restrictions.</li>
    </ol>

    <h2>Step 7 — Getting Paid</h2>
    <ol>
      <li>Nujora calculates commissions and processes payouts as per the chosen schedule.</li>
      <li>View sales, deductions, and payout history in the Seller dashboard.</li>
    </ol>

    <h2>Step 8 — Support &amp; Growth</h2>
    <ol>
      <li>Access the Help Center, FAQs, and video resources for best practices.</li>
      <li>Participate in webinars or training to improve listing quality, SEO, and fulfilment processes.</li>
    </ol>

    <h2>Performance Terms &amp; Definitions</h2>
    <p>
      <strong>Performance metrics:</strong> order volume, average rating, cancellation rate, and response time — all visible on your dashboard. Nujora may apply corrective measures if performance thresholds are not met.
    </p>

    <h2>Final Notes</h2>
    <p>
      By completing onboarding and listing products you confirm you have read and accepted the Seller Terms of Service. For assistance during onboarding or to report issues, contact Seller Support at: <a href="mailto:support@nujora.ng">support@nujora.ng</a>
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