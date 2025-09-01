<?php
/**
 * Booking QR Code Generator
 * Generates QR codes for booking confirmations with secure URLs
 */

class BookingQRGenerator {
    
    /**
     * Generate QR code HTML for booking confirmation
     */
    public static function generateBookingQR($booking_ref, $size = 80) {
        // Create the booking confirmation URL
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $confirmation_url = $base_url . "/Hotel/modules/guest/booking/booking_confirmation.php?booking_ref=" . urlencode($booking_ref);
        
        // Try multiple QR code generation methods
        $qr_html = self::generateQRWithMultipleMethods($confirmation_url, $size, $booking_ref);
        
        return $qr_html;
    }
    
    /**
     * Try multiple QR generation methods for reliability
     */
    private static function generateQRWithMultipleMethods($url, $size, $booking_ref) {
        $encoded_url = urlencode($url);
        
        // Single QR image with fallback to placeholder only if image fails to load
        $qr_html = '<div class="qr-container" style="width: ' . $size . 'px; height: ' . $size . 'px; margin: 0 auto;">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . $encoded_url . '" 
                             alt="Booking QR Code for ' . htmlspecialchars($booking_ref) . '" 
                             style="width: ' . $size . 'px; height: ' . $size . 'px; border: 1px solid #ddd; display: block;"
                             onerror="this.style.display=\'none\'; this.parentElement.querySelector(\'.qr-fallback\').classList.add(\'show\');">
                        <div class="qr-fallback" style="width: ' . $size . 'px; height: ' . $size . 'px;">
                            QR Code<br><small>' . htmlspecialchars($booking_ref) . '</small>
                        </div>
                    </div>';
        
        return $qr_html;
    }
    
    /**
     * Generate test QR code for debugging
     */
    public static function generateTestQR($test_url, $size = 100) {
        $encoded_url = urlencode($test_url);
        
        return '<div style="text-align: center; padding: 10px; border: 1px solid #ddd; margin: 10px;">
                    <h4>Test QR Code</h4>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . $encoded_url . '" 
                         alt="Test QR Code" 
                         style="width: ' . $size . 'px; height: ' . $size . 'px; border: 1px solid #ccc;">
                    <p style="font-size: 10px; margin: 5px 0; word-break: break-all;">URL: ' . htmlspecialchars($test_url) . '</p>
                </div>';
    }
    
    /**
     * Validate booking reference format
     */
    public static function validateBookingRef($booking_ref) {
        // Check if booking reference follows expected format (adjust as needed)
        return preg_match('/^BK\d{12}$/', $booking_ref);
    }
    
    /**
     * Generate secure booking link with timestamp
     */
    public static function generateSecureBookingLink($booking_ref) {
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $timestamp = time();
        $token = hash('sha256', $booking_ref . $timestamp . 'hotel_secret_key');
        
        return $base_url . "/Hotel/modules/guest/booking/booking_confirmation.php?booking_ref=" . urlencode($booking_ref) . "&t=" . $timestamp . "&token=" . $token;
    }
}

?>
