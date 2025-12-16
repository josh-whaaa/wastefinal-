<?php
/**
 * ML Server Configuration for CEMO System
 * Centralized configuration for ML server connections
 */

return [
    // ML Server Settings
    'ml_server' => [
        'url' => 'https://wastetracker-3e73822f0171.herokuapp.com',
        'timeout' => 30,
        'retry_attempts' => 3,
        'retry_delay' => 2, // seconds
        'verify_ssl' => false, // Set to true in production
        'user_agent' => 'CEMO-System/1.0'
    ],
    
    // Endpoints
    'endpoints' => [
        'predict' => '/predict',
        'health' => '/health',
        'forecast' => '/forecast',
        'classify' => '/classify'
    ],
    
    // Fallback Settings
    'fallback' => [
        'enabled' => true,
        'local_scripts' => [
            'waste_forecast' => __DIR__ . '/../pyhton/ml_waste_forecaster.py',
            'health_risk' => __DIR__ . '/../pyhton/ml_health_risk_classifier.py'
        ],
        'python_commands' => [
            'py',
            'python',
            'python3',
            'python3.9',
            'python3.8'
        ]
    ],
    
    // Data Processing
    'data_processing' => [
        'max_lookback_days' => 365,
        'min_lookback_days' => 1,
        'default_lookback_days' => 35,
        'max_barangays' => 50,
        'data_validation' => true
    ],
    
    // Logging
    'logging' => [
        'enabled' => true,
        'log_file' => __DIR__ . '/../logs/ml_server.log',
        'log_level' => 'INFO', // DEBUG, INFO, WARNING, ERROR
        'log_requests' => true,
        'log_responses' => true
    ],
    
    // Performance
    'performance' => [
        'cache_enabled' => false,
        'cache_ttl' => 300, // 5 minutes
        'max_response_size' => 10485760, // 10MB
        'compression' => true
    ],
    
    // Security
    'security' => [
        'rate_limiting' => [
            'enabled' => true,
            'max_requests_per_minute' => 60,
            'max_requests_per_hour' => 1000
        ],
        'input_validation' => true,
        'output_sanitization' => true
    ]
];
?>
