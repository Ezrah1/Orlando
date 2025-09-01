/**
 * Enhanced Menu Style Notification System
 * Provides consistent notifications across all guest pages
 * Based on the notification system from menu_enhanced.php
 */

// Add CSS styles for notifications
document.addEventListener('DOMContentLoaded', function() {
    if (!document.getElementById('enhanced-notification-styles')) {
        const style = document.createElement('style');
        style.id = 'enhanced-notification-styles';
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            .notification-alert {
                animation: slideInRight 0.3s ease-out !important;
            }
            
            .notification-alert:hover {
                cursor: pointer;
                opacity: 0.9;
            }
            
            @media (max-width: 768px) {
                .notification-alert {
                    top: 80px !important;
                    right: 10px !important;
                    left: 10px !important;
                    min-width: auto !important;
                    max-width: none !important;
                }
            }
        `;
        document.head.appendChild(style);
    }
});

// Enhanced Menu Style Notification System - Global Implementation
window.showNotification = function(message, type = 'success') {
    // Ensure jQuery is available
    if (typeof $ === 'undefined') {
        console.error('jQuery is required for notifications');
        return;
    }
    
    // Remove any existing notifications first
    $('.notification-alert').remove();
    
    let alertClass, icon;
    switch(type) {
        case 'success':
            alertClass = 'alert-success';
            icon = 'fa-check-circle';
            break;
        case 'error':
        case 'danger':
            alertClass = 'alert-danger';
            icon = 'fa-exclamation-circle';
            break;
        case 'warning':
            alertClass = 'alert-warning';
            icon = 'fa-exclamation-triangle';
            break;
        case 'info':
            alertClass = 'alert-info';
            icon = 'fa-info-circle';
            break;
        default:
            alertClass = 'alert-info';
            icon = 'fa-info-circle';
    }
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show notification-alert" role="alert" style="position: fixed; top: 100px; right: 20px; z-index: 9999; min-width: 300px; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.15);">
            <i class="fa ${icon}"></i> ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    $('body').append(alertHtml);
    
    // Auto-remove after 4 seconds
    setTimeout(function() {
        $('.notification-alert').fadeOut(function() {
            $(this).remove();
        });
    }, 4000);
    
    // Add click-to-dismiss functionality
    $('.notification-alert').click(function() {
        $(this).fadeOut(function() {
            $(this).remove();
        });
    });
};

// Alternative function names for compatibility
window.showSuccessNotification = function(message) {
    window.showNotification(message, 'success');
};

window.showErrorNotification = function(message) {
    window.showNotification(message, 'error');
};

// For pages that don't have the showNotification function defined
if (typeof showNotification === 'undefined') {
    window.showNotification = window.showNotification;
}
