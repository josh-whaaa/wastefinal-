<?php
session_start();
include '../includes/header.php';
include '../includes/conn.php';

// Check if client is logged in
if (!isset($_SESSION['client_id'])) {
    header("Location: ../index.php");
    exit();
}

// Get client information
$client_id = $_SESSION['client_id'];
$stmt = $pdo->prepare("SELECT * FROM client_table WHERE client_id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();

if (!$client) {
    header("Location: ../login_page/sign-in.php");
    exit();
}

// Get available dates (excluding weekends and holidays)
function getAvailableDates() {
    $available_dates = [];
    $current_date = new DateTime();
    $end_date = new DateTime();
    $end_date->add(new DateInterval('P30D')); // 30 days from now
    
    while ($current_date <= $end_date) {
        $day_of_week = $current_date->format('N'); // 1 (Monday) through 7 (Sunday)
        
        // Available on weekdays (Monday to Friday)
        if ($day_of_week >= 1 && $day_of_week <= 5) {
            $available_dates[] = $current_date->format('Y-m-d');
        }
        
        $current_date->add(new DateInterval('P1D'));
    }
    
    return $available_dates;
}

$available_dates = getAvailableDates();

if (isset($_POST['action']) && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    $admin_notes = $_POST['admin_notes'];

    // Define status based on action
    if ($action === 'approve') {
        $status = 'approved';
    } elseif ($action === 'reject') {
        $status = 'rejected';
    } else {
        $status = 'pending'; // fallback
    }

    // Set this to use in alert
    $new_status = ucfirst($status);

    $stmt = $conn->prepare("UPDATE client_requests SET status = ?, admin_notes = ? WHERE request_id = ?");
    $stmt->bind_param("ssi", $status, $admin_notes, $request_id);
    if ($stmt->execute()) {
        $_SESSION['client_alert'] = [
            'title' => 'Success!',
            'text' => "Request has been $new_status.",
            'icon' => 'success'
        ];
    } else {
        $_SESSION['client_alert'] = [
            'title' => 'Error!',
            'text' => 'Something went wrong while updating the request.',
            'icon' => 'error'
        ];
    }
    header("Location: client_request.php"); // << Add this
exit();
}
$page_title = "Client Request Form";
?>
<body class="bg-light">
    <!-- Sidebar -->
    <?php include '../sidebar/client_sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <!-- Navbar -->
        <?php include '../includes/navbar.php'; ?>

        <div class="container-fluid py-4">
            <h1 class="h3 mb-4 text-gray-800"></h1>
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-lg">
                        <div class="card-header p-0 position-relative mt-n4 mx-4 z-index-2">
                        <div style="background: linear-gradient(60deg, #66c05eff, #49755cff 100%);" class="shadow-dark border-radius-lg pt-4 pb-3">
                                <h5 class="text-white text-center text-uppercase font-weight-bold mb-0">
                                    <i class="fas fa-clipboard-list me-2"></i>Service Request Form
                                </h5>
                                <p class="text-white text-center mb-0">Submit your service request and schedule</p>
                            </div>
                        </div>
                            
                        <div class="form-body">
                            <?php if (isset($_SESSION['msg'])): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i><?= $_SESSION['msg']; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php unset($_SESSION['msg']); ?>
                            <?php endif; ?>

                            <!-- Client Information Display -->
                            <div class="client-info">
                                <h6><i class="fas fa-user me-2"></i>Client Information</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-row">
                                            <span class="info-label">Full Name:</span>
                                            <span class="info-value"><?= htmlspecialchars($client['first_name'] . ' ' . $client['last_name']) ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Email:</span>
                                            <span class="info-value"><?= htmlspecialchars($client['email']) ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-row">
                                            <span class="info-label">Contact:</span>
                                            <span class="info-value"><?= htmlspecialchars($client['contact']) ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Barangay:</span>
                                            <span class="info-value"><?= htmlspecialchars($client['barangay']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Request Validation Status -->
                            <div class="validation-status mb-4">
                                <div class="card border-left-info shadow-sm">
                                    <div class="card-body py-2">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <h6 class="mb-1 text-info">
                                                    <i class="fas fa-shield-alt me-2"></i>Request Validation Status
                                                </h6>
                                                <small class="text-muted">Your request will be validated against system thresholds</small>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <span class="badge bg-info" id="validationBadge">Active</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form method="POST" action="submit_request.php" id="requestForm">
                                <input type="hidden" name="client_id" value="<?= $client_id ?>">
                                <input type="hidden" name="client_name" value="<?= htmlspecialchars($client['first_name'] . ' ' . $client['last_name']) ?>">
                                <input type="hidden" name="client_email" value="<?= htmlspecialchars($client['email']) ?>">
                                <input type="hidden" name="client_contact" value="<?= htmlspecialchars($client['contact']) ?>">
                                <input type="hidden" name="client_barangay" value="<?= htmlspecialchars($client['barangay']) ?>">

                                <div class="row">
                                    <div class="col-md-8">
                                        <!-- Request Type Selection -->
                                        <div class="mb-4">
                                            <label for="request_type" class="form-label fw-bold">
                                                <i class="fas fa-tasks me-2"></i>Service Type
                                            </label>
                                            <select class="form-select form-select-lg" id="request_type" name="request_type" required onchange="showRequirements()">
                                                <option value="">-- Select Service Type --</option>
                                                <option value="Grass-Cutting">Grass-Cutting</option>
                                                <option value="Garbage Collection">Garbage Collection</option>
                                                <option value="Cutting of Trees">Cutting of Trees</option>
                                                <option value="Pruning of Trees">Pruning of Trees</option>
                                                <option value="Street Cleaning">Street Cleaning</option>
                                                <option value="Drainage Maintenance">Drainage Maintenance</option>
                                                <option value="Other">Other (please specify)</option>
                                            </select>
                                        </div>

                                        <!-- Dynamic Requirements Box -->
                                        <div id="requirementsBox" class="requirements-box" style="display: none;">
                                            <h6><i class="fas fa-info-circle me-2"></i>Requirements for this service:</h6>
                                            <div id="requirementsList"></div>
                                        </div>

                                        <!-- Other Request Details -->
                                        <div class="mb-4" id="otherRequestDiv" style="display: none;">
                                            <label for="other_request" class="form-label fw-bold">
                                                <i class="fas fa-edit me-2"></i>Please specify your request
                                            </label>
                                            <textarea class="form-control" id="other_request" name="other_request" rows="3" placeholder="Please provide detailed description of your request..."></textarea>
                                        </div>

                                        <!-- Request Description -->
                                        <div class="mb-4">
                                            <label for="request_description" class="form-label fw-bold">
                                                <i class="fas fa-comment me-2"></i>Additional Details
                                            </label>
                                            <textarea class="form-control" id="request_description" name="request_description" rows="4" placeholder="Please provide any additional details about your request..."></textarea>
                                        </div>

                                        <!-- Preferred Date Selection -->
                                        <div class="mb-4">
                                            <label for="request_date" class="form-label fw-bold">
                                                <i class="fas fa-calendar me-2"></i>Preferred Date
                                            </label>
                                            <input type="text" class="form-control" id="request_date" name="request_date" placeholder="Select your preferred date" required readonly>
                                        </div>

                                        <!-- Preferred Time Selection -->
                                        <div class="mb-4">
                                            <label for="request_time" class="form-label fw-bold">
                                                <i class="fas fa-clock me-2"></i>Preferred Time
                                            </label>
                                            <select class="form-control" id="request_time" name="request_time" required>
                                                <option value="" disabled selected>Select a preferred time</option>
                                                <option value="08:00">8:00 AM</option>
                                                <option value="09:00">9:00 AM</option>
                                                <option value="10:00">10:00 AM</option>
                                                <option value="11:00">11:00 AM</option>
                                                <option value="12:00">12:00 PM</option>
                                                <option value="13:00">1:00 PM</option>
                                                <option value="14:00">2:00 PM</option>
                                            </select>
                                        </div>


                                        <!-- Submit Button -->
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                                <i class="fas fa-paper-plane me-2"></i>Submit Request
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Calendar Section -->
                                    <div class="col-md-4">
                                        <div class="simple-calendar-container">
                                            <h6 class="text-center mb-3">
                                                <i class="fas fa-calendar-alt me-2"></i>Service Calendar
                                            </h6>
                                            <div class="text-center mb-3">
                                                <small class="text-muted">
                                                    <span class="holiday-indicator me-2">●</span>Holiday
                                                    <span class="available-indicator ms-2">●</span>Available
                                                </small>
                                            </div>
                                            <!-- Simple Calendar Grid -->
                                            <div id="simpleCalendar" class="simple-calendar"></div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <?php include '../includes/footer.php'; ?>
    </main>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!-- Your other scripts -->
    
<script>
        // Available dates from PHP
        const availableDates = <?= json_encode($available_dates) ?>;
        
        const serviceRequirements = {
            'Grass-Cutting': [
                'Clear area of any obstacles',
                'Ensure pets are secured',
                'Remove any valuable items from the area'
            ],
            'Garbage Collection': [
                'Properly segregate waste',
                'Place garbage in designated area',
                'Ensure bags are properly sealed'
            ],
            'Cutting of Trees': [
                'Obtain necessary permits',
                'Clear area around the tree',
                'Ensure no power lines nearby',
                'Provide access for equipment'
            ],
            'Pruning of Trees': [
                'Clear area around the tree',
                'Ensure no power lines nearby',
                'Provide access for equipment'
            ],
            'Street Cleaning': [
                'Move vehicles from the street',
                'Clear any obstacles',
                'Ensure proper drainage'
            ],
            'Drainage Maintenance': [
                'Clear area around drainage',
                'Ensure proper access',
                'Remove any blockages if possible'
            ]
        };

        // Holiday dates (you can modify this list)
        const holidayDates = [
            '2024-01-01', // New Year's Day
            '2024-01-15', // Martin Luther King Jr. Day
            '2024-02-19', // Presidents' Day
            '2024-03-29', // Good Friday
            '2024-05-27', // Memorial Day
            '2024-06-19', // Juneteenth
            '2024-07-04', // Independence Day
            '2024-09-02', // Labor Day
            '2024-10-14', // Columbus Day
            '2024-11-11', // Veterans Day
            '2024-11-28', // Thanksgiving
            '2024-12-25', // Christmas Day
            '2025-01-01', // New Year's Day 2025
            '2025-01-20', // Martin Luther King Jr. Day 2025
            '2025-02-17', // Presidents' Day 2025
            '2025-04-18', // Good Friday 2025
            '2025-05-26', // Memorial Day 2025
            '2025-06-19', // Juneteenth 2025
            '2025-07-04', // Independence Day 2025
            '2025-09-01', // Labor Day 2025
            '2025-10-13', // Columbus Day 2025
            '2025-11-11', // Veterans Day 2025
            '2025-11-27', // Thanksgiving 2025
            '2025-12-25'  // Christmas Day 2025
        ];

        // Initialize simple calendar
        function initializeSimpleCalendar() {
            const calendar = document.getElementById('simpleCalendar');
            const currentDate = new Date();
            const currentMonth = currentDate.getMonth();
            const currentYear = currentDate.getFullYear();
            
            let calendarHTML = `
                <div class="calendar-header mb-3">
                    <button class="btn btn-sm btn-outline-secondary" onclick="previousMonth()">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span class="fw-bold mx-3">${getMonthName(currentMonth)} ${currentYear}</span>
                    <button class="btn btn-sm btn-outline-secondary" onclick="nextMonth()">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="calendar-weekdays mb-2">
                    <span class="weekday">S</span>
                    <span class="weekday">M</span>
                    <span class="weekday">T</span>
                    <span class="weekday">W</span>
                    <span class="weekday">T</span>
                    <span class="weekday">F</span>
                    <span class="weekday">S</span>
                </div>
            `;
            
            // Generate calendar days
            const firstDay = new Date(currentYear, currentMonth, 1);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());
            
            for (let week = 0; week < 6; week++) {
                calendarHTML += '<div class="calendar-week">';
                for (let day = 0; day < 7; day++) {
                    const currentDay = new Date(startDate);
                    currentDay.setDate(startDate.getDate() + (week * 7) + day);
                    
                    const dateString = currentDay.toISOString().split('T')[0];
                    const isCurrentMonth = currentDay.getMonth() === currentMonth;
                    const isHoliday = holidayDates.includes(dateString);
                    const isToday = dateString === new Date().toISOString().split('T')[0];
                    const isPast = new Date(dateString) < new Date().setHours(0,0,0,0);
                    const isWeekend = currentDay.getDay() === 0 || currentDay.getDay() === 6;
                    const isAvailable = availableDates.includes(dateString);
                    
                    let dayClass = 'simple-day';
                    if (!isCurrentMonth) {
                        dayClass += ' other-month';
                    } else if (isHoliday) {
                        dayClass += ' holiday-day';
                    } else if (isPast) {
                        dayClass += ' past-day';
                    } else if (isWeekend) {
                        dayClass += ' weekend-day';
                    } else if (isAvailable) {
                        dayClass += ' available-day';
                    } else {
                        dayClass += ' unavailable-day';
                    }
                    
                    if (isToday) {
                        dayClass += ' today';
                    }
                    
                    calendarHTML += `<span class="${dayClass}" onclick="selectSimpleDate('${dateString}')" title="${dateString}">${currentDay.getDate()}</span>`;
                }
                calendarHTML += '</div>';
            }
            
            calendar.innerHTML = calendarHTML;
        }

        function getMonthName(month) {
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 
                          'July', 'August', 'September', 'October', 'November', 'December'];
            return months[month];
        }

        // Global variables for calendar navigation
        let currentMonth = new Date().getMonth();
        let currentYear = new Date().getFullYear();

        function previousMonth() {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            initializeSimpleCalendar();
        }

        function nextMonth() {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            initializeSimpleCalendar();
        }

        function selectSimpleDate(dateString) {
            // Check if date is available (not a holiday, not in the past, and in available dates)
            const isHoliday = holidayDates.includes(dateString);
            const isPast = new Date(dateString) < new Date().setHours(0,0,0,0);
            const isWeekend = new Date(dateString).getDay() === 0 || new Date(dateString).getDay() === 6;
            const isAvailable = availableDates.includes(dateString);
            
            if (!isHoliday && !isPast && !isWeekend && isAvailable) {
                // Set the date in the form
                document.getElementById('request_date').value = dateString;
                
                // Update visual selection
                document.querySelectorAll('.simple-day').forEach(day => {
                    day.classList.remove('selected-day');
                });
                event.target.classList.add('selected-day');
                
                // Show success feedback
                showDateSelectionFeedback(dateString);
            } else if (isHoliday) {
                showDateSelectionFeedback(dateString, 'holiday');
            } else if (isPast) {
                showDateSelectionFeedback(dateString, 'past');
            } else if (isWeekend) {
                showDateSelectionFeedback(dateString, 'weekend');
            } else if (!isAvailable) {
                showDateSelectionFeedback(dateString, 'unavailable');
            }
        }

        function showDateSelectionFeedback(dateString, type = 'success') {
            const feedbackMessages = {
                'success': `Selected: ${new Date(dateString).toLocaleDateString()}`,
                'holiday': 'This date is a holiday - service not available',
                'past': 'Cannot select past dates',
                'weekend': 'Service not available on weekends',
                'unavailable': 'This date is not available for service'
            };
            
            // Create or update feedback element
            let feedbackEl = document.getElementById('date-feedback');
            if (!feedbackEl) {
                feedbackEl = document.createElement('div');
                feedbackEl.id = 'date-feedback';
                feedbackEl.className = 'mt-2 text-center';
                document.querySelector('.simple-calendar-container').appendChild(feedbackEl);
            }
            
            feedbackEl.innerHTML = `
                <small class="alert ${type === 'success' ? 'alert-success' : 'alert-warning'} alert-sm d-inline-block">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-1"></i>
                    ${feedbackMessages[type]}
                </small>
            `;
            
            // Auto-hide after 3 seconds for success messages
            if (type === 'success') {
                setTimeout(() => {
                    if (feedbackEl) feedbackEl.innerHTML = '';
                }, 3000);
            }
        }

        function showRequirements() {
            const requestType = document.getElementById('request_type').value;
            const requirementsBox = document.getElementById('requirementsBox');
            const requirementsList = document.getElementById('requirementsList');
            const otherRequestDiv = document.getElementById('otherRequestDiv');
            const otherRequest = document.getElementById('other_request');
            
            if (requestType === 'Other') {
                requirementsBox.style.display = 'none';
                otherRequestDiv.style.display = 'block';
                otherRequest.required = true;
            } else if (requestType && serviceRequirements[requestType]) {
                requirementsBox.style.display = 'block';
                otherRequestDiv.style.display = 'none';
                otherRequest.required = false;
                
                let requirementsHTML = '<ul class="mb-0">';
                serviceRequirements[requestType].forEach(req => {
                    requirementsHTML += `<li>${req}</li>`;
                });
                requirementsHTML += '</ul>';
                requirementsList.innerHTML = requirementsHTML;
            } else {
                requirementsBox.style.display = 'none';
                otherRequestDiv.style.display = 'none';
                otherRequest.required = false;
            }
        }

        // Initialize flatpickr for date input
        flatpickr("#request_date", {
            dateFormat: "Y-m-d",
            minDate: "today",
            disable: [
                function(date) {
                    const dateString = date.toISOString().split('T')[0];
                    // Disable weekends, holidays, and unavailable dates
                    return (date.getDay() === 0 || date.getDay() === 6) || 
                           holidayDates.includes(dateString) || 
                           !availableDates.includes(dateString);
                }
            ],
            onChange: function(selectedDates, dateStr) {
                // Update calendar selection
                document.querySelectorAll('.simple-day').forEach(day => {
                    day.classList.remove('selected-day');
                    if (day.getAttribute('title') === dateStr) {
                        day.classList.add('selected-day');
                    }
                });
            }
        });

        // Initialize calendar on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeSimpleCalendar();
        });

        // Form validation
        document.getElementById('requestForm').addEventListener('submit', function(e) {
            const requestType = document.getElementById('request_type').value;
            const preferredDate = document.getElementById('request_date').value;
            const preferredTime = document.getElementById('request_time').value;
            const otherRequest = document.getElementById('other_request').value;
            
            if (!requestType) {
                e.preventDefault();
                alert('Please select a service type.');
                return false;
            }
            
            if (requestType === 'Other' && !otherRequest.trim()) {
                e.preventDefault();
                alert('Please specify your request details.');
                return false;
            }
            
            if (!preferredDate) {
                e.preventDefault();
                alert('Please select a preferred date.');
                return false;
            }

            if (!preferredTime) {
                e.preventDefault();
                alert('Please select a preferred time.');
                return false;
            }

            if (!availableDates.includes(preferredDate)) {
                e.preventDefault();
                alert('Please select an available date.');
                return false;
            }
        });
    </script>

    <!-- ✅ Place SweetAlert notification here -->
 <?php if (isset($_SESSION['msg'])): ?>
    <?php 
    $msg = $_SESSION['msg']; 
    unset($_SESSION['msg']); 

    // Determine icon based on content or message type
    $icon = strpos(strtolower($msg), 'error') !== false || strpos(strtolower($msg), 'failed') !== false ? 'error' : 'success';
    ?>
    <script>
        Swal.fire({
            title: <?= $icon === 'success' ? '""' : '"Notice"' ?>,
            text: <?= json_encode($msg) ?>,
            icon: '<?= $icon ?>',
            confirmButtonText: 'OK'
        });
    </script>
