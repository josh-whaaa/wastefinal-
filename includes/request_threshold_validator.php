<?php
/**
 * Request Threshold Validation System
 * 
 * This class provides various validation checks for client requests
 * to prevent spam, abuse, and ensure fair resource allocation.
 */

class RequestThresholdValidator {
    private $pdo;
    private $config;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->config = $this->getDefaultConfig();
    }
    
    /**
     * Get default configuration for threshold limits
     */
    private function getDefaultConfig() {
        return [
            'daily_request_limit' => 3,           // Max requests per client per day
            'hourly_request_limit' => 1,          // Max requests per client per hour
            'weekly_request_limit' => 10,         // Max requests per client per week
            'min_time_between_requests' => 30,    // Minutes between requests
            'max_requests_per_date' => 5,         // Max total requests for a specific date
            'spam_detection_words' => [           // Words that might indicate spam
                'test', 'testing', 'spam', 'fake', 'dummy'
            ],
            'enable_duplicate_check' => true,     // Check for duplicate requests
            'enable_time_validation' => true,     // Validate request times
            'business_hours' => [                 // Allowed request times
                'start' => '08:00',
                'end' => '17:00'
            ]
        ];
    }
    
    /**
     * Validate a client request against all configured thresholds
     * 
     * @param array $requestData The request data to validate
     * @return array Validation result with success status and messages
     */
    public function validateRequest($requestData) {
        $client_id = $requestData['client_id'];
        $request_date = $requestData['request_date'];
        $request_time = $requestData['request_time'];
        $request_description = $requestData['request_description'] ?? '';
        
        $errors = [];
        $warnings = [];
        
        // 1. Check daily request limit
        if (!$this->checkDailyLimit($client_id, $request_date)) {
            $errors[] = "Daily request limit exceeded. You can only submit {$this->config['daily_request_limit']} requests per day.";
        }
        
        // 2. Check hourly request limit
        if (!$this->checkHourlyLimit($client_id)) {
            $errors[] = "Hourly request limit exceeded. You can only submit {$this->config['hourly_request_limit']} request per hour.";
        }
        
        // 3. Check weekly request limit
        if (!$this->checkWeeklyLimit($client_id, $request_date)) {
            $errors[] = "Weekly request limit exceeded. You can only submit {$this->config['weekly_request_limit']} requests per week.";
        }
        
        // 4. Check minimum time between requests
        if (!$this->checkMinTimeBetweenRequests($client_id)) {
            $errors[] = "Please wait {$this->config['min_time_between_requests']} minutes between requests.";
        }
        
        // 5. Check maximum requests per date
        if (!$this->checkMaxRequestsPerDate($request_date)) {
            $errors[] = "Maximum requests for this date exceeded. Only {$this->config['max_requests_per_date']} requests allowed per date.";
        }
        
        // 6. Check for spam indicators
        if ($this->checkSpamIndicators($request_description)) {
            $warnings[] = "Your request description contains words that might indicate spam. Please provide more specific details.";
        }
        
        // 7. Check for duplicate requests
        if ($this->config['enable_duplicate_check'] && $this->checkDuplicateRequest($requestData)) {
            $errors[] = "A similar request already exists. Please check your previous requests.";
        }
        
        // 8. Validate request time
        if ($this->config['enable_time_validation'] && !$this->validateRequestTime($request_time)) {
            $errors[] = "Request time must be between {$this->config['business_hours']['start']} and {$this->config['business_hours']['end']}.";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    /**
     * Check if client has exceeded daily request limit
     */
    private function checkDailyLimit($client_id, $request_date) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM client_requests 
            WHERE client_id = ? AND DATE(request_date) = ? AND status != 'rejected'
        ");
        $stmt->execute([$client_id, $request_date]);
        $result = $stmt->fetch();
        
        return $result['count'] < $this->config['daily_request_limit'];
    }
    
    /**
     * Check if client has exceeded hourly request limit
     */
    private function checkHourlyLimit($client_id) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM client_requests 
            WHERE client_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$client_id]);
        $result = $stmt->fetch();
        
        return $result['count'] < $this->config['hourly_request_limit'];
    }
    
    /**
     * Check if client has exceeded weekly request limit
     */
    private function checkWeeklyLimit($client_id, $request_date) {
        $week_start = date('Y-m-d', strtotime('monday this week', strtotime($request_date)));
        $week_end = date('Y-m-d', strtotime('sunday this week', strtotime($request_date)));
        
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM client_requests 
            WHERE client_id = ? AND DATE(created_at) BETWEEN ? AND ? AND status != 'rejected'
        ");
        $stmt->execute([$client_id, $week_start, $week_end]);
        $result = $stmt->fetch();
        
        return $result['count'] < $this->config['weekly_request_limit'];
    }
    
    /**
     * Check minimum time between requests
     */
    private function checkMinTimeBetweenRequests($client_id) {
        $stmt = $this->pdo->prepare("
            SELECT MAX(created_at) as last_request 
            FROM client_requests 
            WHERE client_id = ? AND status != 'rejected'
        ");
        $stmt->execute([$client_id]);
        $result = $stmt->fetch();
        
        if (!$result['last_request']) {
            return true; // No previous requests
        }
        
        $last_request_time = strtotime($result['last_request']);
        $min_interval = $this->config['min_time_between_requests'] * 60; // Convert to seconds
        
        return (time() - $last_request_time) >= $min_interval;
    }
    
    /**
     * Check maximum requests per specific date
     */
    private function checkMaxRequestsPerDate($request_date) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM client_requests 
            WHERE DATE(request_date) = ? AND status != 'rejected'
        ");
        $stmt->execute([$request_date]);
        $result = $stmt->fetch();
        
        return $result['count'] < $this->config['max_requests_per_date'];
    }
    
    /**
     * Check for spam indicators in request description
     */
    private function checkSpamIndicators($description) {
        $description_lower = strtolower($description);
        
        foreach ($this->config['spam_detection_words'] as $word) {
            if (strpos($description_lower, $word) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check for duplicate requests
     */
    private function checkDuplicateRequest($requestData) {
        $client_id = $requestData['client_id'];
        $request_details = $requestData['request_details'];
        $request_date = $requestData['request_date'];
        
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM client_requests 
            WHERE client_id = ? AND request_details = ? AND request_date = ? 
            AND status IN ('pending', 'approved') AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute([$client_id, $request_details, $request_date]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
    
    /**
     * Validate request time is within business hours
     */
    private function validateRequestTime($request_time) {
        $request_hour = (int)date('H', strtotime($request_time));
        $start_hour = (int)date('H', strtotime($this->config['business_hours']['start']));
        $end_hour = (int)date('H', strtotime($this->config['business_hours']['end']));
        
        return $request_hour >= $start_hour && $request_hour <= $end_hour;
    }
    
    /**
     * Get current request statistics for a client
     */
    public function getClientStats($client_id, $date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        $stats = [];
        
        // Daily count
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM client_requests 
            WHERE client_id = ? AND DATE(request_date) = ? AND status != 'rejected'
        ");
        $stmt->execute([$client_id, $date]);
        $stats['daily'] = $stmt->fetch()['count'];
        
        // Weekly count
        $week_start = date('Y-m-d', strtotime('monday this week', strtotime($date)));
        $week_end = date('Y-m-d', strtotime('sunday this week', strtotime($date)));
        
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM client_requests 
            WHERE client_id = ? AND DATE(created_at) BETWEEN ? AND ? AND status != 'rejected'
        ");
        $stmt->execute([$client_id, $week_start, $week_end]);
        $stats['weekly'] = $stmt->fetch()['count'];
        
        // Hourly count
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM client_requests 
            WHERE client_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$client_id]);
        $stats['hourly'] = $stmt->fetch()['count'];
        
        return $stats;
    }
    
    /**
     * Update configuration (for admin use)
     */
    public function updateConfig($new_config) {
        $this->config = array_merge($this->config, $new_config);
    }
    
    /**
     * Get current configuration
     */
    public function getConfig() {
        return $this->config;
    }
}
?>
