# Client Request System - Enhanced Features

## Overview
The client request system has been completely enhanced with the following new features:

### 🎯 Key Features

#### 1. **Auto-Fill Client Credentials**
- Automatically populates client information from the database
- Shows client name, email, contact, and barangay
- No need for clients to re-enter their information

#### 2. **Date Selection with Calendar**
- Interactive calendar showing available/unavailable dates
- Weekends are automatically marked as unavailable
- Clients can only select available dates
- Visual indicators for available (green) and unavailable (red) dates

#### 3. **Dynamic Service Requirements**
- When clients select a service type, specific requirements are displayed
- Different requirements for different services:
  - **Grass-Cutting**: Clear area, secure pets, remove valuables
  - **Garbage Collection**: Segregate waste, proper placement, sealed bags
  - **Cutting of Trees**: Permits, clear area, check power lines, equipment access
  - **Pruning of Trees**: Clear area, check power lines, equipment access
  - **Street Cleaning**: Move vehicles, clear obstacles, ensure drainage
  - **Drainage Maintenance**: Clear area, ensure access, remove blockages

#### 4. **Admin Approval System**
- Admins receive notifications for new requests
- Admin can approve or reject requests with notes
- Approved requests appear in admin calendar
- Real-time status updates

#### 5. **Client Notification System**
- Clients receive notifications when requests are approved/rejected
- Notification center shows all client requests and status
- Unread notification counter
- Mark notifications as read functionality

## 📁 Files Created/Modified

### New Files:
1. **`client_management/client_request.php`** - Enhanced request form
2. **`client_management/submit_request.php`** - Request submission handler
3. **`client_management/client_notifications.php`** - Client notification center
4. **`admin_management/admin_requests.php`** - Admin request management
5. **`database_tables.sql`** - Database schema

### Modified Files:
1. **`sidebar/admin_sidebar.php`** - Added Service Requests link
2. **`sidebar/client_sidebar.php`** - Added Notifications link

## 🗄️ Database Tables

### 1. `client_requests`
- Stores all client service requests
- Includes client info, request details, preferred date, status
- Tracks creation and update timestamps

### 2. `admin_notifications`
- Notifies admins of new requests
- Tracks read/unread status
- Links to related request IDs

### 3. `client_notifications`
- Notifies clients of request status changes
- Tracks read/unread status
- Links to related request IDs

### 4. `service_requirements`
- Stores requirements for each service type
- Allows for dynamic requirement display
- Easy to add new requirements

### 5. `available_dates`
- Manages available/unavailable dates
- Automatically marks weekends as unavailable
- Can be extended for holidays or maintenance days

## 🚀 Installation Instructions

### 1. Database Setup
Run the SQL commands in `database_tables.sql` to create the necessary tables:

```sql
-- Execute the SQL file in your database
source database_tables.sql;
```

### 2. File Placement
Ensure all new files are placed in their correct directories:
- Client files in `client_management/`
- Admin files in `admin_management/`
- Database file in root directory

### 3. Permissions
Ensure the web server has read/write permissions for the directories.

## 📋 Usage Guide

### For Clients:

1. **Submit Request**:
   - Navigate to "Request Event" in sidebar
   - Client information is auto-filled
   - Select service type from dropdown
   - View requirements for selected service
   - Choose preferred date from calendar
   - Add additional details
   - Submit request

2. **Check Notifications**:
   - Navigate to "Notifications" in sidebar
   - View all notifications and request status
   - Mark notifications as read
   - Track request approval/rejection

### For Admins:

1. **Manage Requests**:
   - Navigate to "Service Requests" in admin sidebar
   - View all pending requests
   - Approve or reject requests with notes
   - See approved requests in calendar view

2. **Notifications**:
   - Receive automatic notifications for new requests
   - Notifications are marked as read when processed

## 🎨 Features in Detail

### Calendar System
- **Available Dates**: Green circles, clickable
- **Unavailable Dates**: Red circles, non-clickable
- **Selected Date**: Blue border around selected date
- **Weekend Restriction**: Saturdays and Sundays automatically unavailable

### Dynamic Requirements
- **Grass-Cutting**: 3 requirements
- **Garbage Collection**: 3 requirements
- **Cutting of Trees**: 4 requirements (including permits)
- **Pruning of Trees**: 3 requirements
- **Street Cleaning**: 3 requirements
- **Drainage Maintenance**: 3 requirements

### Notification System
- **Real-time Updates**: Instant notifications for status changes
- **Unread Counter**: Shows number of unread notifications
- **Mark as Read**: One-click to mark notifications as read
- **Request History**: Complete history of all requests

## 🔧 Customization

### Adding New Service Types
1. Add to dropdown in `client_request.php`
2. Add requirements to `service_requirements` table
3. Update JavaScript requirements object

### Modifying Available Dates
1. Update `available_dates` table
2. Modify `getAvailableDates()` function in `client_request.php`

### Adding New Requirements
1. Insert into `service_requirements` table
2. Update JavaScript requirements object

## 🐛 Troubleshooting

### Common Issues:

1. **Calendar not showing**: Check if Flatpickr CSS/JS is loaded
2. **Auto-fill not working**: Verify client session is active
3. **Notifications not appearing**: Check database connection and table structure
4. **Date validation errors**: Ensure date format is YYYY-MM-DD

### Debug Steps:
1. Check browser console for JavaScript errors
2. Verify database tables exist and have correct structure
3. Check PHP error logs for server-side issues
4. Ensure all file paths are correct

## 📞 Support

For issues or questions:
1. Check the troubleshooting section above
2. Verify all files are in correct locations
3. Ensure database tables are created properly
4. Check file permissions

## 🔄 Future Enhancements

Potential improvements:
- Email notifications
- SMS notifications
- File upload for supporting documents
- Advanced calendar with time slots
- Bulk request processing
- Request templates
- Mobile app integration 