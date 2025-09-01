<?php
include("../db.php");

// M-Pesa Webhook Handler
// This endpoint receives payment confirmations from M-Pesa

header('Content-Type: application/json');

// Log the incoming webhook
$webhook_data = file_get_contents('php://input');
$log_file = '../logs/mpesa_webhook_' . date('Y-m-d') . '.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Webhook received: " . $webhook_data . "\n", FILE_APPEND);

try {
    // Parse the webhook data
    $data = json_decode($webhook_data, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data received');
    }
    
    // Extract payment details
    $transaction_id = $data['TransID'] ?? null;
    $amount = $data['TransAmount'] ?? null;
    $phone = $data['MSISDN'] ?? null;
    $business_shortcode = $data['BusinessShortCode'] ?? null;
    $bill_reference = $data['BillReferenceNumber'] ?? null;
    $invoice_number = $data['InvoiceNumber'] ?? null;
    $org_account_balance = $data['OrgAccountBalance'] ?? null;
    $first_name = $data['FirstName'] ?? null;
    $middle_name = $data['MiddleName'] ?? null;
    $last_name = $data['LastName'] ?? null;
    $result_code = $data['ResultCode'] ?? null;
    $result_desc = $data['ResultDesc'] ?? null;
    
    // Validate required fields
    if (!$transaction_id || !$amount || !$phone) {
        throw new Exception('Missing required payment information');
    }
    
    // Check if this is a successful payment
    if ($result_code == '0') {
        // Payment successful
        // Find the booking by phone number or invoice number
        $booking_query = "SELECT * FROM roombook WHERE phone = '$phone' AND status = 'pending' ORDER BY created_at DESC LIMIT 1";
        $booking_result = mysqli_query($booking_query, "");
        
        if ($booking_result && mysqli_num_rows($booking_result) > 0) {
            $booking = mysqli_fetch_assoc($booking_result);
            $booking_id = $booking['id'];
            
            // Update booking status
            $update_booking = "UPDATE roombook SET status = 'confirmed', payment_status = 'paid' WHERE id = $booking_id";
            mysqli_query($update_booking, "");
            
            // Record the payment
            $payment_sql = "INSERT INTO payment (booking_id, amount, payment_method, transaction_ref, status, payment_date) 
                           VALUES ($booking_id, $amount, 'mpesa', '$transaction_id', 'completed', NOW())";
            mysqli_query($payment_sql, "");
            
            // Log successful payment
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Payment successful for booking $booking_id: $transaction_id\n", FILE_APPEND);
            
            // Send confirmation response
            echo json_encode([
                'status' => 'success',
                'message' => 'Payment processed successfully',
                'booking_id' => $booking_id,
                'transaction_id' => $transaction_id
            ]);
        } else {
            // No pending booking found
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - No pending booking found for phone: $phone\n", FILE_APPEND);
            
            echo json_encode([
                'status' => 'warning',
                'message' => 'No pending booking found for this phone number'
            ]);
        }
    } else {
        // Payment failed
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Payment failed: $result_desc\n", FILE_APPEND);
        
        echo json_encode([
            'status' => 'error',
            'message' => 'Payment failed: ' . $result_desc
        ]);
    }
    
} catch (Exception $e) {
    // Log error
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

// Create logs directory if it doesn't exist
if (!is_dir('../logs')) {
    mkdir('../logs', 0755, true);
}
?>


