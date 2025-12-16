<?php
session_start();
include '../includes/conn.php';

// Check if the user is logged in as an admin
if (!isset($_SESSION['admin_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login_page/sign-in.php");
    exit();
}

$page_title = "Waste Dashboard"; // Set the page title
include '../includes/header.php'; // Includes the head section and styles

// Query to count users in admin_table
$query = "SELECT COUNT(*) AS total_admins FROM admin_table";
$result = mysqli_query($conn, $query);

$totalAdmins = 0;
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $totalAdmins = $row['total_admins'];
}
// Query to count users in client_table

$queryClient = "SELECT COUNT(*) AS total_clients FROM client_table";
$resultClient = mysqli_query($conn, $queryClient);

$totalClients = 0;
if ($resultClient && mysqli_num_rows($resultClient) > 0) {
    $rowClient = mysqli_fetch_assoc($resultClient);
    $totalClients = $rowClient['total_clients'];
}

// Fetch count from driver_waste_uploads table only
$tableCheckToday = $conn->query("SHOW TABLES LIKE 'driver_waste_uploads'");
$hasUploadsTableToday = $tableCheckToday && $tableCheckToday->num_rows > 0;

$tons = 0;
if ($hasUploadsTableToday) {
    // Get today's total waste count from driver_waste_uploads
    $todayDate = date('Y-m-d');
    $query = "SELECT COALESCE(SUM(waste_count), 0) as today_count FROM driver_waste_uploads WHERE DATE(collection_date) = DATE(?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $todayDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $tons = ($row['today_count'] ?? 0) * 0.001;
    $stmt->close();
}

// Monthly sum from driver_waste_uploads table only
$tableCheck = $conn->query("SHOW TABLES LIKE 'driver_waste_uploads'");
$hasUploadsTable = $tableCheck && $tableCheck->num_rows > 0;

$monthlyCount = 0;
if ($hasUploadsTable) {
    // Get monthly waste count from driver_waste_uploads
    $sql = "SELECT COALESCE(SUM(waste_count), 0) AS monthly_count
            FROM driver_waste_uploads
            WHERE MONTH(collection_date) = MONTH(CURDATE())
              AND YEAR(collection_date) = YEAR(CURDATE())";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $monthlyCount = $row['monthly_count'] ?? 0;
}

// Convert count to tons (1 count = 0.001 tons)
$conversionFactor = 0.001; 
$monthlyTons = $monthlyCount * $conversionFactor;

// Initial route collection progress (will be loaded dynamically via JavaScript)
$progress = 0;

?>
<body class="g-sidenav-show bg-gray-100">
    <?php include '../sidebar/admin_sidebar.php'; ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include '../includes/navbar.php'; ?>
        <div class="container-fluid">
            <h1 class="h3 mb-4 text-gray-800"></h1>
            <div class="row">
                <!-- Cards Section -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Collected Waste Today</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="collectedWasteToday">--% volume</div>
                                </div>                            
                                <div class="col-auto">
                                    <i class="material-symbols-rounded opacity-10">delete</i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Forecasted Waste Volume (Next Week)</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="forecastedWasteNextWeek">--% volume</div>
                                </div>
                                <div class="col-auto">
                                    <i class="material-symbols-rounded opacity-10">schedule</i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Route Collection Progress
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="wasteCollectionProgress">
                                        <?php echo number_format($progress, 2) . "%"; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="material-symbols-rounded opacity-10" style="cursor: pointer;" onclick="showRouteProgressModal()" title="View Route Progress Details">bar_chart</i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Monthly Collected Waste
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="monthlyCollectedWaste">
                                        <?php echo number_format($monthlyTons, 2) . " estimated tons"; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="material-symbols-rounded opacity-10">calendar_month</i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            <!-- Charts Section -->
            <div class="row">
                <div class="col-lg-12 col-md-10 mt-4 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center px-4">
                            <div class="text-center flex-grow-1">
                                <h5 class="mb-1 fw-semibold text-success">Waste Collected</h5>
                                <p class="text-muted mb-0">Weekly Waste Collection Performance</p>
                                <div id="selectedWeekDisplay" class="small text-primary mt-1"></div>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-primary dropdown-toggle d-flex align-items-center" 
                                        type="button" 
                                        id="weekDropdown" 
                                        data-bs-toggle="dropdown" 
                                        aria-expanded="false">
                                    <span>View Details</span>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-2" aria-labelledby="weekDropdown" style="width: 350px;">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <button class="btn btn-sm btn-outline-secondary prev-month"><i class="material-symbols-rounded">chevron_left</i></button>
                                    <h6 class="mb-0 fw-semibold month-year">Loading...</h6>
                                    <button class="btn btn-sm btn-outline-secondary next-month"><i class="material-symbols-rounded">chevron_right</i></button>
                                    </div>
                                    <div class="week-grid">
                                        <div class="row row-cols-2 g-2"></div>
                                    </div>
                                    <div class="mt-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0 fw-semibold">Selected Week Details</h6>
                                        </div>
                                        <div class="day-stats">
                                            <div class="row row-cols-7 g-1 text-start mb-2" id="dayStatsLabels"></div>
                                            <div class="row row-cols-7 g-1 text-center" id="dayStatsValues"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-3 pb-1">
                            <div class="chart-container mx-auto" style="position: relative; height:350px; width:100%">
                                <canvas id="chart-bars" class="chart-canvas"></canvas>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-4">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <i class="material-symbols-rounded text-muted me-1 fs-6">schedule</i>
                                    <span class="text-muted small" id="weeklyWasteUpdated">Loading...</span>
                                </div>
                                <button class="btn btn-sm btn-outline-primary" onclick="showBrgyDetails()">
                                    <i class="material-symbols-rounded me-1">visibility</i>
                                    View Barangay Details
                                </button>
                            </div>
                        </div>
                         <!-- Statistics Cards Row -->
                    <div class="row">
                        <!-- Weekly Performance -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Weekly Performance</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="weeklyPerformance">85%</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="material-symbols-rounded opacity-10">bar_chart</i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Waste Collected This Week -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Waste Collected This Week</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="wasteLastWeek">12.4 estimated tons</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="material-symbols-rounded opacity-10">recycling</i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Average Daily Collection -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Average Daily Collection</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="avgDailyCollection">--% waste volume</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="material-symbols-rounded opacity-10">trending_up</i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        
                        <!-- Collection Efficiency -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-danger shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Collection Efficiency</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="collectionEfficiency">92%</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="material-symbols-rounded opacity-10">speed</i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Vehicle Status Section -->
            <div class="row mt-4">
                <div class="col-lg-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white pb-0">
                            <h6 class="mb-0">Vehicle Status</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <div class="text-sm text-secondary">Vehicle</div>
                                            <div id="vehicleName" class="text-dark fw-bold fs-5">—</div>
                                            <div class="text-xs text-secondary mt-1">
                                                <i class="fas fa-id-card"></i> Plate: <span id="vehiclePlateNo">—</span>
                                            </div>
                                            <div class="text-xs text-secondary mt-1">
                                                <i class="fas fa-user"></i> Driver: <span id="driverName">—</span>
                                            </div>
                                        </div>
                                        <span id="vehicleStatus" class="badge bg-secondary fs-6">—</span>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="text-sm text-secondary mb-1">
                                                <i class="fas fa-map-marker-alt"></i> Current Location
                                            </div>
                                            <div id="vehicleLocation" class="text-dark fw-semibold">—</div>
                                            <div class="text-xs text-secondary mt-1">
                                                GPS: <span id="vehicleGPS">—</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="text-sm text-secondary mb-1">
                                                <i class="fas fa-route"></i> Route
                                            </div>
                                            <div id="vehicleRoute" class="text-dark fw-semibold">—</div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="text-sm text-secondary mb-1">
                                                <i class="fas fa-weight"></i> Vehicle Capacity
                                            </div>
                                            <div id="vehicleCapacityInfo" class="text-dark fw-semibold">—</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="text-sm text-secondary mb-1">
                                                <i class="fas fa-chart-line"></i> Waste Collection Today
                                            </div>
                                            <div class="text-dark fw-semibold">
                                                <span id="vehicleCapacityCount">0</span> / <span id="vehicleCapacityMax">1000</span> units
                                            </div>
                                            <div class="text-xs text-secondary mt-1">
                                                Uploads: <span id="vehicleUploadCount">0</span> | 
                                                Last: <span id="vehicleLastUpload">—</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <div class="text-sm text-secondary mb-2">Capacity Level</div>
                                        <div class="text-lg fw-bold mb-2"><span id="vehicleCapacityPercent">0.0</span>%</div>
                                        <div class="vehicle-figure position-relative mx-auto" style="height: 120px; width: 100%; max-width: 200px;">
                                            <div class="water-fill" id="waterFill"></div>
                                            <div class="vehicle-icon">
                                                <i class="fas fa-truck"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12 col-md-8 mt-4 mb-4">
                    <div class="card waste-volume-card">
                        <div class="card-body p-4">
                            <!-- Header Section -->
                            <div class="card-header-section">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title">Brgy Waste Volume</h5>
                                        <p class="card-subtitle">Monthly Waste Volume for Barangay</p>
                                    </div>
                                    <div class="ms-3">
                                        <select id="brgySelect" class="form-select barangay-selector" style="min-width: 180px;"></select>
                                    </div>
                                </div>
                            </div>

                            <!-- Month Selector (shown only when All Brgy is selected) -->
                            <div id="monthSelectorContainer" class="mb-3" style="display: none;">
                                <div class="d-flex align-items-center">
                                    <label for="brgyMonthSelect" class="me-2 fw-semibold">Select Month:</label>
                                    <select id="brgyMonthSelect" class="form-select" style="min-width: 200px;">
                                        <option value="1">January</option>
                                        <option value="2">February</option>
                                        <option value="3">March</option>
                                        <option value="4">April</option>
                                        <option value="5">May</option>
                                        <option value="6">June</option>
                                        <option value="7">July</option>
                                        <option value="8">August</option>
                                        <option value="9">September</option>
                                        <option value="10">October</option>
                                        <option value="11">November</option>
                                        <option value="12">December</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Chart Section -->
                            <div class="chart-container">
                                <canvas id="chart-line" class="demo-canvas"></canvas>
                            </div>

                            <!-- Progress Section -->
                            <div class="progress-section">
                                <div class="progress progress-container" style="height: 20px;">
                                    <div id="brgyProgressBar" class="progress-bar-custom" style="width: 0%;">
                                        <span class="progress-text">0%</span>
                                    </div>
                                </div>
                                <div class="waste-info-text" id="brgyWasteInfo">
                                    Select a barangay to view waste volume data
                                </div>
                            </div>

                            <!-- Footer Section -->
                            <div class="card-footer-section">
                                <div class="update-info">
                                    <span class="material-symbols-rounded update-icon">schedule</span>
                                    <span id="brgyWasteUpdated">Updated just now</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- <div class="col-lg-6 col-md-6 mt-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="mb-0">Dumpsite Area</h6>
                            <p class="text-sm">Yearly Waste Forecast for Dumpsite Capacity</p>
                            <div class="pe-2">
                                <div class="chart">
                                    <canvas id="chart-line-tasks" class="chart-canvas" height="170"></canvas>
                                </div>
                            </div>
                            <hr class="dark horizontal">
                            <div class="d-flex">
                                <i class="material-symbols-rounded text-sm my-auto me-1">schedule</i>
                                <p class="mb-0 text-sm">Just updated</p>
                            </div>
                        </div>
                    </div>
                </div> -->
        </div>
    </div>
</div>

     <!-- Barangay Details Modal -->
    <div class="modal fade" id="brgyDetailsModal" tabindex="-1" aria-labelledby="brgyDetailsModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl modal-fullscreen-lg-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="brgyDetailsModalLabel">
                        <i class="material-symbols-rounded me-2">location_on</i>
                        Barangay Collection Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="material-symbols-rounded text-primary me-2">calendar_today</i>
                                <span id="selectedDateInfo" class="fw-semibold">Select a day to view details</span>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="d-flex align-items-center justify-content-end">
                                <i class="material-symbols-rounded text-success me-2">recycling</i>
                                <span id="totalWasteInfo" class="text-muted">Total: -- tons</span>
                            </div>
                        </div>
                    </div>
                    
                    
                    <!-- Day Selection -->
                    <div class="mb-4">
                        <div class="row row-cols-7 g-2" id="daySelectionGrid">
                            <!-- Days will be populated here -->
                        </div>
                    </div>
                    
                    <!-- Barangay Data Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                                <thead class="table-white">
                                    <tr>
                                        <th class="text-center">#</th>
                                        <th>Barangay</th>
                                        <th class="text-center">Collection Count</th>
                                        <th class="text-center">Tons</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                            <tbody id="brgyDetailsTable">
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="material-symbols-rounded me-2">info</i>
                                        Select a day to view barangay collection details
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="exportBrgyData()">
                        <i class="material-symbols-rounded me-1">download</i>
                        Export Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Route Progress Details Modal -->
    <div class="modal fade" id="routeProgressModal" tabindex="-1" aria-labelledby="routeProgressModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl modal-fullscreen-lg-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="routeProgressModalLabel">
                        <i class="material-symbols-rounded me-2">route</i>
                        Route Collection Progress - <span id="modalWeekRange">Week 1</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Current Week Summary -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <div class="text-muted small mb-1">Total Routes</div>
                                    <div class="h4 fw-bold text-primary" id="modalTotalRoutes">0</div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-muted small mb-1">Completed Routes</div>
                                    <div class="h4 fw-bold text-success" id="modalCompletedRoutes">0</div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-muted small mb-1">Total Trips to Mailum</div>
                                    <div class="h4 fw-bold text-info" id="modalTotalTrips">0</div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-muted small mb-1">Progress</div>
                                    <div class="h4 fw-bold text-danger" id="modalProgress">0%</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Vehicle Routes Table -->
                    <h6 class="mb-3">
                        <i class="material-symbols-rounded me-1">directions_car</i>
                        Vehicle Routes
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Vehicle</th>
                                    <th>Route</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Progress</th>
                                </tr>
                            </thead>
                            <tbody id="routeProgressTable">
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="material-symbols-rounded me-2">sync</i>
                                        Loading route data...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Vehicle Trips to Mailum -->
                    <hr class="my-4">
                    <h6 class="mb-3">
                        <i class="material-symbols-rounded me-1">transfer_within_a_station</i>
                        Vehicle Trips to Mailum
                    </h6>
                    <div class="alert alert-info d-flex align-items-center mb-3">
                        <i class="material-symbols-rounded me-2">info</i>
                        <span class="small">Trips are counted when GPS location enters Mailum boundaries (within 500m). Minimum 5 minutes between trips.</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Vehicle</th>
                                    <th class="text-center">Number of Trips</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="vehicleTripsTable">
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="material-symbols-rounded me-2">sync</i>
                                        Loading trip data...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Historical Data -->
                    <hr class="my-4">
                    <h6 class="mb-3">
                        <i class="material-symbols-rounded me-1">history</i>
                        Historical Weekly Progress
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Week</th>
                                    <th>Date Range</th>
                                    <th class="text-center">Completed</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Progress</th>
                                </tr>
                            </thead>
                            <tbody id="historicalTable">
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-2">
                                        <i class="material-symbols-rounded me-2">sync</i>
                                        Loading historical data...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="exportRouteProgress()">
                        <i class="material-symbols-rounded me-1">download</i>
                        Export Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Include the Footer -->
<?php include '../includes/footer.php'; ?>
</main>
<style>
    /* Vehicle panel styles */
    .vehicle-panel .vehicle-figure {
        height: 120px;
        border-radius: 12px;
        background: #f8fafc;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        position: relative;
    }
    .vehicle-panel .water-fill {
        position: absolute;
        left: 0; 
        right: 0; 
        bottom: 0;
        width: 100%;
        height: 0%;
        min-height: 0%;
        background: linear-gradient(180deg, #4fc3f7 0%, #0288d1 100%);
        transition: height 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        border-radius: 0 0 12px 12px;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        z-index: 1;
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        pointer-events: none;
    }
    .vehicle-panel .water-fill-full {
        background: linear-gradient(180deg, #28a745 0%, #1e7e34 100%);
    }
    .vehicle-panel .water-fill-warning {
        background: linear-gradient(180deg, #ffc107 0%, #e0a800 100%);
    }
    .vehicle-panel .water-fill-normal {
        background: linear-gradient(180deg, #007bff 0%, #0056b3 100%);
    }
    .vehicle-panel .water-fill-rise {
        animation: waterRise 1s ease-out;
    }
    @keyframes waterRise {
        0% {
            transform: translateY(20px);
            opacity: 0.5;
        }
        50% {
            opacity: 0.8;
        }
        100% {
            transform: translateY(0);
            opacity: 1;
        }
    }
    .vehicle-panel .water-fill-pulse {
        animation: waterPulse 0.6s ease-in-out;
    }
    @keyframes waterPulse {
        0%, 100% {
            transform: scaleY(1);
        }
        50% {
            transform: scaleY(1.05);
        }
    }
    .vehicle-panel .vehicle-icon {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #0d47a1;
        font-size: 48px;
        opacity: 0.9;
        pointer-events: none;
        z-index: 2;
    }
    .vehicle-panel .has-data-today {
        font-weight: 600;
    }
</style>
<script>
        // --- Dynamic Dashboard Cards ---
async function loadDashboardSummary() {
    try {
        console.log('Loading dashboard summary...');
        const res = await fetch('../api/get_dashboard_summary.php');
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const data = await res.json();
        console.log('Dashboard summary API response:', data); // Debug log

        if (data.success) {
            // Waste collected today (convert to percentage: multiply by 100)
            const todayEl = document.getElementById('collectedWasteToday');
            if (todayEl) {
                const todayTons = parseFloat(data.todayTons) || 0;
                const todayPercent = todayTons * 100; // Convert to percentage
                todayEl.textContent = todayPercent.toFixed(0) + '% volume';
                console.log('Updated Collected Waste Today:', todayPercent + '%');
            } else {
                console.error('Element collectedWasteToday not found!');
            }

            // Waste last week (default to API value, but can be overridden by week selection)
            const lastWeekEl = document.getElementById('wasteLastWeek');
            if (lastWeekEl) {
                if (typeof window.selectedWeekWaste !== 'undefined') {
                    lastWeekEl.textContent = window.selectedWeekWaste;
                } else {
                    const lastWeekTons = parseFloat(data.lastWeekTons) || 0;
                    lastWeekEl.textContent = lastWeekTons.toFixed(2) + ' tons';
                }
            }

            // Weekly Performance (default to API value, but can be overridden by week selection)
            const weeklyPerfEl = document.getElementById('weeklyPerformance');
            if (weeklyPerfEl) {
                if (typeof window.selectedWeekPerformance !== 'undefined') {
                    weeklyPerfEl.textContent = window.selectedWeekPerformance;
                } else {
                    const weeklyUtil = parseFloat(data.weeklyUtilization) || 0;
                    weeklyPerfEl.textContent = weeklyUtil.toFixed(1) + '%';
                }
            }
        } else {
            throw new Error(data.error || 'Unknown error');
        }
    } catch (e) {
        console.error('Error loading dashboard summary:', e);
        const todayEl = document.getElementById('collectedWasteToday');
        const lastWeekEl = document.getElementById('wasteLastWeek');
        const weeklyPerfEl = document.getElementById('weeklyPerformance');
        if (todayEl) todayEl.textContent = '0% volume';
        if (lastWeekEl) lastWeekEl.textContent = '0.00 tons';
        if (weeklyPerfEl) weeklyPerfEl.textContent = '0.0%';
    }
}

// --- Load Monthly Collected Waste ---
async function loadMonthlyWaste() {
    try {
        const res = await fetch('../api/get_monthly_waste_summary.php');
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const data = await res.json();
        
        if (data.success) {
            const monthlyTons = parseFloat(data.monthlyTons) || 0;
            const monthlyEl = document.getElementById('monthlyCollectedWaste');
            if (monthlyEl) {
                monthlyEl.textContent = monthlyTons.toFixed(2) + ' estimated tons';
            }
        } else {
            console.error('Monthly waste API error:', data.error || 'Unknown error');
        }
    } catch (error) {
        console.error('Error loading monthly waste:', error);
        const monthlyEl = document.getElementById('monthlyCollectedWaste');
        if (monthlyEl) {
            monthlyEl.textContent = '0.00 estimated tons';
        }
    }
}

// --- Load Admin Statistics (based on bin fill percentage) ---
async function loadAdminStatistics() {
    try {
        const res = await fetch('../api/get_admin_statistics.php');
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const data = await res.json();
        
        if (data.success) {
            // Weekly Performance (based on average bin fill percentage)
            const weeklyPerf = parseFloat(data.weekly_performance) || 0;
            const weeklyPerfEl = document.getElementById('weeklyPerformance');
            if (weeklyPerfEl && typeof window.selectedWeekPerformance === 'undefined') {
                weeklyPerfEl.textContent = weeklyPerf.toFixed(1) + '%';
            }
            
            // Waste Collected This Week (convert count to tons: 1 count = 0.001 tons)
            const weeklyCount = parseFloat(data.weekly_waste_count) || 0;
            const weeklyTons = (weeklyCount * 0.001).toFixed(2);
            const wasteLastWeekEl = document.getElementById('wasteLastWeek');
            if (wasteLastWeekEl && typeof window.selectedWeekWaste === 'undefined') {
                wasteLastWeekEl.textContent = weeklyTons + ' estimated tons';
            }
            
            // Average Daily Collection (convert to % waste volume)
            const avgDaily = parseFloat(data.avg_daily_collection) || 0;
            const avgTons = avgDaily * 0.001; // Convert count to tons
            const avgPercentage = (avgTons * 100).toFixed(0); // Convert to percentage
            const avgDailyEl = document.getElementById('avgDailyCollection');
            if (avgDailyEl) {
                avgDailyEl.textContent = avgPercentage + '% waste volume';
            }
            
            // Collection Efficiency (days with collection / 7 days)
            const efficiency = parseFloat(data.collection_efficiency) || 0;
            const efficiencyEl = document.getElementById('collectionEfficiency');
            if (efficiencyEl) {
                efficiencyEl.textContent = efficiency.toFixed(0) + '%';
            }
        } else {
            console.error('Admin statistics API error:', data.error || 'Unknown error');
            // Set default values on error
            const weeklyPerfEl = document.getElementById('weeklyPerformance');
            const wasteLastWeekEl = document.getElementById('wasteLastWeek');
            const avgDailyEl = document.getElementById('avgDailyCollection');
            const efficiencyEl = document.getElementById('collectionEfficiency');
            if (weeklyPerfEl && typeof window.selectedWeekPerformance === 'undefined') weeklyPerfEl.textContent = '0.0%';
            if (wasteLastWeekEl && typeof window.selectedWeekWaste === 'undefined') wasteLastWeekEl.textContent = '0.00 estimated tons';
            if (avgDailyEl) avgDailyEl.textContent = '0% waste volume';
            if (efficiencyEl) efficiencyEl.textContent = '0%';
        }
    } catch (error) {
        console.error('Error loading admin statistics:', error);
        // Set default values on error
        const weeklyPerfEl = document.getElementById('weeklyPerformance');
        const wasteLastWeekEl = document.getElementById('wasteLastWeek');
        const avgDailyEl = document.getElementById('avgDailyCollection');
        const efficiencyEl = document.getElementById('collectionEfficiency');
        if (weeklyPerfEl && typeof window.selectedWeekPerformance === 'undefined') weeklyPerfEl.textContent = '0.0%';
        if (wasteLastWeekEl && typeof window.selectedWeekWaste === 'undefined') wasteLastWeekEl.textContent = '0.00 estimated tons';
        if (avgDailyEl) avgDailyEl.textContent = '0.00 estimated tons';
        if (efficiencyEl) efficiencyEl.textContent = '0%';
    }
}

// --- Waste Forecast Loading ---
async function loadWasteForecast() {
    try {
        console.log('Loading waste forecast...');
        const res = await fetch('../api/get_waste_forecast.php?lookback_days=35');
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const data = await res.json();
        console.log('Waste forecast API response:', data); // Debug log

        if (data.success && data.forecasts && data.forecasts.length > 0) {
            // Calculate total forecasted waste for next week
            const totalForecast = data.forecasts.reduce((sum, forecast) => {
                return sum + (parseFloat(forecast.forecasted_tons) || 0);
            }, 0);
            
            // Update the forecast display
            const forecastEl = document.getElementById('forecastedWasteNextWeek');
            if (forecastEl) {
                forecastEl.textContent = totalForecast.toFixed(2) + '% volume';
                console.log('Updated Forecasted Waste Volume:', totalForecast);
            } else {
                console.error('Element forecastedWasteNextWeek not found!');
            }
            
            // Add model info as tooltip or console log
            console.log('Forecast model:', data.model_info);
            console.log('Total forecasted waste:', totalForecast.toFixed(2), 'tons');
            console.log('Number of barangays with forecasts:', data.forecasts.length);
            
        } else {
            // Fallback: use last 3 weeks average from weekly data
            console.warn('Forecast data not available, using weekly average fallback');
            const forecastEl = document.getElementById('forecastedWasteNextWeek');
            if (forecastEl) {
                forecastEl.textContent = '0.00% volume';
            }
        }
    } catch (error) {
        console.error('Error loading waste forecast:', error);
        const forecastEl = document.getElementById('forecastedWasteNextWeek');
        if (forecastEl) {
            forecastEl.textContent = '0.00% volume';
        }
    }
}

// --- Brgy Waste Volume Dynamic Section ---
let brgyChart;
let brgyList = [];
let selectedBrgy = '';
let brgyChartWeek = null;

async function loadBarangays() {
    try {
        const res = await fetch('../barangay_api/get_barangays.php');
        const data = await res.json();
        if (Array.isArray(data)) {
            brgyList = data.map(b => ({ brgy_id: b.brgy_id, barangay: b.barangay }));
            const select = document.getElementById('brgySelect');
            select.innerHTML = '<option value="">Select Barangay</option><option value="all">All Brgy</option>' + brgyList.map(b => `<option value="${b.brgy_id}">${b.barangay}</option>`).join('');
        }
    } catch {}
}

async function loadBrgyWasteVolume() {
    const brgy_id = document.getElementById('brgySelect').value;
    if (!brgy_id) return;
    // Use current month/year (API also returns full-year monthly aggregation)
    const params = new URLSearchParams({
        brgy_id,
        year: currentYear,
        month: currentMonth
    });
    const res = await fetch('../api/get_brgy_monthly_waste.php?' + params.toString());
    const data = await res.json();
    if (data.success) {
        // Update progress bar
        const progressBar = document.getElementById('brgyProgressBar');
        const progressText = progressBar.querySelector('.progress-text');
        progressBar.style.width = `${data.progress}%`;
        
        // Show total waste summed across all months (tons)
        let totalYearTons = 0;
        if (data.isAllBrgy && data.allBarangays) {
            // For all barangays, sum up all monthly totals
            data.allBarangays.forEach(brgy => {
                if (Array.isArray(brgy.monthlyTons)) {
                    totalYearTons += brgy.monthlyTons.reduce((sum, t) => sum + (t || 0), 0);
                }
            });
        } else if (Array.isArray(data.monthlyTons) && data.monthlyTons.length === 12) {
            totalYearTons = data.monthlyTons.reduce((sum, t) => sum + (t || 0), 0);
        } else {
            totalYearTons = (data.tons || 0);
        }
        progressText.textContent = `${totalYearTons.toFixed(2)} tons`;
        
        // Info
        const infoText = data.isAllBrgy 
            ? `Showing all barangays - Total: ${data.tons} tons for ${new Date(currentYear, currentMonth - 1, 1).toLocaleString('default', { month: 'long', year: 'numeric' })}.`
            : `Collected: ${data.tons} tons for ${new Date(currentYear, currentMonth - 1, 1).toLocaleString('default', { month: 'long', year: 'numeric' })}.`;
        document.getElementById('brgyWasteInfo').textContent = infoText;
        document.getElementById('brgyWasteUpdated').textContent = 'Updated just now';
        
        // Chart: show full-year by month
        const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        if (data.isAllBrgy && data.allBarangays) {
            // Show bar chart with barangay names on x-axis
            // Show month selector
            document.getElementById('monthSelectorContainer').style.display = 'block';
            document.getElementById('brgyMonthSelect').value = currentMonth;
            
            // Get selected month data for each barangay
            const brgyLabels = data.allBarangays.map(brgy => brgy.barangay);
            const brgyData = data.allBarangays.map(brgy => {
                // Use selectedMonthTons if available, otherwise get from monthlyTons array
                if (brgy.selectedMonthTons !== undefined) {
                    return brgy.selectedMonthTons;
                } else if (Array.isArray(brgy.monthlyTons)) {
                    return brgy.monthlyTons[currentMonth - 1] || 0;
                }
                return brgy.monthTotal || 0;
            });
            
            // Sort by waste amount (descending) for better visualization
            const sortedData = brgyLabels.map((label, index) => ({
                label: label,
                value: brgyData[index]
            })).sort((a, b) => b.value - a.value);
            
            const sortedLabels = sortedData.map(d => d.label);
            const sortedValues = sortedData.map(d => d.value);
            
            // Destroy existing chart if it's not a bar chart
            if (brgyChart && brgyChart.config.type !== 'bar') {
                brgyChart.destroy();
                brgyChart = null;
            }
            
            if (!brgyChart) {
                const ctx = document.getElementById('chart-line').getContext('2d');
                brgyChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: sortedLabels,
                        datasets: [{
                            label: `Waste Collected (tons) - ${new Date(currentYear, currentMonth - 1, 1).toLocaleString('default', { month: 'long', year: 'numeric' })}`,
                            backgroundColor: '#43A047',
                            borderColor: '#43A047',
                            borderWidth: 1,
                            data: sortedValues
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { 
                            legend: { 
                                display: true,
                                position: 'top',
                                labels: {
                                    boxWidth: 12,
                                    padding: 8,
                                    font: { size: 11 }
                                }
                            } 
                        },
                        scales: {
                            y: { 
                                grid: { color: '#e5e5e5' }, 
                                ticks: { color: '#737373', beginAtZero: true },
                                title: {
                                    display: true,
                                    text: 'Waste Collected (tons)',
                                    color: '#737373'
                                }
                            },
                            x: { 
                                grid: { drawBorder: false, display: false }, 
                                ticks: { color: '#737373', maxRotation: 45, minRotation: 45 }
                            }
                        }
                    }
                });
            } else {
                brgyChart.data.labels = sortedLabels;
                brgyChart.data.datasets = [{
                    label: `Waste Collected (tons) - ${new Date(currentYear, currentMonth - 1, 1).toLocaleString('default', { month: 'long', year: 'numeric' })}`,
                    backgroundColor: '#43A047',
                    borderColor: '#43A047',
                    borderWidth: 1,
                    data: sortedValues
                }];
                brgyChart.options.plugins.legend.display = true;
                brgyChart.update();
            }
        } else {
            // Single barangay - show single line chart
            // Hide month selector
            document.getElementById('monthSelectorContainer').style.display = 'none';
            
            const monthData = (Array.isArray(data.monthlyTons) && data.monthlyTons.length === 12) 
                ? data.monthlyTons 
                : Array(12).fill(null).map((v, i) => (i === currentMonth - 1 ? data.tons : null));
            
            // Destroy existing chart if it's not a line chart
            if (brgyChart && brgyChart.config.type !== 'line') {
                brgyChart.destroy();
                brgyChart = null;
            }
            
            if (!brgyChart) {
                const ctx = document.getElementById('chart-line').getContext('2d');
                brgyChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: monthLabels,
                        datasets: [{
                            label: data.barangay + ' - Waste Collected (tons)',
                            backgroundColor: 'rgba(67,160,71,0.2)',
                            borderColor: '#43A047',
                            pointBackgroundColor: '#43A047',
                            pointBorderColor: '#43A047',
                            data: monthData,
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { grid: { color: '#e5e5e5' }, ticks: { color: '#737373' } },
                            x: { grid: { drawBorder: false, display: true }, ticks: { color: '#737373' } }
                        }
                    }
                });
            } else {
                brgyChart.data.labels = monthLabels;
                brgyChart.data.datasets = [{
                    label: data.barangay + ' - Waste Collected (tons)',
                    backgroundColor: 'rgba(67,160,71,0.2)',
                    borderColor: '#43A047',
                    pointBackgroundColor: '#43A047',
                    pointBorderColor: '#43A047',
                    data: monthData,
                    tension: 0.3,
                    fill: true
                }];
                brgyChart.options.plugins.legend.display = false;
                brgyChart.update();
            }
        }
    }
}
function updateSelectedWeekDisplay(week, range) {
    const el = document.getElementById('selectedWeekDisplay');
    if (el) {
        if (week && range) {
            el.textContent = `Selected Week: Week ${week} (${range})`;
        } else {
            el.textContent = '';
        }
    }
}

let currentMonth = new Date().getMonth() + 1;
let currentYear = new Date().getFullYear();
let selectedWeek = null;
let barChart;
let currentBrgyData = null;
let selectedDay = null;

// Load bin level from driver dashboard summary (uses driver_waste_uploads, not sensor)
function loadBinLevelFromDriverSummary() {
    // Get driver_id from vehicle info API response stored in window
    // For admin/client dashboards, driver_id comes from get_vehicle_info.php response
    let driverId = null;
    if (window.lastVehicleInfo && window.lastVehicleInfo.driver_id) {
        driverId = window.lastVehicleInfo.driver_id;
    }
    
    if (!driverId || driverId === 0) {
        console.warn('No driver_id found for bin level');
        // Set default values
        const capEl = document.getElementById('vehicleCapacityPercent');
        const waterEl = document.getElementById('waterFill');
        if (capEl) capEl.textContent = '0.0';
        if (waterEl) waterEl.style.height = '0%';
        return;
    }
    
    // Check if elements exist before fetching
    const capEl = document.getElementById('vehicleCapacityPercent');
    const waterEl = document.getElementById('waterFill');
    if (!capEl || !waterEl) {
        console.error('Bin level elements not found in DOM');
        return;
    }
    
    fetch(`../api/get_driver_dashboard_summary.php?driver_id=${driverId}`)
        .then(r => {
            if (!r.ok) {
                throw new Error(`HTTP error! status: ${r.status}`);
            }
            return r.json();
        })
        .then(data => {
            if (!data || !data.success) {
                console.error('Driver dashboard summary API error:', data?.error || 'Unknown error');
                // Set default values on error
                if (capEl) capEl.textContent = '0.0';
                if (waterEl) waterEl.style.height = '0%';
                return;
            }
            
            // Get today's waste collected (same as "Collected Waste Today")
            const todayTons = parseFloat(data.todayTons || 0);
            // Convert to volume %: tons * 100 = volume %
            const binLevelPercent = todayTons * 100;
            const formattedPercent = Math.max(0, Math.min(100, binLevelPercent));
            
            // Update percentage display
            capEl.textContent = formattedPercent.toFixed(1);
            
            // Update water fill level
            const previousHeight = parseFloat(waterEl.style.height) || 0;
            const newHeight = Math.max(0, formattedPercent); // Ensure non-negative
            
            // Ensure element is visible before updating
            if (!waterEl.style.display || waterEl.style.display === 'none') {
                waterEl.style.display = 'block';
            }
            if (waterEl.style.visibility === 'hidden') {
                waterEl.style.visibility = 'visible';
            }
            
            // Check if there's data today
            const hasDataToday = binLevelPercent > 0;
            
            // Add animation if data first appears
            if (hasDataToday && previousHeight === 0) {
                waterEl.classList.add('water-fill-rise');
                setTimeout(() => {
                    waterEl.classList.remove('water-fill-rise');
                }, 1000);
            }
            
            // Update water fill color based on level FIRST
            // Reset classes first, then add appropriate color class
            waterEl.className = 'water-fill';
            if (formattedPercent >= 100) {
                waterEl.classList.add('water-fill-full');
            } else if (formattedPercent >= 80) {
                waterEl.classList.add('water-fill-warning');
            } else if (formattedPercent > 0) {
                waterEl.classList.add('water-fill-normal');
            }
            
            // Update water fill height with smooth transition
            // For very low percentages, use pixel height to ensure visibility
            let heightValue;
            if (formattedPercent > 0 && formattedPercent < 1) {
                // For percentages less than 1%, use at least 2px for visibility
                const containerHeight = waterEl.parentElement.offsetHeight || 160;
                const pixelHeight = Math.max(2, (formattedPercent / 100) * containerHeight);
                heightValue = pixelHeight + 'px';
            } else {
                // For 1% and above, use percentage
                heightValue = newHeight + '%';
            }
            
            // Use requestAnimationFrame to ensure DOM is ready
            requestAnimationFrame(() => {
                // Force visibility before setting height
                waterEl.style.display = 'block';
                waterEl.style.visibility = 'visible';
                waterEl.style.opacity = '1';
                waterEl.style.position = 'absolute';
                waterEl.style.bottom = '0';
                waterEl.style.left = '0';
                waterEl.style.right = '0';
                waterEl.style.width = '100%';
                waterEl.style.zIndex = '1';
                waterEl.style.height = heightValue;
                waterEl.style.transition = 'height 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
                
                // Force a reflow to ensure the change is applied
                void waterEl.offsetHeight;
                
                // Verify the height was applied
                const appliedHeight = window.getComputedStyle(waterEl).height;
                console.log('Water Fill Height Applied:', {
                    requested: heightValue,
                    applied: appliedHeight,
                    element: waterEl,
                    formattedPercent: formattedPercent
                });
            });
            
            // Add pulse effect if data increased
            if (hasDataToday && (newHeight > previousHeight)) {
                waterEl.classList.add('water-fill-pulse');
                setTimeout(() => {
                    waterEl.classList.remove('water-fill-pulse');
                }, 600);
            }
            
            // Add visual indicator
            if (hasDataToday) {
                capEl.parentElement.classList.add('has-data-today');
                capEl.style.transition = 'color 0.3s ease';
                capEl.style.color = '#0d47a1';
                setTimeout(() => {
                    capEl.style.color = '';
                }, 500);
            } else {
                capEl.parentElement.classList.remove('has-data-today');
            }
            
            // Debug logging
            console.log('Bin Level Update (Driver Summary):', {
                todayTons: todayTons,
                binLevelPercent: binLevelPercent,
                formatted: formattedPercent.toFixed(1) + '%',
                hasData: hasDataToday,
                elementFound: !!capEl && !!waterEl
            });
            
            // Force display update with explicit styles (after requestAnimationFrame)
            // Note: heightValue is set inside requestAnimationFrame, so we'll set it again here
            setTimeout(() => {
                if (capEl) {
                    capEl.style.display = 'inline';
                    capEl.textContent = formattedPercent.toFixed(1);
                }
                if (waterEl) {
                    // Force visibility and proper styling
                    waterEl.style.display = 'block';
                    waterEl.style.visibility = 'visible';
                    waterEl.style.opacity = '1';
                    // Use the same heightValue calculation
                    const finalHeightValue = (formattedPercent > 0 && formattedPercent < 1) 
                        ? Math.max(2, (formattedPercent / 100) * (waterEl.parentElement.offsetHeight || 160)) + 'px'
                        : formattedPercent + '%';
                    waterEl.style.height = finalHeightValue;
                    waterEl.style.width = '100%';
                    waterEl.style.position = 'absolute';
                    waterEl.style.bottom = '0';
                    waterEl.style.left = '0';
                    waterEl.style.right = '0';
                    waterEl.style.zIndex = '1';
                    
                    // Debug: Log the actual computed styles
                    const computedStyle = window.getComputedStyle(waterEl);
                    console.log('Water Fill Styles (Final):', {
                        height: waterEl.style.height,
                        finalHeightValue: finalHeightValue,
                        formattedPercent: formattedPercent,
                        display: computedStyle.display,
                        visibility: computedStyle.visibility,
                        opacity: computedStyle.opacity,
                        zIndex: computedStyle.zIndex,
                        position: computedStyle.position,
                        computedHeight: computedStyle.height,
                        parentHeight: waterEl.parentElement.offsetHeight
                    });
                }
            }, 100);
        })
        .catch(error => {
            console.error('Error loading bin level from driver summary:', error);
        });
}

// Vehicle info panel functions
function initVehiclePanel() {
    if (window.vehicleInfoInterval) return;
    loadVehicleInfo();
    // Also load bin level separately
    loadBinLevelFromDriverSummary();
    window.vehicleInfoInterval = setInterval(() => {
        loadVehicleInfo();
        loadBinLevelFromDriverSummary();
    }, 5000);
}

function loadVehicleInfo() {
    fetch('../api/get_vehicle_info.php')
        .then(r => {
            if (!r.ok) {
                throw new Error(`HTTP error! status: ${r.status}`);
            }
            return r.json();
        })
        .then(d => {
            if (!d || !d.success) {
                console.error('Vehicle info API error:', d?.error || 'Unknown error');
                // Still update UI with default values
                const capEl = document.getElementById('vehicleCapacityPercent');
                const waterEl = document.getElementById('waterFill');
                if (capEl) capEl.textContent = '0.0';
                if (waterEl) waterEl.style.height = '0%';
                return;
            }
            
            // Store vehicle info response for bin level function to access driver_id
            window.lastVehicleInfo = d;
            
            const nameEl = document.getElementById('vehicleName');
            const plateEl = document.getElementById('vehiclePlateNo');
            const driverEl = document.getElementById('driverName');
            const statusEl = document.getElementById('vehicleStatus');
            const locEl = document.getElementById('vehicleLocation');
            const gpsEl = document.getElementById('vehicleGPS');
            const routeEl = document.getElementById('vehicleRoute');
            const capacityInfoEl = document.getElementById('vehicleCapacityInfo');
            const capacityCountEl = document.getElementById('vehicleCapacityCount');
            const capacityMaxEl = document.getElementById('vehicleCapacityMax');
            const uploadCountEl = document.getElementById('vehicleUploadCount');
            const lastUploadEl = document.getElementById('vehicleLastUpload');
            const capEl = document.getElementById('vehicleCapacityPercent');
            const waterEl = document.getElementById('waterFill');

            // Update vehicle details (name, driver, location, route, status)
            // Ensure all fields are populated with proper fallbacks
            if (nameEl) nameEl.textContent = d.vehicle_name || 'Vehicle';
            if (plateEl) plateEl.textContent = d.plate_no || 'N/A';
            if (driverEl) driverEl.textContent = d.driver_name || 'No Driver Assigned';
            if (locEl) locEl.textContent = d.current_location || (d.start_point || 'Bago City Hall');
            
            // Update GPS coordinates
            if (gpsEl && d.gps) {
                const lat = parseFloat(d.gps.latitude || 0).toFixed(6);
                const lng = parseFloat(d.gps.longitude || 0).toFixed(6);
                gpsEl.textContent = `${lat}, ${lng}`;
            }
            
            // Update vehicle capacity info
            if (capacityInfoEl) {
                capacityInfoEl.textContent = d.vehicle_capacity || 'N/A';
            }
            
            // Update capacity count and max
            if (capacityCountEl) capacityCountEl.textContent = d.capacity_count || 0;
            if (capacityMaxEl) capacityMaxEl.textContent = d.capacity_max || 1000;
            
            // Update upload information
            if (uploadCountEl) uploadCountEl.textContent = d.upload_count || 0;
            if (lastUploadEl) {
                if (d.last_upload_time) {
                    const uploadDate = new Date(d.last_upload_time);
                    lastUploadEl.textContent = uploadDate.toLocaleTimeString();
                } else {
                    lastUploadEl.textContent = 'No uploads';
                }
            }
            
            // Build route display with proper fallbacks
            if (routeEl) {
                let routeText = '';
                if (d.start_point && d.end_point) {
                    routeText = d.start_point + ' → ' + d.end_point;
                } else if (d.start_point) {
                    routeText = d.start_point;
                } else if (d.end_point) {
                    routeText = '→ ' + d.end_point;
                } else {
                    routeText = 'No Route Assigned';
                }
                routeEl.textContent = routeText;
            }
            
            if (statusEl) {
                const statusText = d.status || 'Ongoing';
                statusEl.textContent = statusText;
                statusEl.classList.remove('bg-secondary', 'bg-warning', 'bg-success', 'bg-info');
                const s = statusText.toLowerCase();
                if (s === 'collecting') {
                    statusEl.classList.add('bg-warning');
                } else if (s === 'collected' || s === 'route accomplished') {
                    statusEl.classList.add('bg-success');
                } else if (s === 'ongoing') {
                    statusEl.classList.add('bg-info');
                } else {
                    statusEl.classList.add('bg-secondary');
                }
            }
            
            // NOTE: Bin level (capacity) is updated separately by loadBinLevelFromDriverSummary()
            // This ensures it uses driver_waste_uploads data, not sensor data
        })
        .catch(error => {
            console.error('Error loading vehicle info:', error);
            // Set default values on error - ensure all fields have data
            const nameEl = document.getElementById('vehicleName');
            const plateEl = document.getElementById('vehiclePlateNo');
            const driverEl = document.getElementById('driverName');
            const statusEl = document.getElementById('vehicleStatus');
            const locEl = document.getElementById('vehicleLocation');
            const gpsEl = document.getElementById('vehicleGPS');
            const routeEl = document.getElementById('vehicleRoute');
            const capacityInfoEl = document.getElementById('vehicleCapacityInfo');
            const capacityCountEl = document.getElementById('vehicleCapacityCount');
            const capacityMaxEl = document.getElementById('vehicleCapacityMax');
            const uploadCountEl = document.getElementById('vehicleUploadCount');
            const lastUploadEl = document.getElementById('vehicleLastUpload');
            const capEl = document.getElementById('vehicleCapacityPercent');
            const waterEl = document.getElementById('waterFill');
            
            // Set default values on error (same as driver dashboard)
            if (nameEl && nameEl.textContent === '—') nameEl.textContent = 'Vehicle';
            if (plateEl && plateEl.textContent === '—') plateEl.textContent = 'N/A';
            if (driverEl && driverEl.textContent === '—') driverEl.textContent = 'No Driver Assigned';
            if (statusEl && statusEl.textContent === '—') {
                statusEl.textContent = 'Ongoing';
                statusEl.classList.remove('bg-secondary', 'bg-warning', 'bg-success');
                statusEl.classList.add('bg-info');
            }
            if (locEl && locEl.textContent === '—') locEl.textContent = 'Bago City Hall';
            if (gpsEl && gpsEl.textContent === '—') gpsEl.textContent = 'N/A';
            if (routeEl && routeEl.textContent === '—') routeEl.textContent = 'No Route Assigned';
            if (capacityInfoEl && capacityInfoEl.textContent === '—') capacityInfoEl.textContent = 'N/A';
            if (capacityCountEl && capacityCountEl.textContent === '—') capacityCountEl.textContent = '0';
            if (capacityMaxEl && capacityMaxEl.textContent === '—') capacityMaxEl.textContent = '1000';
            if (uploadCountEl && uploadCountEl.textContent === '—') uploadCountEl.textContent = '0';
            if (lastUploadEl && lastUploadEl.textContent === '—') lastUploadEl.textContent = 'No uploads';
            if (capEl) capEl.textContent = '0.0';
            if (waterEl) waterEl.style.height = '0%';
        });
}

// Dashboard initialization function (defined outside DOMContentLoaded)
function initializeDashboard() {
    console.log('Initializing dashboard data loading...');
    
        // Load all dashboard data immediately
        loadDashboardSummary();
        loadWasteForecast();
        loadWeeklyWasteData();
        loadRouteCollectionProgress();
        loadAdminStatistics();
        loadMonthlyWaste();
        initVehiclePanel(); // Initialize vehicle panel
        
        // Set up auto-refresh every 30 seconds (avoid duplicate intervals)
        if (!window.dashboardRefreshInterval) {
            window.dashboardRefreshInterval = setInterval(() => { 
                console.log('Auto-refreshing dashboard data...');
                loadDashboardSummary(); 
                loadWasteForecast();
                loadWeeklyWasteData(); 
                loadBrgyWasteVolume();
                loadRouteCollectionProgress();
                loadAdminStatistics();
                loadMonthlyWaste();
            }, 30000);
        }
}

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, setting up chart and initializing dashboard...');
    
    // Chart.js bar chart setup
    const ctx = document.getElementById('chart-bars').getContext('2d');
    barChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Waste Collected (tons)',
                backgroundColor: '#43A047',
                data: Array(7).fill(0),
                borderRadius: 4,
                borderSkipped: false,
                barThickness: 'flex'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            interaction: { intersect: false, mode: 'index' },
            onClick: (event, elements) => {
                if (elements.length > 0) {
                    const dayIndex = elements[0].index;
                    showBrgyDetailsForDay(dayIndex);
                }
            },
            scales: {
                y: { grid: { color: '#e5e5e5' }, ticks: { color: '#737373' } },
                x: { grid: { drawBorder: false, display: false }, ticks: { color: '#737373' } }
            }
        }
    });

    // Week navigation
    document.querySelector('.prev-month').addEventListener('click', () => {
        currentMonth--;
        if (currentMonth < 1) { currentMonth = 12; currentYear--; }
        selectedWeek = 1;
        loadWeeklyWasteData();
        loadBrgyWasteVolume();
        loadRouteCollectionProgress();
    });
    document.querySelector('.next-month').addEventListener('click', () => {
        currentMonth++;
        if (currentMonth > 12) { currentMonth = 1; currentYear++; }
        selectedWeek = 1;
        loadWeeklyWasteData();
        loadBrgyWasteVolume();
        loadRouteCollectionProgress();
    });

    // Initialize dashboard after a short delay to ensure chart is ready
    setTimeout(initializeDashboard, 500);
    
    // Brgy Waste Volume
    loadBarangays();
    const brgySelect = document.getElementById('brgySelect');
    if (brgySelect) {
        brgySelect.addEventListener('change', loadBrgyWasteVolume);
        // Optionally, auto-select "All Brgy" if available
        setTimeout(() => {
            if (brgySelect.options.length > 1) {
                // Check if "All Brgy" option exists (index 1)
                if (brgySelect.options[1].value === 'all') {
                    brgySelect.selectedIndex = 1;
                    loadBrgyWasteVolume();
                } else if (brgySelect.options.length > 2) {
                    // Otherwise select first barangay
                    brgySelect.selectedIndex = 2;
                    loadBrgyWasteVolume();
                }
            }
        }, 800);
    }
    
    // Month selector for All Brgy view
    const brgyMonthSelect = document.getElementById('brgyMonthSelect');
    if (brgyMonthSelect) {
        // Initialize with current month
        brgyMonthSelect.value = currentMonth;
        brgyMonthSelect.addEventListener('change', function() {
            currentMonth = parseInt(this.value);
            // Only reload if All Brgy is selected
            const brgySelect = document.getElementById('brgySelect');
            if (brgySelect && brgySelect.value === 'all') {
                loadBrgyWasteVolume();
            }
        });
    }

    // Handle window resize for modal positioning
    window.addEventListener('resize', () => {
        const modal = document.getElementById('brgyDetailsModal');
        if (modal && modal.classList.contains('show')) {
            adjustModalPosition();
        }
    });

    // Listen for sidebar toggle events
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            setTimeout(() => {
                const modal = document.getElementById('brgyDetailsModal');
                if (modal && modal.classList.contains('show')) {
                    adjustModalPosition();
                }
            }, 300); // Wait for sidebar animation to complete
        });
    }
});

