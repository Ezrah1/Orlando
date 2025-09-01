<?php
/**
 * Orlando International Resorts - Payment Gateway Manager
 * Unified payment processing system supporting multiple payment providers
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

class PaymentGatewayManager {
    private $config;
    private $activeGateways;
    private $logger;
    private $webhookHandlers;
    private $encryptionKey;

    public function __construct() {
        $this->config = $this->loadConfiguration();
        $this->activeGateways = [];
        $this->webhookHandlers = [];
        $this->encryptionKey = $this->getEncryptionKey();
        $this->initializeLogger();
        $this->registerGateways();
    }

    /**
     * Load payment gateway configuration
     */
    private function loadConfiguration() {
        $configFile = __DIR__ . '/../../config/payment_gateways.json';
        
        if (!file_exists($configFile)) {
            $this->createDefaultConfig($configFile);
        }
        
        $config = json_decode(file_get_contents($configFile), true);
        
        // Decrypt sensitive data
        if (isset($config['gateways'])) {
            foreach ($config['gateways'] as $name => &$gateway) {
                if (isset($gateway['credentials'])) {
                    foreach ($gateway['credentials'] as $key => &$value) {
                        if (strpos($key, 'secret') !== false || strpos($key, 'private') !== false) {
                            $value = $this->decrypt($value);
                        }
                    }
                }
            }
        }
        
        return $config;
    }

    /**
     * Create default configuration file
     */
    private function createDefaultConfig($configFile) {
        $defaultConfig = [
            'default_gateway' => 'stripe',
            'fallback_gateway' => 'paypal',
            'currency' => 'USD',
            'webhook_timeout' => 30,
            'retry_attempts' => 3,
            'gateways' => [
                'stripe' => [
                    'enabled' => true,
                    'name' => 'Stripe',
                    'class' => 'StripeGateway',
                    'supported_methods' => ['card', 'bank_transfer', 'digital_wallets'],
                    'supported_currencies' => ['USD', 'EUR', 'GBP', 'KES'],
                    'credentials' => [
                        'publishable_key' => 'pk_test_...',
                        'secret_key' => $this->encrypt('sk_test_...'),
                        'webhook_secret' => $this->encrypt('whsec_...')
                    ],
                    'settings' => [
                        'capture_method' => 'automatic',
                        'statement_descriptor' => 'Orlando Resorts',
                        'receipt_email' => true
                    ]
                ],
                'paypal' => [
                    'enabled' => true,
                    'name' => 'PayPal',
                    'class' => 'PayPalGateway',
                    'supported_methods' => ['paypal', 'card'],
                    'supported_currencies' => ['USD', 'EUR', 'GBP'],
                    'credentials' => [
                        'client_id' => 'your_paypal_client_id',
                        'client_secret' => $this->encrypt('your_paypal_client_secret'),
                        'webhook_id' => 'your_webhook_id'
                    ],
                    'settings' => [
                        'mode' => 'sandbox', // sandbox or live
                        'brand_name' => 'Orlando International Resorts',
                        'landing_page' => 'billing'
                    ]
                ],
                'mpesa' => [
                    'enabled' => true,
                    'name' => 'M-Pesa',
                    'class' => 'MpesaGateway',
                    'supported_methods' => ['mobile_money'],
                    'supported_currencies' => ['KES'],
                    'credentials' => [
                        'consumer_key' => 'your_mpesa_consumer_key',
                        'consumer_secret' => $this->encrypt('your_mpesa_consumer_secret'),
                        'business_short_code' => '174379',
                        'passkey' => $this->encrypt('your_passkey')
                    ],
                    'settings' => [
                        'environment' => 'sandbox', // sandbox or production
                        'queue_timeout_url' => '/webhooks/mpesa/timeout',
                        'result_url' => '/webhooks/mpesa/result'
                    ]
                ],
                'razorpay' => [
                    'enabled' => false,
                    'name' => 'Razorpay',
                    'class' => 'RazorpayGateway',
                    'supported_methods' => ['card', 'netbanking', 'wallet', 'upi'],
                    'supported_currencies' => ['INR'],
                    'credentials' => [
                        'key_id' => 'your_razorpay_key_id',
                        'key_secret' => $this->encrypt('your_razorpay_key_secret'),
                        'webhook_secret' => $this->encrypt('your_webhook_secret')
                    ]
                ],
                'flutterwave' => [
                    'enabled' => false,
                    'name' => 'Flutterwave',
                    'class' => 'FlutterwaveGateway',
                    'supported_methods' => ['card', 'bank_transfer', 'mobile_money'],
                    'supported_currencies' => ['NGN', 'KES', 'GHS', 'UGX', 'TZS'],
                    'credentials' => [
                        'public_key' => 'your_flutterwave_public_key',
                        'secret_key' => $this->encrypt('your_flutterwave_secret_key'),
                        'encryption_key' => $this->encrypt('your_encryption_key')
                    ]
                ]
            ]
        ];

        // Ensure config directory exists
        $configDir = dirname($configFile);
        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }

        file_put_contents($configFile, json_encode($defaultConfig, JSON_PRETTY_PRINT));
        return $defaultConfig;
    }

    /**
     * Register and initialize payment gateways
     */
    private function registerGateways() {
        foreach ($this->config['gateways'] as $name => $gatewayConfig) {
            if ($gatewayConfig['enabled']) {
                try {
                    $className = $gatewayConfig['class'];
                    $gatewayFile = __DIR__ . "/gateways/{$className}.php";
                    
                    if (file_exists($gatewayFile)) {
                        require_once $gatewayFile;
                        $this->activeGateways[$name] = new $className($gatewayConfig, $this);
                        $this->log("Gateway '{$name}' initialized successfully");
                    } else {
                        $this->log("Gateway file not found: {$gatewayFile}", 'error');
                    }
                } catch (Exception $e) {
                    $this->log("Failed to initialize gateway '{$name}': " . $e->getMessage(), 'error');
                }
            }
        }
    }

    /**
     * Process a payment
     */
    public function processPayment($paymentData, $gatewayName = null) {
        try {
            // Validate payment data
            $this->validatePaymentData($paymentData);
            
            // Determine gateway to use
            $gateway = $this->selectGateway($gatewayName, $paymentData);
            
            if (!$gateway) {
                throw new Exception('No suitable payment gateway available');
            }

            // Log payment attempt
            $this->log("Processing payment via {$gateway->getName()}", 'info', $paymentData['reference']);

            // Process payment
            $result = $gateway->processPayment($paymentData);
            
            // Store transaction record
            $this->storeTransaction($result, $paymentData);
            
            // Send notifications if successful
            if ($result['status'] === 'success' || $result['status'] === 'pending') {
                $this->sendPaymentNotifications($result, $paymentData);
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->log("Payment processing failed: " . $e->getMessage(), 'error', $paymentData['reference'] ?? 'unknown');
            
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'timestamp' => time()
            ];
        }
    }

    /**
     * Validate payment data
     */
    private function validatePaymentData($data) {
        $required = ['amount', 'currency', 'reference', 'customer_email'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        // Validate amount
        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new Exception('Invalid amount');
        }

        // Validate email
        if (!filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address');
        }

        // Validate currency
        $supportedCurrencies = $this->getSupportedCurrencies();
        if (!in_array($data['currency'], $supportedCurrencies)) {
            throw new Exception('Unsupported currency: ' . $data['currency']);
        }
    }

    /**
     * Select appropriate gateway for payment
     */
    private function selectGateway($preferredGateway, $paymentData) {
        // If specific gateway requested, try it first
        if ($preferredGateway && isset($this->activeGateways[$preferredGateway])) {
            $gateway = $this->activeGateways[$preferredGateway];
            if ($gateway->supports($paymentData['currency'], $paymentData['payment_method'] ?? 'card')) {
                return $gateway;
            }
        }

        // Try default gateway
        $defaultGateway = $this->config['default_gateway'];
        if (isset($this->activeGateways[$defaultGateway])) {
            $gateway = $this->activeGateways[$defaultGateway];
            if ($gateway->supports($paymentData['currency'], $paymentData['payment_method'] ?? 'card')) {
                return $gateway;
            }
        }

        // Try fallback gateway
        $fallbackGateway = $this->config['fallback_gateway'];
        if (isset($this->activeGateways[$fallbackGateway])) {
            $gateway = $this->activeGateways[$fallbackGateway];
            if ($gateway->supports($paymentData['currency'], $paymentData['payment_method'] ?? 'card')) {
                return $gateway;
            }
        }

        // Find any compatible gateway
        foreach ($this->activeGateways as $gateway) {
            if ($gateway->supports($paymentData['currency'], $paymentData['payment_method'] ?? 'card')) {
                return $gateway;
            }
        }

        return null;
    }

    /**
     * Handle webhook from payment gateway
     */
    public function handleWebhook($gatewayName, $payload, $signature = null) {
        try {
            if (!isset($this->activeGateways[$gatewayName])) {
                throw new Exception("Unknown gateway: {$gatewayName}");
            }

            $gateway = $this->activeGateways[$gatewayName];
            
            // Verify webhook signature
            if (!$gateway->verifyWebhook($payload, $signature)) {
                throw new Exception('Invalid webhook signature');
            }

            // Process webhook
            $result = $gateway->processWebhook($payload);
            
            // Update transaction status
            if (isset($result['transaction_id'])) {
                $this->updateTransactionStatus($result['transaction_id'], $result);
            }

            $this->log("Webhook processed successfully for {$gatewayName}", 'info');
            
            return ['status' => 'success'];
            
        } catch (Exception $e) {
            $this->log("Webhook processing failed for {$gatewayName}: " . $e->getMessage(), 'error');
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Refund a payment
     */
    public function refundPayment($transactionId, $amount = null, $reason = '') {
        try {
            // Get transaction details
            $transaction = $this->getTransaction($transactionId);
            
            if (!$transaction) {
                throw new Exception('Transaction not found');
            }

            if ($transaction['status'] !== 'success') {
                throw new Exception('Cannot refund non-successful transaction');
            }

            // Get gateway
            $gateway = $this->activeGateways[$transaction['gateway']];
            
            if (!$gateway) {
                throw new Exception('Gateway not available for refund');
            }

            // Process refund
            $refundData = [
                'transaction_id' => $transactionId,
                'amount' => $amount ?: $transaction['amount'],
                'reason' => $reason,
                'reference' => 'refund_' . uniqid()
            ];

            $result = $gateway->refundPayment($refundData);
            
            // Store refund record
            $this->storeRefund($result, $refundData);
            
            $this->log("Refund processed for transaction {$transactionId}", 'info');
            
            return $result;
            
        } catch (Exception $e) {
            $this->log("Refund failed for transaction {$transactionId}: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus($reference) {
        try {
            $transaction = $this->getTransactionByReference($reference);
            
            if (!$transaction) {
                return ['status' => 'not_found'];
            }

            // If transaction is pending, check with gateway
            if ($transaction['status'] === 'pending') {
                $gateway = $this->activeGateways[$transaction['gateway']];
                if ($gateway) {
                    $status = $gateway->getPaymentStatus($transaction['gateway_transaction_id']);
                    
                    // Update local status if changed
                    if ($status['status'] !== $transaction['status']) {
                        $this->updateTransactionStatus($transaction['id'], $status);
                        $transaction['status'] = $status['status'];
                    }
                }
            }

            return [
                'status' => $transaction['status'],
                'amount' => $transaction['amount'],
                'currency' => $transaction['currency'],
                'gateway' => $transaction['gateway'],
                'created_at' => $transaction['created_at'],
                'updated_at' => $transaction['updated_at']
            ];
            
        } catch (Exception $e) {
            $this->log("Failed to get payment status for {$reference}: " . $e->getMessage(), 'error');
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Get supported payment methods
     */
    public function getSupportedPaymentMethods($currency = null) {
        $methods = [];
        
        foreach ($this->activeGateways as $name => $gateway) {
            $gatewayConfig = $this->config['gateways'][$name];
            
            if (!$currency || in_array($currency, $gatewayConfig['supported_currencies'])) {
                foreach ($gatewayConfig['supported_methods'] as $method) {
                    if (!in_array($method, $methods)) {
                        $methods[] = $method;
                    }
                }
            }
        }
        
        return $methods;
    }

    /**
     * Get supported currencies
     */
    public function getSupportedCurrencies() {
        $currencies = [];
        
        foreach ($this->config['gateways'] as $gatewayConfig) {
            if ($gatewayConfig['enabled']) {
                $currencies = array_merge($currencies, $gatewayConfig['supported_currencies']);
            }
        }
        
        return array_unique($currencies);
    }

    /**
     * Get gateway statistics
     */
    public function getGatewayStatistics($period = '30d') {
        global $con;
        
        $periodCondition = '';
        switch ($period) {
            case '24h':
                $periodCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
                break;
            case '7d':
                $periodCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case '30d':
                $periodCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case '90d':
                $periodCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
                break;
        }

        $query = "
            SELECT 
                gateway,
                COUNT(*) as transaction_count,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_count,
                SUM(CASE WHEN status = 'success' THEN amount ELSE 0 END) as total_amount,
                AVG(CASE WHEN status = 'success' THEN amount ELSE NULL END) as average_amount,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count
            FROM payment_transactions 
            WHERE 1=1 {$periodCondition}
            GROUP BY gateway
        ";

        $result = mysqli_query($con, $query);
        $statistics = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $row['success_rate'] = $row['transaction_count'] > 0 ? 
                ($row['successful_count'] / $row['transaction_count']) * 100 : 0;
            $statistics[$row['gateway']] = $row;
        }

        return $statistics;
    }

    /**
     * Store transaction in database
     */
    private function storeTransaction($result, $paymentData) {
        global $con;
        
        $stmt = $con->prepare("
            INSERT INTO payment_transactions (
                reference, gateway, gateway_transaction_id, amount, currency, 
                status, customer_email, customer_name, payment_method, 
                metadata, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $metadata = json_encode([
            'booking_id' => $paymentData['booking_id'] ?? null,
            'order_id' => $paymentData['order_id'] ?? null,
            'customer_phone' => $paymentData['customer_phone'] ?? null,
            'gateway_response' => $result['gateway_response'] ?? null
        ]);

        $stmt->bind_param(
            'sssdssssss',
            $paymentData['reference'],
            $result['gateway'],
            $result['transaction_id'],
            $paymentData['amount'],
            $paymentData['currency'],
            $result['status'],
            $paymentData['customer_email'],
            $paymentData['customer_name'] ?? '',
            $paymentData['payment_method'] ?? 'card',
            $metadata
        );

        $stmt->execute();
    }

    /**
     * Get transaction by ID
     */
    private function getTransaction($transactionId) {
        global $con;
        
        $stmt = $con->prepare("SELECT * FROM payment_transactions WHERE id = ?");
        $stmt->bind_param('i', $transactionId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get transaction by reference
     */
    private function getTransactionByReference($reference) {
        global $con;
        
        $stmt = $con->prepare("SELECT * FROM payment_transactions WHERE reference = ?");
        $stmt->bind_param('s', $reference);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Update transaction status
     */
    private function updateTransactionStatus($transactionId, $statusData) {
        global $con;
        
        $stmt = $con->prepare("
            UPDATE payment_transactions 
            SET status = ?, gateway_response = ?, updated_at = NOW() 
            WHERE id = ?
        ");

        $gatewayResponse = json_encode($statusData['gateway_response'] ?? []);
        
        $stmt->bind_param('ssi', $statusData['status'], $gatewayResponse, $transactionId);
        $stmt->execute();
    }

    /**
     * Store refund record
     */
    private function storeRefund($result, $refundData) {
        global $con;
        
        $stmt = $con->prepare("
            INSERT INTO payment_refunds (
                transaction_id, refund_reference, amount, reason, 
                status, gateway_refund_id, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            'isdsss',
            $refundData['transaction_id'],
            $refundData['reference'],
            $refundData['amount'],
            $refundData['reason'],
            $result['status'],
            $result['refund_id'] ?? null
        );

        $stmt->execute();
    }

    /**
     * Send payment notifications
     */
    private function sendPaymentNotifications($result, $paymentData) {
        // Send customer notification
        if ($result['status'] === 'success') {
            $this->sendCustomerReceipt($paymentData, $result);
        }
        
        // Send admin notification for large amounts
        if ($paymentData['amount'] > 1000) {
            $this->sendAdminNotification($paymentData, $result);
        }
    }

    /**
     * Send customer receipt
     */
    private function sendCustomerReceipt($paymentData, $result) {
        // Implementation would send email receipt
        $this->log("Receipt sent to customer: " . $paymentData['customer_email']);
    }

    /**
     * Send admin notification
     */
    private function sendAdminNotification($paymentData, $result) {
        // Implementation would send admin notification
        $this->log("Admin notification sent for large payment: " . $paymentData['amount']);
    }

    /**
     * Initialize logging
     */
    private function initializeLogger() {
        $this->logger = [
            'file' => __DIR__ . '/../../logs/payments.log',
            'level' => 'info'
        ];
        
        // Ensure log directory exists
        $logDir = dirname($this->logger['file']);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Log message
     */
    private function log($message, $level = 'info', $reference = null) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}]" . ($reference ? " [{$reference}]" : "") . " {$message}" . PHP_EOL;
        
        file_put_contents($this->logger['file'], $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get encryption key
     */
    private function getEncryptionKey() {
        $keyFile = __DIR__ . '/../../config/.payment_key';
        
        if (!file_exists($keyFile)) {
            $key = base64_encode(random_bytes(32));
            file_put_contents($keyFile, $key);
            chmod($keyFile, 0600);
            return $key;
        }
        
        return file_get_contents($keyFile);
    }

    /**
     * Encrypt sensitive data
     */
    private function encrypt($data) {
        if (empty($data)) return $data;
        
        $key = base64_decode($this->encryptionKey);
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt sensitive data
     */
    private function decrypt($encryptedData) {
        if (empty($encryptedData)) return $encryptedData;
        
        $key = base64_decode($this->encryptionKey);
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }

    /**
     * Health check for all gateways
     */
    public function healthCheck() {
        $results = [];
        
        foreach ($this->activeGateways as $name => $gateway) {
            try {
                $results[$name] = [
                    'status' => 'healthy',
                    'response_time' => $gateway->healthCheck(),
                    'last_checked' => time()
                ];
            } catch (Exception $e) {
                $results[$name] = [
                    'status' => 'unhealthy',
                    'error' => $e->getMessage(),
                    'last_checked' => time()
                ];
            }
        }
        
        return $results;
    }
}
?>
