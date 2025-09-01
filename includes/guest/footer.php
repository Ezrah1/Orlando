<?php
/**
 * Guest Footer - For all guest-facing pages
 * Modern footer with links and JavaScript includes
 */

// Ensure config is loaded
if (!defined('APP_ROOT')) {
    require_once __DIR__ . '/../common/config.php';
}

// Include hotel settings for dynamic contact information
require_once __DIR__ . '/../common/hotel_settings.php';

$path_prefix = $GLOBALS['path_prefix'];
?>

<!-- Modern Footer -->
<footer class="modern-footer">
    <div class="footer-main">
        <div class="container">
            <div class="row">
                <!-- Company Info -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="footer-section">
                        <h5 class="footer-title">
                            <img src="<?php echo $path_prefix; ?>images/logo-full.png" alt="<?php echo SITE_NAME; ?>" class="footer-logo">
                        </h5>
                        <p class="footer-description">
                            Experience luxury and comfort at <?php echo SITE_NAME; ?> in Machakos, Kenya. 
                            Where exceptional service meets affordable elegance.
                        </p>
                        <div class="footer-social">
                            <a href="https://www.facebook.com/" class="social-link facebook" target="_blank">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://twitter.com/" class="social-link twitter" target="_blank">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://instagram.com/" class="social-link instagram" target="_blank">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="https://linkedin.com/" class="social-link linkedin" target="_blank">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <div class="footer-section">
                        <h6 class="footer-title">Quick Links</h6>
                        <ul class="footer-links">
                            <li><a href="<?php echo $path_prefix; ?>index.php">Home</a></li>
                            <li><a href="<?php echo $path_prefix; ?>index.php#rooms">Rooms</a></li>
                            <li><a href="http://localhost/Hotel/modules/guest/menu/menu_enhanced.php">Menu</a></li>
                            <li><a href="<?php echo $path_prefix; ?>index.php#services">Services</a></li>
                            <li><a href="<?php echo $path_prefix; ?>index.php#amenities">Amenities</a></li>
                            <li><a href="<?php echo $path_prefix; ?>index.php#contact">Contact</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Services -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <div class="footer-section">
                        <h6 class="footer-title">Services</h6>
                        <ul class="footer-links">
                            <li><a href="<?php echo $path_prefix; ?>modules/guest/booking/booking_form.php">Room Booking</a></li>
                            <li><a href="http://localhost/Hotel/modules/guest/menu/menu_enhanced.php">Restaurant</a></li>
                            <li><a href="<?php echo $path_prefix; ?>index.php#amenities">Conference Hall</a></li>
                            <li><a href="<?php echo $path_prefix; ?>index.php#services">Event Planning</a></li>
                            <li><a href="<?php echo $path_prefix; ?>index.php#services">Airport Transfer</a></li>
                            <li><a href="<?php echo $path_prefix; ?>index.php#services">Spa & Wellness</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="footer-section">
                        <h6 class="footer-title">Contact Information</h6>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div class="contact-content">
                                <strong>Address</strong>
                                <p><?php echo htmlspecialchars(get_hotel_info('address')); ?></p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <div class="contact-content">
                                <strong>Phone</strong>
                                <p><a href="<?php echo get_phone_link(); ?>"><?php echo htmlspecialchars(get_hotel_info('phone')); ?></a></p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <div class="contact-content">
                                <strong>Email</strong>
                                <p><a href="<?php echo get_email_link(); ?>"><?php echo htmlspecialchars(get_hotel_info('email')); ?></a></p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <div class="contact-content">
                                <strong>Reception</strong>
                                <p>24/7 Available</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="copyright">
                        © <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All Rights Reserved | 
                        <a href="<?php echo $path_prefix; ?>legal/privacy-policy.php">Privacy Policy</a> | 
                        <a href="<?php echo $path_prefix; ?>legal/terms-of-service.php">Terms of Service</a>
                    </p>
                </div>
                <div class="col-md-6 text-md-right">
                    <div class="footer-actions">
                        <a href="<?php echo $path_prefix; ?>modules/guest/booking/booking_form.php" class="btn btn-primary btn-sm footer-btn">
                            <i class="fas fa-calendar-check"></i> Book Your Stay
                        </a>
                        <a href="<?php echo get_phone_link(); ?>" class="btn btn-success btn-sm footer-btn">
                            <i class="fas fa-phone"></i> Call Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button id="backToTop" class="back-to-top" title="Back to Top">
    <i class="fas fa-chevron-up"></i>