// ✅ Merged function: Weekly chart + Average Daily Collection + Collection Efficiency
function getWeeklyWasteUrl() {
    const params = new URLSearchParams({ year: currentYear, month: currentMonth });
    if (selectedWeek !== null) params.append('week', selectedWeek);
    return `../api/get_weekly_sensor_data.php?${params.toString()}`;
}

async function loadWeeklyWasteData() {
    try {
        const res = await fetch(getWeeklyWasteUrl());
        const data = await res.json();
        if (!data.success) return;

        // --- Update bar chart ---
        if (barChart && Array.isArray(data.dailyData)) {
            const dailyTons = data.dailyData.map(d => (d.daily_count || 0) * 0.001);
            barChart.data.labels = data.dailyData.map(d => d.day_name);
            barChart.data.datasets[0].data = dailyTons;
            barChart.update();

            // Note: Average Daily Collection and Collection Efficiency are now loaded from loadAdminStatistics()
            // which calculates based on bin fill percentage from driver_waste_uploads table
            // These calculations are kept as fallback but will be overridden by loadAdminStatistics()

            // --- Route Collection Progress will be loaded separately ---
        }

        // --- Update week cards ---
        const weekGrid = document.querySelector('.week-grid .row');
        if (weekGrid && Array.isArray(data.weeklyData)) {
            weekGrid.innerHTML = '';
            let selectedWeekObj = null;
            data.weeklyData.forEach((w) => {
                const isActive = (selectedWeek || data.selectedWeek) === w.week_of_month;
                if (isActive) selectedWeekObj = w;
                const tons = (w.total_count * 0.001).toFixed(1);
                const badgeClass = isActive ? 'bg-white text-success' : 'bg-success';
                const cardClass = isActive ? 'active' : '';
                const col = document.createElement('div');
                 const sortedWeeks = [...data.weeklyData].sort((a, b) => a.week_of_month - b.week_of_month);
                    const last3Weeks = sortedWeeks.slice(-3);
                    console.log('Last 3 weeks:', last3Weeks);
                        const totalTons = last3Weeks.reduce((sum, w) => sum + (w.total_count * 0.001), 0);
                        // Calculate average
                        const avgTons = last3Weeks.length > 0 ? totalTons / last3Weeks.length : 0;
                        document.getElementById('forecastedWasteNextWeek').textContent = avgTons.toFixed(2) + '% volume';
                
                col.className = 'col';
                col.innerHTML = `
                    <div class="week-card p-3 rounded border ${cardClass}" 
                        data-week="${w.week_of_month}"
                        data-tons="${tons}"
                        data-utilization="${(w.utilization || 0).toFixed(1)}%"
                        data-range="${w.date_range}"
                        title="Week ${w.week_of_month} • ${w.date_range} • ${w.total_count} units • ${(w.total_count * 0.001).toFixed(2)} tons">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1 fw-semibold">Week ${w.week_of_month}</h6>
                                <p class="text-muted small mb-2">${w.date_range}</p>
                            </div>
                            <span class="badge ${badgeClass}">${tons}T</span>
                        </div>
                        <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar ${isActive ? 'bg-white' : 'bg-success'}" 
                                style="width: ${Math.min(100, tons / 3 * 100)}%"></div>
                        </div>
                    </div>
                `;
                weekGrid.appendChild(col);
            });

            // Show selected week display
            if (selectedWeekObj) {
                updateSelectedWeekDisplay(selectedWeekObj.week_of_month, selectedWeekObj.date_range);
            } else {
                updateSelectedWeekDisplay();
            }

            // Add click event for week selection
            weekGrid.querySelectorAll('.week-card').forEach(card => {
                card.addEventListener('click', function() {
                    document.querySelectorAll('.week-card').forEach(c => c.classList.remove('active'));
                    this.classList.add('active');
                    selectedWeek = parseInt(this.getAttribute('data-week'));
                    // Set global variables for selected week waste and performance
                    window.selectedWeekWaste = this.getAttribute('data-tons') + ' tons';
                    window.selectedWeekPerformance = this.getAttribute('data-utilization');
                    updateSelectedWeekDisplay(this.getAttribute('data-week'), this.getAttribute('data-range'));
                    document.getElementById('wasteLastWeek').textContent = window.selectedWeekWaste;
                    document.getElementById('weeklyPerformance').textContent = window.selectedWeekPerformance;
                    loadWeeklyWasteData();
                    loadRouteCollectionProgress();
                });
            });
        }

        // --- Update month-year label ---
        const monthYearEl = document.querySelector('.month-year');
        if (monthYearEl) {
            monthYearEl.textContent = data.month;
        }

        // --- Update daily stats labels and values ---
        const dayStatsLabels = document.getElementById('dayStatsLabels');
        const dayStatsValues = document.getElementById('dayStatsValues');
        if (dayStatsLabels && dayStatsValues && Array.isArray(data.dailyData)) {
            dayStatsLabels.innerHTML = '';
            dayStatsValues.innerHTML = '';
            data.dailyData.forEach((d) => {
                // Label
                const labelCol = document.createElement('div');
                labelCol.className = 'col';
                labelCol.innerHTML = `<small class="text-muted">${d.day_name}</small>`;
                dayStatsLabels.appendChild(labelCol);
                // Value
                const valueCol = document.createElement('div');
                valueCol.className = 'col';
                let valueHtml = `<div class="py-1 rounded${d.daily_count > 0 ? ' bg-light' : ''}" title="${d.day_name}, ${d.day_number} • Count: ${d.daily_count} • ${(d.daily_count * 0.001).toFixed(2)} tons">${d.day_number}`;
                if (d.daily_count > 0) {
                    valueHtml += ` <small class="d-block text-success">${(d.daily_count * 0.001).toFixed(1)}T</small>`;
                }
                valueHtml += '</div>';
                valueCol.innerHTML = valueHtml;
                dayStatsValues.appendChild(valueCol);
            });
        }

        // --- Set selectedWeek after first load ---
        if (selectedWeek === null) {
            selectedWeek = data.selectedWeek;
        }
        
        // Update timestamp
        const updatedEl = document.getElementById('weeklyWasteUpdated');
        if (updatedEl) {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            updatedEl.textContent = `Updated ${timeStr}`;
        }

    } catch (e) {
        console.error('Error loading weekly waste data:', e);
        const avgDailyEl = document.getElementById('avgDailyCollection');
        const efficiencyEl = document.getElementById('collectionEfficiency');
        const updatedEl = document.getElementById('weeklyWasteUpdated');
        if (avgDailyEl) avgDailyEl.textContent = '0% waste volume';
        if (efficiencyEl) efficiencyEl.textContent = '0%';
        if (updatedEl) updatedEl.textContent = 'Error loading data';
    }
}

