<?php
/**
 * Reusable Dashboard Widget Components
 * Common dashboard elements and statistics cards
 */

// Include guard to prevent multiple inclusions
if (defined('DASHBOARD_WIDGETS_INCLUDED')) {
    return;
}
define('DASHBOARD_WIDGETS_INCLUDED', true);

/**
 * Render statistics card
 */
if (!function_exists('render_stats_card')) {
function render_stats_card($title, $value, $icon, $color = 'primary', $subtitle = '', $change = null) {
    $change_html = '';
    if ($change !== null) {
        $change_class = $change >= 0 ? 'text-success' : 'text-danger';
        $change_icon = $change >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
        $change_html = "
        <div class=\"stats-change {$change_class}\">
            <i class=\"fas {$change_icon}\"></i> " . abs($change) . "%
        </div>";
    }
    
    return "
    <div class=\"col-lg-3 col-md-6 mb-4\">
        <div class=\"card stats-card border-left-{$color}\">
            <div class=\"card-body\">
                <div class=\"row no-gutters align-items-center\">
                    <div class=\"col mr-2\">
                        <div class=\"stats-title text-{$color} font-weight-bold text-uppercase mb-1\">{$title}</div>
                        <div class=\"stats-value h5 mb-0 font-weight-bold text-gray-800\">{$value}</div>
                        {$subtitle}
                        {$change_html}
                    </div>
                    <div class=\"col-auto\">
                        <i class=\"fas {$icon} fa-2x text-gray-300\"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>";
}
}

/**
 * Render progress card
 */
