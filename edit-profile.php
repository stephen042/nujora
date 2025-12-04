<?php
require 'app/config.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

// Fetch states for the state dropdown
$stmt = $pdo->query("SELECT id, name FROM states ORDER BY name ASC");
$states = $stmt->fetchAll(PDO::FETCH_ASSOC);

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Fetch existing user data
$stmt = $pdo->prepare("SELECT name, email, country, phone, address, state_id, lga_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $countryCode = trim($_POST['country_code'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $state_id = (int)$_POST['state_id'] ?? null;
    $lga_id = (int)$_POST['lga_id'] ?? null;

    // Remove existing country code from phone
    $phone = str_replace($countryCode, '', $phone);

    // Concatenate country code + phone
    $fullPhone = $countryCode . $phone;

    if ($name && $country && $phone && $address) {
        $update = $pdo->prepare("UPDATE users SET name = ?, country = ?, phone = ?, state_id = ?, lga_id = ?, address = ? WHERE id = ?");
        if ($update->execute([$name, $country, $fullPhone, $state_id, $lga_id,  $address, $user_id])) {
            $success = "Profile updated successfully!";
            // Refresh user data
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        } else {
            $error = "Failed to update profile.";
        }
    } else {
        $error = "All fields are required.";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | <?= APP_NAME ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="uploads/default-product.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-4 mb-5">
        <h4>Edit Profile</h4>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($user['name'] ?? '') ?>">
            </div>

            <!-- Country Select with Phone Code -->
            <div class="mb-3">
                <label for="country" class="form-label">Country</label>
                <select id="country" name="country" class="form-select" onchange="updatePhoneCode(), updateState()" required>
                    <option value="" data-code="">-- Select Country --</option>
                    <option value="Afghanistan" data-code="+93" <?= ($user['country'] ?? '') === 'Afghanistan' ? 'selected' : '' ?>>Afghanistan (+93)</option>
                    <option value="Albania" data-code="+355" <?= ($user['country'] ?? '') === 'Albania' ? 'selected' : '' ?>>Albania (+355)</option>
                    <option value="Algeria" data-code="+213" <?= ($user['country'] ?? '') === 'Algeria' ? 'selected' : '' ?>>Algeria (+213)</option>
                    <option value="Andorra" data-code="+376" <?= ($user['country'] ?? '') === 'Andorra' ? 'selected' : '' ?>>Andorra (+376)</option>
                    <option value="Angola" data-code="+244" <?= ($user['country'] ?? '') === 'Angola' ? 'selected' : '' ?>>Angola (+244)</option>
                    <option value="Argentina" data-code="+54" <?= ($user['country'] ?? '') === 'Argentina' ? 'selected' : '' ?>>Argentina (+54)</option>
                    <option value="Armenia" data-code="+374" <?= ($user['country'] ?? '') === 'Armenia' ? 'selected' : '' ?>>Armenia (+374)</option>
                    <option value="Australia" data-code="+61" <?= ($user['country'] ?? '') === 'Australia' ? 'selected' : '' ?>>Australia (+61)</option>
                    <option value="Austria" data-code="+43" <?= ($user['country'] ?? '') === 'Austria' ? 'selected' : '' ?>>Austria (+43)</option>
                    <option value="Bangladesh" data-code="+880" <?= ($user['country'] ?? '') === 'Bangladesh' ? 'selected' : '' ?>>Bangladesh (+880)</option>
                    <option value="Belgium" data-code="+32" <?= ($user['country'] ?? '') === 'Belgium' ? 'selected' : '' ?>>Belgium (+32)</option>
                    <option value="Brazil" data-code="+55" <?= ($user['country'] ?? '') === 'Brazil' ? 'selected' : '' ?>>Brazil (+55)</option>
                    <option value="Canada" data-code="+1" <?= ($user['country'] ?? '') === 'Canada' ? 'selected' : '' ?>>Canada (+1)</option>
                    <option value="China" data-code="+86" <?= ($user['country'] ?? '') === 'China' ? 'selected' : '' ?>>China (+86)</option>
                    <option value="Denmark" data-code="+45" <?= ($user['country'] ?? '') === 'Denmark' ? 'selected' : '' ?>>Denmark (+45)</option>
                    <option value="Egypt" data-code="+20" <?= ($user['country'] ?? '') === 'Egypt' ? 'selected' : '' ?>>Egypt (+20)</option>
                    <option value="France" data-code="+33" <?= ($user['country'] ?? '') === 'France' ? 'selected' : '' ?>>France (+33)</option>
                    <option value="Germany" data-code="+49" <?= ($user['country'] ?? '') === 'Germany' ? 'selected' : '' ?>>Germany (+49)</option>
                    <option value="Ghana" data-code="+233" <?= ($user['country'] ?? '') === 'Ghana' ? 'selected' : '' ?>>Ghana (+233)</option>
                    <option value="India" data-code="+91" <?= ($user['country'] ?? '') === 'India' ? 'selected' : '' ?>>India (+91)</option>
                    <option value="Italy" data-code="+39" <?= ($user['country'] ?? '') === 'Italy' ? 'selected' : '' ?>>Italy (+39)</option>
                    <option value="Japan" data-code="+81" <?= ($user['country'] ?? '') === 'Japan' ? 'selected' : '' ?>>Japan (+81)</option>
                    <option value="Kenya" data-code="+254" <?= ($user['country'] ?? '') === 'Kenya' ? 'selected' : '' ?>>Kenya (+254)</option>
                    <option value="Mexico" data-code="+52" <?= ($user['country'] ?? '') === 'Mexico' ? 'selected' : '' ?>>Mexico (+52)</option>
                    <option value="Netherlands" data-code="+31" <?= ($user['country'] ?? '') === 'Netherlands' ? 'selected' : '' ?>>Netherlands (+31)</option>
                    <option value="Nigeria" data-code="+234" <?= ($user['country'] ?? '') === 'Nigeria' ? 'selected' : '' ?>>Nigeria (+234)</option>
                    <option value="Norway" data-code="+47" <?= ($user['country'] ?? '') === 'Norway' ? 'selected' : '' ?>>Norway (+47)</option>
                    <option value="Pakistan" data-code="+92" <?= ($user['country'] ?? '') === 'Pakistan' ? 'selected' : '' ?>>Pakistan (+92)</option>
                    <option value="Russia" data-code="+7" <?= ($user['country'] ?? '') === 'Russia' ? 'selected' : '' ?>>Russia (+7)</option>
                    <option value="Saudi Arabia" data-code="+966" <?= ($user['country'] ?? '') === 'Saudi Arabia' ? 'selected' : '' ?>>Saudi Arabia (+966)</option>
                    <option value="South Africa" data-code="+27" <?= ($user['country'] ?? '') === 'South Africa' ? 'selected' : '' ?>>South Africa (+27)</option>
                    <option value="Spain" data-code="+34" <?= ($user['country'] ?? '') === 'Spain' ? 'selected' : '' ?>>Spain (+34)</option>
                    <option value="Sweden" data-code="+46" <?= ($user['country'] ?? '') === 'Sweden' ? 'selected' : '' ?>>Sweden (+46)</option>
                    <option value="Turkey" data-code="+90" <?= ($user['country'] ?? '') === 'Turkey' ? 'selected' : '' ?>>Turkey (+90)</option>
                    <option value="Uganda" data-code="+256" <?= ($user['country'] ?? '') === 'Uganda' ? 'selected' : '' ?>>Uganda (+256)</option>
                    <option value="United Arab Emirates" data-code="+971" <?= ($user['country'] ?? '') === 'United Arab Emirates' ? 'selected' : '' ?>>United Arab Emirates (+971)</option>
                    <option value="United Kingdom" data-code="+44" <?= ($user['country'] ?? '') === 'United Kingdom' ? 'selected' : '' ?>>United Kingdom (+44)</option>
                    <option value="United States" data-code="+1" <?= ($user['country'] ?? '') === 'United States' ? 'selected' : '' ?>>United States (+1)</option>
                    <option value="Zimbabwe" data-code="+263" <?= ($user['country'] ?? '') === 'Zimbabwe' ? 'selected' : '' ?>>Zimbabwe (+263)</option>
                </select>
            </div>


            <!-- Phone Input -->
            <div class="mb-3">
                <label for="phone" class="form-label">Phone Number (shows with country code )</label>
                <div class="input-group">
                    <span id="phoneCode" class="input-group-text bg-light border">+000</span>
                    <input type="text" id="phone" name="phone" class="form-control"
                        placeholder="Enter phone number"
                        value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>
                <input type="hidden" name="country_code" id="country_code" value="">
            </div>

            <div class="mb-3 toggle-address">
                <label>State * (Nigeria Only)</label>
                <select class="form-control" id="state_select" name="state_id" required>
                    <option value="">Select State</option>
                    <?php if ($user['state_id']): ?>
                        <option value="<?= $user['state_id'] ?>" selected><?= htmlspecialchars($states[array_search($user['state_id'], array_column($states, 'id'))]['name'] ?? '') ?>
                        <?php endif; ?>
                        <?php foreach ($states as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3 toggle-address">
                <label>LGA * (Nigeria Only)</label>
                <select class="form-control" id="lga_select" name="lga_id" required>
                    <option value="">Select LGA</option>
                    <?php
                    if ($user['lga_id']):
                        $stmt = $pdo->prepare("SELECT name FROM local_governments WHERE id = ?");
                        $stmt->execute([$user['lga_id']]);
                        $userLga = $stmt->fetch();
                    ?>
                        <option value="<?= $user['lga_id'] ?>" selected><?= htmlspecialchars($userLga['name']) ?>
                        <?php endif; ?></option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Address (can be used as shipping address)</label>
                <textarea name="address" class="form-control" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
            </div>

            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary w-25">Save Changes</button>
                <a href="profile.php" class="btn btn-secondary w-25">Back</a>
            </div>
        </form>
    </div>
    <script>
        function updatePhoneCode() {
            let select = document.getElementById("country");
            let code = select.options[select.selectedIndex].getAttribute("data-code");
            document.getElementById("phoneCode").textContent = code ? code : "+000";
            document.getElementById("country_code").value = code ? code : "";
        }

        // Run once when page loads to sync country + phoneCode
        window.onload = updatePhoneCode;

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
            fetch("get_lgas.php?state_id=" + this.value)
                .then(r => r.text())
                .then(data => document.getElementById("lga_select").innerHTML = data);
        });
    </script>

</body>

</html>