/**
 * Dropdown Selection Enhancement Script
 * Provides better visual feedback for dropdown selections
 */

(function($) {
    'use strict';
    
    // Initialize dropdown enhancements when document is ready
    $(document).ready(function() {
        initDropdownEnhancements();
        
        // Force refresh all dropdowns after a short delay to ensure proper display
        setTimeout(function() {
            forceDropdownRefresh();
        }, 500);
    });
    
    function initDropdownEnhancements() {
        // Enhanced select styling and feedback
        enhanceSelectElements();
        
        // Add selection indicators
        addSelectionIndicators();
        
        // Handle dynamic form updates
        setupDynamicUpdates();
        
        // Add keyboard navigation improvements
        improveKeyboardNavigation();
    }
    
    function enhanceSelectElements() {
        // Apply enhanced styling to all select elements
        $('select.form-control').each(function() {
            var $select = $(this);
            
            // Add wrapper if not already wrapped
            if (!$select.parent().hasClass('custom-select-wrapper')) {
                $select.wrap('<div class="custom-select-wrapper"></div>');
            }
            
            // Add enhanced class
            $select.addClass('enhanced');
            
            // Handle selection changes
            $select.on('change', function() {
                handleSelectionChange($(this));
            });
            
            // Initial state setup
            if ($select.val() && $select.val() !== '') {
                handleSelectionChange($select);
            }
        });
    }
    
    function handleSelectionChange($select) {
        var selectedValue = $select.val();
        var selectedText = $select.find('option:selected').text();
        
        // Remove any existing indicators
        $select.siblings('.selection-indicator').remove();
        
        if (selectedValue && selectedValue !== '') {
            // Add visual feedback
            $select.addClass('has-selection');
            
            // Force display refresh for browser compatibility
            $select.css('color', '#333333');
            $select.css('background-color', '#ffffff');
            
            // For webkit browsers, force text visibility
            $select.css('-webkit-text-fill-color', '#333333');
            
            // Create selection indicator
            var $indicator = $('<span class="selection-indicator">')
                .html('<i class="fa fa-check-circle text-success"></i>')
                .css({
                    position: 'absolute',
                    right: '30px',
                    top: '50%',
                    transform: 'translateY(-50%)',
                    pointerEvents: 'none',
                    zIndex: 10
                });
            
            $select.parent().css('position', 'relative').append($indicator);
            
            // Add tooltip with selected value
            $select.attr('title', 'Selected: ' + selectedText);
            
            // Trigger custom event
            $select.trigger('selection:changed', {
                value: selectedValue,
                text: selectedText
            });
            
            // Flash effect to indicate change
            $select.addClass('selection-flash');
            setTimeout(function() {
                $select.removeClass('selection-flash');
            }, 500);
            
            // Force a repaint to ensure text is visible
            setTimeout(function() {
                $select.hide().show(0);
            }, 10);
            
        } else {
            $select.removeClass('has-selection');
            $select.removeAttr('title');
        }
        
        // Update any dependent elements
        updateDependentElements($select);
    }
    
    function addSelectionIndicators() {
        // Add CSS for selection flash effect
        if (!$('#dropdown-enhancement-styles').length) {
            var styles = `
                <style id="dropdown-enhancement-styles">
                    .selection-flash {
                        background-color: #d4edda !important;
                        border-color: #28a745 !important;
                        transition: all 0.3s ease !important;
                    }
                    
                    .has-selection {
                        border-color: #28a745 !important;
                        background-color: #f8fff9 !important;
                    }
                    
                    .selection-indicator {
                        animation: fadeIn 0.3s ease-in-out;
                    }
                    
                    @keyframes fadeIn {
                        from { opacity: 0; transform: translateY(-50%) scale(0.8); }
                        to { opacity: 1; transform: translateY(-50%) scale(1); }
                    }
                    
                    .dropdown-help-text {
                        font-size: 0.875rem;
                        color: #6c757d;
                        margin-top: 4px;
                        font-style: italic;
                    }
                    
                    .dropdown-validation-message {
                        font-size: 0.875rem;
                        margin-top: 4px;
                        padding: 4px 8px;
                        border-radius: 4px;
                    }
                    
                    .dropdown-validation-message.success {
                        background-color: #d4edda;
                        color: #155724;
                        border: 1px solid #c3e6cb;
                    }
                    
                    .dropdown-validation-message.error {
                        background-color: #f8d7da;
                        color: #721c24;
                        border: 1px solid #f5c6cb;
                    }
                </style>
            `;
            $('head').append(styles);
        }
    }
    
    function setupDynamicUpdates() {
        // Handle dynamically added select elements
        $(document).on('change', 'select.form-control:not(.enhanced)', function() {
            var $select = $(this);
            $select.addClass('enhanced');
            handleSelectionChange($select);
        });
        
        // Handle form resets
        $(document).on('reset', 'form', function() {
            setTimeout(function() {
                $('select.form-control').each(function() {
                    handleSelectionChange($(this));
                });
            }, 100);
        });
    }
    
    function updateDependentElements($select) {
        var selectedValue = $select.val();
        var selectName = $select.attr('name');
        
        // Update price calculations for booking forms
        if (selectName === 'troom' || selectName === 'room_name') {
            if (typeof calculatePrice === 'function') {
                calculatePrice();
            }
            if (typeof updateRoomDetails === 'function') {
                updateRoomDetails(selectedValue);
            }
        }
        
        // Update meal options based on room selection
        if (selectName === 'troom' && selectedValue) {
            updateMealOptions(selectedValue);
        }
        
        // Update payment options based on selections
        if (selectName === 'payment_method') {
            updatePaymentInfo(selectedValue);
        }
    }
    
    function updateMealOptions(roomType) {
        var $mealSelect = $('select[name="meal"]');
        if ($mealSelect.length > 0) {
            // Add room-specific meal recommendations
            $mealSelect.siblings('.dropdown-help-text').remove();
            
            var helpText = '';
            if (roomType.toLowerCase().includes('suite') || roomType.toLowerCase().includes('deluxe')) {
                helpText = 'Complimentary breakfast included with premium rooms';
            } else {
                helpText = 'Choose your preferred meal plan';
            }
            
            $mealSelect.after('<div class="dropdown-help-text">' + helpText + '</div>');
        }
    }
    
    function updatePaymentInfo(paymentMethod) {
        var $paymentInfo = $('.payment-info');
        
        if (paymentMethod === 'mpesa') {
            showPaymentHelper('M-Pesa payment will redirect to mobile payment interface');
        } else if (paymentMethod === 'card') {
            showPaymentHelper('Secure card payment processing available');
        } else if (paymentMethod === 'cash') {
            showPaymentHelper('Cash payment accepted at check-in');
        }
    }
    
    function showPaymentHelper(message) {
        $('.payment-helper').remove();
        $('.payment-options').after(
            '<div class="dropdown-validation-message success payment-helper">' +
            '<i class="fa fa-info-circle"></i> ' + message +
            '</div>'
        );
    }
    
    function improveKeyboardNavigation() {
        $('select.form-control').on('keydown', function(e) {
            var $select = $(this);
            
            // Enhanced keyboard navigation
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $select.focus();
                
                // Show options (browser dependent)
                if ($select[0].showPicker) {
                    $select[0].showPicker();
                }
            }
        });
        
        // Add focus indicators
        $('select.form-control').on('focus', function() {
            $(this).addClass('keyboard-focused');
        }).on('blur', function() {
            $(this).removeClass('keyboard-focused');
        });
    }
    
    // Public API for external use
    window.DropdownEnhancements = {
        refresh: function(selector) {
            if (selector) {
                $(selector).find('select.form-control').each(function() {
                    handleSelectionChange($(this));
                });
            } else {
                enhanceSelectElements();
            }
        },
        
        validateSelection: function($select, isRequired) {
            var value = $select.val();
            var isValid = !isRequired || (value && value !== '');
            
            $select.siblings('.dropdown-validation-message').remove();
            
            if (isRequired && !isValid) {
                $select.after(
                    '<div class="dropdown-validation-message error">' +
                    '<i class="fa fa-exclamation-triangle"></i> Please make a selection' +
                    '</div>'
                );
                $select.addClass('is-invalid');
            } else {
                $select.removeClass('is-invalid');
                if (value && value !== '') {
                    $select.after(
                        '<div class="dropdown-validation-message success">' +
                        '<i class="fa fa-check"></i> Selection confirmed' +
                        '</div>'
                    );
                }
            }
            
            return isValid;
        },
        
        setSelection: function($select, value, triggerChange) {
            $select.val(value);
            if (triggerChange !== false) {
                $select.trigger('change');
            }
            handleSelectionChange($select);
        }
    };
    
    // Force all dropdowns to refresh and display selected values
    function forceDropdownRefresh() {
        $('select.form-control').each(function() {
            var $select = $(this);
            var currentValue = $select.val();
            
            if (currentValue && currentValue !== '') {
                // Force the browser to refresh the display
                $select.css({
                    'color': '#333333',
                    'background-color': '#ffffff',
                    '-webkit-text-fill-color': '#333333'
                });
                
                // Trigger change to update visual indicators
                handleSelectionChange($select);
                
                // Force repaint
                $select.hide().show(0);
            }
        });
    }
    
})(jQuery);

// Initialize for forms without jQuery
document.addEventListener('DOMContentLoaded', function() {
    // Fallback for non-jQuery environments
    if (typeof jQuery === 'undefined') {
        var selects = document.querySelectorAll('select.form-control');
        
        selects.forEach(function(select) {
            select.addEventListener('change', function() {
                // Add basic visual feedback
                if (this.value && this.value !== '') {
                    this.style.backgroundColor = '#f8fff9';
                    this.style.borderColor = '#28a745';
                } else {
                    this.style.backgroundColor = '';
                    this.style.borderColor = '';
                }
            });
        });
    }
});
