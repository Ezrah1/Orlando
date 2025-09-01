<?php
/**
 * Reusable Form Components
 * Common form elements and validation
 */

// Include guard to prevent multiple inclusions
if (defined('FORMS_COMPONENTS_INCLUDED')) {
    return;
}
define('FORMS_COMPONENTS_INCLUDED', true);

/**
 * Render form input field
 */
if (!function_exists('render_input_field')) {
function render_input_field($name, $label, $type = 'text', $value = '', $attributes = [], $required = false) {
    $required_attr = $required ? 'required' : '';
    $required_label = $required ? '<span class="text-danger">*</span>' : '';
    
    $attr_string = '';
    foreach ($attributes as $key => $val) {
        $attr_string .= " {$key}=\"{$val}\"";
    }
    
    $value = htmlspecialchars($value);
    
    return "
    <div class=\"form-group\">
        <label for=\"{$name}\">{$label} {$required_label}</label>
        <input type=\"{$type}\" class=\"form-control\" id=\"{$name}\" name=\"{$name}\" value=\"{$value}\" {$required_attr} {$attr_string}>
    </div>";
}
}

/**
 * Render textarea field
 */
if (!function_exists('render_textarea_field')) {
function render_textarea_field($name, $label, $value = '', $rows = 3, $attributes = [], $required = false) {
    $required_attr = $required ? 'required' : '';
    $required_label = $required ? '<span class="text-danger">*</span>' : '';
    
    $attr_string = '';
    foreach ($attributes as $key => $val) {
        $attr_string .= " {$key}=\"{$val}\"";
    }
    
    $value = htmlspecialchars($value);
    
    return "
    <div class=\"form-group\">
        <label for=\"{$name}\">{$label} {$required_label}</label>
        <textarea class=\"form-control\" id=\"{$name}\" name=\"{$name}\" rows=\"{$rows}\" {$required_attr} {$attr_string}>{$value}</textarea>
    </div>";
}
}

/**
 * Render select dropdown
 */
if (!function_exists('render_select_field')) {
function render_select_field($name, $label, $options, $selected = '', $attributes = [], $required = false) {
    $required_attr = $required ? 'required' : '';
    $required_label = $required ? '<span class="text-danger">*</span>' : '';
    
    $attr_string = '';
    foreach ($attributes as $key => $val) {
        $attr_string .= " {$key}=\"{$val}\"";
    }
    
    $html = "
    <div class=\"form-group\">
        <label for=\"{$name}\">{$label} {$required_label}</label>
        <select class=\"form-control\" id=\"{$name}\" name=\"{$name}\" {$required_attr} {$attr_string}>";
    
    if (!$required) {
        $html .= "<option value=\"\">Select {$label}</option>";
    }
    
    foreach ($options as $value => $text) {
        $selected_attr = ($value == $selected) ? 'selected' : '';
        $html .= "<option value=\"{$value}\" {$selected_attr}>{$text}</option>";
    }
    
    $html .= "
        </select>
    </div>";
    
    return $html;
}
}

/**
 * Render checkbox field
 */
if (!function_exists('render_checkbox_field')) {
function render_checkbox_field($name, $label, $value = '1', $checked = false, $attributes = []) {
    $checked_attr = $checked ? 'checked' : '';
    
    $attr_string = '';
    foreach ($attributes as $key => $val) {
        $attr_string .= " {$key}=\"{$val}\"";
    }
    
    return "
    <div class=\"form-check\">
        <input class=\"form-check-input\" type=\"checkbox\" id=\"{$name}\" name=\"{$name}\" value=\"{$value}\" {$checked_attr} {$attr_string}>
        <label class=\"form-check-label\" for=\"{$name}\">
            {$label}
        </label>
    </div>";
}
}

/**
 * Render radio button group
 */
function render_radio_group($name, $label, $options, $selected = '', $required = false) {
    $required_label = $required ? '<span class="text-danger">*</span>' : '';
    
    $html = "
    <div class=\"form-group\">
        <label>{$label} {$required_label}</label>";
    
    foreach ($options as $value => $text) {
        $checked_attr = ($value == $selected) ? 'checked' : '';
        $required_attr = $required ? 'required' : '';
        
        $html .= "
        <div class=\"form-check\">
            <input class=\"form-check-input\" type=\"radio\" id=\"{$name}_{$value}\" name=\"{$name}\" value=\"{$value}\" {$checked_attr} {$required_attr}>
            <label class=\"form-check-label\" for=\"{$name}_{$value}\">
                {$text}
            </label>
        </div>";
    }
    
    $html .= "</div>";
    
    return $html;
}

/**
 * Render file upload field
 */
