<?php
/**
 * Guest Header - For all guest-facing pages
 * Modern, responsive header with navigation
 */

// Start session first, before any other includes or output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load hotel settings first (includes database connection and settings)
require_once __DIR__ . '/../common/hotel_settings.php';

// Load common configuration
require_once __DIR__ . '/../common/config.php';
$path_prefix = $GLOBALS['path_prefix'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../common/meta.php'; ?>
    
    <!-- Guest-specific CSS -->
    <link rel="stylesheet" href="<?php echo $path_prefix; ?>css/chocolat.css" type="text/css" media="screen">
    <link href="<?php echo $path_prefix; ?>css/easy-responsive-tabs.css" rel='stylesheet' type='text/css'/>
    <link rel="stylesheet" href="<?php echo $path_prefix; ?>css/flexslider.css" type="text/css" media="screen" property="" />
    <link rel="stylesheet" href="<?php echo $path_prefix; ?>css/jquery-ui.css" />
    <link href="<?php echo $path_prefix; ?>css/style.css" rel="stylesheet" type="text/css" media="all" />
    <link href="<?php echo $path_prefix; ?>css/swipebox.css" rel="stylesheet" type="text/css" media="all" />

    <style>
    /* ===== MODERN GUEST HEADER DESIGN ===== */
    
    /* Fresh Header Container */
    .fresh-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #2c3e50 100%);
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 20px rgba(0,0,0,0.1);
    }

    .fresh-header.scrolled {
        background: rgba(44, 62, 80, 0.98);
        backdrop-filter: blur(15px);
        box-shadow: 0 4px 30px rgba(0,0,0,0.15);
    }

    /* Top Bar */
    .top-bar {
        background: rgba(255,255,255,0.05);
        padding: 8px 0;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .top-bar-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13px;
        color: rgba(255,255,255,0.8);
    }

    .contact-links {
        display: flex;
        gap: 25px;
        align-items: center;
    }

    .contact-links a, .contact-links span {
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 400;
    }

    .contact-links a:hover {
        color: #3498db;
        transform: translateY(-1px);
        text-decoration: none;
    }

    .contact-links i {
        font-size: 14px;
        color: #3498db;
    }

    .social-icons {
        display: flex;
        gap: 12px;
    }

    .social-icons a {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: rgba(255,255,255,0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        transition: all 0.3s ease;
        border: 1px solid rgba(255,255,255,0.2);
    }

    .social-icons a:hover {
        background: #3498db;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.4);
        text-decoration: none;
    }

    /* Main Navigation */
    .main-nav {
        padding: 15px 0;
    }

    .nav-wrapper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 30px;
    }

    /* Brand Section */
    .brand-section {
        display: flex;
        align-items: center;
        gap: 18px;
        flex-shrink: 0;
    }

    .brand-link {
        text-decoration: none;
        color: inherit;
        display: block;
        transition: transform 0.3s ease;
    }

    .brand-link:hover {
        text-decoration: none;
        color: inherit;
        transform: scale(1.02);
    }

    .brand-logo {
        width: 180px;
        height: 80px;
        object-fit: contain;
        filter: brightness(1.1);
        transition: all 0.3s ease;
        background: none !important;
    }

    .brand-logo:hover {
        transform: scale(1.05) rotate(2deg);
        filter: drop-shadow(0 4px 12px rgba(0,0,0,0.3));
    }

    .brand-text {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .brand-subtitle {
        font-size: 11px;
        color: #3498db;
        font-weight: 600;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        margin: 0;
        line-height: 1.2;
    }

    /* Navigation Menu */
    .nav-menu {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .nav-menu li {
        position: relative;
    }

    .nav-menu a {
        color: rgba(255,255,255,0.9);
        text-decoration: none;
        padding: 12px 18px;
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        border-radius: 8px;
        transition: all 0.3s ease;
        display: block;
        position: relative;
        overflow: hidden;
    }

    .nav-menu a::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(52, 152, 219, 0.2), transparent);
        transition: left 0.5s ease;
    }

    .nav-menu a:hover::before {
        left: 100%;
    }

    .nav-menu a:hover,
    .nav-menu .active a {
        color: white;
        background: rgba(52, 152, 219, 0.15);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.2);
        text-decoration: none;
    }

    /* Dropdown Menu Styles */
    .dropdown {
        position: relative;
    }

    .dropdown-menu {
        position: absolute;
        top: 100%;
        left: 0;
        background: white;
        border-radius: 8px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        min-width: 200px;
        z-index: 1000;
        padding: 8px 0;
        margin-top: 10px;
    }

    .dropdown:hover .dropdown-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .dropdown-menu li {
        margin: 0;
        border-bottom: 1px solid #f1f2f6;
    }

    .dropdown-menu li:last-child {
        border-bottom: none;
    }

    .dropdown-menu a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 20px;
        color: #2c3e50;
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 14px;
        font-weight: 500;
    }

    .dropdown-menu a:hover {
        background: #f8f9fa;
        color: #e74c3c;
        transform: translateX(5px);
    }

    .dropdown-menu a i {
        width: 16px;
        text-align: center;
        color: #7f8c8d;
    }

    .dropdown-menu a:hover i {
        color: #e74c3c;
    }

    /* Enhanced CTA Button */
    .cta-button {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 14px 28px;
        border-radius: 50px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        text-decoration: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
        border: 2px solid transparent;
        font-size: 14px;
        position: relative;
        overflow: hidden;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        backdrop-filter: blur(10px);
    }

    .cta-button::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.6s ease;
    }

    .cta-button::after {
        content: '🏨';
        font-size: 16px;
        opacity: 0;
        transform: translateX(10px);
        transition: all 0.3s ease;
    }

    .cta-button:hover::before {
        left: 100%;
    }

    .cta-button:hover::after {
        opacity: 1;
        transform: translateX(0);
    }

    .cta-button:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 12px 35px rgba(102, 126, 234, 0.5);
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        border: 2px solid rgba(255,255,255,0.2);
        color: white;
        text-decoration: none;
        animation: none; /* Stop pulse on hover */
    }

    .cta-button:hover span {
        animation: wiggle 0.6s ease-in-out;
    }

    @keyframes wiggle {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-2px); }
        75% { transform: translateX(2px); }
    }

    .cta-button:active {
        transform: translateY(-2px) scale(0.98);
        transition: all 0.1s ease;
    }

    /* Subtle pulse animation to draw attention */
    .cta-button {
        animation: subtle-pulse 3s ease-in-out infinite;
    }

    @keyframes subtle-pulse {
        0%, 100% {
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
        }
        50% {
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
    }

    /* Enhanced text styling */
    .cta-button span {
        position: relative;
        z-index: 2;
        font-weight: 700;
        text-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    /* Mobile Menu Toggle */
    .mobile-toggle {
        display: none;
        background: none;
        border: none;
        color: white;
        font-size: 22px;
        cursor: pointer;
        padding: 8px;
        border-radius: 6px;
        transition: all 0.3s ease;
    }

    .mobile-toggle:hover {
        background: rgba(52, 152, 219, 0.2);
        transform: scale(1.1);
    }

    /* Cart Icon */
    .cart-icon {
        position: relative;
        color: rgba(255,255,255,0.9);
        font-size: 18px;
        padding: 8px;
        margin-left: 15px;
        transition: all 0.3s ease;
    }

    .cart-icon:hover {
        color: #3498db;
        transform: scale(1.1);
    }

    .cart-count {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #e74c3c;
        color: white;
        font-size: 10px;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 50%;
        min-width: 18px;
        text-align: center;
        line-height: 1.2;
    }

    /* Mobile Responsive */
    @media (max-width: 991px) {
        .top-bar-content {
            flex-direction: column;
            gap: 10px;
        }

        .contact-links {
            gap: 15px;
        }

        .nav-wrapper {
            flex-wrap: wrap;
            gap: 15px;
        }

        .mobile-toggle {
            display: block;
        }

        .nav-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: rgba(44, 62, 80, 0.98);
            backdrop-filter: blur(15px);
            flex-direction: column;
            padding: 20px;
            gap: 8px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .nav-menu.active {
            display: flex;
        }

        .nav-menu a {
            padding: 15px 20px;
            border-radius: 10px;
            text-align: center;
        }

        .cta-button {
            margin: 15px 0 0 0;
            text-align: center;
            display: block;
            padding: 16px 32px;
            font-size: 15px;
            border-radius: 60px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .cta-button::after {
            content: '🏨';
            opacity: 1;
            transform: translateX(0);
        }
    }

    @media (max-width: 576px) {
        .brand-section {
            gap: 12px;
        }

        .brand-logo {
            width: 120px;
            height: 60px;
        }

        .brand-subtitle {
            font-size: 10px;
        }

        .contact-links {
            gap: 10px;
            font-size: 11px;
        }

        .social-icons {
            gap: 8px;
        }

        .social-icons a {
            width: 24px;
            height: 24px;
        }
    }

    /* Body padding for fixed header */
    body {
        padding-top: 130px;
    }

    @media (max-width: 991px) {
        body {
            padding-top: 110px;
        }
    }

    /* Success/Error Messages */
    .page-messages {
        position: fixed;
        top: 140px;
        right: 20px;
        z-index: 1001;
        max-width: 400px;
    }

    .message-alert {
        margin-bottom: 10px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideInRight 0.3s ease;
    }

    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    </style>
</head>
<body>

<!-- Page Messages -->
<div class="page-messages">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible message-alert">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong>✓</strong> <?php echo escape_output($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible message-alert">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong>✗</strong> <?php echo escape_output($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['info_message'])): ?>
        <div class="alert alert-info alert-dismissible message-alert">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong>ℹ</strong> <?php echo escape_output($_SESSION['info_message']); unset($_SESSION['info_message']); ?>
        </div>
    <?php endif; ?>
</div>

<!-- Fresh Modern Header -->
<header class="fresh-header">
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-content">
                <div class="contact-links">
                    <a href="<?php echo get_email_link(); ?>">
                        <i class="fa fa-envelope"></i>
                        <?php echo get_hotel_info('email'); ?>
                    </a>
                    <a href="<?php echo get_phone_link(); ?>">
                        <i class="fa fa-phone"></i>
                        <?php echo get_hotel_info('phone'); ?>
                    </a>
                    <span>
                        <i class="fa fa-map-marker"></i>
                        <?php echo get_hotel_info('address'); ?>
                    </span>
                </div>
                <div class="social-icons">
                    <?php 
                    $facebook = get_hotel_info('facebook');
                    $twitter = get_hotel_info('twitter'); 
                    $instagram = get_hotel_info('instagram');
                    ?>
                    <?php if (!empty($facebook)): ?>
                    <a href="<?php echo htmlspecialchars($facebook); ?>" title="Facebook" target="_blank">
                        <i class="fa fa-facebook"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($twitter)): ?>
                    <a href="<?php echo htmlspecialchars($twitter); ?>" title="Twitter" target="_blank">
                        <i class="fa fa-twitter"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($instagram)): ?>
                    <a href="<?php echo htmlspecialchars($instagram); ?>" title="Instagram" target="_blank">
                        <i class="fa fa-instagram"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <div class="main-nav">
        <div class="container">
            <div class="nav-wrapper">
                <!-- Brand Section -->
                <div class="brand-section">
                    <a href="<?php echo $path_prefix; ?>index.php" class="brand-link">
                        <div class="brand-text">
                            <img src="<?php echo $path_prefix; ?>images/logo-full.png" alt="<?php echo SITE_NAME; ?>" class="brand-logo">
                            <p class="brand-subtitle"><?php echo SITE_TAGLINE; ?></p>
                        </div>
                    </a>
                </div>

                <!-- Navigation Menu -->
                <nav class="nav-menu" id="navMenu">
                    <ul class="nav-menu">
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                            <a href="<?php echo $path_prefix; ?>index.php">Home</a>
                        </li>
                        <li>
                            <a href="<?php echo $path_prefix; ?>index.php#rooms">Rooms</a>
                        </li>
                        <li class="dropdown">
                            <a href="http://localhost/Hotel/modules/guest/menu/menu_enhanced.php">Menu <i class="fa fa-chevron-down"></i></a>
                            <ul class="dropdown-menu">
                                <li><a href="http://localhost/Hotel/modules/guest/menu/menu_enhanced.php"><i class="fa fa-utensils"></i> Full Menu</a></li>
                                <li><a href="http://localhost/Hotel/modules/guest/menu/menu_bar_integrated.php"><i class="fa fa-glass-cheers"></i> Bar & Beverages</a></li>
                                <li><a href="http://localhost/Hotel/modules/guest/menu/menu_enhanced.php?category=6"><i class="fa fa-wine-glass"></i> Bar Menu</a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="http://localhost/Hotel/modules/guest/menu/track_order.php">Track Order</a>
                        </li>
                        <li>
                            <a href="<?php echo $path_prefix; ?>index.php#services">Services</a>
                        </li>
                        <li>
                            <a href="<?php echo $path_prefix; ?>index.php#amenities">Amenities</a>
                        </li>
                        <li>
                            <a href="<?php echo $path_prefix; ?>index.php#contact">Contact</a>
                        </li>
                    </ul>
                </nav>

                <!-- Cart Icon (Mobile Only) -->
                <a href="<?php echo $path_prefix; ?>modules/guest/cart/view_cart.php" class="cart-icon mobile-cart-icon" title="View Cart" style="display: none;">
                    <i class="fa fa-shopping-cart"></i>
                    <span class="cart-count" id="cartCount">0</span>
                </a>

                <!-- Book Now Button -->
                <a href="<?php echo $path_prefix; ?>modules/guest/booking/booking_form.php" class="cta-button">
                    <span>Book Now</span>
                </a>

                <!-- Mobile Menu Toggle -->
                <button class="mobile-toggle" id="mobileToggle">
                    <i class="fa fa-bars"></i>
                </button>
            </div>
        </div>
    </div>
