<?php
/**
 * Contact Information Widget
 * Displays dynamic hotel contact information
 */

// Include hotel settings if not already included
if (!function_exists('get_hotel_info')) {
    require_once __DIR__ . '/../common/hotel_settings.php';
}

$contact = get_contact_display();
$hotel_info = get_hotel_info();

// Widget styles (inline for portability)
$widget_styles = '
<style>
.contact-widget {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin: 15px 0;
}

.contact-widget h5 {
    color: #fff;
    margin-bottom: 15px;
    font-weight: 600;
}

.contact-item {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    font-size: 14px;
}

.contact-item i {
    width: 20px;
    margin-right: 10px;
    color: #ffce14;
}

.contact-item a {
    color: #fff;
    text-decoration: none;
}

.contact-item a:hover {
    color: #ffce14;
    text-decoration: none;
}

.quick-contact-buttons {
    margin-top: 15px;
}

.quick-contact-buttons .btn {
    margin-right: 10px;
    margin-bottom: 5px;
    border-radius: 20px;
    font-size: 12px;
    padding: 5px 15px;
}

.quick-contact-buttons .btn-success {
    background: #25d366;
    border-color: #25d366;
}

.quick-contact-buttons .btn-info {
    background: #17a2b8;
    border-color: #17a2b8;
}
</style>
';

?>

<?php echo $widget_styles; ?>

<div class="contact-widget">
    <h5><i class="fas fa-phone"></i> Contact <?php echo htmlspecialchars($hotel_info['name']); ?></h5>
    
    <?php if ($contact['phone_display']): ?>
    <div class="contact-item">
        <i class="fas fa-phone"></i>
        <a href="<?php echo $contact['phone_link']; ?>">
            <?php echo htmlspecialchars($contact['phone_display']); ?>
        </a>
    </div>
    <?php endif; ?>
    
    <?php if ($contact['email_display']): ?>
    <div class="contact-item">
        <i class="fas fa-envelope"></i>
        <a href="<?php echo $contact['email_link']; ?>">
            <?php echo htmlspecialchars($contact['email_display']); ?>
        </a>
    </div>
    <?php endif; ?>
    
    <?php if ($contact['address_display']): ?>
    <div class="contact-item">
        <i class="fas fa-map-marker-alt"></i>
        <span><?php echo htmlspecialchars($contact['full_address']); ?></span>
    </div>
    <?php endif; ?>
    
    <div class="quick-contact-buttons">
        <?php if ($contact['phone_display']): ?>
        <a href="<?php echo $contact['phone_link']; ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-phone"></i> Call Now
        </a>
        <?php endif; ?>
        
        <?php if ($contact['whatsapp_display']): ?>
        <a href="<?php echo $contact['whatsapp_link']; ?>" class="btn btn-success btn-sm" target="_blank">
            <i class="fab fa-whatsapp"></i> WhatsApp
        </a>
        <?php endif; ?>
        
        <?php if ($contact['email_display']): ?>
        <a href="<?php echo $contact['email_link']; ?>" class="btn btn-info btn-sm">
            <i class="fas fa-envelope"></i> Email
        </a>
        <?php endif; ?>
    </div>
</div>