// --- Route Collection Progress Functions ---
async function loadRouteCollectionProgress() {
    try {
        const params = new URLSearchParams({
            year: currentYear,
            month: currentMonth,
            week: selectedWeek || 1
        });
        
        const res = await fetch(`../api/get_route_collection_progress.php?${params.toString()}`);
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const data = await res.json();
        
        if (data.success) {
            const progressEl = document.getElementById('wasteCollectionProgress');
            if (progressEl) {
                const progress = parseFloat(data.progress) || 0;
                progressEl.textContent = progress.toFixed(2) + '%';
            }
            
            console.log(`Route Progress: ${data.days_with_data || 0} days with data out of 7 days (${data.progress}%)`);
        } else {
            throw new Error(data.error || 'Unknown error');
        }
    } catch (error) {
        console.error('Error loading route collection progress:', error);
        const progressEl = document.getElementById('wasteCollectionProgress');
        if (progressEl) {
            progressEl.textContent = '0.00%';
        }
    }
}

// --- Route Progress Modal Functions ---
async function showRouteProgressModal() {
    try {
        const modal = new bootstrap.Modal(document.getElementById('routeProgressModal'));
        modal.show();
        
        // Load current week data
        await loadCurrentWeekRouteProgress();
        
        // Load vehicle trips data
        await loadVehicleTrips();
        
        // Load historical data
        await loadHistoricalRouteProgress();
    } catch (error) {
        console.error('Error showing route progress modal:', error);
    }
}

