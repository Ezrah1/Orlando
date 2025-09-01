<?php
/**
 * Settings Management Class
 * Handles system configuration storage and retrieval
 */
class Settings {
    private $con;
    private static $cache = [];
    
    public function __construct($connection) {
        $this->con = $connection;
    }
    
    /**
     * Get a setting value by key
     */
    public function get($key, $default = null) {
        // Check cache first
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        
        $stmt = mysqli_prepare($this->con, "SELECT setting_value, setting_type FROM system_settings WHERE setting_key = ?");
        mysqli_stmt_bind_param($stmt, "s", $key);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $value = $this->castValue($row['setting_value'], $row['setting_type']);
            self::$cache[$key] = $value;
            return $value;
        }
        
        return $default;
    }
    
    /**
     * Set a setting value
     */
    public function set($key, $value, $type = 'text', $category = 'general', $description = '') {
        // Clear cache
        unset(self::$cache[$key]);
        
        $stmt = mysqli_prepare($this->con, "
            INSERT INTO system_settings (setting_key, setting_value, setting_type, category, description) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value),
            setting_type = VALUES(setting_type),
            category = VALUES(category),
            description = VALUES(description),
            updated_at = CURRENT_TIMESTAMP
        ");
        
        mysqli_stmt_bind_param($stmt, "sssss", $key, $value, $type, $category, $description);
        return mysqli_stmt_execute($stmt);
    }
    
    /**
     * Get all settings by category
     */
    public function getByCategory($category) {
        $stmt = mysqli_prepare($this->con, "
            SELECT setting_key, setting_value, setting_type, description, is_public 
            FROM system_settings 
            WHERE category = ? 
            ORDER BY setting_key
        ");
        mysqli_stmt_bind_param($stmt, "s", $category);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $settings = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $settings[$row['setting_key']] = [
                'value' => $this->castValue($row['setting_value'], $row['setting_type']),
                'type' => $row['setting_type'],
                'description' => $row['description'],
                'is_public' => (bool)$row['is_public']
            ];
        }
        
        return $settings;
    }
    
    /**
     * Get all settings grouped by category
     */
    public function getAllGrouped() {
        $result = mysqli_query($this->con, "
            SELECT setting_key, setting_value, setting_type, category, description, is_public 
            FROM system_settings 
            ORDER BY category, setting_key
        ");
        
        $grouped = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $grouped[$row['category']][$row['setting_key']] = [
                'value' => $this->castValue($row['setting_value'], $row['setting_type']),
                'type' => $row['setting_type'],
                'description' => $row['description'],
                'is_public' => (bool)$row['is_public']
            ];
        }
        
        return $grouped;
    }
    
    /**
     * Update multiple settings at once
     */
    public function updateMultiple($settings) {
        $success = true;
        
        mysqli_autocommit($this->con, false);
        
        foreach ($settings as $key => $value) {
            $stmt = mysqli_prepare($this->con, "
                UPDATE system_settings 
                SET setting_value = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE setting_key = ?
            ");
            mysqli_stmt_bind_param($stmt, "ss", $value, $key);
            
            if (!mysqli_stmt_execute($stmt)) {
                $success = false;
                break;
            }
            
            // Clear cache
            unset(self::$cache[$key]);
        }
        
        if ($success) {
            mysqli_commit($this->con);
        } else {
            mysqli_rollback($this->con);
        }
        
        mysqli_autocommit($this->con, true);
        return $success;
    }
    
    /**
     * Cast setting value to appropriate type
     */
    private function castValue($value, $type) {
        switch ($type) {
            case 'boolean':
                return (bool)$value;
            case 'number':
                return is_numeric($value) ? (float)$value : $value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }
    
    /**
     * Get public settings (safe for frontend)
     */
    public function getPublicSettings() {
        $result = mysqli_query($this->con, "
            SELECT setting_key, setting_value, setting_type 
            FROM system_settings 
            WHERE is_public = 1
        ");
        
        $settings = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $settings[$row['setting_key']] = $this->castValue($row['setting_value'], $row['setting_type']);
        }
        
        return $settings;
    }
    
    /**
     * Delete a setting
     */
    public function delete($key) {
        unset(self::$cache[$key]);
        
        $stmt = mysqli_prepare($this->con, "DELETE FROM system_settings WHERE setting_key = ?");
        mysqli_stmt_bind_param($stmt, "s", $key);
        return mysqli_stmt_execute($stmt);
    }
    
    /**
     * Check if setting exists
     */
    public function exists($key) {
        $stmt = mysqli_prepare($this->con, "SELECT 1 FROM system_settings WHERE setting_key = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $key);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        return mysqli_num_rows($result) > 0;
    }
    
    /**
     * Get hotel information as an object/array
     */
    public function getHotelInfo() {
        $hotelSettings = $this->getByCategory('hotel');
        
        return [
            'name' => $hotelSettings['hotel_name']['value'] ?? 'Orlando International Resorts',
            'address' => $hotelSettings['hotel_address']['value'] ?? 'Machakos Town, Kenya',
            'phone' => $hotelSettings['hotel_phone']['value'] ?? '+254 742 824 006',
            'email' => $hotelSettings['hotel_email']['value'] ?? 'info@orlandointernationalresort.net',
            'website' => $hotelSettings['hotel_website']['value'] ?? '',
            'description' => $hotelSettings['hotel_description']['value'] ?? '',
            'city' => $hotelSettings['hotel_city']['value'] ?? 'Machakos',
            'state' => $hotelSettings['hotel_state']['value'] ?? 'Machakos County',
            'country' => $hotelSettings['hotel_country']['value'] ?? 'Kenya',
            'postal_code' => $hotelSettings['hotel_postal_code']['value'] ?? '90100',
            'whatsapp' => $hotelSettings['hotel_whatsapp']['value'] ?? '+254742824006',
            'facebook' => $hotelSettings['hotel_facebook']['value'] ?? '',
            'instagram' => $hotelSettings['hotel_instagram']['value'] ?? '',
            'twitter' => $hotelSettings['hotel_twitter']['value'] ?? '',
        ];
    }
    
    /**
     * Get business configuration
     */
    public function getBusinessConfig() {
        $businessSettings = $this->getByCategory('business');
        
        return [
            'check_in_time' => $businessSettings['check_in_time']['value'] ?? '14:00',
            'check_out_time' => $businessSettings['check_out_time']['value'] ?? '11:00',
            'currency_symbol' => $businessSettings['currency_symbol']['value'] ?? 'KES',
            'currency_code' => $businessSettings['currency_code']['value'] ?? 'KES',
            'tax_rate' => $businessSettings['tax_rate']['value'] ?? '16.00',
            'service_charge' => $businessSettings['service_charge']['value'] ?? '10.00',
            'cancellation_policy' => $businessSettings['cancellation_policy']['value'] ?? '',
            'payment_methods' => $businessSettings['payment_methods']['value'] ?? 'M-Pesa, Cash, Bank Transfer',
        ];
    }
    
    /**
     * Get formatted contact information for display
     */
    public function getContactDisplay() {
        $info = $this->getHotelInfo();
        
        return [
            'phone_display' => $info['phone'],
            'phone_link' => 'tel:' . str_replace(' ', '', $info['phone']),
            'email_display' => $info['email'],
            'email_link' => 'mailto:' . $info['email'],
            'whatsapp_display' => $info['whatsapp'],
            'whatsapp_link' => 'https://wa.me/' . str_replace(['+', ' '], '', $info['whatsapp']),
            'address_display' => $info['address'],
            'full_address' => $info['address'] . ', ' . $info['city'] . ', ' . $info['country'],
        ];
    }
    
    /**
     * Global function to get hotel settings instance
     */
    public static function getInstance($connection = null) {
        static $instance = null;
        if ($instance === null) {
            global $con;
            $connection = $connection ?: $con;
            $instance = new self($connection);
        }
        return $instance;
    }
}
?>
