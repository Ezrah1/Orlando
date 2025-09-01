<?php
// Include required files first
include 'db.php';
include 'includes/common/hotel_settings.php';

$page_title = 'Our Rooms - ' . (get_hotel_info('name') ?: 'Orlando International Resorts');
include 'includes/header.php';

// Get search/filter parameters
$search_term = isset($_GET['search']) ? mysqli_real_escape_string($con, $_GET['search']) : '';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 50000;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'price_asc';

// Build the query
$where_conditions = ['is_active = 1'];
if (!empty($search_term)) {
    $where_conditions[] = "(room_name LIKE '%$search_term%' OR description LIKE '%$search_term%')";
}
if ($min_price > 0) {
    $where_conditions[] = "base_price >= $min_price";
}
if ($max_price < 50000) {
    $where_conditions[] = "base_price <= $max_price";
}

$where_clause = implode(' AND ', $where_conditions);

// Set sorting
switch ($sort_by) {
    case 'price_desc':
        $order_clause = 'ORDER BY base_price DESC';
        break;
    case 'name_asc':
        $order_clause = 'ORDER BY room_name ASC';
        break;
    case 'name_desc':
        $order_clause = 'ORDER BY room_name DESC';
        break;
    default: // price_asc
        $order_clause = 'ORDER BY base_price ASC';
}

// Get rooms
$rooms_query = "SELECT * FROM named_rooms WHERE $where_clause $order_clause";
$rooms_result = mysqli_query($con, $rooms_query);

// Get price range for filters
$price_range_query = "SELECT MIN(base_price) as min_price, MAX(base_price) as max_price FROM named_rooms WHERE is_active = 1";
$price_range_result = mysqli_query($con, $price_range_query);
$price_range = mysqli_fetch_assoc($price_range_result);
?>

<!-- Page Header Banner -->
<div class="w3ls-banner">
    <div class="w3layouts-banner-top">
        <div class="container">
            <div class="agileits-banner-info">
                <h4>Our Rooms</h4>
                <h3>Comfort & Luxury Await</h3>
                <p>Choose from our collection of uniquely named rooms, each designed for your perfect stay</p>
            </div>
        </div>
    </div>
</div>

