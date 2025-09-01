<?php
/**
 * Orlando International Resorts - Comprehensive Testing Framework
 * Automated testing suite with unit tests, integration tests, and quality assurance
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

class TestingFramework {
    private $config;
    private $logger;
    private $testResults;
    private $testSuites;
    private $coverage;
    private $performance;

    public function __construct() {
        $this->config = $this->loadConfiguration();
        $this->initializeLogger();
        $this->initializeTestSuites();
        $this->testResults = [];
        $this->coverage = [];
        $this->performance = [];
    }

    /**
     * Load testing configuration
     */
    private function loadConfiguration() {
        return [
            'test_environment' => 'testing',
            'database' => [
                'host' => 'localhost',
                'username' => 'root',
                'password' => '',
                'database' => 'hotel_test',
                'reset_before_tests' => true
            ],
            'test_data' => [
                'generate_sample_data' => true,
                'sample_users' => 50,
                'sample_bookings' => 100,
                'sample_orders' => 200
            ],
            'coverage' => [
                'enabled' => true,
                'minimum_threshold' => 80,
                'exclude_files' => [
                    'vendor/*',
                    'tests/*',
                    'logs/*',
                    'cache/*'
                ]
            ],
            'performance' => [
                'enabled' => true,
                'max_execution_time' => 5000, // milliseconds
                'memory_limit' => '256M',
                'profiling' => true
            ],
            'notifications' => [
                'email_on_failure' => true,
                'slack_webhook' => null,
                'recipients' => ['dev@orlandoresorts.com']
            ]
        ];
    }

    /**
     * Initialize test suites
     */
    private function initializeTestSuites() {
        $this->testSuites = [
            'unit' => new UnitTestSuite($this),
            'integration' => new IntegrationTestSuite($this),
            'functional' => new FunctionalTestSuite($this),
            'performance' => new PerformanceTestSuite($this),
            'security' => new SecurityTestSuite($this),
            'api' => new APITestSuite($this),
            'ui' => new UITestSuite($this)
        ];
    }

    /**
     * Run all test suites
     */
    public function runAllTests($suites = null) {
        $this->log("Starting comprehensive test execution");
        
        $suitesToRun = $suites ?: array_keys($this->testSuites);
        $overallResults = [
            'total_tests' => 0,
            'passed' => 0,
            'failed' => 0,
            'skipped' => 0,
            'execution_time' => 0,
            'memory_usage' => 0,
            'coverage' => 0,
            'suite_results' => []
        ];

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Setup test environment
        $this->setupTestEnvironment();

        foreach ($suitesToRun as $suiteName) {
            if (!isset($this->testSuites[$suiteName])) {
                $this->log("Test suite '{$suiteName}' not found", 'warning');
                continue;
            }

            $this->log("Running {$suiteName} test suite");
            
            try {
                $suiteResults = $this->testSuites[$suiteName]->run();
                $overallResults['suite_results'][$suiteName] = $suiteResults;
                
                $overallResults['total_tests'] += $suiteResults['total_tests'];
                $overallResults['passed'] += $suiteResults['passed'];
                $overallResults['failed'] += $suiteResults['failed'];
                $overallResults['skipped'] += $suiteResults['skipped'];
                
                $this->log("Completed {$suiteName} test suite: {$suiteResults['passed']}/{$suiteResults['total_tests']} passed");
                
            } catch (Exception $e) {
                $this->log("Error running {$suiteName} test suite: " . $e->getMessage(), 'error');
                $overallResults['suite_results'][$suiteName] = [
                    'error' => $e->getMessage(),
                    'total_tests' => 0,
                    'passed' => 0,
                    'failed' => 1,
                    'skipped' => 0
                ];
                $overallResults['failed']++;
            }
        }

        $endTime = microtime(true);
        $endMemory = memory_get_peak_usage(true);

        $overallResults['execution_time'] = round(($endTime - $startTime) * 1000, 2);
        $overallResults['memory_usage'] = $endMemory - $startMemory;
        $overallResults['coverage'] = $this->calculateCoverage();
        $overallResults['timestamp'] = date('Y-m-d H:i:s');

        // Cleanup test environment
        $this->cleanupTestEnvironment();

        // Store results
        $this->storeTestResults($overallResults);

        // Send notifications if needed
        if ($overallResults['failed'] > 0) {
            $this->sendFailureNotifications($overallResults);
        }

        $this->log("Test execution completed: {$overallResults['passed']}/{$overallResults['total_tests']} tests passed");

        return $overallResults;
    }

    /**
     * Setup test environment
     */
    private function setupTestEnvironment() {
        // Set test environment variables
        $_ENV['TESTING'] = true;
        $_ENV['TEST_DATABASE'] = $this->config['database']['database'];

        // Setup test database
        if ($this->config['database']['reset_before_tests']) {
            $this->setupTestDatabase();
        }

        // Generate test data
        if ($this->config['test_data']['generate_sample_data']) {
            $this->generateTestData();
        }

        // Start code coverage if enabled
        if ($this->config['coverage']['enabled'] && extension_loaded('xdebug')) {
            xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
        }
    }

    /**
     * Setup test database
     */
    private function setupTestDatabase() {
        $config = $this->config['database'];
        
        try {
            // Connect to MySQL server
            $pdo = new PDO(
                "mysql:host={$config['host']}",
                $config['username'],
                $config['password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Drop and recreate test database
            $pdo->exec("DROP DATABASE IF EXISTS `{$config['database']}`");
            $pdo->exec("CREATE DATABASE `{$config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$config['database']}`");

            // Import main database schema
            $this->importDatabaseSchema($pdo);

            $this->log("Test database setup completed");

        } catch (Exception $e) {
            throw new Exception("Failed to setup test database: " . $e->getMessage());
        }
    }

    /**
     * Import database schema
     */
    private function importDatabaseSchema($pdo) {
        $schemaFiles = [
            __DIR__ . '/../database/hotel_schema.sql',
            __DIR__ . '/../database/payment_system_schema.sql',
            __DIR__ . '/../database/performance_monitoring_schema.sql'
        ];

        foreach ($schemaFiles as $schemaFile) {
            if (file_exists($schemaFile)) {
                $sql = file_get_contents($schemaFile);
                
                // Split SQL into individual statements
                $statements = array_filter(
                    array_map('trim', explode(';', $sql)),
                    function($stmt) { return !empty($stmt) && !preg_match('/^(--|#)/', $stmt); }
                );

                foreach ($statements as $statement) {
                    try {
                        $pdo->exec($statement);
                    } catch (PDOException $e) {
                        // Log but continue - some statements might already exist
                        $this->log("Schema warning: " . $e->getMessage(), 'warning');
                    }
                }
            }
        }
    }

    /**
     * Generate test data
     */
    private function generateTestData() {
        $generator = new TestDataGenerator($this->config['database']);
        
        $generator->generateUsers($this->config['test_data']['sample_users']);
        $generator->generateRooms(20);
        $generator->generateBookings($this->config['test_data']['sample_bookings']);
        $generator->generateMenuItems(50);
        $generator->generateOrders($this->config['test_data']['sample_orders']);
        
        $this->log("Test data generation completed");
    }

    /**
     * Calculate code coverage
     */
    private function calculateCoverage() {
        if (!$this->config['coverage']['enabled'] || !extension_loaded('xdebug')) {
            return 0;
        }

        $coverage = xdebug_get_code_coverage();
        xdebug_stop_code_coverage();

        if (empty($coverage)) {
            return 0;
        }

        $totalLines = 0;
        $coveredLines = 0;

        foreach ($coverage as $file => $lines) {
            // Skip excluded files
            $skip = false;
            foreach ($this->config['coverage']['exclude_files'] as $pattern) {
                if (fnmatch($pattern, $file)) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) continue;

            foreach ($lines as $line => $status) {
                $totalLines++;
                if ($status > 0) {
                    $coveredLines++;
                }
            }
        }

        $coveragePercentage = $totalLines > 0 ? ($coveredLines / $totalLines) * 100 : 0;
        
        $this->coverage = [
            'percentage' => round($coveragePercentage, 2),
            'total_lines' => $totalLines,
            'covered_lines' => $coveredLines,
            'files_analyzed' => count($coverage)
        ];

        return $this->coverage['percentage'];
    }

    /**
     * Cleanup test environment
     */
    private function cleanupTestEnvironment() {
        // Remove test environment variables
        unset($_ENV['TESTING'], $_ENV['TEST_DATABASE']);

        // Clean temporary files
        $tempDir = __DIR__ . '/../../temp/tests';
        if (is_dir($tempDir)) {
            $this->recursiveRemoveDirectory($tempDir);
        }

        $this->log("Test environment cleanup completed");
    }

    /**
     * Store test results in database
     */
    private function storeTestResults($results) {
        try {
            global $con;
            
            $stmt = $con->prepare("
                INSERT INTO test_executions (
                    execution_id, total_tests, passed_tests, failed_tests, 
                    skipped_tests, execution_time, memory_usage, coverage_percentage,
                    results_data, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $executionId = 'test_' . date('Ymd_His') . '_' . uniqid();
            $resultsJson = json_encode($results);

            $stmt->bind_param(
                'siiiidids',
                $executionId,
                $results['total_tests'],
                $results['passed'],
                $results['failed'],
                $results['skipped'],
                $results['execution_time'],
                $results['memory_usage'],
                $results['coverage'],
                $resultsJson
            );

            $stmt->execute();
            
            $this->log("Test results stored with ID: {$executionId}");
            
        } catch (Exception $e) {
            $this->log("Failed to store test results: " . $e->getMessage(), 'error');
        }
    }

    /**
     * Send failure notifications
     */
    private function sendFailureNotifications($results) {
        if (!$this->config['notifications']['email_on_failure']) {
            return;
        }

        $subject = "Test Failures Detected - Orlando Resorts";
        $message = $this->generateFailureReport($results);

        foreach ($this->config['notifications']['recipients'] as $recipient) {
            mail($recipient, $subject, $message, "Content-Type: text/html; charset=UTF-8");
        }

        $this->log("Failure notifications sent");
    }

    /**
     * Generate failure report
     */
    private function generateFailureReport($results) {
        $html = "<h2>Test Execution Report</h2>";
        $html .= "<p><strong>Status:</strong> <span style='color: red;'>FAILED</span></p>";
        $html .= "<p><strong>Timestamp:</strong> {$results['timestamp']}</p>";
        $html .= "<p><strong>Summary:</strong> {$results['passed']}/{$results['total_tests']} tests passed</p>";
        $html .= "<p><strong>Failed Tests:</strong> {$results['failed']}</p>";
        $html .= "<p><strong>Execution Time:</strong> {$results['execution_time']}ms</p>";
        $html .= "<p><strong>Code Coverage:</strong> {$results['coverage']}%</p>";

        $html .= "<h3>Suite Results:</h3>";
        $html .= "<table border='1' style='border-collapse: collapse;'>";
        $html .= "<tr><th>Suite</th><th>Total</th><th>Passed</th><th>Failed</th><th>Skipped</th></tr>";

        foreach ($results['suite_results'] as $suite => $suiteResult) {
            $html .= "<tr>";
            $html .= "<td>{$suite}</td>";
            $html .= "<td>{$suiteResult['total_tests']}</td>";
            $html .= "<td style='color: green;'>{$suiteResult['passed']}</td>";
            $html .= "<td style='color: red;'>{$suiteResult['failed']}</td>";
            $html .= "<td style='color: orange;'>{$suiteResult['skipped']}</td>";
            $html .= "</tr>";
        }

        $html .= "</table>";
        
        return $html;
    }

    /**
     * Run specific test suite
     */
    public function runTestSuite($suiteName) {
        if (!isset($this->testSuites[$suiteName])) {
            throw new Exception("Test suite '{$suiteName}' not found");
        }

        $this->setupTestEnvironment();
        
        try {
            $results = $this->testSuites[$suiteName]->run();
            $this->cleanupTestEnvironment();
            return $results;
        } catch (Exception $e) {
            $this->cleanupTestEnvironment();
            throw $e;
        }
    }

    /**
     * Get test execution history
     */
    public function getTestHistory($limit = 50) {
        global $con;
        
        $stmt = $con->prepare("
            SELECT execution_id, total_tests, passed_tests, failed_tests, 
                   execution_time, coverage_percentage, created_at
            FROM test_executions 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $history = [];
        
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        
        return $history;
    }

    /**
     * Generate test report
     */
    public function generateTestReport($executionId = null) {
        global $con;
        
        if ($executionId) {
            $stmt = $con->prepare("SELECT * FROM test_executions WHERE execution_id = ?");
            $stmt->bind_param('s', $executionId);
        } else {
            $stmt = $con->prepare("SELECT * FROM test_executions ORDER BY created_at DESC LIMIT 1");
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $execution = $result->fetch_assoc();
        
        if (!$execution) {
            throw new Exception("Test execution not found");
        }
        
        $results = json_decode($execution['results_data'], true);
        
        return [
            'execution' => $execution,
            'results' => $results,
            'report_html' => $this->generateHTMLReport($execution, $results)
        ];
    }

    /**
     * Generate HTML test report
     */
    private function generateHTMLReport($execution, $results) {
        ob_start();
        include __DIR__ . '/../templates/test_report.html';
        return ob_get_clean();
    }

    /**
     * Recursive directory removal
     */
    private function recursiveRemoveDirectory($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $this->recursiveRemoveDirectory($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }

    /**
     * Initialize logger
     */
    private function initializeLogger() {
        $this->logger = [
            'file' => __DIR__ . '/../../logs/testing.log'
        ];
        
        $logDir = dirname($this->logger['file']);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Log message
     */
    private function log($message, $level = 'info') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        file_put_contents($this->logger['file'], $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get configuration
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * Assert methods for testing
     */
    public function assertTrue($condition, $message = '') {
        if (!$condition) {
            throw new AssertionException($message ?: 'Assertion failed: expected true');
        }
    }

    public function assertFalse($condition, $message = '') {
        if ($condition) {
            throw new AssertionException($message ?: 'Assertion failed: expected false');
        }
    }

    public function assertEquals($expected, $actual, $message = '') {
        if ($expected !== $actual) {
            throw new AssertionException($message ?: "Assertion failed: expected '{$expected}', got '{$actual}'");
        }
    }

    public function assertNotEquals($expected, $actual, $message = '') {
        if ($expected === $actual) {
            throw new AssertionException($message ?: "Assertion failed: expected not '{$expected}'");
        }
    }

    public function assertNull($value, $message = '') {
        if ($value !== null) {
            throw new AssertionException($message ?: 'Assertion failed: expected null');
        }
    }

    public function assertNotNull($value, $message = '') {
        if ($value === null) {
            throw new AssertionException($message ?: 'Assertion failed: expected not null');
        }
    }

    public function assertArrayHasKey($key, $array, $message = '') {
        if (!array_key_exists($key, $array)) {
            throw new AssertionException($message ?: "Assertion failed: array does not have key '{$key}'");
        }
    }

    public function assertContains($needle, $haystack, $message = '') {
        if (is_array($haystack)) {
            if (!in_array($needle, $haystack)) {
                throw new AssertionException($message ?: "Assertion failed: array does not contain '{$needle}'");
            }
        } else {
            if (strpos($haystack, $needle) === false) {
                throw new AssertionException($message ?: "Assertion failed: string does not contain '{$needle}'");
            }
        }
    }
}

/**
 * Custom exception for test assertions
 */
class AssertionException extends Exception {}

/**
 * Test data generator
 */
class TestDataGenerator {
    private $dbConfig;
    private $pdo;

    public function __construct($dbConfig) {
        $this->dbConfig = $dbConfig;
        $this->connectToDatabase();
    }

    private function connectToDatabase() {
        $this->pdo = new PDO(
            "mysql:host={$this->dbConfig['host']};dbname={$this->dbConfig['database']}",
            $this->dbConfig['username'],
            $this->dbConfig['password']
        );
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function generateUsers($count) {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (name, email, phone, country, password, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        for ($i = 1; $i <= $count; $i++) {
            $stmt->execute([
                "Test User {$i}",
                "testuser{$i}@example.com",
                "+1234567" . str_pad($i, 4, '0', STR_PAD_LEFT),
                "Test Country",
                password_hash('password123', PASSWORD_DEFAULT)
            ]);
        }
    }

    public function generateRooms($count) {
        $types = ['Superior Room', 'Deluxe Room', 'Family Room', 'Suite'];
        $bedding = ['Single', 'Double', 'Queen', 'King'];
        
        $stmt = $this->pdo->prepare("
            INSERT INTO room (type, bedding, place, price) 
            VALUES (?, ?, ?, ?)
        ");

        for ($i = 1; $i <= $count; $i++) {
            $stmt->execute([
                $types[array_rand($types)],
                $bedding[array_rand($bedding)],
                "Room {$i}",
                rand(100, 500)
            ]);
        }
    }

    public function generateBookings($count) {
        $stmt = $this->pdo->prepare("
            INSERT INTO roombook (name, email, phone, country, room, cin, cout, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        for ($i = 1; $i <= $count; $i++) {
            $checkin = date('Y-m-d', strtotime("+{$i} days"));
            $checkout = date('Y-m-d', strtotime("+{$i} days +3 days"));
            
            $stmt->execute([
                "Test Booking {$i}",
                "booking{$i}@example.com",
                "+1234567" . str_pad($i, 4, '0', STR_PAD_LEFT),
                "Test Country",
                rand(1, 20),
                $checkin,
                $checkout
            ]);
        }
    }

    public function generateMenuItems($count) {
        $categories = ['Appetizers', 'Main Course', 'Desserts', 'Beverages'];
        
        $stmt = $this->pdo->prepare("
            INSERT INTO menu_items (name, description, price, category_id, available) 
            VALUES (?, ?, ?, ?, 1)
        ");

        for ($i = 1; $i <= $count; $i++) {
            $stmt->execute([
                "Test Menu Item {$i}",
                "Delicious test item {$i} description",
                rand(10, 50),
                rand(1, 4)
            ]);
        }
    }

    public function generateOrders($count) {
        $stmt = $this->pdo->prepare("
            INSERT INTO restaurant_orders (customer_name, customer_email, total_amount, status, created_at) 
            VALUES (?, ?, ?, 'completed', NOW())
        ");

        for ($i = 1; $i <= $count; $i++) {
            $stmt->execute([
                "Test Order Customer {$i}",
                "order{$i}@example.com",
                rand(20, 100)
            ]);
        }
    }
}
?>
