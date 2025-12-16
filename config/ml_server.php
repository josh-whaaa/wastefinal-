<?php
/**
 * ML Server Configuration
 * Centralized configuration for ML server endpoints
 */

// ML Server Configuration
define('ML_SERVER_URL', 'https://wastetracker-3e73822f0171.herokuapp.com');
define('ML_SERVER_TIMEOUT', 60);
define('ML_SERVER_DEBUG', true);

// API Endpoints
define('ML_ENDPOINT_WASTE_FORECAST', '/api/waste-forecast');
define('ML_ENDPOINT_HEALTH_RISK', '/api/health-risk');
define('ML_ENDPOINT_HEALTH_CHECK', '/health');

// Fallback Configuration
define('ML_FALLBACK_ENABLED', true);
define('ML_FALLBACK_TIMEOUT', 30);

// Get ML server configuration
function getMLServerConfig() {
    return [
        'url' => ML_SERVER_URL,
        'timeout' => ML_SERVER_TIMEOUT,
        'debug' => ML_SERVER_DEBUG,
        'endpoints' => [
            'waste_forecast' => ML_SERVER_URL . ML_ENDPOINT_WASTE_FORECAST,
            'health_risk' => ML_SERVER_URL . ML_ENDPOINT_HEALTH_RISK,
            'health_check' => ML_SERVER_URL . ML_ENDPOINT_HEALTH_CHECK
        ],
        'fallback' => [
            'enabled' => ML_FALLBACK_ENABLED,
            'timeout' => ML_FALLBACK_TIMEOUT
        ]
    ];
}

// Check if ML server is accessible
function checkMLServerHealth() {
    $config = getMLServerConfig();
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $config['endpoints']['health_check'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'CEMO-PHP-HealthCheck/1.0'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'accessible' => $response !== false && $httpCode < 400,
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error,
        'url' => $config['endpoints']['health_check']
    ];
}

// Get ML server status
function getMLServerStatus() {
    $health = checkMLServerHealth();
    $config = getMLServerConfig();
    
    return [
        'server_url' => $config['url'],
        'accessible' => $health['accessible'],
        'last_check' => date('Y-m-d H:i:s'),
        'health_check' => $health,
        'configuration' => $config
    ];
}
?>
