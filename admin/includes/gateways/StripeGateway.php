<?php
/**
 * Orlando International Resorts - Stripe Payment Gateway
 * Implementation of Stripe payment processing
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

class StripeGateway {
    private $config;
    private $manager;
    private $apiKey;
    private $webhookSecret;
    private $apiBase = 'https://api.stripe.com';

    public function __construct($config, $manager) {
        $this->config = $config;
        $this->manager = $manager;
        $this->apiKey = $config['credentials']['secret_key'];
        $this->webhookSecret = $config['credentials']['webhook_secret'];
    }

    /**
     * Get gateway name
     */
    public function getName() {
        return $this->config['name'];
    }

    /**
     * Check if gateway supports currency and payment method
     */
    public function supports($currency, $paymentMethod) {
        return in_array($currency, $this->config['supported_currencies']) &&
               in_array($paymentMethod, $this->config['supported_methods']);
    }

    /**
     * Process payment
     */
    public function processPayment($paymentData) {
        try {
            // Create payment intent
            $intentData = [
                'amount' => $this->convertToSmallestUnit($paymentData['amount'], $paymentData['currency']),
                'currency' => strtolower($paymentData['currency']),
                'payment_method_types' => $this->getPaymentMethodTypes($paymentData['payment_method'] ?? 'card'),
                'description' => $paymentData['description'] ?? 'Payment for Orlando International Resorts',
                'receipt_email' => $paymentData['customer_email'],
                'metadata' => [
                    'reference' => $paymentData['reference'],
                    'booking_id' => $paymentData['booking_id'] ?? null,
                    'order_id' => $paymentData['order_id'] ?? null,
                    'customer_name' => $paymentData['customer_name'] ?? '',
                ],
                'statement_descriptor' => $this->config['settings']['statement_descriptor'] ?? 'Orlando Resort',
                'capture_method' => $this->config['settings']['capture_method'] ?? 'automatic'
            ];

            // Add shipping address if provided
            if (isset($paymentData['shipping_address'])) {
                $intentData['shipping'] = [
                    'name' => $paymentData['customer_name'] ?? 'Customer',
                    'address' => $paymentData['shipping_address']
                ];
            }

            $response = $this->makeApiCall('POST', '/v1/payment_intents', $intentData);

            if ($response['status'] === 'succeeded') {
                return [
                    'status' => 'success',
                    'transaction_id' => $response['id'],
                    'gateway' => 'stripe',
                    'gateway_response' => $response,
                    'amount' => $paymentData['amount'],
                    'currency' => $paymentData['currency'],
                    'message' => 'Payment processed successfully'
                ];
            } elseif ($response['status'] === 'requires_action') {
                return [
                    'status' => 'requires_action',
                    'transaction_id' => $response['id'],
                    'gateway' => 'stripe',
                    'client_secret' => $response['client_secret'],
                    'next_action' => $response['next_action'],
                    'gateway_response' => $response,
                    'message' => 'Additional authentication required'
                ];
            } else {
                return [
                    'status' => 'pending',
                    'transaction_id' => $response['id'],
                    'gateway' => 'stripe',
                    'client_secret' => $response['client_secret'],
                    'gateway_response' => $response,
                    'message' => 'Payment is being processed'
                ];
            }

        } catch (Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'gateway' => 'stripe'
            ];
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhook($payload, $signature) {
        if (empty($signature) || empty($this->webhookSecret)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);
        
        // Extract signature from header
        $elements = explode(',', $signature);
        $signatureHash = '';
        
        foreach ($elements as $element) {
            if (strpos($element, 'v1=') === 0) {
                $signatureHash = substr($element, 3);
                break;
            }
        }

        return hash_equals($expectedSignature, $signatureHash);
    }

    /**
     * Process webhook
     */
    public function processWebhook($payload) {
        $event = json_decode($payload, true);
        
        switch ($event['type']) {
            case 'payment_intent.succeeded':
                return $this->handlePaymentSucceeded($event['data']['object']);
                
            case 'payment_intent.payment_failed':
                return $this->handlePaymentFailed($event['data']['object']);
                
            case 'payment_intent.requires_action':
                return $this->handlePaymentRequiresAction($event['data']['object']);
                
            case 'charge.dispute.created':
                return $this->handleDisputeCreated($event['data']['object']);
                
            default:
                return ['status' => 'ignored', 'message' => 'Event type not handled'];
        }
    }

    /**
     * Handle successful payment webhook
     */
    private function handlePaymentSucceeded($paymentIntent) {
        return [
            'status' => 'success',
            'transaction_id' => $paymentIntent['id'],
            'amount' => $this->convertFromSmallestUnit($paymentIntent['amount'], $paymentIntent['currency']),
            'currency' => strtoupper($paymentIntent['currency']),
            'gateway_response' => $paymentIntent
        ];
    }

    /**
     * Handle failed payment webhook
     */
    private function handlePaymentFailed($paymentIntent) {
        $lastPaymentError = $paymentIntent['last_payment_error'] ?? [];
        
        return [
            'status' => 'failed',
            'transaction_id' => $paymentIntent['id'],
            'error' => $lastPaymentError['message'] ?? 'Payment failed',
            'error_code' => $lastPaymentError['code'] ?? 'unknown',
            'gateway_response' => $paymentIntent
        ];
    }

    /**
     * Handle payment requires action webhook
     */
    private function handlePaymentRequiresAction($paymentIntent) {
        return [
            'status' => 'requires_action',
            'transaction_id' => $paymentIntent['id'],
            'next_action' => $paymentIntent['next_action'],
            'gateway_response' => $paymentIntent
        ];
    }

    /**
     * Handle dispute created webhook
     */
    private function handleDisputeCreated($dispute) {
        // Store dispute information
        global $con;
        
        $stmt = $con->prepare("
            INSERT INTO payment_disputes (
                transaction_id, gateway_dispute_id, amount, currency, 
                reason, status, evidence_due_by, gateway_response, created_at
            ) 
            SELECT pt.id, ?, ?, ?, ?, ?, ?, ?, NOW()
            FROM payment_transactions pt 
            WHERE pt.gateway_transaction_id = ?
        ");

        $evidenceDueBy = isset($dispute['evidence_details']['due_by']) ? 
            date('Y-m-d H:i:s', $dispute['evidence_details']['due_by']) : null;

        $stmt->bind_param(
            'sidsssss',
            $dispute['id'],
            $this->convertFromSmallestUnit($dispute['amount'], $dispute['currency']),
            strtoupper($dispute['currency']),
            $dispute['reason'],
            $dispute['status'],
            $evidenceDueBy,
            json_encode($dispute),
            $dispute['charge']
        );

        $stmt->execute();

        return [
            'status' => 'processed',
            'dispute_id' => $dispute['id'],
            'message' => 'Dispute recorded'
        ];
    }

    /**
     * Refund payment
     */
    public function refundPayment($refundData) {
        try {
            $refundParams = [
                'payment_intent' => $refundData['transaction_id'],
                'amount' => $this->convertToSmallestUnit($refundData['amount'], $refundData['currency'] ?? 'USD'),
                'reason' => $this->mapRefundReason($refundData['reason'] ?? ''),
                'metadata' => [
                    'refund_reference' => $refundData['reference'],
                    'original_reference' => $refundData['original_reference'] ?? '',
                    'reason' => $refundData['reason'] ?? ''
                ]
            ];

            $response = $this->makeApiCall('POST', '/v1/refunds', $refundParams);

            return [
                'status' => $response['status'] === 'succeeded' ? 'success' : 'pending',
                'refund_id' => $response['id'],
                'amount' => $this->convertFromSmallestUnit($response['amount'], $response['currency']),
                'currency' => strtoupper($response['currency']),
                'gateway_response' => $response
            ];

        } catch (Exception $e) {
            throw new Exception('Stripe refund failed: ' . $e->getMessage());
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus($transactionId) {
        try {
            $response = $this->makeApiCall('GET', "/v1/payment_intents/{$transactionId}");

            $status = 'pending';
            switch ($response['status']) {
                case 'succeeded':
                    $status = 'success';
                    break;
                case 'canceled':
                    $status = 'cancelled';
                    break;
                case 'payment_failed':
                    $status = 'failed';
                    break;
                case 'requires_action':
                case 'requires_confirmation':
                case 'requires_payment_method':
                    $status = 'pending';
                    break;
            }

            return [
                'status' => $status,
                'gateway_response' => $response
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Health check
     */
    public function healthCheck() {
        $startTime = microtime(true);
        
        try {
            // Make a simple API call to check connectivity
            $this->makeApiCall('GET', '/v1/balance');
            
            $endTime = microtime(true);
            return round(($endTime - $startTime) * 1000); // Return response time in ms
            
        } catch (Exception $e) {
            throw new Exception('Stripe API health check failed: ' . $e->getMessage());
        }
    }

    /**
     * Make API call to Stripe
     */
    private function makeApiCall($method, $endpoint, $data = null) {
        $url = $this->apiBase . $endpoint;
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/x-www-form-urlencoded',
            'Stripe-Version: 2023-10-16'
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'Orlando-Resorts/1.0'
        ]);

        if ($data && ($method === 'POST' || $method === 'PUT')) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('cURL error: ' . $error);
        }

        $decodedResponse = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMessage = $decodedResponse['error']['message'] ?? 'Unknown error';
            $errorCode = $decodedResponse['error']['code'] ?? 'unknown';
            throw new Exception("Stripe API error: {$errorMessage}", $httpCode);
        }

        return $decodedResponse;
    }

    /**
     * Convert amount to smallest currency unit (cents)
     */
    private function convertToSmallestUnit($amount, $currency) {
        $zeroDecimalCurrencies = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];
        
        if (in_array(strtoupper($currency), $zeroDecimalCurrencies)) {
            return (int) $amount;
        }
        
        return (int) ($amount * 100);
    }

    /**
     * Convert amount from smallest currency unit
     */
    private function convertFromSmallestUnit($amount, $currency) {
        $zeroDecimalCurrencies = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];
        
        if (in_array(strtoupper($currency), $zeroDecimalCurrencies)) {
            return $amount;
        }
        
        return $amount / 100;
    }

    /**
     * Get payment method types for Stripe
     */
    private function getPaymentMethodTypes($paymentMethod) {
        $methodMap = [
            'card' => ['card'],
            'bank_transfer' => ['us_bank_account', 'sepa_debit'],
            'digital_wallets' => ['card'], // Apple Pay/Google Pay work through card
            'mobile_money' => ['card'] // Fallback to card
        ];

        return $methodMap[$paymentMethod] ?? ['card'];
    }

    /**
     * Map refund reason to Stripe format
     */
    private function mapRefundReason($reason) {
        $reasonMap = [
            'duplicate' => 'duplicate',
            'fraudulent' => 'fraudulent',
            'customer_request' => 'requested_by_customer',
            'processing_error' => 'duplicate',
            'other' => 'requested_by_customer'
        ];

        return $reasonMap[strtolower($reason)] ?? 'requested_by_customer';
    }

    /**
     * Create customer in Stripe
     */
    public function createCustomer($customerData) {
        try {
            $customerParams = [
                'email' => $customerData['email'],
                'name' => $customerData['name'] ?? '',
                'phone' => $customerData['phone'] ?? '',
                'metadata' => [
                    'customer_id' => $customerData['customer_id'] ?? '',
                    'source' => 'orlando_resorts'
                ]
            ];

            if (isset($customerData['address'])) {
                $customerParams['address'] = $customerData['address'];
            }

            $response = $this->makeApiCall('POST', '/v1/customers', $customerParams);
            
            return [
                'customer_id' => $response['id'],
                'gateway_response' => $response
            ];

        } catch (Exception $e) {
            throw new Exception('Failed to create Stripe customer: ' . $e->getMessage());
        }
    }

    /**
     * Create payment method
     */
    public function createPaymentMethod($paymentMethodData) {
        try {
            $response = $this->makeApiCall('POST', '/v1/payment_methods', $paymentMethodData);
            return $response;
        } catch (Exception $e) {
            throw new Exception('Failed to create payment method: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve payment intent for client-side completion
     */
    public function getClientSecret($transactionId) {
        try {
            $response = $this->makeApiCall('GET', "/v1/payment_intents/{$transactionId}");
            return $response['client_secret'];
        } catch (Exception $e) {
            throw new Exception('Failed to retrieve client secret: ' . $e->getMessage());
        }
    }
}
?>
