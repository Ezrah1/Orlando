<?php
/**
 * Orlando International Resorts - External API Integration Manager
 * Comprehensive system for managing third-party API integrations
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

class ExternalAPIManager {
    private $config;
    private $apiConnectors;
    private $rateLimiter;
    private $cache;
    private $logger;
    private $webhookHandlers;

    public function __construct() {
        $this->config = $this->loadConfiguration();
        $this->apiConnectors = [];
        $this->webhookHandlers = [];
        $this->initializeRateLimiter();
        $this->initializeCache();
        $this->initializeLogger();
        $this->registerAPIConnectors();
    }

    /**
     * Load API configuration
     */
    private function loadConfiguration() {
        $configFile = __DIR__ . '/../../config/external_apis.json';
        
        if (!file_exists($configFile)) {
            $this->createDefaultConfig($configFile);
        }
        
        return json_decode(file_get_contents($configFile), true);
    }

    /**
     * Create default API configuration
     */
    private function createDefaultConfig($configFile) {
        $defaultConfig = [
            'rate_limiting' => [
                'enabled' => true,
                'default_limit' => 100,
                'default_window' => 3600
            ],
            'caching' => [
                'enabled' => true,
                'default_ttl' => 300,
                'cache_driver' => 'file'
            ],
            'retry_policy' => [
                'max_attempts' => 3,
                'backoff_multiplier' => 2,
                'initial_delay' => 1000
            ],
            'apis' => [
                'booking_com' => [
                    'enabled' => false,
                    'name' => 'Booking.com',
                    'type' => 'accommodation',
                    'base_url' => 'https://distribution-xml.booking.com',
                    'version' => '2.6',
                    'credentials' => [
                        'username' => 'your_booking_username',
                        'password' => 'your_booking_password'
                    ],
                    'endpoints' => [
                        'hotels' => '/hotelresv/hotels',
                        'availability' => '/hotelresv/availability',
                        'reservations' => '/hotelresv/reservations'
                    ],
                    'rate_limit' => [
                        'requests' => 1000,
                        'window' => 3600
                    ]
                ],
                'expedia' => [
                    'enabled' => false,
                    'name' => 'Expedia Partner Solutions',
                    'type' => 'accommodation',
                    'base_url' => 'https://services.expediapartnercentral.com',
                    'version' => 'v1',
                    'credentials' => [
                        'username' => 'your_expedia_username',
                        'password' => 'your_expedia_password'
                    ],
                    'endpoints' => [
                        'properties' => '/properties/v1',
                        'rates' => '/rates/v1',
                        'availability' => '/availability/v1'
                    ]
                ],
                'google_maps' => [
                    'enabled' => true,
                    'name' => 'Google Maps API',
                    'type' => 'location',
                    'base_url' => 'https://maps.googleapis.com/maps/api',
                    'credentials' => [
                        'api_key' => 'your_google_maps_api_key'
                    ],
                    'endpoints' => [
                        'geocoding' => '/geocode/json',
                        'places' => '/place/nearbysearch/json',
                        'directions' => '/directions/json',
                        'static_maps' => '/staticmap'
                    ],
                    'rate_limit' => [
                        'requests' => 1000,
                        'window' => 86400
                    ]
                ],
                'weather_api' => [
                    'enabled' => true,
                    'name' => 'OpenWeatherMap',
                    'type' => 'weather',
                    'base_url' => 'https://api.openweathermap.org/data/2.5',
                    'credentials' => [
                        'api_key' => 'your_openweather_api_key'
                    ],
                    'endpoints' => [
                        'current' => '/weather',
                        'forecast' => '/forecast',
                        'onecall' => '/onecall'
                    ],
                    'cache_ttl' => 1800,
                    'rate_limit' => [
                        'requests' => 1000,
                        'window' => 3600
                    ]
                ],
                'twilio' => [
                    'enabled' => true,
                    'name' => 'Twilio',
                    'type' => 'communication',
                    'base_url' => 'https://api.twilio.com/2010-04-01',
                    'credentials' => [
                        'account_sid' => 'your_twilio_account_sid',
                        'auth_token' => 'your_twilio_auth_token'
                    ],
                    'endpoints' => [
                        'messages' => '/Accounts/{AccountSid}/Messages.json',
                        'calls' => '/Accounts/{AccountSid}/Calls.json'
                    ],
                    'rate_limit' => [
                        'requests' => 100,
                        'window' => 60
                    ]
                ],
                'sendgrid' => [
                    'enabled' => true,
                    'name' => 'SendGrid',
                    'type' => 'email',
                    'base_url' => 'https://api.sendgrid.com/v3',
                    'credentials' => [
                        'api_key' => 'your_sendgrid_api_key'
                    ],
                    'endpoints' => [
                        'send' => '/mail/send',
                        'templates' => '/templates'
                    ]
                ],
                'exchange_rates' => [
                    'enabled' => true,
                    'name' => 'ExchangeRate-API',
                    'type' => 'financial',
                    'base_url' => 'https://api.exchangerate-api.com/v4',
                    'endpoints' => [
                        'latest' => '/latest/{base}',
                        'convert' => '/latest/{from}'
                    ],
                    'cache_ttl' => 3600
                ],
                'analytics' => [
                    'enabled' => false,
                    'name' => 'Google Analytics',
                    'type' => 'analytics',
                    'base_url' => 'https://www.googleapis.com/analytics/v3',
                    'credentials' => [
                        'client_id' => 'your_google_client_id',
                        'client_secret' => 'your_google_client_secret',
                        'refresh_token' => 'your_refresh_token'
                    ]
                ],
                'social_media' => [
                    'enabled' => false,
                    'name' => 'Social Media APIs',
                    'type' => 'social',
                    'apis' => [
                        'facebook' => [
                            'base_url' => 'https://graph.facebook.com/v18.0',
                            'credentials' => [
                                'app_id' => 'your_facebook_app_id',
                                'app_secret' => 'your_facebook_app_secret',
                                'access_token' => 'your_access_token'
                            ]
                        ],
                        'instagram' => [
                            'base_url' => 'https://graph.instagram.com',
                            'credentials' => [
                                'access_token' => 'your_instagram_access_token'
                            ]
                        ]
                    ]
                ],
                'crm_integration' => [
                    'enabled' => false,
                    'name' => 'CRM Systems',
                    'type' => 'crm',
                    'apis' => [
                        'salesforce' => [
                            'base_url' => 'https://your-instance.salesforce.com/services/data/v58.0',
                            'credentials' => [
                                'client_id' => 'your_salesforce_client_id',
                                'client_secret' => 'your_salesforce_client_secret',
                                'username' => 'your_salesforce_username',
                                'password' => 'your_salesforce_password'
                            ]
                        ],
                        'hubspot' => [
                            'base_url' => 'https://api.hubapi.com',
                            'credentials' => [
                                'api_key' => 'your_hubspot_api_key'
                            ]
                        ]
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
     * Register API connectors
     */
    private function registerAPIConnectors() {
        foreach ($this->config['apis'] as $apiName => $apiConfig) {
            if ($apiConfig['enabled']) {
                $this->apiConnectors[$apiName] = new APIConnector($apiName, $apiConfig, $this);
            }
        }
    }

    /**
     * Make API request
     */
    public function makeRequest($apiName, $endpoint, $method = 'GET', $data = null, $options = []) {
        if (!isset($this->apiConnectors[$apiName])) {
            throw new Exception("API connector '{$apiName}' not found or not enabled");
        }

        $connector = $this->apiConnectors[$apiName];
        
        // Check rate limiting
        if (!$this->checkRateLimit($apiName)) {
            throw new Exception("Rate limit exceeded for API '{$apiName}'");
        }

        // Check cache first
        $cacheKey = $this->generateCacheKey($apiName, $endpoint, $method, $data);
        if ($method === 'GET' && $this->cache->has($cacheKey)) {
            $this->log("Cache hit for {$apiName} {$endpoint}");
            return $this->cache->get($cacheKey);
        }

        try {
            $response = $connector->makeRequest($endpoint, $method, $data, $options);
            
            // Cache GET requests
            if ($method === 'GET' && $response['success']) {
                $ttl = $options['cache_ttl'] ?? $this->config['apis'][$apiName]['cache_ttl'] ?? $this->config['caching']['default_ttl'];
                $this->cache->set($cacheKey, $response, $ttl);
            }

            $this->log("API request successful: {$apiName} {$method} {$endpoint}");
            return $response;

        } catch (Exception $e) {
            $this->log("API request failed: {$apiName} {$method} {$endpoint} - " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Get weather information
     */
    public function getWeatherInfo($location, $options = []) {
        if (!isset($this->apiConnectors['weather_api'])) {
            throw new Exception('Weather API not configured');
        }

        $params = [
            'q' => $location,
            'appid' => $this->config['apis']['weather_api']['credentials']['api_key'],
            'units' => $options['units'] ?? 'metric'
        ];

        if (isset($options['forecast']) && $options['forecast']) {
            $endpoint = 'forecast';
        } else {
            $endpoint = 'current';
        }

        $response = $this->makeRequest('weather_api', $endpoint, 'GET', $params);
        
        if ($response['success']) {
            return $this->formatWeatherData($response['data']);
        }

        throw new Exception('Failed to fetch weather data');
    }

    /**
     * Send SMS notification
     */
    public function sendSMS($to, $message, $options = []) {
        if (!isset($this->apiConnectors['twilio'])) {
            throw new Exception('Twilio SMS API not configured');
        }

        $data = [
            'To' => $to,
            'Body' => $message,
            'From' => $options['from'] ?? $this->config['apis']['twilio']['default_from'] ?? null
        ];

        if (!$data['From']) {
            throw new Exception('SMS sender number not configured');
        }

        $response = $this->makeRequest('twilio', 'messages', 'POST', $data);
        
        if ($response['success']) {
            $this->log("SMS sent successfully to {$to}");
            return $response['data'];
        }

        throw new Exception('Failed to send SMS: ' . $response['error']);
    }

    /**
     * Send email via SendGrid
     */
    public function sendEmail($emailData) {
        if (!isset($this->apiConnectors['sendgrid'])) {
            throw new Exception('SendGrid API not configured');
        }

        $data = [
            'personalizations' => [
                [
                    'to' => [['email' => $emailData['to']]],
                    'subject' => $emailData['subject']
                ]
            ],
            'from' => [
                'email' => $emailData['from'] ?? 'noreply@orlandoresorts.com',
                'name' => $emailData['from_name'] ?? 'Orlando International Resorts'
            ],
            'content' => [
                [
                    'type' => 'text/html',
                    'value' => $emailData['html'] ?? $emailData['content']
                ]
            ]
        ];

        if (isset($emailData['template_id'])) {
            $data['template_id'] = $emailData['template_id'];
            if (isset($emailData['dynamic_template_data'])) {
                $data['personalizations'][0]['dynamic_template_data'] = $emailData['dynamic_template_data'];
            }
        }

        $response = $this->makeRequest('sendgrid', 'send', 'POST', $data);
        
        if ($response['success']) {
            $this->log("Email sent successfully to {$emailData['to']}");
            return true;
        }

        throw new Exception('Failed to send email: ' . $response['error']);
    }

    /**
     * Get exchange rates
     */
    public function getExchangeRates($baseCurrency = 'USD', $targetCurrencies = null) {
        if (!isset($this->apiConnectors['exchange_rates'])) {
            throw new Exception('Exchange rates API not configured');
        }

        $endpoint = str_replace('{base}', $baseCurrency, 'latest/{base}');
        $response = $this->makeRequest('exchange_rates', $endpoint);

        if ($response['success']) {
            $rates = $response['data']['rates'];
            
            if ($targetCurrencies) {
                $filteredRates = [];
                foreach ($targetCurrencies as $currency) {
                    if (isset($rates[$currency])) {
                        $filteredRates[$currency] = $rates[$currency];
                    }
                }
                return $filteredRates;
            }

            return $rates;
        }

        throw new Exception('Failed to fetch exchange rates');
    }

    /**
     * Convert currency
     */
    public function convertCurrency($amount, $fromCurrency, $toCurrency) {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $rates = $this->getExchangeRates($fromCurrency, [$toCurrency]);
        
        if (!isset($rates[$toCurrency])) {
            throw new Exception("Exchange rate not available for {$fromCurrency} to {$toCurrency}");
        }

        return round($amount * $rates[$toCurrency], 2);
    }

    /**
     * Get location information from coordinates
     */
    public function getLocationInfo($latitude, $longitude) {
        if (!isset($this->apiConnectors['google_maps'])) {
            throw new Exception('Google Maps API not configured');
        }

        $params = [
            'latlng' => "{$latitude},{$longitude}",
            'key' => $this->config['apis']['google_maps']['credentials']['api_key']
        ];

        $response = $this->makeRequest('google_maps', 'geocoding', 'GET', $params);

        if ($response['success'] && !empty($response['data']['results'])) {
            return $response['data']['results'][0];
        }

        throw new Exception('Failed to get location information');
    }

    /**
     * Search nearby places
     */
    public function searchNearbyPlaces($latitude, $longitude, $type = 'restaurant', $radius = 1000) {
        if (!isset($this->apiConnectors['google_maps'])) {
            throw new Exception('Google Maps API not configured');
        }

        $params = [
            'location' => "{$latitude},{$longitude}",
            'radius' => $radius,
            'type' => $type,
            'key' => $this->config['apis']['google_maps']['credentials']['api_key']
        ];

        $response = $this->makeRequest('google_maps', 'places', 'GET', $params);

        if ($response['success']) {
            return $response['data']['results'];
        }

        throw new Exception('Failed to search nearby places');
    }

    /**
     * Sync data with external booking platforms
     */
    public function syncBookingPlatforms($operation, $data) {
        $results = [];

        foreach (['booking_com', 'expedia'] as $platform) {
            if (isset($this->apiConnectors[$platform])) {
                try {
                    $result = $this->performBookingSync($platform, $operation, $data);
                    $results[$platform] = ['success' => true, 'data' => $result];
                } catch (Exception $e) {
                    $results[$platform] = ['success' => false, 'error' => $e->getMessage()];
                    $this->log("Booking sync failed for {$platform}: " . $e->getMessage(), 'error');
                }
            }
        }

        return $results;
    }

    /**
     * Perform booking platform synchronization
     */
    private function performBookingSync($platform, $operation, $data) {
        switch ($operation) {
            case 'update_availability':
                return $this->updateAvailability($platform, $data);
            case 'update_rates':
                return $this->updateRates($platform, $data);
            case 'sync_reservations':
                return $this->syncReservations($platform, $data);
            default:
                throw new Exception("Unknown sync operation: {$operation}");
        }
    }

    /**
     * Update availability on booking platform
     */
    private function updateAvailability($platform, $data) {
        // Implementation would depend on specific platform API
        $this->log("Updating availability for {$platform}");
        
        // This is a placeholder - actual implementation would vary by platform
        return ['updated' => true, 'rooms_updated' => count($data['rooms'])];
    }

    /**
     * Check API rate limits
     */
    private function checkRateLimit($apiName) {
        if (!$this->config['rate_limiting']['enabled']) {
            return true;
        }

        $apiConfig = $this->config['apis'][$apiName];
        $limit = $apiConfig['rate_limit']['requests'] ?? $this->config['rate_limiting']['default_limit'];
        $window = $apiConfig['rate_limit']['window'] ?? $this->config['rate_limiting']['default_window'];

        return $this->rateLimiter->check($apiName, $limit, $window);
    }

    /**
     * Initialize rate limiter
     */
    private function initializeRateLimiter() {
        $this->rateLimiter = new class {
            private $requests = [];

            public function check($key, $limit, $window) {
                $now = time();
                
                if (!isset($this->requests[$key])) {
                    $this->requests[$key] = [];
                }

                // Remove old requests outside the window
                $this->requests[$key] = array_filter($this->requests[$key], function($timestamp) use ($now, $window) {
                    return ($now - $timestamp) < $window;
                });

                // Check if we're within limits
                if (count($this->requests[$key]) >= $limit) {
                    return false;
                }

                // Add current request
                $this->requests[$key][] = $now;
                return true;
            }
        };
    }

    /**
     * Initialize cache system
     */
    private function initializeCache() {
        $this->cache = new class {
            private $cache = [];
            private $ttls = [];

            public function has($key) {
                if (!isset($this->cache[$key])) {
                    return false;
                }

                if (isset($this->ttls[$key]) && time() > $this->ttls[$key]) {
                    unset($this->cache[$key], $this->ttls[$key]);
                    return false;
                }

                return true;
            }

            public function get($key) {
                return $this->cache[$key] ?? null;
            }

            public function set($key, $value, $ttl = 300) {
                $this->cache[$key] = $value;
                $this->ttls[$key] = time() + $ttl;
            }
        };
    }

    /**
     * Generate cache key
     */
    private function generateCacheKey($apiName, $endpoint, $method, $data) {
        return md5("{$apiName}:{$endpoint}:{$method}:" . serialize($data));
    }

    /**
     * Format weather data
     */
    private function formatWeatherData($data) {
        if (isset($data['main'])) {
            // Current weather
            return [
                'temperature' => $data['main']['temp'],
                'feels_like' => $data['main']['feels_like'],
                'humidity' => $data['main']['humidity'],
                'pressure' => $data['main']['pressure'],
                'description' => $data['weather'][0]['description'],
                'icon' => $data['weather'][0]['icon'],
                'wind_speed' => $data['wind']['speed'] ?? null,
                'visibility' => $data['visibility'] ?? null
            ];
        } else {
            // Forecast
            return array_map(function($item) {
                return [
                    'datetime' => $item['dt_txt'],
                    'temperature' => $item['main']['temp'],
                    'description' => $item['weather'][0]['description'],
                    'icon' => $item['weather'][0]['icon']
                ];
            }, $data['list']);
        }
    }

    /**
     * Initialize logging
     */
    private function initializeLogger() {
        $this->logger = [
            'file' => __DIR__ . '/../../logs/external_apis.log'
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
     * Get API statistics
     */
    public function getAPIStatistics() {
        $stats = [];
        
        foreach ($this->apiConnectors as $name => $connector) {
            $stats[$name] = $connector->getStatistics();
        }

        return $stats;
    }

    /**
     * Health check for all APIs
     */
    public function healthCheck() {
        $results = [];
        
        foreach ($this->apiConnectors as $name => $connector) {
            try {
                $results[$name] = [
                    'status' => 'healthy',
                    'response_time' => $connector->healthCheck(),
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

/**
 * Generic API Connector class
 */
class APIConnector {
    private $name;
    private $config;
    private $manager;
    private $statistics;

    public function __construct($name, $config, $manager) {
        $this->name = $name;
        $this->config = $config;
        $this->manager = $manager;
        $this->statistics = [
            'requests_made' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'total_response_time' => 0
        ];
    }

    public function makeRequest($endpoint, $method = 'GET', $data = null, $options = []) {
        $startTime = microtime(true);
        $this->statistics['requests_made']++;

        try {
            $url = $this->buildUrl($endpoint);
            $headers = $this->buildHeaders($options);
            
            $response = $this->executeRequest($url, $method, $data, $headers, $options);
            
            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000;
            $this->statistics['total_response_time'] += $responseTime;
            $this->statistics['successful_requests']++;

            return [
                'success' => true,
                'data' => $response,
                'response_time' => $responseTime
            ];

        } catch (Exception $e) {
            $this->statistics['failed_requests']++;
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response_time' => (microtime(true) - $startTime) * 1000
            ];
        }
    }

    private function buildUrl($endpoint) {
        $baseUrl = $this->config['base_url'];
        
        // Handle endpoint placeholders
        if (strpos($endpoint, '{') !== false && isset($this->config['credentials']['account_sid'])) {
            $endpoint = str_replace('{AccountSid}', $this->config['credentials']['account_sid'], $endpoint);
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');
    }

    private function buildHeaders($options) {
        $headers = ['Content-Type: application/json'];
        
        // Add authentication headers based on API type
        if (isset($this->config['credentials']['api_key'])) {
            if ($this->name === 'sendgrid') {
                $headers[] = 'Authorization: Bearer ' . $this->config['credentials']['api_key'];
            } elseif ($this->name === 'google_maps' || $this->name === 'weather_api') {
                // API key will be added as parameter
            }
        }

        if (isset($this->config['credentials']['auth_token']) && isset($this->config['credentials']['account_sid'])) {
            // Twilio Basic Auth
            $auth = base64_encode($this->config['credentials']['account_sid'] . ':' . $this->config['credentials']['auth_token']);
            $headers[] = 'Authorization: Basic ' . $auth;
        }

        return array_merge($headers, $options['headers'] ?? []);
    }

    private function executeRequest($url, $method, $data, $headers, $options) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $options['timeout'] ?? 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'Orlando-Resorts/1.0'
        ]);

        if ($data) {
            if ($method === 'GET') {
                $url .= '?' . http_build_query($data);
                curl_setopt($ch, CURLOPT_URL, $url);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('cURL error: ' . $error);
        }

        if ($httpCode >= 400) {
            throw new Exception("API error: HTTP {$httpCode} - {$response}");
        }

        return json_decode($response, true);
    }

    public function healthCheck() {
        $startTime = microtime(true);
        
        // Simple health check - make a lightweight request
        switch ($this->name) {
            case 'google_maps':
                $this->makeRequest('geocoding', 'GET', ['address' => 'test', 'key' => $this->config['credentials']['api_key']]);
                break;
            default:
                // Generic health check
                $url = $this->config['base_url'];
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_exec($ch);
                curl_close($ch);
        }
        
        return round((microtime(true) - $startTime) * 1000);
    }

    public function getStatistics() {
        $stats = $this->statistics;
        $stats['average_response_time'] = $stats['requests_made'] > 0 ? 
            $stats['total_response_time'] / $stats['requests_made'] : 0;
        $stats['success_rate'] = $stats['requests_made'] > 0 ? 
            ($stats['successful_requests'] / $stats['requests_made']) * 100 : 0;
        
        return $stats;
    }
}
?>
