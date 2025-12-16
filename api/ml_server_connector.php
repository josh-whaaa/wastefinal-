<?php
/**
 * Enhanced ML Server Connector for CEMO System
 * Connects to external ML server (Heroku) with fallback to local Python
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/conn.php';

class MLServerConnector {
    private $mlServerUrl;
    private $timeout;
    private $fallbackEnabled;
    
    public function __construct($serverUrl = null, $timeout = 30, $fallbackEnabled = true) {
        $this->mlServerUrl = $serverUrl ?: 'https://wastetracker-3e73822f0171.herokuapp.com';
        $this->timeout = $timeout;
        $this->fallbackEnabled = $fallbackEnabled;
    }
    
    /**
     * Send data to ML server and get predictions
     */
    public function predict($endpoint, $data) {
        $fullUrl = $this->mlServerUrl . '/' . ltrim($endpoint, '/');
        
        try {
            // Try ML server first
            $result = $this->callMLServer($fullUrl, $data);
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'data' => $result['data'],
                    'source' => 'ml_server',
                    'server_url' => $fullUrl,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
            
            // If ML server fails and fallback is enabled
            if ($this->fallbackEnabled) {
                return $this->fallbackToLocal($data);
            }
            
            return [
                'success' => false,
                'error' => 'ML server failed and fallback disabled',
                'server_error' => $result['error'] ?? 'Unknown error'
            ];
            
        } catch (Exception $e) {
            // If ML server is unreachable and fallback is enabled
            if ($this->fallbackEnabled) {
                return $this->fallbackToLocal($data);
            }
            
            return [
                'success' => false,
                'error' => 'ML server unreachable: ' . $e->getMessage(),
                'fallback_available' => $this->fallbackEnabled
            ];
        }
    }
    
    /**
     * Call external ML server
     */
    private function callMLServer($url, $data) {
        $jsonData = json_encode($data);
        
        $options = [
            'http' => [
                'header' => [
                    "Content-Type: application/json",
                    "User-Agent: CEMO-System/1.0",
                    "Accept: application/json"
                ],
                'method' => 'POST',
                'content' => $jsonData,
                'timeout' => $this->timeout,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];
        
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        
        if ($result === false) {
            return [
                'success' => false,
                'error' => 'Failed to connect to ML server'
            ];
        }
        
        // Check HTTP response code
        $httpCode = $this->getHttpResponseCode($http_response_header ?? []);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $decodedResult = json_decode($result, true);
            return [
                'success' => true,
                'data' => $decodedResult,
                'http_code' => $httpCode
            ];
        } else {
            return [
                'success' => false,
                'error' => "ML server returned HTTP $httpCode",
                'response' => $result
            ];
        }
    }
    
    /**
     * Fallback to local Python ML
     */
    private function fallbackToLocal($data) {
        try {
            // Determine which local script to use based on data structure
            $scriptPath = $this->determineLocalScript($data);
            
            if (!$scriptPath || !file_exists($scriptPath)) {
                return [
                    'success' => false,
                    'error' => 'Local Python script not found',
                    'script_path' => $scriptPath
                ];
            }
            
            // Execute local Python script
            $result = $this->executeLocalPython($scriptPath, $data);
            
            return [
                'success' => $result['success'],
                'data' => $result['data'],
                'source' => 'local_python',
                'script_path' => $scriptPath,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Local fallback failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Determine which local Python script to use
     */
    private function determineLocalScript($data) {
        // Check if this is waste forecasting data
        if (isset($data['barangay_data']) && isset($data['lookback_days'])) {
            return __DIR__ . '/../pyhton/ml_waste_forecaster.py';
        }
        
        // Check if this is health risk data
        if (isset($data['health_risk_data']) || isset($data['risk_features'])) {
            return __DIR__ . '/../pyhton/ml_health_risk_classifier.py';
        }
        
        // Default to waste forecaster
        return __DIR__ . '/../pyhton/ml_waste_forecaster.py';
    }
    
    /**
     * Execute local Python script
     */
    private function executeLocalPython($scriptPath, $data) {
        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin
            1 => ["pipe", "w"],  // stdout
            2 => ["pipe", "w"]   // stderr
        ];
        
        $pythonCommands = [
            "py " . escapeshellarg($scriptPath) . " 2>nul",
            "python " . escapeshellarg($scriptPath) . " 2>nul",
            "python3 " . escapeshellarg($scriptPath) . " 2>nul"
        ];
        
        foreach ($pythonCommands as $command) {
            $process = proc_open($command, $descriptorspec, $pipes);
            
            if (is_resource($process)) {
                // Write input data
                fwrite($pipes[0], json_encode($data));
                fclose($pipes[0]);
                
                // Read output
                $output = stream_get_contents($pipes[1]);
                $error = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                
                $returnValue = proc_close($process);
                
                if ($returnValue === 0 && !empty($output)) {
                    $decodedOutput = json_decode($output, true);
                    return [
                        'success' => true,
                        'data' => $decodedOutput
                    ];
                }
            }
        }
        
        return [
            'success' => false,
            'error' => 'All Python commands failed'
        ];
    }
    
    /**
     * Get HTTP response code from headers
     */
    private function getHttpResponseCode($headers) {
        if (empty($headers)) return 0;
        
        $statusLine = $headers[0];
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $statusLine, $matches);
        return isset($matches[1]) ? (int)$matches[1] : 0;
    }
    
    /**
     * Test ML server connectivity
     */
    public function testConnection() {
        $testData = [
            'test' => true,
            'timestamp' => time()
        ];
        
        try {
            $result = $this->callMLServer($this->mlServerUrl . '/health', $testData);
            return [
                'success' => $result['success'],
                'server_url' => $this->mlServerUrl,
                'response_time' => microtime(true),
                'error' => $result['error'] ?? null
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'server_url' => $this->mlServerUrl,
                'error' => $e->getMessage()
            ];
        }
    }
}

// Handle different endpoints
$endpoint = $_GET['endpoint'] ?? 'predict';
$action = $_GET['action'] ?? 'predict';

try {
    $connector = new MLServerConnector();
    
    switch ($action) {
        case 'test':
            // Test ML server connection
            $result = $connector->testConnection();
            echo json_encode($result, JSON_PRETTY_PRINT);
            break;
            
        case 'predict':
        default:
            // Get data from request
            $inputData = null;
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $rawInput = file_get_contents('php://input');
                $inputData = json_decode($rawInput, true);
            } else {
                // For GET requests, build data from parameters
                $inputData = [
                    'input1' => $_GET['input1'] ?? 100,
                    'input2' => $_GET['input2'] ?? 50,
                    'lookback_days' => $_GET['lookback_days'] ?? 14
                ];
            }
            
            if (!$inputData) {
                throw new Exception('No input data provided');
            }
            
            // Send to ML server
            $result = $connector->predict($endpoint, $inputData);
            echo json_encode($result, JSON_PRETTY_PRINT);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>
