<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap cybersec-db-manager">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-database"></span>
        CyberSec Database Control
    </h1>
    
    <div class="cybersec-db-status">
        <h2>Database Status</h2>
        <div class="status-cards">
            <div class="status-card">
                <h3>Schema Version</h3>
                <p class="version"><?php echo esc_html($schema_version); ?></p>
            </div>
            <div class="status-card">
                <h3>Total Tables</h3>
                <p class="count"><?php echo esc_html($db_status['total_tables']); ?></p>
            </div>
            <div class="status-card">
                <h3>Existing Tables</h3>
                <p class="count success"><?php echo esc_html($db_status['existing_tables']); ?></p>
            </div>
            <div class="status-card">
                <h3>Missing Tables</h3>
                <p class="count <?php echo $db_status['missing_tables'] > 0 ? 'error' : 'success'; ?>">
                    <?php echo esc_html(count($db_status['missing_tables'])); ?>
                </p>
            </div>
            <div class="status-card">
                <h3>Total Records</h3>
                <p class="count"><?php echo esc_html(number_format($db_status['total_records'])); ?></p>
            </div>
            <div class="status-card">
                <h3>Last Backup</h3>
                <p class="backup"><?php echo $last_backup ? esc_html($last_backup) : 'None'; ?></p>
            </div>
        </div>
    </div>

    <?php if (!empty($db_status['missing_tables'])): ?>
    <div class="notice notice-warning">
        <p><strong>Warning:</strong> The following tables are missing from your database:</p>
        <ul>
            <?php foreach ($db_status['missing_tables'] as $table): ?>
                <li><code><?php echo esc_html($table); ?></code></li>
            <?php endforeach; ?>
        </ul>
        <p>Use the "Fix Database" button below to add missing tables and fields.</p>
    </div>
    <?php endif; ?>

    <div class="cybersec-db-actions">
        <h2>Database Actions</h2>
        <div class="action-buttons">
            
            <!-- Build/Rebuild Database -->
            <div class="action-card">
                <h3>Build/Rebuild Database</h3>
                <p>Create or rebuild the complete database schema with all tables, indexes, and sample data.</p>
                <button type="button" class="button button-primary button-large" id="build-database">
                    <span class="dashicons dashicons-hammer"></span>
                    Build Database
                </button>
                <div class="action-status" id="build-status"></div>
            </div>

            <!-- Remove Database -->
            <div class="action-card">
                <h3>Remove Database</h3>
                <p><strong>DANGER:</strong> This will permanently delete all CyberSec platform tables and data!</p>
                <button type="button" class="button button-secondary button-large" id="remove-database">
                    <span class="dashicons dashicons-trash"></span>
                    Remove Database
                </button>
                <div class="action-status" id="remove-status"></div>
            </div>

            <!-- Export Database -->
            <div class="action-card">
                <h3>Export Database</h3>
                <p>Export all CyberSec platform data to a SQL file for backup or migration purposes.</p>
                <button type="button" class="button button-secondary button-large" id="export-database">
                    <span class="dashicons dashicons-download"></span>
                    Export Database
                </button>
                <div class="action-status" id="export-status"></div>
            </div>

            <!-- Fix Database -->
            <div class="action-card">
                <h3>Fix Database</h3>
                <p>Add missing tables and fields without affecting existing data. Safe to run multiple times.</p>
                <button type="button" class="button button-secondary button-large" id="fix-database">
                    <span class="dashicons dashicons-admin-tools"></span>
                    Fix Database
                </button>
                <div class="action-status" id="fix-status"></div>
            </div>

        </div>
    </div>

    <div class="cybersec-db-info">
        <h2>Database Information</h2>
        <div class="info-grid">
            <div class="info-section">
                <h3>Core Tables</h3>
                <ul>
                    <li>users, user_profiles, user_stats</li>
                    <li>teams, team_members, team_stats</li>
                    <li>competitions, competition_participants</li>
                    <li>challenges, challenge_categories, challenge_submissions</li>
                </ul>
            </div>
            <div class="info-section">
                <h3>Learning System</h3>
                <ul>
                    <li>learning_paths, learning_modules</li>
                    <li>user_learning_progress</li>
                    <li>badges, user_badges</li>
                </ul>
            </div>
            <div class="info-section">
                <h3>Moderation & Security</h3>
                <ul>
                    <li>user_classifications, user_moderation</li>
                    <li>user_activity_logs, user_reputation</li>
                    <li>security_events, api_usage_logs</li>
                    <li>content_moderation</li>
                </ul>
            </div>
            <div class="info-section">
                <h3>System Tables</h3>
                <ul>
                    <li>leaderboards, notifications</li>
                    <li>activity_feed, platform_settings</li>
                    <li>system_maintenance, schema_versions</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="cybersec-db-logs">
        <h2>Recent Activity</h2>
        <div class="logs-container">
            <?php
            $logs = $this->get_logs();
            if (!empty($logs)):
                foreach (array_slice($logs, 0, 10) as $log):
                    $log_parts = explode('] ', $log, 2);
                    $level = str_replace('[', '', $log_parts[0]);
                    $message = isset($log_parts[1]) ? $log_parts[1] : $log;
                    $class = strtolower($level);
            ?>
                <div class="log-entry <?php echo esc_attr($class); ?>">
                    <span class="log-level"><?php echo esc_html($level); ?></span>
                    <span class="log-message"><?php echo esc_html($message); ?></span>
                </div>
            <?php
                endforeach;
            else:
            ?>
                <p class="no-logs">No recent activity logs found.</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Build Database
    $('#build-database').on('click', function() {
        if (!confirm(cybersecDbAjax.strings.confirm_build)) {
            return;
        }
        
        var $button = $(this);
        var $status = $('#build-status');
        
        $button.prop('disabled', true).text(cybersecDbAjax.strings.processing);
        $status.html('<div class="spinner is-active"></div> Processing...');
        
        $.post(cybersecDbAjax.ajax_url, {
            action: 'cybersec_db_action',
            action_type: 'build_database',
            nonce: cybersecDbAjax.nonce
        }, function(response) {
            if (response.success) {
                $status.html('<div class="notice notice-success"><p>' + response.message + '</p></div>');
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                $status.html('<div class="notice notice-error"><p>' + response.message + '</p></div>');
            }
            $button.prop('disabled', false).html('<span class="dashicons dashicons-hammer"></span> Build Database');
        });
    });
    
    // Remove Database
    $('#remove-database').on('click', function() {
        if (!confirm(cybersecDbAjax.strings.confirm_remove)) {
            return;
        }
        
        var $button = $(this);
        var $status = $('#remove-status');
        
        $button.prop('disabled', true).text(cybersecDbAjax.strings.processing);
        $status.html('<div class="spinner is-active"></div> Processing...');
        
        $.post(cybersecDbAjax.ajax_url, {
            action: 'cybersec_db_action',
            action_type: 'remove_database',
            nonce: cybersecDbAjax.nonce
        }, function(response) {
            if (response.success) {
                $status.html('<div class="notice notice-success"><p>' + response.message + '</p></div>');
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                $status.html('<div class="notice notice-error"><p>' + response.message + '</p></div>');
            }
            $button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Remove Database');
        });
    });
    
    // Export Database
    $('#export-database').on('click', function() {
        if (!confirm(cybersecDbAjax.strings.confirm_export)) {
            return;
        }
        
        var $button = $(this);
        var $status = $('#export-status');
        
        $button.prop('disabled', true).text(cybersecDbAjax.strings.processing);
        $status.html('<div class="spinner is-active"></div> Processing...');
        
        $.post(cybersecDbAjax.ajax_url, {
            action: 'cybersec_db_action',
            action_type: 'export_database',
            nonce: cybersecDbAjax.nonce
        }, function(response) {
            if (response.success) {
                $status.html('<div class="notice notice-success"><p>' + response.message + '</p></div>');
                if (response.download_url) {
                    $status.append('<p><a href="' + response.download_url + '" class="button button-primary">Download Export</a></p>');
                }
            } else {
                $status.html('<div class="notice notice-error"><p>' + response.message + '</p></div>');
            }
            $button.prop('disabled', false).html('<span class="dashicons dashicons-download"></span> Export Database');
        });
    });
    
    // Fix Database
    $('#fix-database').on('click', function() {
        if (!confirm(cybersecDbAjax.strings.confirm_fix)) {
            return;
        }
        
        var $button = $(this);
        var $status = $('#fix-status');
        
        $button.prop('disabled', true).text(cybersecDbAjax.strings.processing);
        $status.html('<div class="spinner is-active"></div> Processing...');
        
        $.post(cybersecDbAjax.ajax_url, {
            action: 'cybersec_db_action',
            action_type: 'fix_database',
            nonce: cybersecDbAjax.nonce
        }, function(response) {
            if (response.success) {
                $status.html('<div class="notice notice-success"><p>' + response.message + '</p></div>');
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                $status.html('<div class="notice notice-error"><p>' + response.message + '</p></div>');
            }
            $button.prop('disabled', false).html('<span class="dashicons dashicons-admin-tools"></span> Fix Database');
        });
    });
    
});
</script>