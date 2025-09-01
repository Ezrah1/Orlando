<?php
/**
 * Orlando International Resorts - Financial Management API
 * Real-time financial data, reporting, and transaction processing
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../includes/PermissionManager.php';
require_once __DIR__ . '/../includes/EventManager.php';

class FinancialAPI {
    private $db;
    private $permission_manager;
    private $event_manager;
    private $user_id;
    
    public function __construct($database_connection) {
        $this->db = $database_connection;
        $this->permission_manager = getPermissionManager();
        $this->event_manager = getEventManager();
        $this->user_id = $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get financial dashboard data
     */
    public function getFinancialDashboard() {
        $this->checkPermission('finance.view');
        
        $timeframe = $_GET['timeframe'] ?? 'today';
        $department = $_GET['department'] ?? 'all';
        
        $dashboard_data = [
            'revenue_summary' => $this->getRevenueSummary($timeframe, $department),
            'expense_summary' => $this->getExpenseSummary($timeframe, $department),
            'profit_loss' => $this->getProfitLoss($timeframe, $department),
            'cash_flow' => $this->getCashFlow($timeframe),
            'key_metrics' => $this->getKeyMetrics($timeframe),
            'recent_transactions' => $this->getRecentTransactions(10),
            'pending_payments' => $this->getPendingPayments(),
            'department_performance' => $this->getDepartmentPerformance($timeframe)
        ];
        
        $this->sendSuccess($dashboard_data);
    }
    
    /**
     * Get detailed revenue analytics
     */
    public function getRevenueAnalytics() {
        $this->checkPermission('finance.reports');
        
        $period = $_GET['period'] ?? 'month';
        $breakdown = $_GET['breakdown'] ?? 'department';
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $end_date = $_GET['end_date'] ?? date('Y-m-t');
        
        $analytics = [
            'revenue_trends' => $this->getRevenueTrends($period, $start_date, $end_date),
            'revenue_breakdown' => $this->getRevenueBreakdown($breakdown, $start_date, $end_date),
            'growth_metrics' => $this->getGrowthMetrics($period, $start_date, $end_date),
            'seasonal_analysis' => $this->getSeasonalAnalysis($start_date, $end_date),
            'forecast' => $this->generateRevenueForecast($period)
        ];
        
        $this->sendSuccess($analytics);
    }
    
    /**
     * Get transaction history with filters
     */
    public function getTransactions() {
        $this->checkPermission('finance.transactions');
        
        $filters = [
            'type' => $_GET['type'] ?? '',
            'status' => $_GET['status'] ?? '',
            'department' => $_GET['department'] ?? '',
            'amount_min' => floatval($_GET['amount_min'] ?? 0),
            'amount_max' => floatval($_GET['amount_max'] ?? 0),
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'search' => $_GET['search'] ?? '',
            'limit' => intval($_GET['limit'] ?? 50),
            'offset' => intval($_GET['offset'] ?? 0)
        ];
        
        $transactions = $this->fetchTransactions($filters);
        $total_count = $this->getTransactionCount($filters);
        $summary = $this->getTransactionSummary($filters);
        
        $this->sendSuccess([
            'transactions' => $transactions,
            'summary' => $summary,
            'pagination' => [
                'total' => $total_count,
                'limit' => $filters['limit'],
                'offset' => $filters['offset'],
                'pages' => ceil($total_count / $filters['limit'])
            ]
        ]);
    }
    
    /**
     * Create new transaction
     */
    public function createTransaction() {
        $this->checkPermission('finance.create_transaction');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['amount', 'transaction_type', 'category', 'description'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                $this->sendError("Field '$field' is required");
                return;
            }
        }
        
        // Validate transaction type
        $valid_types = ['credit', 'debit'];
        if (!in_array($input['transaction_type'], $valid_types)) {
            $this->sendError('Invalid transaction type');
            return;
        }
        
        // Create transaction
        $transaction_data = [
            'amount' => floatval($input['amount']),
            'transaction_type' => $input['transaction_type'],
            'category' => $input['category'],
            'description' => $input['description'],
            'department' => $input['department'] ?? 'general',
            'reference_type' => $input['reference_type'] ?? null,
            'reference_id' => $input['reference_id'] ?? null,
            'payment_method' => $input['payment_method'] ?? 'cash',
            'created_by' => $this->user_id,
            'status' => 'completed'
        ];
        
        $transaction_id = $this->insertTransaction($transaction_data);
        
        if ($transaction_id) {
            // Update account balances
            $this->updateAccountBalances($transaction_data);
            
            // Trigger transaction created event
            if ($this->event_manager) {
                $this->event_manager->triggerEvent('transaction.created', [
                    'transaction_id' => $transaction_id,
                    'amount' => $transaction_data['amount'],
                    'type' => $transaction_data['transaction_type'],
                    'category' => $transaction_data['category'],
                    'created_by' => $this->user_id
                ]);
            }
            
            $this->sendSuccess([
                'transaction_id' => $transaction_id,
                'reference_number' => $this->generateReferenceNumber($transaction_id)
            ], 'Transaction created successfully');
        } else {
            $this->sendError('Failed to create transaction');
        }
    }
    
    /**
     * Process payment
     */
    public function processPayment() {
        $this->checkPermission('payment.process');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['amount', 'payment_method', 'reference_type', 'reference_id'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                $this->sendError("Field '$field' is required");
                return;
            }
        }
        
        // Validate payment method
        $valid_methods = ['cash', 'card', 'bank_transfer', 'mobile_payment', 'check'];
        if (!in_array($input['payment_method'], $valid_methods)) {
            $this->sendError('Invalid payment method');
            return;
        }
        
        // Process payment based on method
        $payment_result = $this->processPaymentMethod($input);
        
        if ($payment_result['success']) {
            // Create payment record
            $payment_data = [
                'amount' => floatval($input['amount']),
                'payment_method' => $input['payment_method'],
                'reference_type' => $input['reference_type'],
                'reference_id' => $input['reference_id'],
                'status' => 'completed',
                'transaction_id' => $payment_result['transaction_id'] ?? null,
                'gateway_response' => json_encode($payment_result['gateway_data'] ?? []),
                'processed_by' => $this->user_id
            ];
            
            $payment_id = $this->insertPayment($payment_data);
            
            // Update related record (booking, invoice, etc.)
            $this->updateRelatedRecord(
                $input['reference_type'],
                $input['reference_id'],
                $payment_data['amount']
            );
            
            // Trigger payment received event
            if ($this->event_manager) {
                $this->event_manager->triggerEvent('payment.received', [
                    'payment_id' => $payment_id,
                    'amount' => $payment_data['amount'],
                    'method' => $payment_data['payment_method'],
                    'reference_type' => $input['reference_type'],
                    'reference_id' => $input['reference_id']
                ]);
            }
            
            $this->sendSuccess([
                'payment_id' => $payment_id,
                'transaction_id' => $payment_result['transaction_id'],
                'receipt_number' => $this->generateReceiptNumber($payment_id)
            ], 'Payment processed successfully');
        } else {
            // Trigger payment failed event
            if ($this->event_manager) {
                $this->event_manager->triggerEvent('payment.failed', [
                    'amount' => $input['amount'],
                    'method' => $input['payment_method'],
                    'error' => $payment_result['error'],
                    'reference_type' => $input['reference_type'],
                    'reference_id' => $input['reference_id']
                ]);
            }
            
            $this->sendError($payment_result['error']);
        }
    }
    
    /**
     * Generate financial report
     */
    public function generateReport() {
        $this->checkPermission('finance.reports');
        
        $report_type = $_GET['type'] ?? '';
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $end_date = $_GET['end_date'] ?? date('Y-m-t');
        $format = $_GET['format'] ?? 'json';
        
        switch ($report_type) {
            case 'profit_loss':
                $report_data = $this->generateProfitLossReport($start_date, $end_date);
                break;
                
            case 'cash_flow':
                $report_data = $this->generateCashFlowReport($start_date, $end_date);
                break;
                
            case 'revenue_summary':
                $report_data = $this->generateRevenueSummaryReport($start_date, $end_date);
                break;
                
            case 'expense_analysis':
                $report_data = $this->generateExpenseAnalysisReport($start_date, $end_date);
                break;
                
            case 'department_performance':
                $report_data = $this->generateDepartmentPerformanceReport($start_date, $end_date);
                break;
                
            default:
                $this->sendError('Invalid report type');
                return;
        }
        
        if ($format === 'pdf') {
            $this->generatePDFReport($report_type, $report_data);
        } elseif ($format === 'excel') {
            $this->generateExcelReport($report_type, $report_data);
        } else {
            $this->sendSuccess($report_data);
        }
    }
    
    /**
     * Get accounts overview
     */
    public function getAccountsOverview() {
        $this->checkPermission('finance.accounts');
        
        $accounts = [
            'cash_accounts' => $this->getCashAccounts(),
            'bank_accounts' => $this->getBankAccounts(),
            'receivables' => $this->getAccountsReceivable(),
            'payables' => $this->getAccountsPayable(),
            'total_assets' => $this->getTotalAssets(),
            'total_liabilities' => $this->getTotalLiabilities()
        ];
        
        $this->sendSuccess($accounts);
    }
    
    /**
     * Update account balance
     */
    public function updateAccountBalance() {
        $this->checkPermission('finance.accounts_modify');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $account_id = intval($input['account_id'] ?? 0);
        $amount = floatval($input['amount'] ?? 0);
        $type = $input['type'] ?? ''; // 'credit' or 'debit'
        $description = $input['description'] ?? '';
        
        if (!$account_id || !$amount || !$type) {
            $this->sendError('Account ID, amount, and type are required');
            return;
        }
        
        // Get current account balance
        $current_balance = $this->getAccountBalance($account_id);
        
        // Calculate new balance
        $new_balance = $type === 'credit' ? 
            $current_balance + $amount : 
            $current_balance - $amount;
        
        // Update account balance
        $success = $this->setAccountBalance($account_id, $new_balance);
        
        if ($success) {
            // Create audit trail
            $this->createAccountAudit($account_id, $amount, $type, $description);
            
            $this->sendSuccess([
                'account_id' => $account_id,
                'previous_balance' => $current_balance,
                'new_balance' => $new_balance,
                'change_amount' => $amount,
                'change_type' => $type
            ], 'Account balance updated successfully');
        } else {
            $this->sendError('Failed to update account balance');
        }
    }
    
    /**
     * Get budget vs actual comparison
     */
    public function getBudgetComparison() {
        $this->checkPermission('finance.budget');
        
        $period = $_GET['period'] ?? 'month';
        $department = $_GET['department'] ?? 'all';
        
        $comparison = [
            'budget_data' => $this->getBudgetData($period, $department),
            'actual_data' => $this->getActualData($period, $department),
            'variance_analysis' => $this->getVarianceAnalysis($period, $department),
            'forecasts' => $this->getBudgetForecasts($period, $department)
        ];
        
        $this->sendSuccess($comparison);
    }
    
    /**
     * Process expense claim
     */
    public function processExpenseClaim() {
        $this->checkPermission('expense.create');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['amount', 'category', 'description', 'expense_date'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                $this->sendError("Field '$field' is required");
                return;
            }
        }
        
        // Create expense claim
        $expense_data = [
            'amount' => floatval($input['amount']),
            'category' => $input['category'],
            'description' => $input['description'],
            'expense_date' => $input['expense_date'],
            'department' => $input['department'] ?? 'general',
            'receipt_reference' => $input['receipt_reference'] ?? '',
            'claimed_by' => $this->user_id,
            'status' => 'pending'
        ];
        
        $expense_id = $this->insertExpenseClaim($expense_data);
        
        if ($expense_id) {
            // Trigger expense claim created event
            if ($this->event_manager) {
                $this->event_manager->triggerEvent('expense.claim_created', [
                    'expense_id' => $expense_id,
                    'amount' => $expense_data['amount'],
                    'category' => $expense_data['category'],
                    'claimed_by' => $this->user_id
                ]);
            }
            
            $this->sendSuccess([
                'expense_id' => $expense_id,
                'claim_number' => $this->generateClaimNumber($expense_id)
            ], 'Expense claim submitted successfully');
        } else {
            $this->sendError('Failed to submit expense claim');
        }
    }
    
    // Helper Methods
    
    private function getRevenueSummary($timeframe, $department) {
        $date_condition = $this->getDateCondition($timeframe);
        $dept_condition = $department !== 'all' ? "AND department = '$department'" : '';
        
        $query = "SELECT 
            SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as total_revenue,
            COUNT(CASE WHEN transaction_type = 'credit' THEN 1 END) as transaction_count,
            AVG(CASE WHEN transaction_type = 'credit' THEN amount ELSE NULL END) as avg_transaction
            FROM transactions 
            WHERE status = 'completed' $date_condition $dept_condition";
        
        $result = $this->db->query($query);
        return $result->fetch_assoc();
    }
    
    private function getExpenseSummary($timeframe, $department) {
        $date_condition = $this->getDateCondition($timeframe);
        $dept_condition = $department !== 'all' ? "AND department = '$department'" : '';
        
        $query = "SELECT 
            SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as total_expenses,
            COUNT(CASE WHEN transaction_type = 'debit' THEN 1 END) as transaction_count,
            AVG(CASE WHEN transaction_type = 'debit' THEN amount ELSE NULL END) as avg_expense
            FROM transactions 
            WHERE status = 'completed' $date_condition $dept_condition";
        
        $result = $this->db->query($query);
        return $result->fetch_assoc();
    }
    
    private function getDateCondition($timeframe) {
        switch ($timeframe) {
            case 'today':
                return "AND DATE(created_at) = CURDATE()";
            case 'week':
                return "AND WEEK(created_at, 1) = WEEK(CURDATE(), 1)";
            case 'month':
                return "AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
            case 'year':
                return "AND YEAR(created_at) = YEAR(CURDATE())";
            default:
                return "";
        }
    }
    
    private function fetchTransactions($filters) {
        $where_conditions = ["status = 'completed'"];
        $params = [];
        $param_types = "";
        
        if ($filters['type']) {
            $where_conditions[] = "transaction_type = ?";
            $params[] = $filters['type'];
            $param_types .= "s";
        }
        
        if ($filters['department']) {
            $where_conditions[] = "department = ?";
            $params[] = $filters['department'];
            $param_types .= "s";
        }
        
        if ($filters['amount_min'] > 0) {
            $where_conditions[] = "amount >= ?";
            $params[] = $filters['amount_min'];
            $param_types .= "d";
        }
        
        if ($filters['amount_max'] > 0) {
            $where_conditions[] = "amount <= ?";
            $params[] = $filters['amount_max'];
            $param_types .= "d";
        }
        
        if ($filters['date_from']) {
            $where_conditions[] = "DATE(created_at) >= ?";
            $params[] = $filters['date_from'];
            $param_types .= "s";
        }
        
        if ($filters['date_to']) {
            $where_conditions[] = "DATE(created_at) <= ?";
            $params[] = $filters['date_to'];
            $param_types .= "s";
        }
        
        if ($filters['search']) {
            $where_conditions[] = "(description LIKE ? OR reference_number LIKE ?)";
            $search_term = "%" . $filters['search'] . "%";
            $params[] = $search_term;
            $params[] = $search_term;
            $param_types .= "ss";
        }
        
        $where_clause = implode(" AND ", $where_conditions);
        
        $query = "SELECT 
            t.*,
            u.full_name as created_by_name
            FROM transactions t
            LEFT JOIN users u ON t.created_by = u.id
            WHERE $where_clause
            ORDER BY t.created_at DESC
            LIMIT ? OFFSET ?";
        
        $params[] = $filters['limit'];
        $params[] = $filters['offset'];
        $param_types .= "ii";
        
        $stmt = $this->db->prepare($query);
        if ($param_types) {
            $stmt->bind_param($param_types, ...$params);
        }
        $stmt->execute();
        
        $result = $stmt->get_result();
        $transactions = [];
        
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        
        return $transactions;
    }
    
    private function checkPermission($permission) {
        if (!$this->permission_manager || !$this->permission_manager->hasPermission($this->user_id, $permission)) {
            $this->sendError('Permission denied', 403);
            exit();
        }
    }
    
    private function sendSuccess($data, $message = 'Success') {
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ]);
    }
    
    private function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'code' => $code,
            'timestamp' => time()
        ]);
    }
}

// Handle API requests
try {
    ensure_logged_in();
    
    $api = new FinancialAPI($con);
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'dashboard':
            $api->getFinancialDashboard();
            break;
            
        case 'analytics':
            $api->getRevenueAnalytics();
            break;
            
        case 'transactions':
            $api->getTransactions();
            break;
            
        case 'create_transaction':
            $api->createTransaction();
            break;
            
        case 'process_payment':
            $api->processPayment();
            break;
            
        case 'generate_report':
            $api->generateReport();
            break;
            
        case 'accounts':
            $api->getAccountsOverview();
            break;
            
        case 'update_balance':
            $api->updateAccountBalance();
            break;
            
        case 'budget_comparison':
            $api->getBudgetComparison();
            break;
            
        case 'expense_claim':
            $api->processExpenseClaim();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("Financial API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
