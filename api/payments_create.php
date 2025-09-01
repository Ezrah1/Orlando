<?php
require_once __DIR__ . '/bootstrap.php';

require_login();
require_permission($con, 'payment.capture');

$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload) { json_response([ 'error' => 'invalid_json' ], 400); }

$transactionId = intval($payload['transaction_id'] ?? 0);
$methodCode = $con->real_escape_string($payload['method'] ?? '');
$amount = floatval($payload['amount'] ?? 0);
$externalRef = $con->real_escape_string($payload['external_ref'] ?? '');

if ($transactionId <= 0 || $amount <= 0 || $methodCode === '') {
	json_response([ 'error' => 'invalid_parameters' ], 400);
}

// Resolve method id
$res = $con->query("SELECT id FROM payment_methods WHERE code = '$methodCode' LIMIT 1");
if (!$res || $res->num_rows === 0) { json_response([ 'error' => 'unknown_method' ], 400); }
$method = $res->fetch_assoc();
$methodId = intval($method['id']);

// Insert payment
$sql = "INSERT INTO payments (transaction_id, method_id, amount, currency, external_ref, status, paid_at) VALUES (?,?,?,?,?, 'paid', NOW())";
$stmt = $con->prepare($sql);
$currency = 'KES';
$stmt->bind_param('iisss', $transactionId, $methodId, $amount, $currency, $externalRef);
if (!$stmt->execute()) {
	json_response([ 'error' => 'db_error', 'message' => $con->error ], 500);
}

// Update transaction status to paid if fully covered (simplified: mark paid)
$con->query("UPDATE transactions SET payment_status = 'paid' WHERE id = $transactionId");

json_response([ 'ok' => true, 'payment_id' => $stmt->insert_id ]);

?>


