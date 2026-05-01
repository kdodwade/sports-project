<?php
// ─── RAZORPAY CONFIG — must match payment.php ─────────────────────
define('RAZORPAY_KEY_ID',     'rzp_test_XXXXXXXXXXXXXXXX');
define('RAZORPAY_KEY_SECRET', 'XXXXXXXXXXXXXXXXXXXXXXXX');
// ──────────────────────────────────────────────────────────────────

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$player_id = intval($input['player_id'] ?? 0);
$amount    = floatval($input['amount']    ?? 0);
$note      = trim($input['note']          ?? '');

if ($player_id <= 0 || $amount <= 0) {
    echo json_encode(['error' => 'Invalid player or amount']);
    exit;
}

// Amount in paise (multiply by 100)
$amount_paise = intval(round($amount * 100));

// Create order via Razorpay REST API
$data = [
    'amount'          => $amount_paise,
    'currency'        => 'INR',
    'receipt'         => 'rcpt_player_' . $player_id . '_' . time(),
    'payment_capture' => 1,
    'notes'           => ['player_id' => $player_id, 'note' => $note]
];

$ch = curl_init('https://api.razorpay.com/v1/orders');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($data),
    CURLOPT_USERPWD        => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['id'])) {
    echo json_encode([
        'order_id'    => $result['id'],
        'amount_paise'=> $amount_paise
    ]);
} else {
    echo json_encode([
        'error' => $result['error']['description'] ?? 'Could not create Razorpay order'
    ]);
}
