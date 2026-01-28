<style>
  .footer-branding h5 {
    letter-spacing: 1px;
    border-left: 3px solid #ffc107;
    /* Bootstrap Warning Yellow */
    padding-left: 15px;
  }

  .footer-branding p {
    font-size: 0.95rem;
    margin-top: 1rem;
  }

  .footer-divider {
    width: 50px;
    height: 2px;
    background: rgba(255, 255, 255, 0.2);
    margin-top: 15px;
  }
</style>
<footer class="bg-dark text-white py-5">
  <div class="container">
    <div class="row">
      <div class="col-md-4 mb-4 footer-branding">
        <h5 class="fw-bold text-uppercase mb-3">
          <?= APP_NAME ?>
          <span class="d-block mt-1 fw-light fs-6 text-warning">
            Powered By Ramor Logistics and Tech Services LTD
          </span>
        </h5>
        <p class="text-white-50 lh-base">
          Your one-stop shop for all your needs. <br>
          <span class="fst-italic">Quality products at affordable prices.</span>
        </p>
        <div class="footer-divider"></div>
      </div>
      <div class="col-md-2 mb-4">
        <h5>Shop</h5>
        <ul class="list-unstyled">
          <!-- <li><a href="#" class="text-white-50">Categories</a></li> -->
          <li><a href="#" class="text-white-50">Deals</a></li>
          <li><a href="#" class="text-white-50">New Arrivals</a></li>
        </ul>
      </div>
      <div class="col-md-2 mb-4">
        <h5>Help</h5>
        <ul class="list-unstyled">
          <li><a href="faqs.php" class="text-white-50">FAQs</a></li>
          <!-- <li><a href="/shipping.php" class="text-white-50">Shipping</a></li> -->
          <li><a href="/returns.php" class="text-white-50">Returns</a></li>
          <li><a href="/contact-us.php" class="text-white-50">Contact us</a></li>
          <li><a href="/dispute-resolution-policy.php" class="text-white-50">Dispute resolution policy</a></li>
          <li><a href="/terms-of-service.php" class="text-white-50">Terms of service</a></li>
          <li><a href="/privacy-policy.php" class="text-white-50">Privacy policy</a></li>
          <li><a href="/cookie-policy.php" class="text-white-50">Cookie policy</a></li>
          <li><a href="/selling-on-nujora.php" class="text-white-50">Selling on Nujora</a></li>
          <!-- <li><a href="/buying-on-nujora.php" class="text-white-50">Buying on Nujora</a></li> -->
        </ul>
      </div>
      <div class="col-md-4 mb-4">
        <h5>Newsletter</h5>
        <p class="text-white-50">Subscribe for updates and special offers</p>
        <div class="input-group">
          <input type="email" class="form-control" placeholder="Your email">
          <button class="btn btn-primary" type="button">Subscribe</button>
        </div>
      </div>
    </div>
    <hr class="my-4 bg-secondary">
    <div class="row">
      <div class="col-md-6">
        <p class="mb-0 text-white">&copy; 2023 <?= APP_NAME ?>. All rights reserved.</p>
      </div>
      <div class="col-md-6 text-md-end">
        <a href="https://www.facebook.com/Nujora/" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
      </div>
    </div>
  </div>
</footer>