</header>

<script>
// Header functionality
document.addEventListener('DOMContentLoaded', function() {
    // Header scroll effect
    window.addEventListener('scroll', function() {
        const header = document.querySelector('.fresh-header');
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // Mobile menu toggle
    document.getElementById('mobileToggle').addEventListener('click', function() {
        const navMenu = document.getElementById('navMenu');
        navMenu.classList.toggle('active');
        
        // Change icon
        const icon = this.querySelector('i');
        if (navMenu.classList.contains('active')) {
            icon.className = 'fa fa-times';
        } else {
            icon.className = 'fa fa-bars';
        }
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        const navMenu = document.getElementById('navMenu');
        const mobileToggle = document.getElementById('mobileToggle');
        
        if (!navMenu.contains(event.target) && !mobileToggle.contains(event.target)) {
            navMenu.classList.remove('active');
            const icon = mobileToggle.querySelector('i');
            icon.className = 'fa fa-bars';
        }
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Update cart count
    updateCartCount();
});

// Cart count update function
function updateCartCount() {
    fetch('<?php echo $path_prefix; ?>api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_cart_counts'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.counts) {
            const totalItems = (data.counts.booking_items || 0) + (data.counts.order_items || 0);
            document.getElementById('cartCount').textContent = totalItems;
            
            if (totalItems > 0) {
                document.getElementById('cartCount').style.display = 'block';
            } else {
                document.getElementById('cartCount').style.display = 'none';
            }
        }
    })
    .catch(error => {
        console.log('Cart count update failed:', error);
    });
}

// Global function to refresh cart count (called from other pages)
window.refreshCartCount = updateCartCount;
</script>

<!-- Enhanced Menu Style Notifications System -->
<script src="<?php echo $path_prefix; ?>js/enhanced-menu-notifications.js"></script>

<!-- Responsive Cart CSS -->
<style>
/* Responsive Cart Display */
.mobile-cart-icon {
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #333;
    text-decoration: none;
    padding: 8px 12px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.mobile-cart-icon:hover {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
    text-decoration: none;
}

.mobile-cart-icon i {
    font-size: 1.2rem;
}

.mobile-cart-icon .cart-count {
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    min-width: 20px;
    animation: bounceIn 0.6s ease-out;
}

@keyframes bounceIn {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.2);
        opacity: 0.8;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.mobile-cart-icon {
    animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
    from {
        transform: translateX(20px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Responsive behavior */
@media (min-width: 769px) {
    /* Desktop/Tablet: Hide mobile cart icon */
    .mobile-cart-icon {
        display: none;
    }
}

@media (max-width: 768px) {
    /* Mobile: Show cart icon */
    .mobile-cart-icon {
        display: flex;
    }
    
    /* Ensure proper spacing on mobile */
    .header-right {
        gap: 8px;
    }
}
</style>
