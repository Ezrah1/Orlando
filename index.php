<?php
// Include hotel settings for dynamic content
require_once 'includes/common/hotel_settings.php';

$page_title = get_hotel_info('name') . ' - Luxury Meets Affordability';
$page_description = get_hotel_info('description') ?: 'Experience luxury and comfort at Orlando International Resorts in Machakos, Kenya. Premium accommodation from KES 1,300 per night with modern amenities.';
$page_keywords = get_hotel_info('name') . ', ' . get_hotel_info('city') . ' hotel, ' . get_hotel_info('country') . ' accommodation, luxury hotel, affordable rates';

include('includes/header.php');

// Include necessary dependencies for menu widget
require_once 'db.php';
require_once 'cart_manager.php';

// Initialize cart system for menu widget
if (class_exists('CartManager')) {
    CartManager::initCarts();
}
?>

<!-- Maya AI Setup Notification -->
<style>
@keyframes mayaSlideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes mayaPulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

/* Maya AI is now fully installed and operational */










</style>

<!-- Maya AI is now fully installed and operational -->

<!-- Banner Section -->
<div id="home" class="w3ls-banner">
    <div class="slider">
        <div class="callbacks_container">
            <ul class="rslides callbacks callbacks1" id="slider4">
                <li>
                    <div class="w3layouts-banner-top">
                        <div class="container">
                            <div class="agileits-banner-info">
                                <h4>Welcome to <?php echo htmlspecialchars(get_hotel_info('name')); ?></h4>
                                <h3>Experience Luxury at <?php echo htmlspecialchars(get_hotel_info('name')); ?></h3>
                                <p>From KES 1,300 per night • Free WiFi • 24/7 Service</p>
                                <div class="agileits_w3layouts_more menu__item">
                                    <a href="modules/guest/booking/booking_form.php" class="menu__link">Book Your Stay</a>
                                </div>
                            </div>	
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- AI-Powered Booking Banner -->
<div id="availability-agileits">
        <div class="col-md-12 book-form-left-w3layouts">
        <div class="ai-booking-banner">
            <div class="ai-banner-content">
                <div class="ai-banner-text">
                    <h2 class="ai-banner-title">
                        AI-POWERED SMART BOOKING
                    </h2>
                    <p class="ai-banner-subtitle">
                        Let our AI find the perfect room and dates for you
                        <span class="ai-highlight">• No deposit required</span>
                    </p>
                </div>
                <div class="ai-banner-actions">
                    <button onclick="openMayaChat()" class="ai-primary-booking-btn">
                        <i class="fa fa-robot"></i>
                        <span>Start AI Booking</span>
                        <div class="btn-glow"></div>
                    </button>
                    <button onclick="openQuickBooking()" class="ai-secondary-booking-btn">
                        <i class="fa fa-calendar"></i>
                        <span>Quick Book</span>
                    </button>
                </div>
            </div>
            <div class="ai-banner-bg-effects">
                <div class="ai-particle ai-particle-1"></div>
                <div class="ai-particle ai-particle-2"></div>
                <div class="ai-particle ai-particle-3"></div>
            </div>
        </div>
    </div>
<div class="clearfix"> </div>
</div>

<!-- About Section -->
<div class="about-wthree" id="about">
    <div class="container">
        <div class="ab-w3l-spa">
            <h3 class="title-w3-agileits title-black-wthree">Welcome to <?php echo htmlspecialchars(get_hotel_info('name')); ?></h3> 
            <p class="about-para-w3ls">Located in the heart of <?php echo htmlspecialchars(get_hotel_info('address')); ?>, <?php echo htmlspecialchars(get_hotel_info('name')); ?> offers the perfect blend of luxury and affordability. Our uniquely named rooms provide comfortable accommodation for business travelers, families, and tourists. With prices starting from just <?php echo format_currency(1300); ?> per night, we make luxury accessible to everyone.</p>
            <img src="images/about.jpg" class="img-responsive" alt="<?php echo htmlspecialchars(get_hotel_info('name')); ?>">
            <div class="w3ls-info-about">
                <h4>Why guests choose us again and again!</h4>
                <p>✓ Free WiFi • ✓ M-Pesa Payments • ✓ 24/7 Service • ✓ Secure Parking • ✓ Restaurant & Bar • ✓ Clean Rooms</p>
            </div>
        </div>
        <div class="clearfix"> </div>
    </div>
</div>

<!-- Featured Menu Section -->
<div class="container">
    <?php
    // Display cart message if available
    if (isset($_SESSION['cart_message'])) {
        $message_type = $_SESSION['cart_message_type'] ?? 'info';
        $message = $_SESSION['cart_message'];
        echo "<div class='alert alert-$message_type' style='margin: 20px auto; max-width: 800px; text-align: center;'>$message</div>";
        unset($_SESSION['cart_message']);
        unset($_SESSION['cart_message_type']);
    }

    // Configure enhanced menu widget for homepage
    $widget_title = "Featured Menu";
    $widget_items_limit = 12; // Show more items on homepage
    $widget_style = "full"; // Full layout for main content area
    $widget_show_filters = true; // Enable filtering
    $widget_show_search = true; // Enable search
    $widget_show_cart_actions = true; // Enable cart functionality

    // Include the enhanced menu widget
    include('includes/menu_widget.php');
    ?>
</div>

