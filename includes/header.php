<?php
// Shared header for all guest-facing pages
// Start session first if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include hotel settings for dynamic data
require_once __DIR__ . '/common/hotel_settings.php';

if (!isset($page_title)) {
    $page_title = get_hotel_info('name') ?: 'Orlando International Resorts';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="keywords" content="Orlando International Resorts, Hotel, Machakos, Kenya" />
<?php
    // Detect if we're in a subdirectory and adjust paths accordingly
    $current_dir = dirname($_SERVER['SCRIPT_NAME']);
    $root_dir = '/Hotel'; // Adjust this if your project is in a different folder
    if ($current_dir === $root_dir) {
        $path_prefix = '';
    } else {
        // Count directory levels from root
        $relative_path = str_replace($root_dir, '', $current_dir);
        $depth = substr_count($relative_path, '/');
        $path_prefix = str_repeat('../', $depth);
    }
    ?>
    <link rel="icon" type="image/png" href="<?php echo $path_prefix; ?>images/logo-full.png">
    
    <!-- CSS Files -->
    <link href="<?php echo $path_prefix; ?>css/bootstrap.css" rel="stylesheet" type="text/css" media="all" />
    <link href="<?php echo $path_prefix; ?>css/font-awesome.css" rel="stylesheet"> 
    <link rel="stylesheet" href="<?php echo $path_prefix; ?>css/chocolat.css" type="text/css" media="screen">
    <link href="<?php echo $path_prefix; ?>css/easy-responsive-tabs.css" rel='stylesheet' type='text/css'/>
    <link rel="stylesheet" href="<?php echo $path_prefix; ?>css/flexslider.css" type="text/css" media="screen" property="" />
    <link rel="stylesheet" href="<?php echo $path_prefix; ?>css/jquery-ui.css" />
    <link href="<?php echo $path_prefix; ?>css/style.css" rel="stylesheet" type="text/css" media="all" />
    <link href="<?php echo $path_prefix; ?>css/shared-styles.css" rel="stylesheet" type="text/css" media="all" />
    <!-- Dropdown Enhancement Styles -->
    <link href="<?php echo $path_prefix; ?>css/dropdown-fixes.css" rel="stylesheet" type="text/css" media="all" />
    
    <!-- Google Fonts -->
    <link href="/fonts.googleapis.com/css?family=Oswald:300,400,700" rel="stylesheet">
    <link href="/fonts.googleapis.com/css?family=Federo" rel="stylesheet">
    <link href="/fonts.googleapis.com/css?family=Lato:300,400,700,900" rel="stylesheet">
    
    <!-- JavaScript -->
    <script type="text/javascript" src="<?php echo $path_prefix; ?>js/modernizr-2.6.2.min.js"></script>
    <script type="application/x-javascript"> 
        addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false);
        function hideURLbar(){ window.scrollTo(0,1); } 
    </script>

    <style>
    /* ===== FRESH MODERN HEADER DESIGN =====  */
    
    /* Reset and Base Styles  */
    * {
        box-sizing: border-box;
    }
    
    /* Fresh Header Container  */
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

    /* Top Bar  */
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

    .contact-links a {
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
    }

    /* Main Navigation  */
    .main-nav {
        padding: 15px 0;
    }

    .nav-wrapper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 30px;
    }

    /* Brand Section  */
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

    /* Navigation Menu  */
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
    }

    /* Book Now Button  */
    .cta-button {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        color: white;
        padding: 12px 24px;
        border-radius: 30px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-decoration: none;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        border: 2px solid transparent;
        font-size: 13px;
        position: relative;
        overflow: hidden;
    }

    .cta-button::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s ease;
    }

    .cta-button:hover::before {
        left: 100%;
    }

    .cta-button:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4);
        background: linear-gradient(135deg, #c0392b, #e74c3c);
        color: white;
        text-decoration: none;
    }

    /* Mobile Menu Toggle  */
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

    /* Mobile Responsive  */
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
            margin: 10px 0 0 0;
            text-align: center;
            display: block;
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

    /* Body padding for fixed header  */
    body {
        padding-top: 130px;
    }

    @media (max-width: 991px) {
        body {
            padding-top: 110px;
        }
    }

    /* ===== SHARED COMPONENT STYLES =====  */
    
    /* Enhanced Banner Section  */
    .w3ls-banner {
        position: relative;
        background: linear-gradient(135deg, rgba(44, 62, 80, 0.8) 0%, rgba(52, 73, 94, 0.8) 100%);
        margin-top: -130px;
        padding-top: 130px;
    }

    .w3layouts-banner-top {
        background: url('images/banner-bg.jpg') center center/cover no-repeat;
        position: relative;
        min-height: 600px;
        display: flex;
        align-items: center;
    }

    .w3layouts-banner-top::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(44, 62, 80, 0.7) 0%, rgba(52, 73, 94, 0.7) 100%);
    }

    .agileits-banner-info {
        position: relative;
        z-index: 2;
        text-align: center;
        color: white;
    }

    .agileits-banner-info h4 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 20px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
    }

    .agileits-banner-info h3 {
        font-size: 1.8rem;
        font-weight: 600;
        margin-bottom: 15px;
        color: #3498db;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
    }

    .agileits-banner-info p {
        font-size: 1.2rem;
        margin-bottom: 30px;
        opacity: 0.9;
    }

    .agileits_w3layouts_more .menu__link {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        color: white;
        padding: 15px 30px;
        border-radius: 50px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
    }

    .agileits_w3layouts_more .menu__link:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4);
        background: linear-gradient(135deg, #c0392b, #e74c3c);
    }

    /* Section Titles  */
    .title-w3-agileits {
        font-size: 2.5rem;
        font-weight: 700;
        text-align: center;
        margin-bottom: 20px;
        color: #333;
    }

    .title-black-wthree {
        color: #333;
    }

    /* Buttons  */
    .btn-primary {
        background: linear-gradient(135deg, #3498db, #2980b9);
        border: none;
        padding: 12px 25px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        text-decoration: none;
    }

    /* Utility Classes  */
    .text-center { text-align: center; }
    .clearfix::after { content: ""; display: table; clear: both; }
    </style>
</head>
<body>

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
                            <img src="<?php echo $path_prefix; ?>images/logo-full.png" alt="Orlando International Resorts" class="brand-logo">
                            <p class="brand-subtitle">Luxury Meets Affordability</p>
                        </div>
                    </a>
                </div>

                <!-- Navigation Menu -->
                <nav class="nav-menu" id="navMenu">
                    <ul class="nav-menu">
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                            <a href="<?php echo $path_prefix; ?>index.php">Home</a>
                        </li>
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'rooms.php' ? 'active' : ''; ?>">
                            <a href="<?php echo $path_prefix; ?>rooms.php">Rooms</a>
                        </li>
                        <li>
                            <a href="http://localhost/Hotel/modules/guest/menu/menu_enhanced.php">Menu</a>
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

                <!-- Book Now Button -->
                <a href="<?php echo $path_prefix; ?>modules/guest/booking/booking_form.php" class="cta-button">Book Now</a>

                <!-- Mobile Menu Toggle -->
                <button class="mobile-toggle" id="mobileToggle">
                    <i class="fa fa-bars"></i>
                </button>
            </div>
        </div>
    </div>
</header>

<script>
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

// Load dropdown enhancements if jQuery is available
if (typeof jQuery !== 'undefined') {
    // Load the dropdown enhancement script
    var script = document.createElement('script');
    script.src = '<?php echo $path_prefix; ?>js/dropdown-enhancements.js';
    script.type = 'text/javascript';
    document.head.appendChild(script);
    
    // Load intelligent defaults script
    var defaultsScript = document.createElement('script');
    defaultsScript.src = '<?php echo $path_prefix; ?>js/intelligent-defaults.js';
    defaultsScript.type = 'text/javascript';
    document.head.appendChild(defaultsScript);
}
</script>