if (!function_exists('render_progress_card')) {
function render_progress_card($title, $current, $total, $color = 'primary', $show_percentage = true) {
    $percentage = $total > 0 ? round(($current / $total) * 100, 1) : 0;
    $percentage_text = $show_percentage ? " ({$percentage}%)" : '';
    
    return "
    <div class=\"col-lg-6 col-md-6 mb-4\">
        <div class=\"card\">
            <div class=\"card-body\">
                <h6 class=\"card-title text-{$color}\">{$title}</h6>
                <div class=\"progress mb-2\" style=\"height: 20px;\">
                    <div class=\"progress-bar bg-{$color}\" role=\"progressbar\" style=\"width: {$percentage}%\" aria-valuenow=\"{$current}\" aria-valuemin=\"0\" aria-valuemax=\"{$total}\">
                        {$percentage}%
                    </div>
                </div>
                <div class=\"progress-text\">
                    <span class=\"font-weight-bold\">{$current}</span> of <span class=\"font-weight-bold\">{$total}</span>{$percentage_text}
                </div>
            </div>
        </div>
    </div>";
}

/**
 * Render chart container
 */
function render_chart_container($chart_id, $title, $height = '300px', $controls = '') {
    return "
    <div class=\"card mb-4\">
        <div class=\"card-header d-flex justify-content-between align-items-center\">
            <h6 class=\"m-0 font-weight-bold text-primary\">{$title}</h6>
            <div class=\"chart-controls\">
                {$controls}
            </div>
        </div>
        <div class=\"card-body\">
            <div class=\"chart-container\" style=\"position: relative; height: {$height};\">
                <canvas id=\"{$chart_id}\"></canvas>
            </div>
        </div>
    </div>";
}

/**
 * Render recent activity list
 */
function render_activity_list($activities, $title = 'Recent Activity', $show_more_url = '') {
    $show_more_link = $show_more_url ? 
        "<a href=\"{$show_more_url}\" class=\"btn btn-sm btn-outline-primary\">View All</a>" : '';
    
    $html = "
    <div class=\"card mb-4\">
        <div class=\"card-header d-flex justify-content-between align-items-center\">
            <h6 class=\"m-0 font-weight-bold text-primary\">{$title}</h6>
            {$show_more_link}
        </div>
        <div class=\"card-body p-0\">";
    
    if (empty($activities)) {
        $html .= '<div class="text-center p-4 text-muted">No recent activity</div>';
    } else {
        $html .= '<div class="list-group list-group-flush">';
        
        foreach ($activities as $activity) {
            $time_ago = time_ago($activity['created_at']);
            $icon = $activity['icon'] ?? 'fa-circle';
            $color = $activity['color'] ?? 'primary';
            
            $html .= "
            <div class=\"list-group-item list-group-item-action\">
                <div class=\"d-flex w-100 justify-content-between align-items-start\">
                    <div class=\"d-flex align-items-center\">
                        <i class=\"fas {$icon} text-{$color} mr-3\"></i>
                        <div>
                            <h6 class=\"mb-1\">{$activity['title']}</h6>
                            <p class=\"mb-1 text-muted\">{$activity['description']}</p>
                        </div>
                    </div>
                    <small class=\"text-muted\">{$time_ago}</small>
                </div>
            </div>";
        }
        
        $html .= '</div>';
    }
    
    $html .= '
        </div>
    </div>';
    
    return $html;
}

/**
 * Render data table with actions
 */
function render_data_table($headers, $data, $actions = [], $table_id = 'dataTable', $searchable = true) {
    $search_html = $searchable ? 
        '<div class="mb-3">
            <input type="text" class="form-control search-input" placeholder="Search..." data-target="#' . $table_id . '">
         </div>' : '';
    
    $html = "
    <div class=\"card mb-4\">
        <div class=\"card-body\">
            {$search_html}
            <div class=\"table-responsive\">
                <table class=\"table table-bordered\" id=\"{$table_id}\">
                    <thead>
                        <tr>";
    
    foreach ($headers as $header) {
        $html .= "<th>{$header}</th>";
    }
    
    if (!empty($actions)) {
        $html .= '<th>Actions</th>';
    }
    
    $html .= '
                        </tr>
                    </thead>
                    <tbody>';
    
    foreach ($data as $row) {
        $html .= '<tr>';
        
        foreach ($row as $cell) {
            $html .= "<td>{$cell}</td>";
        }
        
        if (!empty($actions)) {
            $html .= '<td class="action-buttons">';
            foreach ($actions as $action) {
                $html .= $action . ' ';
            }
            $html .= '</td>';
        }
        
        $html .= '</tr>';
    }
    
    $html .= '
                    </tbody>
                </table>
            </div>
        </div>
    </div>';
    
    return $html;
}

/**
 * Render quick action buttons
 */
function render_quick_actions($actions, $title = 'Quick Actions') {
    $html = "
    <div class=\"card mb-4\">
        <div class=\"card-header\">
            <h6 class=\"m-0 font-weight-bold text-primary\">{$title}</h6>
        </div>
        <div class=\"card-body\">
            <div class=\"row\">";
    
    foreach ($actions as $action) {
        $icon = $action['icon'] ?? 'fa-cog';
        $color = $action['color'] ?? 'primary';
        $url = $action['url'] ?? '#';
        $title = $action['title'] ?? 'Action';
        $description = $action['description'] ?? '';
        
        $html .= "
        <div class=\"col-md-6 mb-3\">
            <a href=\"{$url}\" class=\"btn btn-outline-{$color} btn-block text-left h-100\">
                <div class=\"d-flex align-items-center\">
                    <i class=\"fas {$icon} fa-2x mr-3\"></i>
                    <div>
                        <div class=\"font-weight-bold\">{$title}</div>
                        <small class=\"text-muted\">{$description}</small>
                    </div>
                </div>
            </a>
        </div>";
    }
    
    $html .= '
            </div>
        </div>
    </div>';
    
    return $html;
}

/**
 * Render notification list
 */
function render_notifications($notifications, $title = 'Notifications') {
    $html = "
    <div class=\"card mb-4\">
        <div class=\"card-header\">
            <h6 class=\"m-0 font-weight-bold text-primary\">{$title}</h6>
        </div>
        <div class=\"card-body p-0\">";
    
    if (empty($notifications)) {
        $html .= '<div class="text-center p-4 text-muted">No notifications</div>';
    } else {
        $html .= '<div class="list-group list-group-flush">';
        
        foreach ($notifications as $notification) {
            $type_class = $notification['type'] ?? 'info';
            $icon = $notification['icon'] ?? 'fa-bell';
            $is_read = $notification['is_read'] ?? false;
            $read_class = $is_read ? '' : 'font-weight-bold';
            
            $html .= "
            <div class=\"list-group-item\">
                <div class=\"d-flex align-items-start\">
                    <i class=\"fas {$icon} text-{$type_class} mr-3 mt-1\"></i>
                    <div class=\"flex-grow-1\">
                        <div class=\"{$read_class}\">{$notification['message']}</div>
                        <small class=\"text-muted\">" . time_ago($notification['created_at']) . "</small>
                    </div>
                </div>
            </div>";
        }
        
        $html .= '</div>';
    }
    
    $html .= '
        </div>
    </div>';
    
    return $html;
}

/**
 * Helper function to calculate time ago
 */
function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time / 60) . ' minutes ago';
    if ($time < 86400) return floor($time / 3600) . ' hours ago';
    if ($time < 2592000) return floor($time / 86400) . ' days ago';
    if ($time < 31536000) return floor($time / 2592000) . ' months ago';
    
    return floor($time / 31536000) . ' years ago';
}

/**
 * Render dashboard grid container
 */
function render_dashboard_grid($widgets, $columns = 12) {
    $html = '<div class="row">';
    
    foreach ($widgets as $widget) {
        $col_size = $widget['col_size'] ?? ($columns / count($widgets));
        $html .= "<div class=\"col-lg-{$col_size} col-md-{$col_size}\">";
        $html .= $widget['content'];
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Dashboard styles
 */
function render_dashboard_styles() {
    echo "
    <style>
    .stats-card {
        border-left: 4px solid;
        border-radius: 10px;
        transition: transform 0.2s ease;
    }
    
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .border-left-primary { border-left-color: #4e73df !important; }
    .border-left-success { border-left-color: #1cc88a !important; }
    .border-left-info { border-left-color: #36b9cc !important; }
    .border-left-warning { border-left-color: #f6c23e !important; }
    .border-left-danger { border-left-color: #e74a3b !important; }
    
    .stats-title {
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .stats-value {
        font-size: 1.5rem;
    }
    
    .stats-change {
        font-size: 0.8rem;
        margin-top: 5px;
    }
    
    .chart-container {
        position: relative;
    }
    
    .action-buttons .btn {
        margin-right: 5px;
        margin-bottom: 5px;
    }
    
    .search-input {
        max-width: 300px;
        margin-bottom: 15px;
    }
    
    @media (max-width: 768px) {
        .stats-card {
            margin-bottom: 20px;
        }
        
        .chart-container {
            height: 250px !important;
        }
    }
    </style>";
}
}
?>