<!-- Services Section -->
<div class="services-section" id="services">
    <div class="container">
        <h3 class="title-w3-agileits title-black-wthree">Our Services</h3>
        <p class="text-center" style="margin-bottom: 40px; color: #666;">Everything you need for a comfortable stay</p>
        <div class="row">
            <!-- Static service cards -->
            <div class="col-md-4 col-sm-6">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fa fa-wifi"></i>
                    </div>
                    <h4>Free WiFi</h4>
                    <p>High-speed internet access throughout the resort</p>
                    <div class="service-price">FREE</div>
                    <a href="#contact" class="btn btn-primary">Learn More</a>
                </div>
            </div>
            
            <div class="col-md-4 col-sm-6">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fa fa-car"></i>
                    </div>
                    <h4>Secure Parking</h4>
                    <p>24/7 monitored parking for your vehicle</p>
                    <div class="service-price">FREE</div>
                    <a href="#contact" class="btn btn-primary">Learn More</a>
                </div>
            </div>
            
            <div class="col-md-4 col-sm-6">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fa fa-mobile"></i>
                    </div>
                    <h4>M-Pesa Payments</h4>
                    <p>Convenient mobile money payments accepted</p>
                    <div class="service-price">NO FEE</div>
                    <a href="modules/guest/booking/booking_form.php" class="btn btn-primary">Book Now</a>
                </div>
            </div>
            
            <div class="col-md-4 col-sm-6">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fa fa-cutlery"></i>
                    </div>
                    <h4>Restaurant & Bar</h4>
                    <p>Delicious meals and refreshing drinks available 24/7</p>
                    <div class="service-price">KES 150+</div>
                    <a href="http://localhost/Hotel/modules/guest/menu/menu_enhanced.php" class="btn btn-primary">View Menu</a>
                </div>
            </div>
            
            <div class="col-md-4 col-sm-6">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fa fa-phone"></i>
                    </div>
                    <h4>24/7 Room Service</h4>
                    <p>Order food and drinks directly to your room</p>
                    <div class="service-price">Available</div>
                    <a href="<?php echo get_phone_link(); ?>" class="btn btn-primary">Call Now</a>
                </div>
            </div>
            
            <div class="col-md-4 col-sm-6">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fa fa-clock-o"></i>
                    </div>
                    <h4>Front Desk</h4>
                    <p>Always available to assist with your needs</p>
                    <div class="service-price">24/7</div>
                    <a href="#contact" class="btn btn-primary">Contact Us</a>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- Amenities Section -->
<div class="amenities-section" id="amenities">
    <div class="container">
        <h3 class="title-w3-agileits title-black-wthree">Amenities & Facilities</h3>
        <p class="text-center" style="margin-bottom: 40px; color: #666;">Everything you need for a comfortable and enjoyable stay</p>
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="amenity-card">
                    <div class="amenity-icon">
                        <i class="fa fa-wifi"></i>
                    </div>
                    <h4>Free WiFi</h4>
                    <p>High-speed internet access throughout the resort</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="amenity-card">
                    <div class="amenity-icon">
                        <i class="fa fa-car"></i>
                    </div>
                    <h4>Secure Parking</h4>
                    <p>24/7 monitored parking for your vehicle</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="amenity-card">
                    <div class="amenity-icon">
                        <i class="fa fa-cutlery"></i>
                    </div>
                    <h4>Restaurant & Bar</h4>
                    <p>Delicious meals and refreshing drinks</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="amenity-card">
                    <div class="amenity-icon">
                        <i class="fa fa-mobile"></i>
                    </div>
                    <h4>M-Pesa Payments</h4>
                    <p>Convenient mobile money payments</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="amenity-card">
                    <div class="amenity-icon">
                        <i class="fa fa-snowflake-o"></i>
                    </div>
                    <h4>Air Conditioning</h4>
                    <p>Climate control in all rooms</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="amenity-card">
                    <div class="amenity-icon">
                        <i class="fa fa-tv"></i>
                    </div>
                    <h4>TV & Entertainment</h4>
                    <p>Flat-screen TVs with cable channels</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="amenity-card">
                    <div class="amenity-icon">
                        <i class="fa fa-shower"></i>
                    </div>
                    <h4>Private Bathrooms</h4>
                    <p>Clean, modern bathrooms in every room</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="amenity-card">
                    <div class="amenity-icon">
                        <i class="fa fa-clock-o"></i>
                    </div>
                    <h4>24/7 Service</h4>
                    <p>Round-the-clock front desk service</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rooms & Rates Section -->
<div class="plans-section" id="rooms">
    <div class="container">
        <h3 class="title-w3-agileits title-black-wthree">Rooms And Rates</h3>
        <p class="text-center" style="margin-bottom: 40px; color: #666;">Click on any room to select it for booking, or book directly</p>
        <div class="row">
            <?php
            // Get all rooms from database
            $rooms_query = "SELECT * FROM named_rooms WHERE is_active = 1 ORDER BY base_price ASC";
            $rooms_result = mysqli_query($con, $rooms_query);
            while($room = mysqli_fetch_assoc($rooms_result)):
            ?>
            <div class="col-md-4 col-sm-6">
                <div class="room-card" data-room="<?php echo htmlspecialchars($room['room_name']); ?>" data-price="<?php echo $room['base_price']; ?>">
                    <div class="room-image">
                        <img src="images/r<?php echo ($room['id'] % 4) + 1; ?>.jpg" alt="<?php echo htmlspecialchars($room['room_name']); ?>" class="img-responsive" />
                        <?php if($room['base_price'] >= 3500): ?>
                            <div class="room-badge">Premium</div>
                        <?php elseif($room['base_price'] >= 2000): ?>
                            <div class="room-badge">Standard</div>
                        <?php else: ?>
                            <div class="room-badge">Economy</div>
                        <?php endif; ?>
                    </div>
                    <div class="room-content">
                        <h4 class="room-title"><?php echo htmlspecialchars($room['room_name']); ?></h4>
                        <p class="room-description"><?php echo htmlspecialchars($room['description']); ?></p>
                        <div class="room-price">KES <?php echo number_format($room['base_price'], 0); ?> / night</div>
                        <div class="room-features">
                            <span class="room-feature"><i class="fa fa-wifi"></i> WiFi</span>
                            <span class="room-feature"><i class="fa fa-tv"></i> TV</span>
                            <span class="room-feature"><i class="fa fa-snowflake-o"></i> AC</span>
                            <span class="room-feature"><i class="fa fa-shower"></i> Private Bath</span>
                        </div>
                        <div class="room-actions">
                            <button class="btn-select" onclick="selectRoom('<?php echo htmlspecialchars($room['room_name']); ?>', <?php echo $room['base_price']; ?>)">Select Room</button>
                            <button class="btn-availability" onclick="checkAvailability('<?php echo htmlspecialchars($room['room_name']); ?>')">
                                <i class="fa fa-calendar"></i> Check Availability
                            </button>
                            <a href="modules/guest/booking/luxury_booking.php?room=<?php echo urlencode($room['room_name']); ?>&price=<?php echo $room['base_price']; ?>" class="btn-book">Book Now</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<!-- Testimonials Section -->
