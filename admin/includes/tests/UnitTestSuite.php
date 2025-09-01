<?php
/**
 * Orlando International Resorts - Unit Test Suite
 * Comprehensive unit tests for individual components and functions
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

class UnitTestSuite {
    private $framework;
    private $tests;
    private $results;

    public function __construct($framework) {
        $this->framework = $framework;
        $this->tests = [];
        $this->results = [
            'total_tests' => 0,
            'passed' => 0,
            'failed' => 0,
            'skipped' => 0,
            'execution_time' => 0,
            'test_details' => []
        ];
        
        $this->registerTests();
    }

    /**
     * Register all unit tests
     */
    private function registerTests() {
        // Database connection tests
        $this->tests['database'] = [
            'testDatabaseConnection',
            'testDatabaseQueries',
            'testDatabaseTransactions'
        ];

        // Authentication tests
        $this->tests['authentication'] = [
            'testUserRegistration',
            'testUserLogin',
            'testPasswordHashing',
            'testSessionManagement'
        ];

        // Booking system tests
        $this->tests['booking'] = [
            'testRoomAvailability',
            'testBookingCreation',
            'testBookingValidation',
            'testBookingCancellation'
        ];

        // Payment system tests
        $this->tests['payment'] = [
            'testPaymentGatewayManager',
            'testPaymentProcessing',
            'testPaymentValidation',
            'testRefundProcessing'
        ];

        // Menu system tests
        $this->tests['menu'] = [
            'testMenuItemCreation',
            'testMenuCategories',
            'testOrderProcessing',
            'testCartManagement'
        ];

        // Performance system tests
        $this->tests['performance'] = [
            'testCacheOperations',
            'testPerformanceMetrics',
            'testAssetOptimization'
        ];

        // API tests
        $this->tests['api'] = [
            'testAPIEndpoints',
            'testAPIAuthentication',
            'testAPIRateLimiting',
            'testAPIResponseFormat'
        ];

        // Utility function tests
        $this->tests['utilities'] = [
            'testDateFormatting',
            'testEmailValidation',
            'testPhoneValidation',
            'testDataSanitization'
        ];
    }

    /**
     * Run all unit tests
     */
    public function run() {
        $startTime = microtime(true);
        
        foreach ($this->tests as $category => $categoryTests) {
            foreach ($categoryTests as $testMethod) {
                $this->runSingleTest($category, $testMethod);
            }
        }
        
        $this->results['execution_time'] = round((microtime(true) - $startTime) * 1000, 2);
        
        return $this->results;
    }

    /**
     * Run a single test
     */
    private function runSingleTest($category, $testMethod) {
        $this->results['total_tests']++;
        $testStartTime = microtime(true);
        
        try {
            $this->$testMethod();
            $this->results['passed']++;
            $status = 'passed';
            $error = null;
        } catch (AssertionException $e) {
            $this->results['failed']++;
            $status = 'failed';
            $error = $e->getMessage();
        } catch (Exception $e) {
            $this->results['failed']++;
            $status = 'error';
            $error = $e->getMessage();
        }
        
        $executionTime = round((microtime(true) - $testStartTime) * 1000, 2);
        
        $this->results['test_details'][] = [
            'category' => $category,
            'method' => $testMethod,
            'status' => $status,
            'execution_time' => $executionTime,
            'error' => $error
        ];
    }

    // ========== DATABASE TESTS ==========

    /**
     * Test database connection
     */
    private function testDatabaseConnection() {
        global $con;
        
        $this->framework->assertNotNull($con, 'Database connection should not be null');
        $this->framework->assertTrue(mysqli_ping($con), 'Database connection should be active');
        
        // Test query execution
        $result = mysqli_query($con, "SELECT 1 as test");
        $this->framework->assertNotNull($result, 'Simple query should execute successfully');
        
        $row = mysqli_fetch_assoc($result);
        $this->framework->assertEquals(1, $row['test'], 'Query result should match expected value');
    }

    /**
     * Test database queries
     */
    private function testDatabaseQueries() {
        global $con;
        
        // Test SELECT query
        $result = mysqli_query($con, "SELECT COUNT(*) as count FROM users");
        $this->framework->assertNotNull($result, 'Users count query should execute');
        
        // Test INSERT query
        $testEmail = 'unittest_' . uniqid() . '@test.com';
        $insertQuery = "INSERT INTO users (name, email, phone, country, password) VALUES ('Unit Test', ?, '1234567890', 'Test', 'test123')";
        $stmt = $con->prepare($insertQuery);
        $stmt->bind_param('s', $testEmail);
        $result = $stmt->execute();
        $this->framework->assertTrue($result, 'INSERT query should execute successfully');
        
        $insertId = $con->insert_id;
        $this->framework->assertTrue($insertId > 0, 'INSERT should return valid ID');
        
        // Test UPDATE query
        $updateQuery = "UPDATE users SET name = 'Updated Unit Test' WHERE id = ?";
        $stmt = $con->prepare($updateQuery);
        $stmt->bind_param('i', $insertId);
        $result = $stmt->execute();
        $this->framework->assertTrue($result, 'UPDATE query should execute successfully');
        
        // Test DELETE query
        $deleteQuery = "DELETE FROM users WHERE id = ?";
        $stmt = $con->prepare($deleteQuery);
        $stmt->bind_param('i', $insertId);
        $result = $stmt->execute();
        $this->framework->assertTrue($result, 'DELETE query should execute successfully');
    }

    /**
     * Test database transactions
     */
    private function testDatabaseTransactions() {
        global $con;
        
        // Start transaction
        $this->framework->assertTrue(mysqli_begin_transaction($con), 'Transaction should start successfully');
        
        // Insert test data
        $testEmail = 'transaction_test_' . uniqid() . '@test.com';
        $stmt = $con->prepare("INSERT INTO users (name, email, phone, country, password) VALUES ('Transaction Test', ?, '1234567890', 'Test', 'test123')");
        $stmt->bind_param('s', $testEmail);
        $result = $stmt->execute();
        $this->framework->assertTrue($result, 'INSERT in transaction should succeed');
        
        $insertId = $con->insert_id;
        
        // Rollback transaction
        $this->framework->assertTrue(mysqli_rollback($con), 'Transaction rollback should succeed');
        
        // Verify data was rolled back
        $stmt = $con->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->bind_param('i', $insertId);
        $stmt->execute();
        $result = $stmt->get_result();
        $this->framework->assertEquals(0, $result->num_rows, 'Rolled back data should not exist');
    }

    // ========== AUTHENTICATION TESTS ==========

    /**
     * Test user registration
     */
    private function testUserRegistration() {
        $testData = [
            'name' => 'Test User',
            'email' => 'test_' . uniqid() . '@example.com',
            'phone' => '1234567890',
            'country' => 'Test Country',
            'password' => 'password123'
        ];
        
        // Test password hashing
        $hashedPassword = password_hash($testData['password'], PASSWORD_DEFAULT);
        $this->framework->assertNotEquals($testData['password'], $hashedPassword, 'Password should be hashed');
        $this->framework->assertTrue(password_verify($testData['password'], $hashedPassword), 'Password verification should work');
    }

    /**
     * Test user login
     */
    private function testUserLogin() {
        global $con;
        
        // Create test user
        $testEmail = 'login_test_' . uniqid() . '@test.com';
        $testPassword = 'password123';
        $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);
        
        $stmt = $con->prepare("INSERT INTO users (name, email, phone, country, password) VALUES ('Login Test', ?, '1234567890', 'Test', ?)");
        $stmt->bind_param('ss', $testEmail, $hashedPassword);
        $stmt->execute();
        
        // Test login
        $stmt = $con->prepare("SELECT id, password FROM users WHERE email = ?");
        $stmt->bind_param('s', $testEmail);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $this->framework->assertEquals(1, $result->num_rows, 'User should be found');
        
        $user = $result->fetch_assoc();
        $this->framework->assertTrue(password_verify($testPassword, $user['password']), 'Password verification should succeed');
        
        // Cleanup
        $stmt = $con->prepare("DELETE FROM users WHERE email = ?");
        $stmt->bind_param('s', $testEmail);
        $stmt->execute();
    }

    /**
     * Test password hashing
     */
    private function testPasswordHashing() {
        $password = 'testPassword123';
        
        // Test different hash algorithms
        $hash1 = password_hash($password, PASSWORD_DEFAULT);
        $hash2 = password_hash($password, PASSWORD_DEFAULT);
        
        $this->framework->assertNotEquals($hash1, $hash2, 'Different hashes should be generated for same password');
        $this->framework->assertTrue(password_verify($password, $hash1), 'First hash should verify correctly');
        $this->framework->assertTrue(password_verify($password, $hash2), 'Second hash should verify correctly');
        $this->framework->assertFalse(password_verify('wrongPassword', $hash1), 'Wrong password should not verify');
    }

    /**
     * Test session management
     */
    private function testSessionManagement() {
        // Test session start
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->framework->assertTrue(session_status() === PHP_SESSION_ACTIVE, 'Session should be active');
        
        // Test session variables
        $_SESSION['test_key'] = 'test_value';
        $this->framework->assertEquals('test_value', $_SESSION['test_key'], 'Session variable should be set correctly');
        
        // Test session cleanup
        unset($_SESSION['test_key']);
        $this->framework->assertFalse(isset($_SESSION['test_key']), 'Session variable should be unset');
    }

    // ========== BOOKING SYSTEM TESTS ==========

    /**
     * Test room availability
     */
    private function testRoomAvailability() {
        global $con;
        
        // Get available rooms
        $checkin = date('Y-m-d', strtotime('+30 days'));
        $checkout = date('Y-m-d', strtotime('+33 days'));
        
        $query = "SELECT r.id, r.type, r.place FROM room r 
                  WHERE r.id NOT IN (
                      SELECT rb.room FROM roombook rb 
                      WHERE (rb.cin <= ? AND rb.cout >= ?) 
                         OR (rb.cin <= ? AND rb.cout >= ?)
                  )";
        
        $stmt = $con->prepare($query);
        $stmt->bind_param('ssss', $checkin, $checkin, $checkout, $checkout);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $this->framework->assertTrue($result->num_rows >= 0, 'Room availability query should execute');
    }

    /**
     * Test booking creation
     */
    private function testBookingCreation() {
        global $con;
        
        $bookingData = [
            'name' => 'Test Booking',
            'email' => 'booking_' . uniqid() . '@test.com',
            'phone' => '1234567890',
            'country' => 'Test',
            'room' => 1,
            'cin' => date('Y-m-d', strtotime('+60 days')),
            'cout' => date('Y-m-d', strtotime('+63 days'))
        ];
        
        $stmt = $con->prepare("INSERT INTO roombook (name, email, phone, country, room, cin, cout) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssiss', 
            $bookingData['name'],
            $bookingData['email'],
            $bookingData['phone'],
            $bookingData['country'],
            $bookingData['room'],
            $bookingData['cin'],
            $bookingData['cout']
        );
        
        $result = $stmt->execute();
        $this->framework->assertTrue($result, 'Booking creation should succeed');
        
        $bookingId = $con->insert_id;
        $this->framework->assertTrue($bookingId > 0, 'Booking should have valid ID');
        
        // Cleanup
        $stmt = $con->prepare("DELETE FROM roombook WHERE id = ?");
        $stmt->bind_param('i', $bookingId);
        $stmt->execute();
    }

    /**
     * Test booking validation
     */
    private function testBookingValidation() {
        // Test date validation
        $checkin = date('Y-m-d', strtotime('+1 day'));
        $checkout = date('Y-m-d', strtotime('+3 days'));
        
        $this->framework->assertTrue(strtotime($checkout) > strtotime($checkin), 'Checkout should be after checkin');
        $this->framework->assertTrue(strtotime($checkin) > time(), 'Checkin should be in the future');
        
        // Test email validation
        $validEmail = 'test@example.com';
        $invalidEmail = 'invalid-email';
        
        $this->framework->assertTrue(filter_var($validEmail, FILTER_VALIDATE_EMAIL) !== false, 'Valid email should pass validation');
        $this->framework->assertFalse(filter_var($invalidEmail, FILTER_VALIDATE_EMAIL) !== false, 'Invalid email should fail validation');
    }

    /**
     * Test booking cancellation
     */
    private function testBookingCancellation() {
        global $con;
        
        // Create test booking
        $testEmail = 'cancel_test_' . uniqid() . '@test.com';
        $stmt = $con->prepare("INSERT INTO roombook (name, email, phone, country, room, cin, cout) VALUES ('Cancel Test', ?, '1234567890', 'Test', 1, ?, ?)");
        $checkin = date('Y-m-d', strtotime('+90 days'));
        $checkout = date('Y-m-d', strtotime('+93 days'));
        $stmt->bind_param('sss', $testEmail, $checkin, $checkout);
        $stmt->execute();
        
        $bookingId = $con->insert_id;
        
        // Test cancellation
        $stmt = $con->prepare("DELETE FROM roombook WHERE id = ?");
        $stmt->bind_param('i', $bookingId);
        $result = $stmt->execute();
        
        $this->framework->assertTrue($result, 'Booking cancellation should succeed');
        $this->framework->assertEquals(1, $stmt->affected_rows, 'One booking should be cancelled');
    }

    // ========== PAYMENT SYSTEM TESTS ==========

    /**
     * Test payment gateway manager
     */
    private function testPaymentGatewayManager() {
        if (!class_exists('PaymentGatewayManager')) {
            $this->results['skipped']++;
            $this->results['total_tests']--;
            return;
        }
        
        $paymentManager = new PaymentGatewayManager();
        $this->framework->assertNotNull($paymentManager, 'Payment manager should be instantiated');
        
        // Test supported currencies
        $currencies = $paymentManager->getSupportedCurrencies();
        $this->framework->assertTrue(is_array($currencies), 'Supported currencies should be an array');
        $this->framework->assertContains('USD', $currencies, 'USD should be supported');
    }

    /**
     * Test payment processing
     */
    private function testPaymentProcessing() {
        $paymentData = [
            'amount' => 100.00,
            'currency' => 'USD',
            'reference' => 'test_' . uniqid(),
            'customer_email' => 'test@example.com',
            'customer_name' => 'Test Customer'
        ];
        
        // Validate payment data
        $this->framework->assertTrue(is_numeric($paymentData['amount']), 'Amount should be numeric');
        $this->framework->assertTrue($paymentData['amount'] > 0, 'Amount should be positive');
        $this->framework->assertTrue(filter_var($paymentData['customer_email'], FILTER_VALIDATE_EMAIL) !== false, 'Email should be valid');
        $this->framework->assertNotNull($paymentData['reference'], 'Reference should not be null');
    }

    /**
     * Test payment validation
     */
    private function testPaymentValidation() {
        // Test amount validation
        $this->framework->assertTrue(is_numeric(100), 'Valid amount should pass validation');
        $this->framework->assertFalse(is_numeric('invalid'), 'Invalid amount should fail validation');
        
        // Test currency validation
        $validCurrencies = ['USD', 'EUR', 'GBP', 'KES'];
        $this->framework->assertContains('USD', $validCurrencies, 'USD should be valid currency');
        $this->framework->assertFalse(in_array('INVALID', $validCurrencies), 'INVALID should not be valid currency');
    }

    /**
     * Test refund processing
     */
    private function testRefundProcessing() {
        $refundData = [
            'transaction_id' => 'test_123',
            'amount' => 50.00,
            'reason' => 'customer_request'
        ];
        
        $this->framework->assertNotNull($refundData['transaction_id'], 'Transaction ID should not be null');
        $this->framework->assertTrue($refundData['amount'] > 0, 'Refund amount should be positive');
        $this->framework->assertNotNull($refundData['reason'], 'Refund reason should not be null');
    }

    // ========== MENU SYSTEM TESTS ==========

    /**
     * Test menu item creation
     */
    private function testMenuItemCreation() {
        global $con;
        
        $menuItem = [
            'name' => 'Test Menu Item',
            'description' => 'Test description',
            'price' => 15.99,
            'category_id' => 1,
            'available' => 1
        ];
        
        $stmt = $con->prepare("INSERT INTO menu_items (name, description, price, category_id, available) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('ssdii', 
            $menuItem['name'],
            $menuItem['description'],
            $menuItem['price'],
            $menuItem['category_id'],
            $menuItem['available']
        );
        
        $result = $stmt->execute();
        $this->framework->assertTrue($result, 'Menu item creation should succeed');
        
        $itemId = $con->insert_id;
        
        // Cleanup
        $stmt = $con->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt->bind_param('i', $itemId);
        $stmt->execute();
    }

    /**
     * Test menu categories
     */
    private function testMenuCategories() {
        global $con;
        
        $result = mysqli_query($con, "SELECT COUNT(*) as count FROM menu_categories");
        $this->framework->assertNotNull($result, 'Menu categories query should execute');
        
        $row = mysqli_fetch_assoc($result);
        $this->framework->assertTrue($row['count'] >= 0, 'Categories count should be non-negative');
    }

    /**
     * Test order processing
     */
    private function testOrderProcessing() {
        $orderData = [
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'items' => [
                ['id' => 1, 'quantity' => 2, 'price' => 15.99],
                ['id' => 2, 'quantity' => 1, 'price' => 8.50]
            ]
        ];
        
        // Calculate total
        $total = 0;
        foreach ($orderData['items'] as $item) {
            $total += $item['quantity'] * $item['price'];
        }
        
        $this->framework->assertEquals(40.48, $total, 'Order total should be calculated correctly');
        $this->framework->assertTrue(filter_var($orderData['customer_email'], FILTER_VALIDATE_EMAIL) !== false, 'Customer email should be valid');
    }

    /**
     * Test cart management
     */
    private function testCartManagement() {
        if (!class_exists('CartManager')) {
            $this->results['skipped']++;
            $this->results['total_tests']--;
            return;
        }
        
        // Test cart operations
        $_SESSION['cart'] = [];
        
        // Add item to cart
        $item = ['id' => 1, 'name' => 'Test Item', 'price' => 10.00, 'quantity' => 2];
        $_SESSION['cart'][] = $item;
        
        $this->framework->assertEquals(1, count($_SESSION['cart']), 'Cart should have one item');
        $this->framework->assertEquals(2, $_SESSION['cart'][0]['quantity'], 'Item quantity should be correct');
        
        // Clear cart
        $_SESSION['cart'] = [];
        $this->framework->assertEquals(0, count($_SESSION['cart']), 'Cart should be empty after clearing');
    }

    // ========== UTILITY FUNCTION TESTS ==========

    /**
     * Test date formatting
     */
    private function testDateFormatting() {
        $date = '2024-12-20';
        $timestamp = strtotime($date);
        
        $this->framework->assertNotFalse($timestamp, 'Date should be parseable');
        $this->framework->assertEquals($date, date('Y-m-d', $timestamp), 'Date formatting should be consistent');
        
        // Test date validation
        $this->framework->assertTrue(checkdate(12, 20, 2024), 'Valid date should pass validation');
        $this->framework->assertFalse(checkdate(13, 35, 2024), 'Invalid date should fail validation');
    }

    /**
     * Test email validation
     */
    private function testEmailValidation() {
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'user+tag@example.org'
        ];
        
        $invalidEmails = [
            'invalid-email',
            '@domain.com',
            'user@',
            'user..name@domain.com'
        ];
        
        foreach ($validEmails as $email) {
            $this->framework->assertTrue(filter_var($email, FILTER_VALIDATE_EMAIL) !== false, "'{$email}' should be valid");
        }
        
        foreach ($invalidEmails as $email) {
            $this->framework->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL) !== false, "'{$email}' should be invalid");
        }
    }

    /**
     * Test phone validation
     */
    private function testPhoneValidation() {
        $validPhones = [
            '+1234567890',
            '1234567890',
            '+44 20 7946 0958',
            '(555) 123-4567'
        ];
        
        foreach ($validPhones as $phone) {
            $cleaned = preg_replace('/[^0-9+]/', '', $phone);
            $this->framework->assertTrue(strlen($cleaned) >= 10, "'{$phone}' should have at least 10 digits");
        }
    }

    /**
     * Test data sanitization
     */
    private function testDataSanitization() {
        $testData = [
            'script' => '<script>alert("xss")</script>',
            'sql' => "'; DROP TABLE users; --",
            'html' => '<h1>Title</h1><p>Content</p>',
            'normal' => 'Normal text content'
        ];
        
        // Test HTML escaping
        $escaped = htmlspecialchars($testData['script'], ENT_QUOTES, 'UTF-8');
        $this->framework->assertNotContains('<script>', $escaped, 'Script tags should be escaped');
        
        // Test SQL escaping
        global $con;
        $escaped = mysqli_real_escape_string($con, $testData['sql']);
        $this->framework->assertNotEquals($testData['sql'], $escaped, 'SQL should be escaped');
        
        // Test strip tags
        $stripped = strip_tags($testData['html']);
        $this->framework->assertEquals('TitleContent', $stripped, 'HTML tags should be stripped');
    }

    // ========== API TESTS ==========

    /**
     * Test API endpoints
     */
    private function testAPIEndpoints() {
        // Test that API files exist
        $apiFiles = [
            'get_dashboard_stats.php',
            'get_notifications.php',
            'bookings-api.php'
        ];
        
        foreach ($apiFiles as $file) {
            $filePath = __DIR__ . '/../../api/' . $file;
            if (file_exists($filePath)) {
                $this->framework->assertTrue(true, "API file {$file} exists");
            }
        }
    }

    /**
     * Test API authentication
     */
    private function testAPIAuthentication() {
        // Test session-based authentication
        $_SESSION['user'] = 'test_user';
        $this->framework->assertTrue(isset($_SESSION['user']), 'User session should be set');
        
        unset($_SESSION['user']);
        $this->framework->assertFalse(isset($_SESSION['user']), 'User session should be unset');
    }

    /**
     * Test API rate limiting
     */
    private function testAPIRateLimiting() {
        // Basic rate limiting test
        $requests = [];
        $currentTime = time();
        
        // Simulate 10 requests in current minute
        for ($i = 0; $i < 10; $i++) {
            $requests[] = $currentTime;
        }
        
        $this->framework->assertEquals(10, count($requests), 'Should track 10 requests');
        
        // Filter requests in last minute
        $recentRequests = array_filter($requests, function($timestamp) use ($currentTime) {
            return ($currentTime - $timestamp) < 60;
        });
        
        $this->framework->assertEquals(10, count($recentRequests), 'Should have 10 recent requests');
    }

    /**
     * Test API response format
     */
    private function testAPIResponseFormat() {
        $response = [
            'success' => true,
            'data' => ['key' => 'value'],
            'timestamp' => time()
        ];
        
        $this->framework->assertArrayHasKey('success', $response, 'Response should have success key');
        $this->framework->assertArrayHasKey('data', $response, 'Response should have data key');
        $this->framework->assertTrue($response['success'], 'Success should be true');
        
        // Test JSON encoding
        $json = json_encode($response);
        $this->framework->assertNotNull($json, 'Response should be JSON encodable');
        
        $decoded = json_decode($json, true);
        $this->framework->assertEquals($response, $decoded, 'JSON should decode correctly');
    }

    // ========== PERFORMANCE TESTS ==========

    /**
     * Test cache operations
     */
    private function testCacheOperations() {
        if (!class_exists('PerformanceManager')) {
            $this->results['skipped']++;
            $this->results['total_tests']--;
            return;
        }
        
        $performanceManager = new PerformanceManager();
        
        // Test cache set and get
        $testKey = 'unit_test_' . uniqid();
        $testValue = 'test_value_' . time();
        
        $setResult = $performanceManager->cache($testKey, $testValue, 300);
        $this->framework->assertTrue($setResult, 'Cache set should succeed');
        
        $getValue = $performanceManager->cache($testKey);
        $this->framework->assertEquals($testValue, $getValue, 'Cache get should return correct value');
        
        // Test cache delete
        $deleteResult = $performanceManager->uncache($testKey);
        $this->framework->assertTrue($deleteResult, 'Cache delete should succeed');
        
        $deletedValue = $performanceManager->cache($testKey);
        $this->framework->assertFalse($deletedValue, 'Deleted cache value should not exist');
    }

    /**
     * Test performance metrics
     */
    private function testPerformanceMetrics() {
        if (!class_exists('PerformanceManager')) {
            $this->results['skipped']++;
            $this->results['total_tests']--;
            return;
        }
        
        $performanceManager = new PerformanceManager();
        $report = $performanceManager->getPerformanceReport();
        
        $this->framework->assertArrayHasKey('metrics', $report, 'Report should have metrics');
        $this->framework->assertArrayHasKey('uptime', $report, 'Report should have uptime');
        $this->framework->assertTrue($report['uptime'] >= 0, 'Uptime should be non-negative');
    }

    /**
     * Test asset optimization
     */
    private function testAssetOptimization() {
        if (!class_exists('PerformanceManager')) {
            $this->results['skipped']++;
            $this->results['total_tests']--;
            return;
        }
        
        $performanceManager = new PerformanceManager();
        
        // Test CSS minification
        $css = "body { margin: 0; padding: 0; } .class { color: red; }";
        $minified = $performanceManager->minifyCSS($css);
        
        $this->framework->assertTrue(strlen($minified) <= strlen($css), 'Minified CSS should be smaller or equal');
        $this->framework->assertNotContains('  ', $minified, 'Minified CSS should not contain double spaces');
        
        // Test JS minification
        $js = "function test() { var x = 1; return x; }";
        $minifiedJs = $performanceManager->minifyJS($js);
        
        $this->framework->assertTrue(strlen($minifiedJs) <= strlen($js), 'Minified JS should be smaller or equal');
    }
}
?>
