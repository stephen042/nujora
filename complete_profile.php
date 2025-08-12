<?php
require 'db.php';
session_start();

// Check if user is logged in and is a buyer with approved but incomplete profile
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller' || $_SESSION['is_approved'] != 1 || $_SESSION['profile_complete'] == 1) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $streetAddress = trim($_POST['street_address'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $lga = trim($_POST['lga'] ?? '');
    $landmark = trim($_POST['landmark'] ?? '');
    $referralCode = trim($_POST['referral_code'] ?? '');
    $storeName = trim($_POST['store_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Handle file upload for logo
    $logoPath = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/logos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['logo']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
            $logoPath = $targetPath;
        }
    }
    
    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($phone) || empty($streetAddress) || empty($state) || empty($lga)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            // Update user profile information
            $stmt = $pdo->prepare("
                UPDATE users SET 
                    title = ?,
                    first_name = ?,
                    last_name = ?,
                    phone = ?,
                    street_address = ?,
                    state = ?,
                    lga = ?,
                    landmark = ?,
                    referral_code = ?,
                    store_name = ?,
                    description = ?,
                    logo = ?,
                    profile_complete = 1
                WHERE id = ?
            ");
            $stmt->execute([
                $title,
                $firstName,
                $lastName,
                $phone,
                $streetAddress,
                $state,
                $lga,
                $landmark,
                $referralCode,
                $storeName,
                $description,
                $logoPath,
                $_SESSION['user_id']
            ]);
            
            $_SESSION['profile_complete'] = 1;
            $success = "Profile updated successfully!";
            header('Location: profile.php'); // Redirect to profile page
            exit;
        } catch (PDOException $e) {
            $error = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Fetch states for dropdown (you would typically get these from a database)
$states = [
    'Lagos', 'Abuja', 'Kano', 'Ogun', 'Oyo', 'Rivers', 'Delta', 'Enugu', 
    'Kaduna', 'Katsina', 'Anambra', 'Bauchi', 'Benue', 'Borno', 'Cross River',
    'Ebonyi', 'Edo', 'Ekiti', 'Gombe', 'Imo', 'Jigawa', 'Nasarawa', 'Niger',
    'Plateau', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara', 'Adamawa', 'Bayelsa',
    'Kebbi', 'Kwara', 'Osun', 'Akwa Ibom', 'Ondo'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Complete Your Profile | TrendyMart</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .profile-container {
      max-width: 800px;
      margin: 30px auto;
      padding: 20px;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    .form-section {
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 1px solid #eee;
    }
    .section-title {
      color: #6a11cb;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="profile-container">
      <h2 class="text-center mb-4">Complete Your Profile</h2>
      
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      
      <form method="POST" action="complete_profile.php" enctype="multipart/form-data">
        <div class="form-section">
          <h4 class="section-title">Store Information</h4>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="store_name" class="form-label">Store Name</label>
              <input type="text" class="form-control" id="store_name" name="store_name">
            </div>
            <div class="col-md-6 mb-3">
              <label for="description" class="form-label">Description</label>
              <textarea class="form-control" id="description" name="description" rows="2"></textarea>
            </div>
          </div>
          <div class="mb-3">
            <label for="logo" class="form-label">Store Logo (Optional)</label>
            <input class="form-control" type="file" id="logo" name="logo" accept="image/*">
          </div>
        </div>
        
        <div class="form-section">
          <h4 class="section-title">Personal Information</h4>
          <div class="row">
            <div class="col-md-3 mb-3">
              <label for="title" class="form-label">Title</label>
              <select class="form-select" id="title" name="title">
                <option value="">Select</option>
                <option value="Mr">Mr</option>
                <option value="Mrs">Mrs</option>
                <option value="Miss">Miss</option>
                <option value="Dr">Dr</option>
                <option value="Prof">Prof</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="first_name" class="form-label">First Name *</label>
              <input type="text" class="form-control" id="first_name" name="first_name" required>
            </div>
            <div class="col-md-5 mb-3">
              <label for="last_name" class="form-label">Last Name *</label>
              <input type="text" class="form-control" id="last_name" name="last_name" required>
            </div>
          </div>
          <div class="mb-3">
            <label for="phone" class="form-label">Phone Number *</label>
            <input type="tel" class="form-control" id="phone" name="phone" required>
          </div>
        </div>
        
        <div class="form-section">
          <h4 class="section-title">Address Information</h4>
          <div class="mb-3">
            <label for="street_address" class="form-label">Street Address *</label>
            <input type="text" class="form-control" id="street_address" name="street_address" required>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="state" class="form-label">State *</label>
              <select class="form-select" id="state" name="state" required>
                <option value="">Select State</option>
                <?php foreach ($states as $state): ?>
                  <option value="<?= htmlspecialchars($state) ?>"><?= htmlspecialchars($state) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="lga" class="form-label">LGA *</label>
              <select class="form-select" id="lga" name="lga" required>
                <option value="">Select State first</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label for="landmark" class="form-label">Nearest Landmark</label>
            <input type="text" class="form-control" id="landmark" name="landmark">
          </div>
        </div>
        
        <div class="form-section">
          <h4 class="section-title">Referral Information</h4>
          <div class="mb-3">
            <label for="referral_code" class="form-label">Referral Code (Optional)</label>
            <input type="text" class="form-control" id="referral_code" name="referral_code">
          </div>
        </div>
        
        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-primary btn-lg">Complete Profile</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // This would be a simplified version - in a real app you'd want to fetch LGAs based on state from your database
    const stateLgaMap = {
      'Lagos': ['Agege', 'Ajeromi-Ifelodun', 'Alimosho', 'Amuwo-Odofin', 'Apapa', 'Badagry', 'Epe', 'Eti-Osa', 'Ibeju-Lekki', 'Ifako-Ijaiye', 'Ikeja', 'Ikorodu', 'Kosofe', 'Lagos Island', 'Lagos Mainland', 'Mushin', 'Ojo', 'Oshodi-Isolo', 'Shomolu', 'Surulere'],
      'Abuja': ['Abaji', 'Bwari', 'Gwagwalada', 'Kuje', 'Kwali', 'Municipal Area Council'],
      // Add more states and their LGAs as needed
    };
    
    document.getElementById('state').addEventListener('change', function() {
      const state = this.value;
      const lgaSelect = document.getElementById('lga');
      
      lgaSelect.innerHTML = '<option value="">Select LGA</option>';
      
      if (state && stateLgaMap[state]) {
        stateLgaMap[state].forEach(lga => {
          const option = document.createElement('option');
          option.value = lga;
          option.textContent = lga;
          lgaSelect.appendChild(option);
        });
      }
    });
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>