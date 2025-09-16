<?php
/**
 * CyberSec URL Controller
 * Controls allowed URLs and routing for the platform
 */

class CyberSecURLController {
    
    private $allowed_urls;
    private $platform_routes;
    
    public function __construct() {
        $this->initialize_allowed_urls();
        $this->initialize_platform_routes();
        add_action('init', array($this, 'handle_routing'), 1);
        add_action('template_redirect', array($this, 'check_url_access'));
    }
    
    private function initialize_allowed_urls() {
        $this->allowed_urls = array(
            // Public URLs (no authentication required)
            'home' => array(
                'pattern' => '/^\/$/',  // Homepage only
                'methods' => array('GET'),
                'auth_required' => false
            ),
            'login' => array(
                'pattern' => '/^\/login\/?$/',  // Login page
                'methods' => array('GET', 'POST'),
                'auth_required' => false
            ),
            'register' => array(
                'pattern' => '/^\/register\/?$/',  // Registration page
                'methods' => array('GET', 'POST'),
                'auth_required' => false
            ),
            'api_auth' => array(
                'pattern' => '/^\/api\/auth\/?$/',  // Authentication API
                'methods' => array('POST'),
                'auth_required' => false
            ),
            'api_handshake' => array(
                'pattern' => '/^\/api\/handshake\/?$/',  // Handshake API
                'methods' => array('POST'),
                'auth_required' => false
            ),
            'api_device' => array(
                'pattern' => '/^\/api\/device\/?$/',  // Device registration API
                'methods' => array('POST'),
                'auth_required' => false
            ),
            
            // Protected URLs (authentication required)
            'dashboard' => array(
                'pattern' => '/^\/dashboard\/?$/',  // User dashboard
                'methods' => array('GET'),
                'auth_required' => true
            ),
            'profile' => array(
                'pattern' => '/^\/profile\/?$/',  // User profile
                'methods' => array('GET', 'POST'),
                'auth_required' => true
            ),
            'teams' => array(
                'pattern' => '/^\/teams\/?$/',  // Teams page
                'methods' => array('GET'),
                'auth_required' => true
            ),
            'my_team' => array(
                'pattern' => '/^\/my-team\/?$/',  // My team page
                'methods' => array('GET', 'POST'),
                'auth_required' => true
            ),
            'competitions' => array(
                'pattern' => '/^\/competitions\/?$/',  // Competitions page
                'methods' => array('GET'),
                'auth_required' => true
            ),
            'competition_detail' => array(
                'pattern' => '/^\/competition\/(\d+)\/?$/',  // Competition detail
                'methods' => array('GET'),
                'auth_required' => true
            ),
            'learning' => array(
                'pattern' => '/^\/learning\/?$/',  // Learning resources
                'methods' => array('GET'),
                'auth_required' => true
            ),
            'leaderboard' => array(
                'pattern' => '/^\/leaderboard\/?$/',  // Leaderboard
                'methods' => array('GET'),
                'auth_required' => true
            ),
            
            // API endpoints (authentication required)
            'api_user' => array(
                'pattern' => '/^\/api\/user\/?$/',  // User API
                'methods' => array('GET', 'POST', 'PUT'),
                'auth_required' => true
            ),
            'api_teams' => array(
                'pattern' => '/^\/api\/teams\/?$/',  // Teams API
                'methods' => array('GET', 'POST'),
                'auth_required' => true
            ),
            'api_competitions' => array(
                'pattern' => '/^\/api\/competitions\/?$/',  // Competitions API
                'methods' => array('GET', 'POST'),
                'auth_required' => true
            ),
            'api_challenges' => array(
                'pattern' => '/^\/api\/challenges\/?$/',  // Challenges API
                'methods' => array('GET', 'POST'),
                'auth_required' => true
            ),
            'api_learning' => array(
                'pattern' => '/^\/api\/learning\/?$/',  // Learning API
                'methods' => array('GET', 'POST'),
                'auth_required' => true
            ),
            'api_leaderboard' => array(
                'pattern' => '/^\/api\/leaderboard\/?$/',  // Leaderboard API
                'methods' => array('GET'),
                'auth_required' => true
            )
        );
    }
    