<div class="w3l-visitors-agile">
    <div class="container">
        <h3 class="title-w3-agileits title-black-wthree">What Our Guests Say</h3> 
        <p class="text-center" style="margin-bottom: 40px; color: #666;">Real experiences from our valued guests</p>
    </div>
    <div class="w3layouts_work_grids">
        <section class="slider">
            <div class="flexslider">
                <ul class="slides">
                    <li>
                        <div class="w3layouts_work_grid_left">
                            <img src="images/5.jpg" alt=" " class="img-responsive" />
                            <div class="w3layouts_work_grid_left_pos">
                                <img src="images/c1.jpg" alt=" " class="img-responsive" />
                            </div>
                        </div>
                        <div class="w3layouts_work_grid_right">
                            <h4>
                                <i class="fa fa-star" aria-hidden="true"></i>
                                <i class="fa fa-star" aria-hidden="true"></i>
                                <i class="fa fa-star" aria-hidden="true"></i>
                                <i class="fa fa-star" aria-hidden="true"></i>
                                <i class="fa fa-star" aria-hidden="true"></i>
                                Excellent Value for Money
                            </h4>
                            <p>"The room was clean, comfortable, and the staff was very friendly. M-Pesa payment was so convenient. Will definitely stay here again when visiting Machakos!"</p>
                            <h5>Sarah Muthoni</h5>
                            <p>Nairobi, Kenya</p>
                        </div>
                        <div class="clearfix"> </div>
                    </li>
                </ul>
            </div>
        </section>
    </div>	
</div>

<!-- Final CTA Section -->
<div class="final-cta-section">
    <div class="container">
        <div class="row">
            <div class="col-md-12 text-center">
                <div class="final-cta-content">
                    <h3>Experience the Orlando International Resorts Difference</h3>
                    <p>Join thousands of satisfied guests who have made us their preferred choice in Machakos</p>
                    <div class="final-cta-stats">
                        <div class="stat-item">
                            <div class="stat-number">17</div>
                            <div class="stat-label">Unique Rooms</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">24/7</div>
                            <div class="stat-label">Service</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">KES 1,300</div>
                            <div class="stat-label">Starting Price</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">100%</div>
                            <div class="stat-label">Satisfaction</div>
                        </div>
                    </div>
                    <div class="final-cta-buttons">
                        <a href="modules/guest/booking/booking_form.php" class="btn btn-primary btn-lg">Book Your Stay Now</a>
                        <a href="#contact" class="btn btn-outline-light btn-lg scroll">Contact Us</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contact Section -->
<section class="contact-w3ls" id="contact">
    <div class="container">
        <div class="col-lg-6 col-md-6 col-sm-6 contact-w3-agile2" data-aos="flip-left">
            <div class="contact-agileits">
                <h4>Get In Touch</h4>
                <p class="contact-agile2">Have questions? We're here to help!</p>
                <form method="post" name="sentMessage" id="contactForm">
                    <div class="control-group form-group">
                        <label class="contact-p1">Full Name:</label>
                        <input type="text" class="form-control" name="name" id="name" required>
                        <p class="help-block"></p>
                    </div>	
                    <div class="control-group form-group">
                        <label class="contact-p1">Phone Number:</label>
                        <input type="tel" class="form-control" name="phone" id="phone" required>
                        <p class="help-block"></p>
                    </div>
                    <div class="control-group form-group">
                        <label class="contact-p1">Email Address:</label>
                        <input type="email" class="form-control" name="email" id="email" required>
                        <p class="help-block"></p>
                    </div>
                    <div class="control-group form-group">
                        <label class="contact-p1">Message:</label>
                        <textarea class="form-control" name="message" id="message" rows="3" placeholder="Tell us about your inquiry..."></textarea>
                        <p class="help-block"></p>
                    </div>
                    <input type="submit" name="sub" value="Send Message" class="btn btn-primary">	
                </form>
                <?php
                if(isset($_POST['sub'])) {
                    $name = $_POST['name'];
                    $phone = $_POST['phone'];
                    $email = $_POST['email'];
                    $message = isset($_POST['message']) ? $_POST['message'] : '';
                    $approval = "Not Allowed";
                    $sql = "INSERT INTO `contact`(`fullname`, `phoneno`, `email`, `message`, `cdate`,`approval`) VALUES ('$name','$phone','$email','$message',now(),'$approval')";
                    
                    if(mysqli_query($con,$sql)) {
                        echo "<div class='alert alert-success'>Thank you! We'll get back to you soon.</div>";
                    } else {
                        echo "<div class='alert alert-danger'>Sorry, there was an error. Please try again.</div>";
                    }
                }
                ?>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-6 contact-w3-agile1" data-aos="flip-right">
            <h4>Contact Information</h4>
            <div class="contact-info">
                <div class="contact-item">
                    <i class="fa fa-phone"></i>
                    <div>
                        <strong>Phone:</strong><br>
                        <a href="<?php echo get_phone_link(); ?>"><?php echo htmlspecialchars(get_hotel_info('phone')); ?></a>
                    </div>
                </div>
                <div class="contact-item">
                    <i class="fa fa-envelope"></i>
                    <div>
                        <strong>Email:</strong><br>
                        <a href="<?php echo get_email_link(); ?>"><?php echo htmlspecialchars(get_hotel_info('email')); ?></a>
                    </div>
                </div>
                <div class="contact-item">
                    <i class="fa fa-map-marker"></i>
                    <div>
                        <strong>Address:</strong><br>
                        <?php echo htmlspecialchars(get_hotel_info('address')); ?>
                    </div>
                </div>
                <div class="contact-item">
                    <i class="fa fa-clock-o"></i>
                    <div>
                        <strong>Hours:</strong><br>
                        24/7 Front Desk Service
                    </div>
                </div>
            </div>
            
            <div class="social-bnr-agileits footer-icons-agileinfo">
                <ul class="social-icons3">
                    <li><a href="#" class="fa fa-facebook icon-border facebook"> </a></li>
                    <li><a href="#" class="fa fa-twitter icon-border twitter"> </a></li>
                    <li><a href="#" class="fa fa-instagram icon-border instagram"> </a></li> 
                </ul>
            </div>
            
            <!-- Quick Contact Actions -->
            <div class="quick-actions mt-4">
                <h5>Quick Actions</h5>
                <div class="row">
                    <div class="col-xs-6">
                        <a href="<?php echo get_phone_link(); ?>" class="btn btn-success btn-block">
                            <i class="fa fa-phone"></i> Call Now
                        </a>
                    </div>
                    <div class="col-xs-6">
                        <a href="modules/guest/booking/luxury_booking.php" class="btn btn-primary btn-block">
                            <i class="fa fa-calendar"></i> Book Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>
