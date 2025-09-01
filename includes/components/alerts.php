<?php
/**
 * Reusable Alert Components
 * Display various types of alert messages
 */

/**
 * Display session-based alerts
 */
if (!function_exists('display_session_alerts')) {
function display_session_alerts() {
    $alerts = [
        'success_message' => 'success',
        'error_message' => 'danger', 
        'warning_message' => 'warning',
        'info_message' => 'info'
    ];
    
    foreach ($alerts as $session_key => $alert_type) {
        if (isset($_SESSION[$session_key])) {
            echo render_alert($_SESSION[$session_key], $alert_type);
            unset($_SESSION[$session_key]);
        }
    }
}
}

/**
 * Render a single alert
 */
if (!function_exists('render_alert')) {
function render_alert($message, $type = 'info', $dismissible = true) {
    $icons = [
        'success' => '✓',
        'danger' => '✗',
        'warning' => '⚠',
        'info' => 'ℹ'
    ];
    
    $icon = $icons[$type] ?? 'ℹ';
    $dismissible_class = $dismissible ? 'alert-dismissible' : '';
    $dismiss_button = $dismissible ? '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' : '';
    
    return "
    <div class=\"alert alert-{$type} {$dismissible_class} fade show\" role=\"alert\">
        {$dismiss_button}
        <strong>{$icon}</strong> " . htmlspecialchars($message) . "
    </div>";
}
}

/**
 * Display validation errors
 */
if (!function_exists('display_validation_errors')) {
function display_validation_errors($errors) {
    if (empty($errors)) return '';
    
    $html = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    $html .= '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
    $html .= '<strong>✗ Please correct the following errors:</strong><ul class="mb-0 mt-2">';
    
    foreach ($errors as $error) {
        $html .= '<li>' . htmlspecialchars($error) . '</li>';
    }
    
    $html .= '</ul></div>';
    
    return $html;
}
}

/**
 * Display success confirmation
 */
if (!function_exists('display_success_confirmation')) {
function display_success_confirmation($title, $message, $redirect_url = null, $redirect_text = 'Continue') {
    $redirect_button = $redirect_url ? 
        "<a href=\"{$redirect_url}\" class=\"btn btn-success mt-3\">{$redirect_text}</a>" : '';
    
    return "
    <div class=\"alert alert-success text-center\" role=\"alert\">
        <div class=\"mb-3\">
            <i class=\"fas fa-check-circle fa-3x text-success\"></i>
        </div>
        <h4 class=\"alert-heading\">{$title}</h4>
        <p class=\"mb-0\">{$message}</p>
        {$redirect_button}
    </div>";
}
}

/**
 * Display loading spinner
 */
if (!function_exists('display_loading_spinner')) {
function display_loading_spinner($message = 'Loading...') {
    return "
    <div class=\"text-center p-4\">
        <div class=\"spinner-border text-primary\" role=\"status\">
            <span class=\"sr-only\">{$message}</span>
        </div>
        <p class=\"mt-2 text-muted\">{$message}</p>
    </div>";
}
}

// Auto-display session alerts if this file is included directly
if (basename($_SERVER['PHP_SELF']) !== basename(__FILE__)) {
    if (function_exists('display_session_alerts')) {
        display_session_alerts();
    }
}
?>