<!-- Main Rooms Section -->
<div class="rooms-section" style="padding: 60px 0; background: #f8f9fa;">
    <div class="container">
        
        <!-- Search and Filter Section -->
        <div class="rooms-filters" style="background: white; padding: 30px; border-radius: 15px; margin-bottom: 40px; box-shadow: 0 5px 25px rgba(0,0,0,0.1);">
            <div class="row">
                <div class="col-lg-12">
                    <h3 style="color: #0f2453; margin-bottom: 25px; text-align: center;">Find Your Perfect Room</h3>
                </div>
            </div>
            
            <form method="GET" class="room-search-form">
                <div class="row align-items-end">
                    <div class="col-md-3 mb-3">
                        <label for="search" style="font-weight: 600; color: #333; margin-bottom: 8px;">Search Rooms</label>
                        <input type="text" id="search" name="search" class="form-control" 
                               placeholder="Room name or amenities..." 
                               value="<?php echo htmlspecialchars($search_term); ?>"
                               style="border: 2px solid #e9ecef; border-radius: 8px; padding: 12px;">
                    </div>
                    
                    <div class="col-md-2 mb-3">
                        <label for="min_price" style="font-weight: 600; color: #333; margin-bottom: 8px;">Min Price</label>
                        <input type="number" id="min_price" name="min_price" class="form-control" 
                               placeholder="0" min="0" 
                               value="<?php echo $min_price > 0 ? $min_price : ''; ?>"
                               style="border: 2px solid #e9ecef; border-radius: 8px; padding: 12px;">
                    </div>
                    
                    <div class="col-md-2 mb-3">
                        <label for="max_price" style="font-weight: 600; color: #333; margin-bottom: 8px;">Max Price</label>
                        <input type="number" id="max_price" name="max_price" class="form-control" 
                               placeholder="50000" min="0" 
                               value="<?php echo $max_price < 50000 ? $max_price : ''; ?>"
                               style="border: 2px solid #e9ecef; border-radius: 8px; padding: 12px;">
                    </div>
                    
                    <div class="col-md-2 mb-3">
                        <label for="sort" style="font-weight: 600; color: #333; margin-bottom: 8px;">Sort By</label>
                        <select id="sort" name="sort" class="form-control" 
                                style="border: 2px solid #e9ecef; border-radius: 8px; padding: 12px;">
                            <option value="price_asc" <?php echo $sort_by == 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_desc" <?php echo $sort_by == 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="name_asc" <?php echo $sort_by == 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                            <option value="name_desc" <?php echo $sort_by == 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <button type="submit" class="btn btn-primary" 
                                style="background: #0f2453; border: none; padding: 12px 25px; border-radius: 8px; font-weight: 600; width: 100%; margin-right: 10px;">
                            <i class="fa fa-search"></i> Search Rooms
                        </button>
                    </div>
                </div>
                
                <?php if (!empty($search_term) || $min_price > 0 || $max_price < 50000 || $sort_by != 'price_asc'): ?>
                <div class="row mt-3">
                    <div class="col-12 text-center">
                        <a href="rooms.php" class="btn btn-outline-secondary" 
                           style="border: 2px solid #6c757d; color: #6c757d; padding: 8px 20px; border-radius: 8px; text-decoration: none;">
                            <i class="fa fa-refresh"></i> Clear All Filters
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Results Summary -->
        <div class="results-summary" style="margin-bottom: 30px;">
            <div class="row">
                <div class="col-md-6">
                    <p style="color: #666; font-size: 16px; margin: 0;">
                        <?php 
                        $total_rooms = mysqli_num_rows($rooms_result);
                        echo "Showing $total_rooms room" . ($total_rooms != 1 ? 's' : '');
                        if (!empty($search_term)) {
                            echo " matching '<strong>" . htmlspecialchars($search_term) . "</strong>'";
                        }
                        ?>
                    </p>
                </div>
                <div class="col-md-6 text-right">
                    <p style="color: #666; font-size: 14px; margin: 0;">
                        <?php if ($price_range): ?>
                        Price range: KES <?php echo number_format($price_range['min_price']); ?> - KES <?php echo number_format($price_range['max_price']); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Rooms Grid -->
        <div class="rooms-grid">
            <?php if (mysqli_num_rows($rooms_result) > 0): ?>
                <div class="row">
                    <?php while ($room = mysqli_fetch_assoc($rooms_result)): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="room-card" style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,0.1); transition: all 0.3s ease; position: relative;">
                            
                            <!-- Room Image Placeholder -->
                            <div class="room-image" style="height: 250px; background: linear-gradient(135deg, #0f2453 0%, #1a3567 100%); position: relative; overflow: hidden;">
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: white;">
                                    <i class="fa fa-bed" style="font-size: 48px; margin-bottom: 10px; opacity: 0.7;"></i>
                                    <p style="margin: 0; font-size: 14px; opacity: 0.8;">Room Photo Coming Soon</p>
                                </div>
                                
                                <!-- Price Badge -->
                                <div class="price-badge" style="position: absolute; top: 15px; right: 15px; background: #ffce14; color: #0f2453; padding: 8px 15px; border-radius: 20px; font-weight: 700; font-size: 16px;">
                                    KES <?php echo number_format($room['base_price']); ?>
                                </div>
                            </div>
                            
                            <!-- Room Content -->
                            <div class="room-content" style="padding: 25px;">
                                <h4 style="color: #0f2453; font-size: 1.5rem; font-weight: 700; margin-bottom: 15px;">
                                    <?php echo htmlspecialchars($room['room_name']); ?>
                                </h4>
                                
                                <p style="color: #666; margin-bottom: 20px; line-height: 1.6;">
                                    <?php 
                                    $description = $room['description'] ?: 'Comfortable and well-appointed room with modern amenities designed for your perfect stay.';
                                    echo htmlspecialchars($description);
                                    ?>
                                </p>
                                
                                <!-- Room Features -->
                                <div class="room-features" style="margin-bottom: 20px;">
                                    <div class="feature-item" style="display: inline-block; margin-right: 15px; margin-bottom: 8px; color: #0f2453;">
                                        <i class="fa fa-wifi" style="margin-right: 5px;"></i>
                                        <span style="font-size: 14px;">Free WiFi</span>
                                    </div>
                                    <div class="feature-item" style="display: inline-block; margin-right: 15px; margin-bottom: 8px; color: #0f2453;">
                                        <i class="fa fa-car" style="margin-right: 5px;"></i>
                                        <span style="font-size: 14px;">Parking</span>
                                    </div>
                                    <div class="feature-item" style="display: inline-block; margin-right: 15px; margin-bottom: 8px; color: #0f2453;">
                                        <i class="fa fa-cutlery" style="margin-right: 5px;"></i>
                                        <span style="font-size: 14px;">Room Service</span>
                                    </div>
                                    <div class="feature-item" style="display: inline-block; margin-right: 15px; margin-bottom: 8px; color: #0f2453;">
                                        <i class="fa fa-mobile" style="margin-right: 5px;"></i>
                                        <span style="font-size: 14px;">M-Pesa</span>
                                    </div>
                                </div>
                                


                                <!-- Action Buttons -->
                                <div class="room-actions" style="display: flex; gap: 15px;">
                                    <button onclick="openMayaChatForRoom('<?php echo addslashes($room['room_name']); ?>', <?php echo $room['base_price']; ?>)" 
                                            class="btn-ai-book" 
                                            style="flex: 1; background: linear-gradient(135deg, #ffce14, #ffd700); color: #0f2453; border: none; padding: 15px 25px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; font-size: 15px;">
                                        <i class="fa fa-robot"></i> AI Booking
                                    </button>
                                    <a href="modules/guest/booking/booking_form.php?room=<?php echo urlencode($room['room_name']); ?>" 
                                       class="btn-book-now" 
                                       style="flex: 1; background: #0f2453; color: white; padding: 15px 25px; border-radius: 8px; text-decoration: none; text-align: center; font-weight: 600; transition: all 0.3s ease; font-size: 15px;">
                                        <i class="fa fa-calendar-check-o"></i> Quick Book
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <!-- No Rooms Found -->
                <div class="no-rooms-found" style="text-align: center; padding: 60px 20px; background: white; border-radius: 15px;">
                    <i class="fa fa-bed" style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></i>
                    <h3 style="color: #666; margin-bottom: 15px;">No Rooms Found</h3>
                    <p style="color: #999; margin-bottom: 25px;">We couldn't find any rooms matching your criteria. Please try adjusting your filters.</p>
                    <a href="rooms.php" class="btn btn-primary" style="background: #0f2453; border: none; padding: 12px 25px; border-radius: 8px; text-decoration: none; color: white;">
                        <i class="fa fa-refresh"></i> View All Rooms
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Call to Action Section -->
        <div class="rooms-cta" style="background: linear-gradient(135deg, #0f2453, #1a3567); padding: 50px 30px; border-radius: 15px; text-align: center; margin-top: 60px; color: white;">
            <h3 style="color: white; margin-bottom: 15px; font-size: 2rem;">Ready to Book Your Stay?</h3>
            <p style="color: rgba(255,255,255,0.9); margin-bottom: 30px; font-size: 1.1rem;">
                Experience the perfect blend of comfort and luxury at <?php echo htmlspecialchars(get_hotel_info('name')); ?>
            </p>
            <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
                <a href="modules/guest/booking/booking_form.php" 
                   class="btn-cta-primary" 
                   style="background: #ffce14; color: #0f2453; padding: 15px 30px; border-radius: 30px; text-decoration: none; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; transition: all 0.3s ease;">
                    <i class="fa fa-calendar-plus-o"></i> Book Now
                </a>
                <a href="<?php echo get_phone_link(); ?>" 
                   class="btn-cta-secondary" 
                   style="background: transparent; color: white; border: 2px solid white; padding: 13px 30px; border-radius: 30px; text-decoration: none; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; transition: all 0.3s ease;">
                    <i class="fa fa-phone"></i> Call Us
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic Room Details Modal -->
<div id="roomModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6);">
    <div class="modal-content" style="background-color: white; margin: 2% auto; padding: 0; border-radius: 20px; width: 95%; max-width: 800px; position: relative; animation: slideIn 0.3s ease; max-height: 90vh; overflow-y: auto;">
        
        <!-- Modal Header -->
        <div class="modal-header" style="background: linear-gradient(135deg, #0f2453, #1a3567); color: white; padding: 25px 30px; border-radius: 20px 20px 0 0; position: relative;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <i class="fa fa-bed" style="font-size: 24px; color: #ffce14;"></i>
                <h4 id="modalRoomName" style="margin: 0; font-size: 1.8rem;"></h4>
            </div>
            <span class="close" onclick="closeModal()" style="position: absolute; right: 25px; top: 20px; font-size: 32px; font-weight: bold; cursor: pointer; color: white; transition: all 0.3s ease;">&times;</span>
            <div id="modalLoadingIndicator" style="margin-top: 10px; font-size: 14px; opacity: 0.9;">
                <i class="fa fa-spinner fa-spin"></i> Loading room details...
            </div>
        </div>
        
        <!-- Modal Body -->
        <div class="modal-body" style="padding: 30px;">
            
            <!-- Room Status & Price Section -->
            <div class="row" style="margin-bottom: 25px;">
                <div class="col-md-6">
                    <div class="price-info" style="background: linear-gradient(135deg, #f8f9fa, #e9ecef); padding: 20px; border-radius: 12px; text-align: center; border-left: 5px solid #ffce14;">
                        <span style="font-size: 28px; font-weight: 700; color: #0f2453;">KES <span id="modalRoomPrice"></span></span>
                        <span style="color: #666; font-size: 14px; display: block; margin-top: 5px;">per night</span>
                        <div id="modalPriceVariations" style="margin-top: 10px; font-size: 12px; color: #666;"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div id="modalAvailabilityStatus" style="background: #d4edda; padding: 20px; border-radius: 12px; text-align: center; border-left: 5px solid #28a745;">
                        <div style="font-size: 16px; font-weight: 600; color: #155724; margin-bottom: 5px;">
                            <i class="fa fa-check-circle"></i> Available Now
                        </div>
                        <div style="font-size: 13px; color: #155724;">Ready for immediate booking</div>
                    </div>
                </div>
            </div>
            
            <!-- Real-time Availability Calendar -->
            <div class="availability-section" style="margin-bottom: 25px;">
                <h5 style="color: #0f2453; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                    <i class="fa fa-calendar"></i> 7-Day Availability
                </h5>
                <div id="modalAvailabilityCalendar" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; margin-bottom: 15px;">
                    <!-- Dynamic calendar will be populated here -->
                </div>
                <div style="display: flex; gap: 15px; font-size: 12px; color: #666; justify-content: center;">
                    <div><span style="width: 12px; height: 12px; background: #28a745; display: inline-block; border-radius: 2px; margin-right: 5px;"></span>Available</div>
                    <div><span style="width: 12px; height: 12px; background: #ffc107; display: inline-block; border-radius: 2px; margin-right: 5px;"></span>Limited</div>
                    <div><span style="width: 12px; height: 12px; background: #dc3545; display: inline-block; border-radius: 2px; margin-right: 5px;"></span>Booked</div>
                </div>
            </div>
            
            <!-- Room Statistics -->
            <div class="room-stats" style="margin-bottom: 25px;">
                <h5 style="color: #0f2453; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                    <i class="fa fa-bar-chart"></i> Room Statistics
                </h5>
                <div class="row">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div style="background: #e8f4fd; padding: 15px; border-radius: 10px; text-align: center;">
                            <div style="font-size: 24px; font-weight: 700; color: #0f2453;" id="modalBookingCount">0</div>
                            <div style="font-size: 12px; color: #0c5460;">Total Bookings</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div style="background: #fff3cd; padding: 15px; border-radius: 10px; text-align: center;">
                            <div style="font-size: 24px; font-weight: 700; color: #856404;" id="modalOccupancyRate">0%</div>
                            <div style="font-size: 12px; color: #856404;">Occupancy Rate</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div style="background: #d1ecf1; padding: 15px; border-radius: 10px; text-align: center;">
                            <div style="font-size: 24px; font-weight: 700; color: #0c5460;" id="modalAvgStay">0</div>
                            <div style="font-size: 12px; color: #0c5460;">Avg Stay (days)</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div style="background: #f8d7da; padding: 15px; border-radius: 10px; text-align: center;">
                            <div style="font-size: 24px; font-weight: 700; color: #721c24;" id="modalPopularity">⭐</div>
                            <div style="font-size: 12px; color: #721c24;">Popularity</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Room Description & Features -->
            <div class="room-details" style="margin-bottom: 25px;">
                <div class="row">
                    <div class="col-md-8">
                        <h5 style="color: #0f2453; margin-bottom: 15px;">Room Description</h5>
                        <p id="modalRoomDescription" style="color: #666; line-height: 1.6; margin-bottom: 20px;"></p>
                        
                        <h5 style="color: #0f2453; margin-bottom: 15px;">Included Amenities</h5>
                        <div class="amenities-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; margin-bottom: 20px;">
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 14px;"><i class="fa fa-wifi" style="color: #28a745;"></i> Free WiFi</div>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 14px;"><i class="fa fa-car" style="color: #28a745;"></i> Free Parking</div>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 14px;"><i class="fa fa-cutlery" style="color: #28a745;"></i> Room Service</div>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 14px;"><i class="fa fa-mobile" style="color: #28a745;"></i> M-Pesa Payment</div>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 14px;"><i class="fa fa-phone" style="color: #28a745;"></i> 24/7 Support</div>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 14px;"><i class="fa fa-shield" style="color: #28a745;"></i> Secure Stay</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <!-- Quick Info Card -->
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 12px; border-left: 4px solid #0f2453;">
                            <h6 style="color: #0f2453; margin-bottom: 15px; font-weight: 600;">Quick Info</h6>
                            <div style="margin-bottom: 10px;">
                                <strong style="color: #333; font-size: 14px;">Room Type:</strong>
                                <span style="color: #666; font-size: 14px; display: block;" id="modalRoomType">Standard Room</span>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <strong style="color: #333; font-size: 14px;">Max Occupancy:</strong>
                                <span style="color: #666; font-size: 14px; display: block;" id="modalMaxOccupancy">2 Adults</span>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <strong style="color: #333; font-size: 14px;">Check-in:</strong>
                                <span style="color: #666; font-size: 14px; display: block;">2:00 PM</span>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <strong style="color: #333; font-size: 14px;">Check-out:</strong>
                                <span style="color: #666; font-size: 14px; display: block;">11:00 AM</span>
                            </div>
                            <div>
                                <strong style="color: #333; font-size: 14px;">Last Updated:</strong>
                                <span style="color: #666; font-size: 14px; display: block;" id="modalLastUpdated">Just now</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Booking Actions -->
            <div class="modal-actions" style="text-align: center; border-top: 1px solid #eee; padding-top: 25px;">
                <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <a id="modalBookButton" href="#" 
                       class="btn-book-modal" 
                       style="background: linear-gradient(135deg, #0f2453, #1a3567); color: white; padding: 15px 35px; border-radius: 30px; text-decoration: none; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; display: inline-block; transition: all 0.3s ease; font-size: 16px;">
                        <i class="fa fa-calendar-check-o"></i> Book This Room
                    </a>
                    <button onclick="checkAvailabilityDetailed()" 
                            style="background: #ffce14; color: #0f2453; border: none; padding: 15px 30px; border-radius: 30px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-size: 16px;">
                        <i class="fa fa-search"></i> Check Availability
                    </button>
                    <button onclick="closeModal()" 
                            style="background: transparent; color: #6c757d; border: 2px solid #6c757d; padding: 13px 25px; border-radius: 30px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                        <i class="fa fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Room Card Hover Effects */