</section>

<!-- Room Selection JavaScript -->
<script>
// Global variables for selected room
let selectedRoom = null;
let selectedRoomPrice = 0;

// Function to select a room
function selectRoom(roomName, roomPrice) {
    // Remove previous selection
    document.querySelectorAll('.room-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Add selection to clicked room
    const roomCard = document.querySelector(`[data-room="${roomName}"]`);
    if (roomCard) {
        roomCard.classList.add('selected');
        
        // Update the Book Now link to include selected room
        const bookNowLink = roomCard.querySelector('.btn-book');
        if (bookNowLink) {
            bookNowLink.href = `modules/guest/booking/luxury_booking.php?room=${encodeURIComponent(roomName)}&price=${roomPrice}&selected=true`;
        }
    }
    
    // Store selected room info
    selectedRoom = roomName;
    selectedRoomPrice = roomPrice;
    
    // Show success message and floating booking option
    showNotification(`Room "${roomName}" selected! Click "Book Now" to proceed.`, 'success');
    showFloatingBookButton(roomName, roomPrice);
    
    // Scroll to booking section if it exists
    const bookingSection = document.getElementById('availability-agileits');
    if (bookingSection) {
        bookingSection.scrollIntoView({ behavior: 'smooth' });
    }
}

// AI Booking Assistant Functions
function openAIBookingAssistant() {
    showAIBookingModal();
}

function openQuickBooking() {
    window.location.href = 'modules/guest/booking/luxury_booking.php';
}

function showAIBookingModal() {
    // Remove existing modal
    const existingModal = document.getElementById('ai-booking-modal');
    if (existingModal) {
        existingModal.remove();
    }
    
    const modalContent = `
        <div class="modal-content ai-modal-content">
            <div class="modal-header ai-modal-header">
                <h3><i class="fa fa-robot"></i> AI Booking Assistant</h3>
                <span class="close" onclick="closeAIBookingModal()">&times;</span>
            </div>
            <div class="modal-body ai-modal-body">
                <div class="ai-greeting">
                    <div class="ai-avatar">
                        <i class="fa fa-robot"></i>
                    </div>
                    <div class="ai-message">
                        <h4>👋 Hello! I'm your AI booking assistant</h4>
                        <p>I'll help you find the perfect room and dates based on your preferences. Let's get started!</p>
                    </div>
                </div>
                
                <div class="ai-options">
                    <h5>How would you like me to help you today?</h5>
                    <div class="ai-option-buttons">
                        <button onclick="startSmartBooking()" class="ai-option-btn">
                            <i class="fa fa-magic"></i>
                            <div>
                                <strong>Smart Booking</strong>
                                <small>I'll analyze availability and suggest the best options</small>
                            </div>
                        </button>
                        
                        <button onclick="startBudgetBooking()" class="ai-option-btn">
                            <i class="fa fa-money"></i>
                            <div>
                                <strong>Budget Finder</strong>
                                <small>Find the best deals within your budget</small>
                            </div>
                        </button>
                        
                        <button onclick="startDateFlexibility()" class="ai-option-btn">
                            <i class="fa fa-calendar-o"></i>
                            <div>
                                <strong>Flexible Dates</strong>
                                <small>I have flexible dates, show me options</small>
                            </div>
                        </button>
                        
                        <button onclick="startSpecialOccasion()" class="ai-option-btn">
                            <i class="fa fa-heart"></i>
                            <div>
                                <strong>Special Occasion</strong>
                                <small>Anniversary, birthday, or romantic getaway</small>
                            </div>
                        </button>
                    </div>
                </div>
                
                <div class="ai-quick-stats" style="margin-top: 25px; padding: 20px; background: #f8f9fa; border-radius: 12px;">
                    <h6 style="margin: 0 0 15px 0; color: #2c3e50;">📊 Current Availability Overview</h6>
                    <div id="ai-stats-loading" style="text-align: center; padding: 20px;">
                        <i class="fa fa-spinner fa-spin"></i> Analyzing current availability...
                    </div>
                    <div id="ai-stats-content" style="display: none;"></div>
                </div>
            </div>
        </div>
    `;
    
    // Create modal
    const modal = document.createElement('div');
    modal.id = 'ai-booking-modal';
    modal.className = 'ai-booking-modal';
    modal.innerHTML = modalContent;
    
    document.body.appendChild(modal);
    
    // Load current stats
    loadAIStats();
    
    // Add click outside to close
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeAIBookingModal();
        }
    });
}

function closeAIBookingModal() {
    const modal = document.getElementById('ai-booking-modal');
    if (modal) {
        modal.remove();
    }
}

function loadAIStats() {
    // Simulate loading current availability stats
    setTimeout(() => {
        const statsContent = document.getElementById('ai-stats-content');
        const statsLoading = document.getElementById('ai-stats-loading');
        
        if (statsContent && statsLoading) {
            statsLoading.style.display = 'none';
            statsContent.style.display = 'block';
            
            // Get today's date for next 7 days
            const today = new Date();
            const nextWeek = new Date(today);
            nextWeek.setDate(nextWeek.getDate() + 7);
            
            statsContent.innerHTML = `
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                    <div style="text-align: center; padding: 15px; background: white; border-radius: 8px; border-left: 4px solid #28a745;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;">85%</div>
                        <div style="font-size: 0.9rem; color: #6c757d;">Rooms Available</div>
                        <div style="font-size: 0.8rem; color: #6c757d;">Next 7 days</div>
                    </div>
                    <div style="text-align: center; padding: 15px; background: white; border-radius: 8px; border-left: 4px solid #667eea;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: #667eea;">KES 1,300</div>
                        <div style="font-size: 0.9rem; color: #6c757d;">Starting Price</div>
                        <div style="font-size: 0.8rem; color: #6c757d;">Per night</div>
                    </div>

                </div>
                <div style="margin-top: 15px; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px; text-align: center;">
                    <strong>💡 AI Tip:</strong> Weekday bookings offer 15% better rates on average!
                </div>
            `;
        }
    }, 1500);
}

