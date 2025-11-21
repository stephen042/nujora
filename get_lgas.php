<?php
require 'app/config.php';

if (!isset($_GET['state_id'])) {
    echo "<option value=''>Select LGA</option>";
    exit;
}

$state_id = intval($_GET['state_id']);

$stmt = $pdo->prepare("SELECT id, name FROM local_governments WHERE state_id = ? ORDER BY name ASC");
$stmt->execute([$state_id]);
$lgas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$lgas) {
    echo "<option value=''>No LGAs found</option>";
    exit;
}

foreach ($lgas as $lga) {
    echo "<option value='{$lga['id']}'>{$lga['name']}</option>";
}