.room-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.15) !important;
}

.btn-book-now:hover {
    background: #1a3567 !important;
    transform: translateY(-2px);
}

.btn-view-details:hover {
    background: #ffd700 !important;
    transform: scale(1.05);
}

.btn-cta-primary:hover {
    background: #ffd700 !important;
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(255, 206, 20, 0.4);
}

.btn-cta-secondary:hover {
    background: white !important;
    color: #0f2453 !important;
    transform: translateY(-3px);
}

.btn-book-modal:hover {
    background: #1a3567 !important;
    transform: translateY(-2px);
}

.btn-ai-book:hover {
    background: linear-gradient(135deg, #ffd700, #ffce14) !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255, 206, 20, 0.4);
}



/* Modal Animation */
@keyframes slideIn {
    from { opacity: 0; transform: translateY(-50px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideInMessage {
    from { opacity: 0; transform: translateX(-20px); }
    to { opacity: 1; transform: translateX(0); }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Search Form Enhancements */
.room-search-form input:focus,
.room-search-form select:focus {
    border-color: #0f2453 !important;
    box-shadow: 0 0 0 0.2rem rgba(15, 36, 83, 0.25) !important;
    outline: none;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .room-actions {
        flex-direction: column !important;
    }
    
    .rooms-cta > div {
        flex-direction: column !important;
    }
    
    .modal-content {
        width: 95% !important;
        margin: 10% auto !important;
    }
    
    .amenities-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
function showRoomDetails(name, description, price, roomId) {
    // Show modal immediately with basic info
    document.getElementById('modalRoomName').textContent = name;
    document.getElementById('modalRoomDescription').textContent = description || 'Comfortable and well-appointed room with modern amenities designed for your perfect stay.';
    document.getElementById('modalRoomPrice').textContent = new Intl.NumberFormat().format(price);
    document.getElementById('modalBookButton').href = 'modules/guest/booking/booking_form.php?room=' + encodeURIComponent(name);
    document.getElementById('roomModal').style.display = 'block';
    
    // Show loading indicator
    document.getElementById('modalLoadingIndicator').style.display = 'block';
    
    // Load dynamic data
    loadDynamicRoomData(roomId, name, price);
}

function loadDynamicRoomData(roomId, roomName, basePrice) {
    // Generate dynamic availability calendar
    generateAvailabilityCalendar();
    
    // Generate room statistics (simulated for now - replace with real data later)
    generateRoomStatistics(roomName, basePrice);
    
    // Update room type and occupancy based on price
    updateRoomTypeInfo(roomName, basePrice);
    
    // Update price variations
    updatePriceVariations(basePrice);
    
    // Update last updated time
    document.getElementById('modalLastUpdated').textContent = new Date().toLocaleString();
    
    // Update availability status based on current time
    updateAvailabilityStatus();
    
    // Hide loading indicator
    setTimeout(() => {
        document.getElementById('modalLoadingIndicator').style.display = 'none';
    }, 800);
}

function generateAvailabilityCalendar() {
    const calendar = document.getElementById('modalAvailabilityCalendar');
    const today = new Date();
    
    let calendarHTML = '';
    for (let i = 0; i < 7; i++) {
        const date = new Date(today);
        date.setDate(today.getDate() + i);
        
        // Simulate availability (replace with real data)
        const availability = Math.random();
        let status, color, textColor;
        
        if (availability > 0.7) {
            status = 'Available';
            color = '#28a745';
            textColor = 'white';
        } else if (availability > 0.3) {
            status = 'Limited';
            color = '#ffc107';
            textColor = '#212529';
        } else {
            status = 'Booked';
            color = '#dc3545';
            textColor = 'white';
        }
        
        calendarHTML += `
            <div style="
                background: ${color};
                color: ${textColor};
                padding: 12px 8px;
                border-radius: 8px;
                text-align: center;
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
            " title="${status} - ${date.toLocaleDateString()}" onclick="selectDate('${date.toISOString().split('T')[0]}')">
                <div style="font-size: 10px; opacity: 0.8;">${date.toLocaleDateString('en', {weekday: 'short'})}</div>
                <div style="font-size: 14px; font-weight: 700;">${date.getDate()}</div>
                <div style="font-size: 9px; opacity: 0.9;">${status.charAt(0)}</div>
            </div>
        `;
    }
    
    calendar.innerHTML = calendarHTML;
}

function generateRoomStatistics(roomName, basePrice) {
    // Simulate realistic statistics based on room characteristics
    const roomNameLower = roomName.toLowerCase();
    
    // Base statistics influenced by price and room name
    let baseBookings = Math.floor(Math.random() * 100) + 50;
    let occupancyRate = Math.floor(Math.random() * 40) + 60; // 60-100%
    let avgStay = Math.floor(Math.random() * 3) + 2; // 2-5 days
    
    // Adjust based on price tier
    if (basePrice <= 3000) {
        baseBookings += 20; // Budget rooms get more bookings
        occupancyRate = Math.min(95, occupancyRate + 10);
    } else if (basePrice >= 5000) {
        occupancyRate -= 10; // Luxury rooms have lower occupancy
        avgStay += 1; // But longer stays
    }
    
    // Adjust based on room name
    if (roomNameLower.includes('suite') || roomNameLower.includes('deluxe')) {
        avgStay += 1;
        occupancyRate -= 5;
    }
    
    // Update the display
    document.getElementById('modalBookingCount').textContent = baseBookings;
    document.getElementById('modalOccupancyRate').textContent = occupancyRate + '%';
    document.getElementById('modalAvgStay').textContent = avgStay;
    
    // Popularity based on occupancy
    let popularity = '⭐';
    if (occupancyRate >= 90) popularity = '⭐⭐⭐⭐⭐';
    else if (occupancyRate >= 80) popularity = '⭐⭐⭐⭐';
    else if (occupancyRate >= 70) popularity = '⭐⭐⭐';
    else if (occupancyRate >= 60) popularity = '⭐⭐';
    
    document.getElementById('modalPopularity').textContent = popularity;
}

function updateRoomTypeInfo(roomName, basePrice) {
    const roomNameLower = roomName.toLowerCase();
    
    // Determine room type
    let roomType = 'Standard Room';
    let maxOccupancy = '2 Adults';
    
    if (roomNameLower.includes('suite')) {
        roomType = 'Suite';
        maxOccupancy = '4 Adults';
    } else if (roomNameLower.includes('deluxe') || roomNameLower.includes('premium')) {
        roomType = 'Deluxe Room';
        maxOccupancy = '3 Adults';
    } else if (basePrice >= 5000) {
        roomType = 'Premium Room';
        maxOccupancy = '3 Adults';
    } else if (basePrice <= 3000) {
        roomType = 'Economy Room';
        maxOccupancy = '2 Adults';
    }
    
    document.getElementById('modalRoomType').textContent = roomType;
    document.getElementById('modalMaxOccupancy').textContent = maxOccupancy;
}

function updatePriceVariations(basePrice) {
    const variations = document.getElementById('modalPriceVariations');
    
    // Calculate weekend and weekday prices
    const weekendPrice = Math.round(basePrice * 1.15);
    const weekdayPrice = Math.round(basePrice * 0.95);
    
    variations.innerHTML = `
        <div style="margin-top: 8px;">
            <span style="font-size: 11px; color: #28a745;">Weekday: KES ${weekdayPrice.toLocaleString()}</span>
            <span style="margin: 0 8px;">•</span>
            <span style="font-size: 11px; color: #dc3545;">Weekend: KES ${weekendPrice.toLocaleString()}</span>
        </div>
    `;
}

function selectDate(dateString) {
    // Handle date selection for quick booking
    const selectedDate = new Date(dateString);
    const nextDay = new Date(selectedDate);
    nextDay.setDate(selectedDate.getDate() + 1);
    
    alert(`Selected check-in: ${selectedDate.toDateString()}\\nCheck-out: ${nextDay.toDateString()}\\n\\nWould you like to proceed with booking?`);
}

function checkAvailabilityDetailed() {
    // Open a more detailed availability checker
    alert('Opening detailed availability checker...\\nThis would show a full calendar with real-time availability, pricing, and booking options.');
}

// Update availability status dynamically
function updateAvailabilityStatus() {
    const statusDiv = document.getElementById('modalAvailabilityStatus');
    const now = new Date();
    const hour = now.getHours();
    
    // Simulate different availability states based on time
    if (hour >= 14 && hour <= 23) { // 2 PM to 11 PM
        statusDiv.style.background = '#d4edda';
        statusDiv.style.borderLeftColor = '#28a745';
        statusDiv.innerHTML = `
            <div style="font-size: 16px; font-weight: 600; color: #155724; margin-bottom: 5px;">
                <i class="fa fa-check-circle"></i> Available Now
            </div>
            <div style="font-size: 13px; color: #155724;">Ready for immediate check-in</div>
        `;
    } else if (hour >= 0 && hour < 6) { // Late night/early morning
        statusDiv.style.background = '#fff3cd';
        statusDiv.style.borderLeftColor = '#ffc107';
        statusDiv.innerHTML = `
            <div style="font-size: 16px; font-weight: 600; color: #856404; margin-bottom: 5px;">
                <i class="fa fa-clock-o"></i> Available Later
            </div>
            <div style="font-size: 13px; color: #856404;">Check-in available from 2:00 PM</div>
        `;
    } else {
        statusDiv.style.background = '#d1ecf1';
        statusDiv.style.borderLeftColor = '#17a2b8';
        statusDiv.innerHTML = `
            <div style="font-size: 16px; font-weight: 600; color: #0c5460; margin-bottom: 5px;">
                <i class="fa fa-calendar"></i> Book for Today
            </div>
            <div style="font-size: 13px; color: #0c5460;">Check-in from 2:00 PM today</div>
        `;
    }
}

function openAIBookingForRoom(roomName, roomPrice) {
    // Create responsive AI booking assistant modal
    const aiModal = document.createElement('div');
    aiModal.id = 'aiBookingModal';
    aiModal.style.cssText = `
        display: block;
        position: fixed;
        z-index: 1001;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.7);
        animation: fadeIn 0.3s ease;
    `;
    
    aiModal.innerHTML = `
        <div style="
            background: white;
            margin: 2% auto;
            padding: 0;
            border-radius: 20px;
            width: 95%;
            max-width: 900px;
            height: 85vh;
            position: relative;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        ">
            <!-- AI Header -->
            <div style="
                background: linear-gradient(135deg, #0f2453, #1a3567);
                color: white;
                padding: 20px 30px;
                position: relative;
                flex-shrink: 0;
            ">
                <div style="position: absolute; top: 15px; right: 20px;">
                    <span onclick="closeAIModal()" style="
                        font-size: 28px;
                        font-weight: bold;
                        cursor: pointer;
                        color: white;
                        transition: all 0.3s ease;
                    " onmouseover="this.style.color='#ffce14'" onmouseout="this.style.color='white'">&times;</span>
                </div>
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                    <div id="aiAvatar" style="
                        width: 50px;
                        height: 50px;
                        background: #ffce14;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 24px;
                        color: #0f2453;
                        animation: pulse 2s infinite;
                    ">🤖</div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.5rem;">AI Assistant Maya</h3>
                        <p style="margin: 0; opacity: 0.8; font-size: 14px;" id="aiStatus">Ready to help you book ${roomName}</p>
                    </div>
                </div>
            </div>
            
            <!-- Chat Container -->
            <div style="
                flex: 1;
                display: flex;
                flex-direction: column;
                overflow: hidden;
            ">
                <!-- Chat Messages -->
                <div id="aiChatMessages" style="
                    flex: 1;
                    overflow-y: auto;
                    padding: 20px 30px;
                    background: #f8f9fa;
                ">
                    <!-- Messages will be populated here -->
                </div>
                
                <!-- Quick Actions -->
                <div id="aiQuickActions" style="
                    padding: 15px 30px;
                    background: white;
                    border-top: 1px solid #eee;
                    display: flex;
                    gap: 10px;
                    flex-wrap: wrap;
                    justify-content: center;
                ">
                    <!-- Quick action buttons will be populated here -->
                </div>
                
                <!-- Chat Input -->
                <div style="
                    padding: 20px 30px;
                    background: white;
                    border-top: 1px solid #eee;
                    flex-shrink: 0;
                ">
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="text" id="aiChatInput" placeholder="Ask me anything about booking..." style="
                            flex: 1;
                            padding: 12px 20px;
                            border: 2px solid #e9ecef;
                            border-radius: 25px;
                            font-size: 14px;
                            outline: none;
                            transition: all 0.3s ease;
                        " onkeypress="if(event.key==='Enter') sendAIMessage()">
                        <button onclick="sendAIMessage()" style="
                            background: linear-gradient(135deg, #ffce14, #ffd700);
                            color: #0f2453;
                            border: none;
                            padding: 12px 20px;
                            border-radius: 25px;
                            font-weight: 600;
                            cursor: pointer;
                            transition: all 0.3s ease;
                        " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                            <i class="fa fa-paper-plane"></i> Send
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(aiModal);
    
    // Initialize the AI conversation
    initializeAIConversation(roomName, roomPrice);
}

function generateAIRecommendations(roomName, price) {
    const recommendations = [];
    
    // Price-based recommendations
    if (price <= 3000) {
        recommendations.push(`
            <div style="background: #d4edda; padding: 12px; border-radius: 8px; border-left: 4px solid #28a745;">
                <strong style="color: #155724;">💰 Excellent Value</strong>
                <p style="margin: 5px 0 0 0; font-size: 13px; color: #155724;">This room offers great value for budget-conscious travelers</p>
            </div>
        `);
    } else if (price <= 5000) {
        recommendations.push(`
            <div style="background: #fff3cd; padding: 12px; border-radius: 8px; border-left: 4px solid #ffc107;">
                <strong style="color: #856404;">⭐ Best Balance</strong>
                <p style="margin: 5px 0 0 0; font-size: 13px; color: #856404;">Perfect balance of comfort and affordability</p>
            </div>
        `);
    } else {
        recommendations.push(`
            <div style="background: #f8d7da; padding: 12px; border-radius: 8px; border-left: 4px solid #dc3545;">
                <strong style="color: #721c24;">✨ Luxury Experience</strong>
                <p style="margin: 5px 0 0 0; font-size: 13px; color: #721c24;">Premium room with enhanced amenities and service</p>
            </div>
        `);
    }
    
    // Occupancy recommendation
    const occupancy = price <= 4000 ? "1-2 guests" : "Up to 4 guests";
    recommendations.push(`
        <div style="background: #d1ecf1; padding: 12px; border-radius: 8px; border-left: 4px solid #17a2b8;">
            <strong style="color: #0c5460;">👥 Recommended Occupancy</strong>
            <p style="margin: 5px 0 0 0; font-size: 13px; color: #0c5460;">${occupancy} for optimal comfort</p>
        </div>
    `);
    
    // Timing recommendation
    const dayOfWeek = new Date().getDay();
    const timingTip = dayOfWeek >= 1 && dayOfWeek <= 4 
        ? "Book now for weekend availability" 
        : "Perfect for immediate booking";
    
    recommendations.push(`
        <div style="background: #e2e3e5; padding: 12px; border-radius: 8px; border-left: 4px solid #6c757d;">
            <strong style="color: #495057;">📅 Timing Advice</strong>
            <p style="margin: 5px 0 0 0; font-size: 13px; color: #495057;">${timingTip}</p>
        </div>
    `);
    
    return recommendations.join('');
}

function quickBook(option) {
    const today = new Date();
    let checkIn, checkOut;
    
    switch(option) {
        case 'tonight':
            checkIn = today.toISOString().split('T')[0];
            checkOut = new Date(today.getTime() + 24*60*60*1000).toISOString().split('T')[0];
            break;
        case 'weekend':
            const daysUntilFriday = (5 - today.getDay() + 7) % 7;
            checkIn = new Date(today.getTime() + daysUntilFriday*24*60*60*1000).toISOString().split('T')[0];
            checkOut = new Date(today.getTime() + (daysUntilFriday + 2)*24*60*60*1000).toISOString().split('T')[0];
            break;
        default:
            // For custom dates, just proceed to booking form
            break;
    }
    
    alert(`AI Booking: ${option === 'custom' ? 'Custom dates selected' : 'Check-in: ' + checkIn + ', Check-out: ' + checkOut}`);
}

function proceedToBooking(roomName) {
    closeAIModal();
    window.location.href = 'modules/guest/booking/booking_form.php?room=' + encodeURIComponent(roomName) + '&ai=true';
}

function closeAIModal() {
    const aiModal = document.getElementById('aiBookingModal');
    if (aiModal) {
        aiModal.remove();
    }
}

// Interactive AI Conversation System
let currentRoomData = {};
let conversationStep = 0;
let userPreferences = {};

function initializeAIConversation(roomName, roomPrice) {
    currentRoomData = { name: roomName, price: roomPrice };
    conversationStep = 0;
    userPreferences = {};
    
    // Add welcome message
    setTimeout(() => {
        addAIMessage("👋 Hi! I'm Maya, your AI booking assistant. I'm here to help you book the perfect stay in " + roomName + "!");
        
        setTimeout(() => {
            addAIMessage("💡 I can help you with dates, pricing, special requests, and even suggest the best deals. What would you like to know first?");
            showQuickActions(['availability', 'pricing', 'amenities', 'book_now']);
        }, 1500);
    }, 500);
}

function addAIMessage(message, isUser = false) {
    const chatContainer = document.getElementById('aiChatMessages');
    const messageDiv = document.createElement('div');
    
    messageDiv.style.cssText = `
        margin-bottom: 15px;
        display: flex;
        ${isUser ? 'justify-content: flex-end' : 'justify-content: flex-start'};
        animation: slideInMessage 0.3s ease;
    `;
    
    const bubbleStyle = isUser ? 
        'background: linear-gradient(135deg, #0f2453, #1a3567); color: white; margin-left: 60px;' :
        'background: white; color: #333; margin-right: 60px; border: 1px solid #e9ecef;';
    
    messageDiv.innerHTML = `
        <div style="
            padding: 12px 18px;
            border-radius: 18px;
            max-width: 80%;
            ${bubbleStyle}
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
        ">
            ${!isUser ? '<div style="position: absolute; left: -40px; top: 5px; width: 30px; height: 30px; background: #ffce14; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px;">🤖</div>' : ''}
            <div style="font-size: 14px; line-height: 1.4;">${message}</div>
            <div style="font-size: 11px; opacity: 0.7; margin-top: 5px; text-align: right;">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
        </div>
    `;
    
    chatContainer.appendChild(messageDiv);
    chatContainer.scrollTop = chatContainer.scrollHeight;
    
    // Show typing indicator for AI responses
    if (!isUser) {
        updateAIStatus('typing');
        setTimeout(() => updateAIStatus('online'), 2000);
    }
}

function sendAIMessage() {
    const input = document.getElementById('aiChatInput');
    const message = input.value.trim();
    
    if (message) {
        addAIMessage(message, true);
        input.value = '';
        
        // Process AI response
        setTimeout(() => {
            processAIResponse(message);
        }, 1000);
    }
}

function processAIResponse(userMessage) {
    const messageLower = userMessage.toLowerCase();
    
    // Analyze user intent and respond accordingly
    if (messageLower.includes('price') || messageLower.includes('cost') || messageLower.includes('rate')) {
        handlePricingInquiry();
    } else if (messageLower.includes('available') || messageLower.includes('dates') || messageLower.includes('when')) {
        handleAvailabilityInquiry();
    } else if (messageLower.includes('amenity') || messageLower.includes('facility') || messageLower.includes('include')) {
        handleAmenitiesInquiry();
    } else if (messageLower.includes('book') || messageLower.includes('reserve') || messageLower.includes('confirm')) {
        handleBookingRequest();
    } else if (messageLower.includes('discount') || messageLower.includes('deal') || messageLower.includes('offer')) {
        handleDiscountInquiry();
    } else if (messageLower.includes('location') || messageLower.includes('address') || messageLower.includes('where')) {
        handleLocationInquiry();
    } else if (messageLower.includes('cancel') || messageLower.includes('policy') || messageLower.includes('refund')) {
        handlePolicyInquiry();
    } else {
        handleGeneralInquiry(userMessage);
    }
}

function handlePricingInquiry() {
    const weekendPrice = Math.round(currentRoomData.price * 1.15);
    const weekdayPrice = Math.round(currentRoomData.price * 0.95);
    
    addAIMessage(`💰 Great question! Here's the pricing for ${currentRoomData.name}:`);
    setTimeout(() => {
        addAIMessage(`📅 **Weekdays:** KES ${weekdayPrice.toLocaleString()} per night<br>📅 **Weekends:** KES ${weekendPrice.toLocaleString()} per night<br>📅 **Base Rate:** KES ${currentRoomData.price.toLocaleString()} per night`);
        showQuickActions(['check_dates', 'book_weekday', 'book_weekend', 'ask_discount']);
    }, 1000);
}

function handleAvailabilityInquiry() {
    addAIMessage("📅 Let me check real-time availability for you!");
    
    setTimeout(() => {
        const availability = generateAvailabilityData();
        addAIMessage(`✅ Good news! ${currentRoomData.name} has availability:<br><br>🟢 **Tonight:** Available<br>🟡 **This Weekend:** Limited (2 rooms left)<br>🟢 **Next Week:** Excellent availability<br><br>When were you planning to stay?`);
        showQuickActions(['tonight', 'this_weekend', 'next_week', 'custom_dates']);
    }, 1500);
}

function handleAmenitiesInquiry() {
    addAIMessage("🏨 Here's what's included with your stay:");
    
    setTimeout(() => {
        addAIMessage(`✨ **${currentRoomData.name} includes:**<br><br>🔌 Free High-Speed WiFi<br>🚗 Complimentary Parking<br>🍽️ 24/7 Room Service<br>📱 M-Pesa Payment (No fees)<br>🔒 24/7 Security<br>🧹 Daily Housekeeping<br><br>Anything specific you'd like to know more about?`);
        showQuickActions(['room_service', 'wifi_details', 'parking_info', 'book_now']);
    }, 1000);
}

function handleBookingRequest() {
    conversationStep++;
    
    if (!userPreferences.dates) {
        addAIMessage("🗓️ Perfect! Let's get you booked. First, what dates work best for you?");
        showQuickActions(['tonight', 'this_weekend', 'next_week', 'custom_dates']);
    } else if (!userPreferences.guests) {
        addAIMessage("👥 Great! How many guests will be staying?");
        showQuickActions(['1_guest', '2_guests', '3_guests', '4_guests']);
    } else {
        addAIMessage("🎉 Excellent! I have all the details I need. Let me prepare your booking...");
        setTimeout(() => {
            addAIMessage("✅ Ready to proceed! Click the button below to complete your reservation for " + currentRoomData.name);
            showQuickActions(['complete_booking']);
        }, 1500);
    }
}

function handleDiscountInquiry() {
    const discounts = [
        "🎯 **Extended Stay:** 10% off for 3+ nights",
        "💰 **Early Bird:** 5% off for bookings 7+ days in advance",
        "👥 **Group Rate:** Special rates for 3+ rooms",
        "🎓 **Student Discount:** 15% off with valid student ID"
    ];
    
    addAIMessage("💸 Great question! Here are current offers:");
    setTimeout(() => {
        addAIMessage(discounts.join('<br><br>') + '<br><br>Which one interests you most?');
        showQuickActions(['extended_stay', 'early_bird', 'group_rate', 'student_discount']);
    }, 1000);
}

function handleLocationInquiry() {
    addAIMessage("📍 Orlando International Resorts is perfectly located!");
    setTimeout(() => {
        addAIMessage(`🏨 **Address:** Machakos, Kenya<br>🚗 **Distance:** 5 minutes from city center<br>🏪 **Nearby:** Shopping centers, restaurants, banks<br>✈️ **Airport:** 45 minutes to JKIA<br><br>Need directions or transport info?`);
        showQuickActions(['directions', 'transport', 'nearby_places', 'book_now']);
    }, 1000);
}

function handlePolicyInquiry() {
    addAIMessage("📋 Here's our guest-friendly policy:");
    setTimeout(() => {
        addAIMessage(`✅ **Cancellation:** Free cancellation up to 24 hours before arrival<br>💰 **Payment:** No deposit required, pay on arrival<br>🕐 **Check-in:** 2:00 PM onwards<br>🕙 **Check-out:** 11:00 AM<br>🔄 **Modification:** Free date changes subject to availability<br><br>Any specific policy questions?`);
        showQuickActions(['modify_booking', 'cancellation', 'payment_options', 'book_now']);
    }, 1000);
}

function handleGeneralInquiry(message) {
    const responses = [
        "🤔 That's a great question! Let me help you with that.",
        "💭 I understand you're asking about " + message.toLowerCase() + ". Here's what I can tell you:",
        "✨ Thanks for asking! I'm here to make your booking experience smooth.",
        "🎯 Good point! Let me provide you with the best information."
    ];
    
    const randomResponse = responses[Math.floor(Math.random() * responses.length)];
    addAIMessage(randomResponse);
    
    setTimeout(() => {
        addAIMessage("For the most accurate information about " + currentRoomData.name + ", I'd recommend exploring these options:");
        showQuickActions(['room_details', 'availability', 'pricing', 'book_now']);
    }, 1000);
}

function showQuickActions(actions) {
    const quickActionsContainer = document.getElementById('aiQuickActions');
    quickActionsContainer.innerHTML = '';
    
    const actionLabels = {
        'availability': '📅 Check Availability',
        'pricing': '💰 View Pricing',
        'amenities': '🏨 Amenities',
        'book_now': '🚀 Book Now',
        'check_dates': '📅 Check Specific Dates',
        'book_weekday': '💰 Book Weekday Rate',
        'book_weekend': '📅 Book Weekend',
        'ask_discount': '💸 Available Discounts',
        'tonight': '🌙 Tonight',
        'this_weekend': '📅 This Weekend',
        'next_week': '📅 Next Week',
        'custom_dates': '🗓️ Custom Dates',
        'room_service': '🍽️ Room Service',
        'wifi_details': '📶 WiFi Info',
        'parking_info': '🚗 Parking Details',
        '1_guest': '👤 1 Guest',
        '2_guests': '👥 2 Guests',
        '3_guests': '👥 3 Guests',
        '4_guests': '👥 4 Guests',
        'complete_booking': '✅ Complete Booking',
        'extended_stay': '🎯 Extended Stay Discount',
        'early_bird': '⏰ Early Bird Offer',
        'group_rate': '👥 Group Discount',
        'student_discount': '🎓 Student Rate',
        'directions': '🗺️ Get Directions',
        'transport': '🚕 Transport Options',
        'nearby_places': '📍 Nearby Places',
        'modify_booking': '🔄 Modify Booking',
        'cancellation': '❌ Cancellation Info',
        'payment_options': '💳 Payment Methods',
        'room_details': '🏨 Room Details'
    };
    
    actions.forEach(action => {
        const button = document.createElement('button');
        button.textContent = actionLabels[action] || action;
        button.style.cssText = `
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            color: #0f2453;
            border: 1px solid #dee2e6;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 2px;
        `;
        
        button.onmouseover = () => {
            button.style.background = 'linear-gradient(135deg, #ffce14, #ffd700)';
            button.style.transform = 'translateY(-2px)';
        };
        
        button.onmouseout = () => {
            button.style.background = 'linear-gradient(135deg, #f8f9fa, #e9ecef)';
            button.style.transform = 'translateY(0)';
        };
        
        button.onclick = () => handleQuickAction(action);
        quickActionsContainer.appendChild(button);
    });
}

function handleQuickAction(action) {
    // Simulate user clicking on quick action
    const actionText = document.querySelector(`button[onclick="handleQuickAction('${action}')"]`).textContent;
    addAIMessage(actionText, true);
    
    // Process the action
    setTimeout(() => {
        switch(action) {
            case 'availability':
                handleAvailabilityInquiry();
                break;
            case 'pricing':
                handlePricingInquiry();
                break;
            case 'amenities':
                handleAmenitiesInquiry();
                break;
            case 'book_now':
            case 'complete_booking':
                handleBookingRequest();
                break;
            case 'tonight':
                userPreferences.dates = 'tonight';
                addAIMessage("🌙 Perfect choice! Tonight's rate is KES " + Math.round(currentRoomData.price * 0.95).toLocaleString() + ". How many guests?");
                showQuickActions(['1_guest', '2_guests', '3_guests', '4_guests']);
                break;
            case 'this_weekend':
                userPreferences.dates = 'weekend';
                addAIMessage("📅 Excellent! Weekend rate is KES " + Math.round(currentRoomData.price * 1.15).toLocaleString() + " per night. How many guests will be staying?");
                showQuickActions(['1_guest', '2_guests', '3_guests', '4_guests']);
                break;
            case '1_guest':
            case '2_guests':
            case '3_guests':
            case '4_guests':
                userPreferences.guests = action;
                handleBookingRequest();
                break;
            default:
                addAIMessage("🎯 Thanks for that selection! Let me help you further.");
                showQuickActions(['availability', 'pricing', 'book_now']);
        }
    }, 500);
}

function updateAIStatus(status) {
    const statusElement = document.getElementById('aiStatus');
    const avatar = document.getElementById('aiAvatar');
    
    switch(status) {
        case 'typing':
            statusElement.textContent = 'Maya is typing...';
            avatar.innerHTML = '💭';
            break;
        case 'thinking':
            statusElement.textContent = 'Thinking...';
            avatar.innerHTML = '🤔';
            break;
        case 'online':
            statusElement.textContent = `Ready to help you book ${currentRoomData.name}`;
            avatar.innerHTML = '🤖';
            break;
    }
}

function generateAvailabilityData() {
    // Simulate real availability data
    return {
        tonight: Math.random() > 0.3,
        weekend: Math.random() > 0.5,
        nextWeek: Math.random() > 0.2
    };
}

function closeModal() {
    document.getElementById('roomModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('roomModal');
    if (event.target == modal) {
        closeModal();
    }
}

// Enhanced form interactions
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form on sort change
    const sortSelect = document.getElementById('sort');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            this.form.submit();
        });
    }
    
    // Price range validation
    const minPrice = document.getElementById('min_price');
    const maxPrice = document.getElementById('max_price');
    
    if (minPrice && maxPrice) {
        minPrice.addEventListener('change', function() {
            if (maxPrice.value && parseInt(this.value) > parseInt(maxPrice.value)) {
                maxPrice.value = this.value;
            }
        });
        
        maxPrice.addEventListener('change', function() {
            if (minPrice.value && parseInt(this.value) < parseInt(minPrice.value)) {
                minPrice.value = this.value;
            }
        });
    }
});
</script>

<?php 
// Include Maya AI Widget
include_once 'maya/components/maya_ai_widget.php';

include 'includes/footer.php'; 
?>