// AI Booking Flow Functions
function startSmartBooking() {
    closeAIBookingModal();
    
    // Show smart booking options
    showSmartBookingFlow();
}

function startBudgetBooking() {
    closeAIBookingModal();
    
    // Show budget selection
    showBudgetBookingFlow();
}

function startDateFlexibility() {
    closeAIBookingModal();
    
    // Show flexible date options
    showFlexibleDateFlow();
}

function startSpecialOccasion() {
    closeAIBookingModal();
    
    // Show special occasion options
    showSpecialOccasionFlow();
}

function showSmartBookingFlow() {
    const modal = createAIFlowModal('Smart Booking Assistant', `
        <div class="ai-conversation">
            <div class="ai-message-bubble ai-bubble">
                <i class="fa fa-robot"></i>
                <div>
                    <strong>Great choice! Let me find the best options for you.</strong>
                    <p>I'll analyze room availability, pricing trends, and customer preferences to recommend the perfect stay.</p>
                </div>
            </div>
            
            <div class="ai-form-section">
                <h6>Tell me about your stay:</h6>
                <div class="ai-form-grid">
                    <div class="ai-form-group">
                        <label>How many guests?</label>
                        <select id="ai-guests" class="ai-form-control">
                            <option value="1">1 guest</option>
                            <option value="2" selected>2 guests</option>
                            <option value="3">3 guests</option>
                            <option value="4">4+ guests</option>
                        </select>
                    </div>
                    
                    <div class="ai-form-group">
                        <label>Preferred dates (optional)</label>
                        <input type="date" id="ai-checkin" class="ai-form-control" min="${new Date().toISOString().split('T')[0]}">
                    </div>
                    
                    <div class="ai-form-group">
                        <label>Stay duration</label>
                        <select id="ai-duration" class="ai-form-control">
                            <option value="1">1 night</option>
                            <option value="2" selected>2-3 nights</option>
                            <option value="4">4-7 nights</option>
                            <option value="8">Week or more</option>
                        </select>
                    </div>
                    
                    <div class="ai-form-group">
                        <label>Room preference</label>
                        <select id="ai-room-type" class="ai-form-control">
                            <option value="any">Any room (best value)</option>
                            <option value="economy">Economy rooms</option>
                            <option value="standard">Standard rooms</option>
                            <option value="premium">Premium rooms</option>
                        </select>
                    </div>
                </div>
                
                <div class="ai-action-buttons">
                    <button onclick="analyzeSmartBooking()" class="ai-primary-btn">
                        <i class="fa fa-magic"></i> Analyze & Find Best Options
                    </button>
                    <button onclick="closeAIBookingModal()" class="ai-secondary-btn">Cancel</button>
                </div>
            </div>
        </div>
    `);
}

function showBudgetBookingFlow() {
    const modal = createAIFlowModal('Budget Finder', `
        <div class="ai-conversation">
            <div class="ai-message-bubble ai-bubble">
                <i class="fa fa-money"></i>
                <div>
                    <strong>Let's find the best value for your money!</strong>
                    <p>I'll help you discover rooms that offer the most value within your budget range.</p>
                </div>
            </div>
            
            <div class="ai-form-section">
                <h6>What's your budget range per night?</h6>
                <div class="budget-options">
                    <button onclick="setBudgetRange(0, 1500)" class="budget-option">
                        <strong>Under KES 1,500</strong>
                        <small>Economy options</small>
                    </button>
                    <button onclick="setBudgetRange(1500, 2500)" class="budget-option">
                        <strong>KES 1,500 - 2,500</strong>
                        <small>Good value</small>
                    </button>
                    <button onclick="setBudgetRange(2500, 4000)" class="budget-option">
                        <strong>KES 2,500 - 4,000</strong>
                        <small>Premium comfort</small>
                    </button>
                    <button onclick="setBudgetRange(4000, 10000)" class="budget-option">
                        <strong>KES 4,000+</strong>
                        <small>Luxury experience</small>
                    </button>
                </div>
                
                <div class="ai-tip" style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 8px;">
                    <strong>💡 AI Tip:</strong> Booking 2-3 nights often unlocks better rates and special packages!
                </div>
            </div>
        </div>
    `);
}

function showFlexibleDateFlow() {
    const modal = createAIFlowModal('Flexible Dates', `
        <div class="ai-conversation">
            <div class="ai-message-bubble ai-bubble">
                <i class="fa fa-calendar-o"></i>
                <div>
                    <strong>Perfect! Flexible dates get the best deals.</strong>
                    <p>I'll show you the most affordable periods and help you save money on your stay.</p>
                </div>
            </div>
            
            <div class="ai-form-section">
                <h6>When are you looking to visit?</h6>
                <div class="flexible-options">
                    <button onclick="findFlexibleDates('this-week')" class="flexible-option">
                        <i class="fa fa-calendar"></i>
                        <div>
                            <strong>This Week</strong>
                            <small>Next 7 days</small>
                        </div>
                    </button>
                    <button onclick="findFlexibleDates('next-week')" class="flexible-option">
                        <i class="fa fa-calendar-plus-o"></i>
                        <div>
                            <strong>Next Week</strong>
                            <small>Better availability</small>
                        </div>
                    </button>
                    <button onclick="findFlexibleDates('this-month')" class="flexible-option">
                        <i class="fa fa-calendar-check-o"></i>
                        <div>
                            <strong>This Month</strong>
                            <small>Best deals</small>
                        </div>
                    </button>
                    <button onclick="findFlexibleDates('next-month')" class="flexible-option">
                        <i class="fa fa-calendar-o"></i>
                        <div>
                            <strong>Next Month</strong>
                            <small>Maximum flexibility</small>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    `);
}

