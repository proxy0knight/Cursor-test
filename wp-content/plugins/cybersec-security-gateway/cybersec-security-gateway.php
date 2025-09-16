<?php
/**
 * Plugin Name: CyberSec Security Gateway
 * Plugin URI: https://yourwebsite.com/cybersec-security-gateway
 * Description: Advanced security middleware for CyberSec Platform - Network flow control, attack prevention, and user authentication gateway.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: cybersec-security-gateway
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CYBERSEC_GATEWAY_VERSION', '1.0.0');
define('CYBERSEC_GATEWAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CYBERSEC_GATEWAY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CYBERSEC_GATEWAY_PLUGIN_FILE', __FILE__);

class CyberSecSecurityGateway {
    
    private $security_rules;
    private $rate_limits;
    private $blocked_ips;
    private $suspicious_activities;
    
    public function __construct() {
        $this->security_rules = get_option('cybersec_security_rules', array());
        $this->rate_limits = get_option('cybersec_rate_limits', array());
        $this->blocked_ips = get_option('cybersec_blocked_ips', array());
        $this->suspicious_activities = get_option('cybersec_suspicious_activities', array());
        
        // Initialize security gateway
        add_action('init', array($this, 'init_security_gateway'), 1);
        add_action('wp_loaded', array($this, 'monitor_requests'), 1);
        add_action('admin_init', array($this, 'protect_admin_area'));
        add_action('wp_login', array($this, 'log_login_attempt'), 10, 2);
        add_action('wp_login_failed', array($this, 'log_failed_login'));
        add_action('wp_ajax_cybersec_gateway_action', array($this, 'handle_ajax_request'));
        add_action('wp_ajax_nopriv_cybersec_gateway_action', array($this, 'handle_ajax_request'));
        
        // Security headers
        add_action('send_headers', array($this, 'add_security_headers'));
        
        // Content filtering
        add_filter('the_content', array($this, 'filter_content'));
        add_filter('wp_headers', array($this, 'filter_headers'));
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init_security_gateway() {
        // Initialize security rules if not set
        if (empty($this->security_rules)) {
            $this->initialize_default_security_rules();
        }
        
        // Check for blocked IPs
        $this->check_blocked_ips();
        
        // Monitor for suspicious activities
        $this->monitor_suspicious_activities();
        
        // Rate limiting
        $this->enforce_rate_limits();
    }
    
    public function monitor_requests() {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $request_method = $_SERVER['REQUEST_METHOD'] ?? '';
        $user_ip = $this->get_client_ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Log all requests
        $this->log_request($request_uri, $request_method, $user_ip, $user_agent);
        
        // Check for attack patterns
        $this->detect_attack_patterns($request_uri, $request_method, $user_ip, $user_agent);
        
        // Validate request integrity
        $this->validate_request_integrity();
        
        // Check for malicious payloads
        $this->scan_for_malicious_payloads();
    }
    
    public function protect_admin_area() {
        // Only allow admin access to wp-admin
        if (is_admin() && !current_user_can('manage_options')) {
            $this->log_security_event('unauthorized_admin_access', 'high', array(
                'user_id' => get_current_user_id(),
                'ip' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? ''
            ));
            
            wp_die('Access Denied', 'Unauthorized Access', array('response' => 403));
        }
        
        // Additional admin area protection
        $this->protect_admin_endpoints();
    }
    
    private function initialize_default_security_rules() {
        $default_rules = array(
            'blocked_patterns' => array(
                '/\.\./',  // Directory traversal
                '/<script/i',  // XSS attempts
                '/union.*select/i',  // SQL injection
                '/drop.*table/i',  // SQL injection
                '/exec\(/i',  // Command injection
                '/eval\(/i',  // Code injection
                '/base64_decode/i',  // Obfuscated code
                '/system\(/i',  // System commands
                '/shell_exec/i',  // Shell execution
                '/passthru/i',  // Command execution
            ),
            'rate_limits' => array(
                'login_attempts' => 5,  // Max login attempts per hour
                'api_requests' => 100,  // Max API requests per minute
                'page_requests' => 200,  // Max page requests per minute
                'file_uploads' => 10,  // Max file uploads per hour
            ),
            'blocked_user_agents' => array(
                'sqlmap',
                'nikto',
                'nmap',
                'masscan',
                'zap',
                'burp',
                'w3af',
                'acunetix',
                'nessus',
                'openvas'
            ),
            'allowed_admin_ips' => array(),  // Whitelist for admin access
            'blocked_countries' => array(),  // Geo-blocking
            'security_headers' => array(
                'x_frame_options' => 'DENY',
                'x_content_type_options' => 'nosniff',
                'x_xss_protection' => '1; mode=block',
                'strict_transport_security' => 'max-age=31536000; includeSubDomains',
                'content_security_policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'"
            )
        );
        
        update_option('cybersec_security_rules', $default_rules);
        $this->security_rules = $default_rules;
    }
    
    private function check_blocked_ips() {
        $client_ip = $this->get_client_ip();
        
        if (in_array($client_ip, $this->blocked_ips)) {
            $this->log_security_event('blocked_ip_access', 'high', array(
                'ip' => $client_ip,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? ''
            ));
            
            http_response_code(403);
            die('Access Denied - IP Blocked');
        }
    }
    
    private function monitor_suspicious_activities() {
        $client_ip = $this->get_client_ip();
        $current_time = time();
        
        // Check for rapid requests
        $recent_requests = $this->get_recent_requests($client_ip, 60); // Last minute
        if (count($recent_requests) > $this->security_rules['rate_limits']['page_requests']) {
            $this->handle_rate_limit_violation($client_ip, 'page_requests');
        }
        
        // Check for suspicious user agents
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        foreach ($this->security_rules['blocked_user_agents'] as $blocked_ua) {
            if (stripos($user_agent, $blocked_ua) !== false) {
                $this->handle_suspicious_user_agent($client_ip, $user_agent);
            }
        }
    }
    
    private function enforce_rate_limits() {
        $client_ip = $this->get_client_ip();
        $endpoint = $_SERVER['REQUEST_URI'] ?? '';
        
        // API rate limiting
        if (strpos($endpoint, '/wp-json/') === 0 || strpos($endpoint, '/api/') === 0) {
            $recent_api_requests = $this->get_recent_requests($client_ip, 60, 'api');
            if (count($recent_api_requests) > $this->security_rules['rate_limits']['api_requests']) {
                $this->handle_rate_limit_violation($client_ip, 'api_requests');
            }
        }
        
        // Login rate limiting
        if (strpos($endpoint, '/wp-login.php') !== false) {
            $recent_login_attempts = $this->get_recent_requests($client_ip, 3600, 'login');
            if (count($recent_login_attempts) > $this->security_rules['rate_limits']['login_attempts']) {
                $this->handle_rate_limit_violation($client_ip, 'login_attempts');
            }
        }
    }
    
    private function detect_attack_patterns($request_uri, $request_method, $user_ip, $user_agent) {
        $request_data = $request_uri . ' ' . $request_method . ' ' . $user_agent;
        $request_data .= ' ' . serialize($_GET) . ' ' . serialize($_POST);
        
        foreach ($this->security_rules['blocked_patterns'] as $pattern) {
            if (preg_match($pattern, $request_data)) {
                $this->handle_attack_detection($user_ip, $pattern, $request_data);
                break;
            }
        }
    }
    
    private function validate_request_integrity() {
        // Check for request tampering
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !$this->is_trusted_proxy()) {
            $this->log_security_event('suspicious_proxy_header', 'medium', array(
                'ip' => $this->get_client_ip(),
                'x_forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR']
            ));
        }
        
        // Check for request size limits
        $content_length = $_SERVER['CONTENT_LENGTH'] ?? 0;
        if ($content_length > 10 * 1024 * 1024) { // 10MB limit
            $this->log_security_event('oversized_request', 'medium', array(
                'ip' => $this->get_client_ip(),
                'content_length' => $content_length
            ));
            
            http_response_code(413);
            die('Request Entity Too Large');
        }
    }
    
    private function scan_for_malicious_payloads() {
        $payloads = array_merge($_GET, $_POST, $_COOKIE);
        
        foreach ($payloads as $key => $value) {
            if (is_string($value)) {
                foreach ($this->security_rules['blocked_patterns'] as $pattern) {
                    if (preg_match($pattern, $value)) {
                        $this->handle_malicious_payload($key, $value, $pattern);
                        break 2;
                    }
                }
            }
        }
    }
    
    private function handle_attack_detection($ip, $pattern, $request_data) {
        $this->log_security_event('attack_detected', 'critical', array(
            'ip' => $ip,
            'pattern' => $pattern,
            'request_data' => substr($request_data, 0, 1000),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ));
        
        // Auto-block IP after multiple attacks
        $attack_count = $this->get_attack_count($ip, 3600); // Last hour
        if ($attack_count >= 3) {
            $this->block_ip($ip, 'Multiple attack attempts');
        }
        
        // Block the request
        http_response_code(403);
        die('Access Denied - Security Violation');
    }
    
    private function handle_rate_limit_violation($ip, $type) {
        $this->log_security_event('rate_limit_violation', 'high', array(
            'ip' => $ip,
            'type' => $type,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ));
        
        // Temporary block for rate limit violations
        $this->temporary_block_ip($ip, 300); // 5 minutes
        
        http_response_code(429);
        die('Too Many Requests - Rate Limit Exceeded');
    }
    
    private function handle_suspicious_user_agent($ip, $user_agent) {
        $this->log_security_event('suspicious_user_agent', 'high', array(
            'ip' => $ip,
            'user_agent' => $user_agent
        ));
        
        // Block suspicious user agents
        http_response_code(403);
        die('Access Denied - Suspicious User Agent');
    }
    
    private function handle_malicious_payload($key, $value, $pattern) {
        $this->log_security_event('malicious_payload', 'critical', array(
            'ip' => $this->get_client_ip(),
            'key' => $key,
            'value' => substr($value, 0, 500),
            'pattern' => $pattern
        ));
        
        http_response_code(400);
        die('Bad Request - Malicious Payload Detected');
    }
    
    private function block_ip($ip, $reason) {
        if (!in_array($ip, $this->blocked_ips)) {
            $this->blocked_ips[] = $ip;
            update_option('cybersec_blocked_ips', $this->blocked_ips);
            
            $this->log_security_event('ip_blocked', 'high', array(
                'ip' => $ip,
                'reason' => $reason
            ));
        }
    }
    
    private function temporary_block_ip($ip, $duration) {
        $temp_blocks = get_option('cybersec_temp_blocks', array());
        $temp_blocks[$ip] = time() + $duration;
        update_option('cybersec_temp_blocks', $temp_blocks);
    }
    
    private function get_client_ip() {
        $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    private function log_request($uri, $method, $ip, $user_agent) {
        $log_entry = array(
            'timestamp' => time(),
            'ip' => $ip,
            'method' => $method,
            'uri' => $uri,
            'user_agent' => $user_agent,
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'status' => http_response_code()
        );
        
        $this->store_request_log($log_entry);
    }
    
    private function log_security_event($type, $severity, $data) {
        $event = array(
            'timestamp' => time(),
            'type' => $type,
            'severity' => $severity,
            'data' => $data,
            'ip' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
        
        $this->store_security_event($event);
        
        // Send alerts for critical events
        if ($severity === 'critical') {
            $this->send_security_alert($event);
        }
    }
    
    private function store_request_log($log_entry) {
        $logs = get_option('cybersec_request_logs', array());
        $logs[] = $log_entry;
        
        // Keep only last 10000 entries
        if (count($logs) > 10000) {
            $logs = array_slice($logs, -10000);
        }
        
        update_option('cybersec_request_logs', $logs);
    }
    
    private function store_security_event($event) {
        $events = get_option('cybersec_security_events', array());
        $events[] = $event;
        
        // Keep only last 5000 events
        if (count($events) > 5000) {
            $events = array_slice($events, -5000);
        }
        
        update_option('cybersec_security_events', $events);
    }
    
    private function send_security_alert($event) {
        // Send email alert to admin
        $admin_email = get_option('admin_email');
        $subject = 'Critical Security Alert - ' . get_bloginfo('name');
        $message = "Critical security event detected:\n\n";
        $message .= "Type: " . $event['type'] . "\n";
        $message .= "Severity: " . $event['severity'] . "\n";
        $message .= "IP: " . $event['ip'] . "\n";
        $message .= "Time: " . date('Y-m-d H:i:s', $event['timestamp']) . "\n";
        $message .= "Data: " . print_r($event['data'], true) . "\n";
        
        wp_mail($admin_email, $subject, $message);
    }
    
    public function add_security_headers() {
        if (!is_admin()) {
            foreach ($this->security_rules['security_headers'] as $header => $value) {
                header($header . ': ' . $value);
            }
        }
    }
    
    public function filter_content($content) {
        // Remove potentially dangerous content
        $content = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $content);
        $content = preg_replace('/javascript:/i', '', $content);
        $content = preg_replace('/on\w+\s*=/i', '', $content);
        
        return $content;
    }
    
    public function filter_headers($headers) {
        // Remove sensitive headers
        unset($headers['X-Powered-By']);
        unset($headers['Server']);
        
        return $headers;
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Security Gateway',
            'Security Gateway',
            'manage_options',
            'cybersec-security-gateway',
            array($this, 'admin_page'),
            'dashicons-shield',
            25
        );
        
        add_submenu_page(
            'cybersec-security-gateway',
            'Security Dashboard',
            'Dashboard',
            'manage_options',
            'cybersec-security-gateway',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'cybersec-security-gateway',
            'Blocked IPs',
            'Blocked IPs',
            'manage_options',
            'cybersec-blocked-ips',
            array($this, 'blocked_ips_page')
        );
        
        add_submenu_page(
            'cybersec-security-gateway',
            'Security Events',
            'Security Events',
            'manage_options',
            'cybersec-security-events',
            array($this, 'security_events_page')
        );
        
        add_submenu_page(
            'cybersec-security-gateway',
            'Security Rules',
            'Security Rules',
            'manage_options',
            'cybersec-security-rules',
            array($this, 'security_rules_page')
        );
    }
    
    public function admin_page() {
        $security_stats = $this->get_security_stats();
        include CYBERSEC_GATEWAY_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }
    
    public function blocked_ips_page() {
        $blocked_ips = $this->blocked_ips;
        include CYBERSEC_GATEWAY_PLUGIN_DIR . 'templates/blocked-ips.php';
    }
    
    public function security_events_page() {
        $security_events = get_option('cybersec_security_events', array());
        $security_events = array_reverse(array_slice($security_events, -100)); // Last 100 events
        include CYBERSEC_GATEWAY_PLUGIN_DIR . 'templates/security-events.php';
    }
    
    public function security_rules_page() {
        $security_rules = $this->security_rules;
        include CYBERSEC_GATEWAY_PLUGIN_DIR . 'templates/security-rules.php';
    }
    
    private function get_security_stats() {
        $events = get_option('cybersec_security_events', array());
        $logs = get_option('cybersec_request_logs', array());
        
        $stats = array(
            'total_requests' => count($logs),
            'blocked_ips' => count($this->blocked_ips),
            'security_events' => count($events),
            'critical_events' => count(array_filter($events, function($e) { return $e['severity'] === 'critical'; })),
            'high_events' => count(array_filter($events, function($e) { return $e['severity'] === 'high'; })),
            'medium_events' => count(array_filter($events, function($e) { return $e['severity'] === 'medium'; })),
            'recent_attacks' => count(array_filter($events, function($e) { 
                return $e['type'] === 'attack_detected' && $e['timestamp'] > (time() - 3600); 
            }))
        );
        
        return $stats;
    }
    
    public function activate() {
        // Create necessary options
        add_option('cybersec_security_rules', array());
        add_option('cybersec_rate_limits', array());
        add_option('cybersec_blocked_ips', array());
        add_option('cybersec_security_events', array());
        add_option('cybersec_request_logs', array());
        add_option('cybersec_temp_blocks', array());
    }
    
    public function deactivate() {
        // Cleanup on deactivation
        wp_clear_scheduled_hook('cybersec_cleanup_logs');
    }
}

// Initialize the security gateway
new CyberSecSecurityGateway();