</button>

<!-- JavaScript Files -->
<script type="text/javascript" src="<?php echo $path_prefix; ?>js/jquery-2.1.4.min.js"></script>
<script src="<?php echo $path_prefix; ?>js/jqBootstrapValidation.js"></script>
<script src="<?php echo $path_prefix; ?>js/jquery-ui.js"></script>
<script src="<?php echo $path_prefix; ?>js/jquery.swipebox.min.js"></script>
<script type="text/javascript" src="<?php echo $path_prefix; ?>js/move-top.js"></script>
<script type="text/javascript" src="<?php echo $path_prefix; ?>js/easing.js"></script>
<script defer src="<?php echo $path_prefix; ?>js/jquery.flexslider.js"></script>
<script src="<?php echo $path_prefix; ?>js/responsiveslides.min.js"></script>
<script src="<?php echo $path_prefix; ?>js/main.js"></script>
<script src="<?php echo $path_prefix; ?>js/easy-responsive-tabs.js"></script>
<script type="text/javascript" src="<?php echo $path_prefix; ?>js/bootstrap-3.1.1.min.js"></script>

<!-- Footer Styles -->
<style>
/* ===== MODERN FOOTER DESIGN ===== */

.modern-footer {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #2c3e50 100%);
    color: white;
    margin-top: 50px;
}

.footer-main {
    padding: 60px 0 30px;
}

.footer-section {
    height: 100%;
}

.footer-title {
    color: white;
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #3498db;
    display: inline-block;
}

.footer-logo {
    height: 60px;
    width: auto;
    filter: brightness(0) invert(1);
}

.footer-description {
    color: rgba(255,255,255,0.8);
    line-height: 1.6;
    margin-bottom: 20px;
}

.footer-social {
    display: flex;
    gap: 15px;
}

.social-link {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
    border: 2px solid rgba(255,255,255,0.2);
}

