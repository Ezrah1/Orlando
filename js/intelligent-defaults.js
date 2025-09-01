/**
 * Intelligent Form Defaults System
 * Sets logical default choices for dropdowns based on context and form type
 */

(function($) {
    'use strict';
    
    // Configuration for different form types
    const DefaultConfigs = {
        // Guest booking forms
        guest_booking: {
            title: 'Mr', // Most common title
            meal: 'Bed & Breakfast', // Popular choice
            adults: '2', // Typical couple booking
            children: '0', // Most bookings are adults only
            nationality: 'Kenyan', // Local preference
            payment_method: 'mpesa' // Popular payment method in Kenya
        },
        
        // Staff/admin booking forms
        staff_booking: {
            guest_nationality: 'Kenyan', // Most guests are local
            adults: '2', // Standard occupancy
            children: '0', // Business travelers typically
            payment_status: 'pending', // Default status
            payment_method: 'cash' // Common for walk-ins
        },
        
        // Restaurant/bar orders
        restaurant_order: {
            category: 'main_course', // Most popular
            quantity: '1', // Standard quantity
            preparation: 'normal' // Standard preparation
        },
        
        // Inventory forms
        inventory: {
            unit: 'pieces', // Common unit
            category: 'general', // Default category
            status: 'active' // Default status
        },
        
        // User management
        user_management: {
            role: 'staff', // Most common role
            status: 'active', // Default status
            department: 'front_desk' // Common department
        }
    };
    
    // Initialize intelligent defaults when document is ready
    $(document).ready(function() {
        initIntelligentDefaults();
    });
    
    function initIntelligentDefaults() {
        // Detect form type and apply appropriate defaults
        detectAndApplyDefaults();
        
        // Set up dynamic defaults based on user interactions
        setupDynamicDefaults();
        
        // Set date defaults for booking forms
        setDateDefaults();
        
        // Apply time-based defaults
        setTimeBasedDefaults();
        
        // Set up contextual defaults
        setupContextualDefaults();
    }
    
    function detectAndApplyDefaults() {
        const currentPath = window.location.pathname;
        const formClasses = document.querySelector('form') ? document.querySelector('form').className : '';
        
        let configType = null;
        
        // Detect form type based on URL and form classes
        if (currentPath.includes('booking') || formClasses.includes('booking-form')) {
            if (currentPath.includes('staff') || currentPath.includes('admin')) {
                configType = 'staff_booking';
            } else {
                configType = 'guest_booking';
            }
        } else if (currentPath.includes('restaurant') || currentPath.includes('menu')) {
            configType = 'restaurant_order';
        } else if (currentPath.includes('inventory')) {
            configType = 'inventory';
        } else if (currentPath.includes('user')) {
            configType = 'user_management';
        }
        
        if (configType && DefaultConfigs[configType]) {
            applyDefaults(DefaultConfigs[configType]);
        }
        
        // Apply universal defaults
        applyUniversalDefaults();
    }
    
    function applyDefaults(config) {
        Object.keys(config).forEach(fieldName => {
            const defaultValue = config[fieldName];
            const $field = $(`select[name="${fieldName}"], input[name="${fieldName}"]`);
            
            if ($field.length > 0 && !$field.val()) {
                // Set the default value
                $field.val(defaultValue);
                
                // Trigger change event to update any dependent elements
                $field.trigger('change');
                
                // Add visual indication
                if ($field.is('select')) {
                    $field.addClass('default-applied');
                }
                
                // Log for debugging
                console.log(`Applied default: ${fieldName} = ${defaultValue}`);
            }
        });
    }
    
    function applyUniversalDefaults() {
        // Currency defaults for Kenya
        $('select[name*="currency"]').each(function() {
            if (!$(this).val()) {
                $(this).val('KES').trigger('change');
            }
        });
        
        // Country defaults
        $('select[name*="country"]').each(function() {
            if (!$(this).val()) {
                $(this).val('Kenya').trigger('change');
            }
        });
        
        // Status defaults
        $('select[name*="status"]:not([name*="payment"])').each(function() {
            if (!$(this).val()) {
                $(this).val('active').trigger('change');
            }
        });
    }
    
    function setDateDefaults() {
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        
        const todayStr = today.toISOString().split('T')[0];
        const tomorrowStr = tomorrow.toISOString().split('T')[0];
        
        // Check-in dates default to today
        $('input[name*="check_in"], input[name*="cin"]').each(function() {
            if (!$(this).val()) {
                $(this).val(todayStr);
                $(this).trigger('change');
            }
        });
        
        // Check-out dates default to tomorrow
        $('input[name*="check_out"], input[name*="cout"]').each(function() {
            if (!$(this).val()) {
                $(this).val(tomorrowStr);
                $(this).trigger('change');
            }
        });
        
        // Booking dates for future bookings
        $('input[name*="booking_date"]').each(function() {
            if (!$(this).val()) {
                $(this).val(todayStr);
                $(this).trigger('change');
            }
        });
    }
    
    function setTimeBasedDefaults() {
        const currentHour = new Date().getHours();
        
        // Meal plan suggestions based on time
        if (currentHour >= 6 && currentHour <= 10) {
            // Morning: suggest breakfast included
            $('select[name="meal"]').each(function() {
                if (!$(this).val()) {
                    $(this).val('Bed & Breakfast').trigger('change');
                }
            });
        } else if (currentHour >= 18 && currentHour <= 22) {
            // Evening: suggest dinner included
            $('select[name="meal"]').each(function() {
                if (!$(this).val()) {
                    $(this).val('Half Board').trigger('change');
                }
            });
        }
        
        // Working hours vs. after hours defaults
        if (currentHour >= 9 && currentHour <= 17) {
            // Business hours - prefer immediate bookings
            $('select[name="priority"]').val('normal');
        } else {
            // After hours - might need next day processing
            $('select[name="priority"]').val('low');
        }
    }
    
    function setupDynamicDefaults() {
        // Room type influences meal plan
        $('select[name="troom"], select[name="room_name"]').on('change', function() {
            const roomType = $(this).val().toLowerCase();
            const $mealSelect = $('select[name="meal"]');
            
            if (roomType.includes('suite') || roomType.includes('deluxe')) {
                if (!$mealSelect.val()) {
                    $mealSelect.val('Full Board').trigger('change');
                }
            } else if (roomType.includes('standard')) {
                if (!$mealSelect.val()) {
                    $mealSelect.val('Bed & Breakfast').trigger('change');
                }
            }
        });
        
        // Adults count influences children default
        $('select[name="adults"]').on('change', function() {
            const adults = parseInt($(this).val());
            const $childrenSelect = $('select[name="children"]');
            
            if (adults >= 3 && !$childrenSelect.val()) {
                // Larger groups might have children
                $childrenSelect.val('1').trigger('change');
            }
        });
        
        // Nationality influences ID field placeholder
        $('select[name*="nationality"]').on('change', function() {
            const nationality = $(this).val();
            const $idField = $('input[name*="id_number"], input[name*="passport"]');
            
            if (nationality === 'Kenyan') {
                $idField.attr('placeholder', 'Enter Kenyan ID Number');
            } else {
                $idField.attr('placeholder', 'Enter Passport Number');
            }
        });
        
        // Payment method influences status
        $('input[name="payment_method"], select[name="payment_method"]').on('change', function() {
            const method = $(this).val();
            const $statusSelect = $('select[name="payment_status"]');
            
            if (method === 'cash' && !$statusSelect.val()) {
                $statusSelect.val('pending').trigger('change');
            } else if (method === 'mpesa' && !$statusSelect.val()) {
                $statusSelect.val('pending').trigger('change');
            }
        });
    }
    
    function setupContextualDefaults() {
        // Weekend vs weekday defaults
        const today = new Date();
        const dayOfWeek = today.getDay();
        const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
        
        if (isWeekend) {
            // Weekend bookings tend to be longer
            $('select[name="meal"]').each(function() {
                if (!$(this).val()) {
                    $(this).val('Full Board').trigger('change');
                }
            });
        }
        
        // Season-based defaults (simplified)
        const month = today.getMonth();
        const isHighSeason = month >= 11 || month <= 2; // Dec-Feb high season
        
        if (isHighSeason) {
            // High season - suggest premium options
            $('.room-priority').each(function() {
                if (!$(this).val()) {
                    $(this).val('high').trigger('change');
                }
            });
        }
    }
    
    // Public API for manual default setting
    window.IntelligentDefaults = {
        applyDefaults: function(formType) {
            if (DefaultConfigs[formType]) {
                applyDefaults(DefaultConfigs[formType]);
            }
        },
        
        setCustomDefault: function(fieldName, value) {
            const $field = $(`select[name="${fieldName}"], input[name="${fieldName}"]`);
            if ($field.length > 0 && !$field.val()) {
                $field.val(value).trigger('change');
            }
        },
        
        refreshDefaults: function() {
            detectAndApplyDefaults();
        },
        
        addCustomConfig: function(formType, config) {
            DefaultConfigs[formType] = config;
        }
    };
    
    // Auto-apply defaults when new form elements are added dynamically
    $(document).on('DOMNodeInserted', 'select, input', function() {
        setTimeout(() => {
            detectAndApplyDefaults();
        }, 100);
    });
    
})(jQuery);

// Fallback for non-jQuery environments
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery === 'undefined') {
        console.log('Intelligent Defaults: jQuery not available, using basic fallback');
        
        // Basic fallback implementation
        const today = new Date().toISOString().split('T')[0];
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const tomorrowStr = tomorrow.toISOString().split('T')[0];
        
        // Set date defaults
        const checkinInputs = document.querySelectorAll('input[name*="check_in"], input[name*="cin"]');
        checkinInputs.forEach(input => {
            if (!input.value) input.value = today;
        });
        
        const checkoutInputs = document.querySelectorAll('input[name*="check_out"], input[name*="cout"]');
        checkoutInputs.forEach(input => {
            if (!input.value) input.value = tomorrowStr;
        });
        
        // Set common select defaults
        const nationalitySelects = document.querySelectorAll('select[name*="nationality"]');
        nationalitySelects.forEach(select => {
            if (!select.value) select.value = 'Kenyan';
        });
    }
});
