<?php
/**
 * Orlando International Resorts - Data Import/Export Manager
 * Comprehensive system for importing and exporting data in various formats
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

class DataImportExportManager {
    private $config;
    private $supportedFormats;
    private $logger;
    private $validationRules;
    private $transformationRules;

    public function __construct() {
        $this->config = $this->loadConfiguration();
        $this->supportedFormats = ['csv', 'excel', 'json', 'xml', 'pdf'];
        $this->initializeLogger();
        $this->initializeValidationRules();
        $this->initializeTransformationRules();
    }

    /**
     * Load configuration
     */
    private function loadConfiguration() {
        return [
            'max_file_size' => 50 * 1024 * 1024, // 50MB
            'chunk_size' => 1000, // Process in chunks of 1000 records
            'timeout' => 300, // 5 minutes
            'temp_directory' => __DIR__ . '/../../temp/imports',
            'export_directory' => __DIR__ . '/../../exports',
            'backup_directory' => __DIR__ . '/../../backups',
            'allowed_extensions' => ['csv', 'xlsx', 'xls', 'json', 'xml'],
            'date_formats' => ['Y-m-d', 'Y-m-d H:i:s', 'm/d/Y', 'd/m/Y'],
            'encoding' => 'UTF-8'
        ];
    }

    /**
     * Import data from file
     */
    public function importData($filePath, $dataType, $options = []) {
        try {
            $this->log("Starting import for {$dataType} from {$filePath}");
            
            // Validate file
            $this->validateFile($filePath);
            
            // Detect format
            $format = $this->detectFormat($filePath);
            
            // Parse data
            $data = $this->parseFile($filePath, $format, $options);
            
            // Validate data
            $validationResult = $this->validateData($data, $dataType);
            
            if (!$validationResult['valid']) {
                throw new Exception('Data validation failed: ' . implode(', ', $validationResult['errors']));
            }
            
            // Transform data
            $transformedData = $this->transformData($data, $dataType, $options);
            
            // Import to database
            $importResult = $this->importToDatabase($transformedData, $dataType, $options);
            
            $this->log("Import completed successfully for {$dataType}");
            
            return [
                'success' => true,
                'imported_count' => $importResult['imported'],
                'updated_count' => $importResult['updated'],
                'errors' => $importResult['errors'],
                'warnings' => $validationResult['warnings'],
                'total_processed' => count($data)
            ];
            
        } catch (Exception $e) {
            $this->log("Import failed for {$dataType}: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Export data to file
     */
    public function exportData($dataType, $format, $options = []) {
        try {
            $this->log("Starting export for {$dataType} in {$format} format");
            
            // Get data from database
            $data = $this->getDataFromDatabase($dataType, $options);
            
            // Transform data for export
            $transformedData = $this->transformDataForExport($data, $dataType, $options);
            
            // Generate filename
            $filename = $this->generateExportFilename($dataType, $format, $options);
            $filePath = $this->config['export_directory'] . '/' . $filename;
            
            // Ensure export directory exists
            if (!is_dir($this->config['export_directory'])) {
                mkdir($this->config['export_directory'], 0755, true);
            }
            
            // Export based on format
            switch ($format) {
                case 'csv':
                    $this->exportToCSV($transformedData, $filePath, $options);
                    break;
                case 'excel':
                    $this->exportToExcel($transformedData, $filePath, $options);
                    break;
                case 'json':
                    $this->exportToJSON($transformedData, $filePath, $options);
                    break;
                case 'xml':
                    $this->exportToXML($transformedData, $filePath, $options);
                    break;
                case 'pdf':
                    $this->exportToPDF($transformedData, $filePath, $options);
                    break;
                default:
                    throw new Exception("Unsupported export format: {$format}");
            }
            
            $this->log("Export completed successfully for {$dataType}");
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filePath,
                'record_count' => count($transformedData),
                'file_size' => filesize($filePath)
            ];
            
        } catch (Exception $e) {
            $this->log("Export failed for {$dataType}: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Create database backup
     */
    public function createBackup($tables = null, $options = []) {
        try {
            global $con;
            
            $this->log("Starting database backup");
            
            // Get tables to backup
            if ($tables === null) {
                $tables = $this->getAllTables();
            }
            
            $filename = $this->generateBackupFilename($options);
            $backupPath = $this->config['backup_directory'] . '/' . $filename;
            
            // Ensure backup directory exists
            if (!is_dir($this->config['backup_directory'])) {
                mkdir($this->config['backup_directory'], 0755, true);
            }
            
            $sql = "-- Orlando International Resorts Database Backup\n";
            $sql .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- Database: " . mysqli_get_server_info($con) . "\n\n";
            
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
            $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
            $sql .= "SET AUTOCOMMIT = 0;\n";
            $sql .= "START TRANSACTION;\n\n";
            
            foreach ($tables as $table) {
                $sql .= $this->exportTableStructure($table);
                $sql .= $this->exportTableData($table, $options);
            }
            
            $sql .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
            $sql .= "COMMIT;\n";
            
            file_put_contents($backupPath, $sql);
            
            // Compress if requested
            if ($options['compress'] ?? false) {
                $compressedPath = $backupPath . '.gz';
                $this->compressFile($backupPath, $compressedPath);
                unlink($backupPath);
                $backupPath = $compressedPath;
                $filename .= '.gz';
            }
            
            $this->log("Database backup completed");
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $backupPath,
                'tables_backed_up' => count($tables),
                'file_size' => filesize($backupPath)
            ];
            
        } catch (Exception $e) {
            $this->log("Database backup failed: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Restore database from backup
     */
    public function restoreBackup($backupPath, $options = []) {
        try {
            global $con;
            
            $this->log("Starting database restore from {$backupPath}");
            
            if (!file_exists($backupPath)) {
                throw new Exception("Backup file not found: {$backupPath}");
            }
            
            // Decompress if needed
            if (pathinfo($backupPath, PATHINFO_EXTENSION) === 'gz') {
                $tempPath = $this->config['temp_directory'] . '/' . uniqid() . '.sql';
                $this->decompressFile($backupPath, $tempPath);
                $sqlContent = file_get_contents($tempPath);
                unlink($tempPath);
            } else {
                $sqlContent = file_get_contents($backupPath);
            }
            
            // Execute SQL
            $queries = explode(";\n", $sqlContent);
            $executed = 0;
            $errors = [];
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query) && !preg_match('/^(--|#)/', $query)) {
                    if (mysqli_query($con, $query)) {
                        $executed++;
                    } else {
                        $errors[] = mysqli_error($con);
                        if (!($options['continue_on_error'] ?? false)) {
                            throw new Exception("SQL error: " . mysqli_error($con));
                        }
                    }
                }
            }
            
            $this->log("Database restore completed");
            
            return [
                'success' => true,
                'queries_executed' => $executed,
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            $this->log("Database restore failed: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Validate uploaded file
     */
    private function validateFile($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }
        
        $fileSize = filesize($filePath);
        if ($fileSize > $this->config['max_file_size']) {
            throw new Exception("File size exceeds maximum allowed size");
        }
        
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->config['allowed_extensions'])) {
            throw new Exception("File extension not allowed: {$extension}");
        }
        
        // Check for malicious content
        $this->scanForMaliciousContent($filePath);
    }

    /**
     * Detect file format
     */
    private function detectFormat($filePath) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $formatMap = [
            'csv' => 'csv',
            'xlsx' => 'excel',
            'xls' => 'excel',
            'json' => 'json',
            'xml' => 'xml'
        ];
        
        return $formatMap[$extension] ?? 'csv';
    }

    /**
     * Parse file based on format
     */
    private function parseFile($filePath, $format, $options = []) {
        switch ($format) {
            case 'csv':
                return $this->parseCSV($filePath, $options);
            case 'excel':
                return $this->parseExcel($filePath, $options);
            case 'json':
                return $this->parseJSON($filePath, $options);
            case 'xml':
                return $this->parseXML($filePath, $options);
            default:
                throw new Exception("Unsupported format: {$format}");
        }
    }

    /**
     * Parse CSV file
     */
    private function parseCSV($filePath, $options = []) {
        $data = [];
        $delimiter = $options['delimiter'] ?? ',';
        $enclosure = $options['enclosure'] ?? '"';
        $escape = $options['escape'] ?? '\\';
        $skipRows = $options['skip_rows'] ?? 0;
        $hasHeader = $options['has_header'] ?? true;
        
        if (($handle = fopen($filePath, 'r')) !== false) {
            $rowIndex = 0;
            $headers = [];
            
            while (($row = fgetcsv($handle, 0, $delimiter, $enclosure, $escape)) !== false) {
                if ($rowIndex < $skipRows) {
                    $rowIndex++;
                    continue;
                }
                
                if ($hasHeader && $rowIndex === $skipRows) {
                    $headers = $row;
                } else {
                    if ($hasHeader && !empty($headers)) {
                        $data[] = array_combine($headers, $row);
                    } else {
                        $data[] = $row;
                    }
                }
                
                $rowIndex++;
            }
            
            fclose($handle);
        }
        
        return $data;
    }

    /**
     * Parse Excel file
     */
    private function parseExcel($filePath, $options = []) {
        // This would require a library like PhpSpreadsheet
        // For now, return a placeholder implementation
        throw new Exception("Excel parsing requires PhpSpreadsheet library");
    }

    /**
     * Parse JSON file
     */
    private function parseJSON($filePath, $options = []) {
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON: " . json_last_error_msg());
        }
        
        return $data;
    }

    /**
     * Parse XML file
     */
    private function parseXML($filePath, $options = []) {
        $content = file_get_contents($filePath);
        $xml = simplexml_load_string($content);
        
        if ($xml === false) {
            throw new Exception("Invalid XML format");
        }
        
        return json_decode(json_encode($xml), true);
    }

    /**
     * Export to CSV
     */
    private function exportToCSV($data, $filePath, $options = []) {
        $delimiter = $options['delimiter'] ?? ',';
        $enclosure = $options['enclosure'] ?? '"';
        $includeHeaders = $options['include_headers'] ?? true;
        
        if (($handle = fopen($filePath, 'w')) !== false) {
            if ($includeHeaders && !empty($data)) {
                fputcsv($handle, array_keys($data[0]), $delimiter, $enclosure);
            }
            
            foreach ($data as $row) {
                fputcsv($handle, $row, $delimiter, $enclosure);
            }
            
            fclose($handle);
        } else {
            throw new Exception("Cannot create export file: {$filePath}");
        }
    }

    /**
     * Export to JSON
     */
    private function exportToJSON($data, $filePath, $options = []) {
        $jsonOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;
        
        if ($options['minify'] ?? false) {
            $jsonOptions = JSON_UNESCAPED_UNICODE;
        }
        
        $json = json_encode($data, $jsonOptions);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON encoding error: " . json_last_error_msg());
        }
        
        file_put_contents($filePath, $json);
    }

    /**
     * Export to XML
     */
    private function exportToXML($data, $filePath, $options = []) {
        $rootElement = $options['root_element'] ?? 'data';
        $itemElement = $options['item_element'] ?? 'item';
        
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        $root = $xml->createElement($rootElement);
        $xml->appendChild($root);
        
        foreach ($data as $row) {
            $item = $xml->createElement($itemElement);
            
            foreach ($row as $key => $value) {
                $element = $xml->createElement($this->sanitizeXMLElementName($key), htmlspecialchars($value));
                $item->appendChild($element);
            }
            
            $root->appendChild($item);
        }
        
        $xml->save($filePath);
    }

    /**
     * Export to PDF (basic implementation)
     */
    private function exportToPDF($data, $filePath, $options = []) {
        // This would require a PDF library like TCPDF or FPDF
        // For now, create a simple HTML file that can be converted to PDF
        $html = $this->generateHTMLTable($data, $options);
        file_put_contents(str_replace('.pdf', '.html', $filePath), $html);
        
        throw new Exception("PDF export requires a PDF library like TCPDF");
    }

    /**
     * Generate HTML table from data
     */
    private function generateHTMLTable($data, $options = []) {
        $title = $options['title'] ?? 'Data Export';
        
        $html = "<!DOCTYPE html>\n<html>\n<head>\n";
        $html .= "<title>{$title}</title>\n";
        $html .= "<style>table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #f2f2f2; }</style>\n";
        $html .= "</head>\n<body>\n";
        $html .= "<h1>{$title}</h1>\n";
        $html .= "<table>\n";
        
        if (!empty($data)) {
            // Headers
            $html .= "<thead><tr>";
            foreach (array_keys($data[0]) as $header) {
                $html .= "<th>" . htmlspecialchars($header) . "</th>";
            }
            $html .= "</tr></thead>\n";
            
            // Data
            $html .= "<tbody>\n";
            foreach ($data as $row) {
                $html .= "<tr>";
                foreach ($row as $cell) {
                    $html .= "<td>" . htmlspecialchars($cell) . "</td>";
                }
                $html .= "</tr>\n";
            }
            $html .= "</tbody>\n";
        }
        
        $html .= "</table>\n</body>\n</html>";
        
        return $html;
    }

    /**
     * Get data from database
     */
    private function getDataFromDatabase($dataType, $options = []) {
        global $con;
        
        $query = $this->buildExportQuery($dataType, $options);
        $result = mysqli_query($con, $query);
        
        if (!$result) {
            throw new Exception("Database query failed: " . mysqli_error($con));
        }
        
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        
        return $data;
    }

    /**
     * Build export query based on data type
     */
    private function buildExportQuery($dataType, $options = []) {
        $limit = $options['limit'] ?? '';
        $where = $options['where'] ?? '';
        $orderBy = $options['order_by'] ?? '';
        
        switch ($dataType) {
            case 'bookings':
                $query = "SELECT * FROM roombook";
                break;
            case 'users':
                $query = "SELECT id, name, email, phone, country, created_at FROM users";
                break;
            case 'rooms':
                $query = "SELECT * FROM room";
                break;
            case 'menu_items':
                $query = "SELECT * FROM menu_items";
                break;
            case 'transactions':
                $query = "SELECT * FROM payment_transactions";
                break;
            default:
                throw new Exception("Unsupported data type: {$dataType}");
        }
        
        if ($where) {
            $query .= " WHERE {$where}";
        }
        
        if ($orderBy) {
            $query .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $query .= " LIMIT {$limit}";
        }
        
        return $query;
    }

    /**
     * Initialize validation rules
     */
    private function initializeValidationRules() {
        $this->validationRules = [
            'bookings' => [
                'required' => ['name', 'email', 'phone'],
                'email' => ['email'],
                'numeric' => ['phone'],
                'date' => ['cin', 'cout']
            ],
            'users' => [
                'required' => ['name', 'email'],
                'email' => ['email'],
                'unique' => ['email']
            ],
            'rooms' => [
                'required' => ['type', 'bedding'],
                'numeric' => ['id']
            ]
        ];
    }

    /**
     * Initialize transformation rules
     */
    private function initializeTransformationRules() {
        $this->transformationRules = [
            'bookings' => [
                'phone' => 'trim',
                'email' => 'strtolower',
                'cin' => 'parseDate',
                'cout' => 'parseDate'
            ],
            'users' => [
                'email' => 'strtolower',
                'name' => 'trim'
            ]
        ];
    }

    /**
     * Validate data
     */
    private function validateData($data, $dataType) {
        $rules = $this->validationRules[$dataType] ?? [];
        $errors = [];
        $warnings = [];
        
        foreach ($data as $index => $row) {
            // Check required fields
            if (isset($rules['required'])) {
                foreach ($rules['required'] as $field) {
                    if (!isset($row[$field]) || empty($row[$field])) {
                        $errors[] = "Row {$index}: Missing required field '{$field}'";
                    }
                }
            }
            
            // Check email format
            if (isset($rules['email'])) {
                foreach ($rules['email'] as $field) {
                    if (isset($row[$field]) && !filter_var($row[$field], FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Row {$index}: Invalid email format in field '{$field}'";
                    }
                }
            }
            
            // Check numeric fields
            if (isset($rules['numeric'])) {
                foreach ($rules['numeric'] as $field) {
                    if (isset($row[$field]) && !is_numeric($row[$field])) {
                        $warnings[] = "Row {$index}: Non-numeric value in field '{$field}'";
                    }
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Transform data
     */
    private function transformData($data, $dataType, $options = []) {
        $rules = $this->transformationRules[$dataType] ?? [];
        
        foreach ($data as &$row) {
            foreach ($rules as $field => $transformation) {
                if (isset($row[$field])) {
                    $row[$field] = $this->applyTransformation($row[$field], $transformation);
                }
            }
        }
        
        return $data;
    }

    /**
     * Apply transformation to value
     */
    private function applyTransformation($value, $transformation) {
        switch ($transformation) {
            case 'trim':
                return trim($value);
            case 'strtolower':
                return strtolower($value);
            case 'parseDate':
                return $this->parseDate($value);
            default:
                return $value;
        }
    }

    /**
     * Parse date in various formats
     */
    private function parseDate($dateString) {
        foreach ($this->config['date_formats'] as $format) {
            $date = DateTime::createFromFormat($format, $dateString);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }
        
        return $dateString; // Return original if no format matches
    }

    /**
     * Generate export filename
     */
    private function generateExportFilename($dataType, $format, $options = []) {
        $timestamp = date('Y-m-d_H-i-s');
        $prefix = $options['filename_prefix'] ?? $dataType;
        
        return "{$prefix}_{$timestamp}.{$format}";
    }

    /**
     * Generate backup filename
     */
    private function generateBackupFilename($options = []) {
        $timestamp = date('Y-m-d_H-i-s');
        $prefix = $options['filename_prefix'] ?? 'database_backup';
        
        return "{$prefix}_{$timestamp}.sql";
    }

    /**
     * Get all database tables
     */
    private function getAllTables() {
        global $con;
        
        $tables = [];
        $result = mysqli_query($con, "SHOW TABLES");
        
        while ($row = mysqli_fetch_array($result)) {
            $tables[] = $row[0];
        }
        
        return $tables;
    }

    /**
     * Export table structure
     */
    private function exportTableStructure($table) {
        global $con;
        
        $sql = "\n-- Table structure for table `{$table}`\n";
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        
        $result = mysqli_query($con, "SHOW CREATE TABLE `{$table}`");
        $row = mysqli_fetch_array($result);
        $sql .= $row[1] . ";\n\n";
        
        return $sql;
    }

    /**
     * Export table data
     */
    private function exportTableData($table, $options = []) {
        global $con;
        
        $sql = "-- Dumping data for table `{$table}`\n";
        
        $result = mysqli_query($con, "SELECT * FROM `{$table}`");
        
        if (mysqli_num_rows($result) > 0) {
            $sql .= "INSERT INTO `{$table}` VALUES\n";
            $rows = [];
            
            while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
                $escapedRow = array_map(function($value) use ($con) {
                    return $value === null ? 'NULL' : "'" . mysqli_real_escape_string($con, $value) . "'";
                }, $row);
                
                $rows[] = '(' . implode(',', $escapedRow) . ')';
            }
            
            $sql .= implode(",\n", $rows) . ";\n\n";
        }
        
        return $sql;
    }

    /**
     * Compress file using gzip
     */
    private function compressFile($sourceFile, $destinationFile) {
        $data = file_get_contents($sourceFile);
        $compressed = gzencode($data);
        file_put_contents($destinationFile, $compressed);
    }

    /**
     * Decompress gzip file
     */
    private function decompressFile($sourceFile, $destinationFile) {
        $compressed = file_get_contents($sourceFile);
        $data = gzdecode($compressed);
        file_put_contents($destinationFile, $data);
    }

    /**
     * Scan for malicious content
     */
    private function scanForMaliciousContent($filePath) {
        $content = file_get_contents($filePath, false, null, 0, 8192); // Read first 8KB
        
        $maliciousPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i'
        ];
        
        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                throw new Exception("Malicious content detected in file");
            }
        }
    }

    /**
     * Sanitize XML element name
     */
    private function sanitizeXMLElementName($name) {
        // Replace invalid characters with underscores
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
    }

    /**
     * Initialize logging
     */
    private function initializeLogger() {
        $this->logger = [
            'file' => __DIR__ . '/../../logs/import_export.log'
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
     * Get import/export statistics
     */
    public function getStatistics() {
        return [
            'supported_formats' => $this->supportedFormats,
            'max_file_size' => $this->config['max_file_size'],
            'recent_imports' => $this->getRecentOperations('import'),
            'recent_exports' => $this->getRecentOperations('export'),
            'recent_backups' => $this->getRecentBackups()
        ];
    }

    /**
     * Get recent operations from log
     */
    private function getRecentOperations($type) {
        // This would parse the log file to get recent operations
        // For now, return placeholder data
        return [];
    }

    /**
     * Get recent backups
     */
    private function getRecentBackups() {
        $backups = [];
        
        if (is_dir($this->config['backup_directory'])) {
            $files = glob($this->config['backup_directory'] . '/*.sql*');
            
            foreach ($files as $file) {
                $backups[] = [
                    'filename' => basename($file),
                    'size' => filesize($file),
                    'created' => filemtime($file)
                ];
            }
            
            // Sort by creation time, newest first
            usort($backups, function($a, $b) {
                return $b['created'] - $a['created'];
            });
        }
        
        return array_slice($backups, 0, 10); // Return last 10 backups
    }
}
?>
