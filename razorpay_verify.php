<?php
// ─── RAZORPAY CONFIG — must match payment.php ─────────────────────
define('RAZORPAY_KEY_ID',     'rzp_test_XXXXXXXXXXXXXXXX');
define('RAZORPAY_KEY_SECRET', 'XXXXXXXXXXXXXXXXXXXXXXXX');
// ──────────────────────────────────────────────────────────────────

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$order_id   = $input['razorpay_order_id']   ?? '';
$payment_id = $input['razorpay_payment_id'] ?? '';
$signature  = $input['razorpay_signature']  ?? '';
$player_id  = intval($input['player_id']    ?? 0);
$amount     = floatval($input['amount']     ?? 0);
$note       = trim($input['note']           ?? '');

// ── 1. Verify signature ───────────────────────────────────────────
$expected = hash_hmac('sha256', $order_id . '|' . $payment_id, RAZORPAY_KEY_SECRET);

if (!hash_equals($expected, $signature)) {
    echo json_encode(['success' => false, 'error' => 'Signature verification failed']);
    exit;
}

// ── 2. Save to database ───────────────────────────────────────────
$conn = new mysqli("localhost", "root", "", "sports_academy");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

$order_id_esc   = $conn->real_escape_string($order_id);
$payment_id_esc = $conn->real_escape_string($payment_id);
$note_esc       = $conn->real_escape_string($note);

$sql = "INSERT INTO payments (player_id, amount, method, note, razorpay_order_id, razorpay_payment_id, payment_date)
        VALUES ($player_id, $amount, 'Razorpay', '$note_esc', '$order_id_esc', '$payment_id_esc', NOW())";

if ($conn->query($sql)) {
    // Update player fee status
    $conn->query("UPDATE players
                  SET fee_status='Paid', amount_paid=$amount,
                      payment_method='Razorpay', payment_date=NOW()
                  WHERE id=$player_id");

    echo json_encode(['success' => true, 'payment_id' => $payment_id]);
} else {
    echo json_encode(['success' => false, 'error' => 'DB insert failed: ' . $conn->error]);
}

$conn->close();
