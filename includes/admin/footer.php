<?php
/**
 * Admin Footer - For admin dashboard pages
 * Professional footer with scripts and utilities
 */

// Ensure config is loaded
if (!defined('APP_ROOT')) {
    require_once __DIR__ . '/../common/config.php';
}
?>
        </div> <!-- End content-wrapper -->
    </div> <!-- End main-content -->

    <!-- Admin Footer -->
    <footer class="admin-footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <p class="footer-text">
                        © <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Admin Dashboard
                    </p>
                </div>
                <div class="col-md-6 text-right">
                    <div class="footer-links">
                        <a href="help_center.php" class="footer-link">
                            <i class="fas fa-question-circle"></i> Help
                        </a>
                        <a href="user_preferences.php" class="footer-link">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <a href="<?php echo $GLOBALS['path_prefix']; ?>index.php" target="_blank" class="footer-link">
                            <i class="fas fa-external-link-alt"></i> View Site
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <!-- jQuery and Bootstrap are already loaded in the header -->
    
    <!-- PWA and Mobile Scripts -->
    <script src="js/pwa-manager.js"></script>
    <script src="js/mobile-navigation.js"></script>
    <script src="js/cross-device-sync.js"></script>
    
    <!-- Advanced UI Components -->
    <script src="js/theme-manager.js"></script>
    <script src="js/interactive-components.js"></script>
    
    <!-- Admin JavaScript -->
    <script>
        $(document).ready(function() {
            // Sidebar toggle for mobile
            $('#sidebarToggle').on('click', function() {
                $('#sidebar').toggleClass('show');
            });

            // Auto-hide sidebar on mobile when clicking outside
            $(document).on('click', function(e) {
                if ($(window).width() <= 768) {
                    if (!$(e.target).closest('#sidebar, #sidebarToggle').length) {
                        $('#sidebar').removeClass('show');
                    }
                }
            });

            // Show loading spinner on form submissions
            $('form:not([data-no-loading])').on('submit', function() {
                showLoading();
            });

            // Show loading spinner on AJAX requests
            $(document).ajaxStart(function() {
                showLoading();
            });

            $(document).ajaxStop(function() {
                hideLoading();
            });

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);

            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Initialize popovers
            $('[data-toggle="popover"]').popover();

            // Smooth scrolling for anchor links
            $('a[href^="#"]').on('click', function(event) {
                var target = $(this.getAttribute('href'));
                if (target.length) {
                    event.preventDefault();
                    $('html, body').stop().animate({
                        scrollTop: target.offset().top - 100
                    }, 1000);
                }
            });

            // Confirm delete actions
            $('.btn-delete, .delete-btn, [data-action="delete"]').on('click', function(e) {
                if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });

            // Auto-save forms (optional)
            $('form[data-autosave]').on('input', function() {
                var formData = $(this).serialize();
                var formId = $(this).attr('id');
                if (formId) {
                    localStorage.setItem('autosave_' + formId, formData);
                }
            });

            // Restore auto-saved forms
            $('form[data-autosave]').each(function() {
                var formId = $(this).attr('id');
                if (formId) {
                    var savedData = localStorage.getItem('autosave_' + formId);
                    if (savedData) {
                        restoreFormData($(this), savedData);
                    }
                }
            });

            // Clear auto-save on successful form submission
            $('form[data-autosave]').on('submit', function() {
                var formId = $(this).attr('id');
                if (formId) {
                    localStorage.removeItem('autosave_' + formId);
                }
            });

            // Keyboard shortcuts
            $(document).keydown(function(e) {
                // Ctrl/Cmd + S to save
                if ((e.ctrlKey || e.metaKey) && e.keyCode === 83) {
                    e.preventDefault();
                    $('form:visible button[type="submit"], form:visible input[type="submit"]').first().click();
                }
                
                // Ctrl/Cmd + N for new item
                if ((e.ctrlKey || e.metaKey) && e.keyCode === 78) {
                    e.preventDefault();
                    $('.btn-add-new:visible, .add-new-btn:visible').first().click();
                }
                
                // Escape to close modals
                if (e.keyCode === 27) {
                    $('.modal').modal('hide');
                    $('.dropdown-menu').removeClass('show');
                }
            });

            // Table row selection
            $('.table-selectable tbody tr').on('click', function() {
                $(this).toggleClass('selected');
            });

            // Bulk actions
            $('#selectAll').on('change', function() {
                $('.table-selectable tbody input[type="checkbox"]').prop('checked', this.checked);
                $('.table-selectable tbody tr').toggleClass('selected', this.checked);
            });

            // Search functionality
            $('#searchInput, .search-input').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                var targetTable = $(this).data('target') || '.searchable-table';
                $(targetTable + ' tbody tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });

            // Export functionality
            $('.btn-export').on('click', function(e) {
                e.preventDefault();
                var format = $(this).data('format');
                var table = $(this).closest('.card').find('table');
                
                if (format === 'csv') {
                    exportTableToCSV(table[0]);
                } else if (format === 'excel') {
                    showAlert('Excel export functionality would be implemented here', 'info');
                } else if (format === 'pdf') {
                    showAlert('PDF export functionality would be implemented here', 'info');
                }
            });

            // Print functionality
            $('.btn-print').on('click', function(e) {
                e.preventDefault();
                window.print();
            });

            // Status toggle functionality
            $('.status-toggle').on('change', function() {
                var $this = $(this);
                var id = $this.data('id');
                var status = $this.is(':checked') ? 'active' : 'inactive';
                var endpoint = $this.data('endpoint') || 'toggle_status.php';
                
                $.post(endpoint, {
                    id: id,
                    status: status
                }, function(response) {
                    if (response.success) {
                        showAlert('Status updated successfully', 'success');
                    } else {
                        showAlert('Failed to update status', 'error');
                        $this.prop('checked', !$this.is(':checked'));
                    }
                }, 'json').fail(function() {
                    showAlert('Network error occurred', 'error');
                    $this.prop('checked', !$this.is(':checked'));
                });
            });

            // Real-time clock
            updateClock();
            setInterval(updateClock, 1000);

            // Performance monitoring
            var startTime = performance.now();
            $(window).on('load', function() {
                var loadTime = performance.now() - startTime;
                console.log('Admin page load time: ' + loadTime.toFixed(2) + 'ms');
            });
        });

        // Utility functions
        function showLoading() {
            $('#loading').css('display', 'flex');
        }

        function hideLoading() {
            $('#loading').hide();
        }

        function showAlert(message, type = 'success') {
            var alertClass = 'alert-' + (type === 'error' ? 'danger' : type);
            var icon = type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ';
            
            var alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show notification-item" role="alert">
                    <strong>${icon}</strong> ${message}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            `;
            
            $('.admin-notifications').prepend(alertHtml);
            
            setTimeout(function() {
                $('.admin-notifications .alert').first().fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 5000);
        }

        function confirmAction(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }

        function validateForm(form) {
            var isValid = true;
            var requiredFields = form.find('[required]');
            
            requiredFields.each(function() {
                if (!$(this).val().trim()) {
                    $(this).addClass('is-invalid');
                    isValid = false;
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            
            return isValid;
        }

        function restoreFormData(form, serializedData) {
            var data = new URLSearchParams(serializedData);
            form.find('input, select, textarea').each(function() {
                var name = $(this).attr('name');
                if (name && data.has(name)) {
                    var value = data.get(name);
                    if ($(this).is(':checkbox') || $(this).is(':radio')) {
                        $(this).prop('checked', $(this).val() === value);
                    } else {
                        $(this).val(value);
                    }
                }
            });
        }

        function exportTableToCSV(table) {
            var csv = [];
            var rows = table.querySelectorAll('tr');
            
            for (var i = 0; i < rows.length; i++) {
                var row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (var j = 0; j < cols.length; j++) {
                    var text = cols[j].innerText.replace(/"/g, '""');
                    row.push('"' + text + '"');
                }
                
                csv.push(row.join(','));
            }
            
            downloadCSV(csv.join('\n'), 'export_' + new Date().toISOString().slice(0, 10) + '.csv');
        }

        function downloadCSV(csv, filename) {
            var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement('a');
            if (link.download !== undefined) {
                var url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }

        function updateClock() {
            var now = new Date();
            var timeString = now.toLocaleTimeString();
            var dateString = now.toLocaleDateString();
            $('#currentTime').text(timeString);
            $('#currentDate').text(dateString);
        }

        function formatCurrency(amount) {
            return 'KSh ' + parseFloat(amount).toLocaleString('en-KE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function formatDate(dateString) {
            var date = new Date(dateString);
            return date.toLocaleDateString('en-KE', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        function formatDateTime(dateTimeString) {
            var date = new Date(dateTimeString);
            return date.toLocaleDateString('en-KE', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // AJAX helper for common operations
        function ajaxRequest(url, data, successCallback, errorCallback) {
            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (successCallback) successCallback(response);
                },
                error: function(xhr, status, error) {
                    if (errorCallback) {
                        errorCallback(error);
                    } else {
                        showAlert('Network error: ' + error, 'error');
                    }
                }
            });
        }

        // Global variables for page-specific use
        window.AdminUtils = {
            showLoading: showLoading,
            hideLoading: hideLoading,
            showAlert: showAlert,
            confirmAction: confirmAction,
            validateForm: validateForm,
            exportTableToCSV: exportTableToCSV,
            formatCurrency: formatCurrency,
            formatDate: formatDate,
            formatDateTime: formatDateTime,
            ajaxRequest: ajaxRequest
        };
    </script>

    <!-- Additional Styles -->
    <style>
        .admin-footer {
            background: white;
            border-top: 1px solid #e9ecef;
            padding: 15px 0;
            margin-top: auto;
            font-size: 0.85rem;
        }

        .footer-text {
            color: #6c757d;
            margin: 0;
        }

        .footer-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .footer-link {
            color: #6c757d;
            text-decoration: none;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .footer-link:hover {
            color: var(--primary-color);
            text-decoration: none;
        }

        .footer-link i {
            font-size: 0.9rem;
        }

        /* Status indicators */
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #d1ecf1; color: #0c5460; }

        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        /* Responsive tables */
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }

        @media (max-width: 768px) {
            .footer-links {
                flex-direction: column;
                gap: 10px;
                margin-top: 10px;
            }

            .admin-footer .row {
                text-align: center;
            }

            .admin-footer .text-right {
                text-align: center !important;
            }
        }

        /* Print styles */
        @media print {
            .sidebar,
            .top-navbar,
            .admin-footer,
            .btn,
            .admin-notifications {
                display: none !important;
            }

            .main-content {
                margin-left: 0 !important;
            }

            .content-wrapper {
                padding: 0 !important;
            }

            .card {
                box-shadow: none !important;
                border: 1px solid #dee2e6 !important;
            }
        }
    </style>

    <!-- Essential JavaScript Libraries -->
    <!-- jQuery and Bootstrap are already loaded in the header to avoid conflicts -->
    
    <!-- Custom page scripts placeholder -->
    <?php if (isset($page_scripts)): ?>
        <?php echo $page_scripts; ?>
    <?php endif; ?>

    <!-- Development tools (remove in production) -->
    <?php if (defined('DEBUG') && DEBUG): ?>
    <script>
        console.log('Admin Dashboard Debug Mode Enabled');
        console.log('Current User:', <?php echo json_encode($current_user ?? null); ?>);
        console.log('Session Data:', <?php echo json_encode($_SESSION ?? []); ?>);
    </script>
    <?php endif; ?>

</body>
</html>