    private function initialize_platform_routes() {
        $this->platform_routes = array(
            'home' => array(
                'template' => 'homepage',
                'title' => 'CyberSec Platform - Home',
                'description' => 'Welcome to the CyberSec Platform'
            ),
            'login' => array(
                'template' => 'login',
                'title' => 'Login - CyberSec Platform',
                'description' => 'Login to your CyberSec account'
            ),
            'register' => array(
                'template' => 'register',
                'title' => 'Register - CyberSec Platform',
                'description' => 'Create your CyberSec account'
            ),
            'dashboard' => array(
                'template' => 'dashboard',
                'title' => 'Dashboard - CyberSec Platform',
                'description' => 'Your CyberSec dashboard'
            ),
            'profile' => array(
                'template' => 'profile',
                'title' => 'Profile - CyberSec Platform',
                'description' => 'Manage your profile'
            ),
            'teams' => array(
                'template' => 'teams',
                'title' => 'Teams - CyberSec Platform',
                'description' => 'Browse and join teams'
            ),
            'my_team' => array(
                'template' => 'my-team',
                'title' => 'My Team - CyberSec Platform',
                'description' => 'Manage your team'
            ),
            'competitions' => array(
                'template' => 'competitions',
                'title' => 'Competitions - CyberSec Platform',
                'description' => 'View available competitions'
            ),
            'competition_detail' => array(
                'template' => 'competition-detail',
                'title' => 'Competition - CyberSec Platform',
                'description' => 'Competition details'
            ),
            'learning' => array(
                'template' => 'learning',
                'title' => 'Learning - CyberSec Platform',
                'description' => 'Learning resources and courses'
            ),
            'leaderboard' => array(
                'template' => 'leaderboard',
                'title' => 'Leaderboard - CyberSec Platform',
                'description' => 'Platform leaderboard'
            )
        );
    }
    
    public function handle_routing() {
        $request_uri = $_SERVER['REQUEST_URI'];
        $request_method = $_SERVER['REQUEST_METHOD'];
        
        // Remove query string and trailing slash
        $clean_uri = rtrim(strtok($request_uri, '?'), '/');
        if (empty($clean_uri)) {
            $clean_uri = '/';
        }
        
        // Check if URL is allowed
        $matched_route = $this->match_route($clean_uri, $request_method);
        
        if (!$matched_route) {
            $this->handle_unauthorized_access($clean_uri, $request_method);
            return;
        }
        
        // Check authentication requirement
        if ($matched_route['auth_required']) {
            $auth_result = $this->verify_authentication();
            if (!$auth_result['success']) {
                $this->handle_authentication_required($auth_result['error']);
                return;
            }
        }
        
        // Handle the request
        $this->process_request($matched_route, $clean_uri);
    }
    
    private function match_route($uri, $method) {
        foreach ($this->allowed_urls as $route_name => $route_config) {
            if (preg_match($route_config['pattern'], $uri) && 
                in_array($method, $route_config['methods'])) {
                return array_merge($route_config, array('name' => $route_name));
            }
        }
        return false;
    }
    
    private function verify_authentication() {
        // Check for instance ID and handshake data
        $instance_id = $_SERVER['HTTP_X_INSTANCE_ID'] ?? $_POST['instance_id'] ?? $_GET['instance_id'] ?? '';
        $handshake_data = $_POST['handshake_data'] ?? $_GET['handshake_data'] ?? array();
        
        if (empty($instance_id)) {
            return array('success' => false, 'error' => 'Missing instance ID');
        }
        
        // Verify handshake with instance manager
        $instance_manager = new CyberSecInstanceManager();
        $handshake_result = $instance_manager->verify_handshake($instance_id, $handshake_data);
        
        if (!$handshake_result['success']) {
            return array('success' => false, 'error' => $handshake_result['error']);
        }
        
        // Set user context
        wp_set_current_user($handshake_result['user_id']);
        
        return array('success' => true, 'user_id' => $handshake_result['user_id']);
    }
    
    private function process_request($route, $uri) {
        $route_name = $route['name'];
        
        // Handle API requests
        if (strpos($route_name, 'api_') === 0) {
            $this->handle_api_request($route_name, $uri);
            return;
        }
        
        // Handle page requests
        if (isset($this->platform_routes[$route_name])) {
            $this->handle_page_request($route_name, $uri);
            return;
        }
        
        // Default handling
        $this->handle_default_request($route_name, $uri);
    }
    
