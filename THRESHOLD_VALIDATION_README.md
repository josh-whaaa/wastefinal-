# Request Threshold Validation System

## Overview
The Request Threshold Validation System is a comprehensive solution designed to prevent spam, abuse, and ensure fair resource allocation in the CEMO (City Environmental Management Office) system. It automatically validates client requests against configurable thresholds before allowing submission.

## Features

### 🛡️ **Validation Checks**
- **Daily Request Limit** - Prevents clients from submitting too many requests per day
- **Hourly Request Limit** - Prevents rapid-fire submissions
- **Weekly Request Limit** - Controls weekly request volume
- **Minimum Time Between Requests** - Enforces cooldown periods
- **Maximum Requests Per Date** - Prevents overbooking specific dates
- **Spam Detection** - Identifies potentially spammy requests using keyword filtering
- **Duplicate Request Check** - Prevents identical requests from the same client
- **Business Hours Validation** - Ensures requests are within working hours

### ⚙️ **Configuration Management**
- **Admin Dashboard** - Easy-to-use interface for managing all threshold settings
- **Real-time Statistics** - Live view of request counts and system status
- **Flexible Settings** - All thresholds are easily adjustable
- **Reset to Defaults** - Quick restoration of original settings

### 📊 **Monitoring & Analytics**
- **Request Statistics** - Track total, daily, hourly, and weekly request counts
- **Validation Status** - Visual indicators showing system status
- **Client Statistics** - Individual client request tracking

## File Structure

```
├── includes/
│   └── request_threshold_validator.php    # Core validation class
├── admin_management/
│   └── threshold_settings.php             # Admin configuration interface
├── client_management/
│   ├── submit_request.php                 # Enhanced with validation
│   └── client_request.php                 # Updated with status display
├── sidebar/
│   └── admin_sidebar.php                  # Added threshold settings link
└── test_threshold_validation.php          # Test page for demonstration
```

## Usage

### For Administrators

1. **Access Settings**
   - Navigate to "Threshold Settings" in the admin sidebar
   - View current request statistics
   - Adjust validation parameters as needed

2. **Configure Thresholds**
   - Set daily, hourly, and weekly request limits
   - Configure business hours for time validation
   - Add/remove spam detection words
   - Enable/disable specific validation checks

3. **Monitor System**
   - View real-time request statistics
   - Check validation status on dashboard
   - Track system performance

### For Clients

1. **Submit Requests**
   - Validation happens automatically
   - Clear error messages for failed validations
   - Warnings for suspicious content

2. **View Status**
   - Validation status indicator on request form
   - Real-time feedback on request limits

## Default Configuration

```php
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
```

## API Reference

### RequestThresholdValidator Class

#### Methods

**`validateRequest($requestData)`**
- Validates a client request against all configured thresholds
- Returns array with validation results
- Parameters: `$requestData` - Array containing request information
- Returns: Array with 'valid', 'errors', and 'warnings' keys

**`getClientStats($client_id, $date)`**
- Gets current request statistics for a specific client
- Parameters: `$client_id` - Client ID, `$date` - Date (optional)
- Returns: Array with daily, weekly, and hourly counts

**`updateConfig($new_config)`**
- Updates validator configuration
- Parameters: `$new_config` - Array of new configuration values

**`getConfig()`**
- Gets current configuration
- Returns: Array of current configuration values

## Error Messages

### Validation Errors
- "Daily request limit exceeded. You can only submit X requests per day."
- "Hourly request limit exceeded. You can only submit X request per hour."
- "Weekly request limit exceeded. You can only submit X requests per week."
- "Please wait X minutes between requests."
- "Maximum requests for this date exceeded. Only X requests allowed per date."
- "Request time must be between X and Y."
- "A similar request already exists. Please check your previous requests."

### Warnings
- "Your request description contains words that might indicate spam. Please provide more specific details."

## Testing

Use the test page (`test_threshold_validation.php`) to:
- Test different validation scenarios
- View current configuration
- Check client statistics
- Verify system functionality

## Customization

### Adding New Validation Rules

1. Add new validation method to `RequestThresholdValidator` class
2. Include the method in `validateRequest()` function
3. Add configuration options to admin interface
4. Update default configuration

### Modifying UI

1. Update `threshold_settings.php` for admin interface changes
2. Modify `client_request.php` for client-side display
3. Adjust dashboard widgets in `admin_dashboard.php`

## Security Considerations

- All input validation is performed server-side
- Configuration changes require admin authentication
- Request data is sanitized before processing
- SQL injection protection through prepared statements

## Performance

- Efficient database queries with proper indexing
- Minimal overhead on request processing
- Cached configuration for better performance
- Optimized validation logic

## Troubleshooting

### Common Issues

1. **Validation not working**
   - Check if `request_threshold_validator.php` is included
   - Verify database connection
   - Ensure proper file permissions

2. **Configuration not saving**
   - Check admin session authentication
   - Verify form submission
   - Check for JavaScript errors

3. **False positive spam detection**
   - Review spam detection words
   - Adjust keyword sensitivity
   - Add exceptions for legitimate terms

## Support

For technical support or feature requests, please refer to the main CEMO system documentation or contact the development team.

---

**Version:** 1.0  
**Last Updated:** January 2025  
**Compatibility:** PHP 7.4+, MySQL 5.7+
