<?php
/**
 * Orlando International Resorts - Performance Management System
 * Comprehensive performance monitoring, caching, and optimization framework
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

class PerformanceManager {
    private $config;
    private $cacheEngine;
    private $metricsCollector;
    private $logger;
    private $profiler;
    private $startTime;

    public function __construct() {
        $this->startTime = microtime(true);
        $this->config = $this->loadConfiguration();
        $this->initializeCaching();
        $this->initializeMetrics();
        $this->initializeLogger();
        $this->initializeProfiler();
        
        // Auto-start performance monitoring
        $this->startMonitoring();
    }

    /**
     * Load performance configuration
     */
    private function loadConfiguration() {
        return [
            'caching' => [
                'enabled' => true,
                'default_ttl' => 3600,
                'max_memory' => '256M',
                'compression' => true,
                'serialization' => 'json',
                'backends' => [
                    'primary' => 'redis',
                    'fallback' => 'file'
                ]
            ],
            'monitoring' => [
                'enabled' => true,
                'collection_interval' => 60,
                'retention_days' => 30,
                'alert_thresholds' => [
                    'cpu_usage' => 80,
                    'memory_usage' => 85,
                    'response_time' => 3000,
                    'error_rate' => 5
                ]
            ],
            'optimization' => [
                'auto_optimize' => true,
                'compress_output' => true,
                'minify_assets' => true,
                'lazy_loading' => true,
                'image_optimization' => true
            ],
            'scaling' => [
                'load_balancing' => false,
                'auto_scaling' => false,
                'max_connections' => 1000,
                'queue_processing' => true
            ]
        ];
    }

    /**
     * Initialize caching system
     */
    private function initializeCaching() {
        $this->cacheEngine = new class($this->config['caching']) {
            private $config;
            private $backend;
            private $fallbackBackend;
            private $stats;

            public function __construct($config) {
                $this->config = $config;
                $this->stats = ['hits' => 0, 'misses' => 0, 'sets' => 0, 'deletes' => 0];
                $this->initializeBackends();
            }

            private function initializeBackends() {
                // Primary backend (Redis)
                if ($this->config['backends']['primary'] === 'redis' && extension_loaded('redis')) {
                    try {
                        $this->backend = new Redis();
                        $this->backend->connect('127.0.0.1', 6379);
                        $this->backend->select(1); // Use database 1 for cache
                    } catch (Exception $e) {
                        $this->backend = null;
                    }
                }

                // Fallback backend (File)
                $this->fallbackBackend = new class {
                    private $cacheDir;

                    public function __construct() {
                        $this->cacheDir = __DIR__ . '/../../cache';
                        if (!is_dir($this->cacheDir)) {
                            mkdir($this->cacheDir, 0755, true);
                        }
                    }

                    public function get($key) {
                        $file = $this->cacheDir . '/' . md5($key) . '.cache';
                        if (!file_exists($file)) return false;
                        
                        $data = unserialize(file_get_contents($file));
                        if ($data['expires'] < time()) {
                            unlink($file);
                            return false;
                        }
                        
                        return $data['value'];
                    }

                    public function set($key, $value, $ttl = 3600) {
                        $file = $this->cacheDir . '/' . md5($key) . '.cache';
                        $data = [
                            'value' => $value,
                            'expires' => time() + $ttl,
                            'created' => time()
                        ];
                        return file_put_contents($file, serialize($data)) !== false;
                    }

                    public function delete($key) {
                        $file = $this->cacheDir . '/' . md5($key) . '.cache';
                        return file_exists($file) ? unlink($file) : true;
                    }

                    public function flush() {
                        $files = glob($this->cacheDir . '/*.cache');
                        foreach ($files as $file) {
                            unlink($file);
                        }
                        return true;
                    }
                };
            }

            public function get($key) {
                $backend = $this->backend ?: $this->fallbackBackend;
                
                try {
                    $value = $backend->get($key);
                    if ($value !== false) {
                        $this->stats['hits']++;
                        return $this->config['compression'] ? gzuncompress($value) : $value;
                    }
                } catch (Exception $e) {
                    // Try fallback if primary fails
                    if ($this->backend && $backend === $this->backend) {
                        $value = $this->fallbackBackend->get($key);
                        if ($value !== false) {
                            $this->stats['hits']++;
                            return $this->config['compression'] ? gzuncompress($value) : $value;
                        }
                    }
                }
                
                $this->stats['misses']++;
                return false;
            }

            public function set($key, $value, $ttl = null) {
                $ttl = $ttl ?: $this->config['default_ttl'];
                $data = $this->config['compression'] ? gzcompress($value) : $value;
                
                $backend = $this->backend ?: $this->fallbackBackend;
                
                try {
                    $result = $backend->set($key, $data, $ttl);
                    if ($result) {
                        $this->stats['sets']++;
                        return true;
                    }
                } catch (Exception $e) {
                    // Try fallback if primary fails
                    if ($this->backend && $backend === $this->backend) {
                        $result = $this->fallbackBackend->set($key, $data, $ttl);
                        if ($result) {
                            $this->stats['sets']++;
                            return true;
                        }
                    }
                }
                
                return false;
            }

            public function delete($key) {
                $backend = $this->backend ?: $this->fallbackBackend;
                
                try {
                    $result = $backend->delete($key);
                    if ($result) {
                        $this->stats['deletes']++;
                        return true;
                    }
                } catch (Exception $e) {
                    // Try fallback if primary fails
                    if ($this->backend && $backend === $this->backend) {
                        $result = $this->fallbackBackend->delete($key);
                        if ($result) {
                            $this->stats['deletes']++;
                            return true;
                        }
                    }
                }
                
                return false;
            }

            public function flush() {
                $backend = $this->backend ?: $this->fallbackBackend;
                return $backend->flush();
            }

            public function getStats() {
                $total = $this->stats['hits'] + $this->stats['misses'];
                return [
                    'hits' => $this->stats['hits'],
                    'misses' => $this->stats['misses'],
                    'sets' => $this->stats['sets'],
                    'deletes' => $this->stats['deletes'],
                    'hit_rate' => $total > 0 ? ($this->stats['hits'] / $total) * 100 : 0,
                    'backend' => $this->backend ? 'redis' : 'file'
                ];
            }
        };
    }

    /**
     * Initialize metrics collection
     */
    private function initializeMetrics() {
        $this->metricsCollector = new class {
            private $metrics = [];
            private $startTime;

            public function __construct() {
                $this->startTime = microtime(true);
                $this->metrics = [
                    'requests' => 0,
                    'response_times' => [],
                    'memory_usage' => [],
                    'db_queries' => 0,
                    'cache_operations' => 0,
                    'errors' => 0
                ];
            }

            public function recordRequest($responseTime = null) {
                $this->metrics['requests']++;
                if ($responseTime !== null) {
                    $this->metrics['response_times'][] = $responseTime;
                }
            }

            public function recordMemoryUsage() {
                $this->metrics['memory_usage'][] = [
                    'timestamp' => time(),
                    'usage' => memory_get_usage(true),
                    'peak' => memory_get_peak_usage(true)
                ];
            }

            public function recordDbQuery() {
                $this->metrics['db_queries']++;
            }

            public function recordCacheOperation() {
                $this->metrics['cache_operations']++;
            }

            public function recordError() {
                $this->metrics['errors']++;
            }

            public function getMetrics() {
                $avgResponseTime = !empty($this->metrics['response_times']) ? 
                    array_sum($this->metrics['response_times']) / count($this->metrics['response_times']) : 0;

                $currentMemory = memory_get_usage(true);
                $peakMemory = memory_get_peak_usage(true);

                return [
                    'requests_total' => $this->metrics['requests'],
                    'requests_per_second' => $this->metrics['requests'] / (microtime(true) - $this->startTime),
                    'avg_response_time' => round($avgResponseTime, 2),
                    'current_memory' => $currentMemory,
                    'peak_memory' => $peakMemory,
                    'memory_usage_percent' => ($currentMemory / $this->getMemoryLimit()) * 100,
                    'db_queries_total' => $this->metrics['db_queries'],
                    'cache_operations_total' => $this->metrics['cache_operations'],
                    'errors_total' => $this->metrics['errors'],
                    'uptime' => microtime(true) - $this->startTime
                ];
            }

            private function getMemoryLimit() {
                $limit = ini_get('memory_limit');
                if ($limit == -1) return 1024 * 1024 * 1024; // 1GB default
                
                $unit = strtolower(substr($limit, -1));
                $value = (int) $limit;
                
                switch ($unit) {
                    case 'g': return $value * 1024 * 1024 * 1024;
                    case 'm': return $value * 1024 * 1024;
                    case 'k': return $value * 1024;
                    default: return $value;
                }
            }
        };
    }

    /**
     * Initialize profiler
     */
    private function initializeProfiler() {
        $this->profiler = new class {
            private $profiles = [];
            private $activeProfiles = [];

            public function start($name) {
                $this->activeProfiles[$name] = microtime(true);
            }

            public function end($name) {
                if (!isset($this->activeProfiles[$name])) return false;
                
                $duration = microtime(true) - $this->activeProfiles[$name];
                unset($this->activeProfiles[$name]);
                
                if (!isset($this->profiles[$name])) {
                    $this->profiles[$name] = [];
                }
                
                $this->profiles[$name][] = $duration;
                return $duration;
            }

            public function getProfile($name) {
                if (!isset($this->profiles[$name])) return null;
                
                $times = $this->profiles[$name];
                return [
                    'count' => count($times),
                    'total' => array_sum($times),
                    'average' => array_sum($times) / count($times),
                    'min' => min($times),
                    'max' => max($times)
                ];
            }

            public function getAllProfiles() {
                $result = [];
                foreach ($this->profiles as $name => $times) {
                    $result[$name] = $this->getProfile($name);
                }
                return $result;
            }
        };
    }

    /**
     * Start performance monitoring
     */
    private function startMonitoring() {
        if (!$this->config['monitoring']['enabled']) return;
        
        // Record initial metrics
        $this->metricsCollector->recordMemoryUsage();
        
        // Set up periodic metric collection
        if (function_exists('register_tick_function')) {
            declare(ticks=1000);
            register_tick_function([$this, 'collectMetrics']);
        }
    }

    /**
     * Collect performance metrics
     */
    public function collectMetrics() {
        $this->metricsCollector->recordMemoryUsage();
        
        // Store metrics in database periodically
        static $lastStore = 0;
        $now = time();
        
        if ($now - $lastStore >= $this->config['monitoring']['collection_interval']) {
            $this->storeMetrics();
            $lastStore = $now;
        }
    }

    /**
     * Cache data with automatic expiration
     */
    public function cache($key, $data = null, $ttl = null) {
        if ($data === null) {
            // Get from cache
            return $this->cacheEngine->get($key);
        } else {
            // Set to cache
            $this->metricsCollector->recordCacheOperation();
            return $this->cacheEngine->set($key, $data, $ttl);
        }
    }

    /**
     * Delete from cache
     */
    public function uncache($key) {
        return $this->cacheEngine->delete($key);
    }

    /**
     * Cache database query results
     */
    public function cacheQuery($sql, $params = [], $ttl = null) {
        $cacheKey = 'query_' . md5($sql . serialize($params));
        
        $result = $this->cache($cacheKey);
        if ($result !== false) {
            return json_decode($result, true);
        }
        
        // Execute query and cache result
        global $con;
        $this->profiler->start('db_query');
        $this->metricsCollector->recordDbQuery();
        
        if (empty($params)) {
            $queryResult = mysqli_query($con, $sql);
        } else {
            $stmt = $con->prepare($sql);
            if ($stmt) {
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $queryResult = $stmt->get_result();
            } else {
                $queryResult = false;
            }
        }
        
        $this->profiler->end('db_query');
        
        if ($queryResult) {
            $data = [];
            while ($row = mysqli_fetch_assoc($queryResult)) {
                $data[] = $row;
            }
            
            $this->cache($cacheKey, json_encode($data), $ttl);
            return $data;
        }
        
        return false;
    }

    /**
     * Optimize images for web delivery
     */
    public function optimizeImage($imagePath, $quality = 85, $maxWidth = 1920, $maxHeight = 1080) {
        if (!file_exists($imagePath)) return false;
        
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) return false;
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $type = $imageInfo[2];
        
        // Skip if already optimized
        if ($width <= $maxWidth && $height <= $maxHeight) return true;
        
        // Calculate new dimensions
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);
        
        // Create source image
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($imagePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($imagePath);
                break;
            default:
                return false;
        }
        
        if (!$source) return false;
        
        // Create optimized image
        $optimized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($optimized, false);
            imagesavealpha($optimized, true);
            $transparent = imagecolorallocatealpha($optimized, 255, 255, 255, 127);
            imagefill($optimized, 0, 0, $transparent);
        }
        
        imagecopyresampled($optimized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Save optimized image
        $optimizedPath = $this->getOptimizedImagePath($imagePath);
        switch ($type) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($optimized, $optimizedPath, $quality);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($optimized, $optimizedPath, 9);
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($optimized, $optimizedPath);
                break;
        }
        
        imagedestroy($source);
        imagedestroy($optimized);
        
        return $result;
    }

    /**
     * Get optimized image path
     */
    private function getOptimizedImagePath($originalPath) {
        $pathInfo = pathinfo($originalPath);
        return $pathInfo['dirname'] . '/optimized_' . $pathInfo['basename'];
    }

    /**
     * Minify CSS content
     */
    public function minifyCSS($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
        
        // Remove unnecessary spaces
        $css = preg_replace('/\s*([{}|:;,>+~])\s*/', '$1', $css);
        
        return trim($css);
    }

    /**
     * Minify JavaScript content
     */
    public function minifyJS($js) {
        // Basic JS minification (for full minification, use a proper library)
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js); // Remove multi-line comments
        $js = preg_replace('/\/\/.*$/', '', $js); // Remove single-line comments
        $js = preg_replace('/\s+/', ' ', $js); // Compress whitespace
        $js = str_replace(['; ', ' ;'], ';', $js);
        $js = str_replace([', ', ' ,'], ',', $js);
        $js = str_replace([' {', '{ '], '{', $js);
        $js = str_replace([' }', '} '], '}', $js);
        
        return trim($js);
    }

    /**
     * Compress HTML output
     */
    public function compressHTML($html) {
        // Remove HTML comments (except conditional comments)
        $html = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $html);
        
        // Remove whitespace between tags
        $html = preg_replace('/>\s+</', '><', $html);
        
        // Remove extra whitespace
        $html = preg_replace('/\s+/', ' ', $html);
        
        return trim($html);
    }

    /**
     * Enable output compression
     */
    public function enableOutputCompression() {
        if (!headers_sent() && $this->config['optimization']['compress_output']) {
            if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
                ob_start('ob_gzhandler');
            }
        }
    }

    /**
     * Get performance report
     */
    public function getPerformanceReport() {
        $metrics = $this->metricsCollector->getMetrics();
        $cacheStats = $this->cacheEngine->getStats();
        $profiles = $this->profiler->getAllProfiles();
        
        return [
            'timestamp' => time(),
            'uptime' => microtime(true) - $this->startTime,
            'metrics' => $metrics,
            'cache' => $cacheStats,
            'profiles' => $profiles,
            'system' => [
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'opcache_enabled' => extension_loaded('opcache') && opcache_get_status()['opcache_enabled'],
                'extensions' => [
                    'redis' => extension_loaded('redis'),
                    'memcached' => extension_loaded('memcached'),
                    'gd' => extension_loaded('gd'),
                    'zlib' => extension_loaded('zlib')
                ]
            ],
            'recommendations' => $this->getOptimizationRecommendations($metrics, $cacheStats)
        ];
    }

    /**
     * Get optimization recommendations
     */
    private function getOptimizationRecommendations($metrics, $cacheStats) {
        $recommendations = [];
        
        // Memory usage recommendations
        if ($metrics['memory_usage_percent'] > 80) {
            $recommendations[] = [
                'type' => 'memory',
                'priority' => 'high',
                'message' => 'Memory usage is high (' . round($metrics['memory_usage_percent'], 1) . '%). Consider increasing memory_limit or optimizing code.'
            ];
        }
        
        // Response time recommendations
        if ($metrics['avg_response_time'] > 2000) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'high',
                'message' => 'Average response time is slow (' . $metrics['avg_response_time'] . 'ms). Consider enabling caching or optimizing database queries.'
            ];
        }
        
        // Cache hit rate recommendations
        if ($cacheStats['hit_rate'] < 70) {
            $recommendations[] = [
                'type' => 'cache',
                'priority' => 'medium',
                'message' => 'Cache hit rate is low (' . round($cacheStats['hit_rate'], 1) . '%). Review caching strategy and TTL values.'
            ];
        }
        
        // Database query recommendations
        if ($metrics['db_queries_total'] > ($metrics['requests_total'] * 10)) {
            $recommendations[] = [
                'type' => 'database',
                'priority' => 'medium',
                'message' => 'High number of database queries per request. Consider query optimization and result caching.'
            ];
        }
        
        return $recommendations;
    }

    /**
     * Store metrics in database
     */
    private function storeMetrics() {
        global $con;
        
        try {
            $metrics = $this->metricsCollector->getMetrics();
            $cacheStats = $this->cacheEngine->getStats();
            
            $stmt = $con->prepare("
                INSERT INTO performance_metrics (
                    timestamp, requests_total, avg_response_time, memory_usage, 
                    db_queries, cache_hit_rate, errors_total, uptime
                ) VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                'idddidd',
                $metrics['requests_total'],
                $metrics['avg_response_time'],
                $metrics['current_memory'],
                $metrics['db_queries_total'],
                $cacheStats['hit_rate'],
                $metrics['errors_total'],
                $metrics['uptime']
            );
            
            $stmt->execute();
            
        } catch (Exception $e) {
            $this->log("Failed to store metrics: " . $e->getMessage(), 'error');
        }
    }

    /**
     * Initialize logging
     */
    private function initializeLogger() {
        $this->logger = [
            'file' => __DIR__ . '/../../logs/performance.log'
        ];
        
        $logDir = dirname($this->logger['file']);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Log performance message
     */
    private function log($message, $level = 'info') {
        $timestamp = date('Y-m-d H:i:s');
        $memory = number_format(memory_get_usage(true) / 1024 / 1024, 2);
        $logEntry = "[{$timestamp}] [{$level}] [Memory: {$memory}MB] {$message}" . PHP_EOL;
        file_put_contents($this->logger['file'], $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Profile function execution
     */
    public function profile($name, $callback) {
        $this->profiler->start($name);
        $result = $callback();
        $duration = $this->profiler->end($name);
        
        if ($duration > 1) { // Log slow operations
            $this->log("Slow operation '{$name}': {$duration}s", 'warning');
        }
        
        return $result;
    }

    /**
     * Clear all caches
     */
    public function clearCache() {
        return $this->cacheEngine->flush();
    }

    /**
     * Record page view for analytics
     */
    public function recordPageView($url, $responseTime = null) {
        $this->metricsCollector->recordRequest($responseTime);
        
        // Store page view in database for analytics
        global $con;
        
        try {
            $stmt = $con->prepare("
                INSERT INTO page_views (url, response_time, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->bind_param(
                'sdss',
                $url,
                $responseTime,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            $stmt->execute();
            
        } catch (Exception $e) {
            $this->log("Failed to record page view: " . $e->getMessage(), 'error');
        }
    }

    /**
     * Cleanup old performance data
     */
    public function cleanup() {
        global $con;
        
        $retentionDays = $this->config['monitoring']['retention_days'];
        
        try {
            // Clean old metrics
            $stmt = $con->prepare("DELETE FROM performance_metrics WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)");
            $stmt->bind_param('i', $retentionDays);
            $stmt->execute();
            
            // Clean old page views
            $stmt = $con->prepare("DELETE FROM page_views WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
            $stmt->bind_param('i', $retentionDays);
            $stmt->execute();
            
            $this->log("Performance data cleanup completed");
            
        } catch (Exception $e) {
            $this->log("Cleanup failed: " . $e->getMessage(), 'error');
        }
    }

    /**
     * Get real-time system health
     */
    public function getSystemHealth() {
        $metrics = $this->metricsCollector->getMetrics();
        $thresholds = $this->config['monitoring']['alert_thresholds'];
        
        $health = [
            'overall' => 'healthy',
            'checks' => [
                'memory' => [
                    'status' => $metrics['memory_usage_percent'] < $thresholds['memory_usage'] ? 'healthy' : 'warning',
                    'value' => round($metrics['memory_usage_percent'], 1) . '%',
                    'threshold' => $thresholds['memory_usage'] . '%'
                ],
                'response_time' => [
                    'status' => $metrics['avg_response_time'] < $thresholds['response_time'] ? 'healthy' : 'warning',
                    'value' => round($metrics['avg_response_time'], 0) . 'ms',
                    'threshold' => $thresholds['response_time'] . 'ms'
                ],
                'error_rate' => [
                    'status' => $metrics['errors_total'] === 0 ? 'healthy' : 'warning',
                    'value' => $metrics['errors_total'],
                    'threshold' => $thresholds['error_rate']
                ]
            ]
        ];
        
        // Determine overall health
        foreach ($health['checks'] as $check) {
            if ($check['status'] === 'warning') {
                $health['overall'] = 'warning';
                break;
            }
        }
        
        return $health;
    }
}
?>
