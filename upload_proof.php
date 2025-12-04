<?php
require 'app/config.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    if (!isset($_FILES['payment_proof'])) {
        echo json_encode(['status' => 'error', 'message' => 'No file uploaded']);
        exit;
    }

    $file = $_FILES['payment_proof'];

    if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception('Upload failed');
    if ($file['size'] > 5 * 1024 * 1024) throw new Exception('File too large');
    $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
    if (!in_array($file['type'], $allowed)) throw new Exception('Invalid file type');

    $dir = 'uploads/payment_proofs/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $unique = 'proof_' . uniqid() . '_' . time() . '.' . $ext;
    $path = $dir . $unique;

    if (!move_uploaded_file($file['tmp_name'], $path)) {
        throw new Exception('Failed to save file');
    }

    // Create transaction reference
    $txn_ref = 'TXN_' . strtoupper(uniqid()) . '_' . time();

    $stmt = $pdo->prepare("INSERT INTO proof_of_payment (transaction_reference, proof_path, created_at)
                           VALUES (?, ?, NOW())");
    $stmt->execute([$txn_ref, $path]);

    $_SESSION['payment_proof_reference'] = $txn_ref;

    echo json_encode([
        'status' => 'success',
        'message' => 'Proof uploaded successfully',
        'reference' => $txn_ref,
        'preview' => $path
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
