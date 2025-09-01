<?php
/**
 * Orlando International Resorts - CDN and Asset Optimization Manager
 * Content delivery network integration and static asset optimization
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

class CDNManager {
    private $config;
    private $logger;
    private $assetCache;
    private $optimizationRules;

    public function __construct() {
        $this->config = $this->loadConfiguration();
        $this->initializeLogger();
        $this->initializeAssetCache();
        $this->initializeOptimizationRules();
    }

    /**
     * Load CDN configuration
     */
    private function loadConfiguration() {
        return [
            'enabled' => true,
            'providers' => [
                'cloudflare' => [
                    'enabled' => true,
                    'zone_id' => 'your_cloudflare_zone_id',
                    'api_token' => 'your_cloudflare_api_token',
                    'email' => 'your_cloudflare_email',
                    'api_key' => 'your_cloudflare_api_key',
                    'base_url' => 'https://cdn.orlandoresorts.com',
                    'features' => [
                        'cache_purge' => true,
                        'compression' => true,
                        'minification' => true,
                        'image_optimization' => true,
                        'security' => true
                    ]
                ],
                'aws_cloudfront' => [
                    'enabled' => false,
                    'distribution_id' => 'your_distribution_id',
                    'access_key' => 'your_aws_access_key',
                    'secret_key' => 'your_aws_secret_key',
                    'region' => 'us-east-1',
                    'base_url' => 'https://d123456789.cloudfront.net'
                ],
                'maxcdn' => [
                    'enabled' => false,
                    'alias' => 'your_maxcdn_alias',
                    'key' => 'your_maxcdn_key',
                    'secret' => 'your_maxcdn_secret',
                    'base_url' => 'https://your-alias.maxcdn.com'
                ]
            ],
            'asset_optimization' => [
                'css_minification' => true,
                'js_minification' => true,
                'html_compression' => true,
                'image_optimization' => true,
                'webp_conversion' => true,
                'lazy_loading' => true,
                'critical_css' => true,
                'resource_hints' => true
            ],
            'cache_control' => [
                'css' => 'max-age=31536000, public, immutable',
                'js' => 'max-age=31536000, public, immutable',
                'images' => 'max-age=2592000, public',
                'fonts' => 'max-age=31536000, public, crossorigin',
                'html' => 'max-age=3600, public',
                'api' => 'max-age=300, public'
            ],
            'paths' => [
                'assets' => '/assets',
                'images' => '/images',
                'css' => '/css',
                'js' => '/js',
                'fonts' => '/fonts'
            ]
        ];
    }

    /**
     * Get CDN URL for asset
     */
    public function getCDNUrl($assetPath, $options = []) {
        if (!$this->config['enabled']) {
            return $assetPath;
        }

        $provider = $this->getActiveProvider();
        if (!$provider) {
            return $assetPath;
        }

        $baseUrl = $this->config['providers'][$provider]['base_url'];
        $optimizedPath = $this->optimizeAssetPath($assetPath, $options);
        
        return rtrim($baseUrl, '/') . '/' . ltrim($optimizedPath, '/');
    }

    /**
     * Optimize asset path with parameters
     */
    private function optimizeAssetPath($assetPath, $options = []) {
        $pathInfo = pathinfo($assetPath);
        $extension = strtolower($pathInfo['extension'] ?? '');
        
        // Add optimization parameters based on file type
        $params = [];
        
        if ($extension === 'jpg' || $extension === 'jpeg' || $extension === 'png') {
            // Image optimization parameters
            if (isset($options['width'])) {
                $params['w'] = $options['width'];
            }
            if (isset($options['height'])) {
                $params['h'] = $options['height'];
            }
            if (isset($options['quality'])) {
                $params['q'] = $options['quality'];
            }
            if ($this->config['asset_optimization']['webp_conversion']) {
                $params['f'] = 'webp';
            }
        }
        
        if (!empty($params)) {
            $assetPath .= '?' . http_build_query($params);
        }
        
        return $assetPath;
    }

    /**
     * Get active CDN provider
     */
    private function getActiveProvider() {
        foreach ($this->config['providers'] as $name => $provider) {
            if ($provider['enabled']) {
                return $name;
            }
        }
        return null;
    }

    /**
     * Purge CDN cache
     */
    public function purgeCache($urls = null) {
        $provider = $this->getActiveProvider();
        if (!$provider) {
            throw new Exception('No active CDN provider configured');
        }
        
        switch ($provider) {
            case 'cloudflare':
                return $this->purgeCloudflareCache($urls);
            case 'aws_cloudfront':
                return $this->purgeCloudFrontCache($urls);
            case 'maxcdn':
                return $this->purgeMaxCDNCache($urls);
            default:
                throw new Exception("Unsupported CDN provider: {$provider}");
        }
    }

    /**
     * Purge Cloudflare cache
     */
    private function purgeCloudflareCache($urls = null) {
        $config = $this->config['providers']['cloudflare'];
        
        $data = [];
        if ($urls === null) {
            $data['purge_everything'] = true;
        } else {
            $data['files'] = is_array($urls) ? $urls : [$urls];
        }
        
        $response = $this->makeCloudflareRequest('purge_cache', $data);
        
        if ($response['success']) {
            $this->log("Cloudflare cache purged successfully");
            return true;
        } else {
            throw new Exception("Cloudflare cache purge failed: " . implode(', ', $response['errors']));
        }
    }

    /**
     * Make Cloudflare API request
     */
    private function makeCloudflareRequest($endpoint, $data = null, $method = 'POST') {
        $config = $this->config['providers']['cloudflare'];
        $url = "https://api.cloudflare.com/client/v4/zones/{$config['zone_id']}/{$endpoint}";
        
        $headers = [
            'Authorization: Bearer ' . $config['api_token'],
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method
        ]);
        
        if ($data && ($method === 'POST' || $method === 'PUT' || $method === 'PATCH')) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 400) {
            throw new Exception("Cloudflare API error: HTTP {$httpCode}");
        }
        
        return json_decode($response, true);
    }

    /**
     * Optimize CSS file
     */
    public function optimizeCSS($cssContent, $options = []) {
        if (!$this->config['asset_optimization']['css_minification']) {
            return $cssContent;
        }
        
        // Remove comments
        $cssContent = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $cssContent);
        
        // Remove unnecessary whitespace
        $cssContent = preg_replace('/\s+/', ' ', $cssContent);
        $cssContent = preg_replace('/\s*([{}|:;,>+~])\s*/', '$1', $cssContent);
        
        // Remove trailing semicolons
        $cssContent = preg_replace('/;}/', '}', $cssContent);
        
        // Optimize colors
        $cssContent = preg_replace('/#([a-f0-9])\1([a-f0-9])\2([a-f0-9])\3/i', '#$1$2$3', $cssContent);
        
        // Remove quotes from font names when not necessary
        $cssContent = preg_replace('/font-family:\s*["\']([^"\']+)["\']/i', 'font-family:$1', $cssContent);
        
        return trim($cssContent);
    }

    /**
     * Optimize JavaScript file
     */
    public function optimizeJS($jsContent, $options = []) {
        if (!$this->config['asset_optimization']['js_minification']) {
            return $jsContent;
        }
        
        // Basic JS minification
        $jsContent = preg_replace('/\/\*[\s\S]*?\*\//', '', $jsContent); // Remove multi-line comments
        $jsContent = preg_replace('/\/\/.*$/m', '', $jsContent); // Remove single-line comments
        $jsContent = preg_replace('/\s+/', ' ', $jsContent); // Compress whitespace
        $jsContent = str_replace(['; ', ' ;'], ';', $jsContent);
        $jsContent = str_replace([', ', ' ,'], ',', $jsContent);
        $jsContent = str_replace([' {', '{ '], '{', $jsContent);
        $jsContent = str_replace([' }', '} '], '}', $jsContent);
        $jsContent = str_replace(['( ', ' )'], ['(', ')'], $jsContent);
        
        return trim($jsContent);
    }

    /**
     * Generate critical CSS
     */
    public function generateCriticalCSS($url, $cssFiles = []) {
        if (!$this->config['asset_optimization']['critical_css']) {
            return '';
        }
        
        // This would typically use a tool like Puppeteer or similar
        // For now, return a basic implementation
        $criticalRules = [
            'body{margin:0;font-family:sans-serif}',
            'header{background:#667eea;color:white;padding:1rem}',
            '.container{max-width:1200px;margin:0 auto;padding:0 1rem}',
            'h1,h2,h3{margin-top:0}',
            '.btn{padding:0.5rem 1rem;border:none;border-radius:4px;cursor:pointer}'
        ];
        
        return implode('', $criticalRules);
    }

    /**
     * Generate resource hints
     */
    public function generateResourceHints($assets = []) {
        if (!$this->config['asset_optimization']['resource_hints']) {
            return '';
        }
        
        $hints = [];
        $cdnDomain = parse_url($this->getCDNUrl('/'), PHP_URL_HOST);
        
        // DNS prefetch for CDN domain
        if ($cdnDomain) {
            $hints[] = "<link rel='dns-prefetch' href='//{$cdnDomain}'>";
        }
        
        // Preconnect to CDN
        if ($cdnDomain) {
            $hints[] = "<link rel='preconnect' href='https://{$cdnDomain}' crossorigin>";
        }
        
        // Preload critical assets
        foreach ($assets as $asset) {
            $assetType = $this->getAssetType($asset);
            $cdnUrl = $this->getCDNUrl($asset);
            
            switch ($assetType) {
                case 'css':
                    $hints[] = "<link rel='preload' href='{$cdnUrl}' as='style'>";
                    break;
                case 'js':
                    $hints[] = "<link rel='preload' href='{$cdnUrl}' as='script'>";
                    break;
                case 'font':
                    $hints[] = "<link rel='preload' href='{$cdnUrl}' as='font' type='font/woff2' crossorigin>";
                    break;
                case 'image':
                    $hints[] = "<link rel='preload' href='{$cdnUrl}' as='image'>";
                    break;
            }
        }
        
        return implode("\n", $hints);
    }

    /**
     * Get asset type from file extension
     */
    private function getAssetType($assetPath) {
        $extension = strtolower(pathinfo($assetPath, PATHINFO_EXTENSION));
        
        $typeMap = [
            'css' => 'css',
            'js' => 'js',
            'woff' => 'font',
            'woff2' => 'font',
            'ttf' => 'font',
            'eot' => 'font',
            'jpg' => 'image',
            'jpeg' => 'image',
            'png' => 'image',
            'gif' => 'image',
            'svg' => 'image',
            'webp' => 'image'
        ];
        
        return $typeMap[$extension] ?? 'other';
    }

    /**
     * Optimize images
     */
    public function optimizeImage($imagePath, $options = []) {
        if (!$this->config['asset_optimization']['image_optimization']) {
            return $imagePath;
        }
        
        $pathInfo = pathinfo($imagePath);
        $extension = strtolower($pathInfo['extension']);
        
        // Skip if not an image
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            return $imagePath;
        }
        
        $optimizedPath = $pathInfo['dirname'] . '/optimized_' . $pathInfo['basename'];
        
        // Check if optimized version exists and is newer
        if (file_exists($optimizedPath) && filemtime($optimizedPath) > filemtime($imagePath)) {
            return $optimizedPath;
        }
        
        $quality = $options['quality'] ?? 85;
        $maxWidth = $options['max_width'] ?? 1920;
        $maxHeight = $options['max_height'] ?? 1080;
        
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) return $imagePath;
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $type = $imageInfo[2];
        
        // Calculate new dimensions
        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
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
                return $imagePath;
        }
        
        if (!$source) return $imagePath;
        
        // Create optimized image
        $optimized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagealphablending($optimized, false);
            imagesavealpha($optimized, true);
            $transparent = imagecolorallocatealpha($optimized, 255, 255, 255, 127);
            imagefill($optimized, 0, 0, $transparent);
        }
        
        imagecopyresampled($optimized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Save optimized image
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
        
        return $result ? $optimizedPath : $imagePath;
    }

    /**
     * Convert image to WebP format
     */
    public function convertToWebP($imagePath, $quality = 85) {
        if (!$this->config['asset_optimization']['webp_conversion']) {
            return $imagePath;
        }
        
        if (!function_exists('imagewebp')) {
            return $imagePath;
        }
        
        $pathInfo = pathinfo($imagePath);
        $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
        
        // Check if WebP version exists and is newer
        if (file_exists($webpPath) && filemtime($webpPath) > filemtime($imagePath)) {
            return $webpPath;
        }
        
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) return $imagePath;
        
        $type = $imageInfo[2];
        
        // Create source image
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($imagePath);
                break;
            default:
                return $imagePath;
        }
        
        if (!$source) return $imagePath;
        
        // Convert to WebP
        $result = imagewebp($source, $webpPath, $quality);
        imagedestroy($source);
        
        return $result ? $webpPath : $imagePath;
    }

    /**
     * Generate lazy loading attributes
     */
    public function generateLazyLoadAttributes($src, $options = []) {
        if (!$this->config['asset_optimization']['lazy_loading']) {
            return ['src' => $src];
        }
        
        $placeholder = $options['placeholder'] ?? 'data:image/svg+xml;base64,' . base64_encode(
            '<svg width="1" height="1" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="#f0f0f0"/></svg>'
        );
        
        return [
            'src' => $placeholder,
            'data-src' => $src,
            'loading' => 'lazy',
            'class' => 'lazy-load'
        ];
    }

    /**
     * Get CDN statistics
     */
    public function getStatistics() {
        $provider = $this->getActiveProvider();
        
        switch ($provider) {
            case 'cloudflare':
                return $this->getCloudflareStatistics();
            default:
                return [
                    'provider' => $provider,
                    'requests' => 0,
                    'bandwidth' => 0,
                    'cache_hit_ratio' => 0,
                    'error_rate' => 0
                ];
        }
    }

    /**
     * Get Cloudflare statistics
     */
    private function getCloudflareStatistics() {
        try {
            $response = $this->makeCloudflareRequest('analytics/dashboard', null, 'GET');
            
            if ($response['success']) {
                $data = $response['result'];
                return [
                    'provider' => 'cloudflare',
                    'requests' => $data['totals']['requests']['all'] ?? 0,
                    'bandwidth' => $data['totals']['bandwidth']['all'] ?? 0,
                    'cache_hit_ratio' => $data['totals']['requests']['cached'] / max($data['totals']['requests']['all'], 1) * 100,
                    'error_rate' => ($data['totals']['requests']['ssl']['encrypted'] ?? 0) / max($data['totals']['requests']['all'], 1) * 100
                ];
            }
        } catch (Exception $e) {
            $this->log("Failed to get Cloudflare statistics: " . $e->getMessage(), 'error');
        }
        
        return [
            'provider' => 'cloudflare',
            'requests' => 0,
            'bandwidth' => 0,
            'cache_hit_ratio' => 0,
            'error_rate' => 0
        ];
    }

    /**
     * Initialize asset cache
     */
    private function initializeAssetCache() {
        $this->assetCache = new class {
            private $cacheDir;
            
            public function __construct() {
                $this->cacheDir = __DIR__ . '/../../cache/assets';
                if (!is_dir($this->cacheDir)) {
                    mkdir($this->cacheDir, 0755, true);
                }
            }
            
            public function get($key) {
                $file = $this->cacheDir . '/' . md5($key);
                return file_exists($file) ? file_get_contents($file) : false;
            }
            
            public function set($key, $content, $ttl = 3600) {
                $file = $this->cacheDir . '/' . md5($key);
                return file_put_contents($file, $content) !== false;
            }
            
            public function delete($key) {
                $file = $this->cacheDir . '/' . md5($key);
                return file_exists($file) ? unlink($file) : true;
            }
        };
    }

    /**
     * Initialize optimization rules
     */
    private function initializeOptimizationRules() {
        $this->optimizationRules = [
            'css' => [
                'minify' => true,
                'combine' => true,
                'critical' => true
            ],
            'js' => [
                'minify' => true,
                'combine' => true,
                'defer' => true
            ],
            'images' => [
                'optimize' => true,
                'webp' => true,
                'lazy_load' => true,
                'responsive' => true
            ]
        ];
    }

    /**
     * Initialize logging
     */
    private function initializeLogger() {
        $this->logger = [
            'file' => __DIR__ . '/../../logs/cdn.log'
        ];
        
        $logDir = dirname($this->logger['file']);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Log CDN message
     */
    private function log($message, $level = 'info') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        file_put_contents($this->logger['file'], $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Preload critical assets
     */
    public function preloadCriticalAssets() {
        $criticalAssets = [
            '/css/critical.css',
            '/js/critical.js',
            '/fonts/main.woff2'
        ];
        
        $hints = [];
        foreach ($criticalAssets as $asset) {
            $cdnUrl = $this->getCDNUrl($asset);
            $type = $this->getAssetType($asset);
            
            switch ($type) {
                case 'css':
                    $hints[] = "<link rel='preload' href='{$cdnUrl}' as='style'>";
                    break;
                case 'js':
                    $hints[] = "<link rel='preload' href='{$cdnUrl}' as='script'>";
                    break;
                case 'font':
                    $hints[] = "<link rel='preload' href='{$cdnUrl}' as='font' type='font/woff2' crossorigin>";
                    break;
            }
        }
        
        return implode("\n", $hints);
    }

    /**
     * Set cache headers for assets
     */
    public function setCacheHeaders($assetPath) {
        $extension = strtolower(pathinfo($assetPath, PATHINFO_EXTENSION));
        
        $typeMap = [
            'css' => 'css',
            'js' => 'js',
            'jpg' => 'images',
            'jpeg' => 'images',
            'png' => 'images',
            'gif' => 'images',
            'svg' => 'images',
            'woff' => 'fonts',
            'woff2' => 'fonts',
            'ttf' => 'fonts',
            'eot' => 'fonts'
        ];
        
        $type = $typeMap[$extension] ?? 'html';
        $cacheControl = $this->config['cache_control'][$type] ?? 'max-age=3600, public';
        
        header("Cache-Control: {$cacheControl}");
        
        if (in_array($type, ['css', 'js', 'fonts'])) {
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        }
    }

    /**
     * Get asset version for cache busting
     */
    public function getAssetVersion($assetPath) {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $assetPath;
        
        if (file_exists($fullPath)) {
            return '?v=' . filemtime($fullPath);
        }
        
        return '?v=' . time();
    }
}
?>
