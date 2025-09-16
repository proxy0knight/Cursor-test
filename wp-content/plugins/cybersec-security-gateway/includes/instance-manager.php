<?php
/**
 * CyberSec Instance Manager
 * Handles user instances, device fingerprinting, and handshake verification
 */

class CyberSecInstanceManager {
    
    private $instance_table = 'cybersec_user_instances';
    private $device_table = 'cybersec_device_fingerprints';
    private $handshake_table = 'cybersec_handshake_logs';
    
    public function __construct() {
        $this->create_tables();
    }
    
    private function create_tables() {
        global $wpdb;
        
        // User instances table
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}{$this->instance_table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            instance_id VARCHAR(64) UNIQUE NOT NULL,
            device_id VARCHAR(64) NOT NULL,
            device_fingerprint TEXT NOT NULL,
            session_token VARCHAR(128) NOT NULL,
            handshake_key VARCHAR(64) NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_handshake TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP,
            ip_address VARCHAR(45),
            user_agent TEXT,
            INDEX(user_id),
            INDEX(instance_id),
            INDEX(device_id),
            INDEX(session_token)
        )");
        
        // Device fingerprints table
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}{$this->device_table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            device_id VARCHAR(64) UNIQUE NOT NULL,
            user_id INT NOT NULL,
            device_fingerprint TEXT NOT NULL,
            device_info JSON,
            is_trusted BOOLEAN DEFAULT FALSE,
            trust_score INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(user_id),
            INDEX(device_id)
        )");
        
        // Handshake logs table
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}{$this->handshake_table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            instance_id VARCHAR(64) NOT NULL,
            handshake_type ENUM('initial', 'verification', 'refresh') NOT NULL,
            handshake_data TEXT,
            is_successful BOOLEAN DEFAULT FALSE,
            response_time_ms INT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(instance_id),
            INDEX(created_at)
        )");
    }
    
    /**
     * Create new user instance with device binding
     */
    public function create_user_instance($user_id, $device_fingerprint, $device_info = array()) {
        global $wpdb;
        
        // Generate unique identifiers
        $instance_id = $this->generate_instance_id();
        $device_id = $this->generate_device_id($device_fingerprint);
        $session_token = $this->generate_session_token();
        $handshake_key = $this->generate_handshake_key();
        
        // Calculate device trust score
        $trust_score = $this->calculate_device_trust_score($device_fingerprint, $device_info);
        
        // Check if device already exists
        $existing_device = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}{$this->device_table} WHERE device_id = %s",
            $device_id
        ));
        
        if ($existing_device) {
            // Update existing device
            $wpdb->update(
                "{$wpdb->prefix}{$this->device_table}",
                array(
                    'last_seen' => current_time('mysql'),
                    'trust_score' => $trust_score,
                    'device_info' => json_encode($device_info)
                ),
                array('device_id' => $device_id)
            );
        } else {
            // Create new device
            $wpdb->insert(
                "{$wpdb->prefix}{$this->device_table}",
                array(
                    'device_id' => $device_id,
                    'user_id' => $user_id,
                    'device_fingerprint' => $device_fingerprint,
                    'device_info' => json_encode($device_info),
                    'trust_score' => $trust_score,
                    'is_trusted' => $trust_score > 70
                )
            );
        }
        
        // Create user instance
        $wpdb->insert(
            "{$wpdb->prefix}{$this->instance_table}",
            array(
                'user_id' => $user_id,
                'instance_id' => $instance_id,
                'device_id' => $device_id,
                'device_fingerprint' => $device_fingerprint,
                'session_token' => $session_token,
                'handshake_key' => $handshake_key,
                'ip_address' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'expires_at' => date('Y-m-d H:i:s', time() + (24 * 60 * 60)) // 24 hours
            )
        );
        
        // Log initial handshake
        $this->log_handshake($instance_id, 'initial', array(
            'device_id' => $device_id,
            'trust_score' => $trust_score,
            'device_info' => $device_info
        ), true);
        
        return array(
            'instance_id' => $instance_id,
            'session_token' => $session_token,
            'handshake_key' => $handshake_key,
            'device_id' => $device_id,
            'trust_score' => $trust_score
        );
    }
    
    /**
     * Verify handshake for each request
     */
    public function verify_handshake($instance_id, $handshake_data) {
        global $wpdb;
        
        $start_time = microtime(true);
        
        // Get instance details
        $instance = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}{$this->instance_table} WHERE instance_id = %s AND is_active = 1",
            $instance_id
        ));
        
        if (!$instance) {
            $this->log_handshake($instance_id, 'verification', $handshake_data, false);
            return array('success' => false, 'error' => 'Invalid instance');
        }
        
        // Check if instance is expired
        if (strtotime($instance->expires_at) < time()) {
            $this->deactivate_instance($instance_id);
            return array('success' => false, 'error' => 'Instance expired');
        }
        
        // Verify handshake data
        $is_valid = $this->validate_handshake_data($instance, $handshake_data);
        
        if (!$is_valid) {
            $this->log_handshake($instance_id, 'verification', $handshake_data, false);
            $this->handle_invalid_handshake($instance_id);
            return array('success' => false, 'error' => 'Invalid handshake');
        }
        
        // Update last handshake time
        $wpdb->update(
            "{$wpdb->prefix}{$this->instance_table}",
            array('last_handshake' => current_time('mysql')),
            array('instance_id' => $instance_id)
        );
        
        $response_time = round((microtime(true) - $start_time) * 1000);
        $this->log_handshake($instance_id, 'verification', $handshake_data, true, $response_time);
        
        return array(
            'success' => true,
            'user_id' => $instance->user_id,
            'device_id' => $instance->device_id,
            'response_time' => $response_time
        );
    }
    
    /**
     * Generate device fingerprint from client data
     */
    public function generate_device_fingerprint($client_data) {
        $fingerprint_data = array(
            'user_agent' => $client_data['user_agent'] ?? '',
            'screen_resolution' => $client_data['screen_resolution'] ?? '',
            'timezone' => $client_data['timezone'] ?? '',
            'language' => $client_data['language'] ?? '',
            'platform' => $client_data['platform'] ?? '',
            'hardware_concurrency' => $client_data['hardware_concurrency'] ?? '',
            'device_memory' => $client_data['device_memory'] ?? '',
            'canvas_fingerprint' => $client_data['canvas_fingerprint'] ?? '',
            'webgl_fingerprint' => $client_data['webgl_fingerprint'] ?? '',
            'audio_fingerprint' => $client_data['audio_fingerprint'] ?? '',
            'fonts' => $client_data['fonts'] ?? array(),
            'plugins' => $client_data['plugins'] ?? array(),
            'touch_support' => $client_data['touch_support'] ?? false,
            'battery_info' => $client_data['battery_info'] ?? array()
        );
        
        return hash('sha256', json_encode($fingerprint_data));
    }
    
    /**
     * Calculate device trust score
     */
    private function calculate_device_trust_score($device_fingerprint, $device_info) {
        $score = 50; // Base score
        
        // Check for suspicious characteristics
        $user_agent = $device_info['user_agent'] ?? '';
        
        // Reduce score for suspicious user agents
        $suspicious_patterns = array(
            '/bot/i', '/crawler/i', '/spider/i', '/scraper/i',
            '/headless/i', '/phantom/i', '/selenium/i'
        );
        
        foreach ($suspicious_patterns as $pattern) {
            if (preg_match($pattern, $user_agent)) {
                $score -= 30;
            }
        }
        
        // Increase score for consistent fingerprint
        if (strlen($device_fingerprint) > 50) {
            $score += 20;
        }
        
        // Check for WebGL and Canvas support (legitimate browsers)
        if (!empty($device_info['webgl_fingerprint']) && !empty($device_info['canvas_fingerprint'])) {
            $score += 15;
        }
        
        // Check for battery API support (mobile devices)
        if (!empty($device_info['battery_info'])) {
            $score += 10;
        }
        
        // Check for touch support consistency
        if (isset($device_info['touch_support']) && $device_info['touch_support']) {
            if (preg_match('/mobile|android|iphone/i', $user_agent)) {
                $score += 10;
            }
        }
        
        return max(0, min(100, $score));
    }
    
    /**
     * Validate handshake data
     */
    private function validate_handshake_data($instance, $handshake_data) {
        // Verify session token
        if (!isset($handshake_data['session_token']) || $handshake_data['session_token'] !== $instance->session_token) {
            return false;
        }
        
        // Verify handshake key
        if (!isset($handshake_data['handshake_key']) || $handshake_data['handshake_key'] !== $instance->handshake_key) {
            return false;
        }
        
        // Verify device fingerprint hasn't changed significantly
        if (isset($handshake_data['device_fingerprint'])) {
            $similarity = $this->calculate_fingerprint_similarity(
                $instance->device_fingerprint,
                $handshake_data['device_fingerprint']
            );
            
            if ($similarity < 0.8) { // 80% similarity threshold
                return false;
            }
        }
        
        // Verify IP address (optional - can be relaxed for mobile users)
        $current_ip = $this->get_client_ip();
        if ($instance->ip_address !== $current_ip) {
            // Log IP change but don't fail for mobile users
            $this->log_security_event('ip_change_detected', 'medium', array(
                'instance_id' => $instance->instance_id,
                'old_ip' => $instance->ip_address,
                'new_ip' => $current_ip
            ));
        }
        
        return true;
    }
    
    /**
     * Calculate fingerprint similarity
     */
    private function calculate_fingerprint_similarity($fingerprint1, $fingerprint2) {
        // Simple similarity calculation - can be enhanced
        $len1 = strlen($fingerprint1);
        $len2 = strlen($fingerprint2);
        
        if ($len1 !== $len2) {
            return 0;
        }
        
        $matches = 0;
        for ($i = 0; $i < $len1; $i++) {
            if ($fingerprint1[$i] === $fingerprint2[$i]) {
                $matches++;
            }
        }
        
        return $matches / $len1;
    }
    
    /**
     * Handle invalid handshake
     */
    private function handle_invalid_handshake($instance_id) {
        global $wpdb;
        
        // Get instance details
        $instance = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}{$this->instance_table} WHERE instance_id = %s",
            $instance_id
        ));
        
        if (!$instance) {
            return;
        }
        
        // Increment failed handshake count
        $failed_count = get_option("cybersec_failed_handshakes_{$instance_id}", 0) + 1;
        update_option("cybersec_failed_handshakes_{$instance_id}", $failed_count);
        
        // Deactivate instance after 3 failed attempts
        if ($failed_count >= 3) {
            $this->deactivate_instance($instance_id);
            $this->log_security_event('instance_deactivated', 'high', array(
                'instance_id' => $instance_id,
                'user_id' => $instance->user_id,
                'reason' => 'Multiple failed handshakes'
            ));
        }
    }
    
    /**
     * Deactivate user instance
     */
    public function deactivate_instance($instance_id) {
        global $wpdb;
        
        $wpdb->update(
            "{$wpdb->prefix}{$this->instance_table}",
            array('is_active' => false),
            array('instance_id' => $instance_id)
        );
        
        // Clean up failed handshake count
        delete_option("cybersec_failed_handshakes_{$instance_id}");
    }
    
    /**
     * Refresh handshake key
     */
    public function refresh_handshake_key($instance_id) {
        global $wpdb;
        
        $new_handshake_key = $this->generate_handshake_key();
        
        $wpdb->update(
            "{$wpdb->prefix}{$this->instance_table}",
            array('handshake_key' => $new_handshake_key),
            array('instance_id' => $instance_id)
        );
        
        $this->log_handshake($instance_id, 'refresh', array('new_key' => $new_handshake_key), true);
        
        return $new_handshake_key;
    }
    
    /**
     * Get user active instances
     */
    public function get_user_instances($user_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, d.trust_score, d.is_trusted 
             FROM {$wpdb->prefix}{$this->instance_table} i
             LEFT JOIN {$wpdb->prefix}{$this->device_table} d ON i.device_id = d.device_id
             WHERE i.user_id = %s AND i.is_active = 1
             ORDER BY i.last_handshake DESC",
            $user_id
        ));
    }
    
    /**
     * Log handshake attempt
     */
    private function log_handshake($instance_id, $type, $data, $success, $response_time = null) {
        global $wpdb;
        
        $wpdb->insert(
            "{$wpdb->prefix}{$this->handshake_table}",
            array(
                'instance_id' => $instance_id,
                'handshake_type' => $type,
                'handshake_data' => json_encode($data),
                'is_successful' => $success,
                'response_time_ms' => $response_time,
                'ip_address' => $this->get_client_ip()
            )
        );
    }
    
    /**
     * Generate unique instance ID
     */
    private function generate_instance_id() {
        return 'inst_' . bin2hex(random_bytes(16));
    }
    
    /**
     * Generate device ID from fingerprint
     */
    private function generate_device_id($device_fingerprint) {
        return 'dev_' . substr(hash('sha256', $device_fingerprint), 0, 16);
    }
    
    /**
     * Generate session token
     */
    private function generate_session_token() {
        return 'sess_' . bin2hex(random_bytes(32));
    }
    
    /**
     * Generate handshake key
     */
    private function generate_handshake_key() {
        return 'hs_' . bin2hex(random_bytes(16));
    }
    
    /**
     * Get client IP address
     */
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
    
    /**
     * Log security event
     */
    private function log_security_event($type, $severity, $data) {
        $event = array(
            'timestamp' => time(),
            'type' => $type,
            'severity' => $severity,
            'data' => $data,
            'ip' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
        
        $events = get_option('cybersec_security_events', array());
        $events[] = $event;
        
        if (count($events) > 5000) {
            $events = array_slice($events, -5000);
        }
        
        update_option('cybersec_security_events', $events);
    }
}