function showSpecialOccasionFlow() {
    const modal = createAIFlowModal('Special Occasion', `
        <div class="ai-conversation">
            <div class="ai-message-bubble ai-bubble">
                <i class="fa fa-heart"></i>
                <div>
                    <strong>How exciting! Let's make it special.</strong>
                    <p>I'll recommend rooms and amenities perfect for your celebration, plus any available packages.</p>
                </div>
            </div>
            
            <div class="ai-form-section">
                <h6>What's the special occasion?</h6>
                <div class="occasion-options">
                    <button onclick="selectOccasion('anniversary')" class="occasion-option">
                        <i class="fa fa-heart"></i>
                        <div>
                            <strong>Anniversary</strong>
                            <small>Romantic packages available</small>
                        </div>
                    </button>
                    <button onclick="selectOccasion('birthday')" class="occasion-option">
                        <i class="fa fa-birthday-cake"></i>
                        <div>
                            <strong>Birthday</strong>
                            <small>Celebration setups</small>
                        </div>
                    </button>
                    <button onclick="selectOccasion('honeymoon')" class="occasion-option">
                        <i class="fa fa-rings"></i>
                        <div>
                            <strong>Honeymoon</strong>
                            <small>Luxury suites & packages</small>
                        </div>
                    </button>
                    <button onclick="selectOccasion('business')" class="occasion-option">
                        <i class="fa fa-briefcase"></i>
                        <div>
                            <strong>Business Stay</strong>
                            <small>Work-friendly amenities</small>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    `);
}

function createAIFlowModal(title, content) {
    // Remove existing modal
    closeAIBookingModal();
    
    const modalContent = `
        <div class="modal-content ai-flow-modal-content">
            <div class="modal-header ai-modal-header">
                <h3><i class="fa fa-robot"></i> ${title}</h3>
                <span class="close" onclick="closeAIBookingModal()">&times;</span>
            </div>
            <div class="modal-body ai-flow-modal-body">
                ${content}
            </div>
        </div>
    `;
    
    // Create modal
    const modal = document.createElement('div');
    modal.id = 'ai-booking-modal';
    modal.className = 'ai-booking-modal';
    modal.innerHTML = modalContent;
    
    document.body.appendChild(modal);
    
    // Add click outside to close
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeAIBookingModal();
        }
    });
    
    return modal;
}

// AI Action Functions
function analyzeSmartBooking() {
    const guests = document.getElementById('ai-guests')?.value || '2';
    const checkin = document.getElementById('ai-checkin')?.value;
    const duration = document.getElementById('ai-duration')?.value || '2';
    const roomType = document.getElementById('ai-room-type')?.value || 'any';
    
    // Show analyzing state
    showAIAnalyzing('Analyzing availability, pricing trends, and finding the best matches for you...');
    
    setTimeout(() => {
        const params = new URLSearchParams({
            ai_booking: 'smart',
            guests: guests,
            duration: duration,
            room_type: roomType
        });
        
        if (checkin) {
            params.append('preferred_date', checkin);
        }
        
        window.location.href = `modules/guest/booking/luxury_booking.php?${params.toString()}`;
    }, 2000);
}

function setBudgetRange(min, max) {
    showAIAnalyzing(`Finding the best rooms in your KES ${min.toLocaleString()} - ${max.toLocaleString()} budget range...`);
    
    setTimeout(() => {
        window.location.href = `modules/guest/booking/luxury_booking.php?ai_booking=budget&budget_min=${min}&budget_max=${max}`;
    }, 1500);
}

function findFlexibleDates(period) {
    showAIAnalyzing('Scanning availability patterns and finding the best deals for flexible dates...');
    
    setTimeout(() => {
        window.location.href = `modules/guest/booking/luxury_booking.php?ai_booking=flexible&period=${period}`;
    }, 1500);
}

function selectOccasion(occasion) {
    showAIAnalyzing(`Curating special recommendations for your ${occasion}...`);
    
    setTimeout(() => {
        window.location.href = `modules/guest/booking/luxury_booking.php?ai_booking=special&occasion=${occasion}`;
    }, 1500);
}

function showAIAnalyzing(message) {
    const modal = document.getElementById('ai-booking-modal');
    if (modal) {
        modal.querySelector('.modal-body').innerHTML = `
            <div class="ai-analyzing">
                <div class="ai-loader">
                    <div class="ai-spinner"></div>
                    <i class="fa fa-robot"></i>
                </div>
                <h4>🤖 AI Working...</h4>
                <p>${message}</p>
                <div class="ai-progress">
                    <div class="ai-progress-bar"></div>
                </div>
            </div>
        `;
    }
}

// Show floating book button for selected room
function showFloatingBookButton(roomName, roomPrice) {
    // Remove existing floating button
    const existing = document.getElementById('floating-book-btn');
    if (existing) {
        existing.remove();
    }
    
    // Create floating book button
    const floatingBtn = document.createElement('div');
    floatingBtn.id = 'floating-book-btn';
    floatingBtn.innerHTML = `
        <div style="position: fixed; bottom: 20px; right: 20px; z-index: 1000; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 25px; border-radius: 50px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); cursor: pointer; transition: all 0.3s ease; max-width: 300px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fa fa-bed" style="font-size: 1.2rem;"></i>
                <div style="flex: 1;">
                    <div style="font-weight: 600; font-size: 0.9rem;">${roomName}</div>
                    <div style="opacity: 0.9; font-size: 0.8rem;">KES ${roomPrice.toLocaleString()}/night</div>
                </div>
                <a href="modules/guest/booking/luxury_booking.php?room=${encodeURIComponent(roomName)}&price=${roomPrice}&selected=true" 
                   style="background: rgba(255,255,255,0.2); color: white; padding: 8px 15px; border-radius: 20px; text-decoration: none; font-weight: 600; font-size: 0.8rem;">
                    Book Now
                </a>
            </div>
            <div onclick="this.parentElement.remove()" style="position: absolute; top: -5px; right: 5px; width: 20px; height: 20px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 0.7rem;">×</div>
        </div>
    `;
    
    document.body.appendChild(floatingBtn);
    
    // Auto-hide after 10 seconds
    setTimeout(() => {
        if (document.getElementById('floating-book-btn')) {
            floatingBtn.style.transform = 'translateX(100%)';
            setTimeout(() => floatingBtn.remove(), 300);
        }
    }, 10000);
}

// Check room availability function
function checkAvailability(roomName) {
    // Show loading modal
    showAvailabilityModal(roomName, true);
    
    // Fetch availability data
    fetch(`modules/guest/booking/check_availability.php?room=${encodeURIComponent(roomName)}&days=30`)
        .then(response => response.json())
        .then(data => {
            showAvailabilityModal(roomName, false, data);
        })
        .catch(error => {
            console.error('Error checking availability:', error);
            showAvailabilityModal(roomName, false, { error: 'Failed to check availability' });
        });
}