    private function handle_api_request($route_name, $uri) {
        // Set JSON response headers
        header('Content-Type: application/json');
        
        // Route to appropriate API handler
        switch ($route_name) {
            case 'api_auth':
                $this->handle_auth_api();
                break;
            case 'api_handshake':
                $this->handle_handshake_api();
                break;
            case 'api_device':
                $this->handle_device_api();
                break;
            case 'api_user':
                $this->handle_user_api();
                break;
            case 'api_teams':
                $this->handle_teams_api();
                break;
            case 'api_competitions':
                $this->handle_competitions_api();
                break;
            case 'api_challenges':
                $this->handle_challenges_api();
                break;
            case 'api_learning':
                $this->handle_learning_api();
                break;
            case 'api_leaderboard':
                $this->handle_leaderboard_api();
                break;
            default:
                $this->send_error_response('Unknown API endpoint', 404);
        }
    }
    
    private function handle_page_request($route_name, $uri) {
        $route_config = $this->platform_routes[$route_name];
        
        // Set page title and meta
        add_filter('wp_title', function() use ($route_config) {
            return $route_config['title'];
        });
        
        add_action('wp_head', function() use ($route_config) {
            echo '<meta name="description" content="' . esc_attr($route_config['description']) . '">';
        });
        
        // Load the appropriate template
        $template_file = CYBERSEC_GATEWAY_PLUGIN_DIR . 'templates/' . $route_config['template'] . '.php';
        
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            $this->handle_template_not_found($route_name);
        }
        
        exit; // Stop WordPress from loading default template
    }
    
    private function handle_unauthorized_access($uri, $method) {
        // Log unauthorized access attempt
        $this->log_security_event('unauthorized_url_access', 'high', array(
            'uri' => $uri,
            'method' => $method,
            'ip' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ));
        
        // Block the request
        http_response_code(403);
        die('Access Denied - Unauthorized URL');
    }
    
    private function handle_authentication_required($error) {
        // Redirect to login page
        wp_redirect('/login?error=' . urlencode($error));
        exit;
    }
    
    private function handle_auth_api() {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'login':
                $this->process_login();
                break;
            case 'register':
                $this->process_registration();
                break;
            case 'logout':
                $this->process_logout();
                break;
            default:
                $this->send_error_response('Invalid action', 400);
        }
    }
    
    private function process_login() {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $device_fingerprint = $_POST['device_fingerprint'] ?? '';
        $device_info = $_POST['device_info'] ?? array();
        
        if (empty($username) || empty($password) || empty($device_fingerprint)) {
            $this->send_error_response('Missing required fields', 400);
            return;
        }
        
        // Authenticate user
        $user = wp_authenticate($username, $password);
        
        if (is_wp_error($user)) {
            $this->log_security_event('failed_login', 'medium', array(
                'username' => $username,
                'ip' => $this->get_client_ip(),
                'error' => $user->get_error_message()
            ));
            
            $this->send_error_response('Invalid credentials', 401);
            return;
        }
        
        // Create user instance
        $instance_manager = new CyberSecInstanceManager();
        $instance_data = $instance_manager->create_user_instance(
            $user->ID,
            $device_fingerprint,
            $device_info
        );
        
        $this->send_success_response(array(
            'message' => 'Login successful',
            'user' => array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name
            ),
            'instance' => $instance_data
        ));
    }
    
    private function process_registration() {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $device_fingerprint = $_POST['device_fingerprint'] ?? '';
        $device_info = $_POST['device_info'] ?? array();
        
        if (empty($username) || empty($email) || empty($password) || empty($device_fingerprint)) {
            $this->send_error_response('Missing required fields', 400);
            return;
        }
        
        // Validate input
        if (!is_email($email)) {
            $this->send_error_response('Invalid email address', 400);
            return;
        }
        
        if (strlen($password) < 8) {
            $this->send_error_response('Password must be at least 8 characters', 400);
            return;
        }
        
        // Check if user already exists
        if (username_exists($username) || email_exists($email)) {
            $this->send_error_response('Username or email already exists', 409);
            return;
        }
        
        // Create user
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            $this->send_error_response('Registration failed: ' . $user_id->get_error_message(), 500);
            return;
        }
        
        // Create user instance
        $instance_manager = new CyberSecInstanceManager();
        $instance_data = $instance_manager->create_user_instance(
            $user_id,
            $device_fingerprint,
            $device_info
        );
        
        $this->send_success_response(array(
            'message' => 'Registration successful',
            'user' => array(
                'id' => $user_id,
                'username' => $username,
                'email' => $email
            ),
            'instance' => $instance_data
        ));
    }
    
    private function send_success_response($data) {
        http_response_code(200);
        echo json_encode(array('success' => true, 'data' => $data));
        exit;
    }
    
    private function send_error_response($message, $code = 400) {
        http_response_code($code);
        echo json_encode(array('success' => false, 'error' => $message));
        exit;
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