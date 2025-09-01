<?php
// Ensure path_prefix is set (in case footer is included without header)
if (!isset($path_prefix)) {
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
}

// Include hotel settings if not already included
if (!function_exists('get_hotel_info')) {
    require_once __DIR__ . '/common/hotel_settings.php';
}
?>
<!-- Footer -->
<div class="copy">
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <p>© 2024 <?php echo htmlspecialchars(get_hotel_info('name')); ?>. All Rights Reserved | Luxury Meets Affordability in <?php echo htmlspecialchars(get_hotel_info('city')); ?></p>
            </div>
            <div class="col-md-4 text-right">
                <a href="<?php echo $path_prefix; ?>modules/guest/booking/booking_form.php" class="btn btn-primary btn-sm">Book Your Stay</a>
                <a href="<?php echo get_phone_link(); ?>" class="btn btn-success btn-sm">Call Now</a>
            </div>
        </div>
    </div>
</div>

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

<!-- Shared JavaScript -->
<script>
// Smooth scrolling
jQuery(document).ready(function($) {
    $(".scroll").click(function(event){		
        event.preventDefault();
        $('html,body').animate({scrollTop:$(this.hash).offset().top},1000);
    });
});

// UI to top
$().UItoTop({ easingType: 'easeOutQuart' });

// Auto-hide alerts
$(document).ready(function() {
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});

// Form validation
function validateForm(formId) {
    var form = document.getElementById(formId);
    var inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    var isValid = true;
    
    inputs.forEach(function(input) {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade in`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    notification.innerHTML = `
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        <strong>${type === 'success' ? '✓' : 'ℹ'}</strong> ${message}
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 5000);
}
</script>

</body>
</html>