async function loadCurrentWeekRouteProgress() {
    try {
        const params = new URLSearchParams({
            year: currentYear,
            month: currentMonth,
            week: selectedWeek || 1
        });
        
        const res = await fetch(`../api/get_route_collection_progress.php?${params.toString()}`);
        const data = await res.json();
        
        console.log('Full API Response:', data); // Debug log
        
        if (data.success) {
            // Calculate total trips from all vehicles
            let totalTrips = 0;
            if (data.vehicles && data.vehicles.length > 0) {
                console.log('Vehicles data:', data.vehicles); // Debug log
                data.vehicles.forEach(v => {
                    console.log(`Vehicle: ${v.vehicle_name}, Trips: ${v.trips || 0}, In Mailum: ${v.in_mailum || false}`); // Debug log
                });
                totalTrips = data.vehicles.reduce((sum, vehicle) => sum + (vehicle.trips || 0), 0);
            }
            
            // Update summary
            document.getElementById('modalTotalRoutes').textContent = data.total_routes;
            document.getElementById('modalCompletedRoutes').textContent = data.completed_routes;
            document.getElementById('modalTotalTrips').textContent = totalTrips;
            document.getElementById('modalProgress').textContent = data.progress.toFixed(1) + '%';
            document.getElementById('modalWeekRange').textContent = data.week_range;
            
            // Populate vehicle routes table
            const tbody = document.getElementById('routeProgressTable');
            tbody.innerHTML = '';
            
            if (data.vehicles && data.vehicles.length > 0) {
                data.vehicles.forEach((vehicle, index) => {
                    const row = document.createElement('tr');
                    let statusBadge = vehicle.completed 
                        ? '<span class="badge bg-success">Completed</span>' 
                        : '<span class="badge bg-warning">In Progress</span>';
                    
                    // Add Mailum indicator if vehicle is in Mailum
                    if (vehicle.in_mailum) {
                        statusBadge += '<br><span class="badge bg-info mt-1"><i class="material-symbols-rounded" style="font-size: 14px;">location_on</i> In Mailum</span>';
                    }
                    
                    // Add trip count if available
                    if (vehicle.trips !== undefined && vehicle.trips > 0) {
                        statusBadge += '<br><span class="badge bg-primary mt-1">Trips: ' + vehicle.trips + '</span>';
                    }
                    
                    // Progress based on trips or completion
                    let progressBar = 0;
                    let progressClass = 'bg-secondary';
                    
                    if (vehicle.completed || (vehicle.trips && vehicle.trips > 0)) {
                        progressBar = 100;
                        progressClass = 'bg-success';
                    }
                    
                    row.innerHTML = `
                        <td class="text-center">${index + 1}</td>
                        <td class="fw-semibold">${vehicle.vehicle_name}</td>
                        <td>${vehicle.route}</td>
                        <td class="text-center">${statusBadge}</td>
                        <td class="text-center">
                            <div class="progress" style="height: 20px; width: 100px; margin: 0 auto;">
                                <div class="progress-bar ${progressClass}" 
                                     style="width: ${progressBar}%">
                                    ${progressBar}%
                                </div>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="material-symbols-rounded me-2">info</i>
                            No vehicle routes found
                        </td>
                    </tr>
                `;
            }
        }
    } catch (error) {
        console.error('Error loading current week route progress:', error);
    }
}

async function loadVehicleTrips() {
    try {
        const params = new URLSearchParams({
            year: currentYear,
            month: currentMonth,
            week: selectedWeek || 1
        });
        
        const res = await fetch(`../api/get_route_collection_progress.php?${params.toString()}`);
        const data = await res.json();
        
        if (data.success) {
            const tbody = document.getElementById('vehicleTripsTable');
            tbody.innerHTML = '';
            
            if (data.vehicles && data.vehicles.length > 0) {
                data.vehicles.forEach((vehicle, index) => {
                    const row = document.createElement('tr');
                    let statusBadge = '';
                    let statusClass = '';
                    
                    // Get trip count, default to 0 if not available
                    const trips = vehicle.trips || 0;
                    
                    if (trips === 0) {
                        statusBadge = 'No Trips';
                        statusClass = 'bg-secondary';
                    } else if (trips >= 5) {
                        statusBadge = 'Excellent';
                        statusClass = 'bg-success';
                    } else if (trips >= 3) {
                        statusBadge = 'Good';
                        statusClass = 'bg-info';
                    } else {
                        statusBadge = 'Active';
                        statusClass = 'bg-warning';
                    }
                    
                    row.innerHTML = `
                        <td class="text-center">${index + 1}</td>
                        <td class="fw-semibold">${vehicle.vehicle_name}</td>
                        <td class="text-center">
                            <span class="h5 mb-0 fw-bold">${trips}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge ${statusClass}">${statusBadge}</span>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            <i class="material-symbols-rounded me-2">info</i>
                            No trip data available
                        </td>
                    </tr>
                `;
            }
        }
    } catch (error) {
        console.error('Error loading vehicle trips:', error);
        const tbody = document.getElementById('vehicleTripsTable');
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="text-center text-danger py-4">
                    <i class="material-symbols-rounded me-2">error</i>
                    Error loading trip data
                </td>
            </tr>
        `;
    }
}

async function loadHistoricalRouteProgress() {
    try {
        // Get data for all weeks in the current month
        const historicalData = [];
        
        // Calculate number of weeks in the month
        const firstDayOfMonth = new Date(currentYear, currentMonth - 1, 1);
        const lastDayOfMonth = new Date(currentYear, currentMonth, 0);
        const firstMonday = new Date(firstDayOfMonth);
        const daysToMonday = (firstMonday.getDay() === 0 ? -6 : 1 - firstMonday.getDay());
        firstMonday.setDate(firstDayOfMonth.getDate() + daysToMonday);
        
        let currentWeekStart = new Date(firstMonday);
        let weekNum = 1;
        
        while (currentWeekStart <= lastDayOfMonth && weekNum <= 6) {
            const weekEnd = new Date(currentWeekStart);
            weekEnd.setDate(weekEnd.getDate() + 6);
            
            // Only show weeks that overlap with the current month
            if (weekEnd >= firstDayOfMonth && currentWeekStart <= lastDayOfMonth) {
                const params = new URLSearchParams({
                    year: currentYear,
                    month: currentMonth,
                    week: weekNum
                });
                
                const res = await fetch(`../api/get_route_collection_progress.php?${params.toString()}`);
                const data = await res.json();
                
                if (data.success) {
                    historicalData.push({
                        week: weekNum,
                        range: data.week_range,
                        completed: data.completed_routes,
                        total: data.total_routes,
                        progress: data.progress
                    });
                }
            }
            
            currentWeekStart.setDate(currentWeekStart.getDate() + 7);
            weekNum++;
        }
        
        // Populate historical table
        const tbody = document.getElementById('historicalTable');
        tbody.innerHTML = '';
        
        if (historicalData.length > 0) {
            // Sort by week number (descending)
            historicalData.sort((a, b) => b.week - a.week);
            
            historicalData.forEach((week) => {
                const row = document.createElement('tr');
                const isCurrentWeek = week.week === (selectedWeek || 1);
                const rowClass = isCurrentWeek ? 'table-active' : '';
                
                row.className = rowClass;
                row.innerHTML = `
                    <td class="fw-semibold">Week ${week.week}</td>
                    <td>${week.range}</td>
                    <td class="text-center">${week.completed}</td>
                    <td class="text-center">${week.total}</td>
                    <td class="text-center">
                        <span class="badge ${week.progress === 100 ? 'bg-success' : week.progress > 0 ? 'bg-warning' : 'bg-secondary'}">
                            ${week.progress.toFixed(1)}%
                        </span>
                    </td>
                `;
                tbody.appendChild(row);
            });
        } else {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted py-2">
                        <i class="material-symbols-rounded me-2">info</i>
                        No historical data available
                    </td>
                </tr>
            `;
        }
    } catch (error) {
        console.error('Error loading historical route progress:', error);
    }
}

function exportRouteProgress() {
    // Get modal data and export as CSV
    const params = new URLSearchParams({
        year: currentYear,
        month: currentMonth,
        week: selectedWeek || 1
    });
    
    fetch(`../api/get_route_collection_progress.php?${params.toString()}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const csvContent = [
                    ['Week', 'Date Range', 'Vehicle', 'Route', 'Status'],
                    ...data.vehicles.map(v => [
                        `${data.week_range}`,
                        data.week_range,
                        v.vehicle_name,
                        v.route,
                        v.completed ? 'Completed' : 'In Progress'
                    ])
                ].map(row => row.join(',')).join('\n');
                
                const blob = new Blob([csvContent], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `route_progress_week_${data.week_range.replace(/\s/g, '_')}.csv`;
                a.click();
                window.URL.revokeObjectURL(url);
            }
        })
        .catch(error => {
            console.error('Error exporting route progress:', error);
            alert('Failed to export data');
        });
}

// --- Barangay Details Modal Functions ---
let selectedDate = new Date();
let currentWeekStart = new Date();

async function showBrgyDetails() {
    try {
        const modal = new bootstrap.Modal(document.getElementById('brgyDetailsModal'));
        
        // Adjust modal positioning based on sidebar state
        adjustModalPosition();
        
        modal.show();
        
        // Load weekly barangay data
        await loadWeeklyBrgyData();
    } catch (e) {
        console.error('Error showing barangay details:', e);
    }
}

function adjustModalPosition() {
    const modalDialog = document.querySelector('#brgyDetailsModal .modal-dialog');
    const sidebar = document.getElementById('sidenav-main');
    
    // Remove any existing positioning classes
    modalDialog.classList.remove('sidebar-hidden');
    
    if (sidebar && window.innerWidth >= 1200) {
        // Check if sidebar is visible on large screens
        const sidebarRect = sidebar.getBoundingClientRect();
        if (sidebarRect.width <= 0 || sidebarRect.left < 0) {
            // Sidebar is hidden or collapsed
            modalDialog.classList.add('sidebar-hidden');
        }
    } else if (window.innerWidth < 1200) {
        // Medium screens and below - use default responsive behavior
        modalDialog.classList.add('sidebar-hidden');
    }
}

async function loadWeeklyBrgyData() {
    try {
        const params = new URLSearchParams({ 
            year: currentYear, 
            month: currentMonth,
            week: selectedWeek || 1
        });
        
        const res = await fetch(`../api/get_daily_brgy_waste_details.php?${params.toString()}`);
        const data = await res.json();
        
        if (data.success) {
            currentBrgyData = data.daily_data;
            populateDaySelection();
        }
    } catch (e) {
        console.error('Error loading weekly barangay data:', e);
    }
}

function populateDaySelection() {
    const dayGrid = document.getElementById('daySelectionGrid');
    dayGrid.innerHTML = '';
    
    if (!currentBrgyData) return;
    
    currentBrgyData.forEach((day, index) => {
        const dayCard = document.createElement('div');
        dayCard.className = 'col';
        dayCard.innerHTML = `
            <div class="day-card p-3 rounded border text-center cursor-pointer ${selectedDay === index ? 'bg-primary text-white' : 'bg-light'}" 
                 onclick="selectDay(${index})" 
                 style="cursor: pointer; transition: all 0.2s;">
                <div class="fw-semibold">${day.day_name}</div>
                <div class="small">${day.day_number}</div>
                <div class="mt-1">
                    <span class="badge ${selectedDay === index ? 'bg-white text-primary' : 'bg-success'}">
                        ${day.total_tons.toFixed(2)}T
                    </span>
                </div>
            </div>
        `;
        dayGrid.appendChild(dayCard);
    });
}

function selectDay(dayIndex) {
    selectedDay = dayIndex;
    populateDaySelection();
    showDayBrgyDetails(dayIndex);
}

async function showDayBrgyDetails(dayIndex) {
    if (!currentBrgyData || !currentBrgyData[dayIndex]) return;
    
    const dayData = currentBrgyData[dayIndex];
    
    // Update header info
    document.getElementById('selectedDateInfo').textContent = 
        `${dayData.day_name}, ${dayData.date} (${dayData.day_number})`;
    document.getElementById('totalWasteInfo').textContent = 
        `Total: ${dayData.total_tons.toFixed(2)} tons`;
    
    // Populate table
    const tableBody = document.getElementById('brgyDetailsTable');
    tableBody.innerHTML = '';
    
    if (dayData.barangay_data.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    <i class="material-symbols-rounded me-2">info</i>
                    No collection data for this day
                </td>
            </tr>
        `;
        return;
    }
    
    // Calculate total for percentage calculation
    const totalCount = dayData.total_count;
    
    dayData.barangay_data.forEach((brgy, index) => {
        const progressWidth = totalCount > 0 ? Math.min(98, (brgy.daily_count / totalCount) * 100) : 0;
        const percentage = totalCount > 0 ? Math.min(98, (brgy.daily_count / totalCount) * 100) : 0;
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="text-center">
                <span class="badge ${index < 3 ? 'bg-warning text-dark' : 'bg-secondary'} fs-6">${index + 1}</span>
            </td>
            <td class="fw-semibold text-dark">${brgy.barangay}</td>
            <td class="text-center fw-bold text-primary">${brgy.daily_count.toLocaleString()}</td>
            <td class="text-center fw-bold text-success">${brgy.tons.toFixed(3)}</td>
            <td class="text-center">
                <span class="badge ${brgy.daily_count > 0 ? 'bg-success' : 'bg-secondary'}">
                    ${brgy.daily_count > 0 ? 'Collected' : 'Done'}
                </span>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

function showBrgyDetailsForDay(dayIndex) {
    showBrgyDetails();
    setTimeout(() => {
        selectDay(dayIndex);
    }, 300);
}

function exportBrgyData() {
    if (!currentBrgyData || selectedDay === null) {
        alert('Please select a day to export data.');
        return;
    }
    
    const dayData = currentBrgyData[selectedDay];
    const csvContent = [
        ['Barangay', 'Count', 'Tons', 'Vehicle', 'Driver', 'Plate Number'],
        ...dayData.barangay_data.map(brgy => [
            brgy.barangay,
            brgy.daily_count,
            brgy.tons.toFixed(3),
            brgy.vehicles,
            brgy.drivers,
            brgy.plate_numbers
        ])
    ].map(row => row.join(',')).join('\n');
    
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `barangay_collection_${dayData.date}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
}

// --- Simple Date Selection ---
async function loadDataForSelectedDate() {
    const dateInput = document.getElementById('datePicker');
    if (!dateInput.value) return;
    
    selectedDate = new Date(dateInput.value);
    
    try {
        const year = selectedDate.getFullYear();
        const month = selectedDate.getMonth() + 1;
        const day = selectedDate.getDate();
        
        // Calculate which week of the month this date falls into
        const firstDayOfMonth = new Date(year, month - 1, 1);
        const firstMonday = new Date(firstDayOfMonth);
        const dayOfWeek = firstMonday.getDay();
        const daysToMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
        firstMonday.setDate(firstMonday.getDate() - daysToMonday);
        
        const weekOfMonth = Math.ceil((day - firstMonday.getDate() + 1) / 7);
        
        const params = new URLSearchParams({ 
            year, 
            month,
            week: weekOfMonth,
            day: day
        });
        
        const res = await fetch(`../api/get_daily_brgy_waste_details.php?${params.toString()}`);
        const data = await res.json();
        
        if (data.success) {
            // Update the display with the selected date's data
            const dateStr = selectedDate.toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            document.getElementById('selectedDateInfo').textContent = dateStr;
            updateDateDisplay();
            updateSelectedDateInfo();
            
            // Clear the day selection grid
            const dayGrid = document.getElementById('daySelectionGrid');
            dayGrid.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="material-symbols-rounded me-2">info</i>
                        Showing data for ${data.date || 'selected date'}
                    </div>
                </div>
            `;
            
            // Update total waste info
            const totalTons = data.total_tons || 0;
            document.getElementById('totalWasteInfo').textContent = `Total: ${totalTons.toFixed(2)} tons`;
            
            // Display the barangay data
            showDayBrgyDetailsFromData(data);
        }
    } catch (e) {
        console.error('Error loading data for selected date:', e);
    }
}

function showDayBrgyDetailsFromData(data) {
    const tableBody = document.getElementById('brgyDetailsTable');
    tableBody.innerHTML = '';
    
    if (!data.barangay_data || data.barangay_data.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    <i class="material-symbols-rounded me-2">info</i>
                    No collection data for this date
                </td>
            </tr>
        `;
        return;
    }
    
    const totalCount = data.total_count || 0;
    
    data.barangay_data.forEach((brgy, index) => {
        const progressWidth = totalCount > 0 ? Math.min(98, (brgy.daily_count / totalCount) * 100) : 0;
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="text-center">
                <span class="badge ${index < 3 ? 'bg-warning text-dark' : 'bg-secondary'} fs-6">${index + 1}</span>
            </td>
            <td class="fw-semibold text-dark">${brgy.barangay}</td>
            <td class="text-center fw-bold text-primary">${brgy.daily_count.toLocaleString()}</td>
            <td class="text-center fw-bold text-success">${brgy.tons.toFixed(3)}</td>
            <td class="text-center">
                <span class="badge ${brgy.daily_count > 0 ? 'bg-success' : 'bg-secondary'}">
                    ${brgy.daily_count > 0 ? 'Collected' : 'Done'}
                </span>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

// --- Weekly Calendar Functions ---
function getWeekStart(date) {
    const d = new Date(date);
    const day = d.getDay();
    const diff = d.getDate() - day;
    const weekStart = new Date(d);
    weekStart.setDate(diff);
    return weekStart;
}

function initializeWeekCalendar() {
    // Set up event listeners for navigation
    document.querySelector('.prev-week').addEventListener('click', () => {
        console.log('Previous week clicked');
        navigateWeek(-1);
    });
    document.querySelector('.next-week').addEventListener('click', () => {
        console.log('Next week clicked');
        navigateWeek(1);
    });
    
    // Load the current week
    loadWeekCalendar();
}

function navigateWeek(direction) {
    console.log('Navigating week:', direction, 'Current week start:', currentWeekStart.toDateString());
    
    // Create a new date to avoid mutating the original
    const newWeekStart = new Date(currentWeekStart);
    newWeekStart.setDate(newWeekStart.getDate() + (direction * 7));
    currentWeekStart = newWeekStart;
    
    console.log('New week start:', currentWeekStart.toDateString());
    
    loadWeekCalendar();
    
    // Auto-select the first day of the new week if no date is selected
    if (!selectedDate) {
        selectedDate = new Date(currentWeekStart);
        updateDateDisplay();
        updateSelectedDateInfo();
        loadDataForSelectedDate();
    }
}

function loadWeekCalendar() {
    const weekYear = document.querySelector('.week-year');
    const weekGrid = document.getElementById('weekCalendarGrid');
    
    // Update week/year display
    const weekEnd = new Date(currentWeekStart);
    weekEnd.setDate(weekEnd.getDate() + 6);
    
    const startMonth = currentWeekStart.toLocaleDateString('en-US', { month: 'short' });
    const endMonth = weekEnd.toLocaleDateString('en-US', { month: 'short' });
    const year = currentWeekStart.getFullYear();
    
    if (startMonth === endMonth) {
        weekYear.textContent = `${startMonth} ${year}`;
    } else {
        weekYear.textContent = `${startMonth} - ${endMonth} ${year}`;
    }
    
    // Generate week days
    weekGrid.innerHTML = '';
    for (let i = 0; i < 7; i++) {
        const dayDate = new Date(currentWeekStart);
        dayDate.setDate(dayDate.getDate() + i);
        
        const dayElement = document.createElement('div');
        dayElement.className = 'col';
        
        const isToday = isSameDay(dayDate, new Date());
        const isSelected = selectedDate && isSameDay(dayDate, selectedDate);
        
        dayElement.innerHTML = `
            <button class="btn btn-sm w-100 ${isSelected ? 'btn-primary' : isToday ? 'btn-outline-primary' : 'btn-outline-secondary'}" 
                    onclick="selectDateFromCalendar('${dayDate.toISOString().split('T')[0]}')">
                ${dayDate.getDate()}
            </button>
        `;
        
        weekGrid.appendChild(dayElement);
    }
    
    console.log('Week loaded:', currentWeekStart.toDateString(), 'to', new Date(currentWeekStart.getTime() + 6 * 24 * 60 * 60 * 1000).toDateString());
}

function isSameDay(date1, date2) {
    return date1.getDate() === date2.getDate() &&
           date1.getMonth() === date2.getMonth() &&
           date1.getFullYear() === date2.getFullYear();
}

function selectDateFromCalendar(dateString) {
    selectedDate = new Date(dateString);
    updateDateDisplay();
    loadDataForSelectedDate();
    loadWeekCalendar(); // Refresh to show selection
    updateSelectedDateInfo();
}

// --- Date Display Functions ---
function updateDateDisplay() {
    const dateDisplay = document.getElementById('selectedDateDisplay');
    if (selectedDate) {
        const dateStr = selectedDate.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric',
            year: 'numeric'
        });
        dateDisplay.textContent = dateStr;
    } else {
        dateDisplay.textContent = 'Choose Date';
    }
}

function goToToday() {
    selectedDate = new Date();
    currentWeekStart = getWeekStart(selectedDate);
    updateDateDisplay();
    loadWeekCalendar();
    loadDataForSelectedDate();
}

function updateSelectedDateInfo() {
    const selectedDateInfo = document.getElementById('selectedDateInfo');
    if (selectedDate) {
        const dateStr = selectedDate.toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        selectedDateInfo.innerHTML = `
            <div class="col text-center">
                <div class="fw-semibold text-primary">${dateStr}</div>
                <div class="small text-muted">Click to view collection data</div>
            </div>
        `;
    } else {
        selectedDateInfo.innerHTML = `
            <div class="col text-muted small">No date selected</div>
        `;
    }
}

function clearDateSelection() {
    selectedDate = null;
    document.getElementById('selectedDateDisplay').textContent = 'Choose Date';
    updateSelectedDateInfo();
    
    // Clear the table
    const tableBody = document.getElementById('brgyDetailsTable');
    tableBody.innerHTML = `
        <tr>
            <td colspan="6" class="text-center text-muted py-4">
                <i class="material-symbols-rounded me-2">info</i>
                Select a date to view collection details
            </td>
        </tr>
    `;
    
    // Reset day selection grid
    loadWeeklyBrgyData();
}


var ctx3 = document.getElementById("chart-line-tasks").getContext("2d");
new Chart(ctx3, {
    type: "line",
    data: {
        labels: ["2025", "2026", "2027", "2028", "2029", "2030", "2031", "2032", "2033"],
        datasets: [{
            label: "Dumpsite Capacity",
            tension: 0,
            borderWidth: 2,
            pointRadius: 3,
            pointBackgroundColor: "#43A047",
            pointBorderColor: "transparent",
            borderColor: "#43A047",
            backgroundColor: "transparent",
            fill: true,
            data: [75, 120, 200, 150, 400, 320, 350, 270, 500],
            maxBarThickness: 6
        }],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
        },
        interaction: { intersect: false, mode: 'index' },
        scales: {
            y: {
                grid: {
                    drawBorder: false,
                    display: true,
                    drawOnChartArea: true,
                    drawTicks: false,
                    borderDash: [4, 4],
                    color: '#e5e5e5'
                },
                ticks: {
                    display: true,
                    padding: 10,
                    color: '#737373',
                    font: { size: 14, lineHeight: 2 },
                }
            },
            x: {
                grid: { drawBorder: false, display: false },
                ticks: {
                    display: true,
                    color: '#737373',
                    padding: 10,
                    font: { size: 14, lineHeight: 2 },
                }
            },
        },
    },
});


</script>
<style>
        .material-symbols-rounded {
            font-family: 'Material Symbols Rounded';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            -webkit-font-feature-settings: 'liga';
            -webkit-font-smoothing: antialiased;
        }

        .waste-volume-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 8px 26px -4px hsla(0, 0%, 8%, 0.15), 0 8px 9px -5px hsla(0, 0%, 8%, 0.06);
            transition: all 0.3s ease;
        }

        .waste-volume-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px -4px hsla(0, 0%, 8%, 0.2), 0 8px 12px -5px hsla(0, 0%, 8%, 0.1);
        }

        .card-header-section {
            padding-bottom: 1rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #344767;
            margin: 0;
        }

        .card-subtitle {
            font-size: 0.875rem;
            color: #67748e;
            margin: 0.5rem 0 0 0;
        }

        .barangay-selector {
            border-radius: 8px;
            border: 1px solid #e0e5ed;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .barangay-selector:focus {
            border-color: #5e72e4;
            box-shadow: 0 0 0 0.2rem rgba(94, 114, 228, 0.1);
        }

        .chart-container {
            position: relative;
            margin: 1.5rem 0;
            background: #f8f9ff;
            border-radius: 12px;
            padding: 1rem;
        }

        .progress-section {
            margin-top: 2rem;
        }

        .progress-container {
            background: #e9ecef;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }

        .progress-bar-custom {
            height: 20px;
            background: linear-gradient(90deg, #4CAF50 0%, #66BB6A 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: width 0.8s ease;
            position: relative;
        }

        .progress-text {
            color: white;
            font-size: 0.875rem;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .waste-info-text {
            font-size: 0.8125rem;
            color: #67748e;
            margin-top: 0.75rem;
        }

        .card-footer-section {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }

        .update-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #67748e;
            font-size: 0.8125rem;
        }

        .update-icon {
            font-size: 18px;
        }

        /* Demo styles */
        .demo-container {
            padding: 2rem;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .demo-canvas {
            background: #ffffff;
            border-radius: 8px;
            width: 100%;
            height: 170px;
        }

        /* Barangay Details Modal Styles */
        .day-card {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .day-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: #007bff;
        }

        .day-card.bg-primary {
            border-color: #007bff;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
        }

        .progress {
            border-radius: 4px;
        }

        .badge {
            font-size: 0.75rem;
        }

        .modal-xl {
            max-width: 1200px;
        }

        /* Vehicle and Driver Info Styling */
        .vehicle-driver-info {
            font-size: 0.875rem;
        }

        .vehicle-info {
            color: #007bff;
            font-weight: 600;
        }

        .driver-info {
            color: #6c757d;
            font-size: 0.8rem;
        }

        .plate-info {
            color: #17a2b8;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .material-symbols-rounded {
            vertical-align: middle;
        }

        /* Simple Date Picker Styles */
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }

        /* Date Dropdown Styling */
        .dropdown-menu {
            border: 1px solid #dee2e6;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .form-control {
            border-radius: 0.375rem;
            border: 1px solid #ced4da;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .btn-sm {
            font-size: 0.875rem;
            padding: 0.375rem 0.75rem;
        }

        /* Weekly Calendar Styling */
        .week-grid .btn {
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .week-grid .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .week-grid .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .week-grid .btn-outline-primary {
            color: #007bff;
            border-color: #007bff;
        }

        .week-grid .btn-outline-secondary {
            color: #6c757d;
            border-color: #dee2e6;
        }

        .selected-date-info {
            background-color: #f8f9fa;
            border-radius: 0.375rem;
            padding: 0.5rem;
        }

        /* Clean Table Styling */
        .table {
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .table-dark th {
            background-color: #343a40;
            border-color: #454d55;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }

        .table tbody tr {
            border-bottom: 1px solid #e9ecef;
        }

        .table tbody tr:last-child {
            border-bottom: none;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
        }

        .progress {
            border-radius: 0.375rem;
            background-color: #e9ecef;
        }

        .progress-bar {
            border-radius: 0.375rem;
            transition: width 0.3s ease;
        }

        /* Modal positioning to avoid sidebar overlap */
        .modal {
            z-index: 1055;
        }

        .modal-backdrop {
            z-index: 1050;
        }

        /* Ensure modal content fits within viewport */
        .modal-dialog {
            margin: 1rem auto;
            max-height: calc(100vh - 2rem);
        }

        .modal-content {
            max-height: calc(100vh - 2rem);
            display: flex;
            flex-direction: column;
        }

        .modal-body {
            overflow-y: auto;
            flex: 1;
            max-height: calc(100vh - 200px);
        }

        /* Responsive modal sizing */
        @media (max-width: 991.98px) {
            .modal-dialog {
                margin: 0.5rem;
                max-height: calc(100vh - 1rem);
            }
            
            .modal-content {
                max-height: calc(100vh - 1rem);
            }
            
            .modal-body {
                max-height: calc(100vh - 150px);
            }
        }

        /* Modal positioning - responsive and sidebar-aware */
        .modal-dialog {
            transition: all 0.3s ease;
        }

        /* Default positioning for large screens */
        @media (min-width: 1200px) {
            .modal-dialog {
                margin-left: 280px;
                margin-right: 1rem;
                max-width: calc(100vw - 300px);
            }
        }

        /* Medium screens - check if sidebar is collapsed */
        @media (min-width: 992px) and (max-width: 1199.98px) {
            .modal-dialog {
                margin-left: auto;
                margin-right: auto;
                max-width: calc(100vw - 2rem);
            }
        }

        /* Small screens and mobile */
        @media (max-width: 991.98px) {
            .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100vw - 1rem);
            }
        }

        /* Override for when sidebar is hidden */
        .modal-dialog.sidebar-hidden {
            margin-left: auto !important;
            margin-right: auto !important;
            max-width: calc(100vw - 2rem) !important;
        }
    </style>
</body>
</html>