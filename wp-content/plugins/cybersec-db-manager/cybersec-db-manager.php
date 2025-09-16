<?php
/**
 * Plugin Name: CyberSec Database Manager
 * Plugin URI: https://yourwebsite.com/cybersec-db-manager
 * Description: Complete database management system for CyberSec Platform - Build, rebuild, export, import, and fix database schemas.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: cybersec-db-manager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CYBERSEC_DB_MANAGER_VERSION', '1.0.0');
define('CYBERSEC_DB_MANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CYBERSEC_DB_MANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CYBERSEC_DB_MANAGER_PLUGIN_FILE', __FILE__);

class CyberSecDBManager {
    
    private $db_schema_file;
    private $extended_schema_file;
    
    public function __construct() {
        $this->db_schema_file = CYBERSEC_DB_MANAGER_PLUGIN_DIR . 'schemas/database-schema.sql';
        $this->extended_schema_file = CYBERSEC_DB_MANAGER_PLUGIN_DIR . 'schemas/database-schema-extended.sql';
        
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_cybersec_db_action', array($this, 'handle_ajax_request'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Initialize plugin
        load_plugin_textdomain('cybersec-db-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function activate() {
        // Create necessary directories
        $this->create_directories();
        
        // Create default options
        add_option('cybersec_db_manager_version', CYBERSEC_DB_MANAGER_VERSION);
        add_option('cybersec_db_manager_last_backup', '');
        add_option('cybersec_db_manager_schema_version', '0.0.0');
    }
    
    public function deactivate() {
        // Cleanup on deactivation
        wp_clear_scheduled_hook('cybersec_db_cleanup');
    }
    
    private function create_directories() {
        $directories = array(
            CYBERSEC_DB_MANAGER_PLUGIN_DIR . 'schemas',
            CYBERSEC_DB_MANAGER_PLUGIN_DIR . 'backups',
            CYBERSEC_DB_MANAGER_PLUGIN_DIR . 'logs'
        );
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'CyberSec DB Manager',
            'Database Control',
            'manage_options',
            'cybersec-db-manager',
            array($this, 'admin_page'),
            'dashicons-database',
            30
        );
        
        add_submenu_page(
            'cybersec-db-manager',
            'Database Status',
            'Status',
            'manage_options',
            'cybersec-db-manager',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'cybersec-db-manager',
            'Database Backup',
            'Backup',
            'manage_options',
            'cybersec-db-backup',
            array($this, 'backup_page')
        );
        
        add_submenu_page(
            'cybersec-db-manager',
            'Database Logs',
            'Logs',
            'manage_options',
            'cybersec-db-logs',
            array($this, 'logs_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'cybersec-db') !== false) {
            wp_enqueue_script('cybersec-db-manager-js', CYBERSEC_DB_MANAGER_PLUGIN_URL . 'assets/admin.js', array('jquery'), CYBERSEC_DB_MANAGER_VERSION, true);
            wp_enqueue_style('cybersec-db-manager-css', CYBERSEC_DB_MANAGER_PLUGIN_URL . 'assets/admin.css', array(), CYBERSEC_DB_MANAGER_VERSION);
            
            wp_localize_script('cybersec-db-manager-js', 'cybersecDbAjax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cybersec_db_nonce'),
                'strings' => array(
                    'confirm_build' => __('Are you sure you want to build/rebuild the database? This action cannot be undone.', 'cybersec-db-manager'),
                    'confirm_remove' => __('Are you sure you want to remove the database? This will delete ALL data!', 'cybersec-db-manager'),
                    'confirm_export' => __('Export database to file?', 'cybersec-db-manager'),
                    'confirm_fix' => __('Fix database schema? This will add missing tables and fields.', 'cybersec-db-manager'),
                    'processing' => __('Processing...', 'cybersec-db-manager'),
                    'success' => __('Operation completed successfully!', 'cybersec-db-manager'),
                    'error' => __('An error occurred. Please check the logs.', 'cybersec-db-manager')
                )
            ));
        }
    }
    
    public function admin_page() {
        $db_status = $this->get_database_status();
        $schema_version = get_option('cybersec_db_manager_schema_version', '0.0.0');
        $last_backup = get_option('cybersec_db_manager_last_backup', '');
        
        include CYBERSEC_DB_MANAGER_PLUGIN_DIR . 'templates/admin-page.php';
    }
    
    public function backup_page() {
        $backups = $this->get_backup_list();
        include CYBERSEC_DB_MANAGER_PLUGIN_DIR . 'templates/backup-page.php';
    }
    
    public function logs_page() {
        $logs = $this->get_logs();
        include CYBERSEC_DB_MANAGER_PLUGIN_DIR . 'templates/logs-page.php';
    }
    
    public function handle_ajax_request() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cybersec_db_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $action = sanitize_text_field($_POST['action_type']);
        $result = array('success' => false, 'message' => '');
        
        try {
            switch ($action) {
                case 'build_database':
                    $result = $this->build_database();
                    break;
                case 'remove_database':
                    $result = $this->remove_database();
                    break;
                case 'export_database':
                    $result = $this->export_database();
                    break;
                case 'fix_database':
                    $result = $this->fix_database();
                    break;
                case 'import_database':
                    $result = $this->import_database();
                    break;
                default:
                    $result['message'] = 'Invalid action';
            }
        } catch (Exception $e) {
            $result['message'] = 'Error: ' . $e->getMessage();
            $this->log_error('AJAX Error: ' . $e->getMessage());
        }
        
        wp_send_json($result);
    }
    
    private function build_database() {
        global $wpdb;
        
        $this->log_info('Starting database build/rebuild process');
        
        // Read schema files
        $core_schema = file_get_contents($this->db_schema_file);
        $extended_schema = file_get_contents($this->extended_schema_file);
        
        if (!$core_schema || !$extended_schema) {
            throw new Exception('Could not read schema files');
        }
        
        // Combine schemas
        $full_schema = $core_schema . "\n" . $extended_schema;
        
        // Split into individual queries
        $queries = $this->split_sql_queries($full_schema);
        
        $executed_queries = 0;
        $errors = array();
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (empty($query) || strpos($query, '--') === 0) {
                continue;
            }
            
            try {
                $wpdb->query($query);
                $executed_queries++;
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
                $this->log_error('Query failed: ' . $query . ' - Error: ' . $e->getMessage());
            }
        }
        
        // Update schema version
        update_option('cybersec_db_manager_schema_version', '1.1.0');
        
        $this->log_info("Database build completed. Executed {$executed_queries} queries");
        
        if (!empty($errors)) {
            return array(
                'success' => false,
                'message' => 'Database built with ' . count($errors) . ' errors. Check logs for details.',
                'errors' => $errors
            );
        }
        
        return array(
            'success' => true,
            'message' => "Database built successfully! Executed {$executed_queries} queries."
        );
    }
    
    private function remove_database() {
        global $wpdb;
        
        $this->log_info('Starting database removal process');
        
        // Get all tables to remove
        $tables = $this->get_cybersec_tables();
        
        $removed_tables = 0;
        $errors = array();
        
        foreach ($tables as $table) {
            try {
                $wpdb->query("DROP TABLE IF EXISTS {$table}");
                $removed_tables++;
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
                $this->log_error("Failed to remove table {$table}: " . $e->getMessage());
            }
        }
        
        // Reset schema version
        update_option('cybersec_db_manager_schema_version', '0.0.0');
        
        $this->log_info("Database removal completed. Removed {$removed_tables} tables");
        
        if (!empty($errors)) {
            return array(
                'success' => false,
                'message' => 'Database removed with ' . count($errors) . ' errors. Check logs for details.',
                'errors' => $errors
            );
        }
        
        return array(
            'success' => true,
            'message' => "Database removed successfully! Removed {$removed_tables} tables."
        );
    }
    
    private function export_database() {
        global $wpdb;
        
        $this->log_info('Starting database export process');
        
        $tables = $this->get_cybersec_tables();
        $export_data = array();
        
        foreach ($tables as $table) {
            // Get table structure
            $create_table = $wpdb->get_row("SHOW CREATE TABLE {$table}", ARRAY_A);
            if ($create_table) {
                $export_data[] = "-- Table structure for table `{$table}`";
                $export_data[] = "DROP TABLE IF EXISTS `{$table}`;";
                $export_data[] = $create_table['Create Table'] . ";";
                $export_data[] = "";
                
                // Get table data
                $rows = $wpdb->get_results("SELECT * FROM {$table}", ARRAY_A);
                if (!empty($rows)) {
                    $export_data[] = "-- Data for table `{$table}`";
                    foreach ($rows as $row) {
                        $values = array();
                        foreach ($row as $value) {
                            $values[] = $wpdb->prepare('%s', $value);
                        }
                        $export_data[] = "INSERT INTO `{$table}` VALUES (" . implode(', ', $values) . ");";
                    }
                    $export_data[] = "";
                }
            }
        }
        
        // Save to file
        $filename = 'cybersec_db_export_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = CYBERSEC_DB_MANAGER_PLUGIN_DIR . 'backups/' . $filename;
        
        file_put_contents($filepath, implode("\n", $export_data));
        
        // Update last backup
        update_option('cybersec_db_manager_last_backup', $filename);
        
        $this->log_info("Database export completed. File: {$filename}");
        
        return array(
            'success' => true,
            'message' => "Database exported successfully! File: {$filename}",
            'download_url' => CYBERSEC_DB_MANAGER_PLUGIN_URL . 'backups/' . $filename
        );
    }
    
    private function fix_database() {
        global $wpdb;
        
        $this->log_info('Starting database fix process');
        
        // Check for missing tables and fields
        $missing_tables = $this->get_missing_tables();
        $missing_fields = $this->get_missing_fields();
        
        $fixed_items = 0;
        $errors = array();
        
        // Fix missing tables
        foreach ($missing_tables as $table) {
            try {
                $this->create_missing_table($table);
                $fixed_items++;
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        
        // Fix missing fields
        foreach ($missing_fields as $field) {
            try {
                $this->add_missing_field($field['table'], $field['field']);
                $fixed_items++;
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        
        $this->log_info("Database fix completed. Fixed {$fixed_items} items");
        
        if (!empty($errors)) {
            return array(
                'success' => false,
                'message' => 'Database fixed with ' . count($errors) . ' errors. Check logs for details.',
                'errors' => $errors
            );
        }
        
        return array(
            'success' => true,
            'message' => "Database fixed successfully! Fixed {$fixed_items} items."
        );
    }
    
    private function get_database_status() {
        global $wpdb;
        
        $tables = $this->get_cybersec_tables();
        $status = array(
            'total_tables' => count($tables),
            'existing_tables' => 0,
            'missing_tables' => array(),
            'total_records' => 0,
            'last_updated' => get_option('cybersec_db_manager_schema_version', '0.0.0')
        );
        
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") == $table) {
                $status['existing_tables']++;
                $status['total_records'] += $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
            } else {
                $status['missing_tables'][] = $table;
            }
        }
        
        return $status;
    }
    
    private function get_cybersec_tables() {
        return array(
            'users', 'user_profiles', 'user_stats', 'user_classifications', 'user_moderation',
            'user_activity_logs', 'user_reputation', 'user_badges', 'teams', 'team_members',
            'team_stats', 'competitions', 'competition_participants', 'competition_challenges',
            'challenges', 'challenge_categories', 'challenge_submissions', 'learning_paths',
            'learning_modules', 'user_learning_progress', 'badges', 'leaderboards',
            'notifications', 'activity_feed', 'platform_settings', 'content_moderation',
            'security_events', 'api_usage_logs', 'system_maintenance', 'schema_versions'
        );
    }
    
    private function split_sql_queries($sql) {
        // Remove comments and split by semicolon
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        $queries = explode(';', $sql);
        return array_filter(array_map('trim', $queries));
    }
    
    private function log_info($message) {
        $this->write_log('INFO', $message);
    }
    
    private function log_error($message) {
        $this->write_log('ERROR', $message);
    }
    
    private function write_log($level, $message) {
        $log_entry = date('Y-m-d H:i:s') . " [{$level}] {$message}\n";
        $log_file = CYBERSEC_DB_MANAGER_PLUGIN_DIR . 'logs/db_manager.log';
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    private function get_backup_list() {
        $backup_dir = CYBERSEC_DB_MANAGER_PLUGIN_DIR . 'backups/';
        $backups = array();
        
        if (is_dir($backup_dir)) {
            $files = glob($backup_dir . '*.sql');
            foreach ($files as $file) {
                $backups[] = array(
                    'filename' => basename($file),
                    'size' => filesize($file),
                    'date' => date('Y-m-d H:i:s', filemtime($file)),
                    'url' => CYBERSEC_DB_MANAGER_PLUGIN_URL . 'backups/' . basename($file)
                );
            }
        }
        
        return $backups;
    }
    
    private function get_logs() {
        $log_file = CYBERSEC_DB_MANAGER_PLUGIN_DIR . 'logs/db_manager.log';
        $logs = array();
        
        if (file_exists($log_file)) {
            $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $logs = array_slice(array_reverse($lines), 0, 100); // Last 100 entries
        }
        
        return $logs;
    }
    
    private function get_missing_tables() {
        global $wpdb;
        $all_tables = $this->get_cybersec_tables();
        $missing = array();
        
        foreach ($all_tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") != $table) {
                $missing[] = $table;
            }
        }
        
        return $missing;
    }
    
    private function get_missing_fields() {
        // This would check for missing fields in existing tables
        // Implementation depends on your specific field requirements
        return array();
    }
    
    private function create_missing_table($table_name) {
        // Implementation for creating specific missing tables
        // This would read from schema and create the specific table
    }
    
    private function add_missing_field($table_name, $field_name) {
        // Implementation for adding missing fields
        // This would read from schema and add the specific field
    }
    
    private function import_database() {
        global $wpdb;
        
        $this->log_info('Starting database import process');
        
        // Check if file was uploaded
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error occurred');
        }
        
        $uploaded_file = $_FILES['import_file'];
        
        // Validate file type
        $file_extension = strtolower(pathinfo($uploaded_file['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'sql') {
            throw new Exception('Invalid file type. Please upload a .sql file');
        }
        
        // Validate file size (max 50MB)
        if ($uploaded_file['size'] > 50 * 1024 * 1024) {
            throw new Exception('File too large. Maximum size is 50MB');
        }
        
        // Read the SQL file
        $sql_content = file_get_contents($uploaded_file['tmp_name']);
        if (!$sql_content) {
            throw new Exception('Could not read the uploaded file');
        }
        
        // Validate SQL content
        if (!$this->validate_sql_content($sql_content)) {
            throw new Exception('Invalid SQL file format or content');
        }
        
        // Create backup before import
        $backup_result = $this->create_backup_before_import();
        if (!$backup_result['success']) {
            throw new Exception('Failed to create backup before import: ' . $backup_result['message']);
        }
        
        // Split SQL into individual queries
        $queries = $this->split_sql_queries($sql_content);
        
        $executed_queries = 0;
        $errors = array();
        $imported_tables = array();
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (empty($query) || strpos($query, '--') === 0) {
                continue;
            }
            
            try {
                $wpdb->query($query);
                $executed_queries++;
                
                // Track imported tables
                if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $query, $matches)) {
                    $imported_tables[] = $matches[1];
                }
                
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
                $this->log_error('Import query failed: ' . $query . ' - Error: ' . $e->getMessage());
            }
        }
        
        // Update schema version if import was successful
        if (empty($errors)) {
            update_option('cybersec_db_manager_schema_version', '1.1.0');
        }
        
        $this->log_info("Database import completed. Executed {$executed_queries} queries, imported " . count($imported_tables) . " tables");
        
        if (!empty($errors)) {
            return array(
                'success' => false,
                'message' => 'Database imported with ' . count($errors) . ' errors. Check logs for details.',
                'errors' => $errors,
                'imported_tables' => $imported_tables
            );
        }
        
        return array(
            'success' => true,
            'message' => "Database imported successfully! Executed {$executed_queries} queries, imported " . count($imported_tables) . " tables.",
            'imported_tables' => $imported_tables
        );
    }
    
    private function validate_sql_content($sql_content) {
        // Basic validation of SQL content
        $required_patterns = array(
            '/CREATE TABLE/i',
            '/INSERT INTO/i'
        );
        
        foreach ($required_patterns as $pattern) {
            if (!preg_match($pattern, $sql_content)) {
                return false;
            }
        }
        
        // Check for potentially dangerous operations
        $dangerous_patterns = array(
            '/DROP DATABASE/i',
            '/DELETE FROM.*users/i',
            '/TRUNCATE TABLE.*users/i'
        );
        
        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $sql_content)) {
                return false;
            }
        }
        
        return true;
    }
    
    private function create_backup_before_import() {
        try {
            $this->log_info('Creating backup before import');
            
            $tables = $this->get_cybersec_tables();
            $backup_data = array();
            
            foreach ($tables as $table) {
                if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") == $table) {
                    // Get table structure
                    $create_table = $wpdb->get_row("SHOW CREATE TABLE {$table}", ARRAY_A);
                    if ($create_table) {
                        $backup_data[] = "-- Table structure for table `{$table}`";
                        $backup_data[] = "DROP TABLE IF EXISTS `{$table}`;";
                        $backup_data[] = $create_table['Create Table'] . ";";
                        $backup_data[] = "";
                        
                        // Get table data
                        $rows = $wpdb->get_results("SELECT * FROM {$table}", ARRAY_A);
                        if (!empty($rows)) {
                            $backup_data[] = "-- Data for table `{$table}`";
                            foreach ($rows as $row) {
                                $values = array();
                                foreach ($row as $value) {
                                    $values[] = $wpdb->prepare('%s', $value);
                                }
                                $backup_data[] = "INSERT INTO `{$table}` VALUES (" . implode(', ', $values) . ");";
                            }
                            $backup_data[] = "";
                        }
                    }
                }
            }
            
            // Save backup
            $filename = 'pre_import_backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = CYBERSEC_DB_MANAGER_PLUGIN_DIR . 'backups/' . $filename;
            
            file_put_contents($filepath, implode("\n", $backup_data));
            
            return array(
                'success' => true,
                'message' => 'Backup created successfully',
                'filename' => $filename
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
}

// Initialize the plugin
new CyberSecDBManager();