.social-link:hover {
    color: white;
    text-decoration: none;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.social-link.facebook:hover { background: #3b5998; border-color: #3b5998; }
.social-link.twitter:hover { background: #1da1f2; border-color: #1da1f2; }
.social-link.instagram:hover { background: #e4405f; border-color: #e4405f; }
.social-link.linkedin:hover { background: #0077b5; border-color: #0077b5; }

.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 10px;
}

.footer-links a {
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    padding: 5px 0;
}

.footer-links a:hover {
    color: #3498db;
    text-decoration: none;
    padding-left: 10px;
}

.footer-links a:before {
    content: '›';
    margin-right: 8px;
    font-weight: 600;
    color: #3498db;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.footer-links a:hover:before {
    opacity: 1;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 20px;
    gap: 15px;
}

.contact-item i {
    color: #3498db;
    font-size: 1.2rem;
    margin-top: 5px;
    width: 20px;
    text-align: center;
}

.contact-content {
    flex: 1;
}

.contact-content strong {
    display: block;
    color: white;
    font-weight: 600;
    margin-bottom: 5px;
}

.contact-content p {
    color: rgba(255,255,255,0.8);
    margin: 0;
    line-height: 1.4;
}

.contact-content a {
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: color 0.3s ease;
}

.contact-content a:hover {
    color: #3498db;
    text-decoration: none;
}

.footer-bottom {
    background: rgba(0,0,0,0.2);
    padding: 20px 0;
    border-top: 1px solid rgba(255,255,255,0.1);
}

.copyright {
    color: rgba(255,255,255,0.7);
    margin: 0;
    font-size: 0.9rem;
}

.copyright a {
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: color 0.3s ease;
}

.copyright a:hover {
    color: #3498db;
    text-decoration: none;
}

.footer-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.footer-btn {
    border-radius: 20px;
    padding: 8px 16px;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
}

.footer-btn:hover {
    transform: translateY(-2px);
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.footer-btn i {
    margin-right: 5px;
}

/* Back to Top Button */
.back-to-top {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    border: none;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transform: translateY(20px);
}

.back-to-top.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.back-to-top:hover {
    background: linear-gradient(135deg, #2980b9, #3498db);
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
}

/* Responsive Design */
@media (max-width: 768px) {
    .footer-main {
        padding: 40px 0 20px;
    }
    
    .footer-actions {
        justify-content: center;
        margin-top: 15px;
    }
    
    .footer-bottom .row {
        text-align: center;
    }
    
    .footer-bottom .col-md-6:last-child {
        text-align: center !important;
    }
    
    .back-to-top {
        bottom: 20px;
        right: 20px;
        width: 45px;
        height: 45px;
        font-size: 16px;
    }
}

@media (max-width: 576px) {
    .footer-actions {
        flex-direction: column;
        gap: 8px;
        width: 100%;
    }
    
    .footer-btn {
        width: 100%;
        text-align: center;
    }
    
    .contact-item {
        margin-bottom: 15px;
    }
    
    .footer-social {
        justify-content: center;
    }
}
</style>

<!-- Footer JavaScript -->
<script>
$(document).ready(function() {
    // Smooth scrolling for footer links
    $(".footer-links a[href^='#']").click(function(event){		
        event.preventDefault();
        var target = $(this.hash);
        if (target.length) {
            $('html,body').animate({
                scrollTop: target.offset().top - 100
            }, 1000);
        }
    });

    // Back to top button
    var backToTop = $('#backToTop');
    
    $(window).scroll(function() {
        if ($(this).scrollTop() > 300) {
            backToTop.addClass('show');
        } else {
            backToTop.removeClass('show');
        }
    });
    
    backToTop.click(function() {
        $('html, body').animate({
            scrollTop: 0
        }, 800);
        return false;
    });

    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Contact form validation (if exists)
    if ($('#contactForm').length) {
        $('#contactForm').on('submit', function(e) {
            var isValid = true;
            var requiredFields = $(this).find('[required]');
            
            requiredFields.each(function() {
                if (!$(this).val().trim()) {
                    $(this).addClass('is-invalid');
                    isValid = false;
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showNotification('Please fill in all required fields', 'warning');
            }
        });
    }

    // Newsletter subscription (if exists)
    if ($('#newsletterForm').length) {
        $('#newsletterForm').on('submit', function(e) {
            e.preventDefault();
            var email = $('#newsletter_email').val();
            
            if (email && isValidEmail(email)) {
                // Here you would typically send an AJAX request
                showNotification('Thank you for subscribing to our newsletter!', 'success');
                $('#newsletter_email').val('');
            } else {
                showNotification('Please enter a valid email address', 'warning');
            }
        });
    }

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Lazy loading for images (if needed)
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
});

// Helper functions
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showNotification(message, type = 'info') {
    const notification = $(`
        <div class="alert alert-${type} alert-dismissible fade show message-alert" style="position: fixed; top: 140px; right: 20px; z-index: 10001; max-width: 400px;">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong>${type === 'success' ? '✓' : type === 'warning' ? '⚠' : 'ℹ'}</strong> ${message}
        </div>
    `);
    
    $('body').append(notification);
    
    setTimeout(() => {
        notification.fadeOut('slow', function() {
            $(this).remove();
        });
    }, 5000);
}

// Google Analytics (replace with your tracking ID)
// gtag('config', 'GA_TRACKING_ID');

// Performance monitoring
if ('performance' in window && 'mark' in performance) {
    performance.mark('footer-loaded');
}
</script>

<!-- Custom page scripts placeholder -->
<?php if (isset($page_scripts)): ?>
    <?php echo $page_scripts; ?>
<?php endif; ?>

<!-- Floating Shopping Cart Widget -->
<?php 
// Set base URL for cart links based on current location
$floating_cart_base_url = '';
if (strpos($_SERVER['REQUEST_URI'], '/modules/guest/') !== false) {
    $floating_cart_base_url = '../../../';
}
include(__DIR__ . '/../components/floating_cart.php'); 
?>

<!-- Maya AI Assistant Widget -->
<?php include_once(__DIR__ . '/../../maya/components/maya_ai_widget.php'); ?>

</body>
</html>
