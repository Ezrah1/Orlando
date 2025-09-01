<?php
require_once __DIR__ . '/bootstrap.php';

require_login();
require_permission($con, 'txn.create');

$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload) { json_response([ 'error' => 'invalid_json' ], 400); }

$deptId = intval($payload['dept_id'] ?? 0);
$description = $con->real_escape_string($payload['description'] ?? '');
$amountGross = floatval($payload['amount_gross'] ?? 0);
$taxAmount = floatval($payload['tax_amount'] ?? 0);
$discountAmount = floatval($payload['discount_amount'] ?? 0);
$netAmount = $amountGross + $taxAmount - $discountAmount;
$currency = $con->real_escape_string($payload['currency'] ?? 'KES');
$source = $con->real_escape_string($payload['source'] ?? 'pos');
$sourceId = isset($payload['source_id']) ? intval($payload['source_id']) : 'NULL';
$refCode = $con->real_escape_string($payload['ref_code'] ?? '');

$user = get_user($con);
$cashierId = intval($user['id']);

if ($deptId <= 0 || $netAmount <= 0) {
	json_response([ 'error' => 'invalid_parameters' ], 400);
}

$sql = "INSERT INTO transactions (dept_id, source, source_id, description, amount_gross, tax_amount, discount_amount, net_amount, currency, cashier_user_id, payment_status, ref_code) VALUES (?,?,?,?,?,?,?,?,?,?, 'unpaid', ?)";
$stmt = $con->prepare($sql);
$sid = $sourceId === 'NULL' ? null : $sourceId;
$stmt->bind_param('isisssddssis', $deptId, $source, $sid, $description, $amountGross, $taxAmount, $discountAmount, $netAmount, $currency, $cashierId, $refCode);
if (!$stmt->execute()) {
	json_response([ 'error' => 'db_error', 'message' => $con->error ], 500);
}
$transactionId = $stmt->insert_id;

json_response([ 'ok' => true, 'transaction_id' => $transactionId ]);

?>


