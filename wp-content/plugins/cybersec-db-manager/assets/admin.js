/**
 * CyberSec Database Manager Admin JavaScript
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initDatabaseManager();
    });

    function initDatabaseManager() {
        // Add click handlers for all action buttons
        bindActionHandlers();
        
        // Initialize tooltips
        initTooltips();
        
        // Auto-refresh status every 30 seconds
        setInterval(refreshStatus, 30000);
    }

    function bindActionHandlers() {
        // Build Database
        $('#build-database').on('click', function(e) {
            e.preventDefault();
            handleDatabaseAction('build_database', $(this), $('#build-status'));
        });

        // Remove Database
        $('#remove-database').on('click', function(e) {
            e.preventDefault();
            handleDatabaseAction('remove_database', $(this), $('#remove-status'));
        });

        // Export Database
        $('#export-database').on('click', function(e) {
            e.preventDefault();
            handleDatabaseAction('export_database', $(this), $('#export-status'));
        });

        // Fix Database
        $('#fix-database').on('click', function(e) {
            e.preventDefault();
            handleDatabaseAction('fix_database', $(this), $('#fix-status'));
        });

        // Import Database
        $('#import-database').on('click', function(e) {
            e.preventDefault();
            handleImportDatabase();
        });
    }

    function handleDatabaseAction(actionType, $button, $status) {
        // Get confirmation message
        var confirmMessage = getConfirmMessage(actionType);
        
        if (!confirm(confirmMessage)) {
            return;
        }

        // Disable button and show loading state
        setButtonLoading($button, true);
        showStatusLoading($status);

        // Make AJAX request
        $.ajax({
            url: cybersecDbAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'cybersec_db_action',
                action_type: actionType,
                nonce: cybersecDbAjax.nonce
            },
            timeout: 300000, // 5 minutes timeout
            success: function(response) {
                handleActionResponse(response, $button, $status, actionType);
            },
            error: function(xhr, status, error) {
                handleActionError(error, $button, $status);
            },
            complete: function() {
                setButtonLoading($button, false);
            }
        });
    }

    function getConfirmMessage(actionType) {
        var messages = {
            'build_database': cybersecDbAjax.strings.confirm_build,
            'remove_database': cybersecDbAjax.strings.confirm_remove,
            'export_database': cybersecDbAjax.strings.confirm_export,
            'fix_database': cybersecDbAjax.strings.confirm_fix,
            'import_database': 'Are you sure you want to import this database? A backup will be created automatically before import.'
        };
        
        return messages[actionType] || 'Are you sure?';
    }

    function handleImportDatabase() {
        var $fileInput = $('#import-file');
        var $button = $('#import-database');
        var $status = $('#import-status');
        
        // Check if file is selected
        if ($fileInput[0].files.length === 0) {
            showStatusError($status, 'Please select a SQL file to import.');
            return;
        }
        
        var file = $fileInput[0].files[0];
        
        // Validate file type
        if (!file.name.toLowerCase().endsWith('.sql')) {
            showStatusError($status, 'Please select a valid SQL file (.sql extension).');
            return;
        }
        
        // Validate file size (50MB limit)
        if (file.size > 50 * 1024 * 1024) {
            showStatusError($status, 'File too large. Maximum size is 50MB.');
            return;
        }
        
        // Get confirmation
        var confirmMessage = getConfirmMessage('import_database');
        if (!confirm(confirmMessage)) {
            return;
        }
        
        // Create FormData for file upload
        var formData = new FormData();
        formData.append('action', 'cybersec_db_action');
        formData.append('action_type', 'import_database');
        formData.append('nonce', cybersecDbAjax.nonce);
        formData.append('import_file', file);
        
        // Set loading state
        setButtonLoading($button, true);
        showStatusLoading($status);
        
        // Make AJAX request
        $.ajax({
            url: cybersecDbAjax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 300000, // 5 minutes timeout
            success: function(response) {
                handleImportResponse(response, $status);
            },
            error: function(xhr, status, error) {
                handleActionError(error, $button, $status);
            },
            complete: function() {
                setButtonLoading($button, false);
                $fileInput.val(''); // Clear file input
            }
        });
    }

    function handleImportResponse(response, $status) {
        if (response.success) {
            showStatusSuccess($status, response.message);
            
            // Show imported tables if available
            if (response.imported_tables && response.imported_tables.length > 0) {
                var tablesHtml = '<div class="imported-tables">';
                tablesHtml += '<h4>Imported Tables:</h4>';
                tablesHtml += '<ul>';
                response.imported_tables.forEach(function(table) {
                    tablesHtml += '<li>' + escapeHtml(table) + '</li>';
                });
                tablesHtml += '</ul></div>';
                $status.append(tablesHtml);
            }
            
            // Reload page after successful import
            setTimeout(function() {
                window.location.reload();
            }, 3000);
        } else {
            showStatusError($status, response.message);
            
            // Show detailed errors if available
            if (response.errors && response.errors.length > 0) {
                showDetailedErrors($status, response.errors);
            }
        }
    }

    function handleActionResponse(response, $button, $status, actionType) {
        if (response.success) {
            showStatusSuccess($status, response.message);
            
            // Handle special cases
            if (actionType === 'export_database' && response.download_url) {
                showDownloadLink($status, response.download_url);
            }
            
            // Reload page for build/remove/fix actions
            if (['build_database', 'remove_database', 'fix_database'].includes(actionType)) {
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            }
        } else {
            showStatusError($status, response.message);
            
            // Show detailed errors if available
            if (response.errors && response.errors.length > 0) {
                showDetailedErrors($status, response.errors);
            }
        }
    }

    function handleActionError(error, $button, $status) {
        var errorMessage = 'An unexpected error occurred: ' + error;
        showStatusError($status, errorMessage);
    }

    function setButtonLoading($button, isLoading) {
        if (isLoading) {
            $button.data('original-text', $button.html());
            $button.prop('disabled', true);
            $button.html('<span class="spinner is-active"></span> ' + cybersecDbAjax.strings.processing);
        } else {
            $button.prop('disabled', false);
            $button.html($button.data('original-text'));
        }
    }

    function showStatusLoading($status) {
        $status.html('<div class="spinner is-active"></div> ' + cybersecDbAjax.strings.processing);
    }

    function showStatusSuccess($status, message) {
        $status.html('<div class="notice notice-success"><p>' + escapeHtml(message) + '</p></div>');
    }

    function showStatusError($status, message) {
        $status.html('<div class="notice notice-error"><p>' + escapeHtml(message) + '</p></div>');
    }

    function showDownloadLink($status, downloadUrl) {
        var downloadHtml = '<p><a href="' + escapeHtml(downloadUrl) + '" class="button button-primary" download>Download Export File</a></p>';
        $status.append(downloadHtml);
    }

    function showDetailedErrors($status, errors) {
        var errorList = '<ul>';
        errors.forEach(function(error) {
            errorList += '<li>' + escapeHtml(error) + '</li>';
        });
        errorList += '</ul>';
        
        $status.append('<div class="notice notice-error"><p><strong>Detailed Errors:</strong></p>' + errorList + '</div>');
    }

    function initTooltips() {
        // Add tooltips to action cards
        $('.action-card').each(function() {
            var $card = $(this);
            var $button = $card.find('.button');
            
            $button.on('mouseenter', function() {
                showTooltip($(this), $card.find('p').text());
            });
            
            $button.on('mouseleave', function() {
                hideTooltip();
            });
        });
    }

    function showTooltip($element, text) {
        var tooltip = $('<div class="cybersec-tooltip">' + text + '</div>');
        $('body').append(tooltip);
        
        var offset = $element.offset();
        tooltip.css({
            position: 'absolute',
            top: offset.top - tooltip.outerHeight() - 10,
            left: offset.left + ($element.outerWidth() / 2) - (tooltip.outerWidth() / 2),
            zIndex: 9999
        });
    }

    function hideTooltip() {
        $('.cybersec-tooltip').remove();
    }

    function refreshStatus() {
        // Only refresh if no operations are in progress
        if ($('.button:disabled').length === 0) {
            // Could implement AJAX status refresh here
            console.log('Status refresh - no active operations');
        }
    }

    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) {
            return map[m];
        });
    }

    // Utility functions for other parts of the admin
    window.CyberSecDBManager = {
        showNotification: function(message, type) {
            type = type || 'info';
            var notification = $('<div class="notice notice-' + type + '"><p>' + escapeHtml(message) + '</p></div>');
            $('.cybersec-db-manager').prepend(notification);
            
            setTimeout(function() {
                notification.fadeOut(function() {
                    notification.remove();
                });
            }, 5000);
        },
        
        confirmAction: function(message, callback) {
            if (confirm(message)) {
                callback();
            }
        },
        
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        formatDate: function(dateString) {
            var date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        }
    };

})(jQuery);

// Add CSS for tooltips
jQuery(document).ready(function($) {
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .cybersec-tooltip {
                background: #333;
                color: #fff;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 12px;
                max-width: 300px;
                word-wrap: break-word;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            }
            
            .cybersec-tooltip::after {
                content: '';
                position: absolute;
                top: 100%;
                left: 50%;
                margin-left: -5px;
                border-width: 5px;
                border-style: solid;
                border-color: #333 transparent transparent transparent;
            }
        `)
        .appendTo('head');
});