function render_file_field($name, $label, $accept = '', $multiple = false, $required = false) {
    $required_attr = $required ? 'required' : '';
    $required_label = $required ? '<span class="text-danger">*</span>' : '';
    $multiple_attr = $multiple ? 'multiple' : '';
    $accept_attr = $accept ? "accept=\"{$accept}\"" : '';
    
    return "
    <div class=\"form-group\">
        <label for=\"{$name}\">{$label} {$required_label}</label>
        <input type=\"file\" class=\"form-control-file\" id=\"{$name}\" name=\"{$name}\" {$accept_attr} {$multiple_attr} {$required_attr}>
    </div>";
}

/**
 * Render date picker field
 */
function render_date_field($name, $label, $value = '', $min_date = '', $max_date = '', $required = false) {
    $required_attr = $required ? 'required' : '';
    $required_label = $required ? '<span class="text-danger">*</span>' : '';
    $min_attr = $min_date ? "min=\"{$min_date}\"" : '';
    $max_attr = $max_date ? "max=\"{$max_date}\"" : '';
    
    $value = htmlspecialchars($value);
    
    return "
    <div class=\"form-group\">
        <label for=\"{$name}\">{$label} {$required_label}</label>
        <input type=\"date\" class=\"form-control\" id=\"{$name}\" name=\"{$name}\" value=\"{$value}\" {$min_attr} {$max_attr} {$required_attr}>
    </div>";
}

/**
 * Render form buttons
 */
function render_form_buttons($submit_text = 'Submit', $cancel_url = '', $additional_buttons = []) {
    $html = '<div class="form-group">';
    
    // Submit button
    $html .= "<button type=\"submit\" class=\"btn btn-primary\">{$submit_text}</button>";
    
    // Cancel button
    if ($cancel_url) {
        $html .= " <a href=\"{$cancel_url}\" class=\"btn btn-secondary\">Cancel</a>";
    }
    
    // Additional buttons
    foreach ($additional_buttons as $button) {
        $type = $button['type'] ?? 'button';
        $class = $button['class'] ?? 'btn btn-outline-secondary';
        $text = $button['text'] ?? 'Button';
        $attributes = $button['attributes'] ?? '';
        
        $html .= " <button type=\"{$type}\" class=\"{$class}\" {$attributes}>{$text}</button>";
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render search form
 */
function render_search_form($action = '', $placeholder = 'Search...', $current_search = '') {
    $current_search = htmlspecialchars($current_search);
    
    return "
    <form method=\"GET\" action=\"{$action}\" class=\"search-form mb-3\">
        <div class=\"input-group\">
            <input type=\"text\" class=\"form-control\" name=\"search\" value=\"{$current_search}\" placeholder=\"{$placeholder}\">
            <div class=\"input-group-append\">
                <button class=\"btn btn-outline-secondary\" type=\"submit\">
                    <i class=\"fas fa-search\"></i>
                </button>
            </div>
        </div>
    </form>";
}

/**
 * Render filter form
 */
function render_filter_form($filters, $current_filters = []) {
    $html = '<form method="GET" class="filter-form mb-3"><div class="row">';
    
    foreach ($filters as $filter) {
        $name = $filter['name'];
        $label = $filter['label'];
        $type = $filter['type'];
        $options = $filter['options'] ?? [];
        $current_value = $current_filters[$name] ?? '';
        
        $html .= '<div class="col-md-3 mb-2">';
        
        if ($type === 'select') {
            $html .= render_select_field($name, $label, $options, $current_value);
        } elseif ($type === 'date') {
            $html .= render_date_field($name, $label, $current_value);
        } else {
            $html .= render_input_field($name, $label, $type, $current_value);
        }
        
        $html .= '</div>';
    }
    
    $html .= '<div class="col-md-3 mb-2">';
    $html .= '<label>&nbsp;</label><div>';
    $html .= '<button type="submit" class="btn btn-primary">Filter</button> ';
    $html .= '<a href="?" class="btn btn-outline-secondary">Clear</a>';
    $html .= '</div></div>';
    
    $html .= '</div></form>';
    
    return $html;
}

/**
 * Render pagination
 */
function render_pagination($current_page, $total_pages, $base_url, $query_params = []) {
    if ($total_pages <= 1) return '';
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Build query string
    $query_string = '';
    if (!empty($query_params)) {
        $query_string = '&' . http_build_query($query_params);
    }
    
    // Previous button
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$base_url}?page={$prev_page}{$query_string}\">Previous</a></li>";
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
    }
    
    // Page numbers
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    if ($start_page > 1) {
        $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$base_url}?page=1{$query_string}\">1</a></li>";
        if ($start_page > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $current_page) {
            $html .= "<li class=\"page-item active\"><span class=\"page-link\">{$i}</span></li>";
        } else {
            $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$base_url}?page={$i}{$query_string}\">{$i}</a></li>";
        }
    }
    
    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$base_url}?page={$total_pages}{$query_string}\">{$total_pages}</a></li>";
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$base_url}?page={$next_page}{$query_string}\">Next</a></li>";
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}
?>
