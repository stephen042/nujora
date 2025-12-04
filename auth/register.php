<?php
require '../app/config.php';
require_once '../lib/mail_functions.php'; // Include mail functions

// Fetch states for the state dropdown
$stmt = $pdo->query("SELECT id, name FROM states ORDER BY name ASC");
$states = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name        = trim($_POST['name'] ?? '');
  $email       = trim($_POST['email'] ?? '');
  $country     = trim($_POST['country'] ?? '');
  $phone       = trim($_POST['phone'] ?? '');
  $state_id   = trim($_POST['state_id'] ?? '');
  $lga_id    = trim($_POST['lga_id'] ?? '');
  $countryCode = trim($_POST['country_code'] ?? '');
  $password    = trim($_POST['password'] ?? '');
  $role        = trim($_POST['role'] ?? 'buyer');
  $shop_name   = trim($_POST['shop_name'] ?? '');

  $fullPhone = $countryCode . $phone;

  if (empty($name) || empty($email) || empty($password) || empty($country) || empty($phone)) {
    $error = "All fields are required.";
  } elseif ($role === 'seller' && empty($shop_name)) {
    $error = "Shop name is required for sellers.";
  } else {
    // Check for duplicate email
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $emailExists = $stmt->fetchColumn();

    if ($emailExists) {
      $error = "Email already exists.";
    } else {
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);

      // Buyer auto-approved, Seller pending
      $is_approved     = ($role === 'buyer') ? 1 : 0;
      $approval_status = ($role === 'buyer') ? 'approved' : 'pending';

      try {
        $stmt = $pdo->prepare("
                    INSERT INTO users (
                        name, email, password_hash, role, shop_name, 
                        badge_level, rating, created_at, is_approved, approval_status, profile_complete,
                        country, phone, state_id, lga_id
                    ) VALUES (
                        ?, ?, ?, ?, ?, 
                        'New', 0.00, NOW(), ?, ?, 0,
                        ?, ?, ?, ?
                    )
                ");
        $stmt->execute([
          $name,
          $email,
          $hashed_password,
          $role,
          $shop_name,
          $is_approved,
          $approval_status,
          $country,
          $fullPhone,
          $state_id,
          $lga_id
        ]);

        $userId = $pdo->lastInsertId();
        $_SESSION['temp_user_id']   = $userId;
        $_SESSION['temp_user_role'] = $role;

        // 🎉 Send Email Based on Role
        $site_url = APP_URL;
        $site_name = APP_NAME;
        if ($role === 'seller') {
          send_mail(
            $email,
            "Thank You for Registering as a Seller",
            "Welcome, {$name}!",
            "Thank you for signing up as a seller on our marketplace! Your account is currently under review. Our team will verify your details and notify you once approved.",
            "Visit Dashboard",
            "$site_url"
          );
        } else {
          send_mail(
            $email,
            "Welcome to $site_name",
            "Welcome, {$name}!",
            "Welcome to $site_name we’re excited to have you join our growing community! 🚀
            You’ve just unlocked a world where discovering amazing products is effortless and connecting with trusted, verified sellers is as smooth as it gets. From everyday essentials to unique finds, $site_name brings the best of online shopping directly to your fingertips.
            Here’s what’s waiting for you:
            ✨ Amazing Products across multiple categories
            🤝 Verified Sellers you can trust
            ⚡ Fast, smooth shopping experience
            💬 Direct communication with sellers
            🛍️ A marketplace built for YOU
            You're officially part of something big and this is only the beginning.
            Go ahead, explore your dashboard and start discovering items that match your style, your needs, and your vibe.
            If you ever need help, our support team is always here for you.
            Welcome to the future of online shopping.
            Welcome to $site_name. 💛",
            "Start exploring now!",
            "$site_url"
          );
        }

        $success = "User registered successfully. A confirmation email has been sent.";
      } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register | Nujora</title>
  <link rel="icon" type="image/png" href="../uploads/default-product.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    body {
      background: linear-gradient(to right, #6a11cb, #2575fc);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 40px;
    }

    .register-box {
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      max-width: 400px;
      width: 100%;
    }

    .register-box h4 {
      margin-bottom: 20px;
      font-weight: bold;
      color: #333;
    }

    .form-control:focus {
      box-shadow: 0 0 5px rgba(38, 143, 255, 0.5);
      border-color: #268fff;
    }

    .btn-primary {
      background: #6a11cb;
      border: none;
    }

    .btn-primary:hover {
      background: #2575fc;
    }
  </style>
</head>

<body>
  <div class="register-box">
    <h4 class="text-center">Create an Account</h4>
    <!-- Centered Logo -->
    <div class="text-center">
      <?php include '../app/logo.php'; ?>
    </div>
    <hr>
    <!-- Display success or error message -->
    <?php if (!empty($success)): ?>
      <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
      <?php if ($role === 'seller'): ?>
        <div class="alert alert-info text-center">
          Before you can start selling, we will need to verify your details. Our team will review your application and notify you once your account is approved.
        </div>
      <?php endif; ?>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php">
      <div class="mb-3">
        <label for="name" class="form-label">Full Name</label>
        <input type="text" name="name" id="name" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" name="email" id="email" class="form-control" required>
      </div>

      <!-- Country Select with Phone Code -->
      <div class="mb-3">
        <label for="country" class="form-label">Country</label>
        <select id="country" name="country" class="form-select" onchange="updatePhoneCode(),updateState()" required>
          <option value="" data-code="">-- Select Country --</option>
          <option value="Afghanistan" data-code="+93">Afghanistan (+93)</option>
          <option value="Albania" data-code="+355">Albania (+355)</option>
          <option value="Algeria" data-code="+213">Algeria (+213)</option>
          <option value="Andorra" data-code="+376">Andorra (+376)</option>
          <option value="Angola" data-code="+244">Angola (+244)</option>
          <option value="Argentina" data-code="+54">Argentina (+54)</option>
          <option value="Armenia" data-code="+374">Armenia (+374)</option>
          <option value="Australia" data-code="+61">Australia (+61)</option>
          <option value="Austria" data-code="+43">Austria (+43)</option>
          <option value="Azerbaijan" data-code="+994">Azerbaijan (+994)</option>
          <option value="Bahamas" data-code="+1-242">Bahamas (+1-242)</option>
          <option value="Bahrain" data-code="+973">Bahrain (+973)</option>
          <option value="Bangladesh" data-code="+880">Bangladesh (+880)</option>
          <option value="Barbados" data-code="+1-246">Barbados (+1-246)</option>
          <option value="Belarus" data-code="+375">Belarus (+375)</option>
          <option value="Belgium" data-code="+32">Belgium (+32)</option>
          <option value="Belize" data-code="+501">Belize (+501)</option>
          <option value="Benin" data-code="+229">Benin (+229)</option>
          <option value="Bhutan" data-code="+975">Bhutan (+975)</option>
          <option value="Bolivia" data-code="+591">Bolivia (+591)</option>
          <option value="Bosnia and Herzegovina" data-code="+387">Bosnia and Herzegovina (+387)</option>
          <option value="Botswana" data-code="+267">Botswana (+267)</option>
          <option value="Brazil" data-code="+55">Brazil (+55)</option>
          <option value="Brunei" data-code="+673">Brunei (+673)</option>
          <option value="Bulgaria" data-code="+359">Bulgaria (+359)</option>
          <option value="Burkina Faso" data-code="+226">Burkina Faso (+226)</option>
          <option value="Burundi" data-code="+257">Burundi (+257)</option>
          <option value="Cambodia" data-code="+855">Cambodia (+855)</option>
          <option value="Cameroon" data-code="+237">Cameroon (+237)</option>
          <option value="Canada" data-code="+1">Canada (+1)</option>
          <option value="Cape Verde" data-code="+238">Cape Verde (+238)</option>
          <option value="Chad" data-code="+235">Chad (+235)</option>
          <option value="Chile" data-code="+56">Chile (+56)</option>
          <option value="China" data-code="+86">China (+86)</option>
          <option value="Colombia" data-code="+57">Colombia (+57)</option>
          <option value="Congo" data-code="+242">Congo (+242)</option>
          <option value="Costa Rica" data-code="+506">Costa Rica (+506)</option>
          <option value="Croatia" data-code="+385">Croatia (+385)</option>
          <option value="Cuba" data-code="+53">Cuba (+53)</option>
          <option value="Cyprus" data-code="+357">Cyprus (+357)</option>
          <option value="Czech Republic" data-code="+420">Czech Republic (+420)</option>
          <option value="Denmark" data-code="+45">Denmark (+45)</option>
          <option value="Djibouti" data-code="+253">Djibouti (+253)</option>
          <option value="Dominican Republic" data-code="+1-809">Dominican Republic (+1-809)</option>
          <option value="Ecuador" data-code="+593">Ecuador (+593)</option>
          <option value="Egypt" data-code="+20">Egypt (+20)</option>
          <option value="El Salvador" data-code="+503">El Salvador (+503)</option>
          <option value="Estonia" data-code="+372">Estonia (+372)</option>
          <option value="Ethiopia" data-code="+251">Ethiopia (+251)</option>
          <option value="Fiji" data-code="+679">Fiji (+679)</option>
          <option value="Finland" data-code="+358">Finland (+358)</option>
          <option value="France" data-code="+33">France (+33)</option>
          <option value="Gabon" data-code="+241">Gabon (+241)</option>
          <option value="Gambia" data-code="+220">Gambia (+220)</option>
          <option value="Georgia" data-code="+995">Georgia (+995)</option>
          <option value="Germany" data-code="+49">Germany (+49)</option>
          <option value="Ghana" data-code="+233">Ghana (+233)</option>
          <option value="Greece" data-code="+30">Greece (+30)</option>
          <option value="Greenland" data-code="+299">Greenland (+299)</option>
          <option value="Guatemala" data-code="+502">Guatemala (+502)</option>
          <option value="Guinea" data-code="+224">Guinea (+224)</option>
          <option value="Guyana" data-code="+592">Guyana (+592)</option>
          <option value="Haiti" data-code="+509">Haiti (+509)</option>
          <option value="Honduras" data-code="+504">Honduras (+504)</option>
          <option value="Hong Kong" data-code="+852">Hong Kong (+852)</option>
          <option value="Hungary" data-code="+36">Hungary (+36)</option>
          <option value="Iceland" data-code="+354">Iceland (+354)</option>
          <option value="India" data-code="+91">India (+91)</option>
          <option value="Indonesia" data-code="+62">Indonesia (+62)</option>
          <option value="Iran" data-code="+98">Iran (+98)</option>
          <option value="Iraq" data-code="+964">Iraq (+964)</option>
          <option value="Ireland" data-code="+353">Ireland (+353)</option>
          <option value="Israel" data-code="+972">Israel (+972)</option>
          <option value="Italy" data-code="+39">Italy (+39)</option>
          <option value="Jamaica" data-code="+1-876">Jamaica (+1-876)</option>
          <option value="Japan" data-code="+81">Japan (+81)</option>
          <option value="Jordan" data-code="+962">Jordan (+962)</option>
          <option value="Kazakhstan" data-code="+7">Kazakhstan (+7)</option>
          <option value="Kenya" data-code="+254">Kenya (+254)</option>
          <option value="Kuwait" data-code="+965">Kuwait (+965)</option>
          <option value="Kyrgyzstan" data-code="+996">Kyrgyzstan (+996)</option>
          <option value="Latvia" data-code="+371">Latvia (+371)</option>
          <option value="Lebanon" data-code="+961">Lebanon (+961)</option>
          <option value="Lesotho" data-code="+266">Lesotho (+266)</option>
          <option value="Liberia" data-code="+231">Liberia (+231)</option>
          <option value="Libya" data-code="+218">Libya (+218)</option>
          <option value="Lithuania" data-code="+370">Lithuania (+370)</option>
          <option value="Luxembourg" data-code="+352">Luxembourg (+352)</option>
          <option value="Madagascar" data-code="+261">Madagascar (+261)</option>
          <option value="Malawi" data-code="+265">Malawi (+265)</option>
          <option value="Malaysia" data-code="+60">Malaysia (+60)</option>
          <option value="Maldives" data-code="+960">Maldives (+960)</option>
          <option value="Mali" data-code="+223">Mali (+223)</option>
          <option value="Malta" data-code="+356">Malta (+356)</option>
          <option value="Mauritania" data-code="+222">Mauritania (+222)</option>
          <option value="Mauritius" data-code="+230">Mauritius (+230)</option>
          <option value="Mexico" data-code="+52">Mexico (+52)</option>
          <option value="Moldova" data-code="+373">Moldova (+373)</option>
          <option value="Monaco" data-code="+377">Monaco (+377)</option>
          <option value="Mongolia" data-code="+976">Mongolia (+976)</option>
          <option value="Montenegro" data-code="+382">Montenegro (+382)</option>
          <option value="Morocco" data-code="+212">Morocco (+212)</option>
          <option value="Mozambique" data-code="+258">Mozambique (+258)</option>
          <option value="Myanmar" data-code="+95">Myanmar (+95)</option>
          <option value="Namibia" data-code="+264">Namibia (+264)</option>
          <option value="Nepal" data-code="+977">Nepal (+977)</option>
          <option value="Netherlands" data-code="+31">Netherlands (+31)</option>
          <option value="New Zealand" data-code="+64">New Zealand (+64)</option>
          <option value="Nicaragua" data-code="+505">Nicaragua (+505)</option>
          <option value="Niger" data-code="+227">Niger (+227)</option>
          <option value="Nigeria" data-code="+234">Nigeria (+234)</option>
          <option value="North Korea" data-code="+850">North Korea (+850)</option>
          <option value="Norway" data-code="+47">Norway (+47)</option>
          <option value="Oman" data-code="+968">Oman (+968)</option>
          <option value="Pakistan" data-code="+92">Pakistan (+92)</option>
          <option value="Panama" data-code="+507">Panama (+507)</option>
          <option value="Paraguay" data-code="+595">Paraguay (+595)</option>
          <option value="Peru" data-code="+51">Peru (+51)</option>
          <option value="Philippines" data-code="+63">Philippines (+63)</option>
          <option value="Poland" data-code="+48">Poland (+48)</option>
          <option value="Portugal" data-code="+351">Portugal (+351)</option>
          <option value="Qatar" data-code="+974">Qatar (+974)</option>
          <option value="Romania" data-code="+40">Romania (+40)</option>
          <option value="Russia" data-code="+7">Russia (+7)</option>
          <option value="Rwanda" data-code="+250">Rwanda (+250)</option>
          <option value="Saudi Arabia" data-code="+966">Saudi Arabia (+966)</option>
          <option value="Senegal" data-code="+221">Senegal (+221)</option>
          <option value="Serbia" data-code="+381">Serbia (+381)</option>
          <option value="Seychelles" data-code="+248">Seychelles (+248)</option>
          <option value="Sierra Leone" data-code="+232">Sierra Leone (+232)</option>
          <option value="Singapore" data-code="+65">Singapore (+65)</option>
          <option value="Slovakia" data-code="+421">Slovakia (+421)</option>
          <option value="Slovenia" data-code="+386">Slovenia (+386)</option>
          <option value="Somalia" data-code="+252">Somalia (+252)</option>
          <option value="South Africa" data-code="+27">South Africa (+27)</option>
          <option value="South Korea" data-code="+82">South Korea (+82)</option>
          <option value="Spain" data-code="+34">Spain (+34)</option>
          <option value="Sri Lanka" data-code="+94">Sri Lanka (+94)</option>
          <option value="Sudan" data-code="+249">Sudan (+249)</option>
          <option value="Sweden" data-code="+46">Sweden (+46)</option>
          <option value="Switzerland" data-code="+41">Switzerland (+41)</option>
          <option value="Syria" data-code="+963">Syria (+963)</option>
          <option value="Taiwan" data-code="+886">Taiwan (+886)</option>
          <option value="Tanzania" data-code="+255">Tanzania (+255)</option>
          <option value="Thailand" data-code="+66">Thailand (+66)</option>
          <option value="Togo" data-code="+228">Togo (+228)</option>
          <option value="Trinidad and Tobago" data-code="+1-868">Trinidad and Tobago (+1-868)</option>
          <option value="Tunisia" data-code="+216">Tunisia (+216)</option>
          <option value="Turkey" data-code="+90">Turkey (+90)</option>
          <option value="Uganda" data-code="+256">Uganda (+256)</option>
          <option value="Ukraine" data-code="+380">Ukraine (+380)</option>
          <option value="United Arab Emirates" data-code="+971">United Arab Emirates (+971)</option>
          <option value="United Kingdom" data-code="+44">United Kingdom (+44)</option>
          <option value="United States" data-code="+1">United States (+1)</option>
          <option value="Uruguay" data-code="+598">Uruguay (+598)</option>
          <option value="Uzbekistan" data-code="+998">Uzbekistan (+998)</option>
          <option value="Venezuela" data-code="+58">Venezuela (+58)</option>
          <option value="Vietnam" data-code="+84">Vietnam (+84)</option>
          <option value="Yemen" data-code="+967">Yemen (+967)</option>
          <option value="Zambia" data-code="+260">Zambia (+260)</option>
          <option value="Zimbabwe" data-code="+263">Zimbabwe (+263)</option>
        </select>
      </div>

      <!-- Phone Input -->
      <div class="mb-3">
        <label for="phone" class="form-label">Phone Number</label>
        <div class="input-group">
          <span id="phoneCode" class="input-group-text bg-light border">+000</span>
          <input type="number" id="phone" name="phone" class="form-control" placeholder="Enter phone number">
        </div>
        <!-- Hidden input to capture the code -->
        <input type="hidden" name="country_code" id="country_code" value="">
      </div>

      <div class="mb-3 toggle-address d-none">
        <label>State *</label>
        <select class="form-control" id="state_select" name="state_id" required>
          <option value="">Select State</option>
          <?php foreach ($states as $s): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3 toggle-address d-none">
        <label>LGA *</label>
        <select class="form-control" id="lga_select" name="lga_id" required>
          <option value="">Select LGA</option>
        </select>
      </div>

      <div class="mb-3 position-relative">
        <label for="password" class="form-label">Password</label>
        <div class="input-group">
          <input type="password" name="password" id="password" class="form-control" required>
          <span class="input-group-text" onclick="togglePassword()" style="cursor: pointer;">
            <!-- Eye (open) -->
            <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
              viewBox="0 0 24 24" role="img" aria-labelledby="eyeTitle">
              <title id="eyeTitle">Show password</title>
              <g fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
                <circle cx="12" cy="12" r="3" />
              </g>
            </svg>
            <!-- Eye with slash (closed) -->
            <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
              viewBox="0 0 24 24" role="img" aria-labelledby="eyeSlashTitle" style="display:none;">
              <title id="eyeSlashTitle">Hide password</title>
              <g fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-7 11-7c2.1 0 4 .5 5.7 1.4" />
                <path d="M21 12s-4 7-11 7c-2.1 0-4-.5-5.7-1.4" />
                <circle cx="12" cy="12" r="3" />
                <path d="M2 2l20 20" />
              </g>
            </svg>
          </span>
        </div>
      </div>

      <div class="mb-3">
        <label for="role" class="form-label">Register As</label>
        <select id="role" name="role" class="form-select" onchange="toggleShopNameField()" required>
          <option value="buyer" selected>Buyer</option>
          <option value="seller">Seller</option>
        </select>
      </div>
      <!-- Shop Name Field (Hidden by Default) -->
      <div class="mb-3" id="shop-name-field" style="display: none;">
        <label for="shop_name" class="form-label">Shop Name</label>
        <input type="text" name="shop_name" id="shop_name" class="form-control" placeholder="Enter your shop name">
      </div>
      <button type="submit" class="btn btn-primary w-100">Register</button>
    </form>
    <p class="text-center mt-3">
      Already have an account? <a href="login.php" class="text-primary">Login here</a>
    </p>
  </div>

  <script>
    // JavaScript to toggle the Shop Name field
    function toggleShopNameField() {
      const roleSelect = document.getElementById('role');
      const shopNameField = document.getElementById('shop-name-field');
      if (roleSelect.value === 'seller') {
        shopNameField.style.display = 'block';
      } else {
        shopNameField.style.display = 'none';
      }
    }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    function updatePhoneCode() {
      let select = document.getElementById("country");
      let code = select.options[select.selectedIndex].getAttribute("data-code");
      document.getElementById("phoneCode").textContent = code ? code : "+000";
      document.getElementById("country_code").value = code ? code : "";
    }

    function updateState() {
      const countrySelect = document.getElementById("country");
      const toggleAddressDivs = document.querySelectorAll(".toggle-address");

      if (countrySelect.value === "Nigeria") {
        toggleAddressDivs.forEach(div => div.classList.remove("d-none"));
      } else {
        toggleAddressDivs.forEach(div => div.classList.add("d-none"));
      }
    }
  </script>
  <script>
    document.getElementById("state_select").addEventListener("change", function() {
      fetch("../get_lgas.php?state_id=" + this.value)
        .then(r => r.text())
        .then(data => document.getElementById("lga_select").innerHTML = data);
    });
  </script>

  <script>
    function togglePassword() {
      const passwordInput = document.getElementById("password");
      const eyeOpen = document.getElementById("eyeOpen");
      const eyeClosed = document.getElementById("eyeClosed");

      if (passwordInput.type === "password") {
        passwordInput.type = "text"; // Show password
        eyeOpen.style.display = "none"; // Hide open eye
        eyeClosed.style.display = "inline"; // Show closed eye
      } else {
        passwordInput.type = "password";
        eyeOpen.style.display = "inline"; // Show open eye
        eyeClosed.style.display = "none"; // Hide closed eye
      }
    }
  </script>

</body>

</html>