// Show availability modal
function showAvailabilityModal(roomName, isLoading, availabilityData) {
    // Remove existing modal
    const existingModal = document.getElementById('availability-modal');
    if (existingModal) {
        existingModal.remove();
    }
    
    let modalContent = '';
    
    if (isLoading) {
        modalContent = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fa fa-calendar"></i> Checking Availability</h3>
                    <span class="close" onclick="closeAvailabilityModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="loading-spinner">
                        <i class="fa fa-spinner fa-spin"></i>
                        <p>Checking availability for ${roomName}...</p>
                    </div>
                </div>
            </div>
        `;
    } else if (availabilityData && availabilityData.error) {
        modalContent = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fa fa-exclamation-triangle"></i> Error</h3>
                    <span class="close" onclick="closeAvailabilityModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="error-message">
                        <p>${availabilityData.error}</p>
                    </div>
                </div>
            </div>
        `;
    } else {
        const availablePeriods = availabilityData.available_periods || [];
        const roomDetails = availabilityData.room_details || {};
        
        let periodsHtml = '';
        if (availablePeriods.length > 0) {
            periodsHtml = availablePeriods.map(period => `
                <div class="availability-period">
                    <div class="period-dates">
                        <strong>${period.formatted_start}</strong> to <strong>${period.formatted_end}</strong>
                    </div>
                    <div class="period-info">
                        ${period.days} day${period.days > 1 ? 's' : ''} available
                        <button class="btn-quick-book" onclick="quickBook('${roomName}', '${period.start_date}', '${period.end_date}')">
                            Quick Book
                        </button>
                    </div>
                </div>
            `).join('');
        } else {
            periodsHtml = '<div class="no-availability">No availability in the next 30 days</div>';
        }
        
        modalContent = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fa fa-bed"></i> ${roomName} Availability</h3>
                    <span class="close" onclick="closeAvailabilityModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="room-info">
                        <div class="room-price">KES ${roomDetails.base_price ? parseInt(roomDetails.base_price).toLocaleString() : 'N/A'}/night</div>
                        <p>${roomDetails.description || 'Comfortable accommodation with modern amenities'}</p>
                    </div>
                    <div class="availability-section">
                        <h4>Next Available Periods</h4>
                        <div class="availability-periods">
                            ${periodsHtml}
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button class="btn btn-primary" onclick="bookRoom('${roomName}', ${roomDetails.base_price || 0})">
                            <i class="fa fa-calendar"></i> Book This Room
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Create modal
    const modal = document.createElement('div');
    modal.id = 'availability-modal';
    modal.className = 'availability-modal';
    modal.innerHTML = modalContent;
    
    document.body.appendChild(modal);
    
    // Add click outside to close
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeAvailabilityModal();
        }
    });
}

// Close availability modal
function closeAvailabilityModal() {
    const modal = document.getElementById('availability-modal');
    if (modal) {
        modal.remove();
    }
}

// Quick book function
function quickBook(roomName, startDate, endDate) {
    const url = `modules/guest/booking/luxury_booking.php?room=${encodeURIComponent(roomName)}&checkin=${startDate}&checkout=${endDate}&quick=true`;
    window.location.href = url;
}

// Book room function
function bookRoom(roomName, price) {
    const url = `modules/guest/booking/luxury_booking.php?room=${encodeURIComponent(roomName)}&price=${price}`;
    window.location.href = url;
}

// Add click handlers to room cards
document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers to room cards
    document.querySelectorAll('.room-card').forEach(card => {
        card.addEventListener('click', function(e) {
            // Don't trigger if clicking on buttons
            if (e.target.tagName === 'BUTTON' || e.target.tagName === 'A') {
                return;
            }
            
            const roomName = this.getAttribute('data-room');
            const roomPrice = parseFloat(this.getAttribute('data-price'));
            selectRoom(roomName, roomPrice);
        });
    });
    
    // Add hover effects
    document.querySelectorAll('.room-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            if (!this.classList.contains('selected')) {
                this.style.transform = 'translateY(-5px)';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            if (!this.classList.contains('selected')) {
                this.style.transform = 'translateY(0)';
            }
        });
    });
});
</script>

<style>
/* AI Booking Modal Styles */
.ai-booking-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    z-index: 1500;
    display: flex;
    justify-content: center;
    align-items: center;
    animation: fadeIn 0.3s ease-in-out;
}

.ai-modal-content {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 700px;
    width: 90%;
    max-height: 85vh;
    overflow-y: auto;
    animation: slideInUp 0.4s ease-out;
}

