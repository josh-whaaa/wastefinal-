<?php
/**
 * Remote ML Client for Heroku ML Server
 * Handles communication with remote ML services
 */

class MLRemoteClient {
    private $mlServerUrl;
    private $timeout;
    private $debug;
    
    public function __construct($mlServerUrl = null, $timeout = null, $debug = null) {
        // Load configuration
        require_once __DIR__ . '/../config/ml_server.php';
        
        $this->mlServerUrl = $mlServerUrl ? rtrim($mlServerUrl, '/') : ML_SERVER_URL;
        $this->timeout = $timeout ?? ML_SERVER_TIMEOUT;
        $this->debug = $debug ?? ML_SERVER_DEBUG;
    }
    
    /**
     * Call waste forecasting API on remote ML server
     */
    public function getWasteForecast($barangayData, $lookbackDays = 35) {
        $endpoint = $this->mlServerUrl . ML_ENDPOINT_WASTE_FORECAST;
        
        $payload = [
            'lookback_days' => $lookbackDays,
            'barangay_data' => $barangayData
        ];
        
        return $this->makeRequest($endpoint, $payload);
    }
    
    /**
     * Call health risk analysis API on remote ML server
     */
    public function getHealthRiskAnalysis($barangayData, $lookbackDays = 14) {
        $endpoint = $this->mlServerUrl . ML_ENDPOINT_HEALTH_RISK;
        
        $payload = [
            'lookback_days' => $lookbackDays,
            'barangay_data' => $barangayData
        ];
        
        return $this->makeRequest($endpoint, $payload);
    }
    
    /**
     * Check if ML server is available
     */
    public function checkServerHealth() {
        $endpoint = $this->mlServerUrl . ML_ENDPOINT_HEALTH_CHECK;
        
        try {
            $response = $this->makeRequest($endpoint, [], 'GET');
            return [
                'available' => true,
                'response' => $response
            ];
        } catch (Exception $e) {
            return [
                'available' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Make HTTP request to ML server
     */
    private function makeRequest($url, $data = [], $method = 'POST') {
        $ch = curl_init();
        
        // Basic cURL options
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'CEMO-PHP-Client/1.0',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ]
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        // Handle cURL errors
        if ($response === false) {
            throw new Exception("cURL Error: " . $error);
        }
        
        // Handle HTTP errors
        if ($httpCode >= 400) {
            throw new Exception("HTTP Error $httpCode: " . $response);
        }
        
        // Parse JSON response
        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response: " . json_last_error_msg());
        }
        
        if ($this->debug) {
            error_log("ML Server Request: $url");
            error_log("ML Server Response: " . $response);
        }
        
        return $decodedResponse;
    }
    
    /**
     * Get server configuration info
     */
    public function getServerInfo() {
        return [
            'ml_server_url' => $this->mlServerUrl,
            'timeout' => $this->timeout,
            'debug' => $this->debug
        ];
    }
}
?>