<?php endif; ?>

<?php if (isset($_SESSION['client_alert'])): ?>
    <?php 
    $alert = $_SESSION['client_alert']; 
    unset($_SESSION['client_alert']); 
    ?>
    <script>
        Swal.fire({
            title: <?= json_encode($alert['title']) ?>,
            text: <?= json_encode($alert['text']) ?>,
            icon: <?= json_encode($alert['icon']) ?>,
            confirmButtonText: 'OK'
        });
    </script>
<?php endif; ?>
</body>
</html>
    <style>
        .simple-calendar-container {
            background: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            margin-bottom: 10px;
        }
        
        .weekday {
            text-align: center;
            font-weight: 600;
            color: #6c757d;
            font-size: 12px;
            padding: 5px;
        }
        
        .calendar-week {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            margin-bottom: 5px;
        }
        
        .simple-day {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }
        
        .simple-day:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .other-month {
            color: #adb5bd;
            background: #f8f9fa;
        }
        
        .available-day {
            background: #e8f5e8;
            color: #2d5a2d;
            border: 1px solid #c3e6c3;
        }
        
        .available-day:hover {
            background: #d4edda;
            border-color: #28a745;
        }
        
        .holiday-day {
            background: #dc3545;
            color: white;
            font-weight: 600;
        }
        
        .holiday-day:hover {
            background: #c82333;
            transform: scale(1.05);
        }
        
        .past-day {
            background: #f8f9fa;
            color: #adb5bd;
            cursor: not-allowed;
        }
        
        .past-day:hover {
            transform: none;
            box-shadow: none;
        }
        
        .today {
            border: 2px solid #007bff;
            font-weight: 600;
        }
        
        .holiday-indicator {
            color: #dc3545;
            font-size: 16px;
        }
        
        .available-indicator {
            color: #28a745;
            font-size: 16px;
        }
        
        .weekend-day {
            background: #f8f9fa;
            color: #adb5bd;
            cursor: not-allowed;
        }
        
        .weekend-day:hover {
            transform: none;
            box-shadow: none;
        }
        
        .unavailable-day {
            background: #f8f9fa;
            color: #adb5bd;
            cursor: not-allowed;
        }
        
        .unavailable-day:hover {
            transform: none;
            box-shadow: none;
        }
        
        .selected-day {
            background: #007bff !important;
            color: white !important;
            border: 2px solid #0056b3 !important;
            font-weight: 600;
        }
        
        .selected-day:hover {
            background: #0056b3 !important;
        }
        .requirements-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .form-header {
            background: linear-gradient(135deg, #66c05eff, #49755cff 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .form-body {
            padding: 30px;
        }
        .client-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .client-info h6 {
            color: #495057;
            margin-bottom: 15px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-label {
            font-weight: 600;
            color: #6c757d;
        }
        .info-value {
            color: #495057;
        }
        /* Ensure navbar z-index is proper */
        .navbar-main {
            z-index: 1030;
            backdrop-filter: saturate(200%) blur(30px);
            background-color: rgba(255, 255, 255, 0.8) !important;
        }
        
        /* Fix dropdown positioning */
        .dropdown-menu {
            z-index: 1040;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border-radius: 0.75rem;
            margin-top: 0.5rem;
        }
        
        .dropdown-item {
            padding: 0.75rem 1.25rem;
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            margin: 0 0.5rem;
            transform: translateX(5px);
        }
        
        /* Toast positioning */
        .toast {
            min-width: 350px;
        }
        
        /* Mobile sidebar toggle styling */
        .sidenav-toggler-inner {
            cursor: pointer;
        }
        
        /* Ensure Font Awesome icons are visible */
        .fa-solid, .fa-regular {
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 900;
        }
        
        .fa-regular {
            font-weight: 400 !important;
        }
        
        /* Fix badge positioning */
        .nav-item .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            z-index: 1;
        }
        
        /* Breadcrumb styling */
        .breadcrumb-item + .breadcrumb-item::before {
            content: "/";
            color: #adb5bd;
        }
        
        /* User info styling */
        .nav-link.dropdown-toggle::after {
            display: none;
        }
        
        /* Success indicator dot */
        .bg-success {
            background-color: #28a745 !important;
        }

    </style>