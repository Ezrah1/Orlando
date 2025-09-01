<?php
/**
 * QR Code Generator for Hotel Booking System
 * Uses Google Charts API for QR code generation
 */

class QRGenerator {
    
    /**
     * Generate QR code URL using Google Charts API
     * @param string $data - Data to encode in QR code
     * @param int $size - Size of QR code (default: 200)
     * @param string $encoding - Character encoding (default: UTF-8)
     * @return string - QR code image URL
     */
    public static function generateQRCodeURL($data, $size = 200, $encoding = 'UTF-8') {
        $baseURL = 'https://chart.googleapis.com/chart';
        $params = array(
            'chs' => $size . 'x' . $size,
            'cht' => 'qr',
            'chl' => urlencode($data),
            'choe' => $encoding
        );
        
        return $baseURL . '?' . http_build_query($params);
    }
    
    /**
     * Generate booking QR code data
     * @param array $booking - Booking data array
     * @return string - Formatted booking data for QR code
     */
    public static function generateBookingQRData($booking) {
        $qr_data = array(
            'booking_ref' => $booking['booking_ref'],
            'guest_name' => trim($booking['FName'] . ' ' . $booking['LName']),
            'checkin' => $booking['cin'],
            'checkout' => $booking['cout'],
            'room' => $booking['TRoom'],
            'hotel' => 'Orlando International Resorts'
        );
        
        // Create a simple JSON format for QR code
        return json_encode($qr_data);
    }
    
    /**
     * Generate simple text format for QR code (more readable)
     * @param array $booking - Booking data array
     * @return string - Simple text format for QR code
     */
    public static function generateSimpleBookingQRData($booking) {
        $guest_name = trim($booking['FName'] . ' ' . $booking['LName']);
        $checkin = date('d/m/Y', strtotime($booking['cin']));
        $checkout = date('d/m/Y', strtotime($booking['cout']));
        
        return "HOTEL BOOKING\n" .
               "Ref: " . $booking['booking_ref'] . "\n" .
               "Guest: " . $guest_name . "\n" .
               "Room: " . $booking['TRoom'] . "\n" .
               "Check-in: " . $checkin . "\n" .
               "Check-out: " . $checkout . "\n" .
               "Orlando International Resorts";
    }
    
    /**
     * Generate compact QR code data for better scannability
     * @param array $booking - Booking data array
     * @return string - Compact format optimized for QR scanning
     */
    public static function generateCompactBookingQRData($booking) {
        $guest_name = trim($booking['FName'] . ' ' . $booking['LName']);
        $checkin = date('d/m/y', strtotime($booking['cin']));
        $checkout = date('d/m/y', strtotime($booking['cout']));
        
        // Compact format: REF|GUEST|ROOM|IN|OUT
        return $booking['booking_ref'] . "|" . 
               $guest_name . "|" . 
               $booking['TRoom'] . "|" . 
               $checkin . "|" . 
               $checkout;
    }
    
    /**
     * Generate URL-based QR code for booking details page
     * @param array $booking - Booking data array
     * @param string $base_url - Base URL of the website
     * @return string - URL for booking details
     */
    public static function generateBookingURLQRData($booking, $base_url = 'http://localhost/Hotel') {
        return $base_url . '/modules/guest/booking/booking_confirmation.php?booking_ref=' . $booking['booking_ref'];
    }
    
    /**
     * Generate QR code for booking confirmation
     * @param array $booking - Booking data array
     * @param int $size - QR code size (default: 200)
     * @param string $format - 'json', 'simple', 'compact', or 'url' (default: compact)
     * @return string - QR code image URL
     */
    public static function generateBookingQRCode($booking, $size = 200, $format = 'compact') {
        switch ($format) {
            case 'json':
                $qr_data = self::generateBookingQRData($booking);
                break;
            case 'simple':
                $qr_data = self::generateSimpleBookingQRData($booking);
                break;
            case 'url':
                $qr_data = self::generateBookingURLQRData($booking);
                break;
            case 'compact':
            default:
                $qr_data = self::generateCompactBookingQRData($booking);
                break;
        }
        
        return self::generateQRCodeURL($qr_data, $size);
    }
    
    /**
     * Generate QR code HTML for display
     * @param array $booking - Booking data array
     * @param int $size - QR code size (default: 200)
     * @param string $format - 'json', 'simple', 'compact', or 'url' (default: compact)
     * @param array $options - Additional options for styling
     * @return string - HTML for QR code display
     */
    public static function generateQRCodeHTML($booking, $size = 200, $format = 'compact', $options = array()) {
        $qr_url = self::generateBookingQRCode($booking, $size, $format);
        
        $css_class = isset($options['css_class']) ? $options['css_class'] : 'qr-code-image';
        $alt_text = isset($options['alt_text']) ? $options['alt_text'] : 'Booking QR Code';
        $title = isset($options['title']) ? $options['title'] : 'Scan for booking details';
        
        $html = '<div class="qr-code-container">';
        $html .= '<img src="' . htmlspecialchars($qr_url, ENT_QUOTES, 'UTF-8') . '" ';
        $html .= 'alt="' . htmlspecialchars($alt_text, ENT_QUOTES, 'UTF-8') . '" ';
        $html .= 'title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '" ';
        $html .= 'class="' . htmlspecialchars($css_class, ENT_QUOTES, 'UTF-8') . '" ';
        $html .= 'onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'block\';" />';
        
        // Fallback div in case QR code fails to load
        $html .= '<div style="display: none; width: ' . $size . 'px; height: ' . $size . 'px; ';
        $html .= 'background: #f8f9fa; border: 2px dashed #6c757d; border-radius: 8px; ';
        $html .= 'display: flex; align-items: center; justify-content: center; flex-direction: column; ';
        $html .= 'color: #6c757d; font-size: 14px; text-align: center; padding: 20px;">';
        $html .= '<i class="fa fa-qrcode" style="font-size: 2em; margin-bottom: 10px;"></i><br>';
        $html .= 'QR Code<br><small>Ref: ' . htmlspecialchars($booking['booking_ref'], ENT_QUOTES, 'UTF-8') . '</small>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
}
?>