@keyframes slideInUp {
    from { transform: translateY(30px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.ai-modal-header {
    padding: 25px;
    border-bottom: 2px solid #e9ecef;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 20px 20px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ai-modal-header h3 {
    margin: 0;
    font-size: 1.6rem;
    font-family: 'Playfair Display', serif;
}

.ai-modal-body {
    padding: 30px;
}

.ai-greeting {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 30px;
    padding: 20px;
    background: linear-gradient(135deg, #f8f9ff 0%, #e3f2fd 100%);
    border-radius: 15px;
    border-left: 5px solid #667eea;
}

.ai-avatar {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.8rem;
    flex-shrink: 0;
}

.ai-option-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.ai-option-btn {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: left;
}

.ai-option-btn:hover {
    border-color: #667eea;
    background: #f8f9ff;
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.15);
}

.ai-option-btn i {
    font-size: 2rem;
    color: #667eea;
    width: 40px;
    text-align: center;
}

/* AI Forms */
.ai-form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.ai-form-control {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.ai-primary-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 15px 25px;
    border-radius: 30px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1rem;
}

.ai-primary-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .ai-modal-content {
        width: 95%;
        margin: 10px;
    }
    
    .ai-option-buttons {
        grid-template-columns: 1fr;
    }
}

/* Enhanced AI Booking Banner Styles */
.ai-booking-banner {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 120px;
    padding: 30px 20px;
    overflow: hidden;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #667eea 100%);
    background-size: 200% 200%;
    animation: gradientShift 8s ease infinite;
}

@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.ai-banner-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 40px;
    width: 100%;
    max-width: 1200px;
    position: relative;
    z-index: 2;
}

.ai-banner-text {
    flex: 1;
    text-align: center;
}

.ai-banner-title {
    margin: 0 0 15px 0;
    color: white;
    font-size: 2.2rem;
    font-weight: 700;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    letter-spacing: 1px;
    font-family: 'Playfair Display', serif;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.ai-icon-pulse {
    font-size: 2.5rem;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.ai-banner-subtitle {
    margin: 0;
    color: white;
    font-size: 1.2rem;
    opacity: 0.95;
    text-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
    line-height: 1.6;
    font-weight: 400;
}

.ai-highlight {
    background: rgba(255, 255, 255, 0.2);
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
    margin-left: 8px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(10px);
}

.ai-banner-actions {
    display: flex;
    gap: 20px;
    align-items: center;
    flex-shrink: 0;
}

.ai-primary-booking-btn {
    position: relative;
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
    color: white;
    border: none;
    padding: 18px 32px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    box-shadow: 
        0 8px 25px rgba(255, 107, 107, 0.4),
        0 4px 15px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    gap: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
    overflow: hidden;
}

.ai-primary-booking-btn:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 
        0 15px 40px rgba(255, 107, 107, 0.6),
        0 8px 25px rgba(0, 0, 0, 0.3);
    background: linear-gradient(135deg, #ff5722 0%, #d32f2f 100%);
}

.ai-primary-booking-btn:active {
    transform: translateY(-1px) scale(1.02);
}

.ai-primary-booking-btn i {
    font-size: 1.3rem;
    animation: robotBounce 2s ease-in-out infinite;
}

@keyframes robotBounce {
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(-5deg); }
    75% { transform: rotate(5deg); }
}

.btn-glow {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.6s ease;
}

.ai-primary-booking-btn:hover .btn-glow {
    left: 100%;
}

.ai-secondary-booking-btn {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.6);
    padding: 16px 28px;
    border-radius: 40px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    gap: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ai-secondary-booking-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    border-color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(255, 255, 255, 0.2);
}

.ai-secondary-booking-btn i {
    font-size: 1.1rem;
}

/* Background Particles */
.ai-banner-bg-effects {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

.ai-particle {
    position: absolute;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    animation: float 6s ease-in-out infinite;
}

.ai-particle-1 {
    width: 60px;
    height: 60px;
    top: 20%;
    left: 10%;
    animation-delay: 0s;
}

.ai-particle-2 {
    width: 40px;
    height: 40px;
    top: 60%;
    right: 15%;
    animation-delay: 2s;
}

.ai-particle-3 {
    width: 80px;
    height: 80px;
    bottom: 20%;
    left: 70%;
    animation-delay: 4s;
}

@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0.7; }
    33% { transform: translateY(-20px) rotate(120deg); opacity: 1; }
    66% { transform: translateY(-10px) rotate(240deg); opacity: 0.8; }
}

/* Responsive Design for AI Banner */
@media (max-width: 992px) {
    .ai-banner-content {
        flex-direction: column;
        gap: 25px;
        text-align: center;
    }
    
    .ai-banner-title {
        font-size: 1.8rem;
    }
    
    .ai-banner-subtitle {
        font-size: 1.1rem;
    }
    
    .ai-banner-actions {
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .ai-booking-banner {
        padding: 25px 15px;
        min-height: 100px;
    }
    
    .ai-banner-title {
        font-size: 1.6rem;
        flex-direction: column;
        gap: 10px;
    }
    
    .ai-icon-pulse {
        font-size: 2rem;
    }
    
    .ai-banner-subtitle {
        font-size: 1rem;
    }
    
    .ai-banner-actions {
        flex-direction: column;
        gap: 15px;
        width: 100%;
    }
    
    .ai-primary-booking-btn,
    .ai-secondary-booking-btn {
        width: 100%;
        justify-content: center;
        max-width: 280px;
    }
    
    .ai-primary-booking-btn {
        padding: 16px 24px;
        font-size: 1rem;
    }
    
    .ai-secondary-booking-btn {
        padding: 14px 24px;
    }
}

@media (max-width: 480px) {
    .ai-banner-title {
        font-size: 1.4rem;
    }
    
    .ai-banner-subtitle {
        font-size: 0.95rem;
    }
    
    .ai-highlight {
        display: block;
        margin: 8px 0 0 0;
        margin-left: 0;
    }
}
/* Availability Modal Styles */
.availability-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from { transform: translateY(50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.modal-header {
    padding: 25px 30px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 20px 20px 0 0;
}

.modal-header h3 {
    margin: 0;
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: white;
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

.close:hover {
    opacity: 1;
}

.modal-body {
    padding: 30px;
}

.loading-spinner {
    text-align: center;
    padding: 40px 20px;
}

.loading-spinner i {
    font-size: 2rem;
    color: #667eea;
    margin-bottom: 15px;
}

.room-info {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 25px;
}

.room-price {
    font-size: 1.5rem;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 10px;
}

.availability-section h4 {
    color: #2c3e50;
    margin-bottom: 15px;
    font-family: 'Playfair Display', serif;
}

.availability-period {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.availability-period:hover {
    border-color: #667eea;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1);
}

.period-dates {
    font-size: 1.1rem;
    color: #2c3e50;
    margin-bottom: 10px;
}

.period-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #6c757d;
}

.btn-quick-book {
    background: #28a745;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-quick-book:hover {
    background: #218838;
    transform: translateY(-1px);
}

.no-availability {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
    font-style: italic;
}

.modal-actions {
    margin-top: 25px;
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.btn {
    padding: 12px 25px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

/* Button styles for room actions */
.btn-availability {
    background: #17a2b8;
    color: white;
    border: 2px solid #17a2b8;
    padding: 8px 12px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.8rem;
    margin: 2px;
}

.btn-availability:hover {
    background: #138496;
    transform: translateY(-1px);
    color: white;
}

.room-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    justify-content: center;
    margin-top: 15px;
}

.room-actions .btn-select,
.room-actions .btn-book {
    font-size: 0.8rem;
    padding: 8px 12px;
    margin: 2px;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .modal-content {
        width: 95%;
        margin: 20px;
    }
    
    .modal-header {
        padding: 20px;
    }
    
    .modal-body {
        padding: 20px;
    }
    
    .period-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .room-actions {
        flex-direction: column;
    }
    
    .room-actions button,
    .room-actions a {
        width: 100%;
        margin: 2px 0;
    }
}
</style>

<?php 
// Include Maya AI Widget
include_once 'maya/components/maya_ai_widget.php';

include('includes/guest/footer.php'); 
?>
