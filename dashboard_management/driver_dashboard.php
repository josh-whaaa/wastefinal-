<?php
session_start();
include '../includes/conn.php';

// Check if the user is logged in as a driver
if (!isset($_SESSION['driver_id']) || $_SESSION['user_role'] !== 'driver') {
    // Redirect to the login page if not logged in as driver
    header("Location: ../login_page/sign-in.php");
    exit();
}

// Fetch all barangays from the database
$sql = "SELECT barangay, latitude, longitude FROM barangays_table WHERE city = 'Bago City'";
$result = $conn->query($sql);
$allBarangays = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $allBarangays[] = $row;
    }
} else {
    echo "<p class='text-center text-secondary'>No barangays found.</p>";
}

// Fetch all routes from the route table
$sql = "SELECT r.*, d.first_name, d.last_name, w.vehicle_name, w.plate_no, w.vehicle_capacity
        FROM route_table r
        LEFT JOIN driver_table d ON r.driver_id = d.driver_id
        LEFT JOIN waste_service_table w ON r.route_id = w.route_id";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0):
    while ($row = $result->fetch_assoc()):

        // Get driver information
        $driver_id = $_SESSION['driver_id'];
        
        // For drivers, get information from driver_table
        $stmt = $pdo->prepare("SELECT address FROM driver_table WHERE driver_id = ?");
        $stmt->execute([$driver_id]);
        $user = $stmt->fetch();
        $driverBarangay = $user['address'] ?? ''; // Use address as barangay for drivers
        
        if (!$user) {
            die("Driver not found.");
        }

        // Set page title for driver
        $page_title = "Driver Dashboard - Bago City Map";
        
        include '../includes/header.php'; // Includes the head section and styles
?>

<body class="g-sidenav-show bg-gray-200">
    <!-- Sidebar -->
    <?php include '../sidebar/driver_sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <!-- Navbar -->
        <?php include '../includes/navbar.php'; ?>

        <?php if (isset($_SESSION['msg'])): ?>
  <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
    <?= $_SESSION['msg']; unset($_SESSION['msg']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
        <!-- Page Content -->
        <div class="container mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="text-center flex-grow-1 mb-0">Bago City Map</h2>
                <a href="../admin_management/driver_map.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-map"></i> Map Dashboard
                </a>
            </div>
            
            <!-- Waste Collection Statistics Cards (Same as Admin Dashboard) -->
            <div class="row mb-4">
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
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Waste Collected This Week</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="wasteCollectedThisWeek">-- estimated tons</div>
                                </div>
                                <div class="col-auto">
                                    <i class="material-symbols-rounded opacity-10">recycling</i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
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
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Collection Efficiency</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="collectionEfficiency">--%</div>
                                </div>
                                <div class="col-auto">
                                    <i class="material-symbols-rounded opacity-10">speed</i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Map + Vehicle Panel Container -->
            <div class="d-flex flex-wrap gap-3 align-items-stretch">
                <div class="map-box" style="flex: 1 1 60%; height: 400px; resize: both; min-width: 320px;">
                    <div id="map" style="width: 100%; height: 100%; border-radius: 8px; border: 1px solid #ccc;"></div>
                    <!-- Floating Trail Controls -->
                    <div class="floating-trail-controls">
                        <button type="button" class="btn btn-primary btn-floating" id="viewTrail" title="View Trail">
                            <i class="fas fa-route"></i>
                        </button>
                        <button type="button" class="btn btn-info btn-floating" id="followGps" title="Follow GPS">
                            <i class="fas fa-location-arrow"></i>
                        </button>
                    </div>
                </div>
                <!-- Vehicle Info Panel -->
                <div class="card vehicle-panel" style="flex: 1 1 38%; min-width: 300px;">
                    <div class="card-header pb-0">
                        <h6 class="mb-0">Vehicle Status</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div class="text-sm text-secondary">Vehicle</div>
                                <div id="vehicleName" class="text-dark fw-bold">—</div>
                                <div class="text-xs text-secondary mt-1">
                                    <i class="fas fa-id-card"></i> Plate: <span id="vehiclePlateNo">—</span>
                                </div>
                                <div class="text-xs text-secondary mt-1">
                                    <i class="fas fa-user"></i> Driver: <span id="driverName">—</span>
                                </div>
                            </div>
                            <span id="vehicleStatus" class="badge bg-secondary">—</span>
                        </div>

                        <div class="mb-3">
                            <div class="text-sm text-secondary mb-1">
                                <i class="fas fa-map-marker-alt"></i> Current Location
                            </div>
                            <div id="vehicleLocation" class="text-dark">—</div>
                            <div class="text-xs text-secondary mt-1">
                                GPS: <span id="vehicleGPS">—</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="text-sm text-secondary mb-1">
                                <i class="fas fa-route"></i> Route
                            </div>
                            <div id="vehicleRoute" class="text-dark">—</div>
                        </div>

                        <div class="mb-3">
                            <div class="text-sm text-secondary mb-1">
                                <i class="fas fa-weight"></i> Vehicle Capacity
                            </div>
                            <div class="text-dark" id="vehicleCapacityInfo">—</div>
                        </div>

                        <div class="mb-3">
                            <div class="text-sm text-secondary mb-1">
                                <i class="fas fa-chart-line"></i> Waste Collection Today
                            </div>
                            <div class="text-dark">
                                <span id="vehicleCapacityCount">0</span> / <span id="vehicleCapacityMax">1000</span> units
                            </div>
                            <div class="text-xs text-secondary mt-1">
                                Uploads: <span id="vehicleUploadCount">0</span> | 
                                Last: <span id="vehicleLastUpload">—</span>
                            </div>
                        </div>

                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="text-sm text-secondary">Capacity Level</div>
                                <div class="text-sm"><span id="vehicleCapacityPercent" style="display: inline;">0.0</span>%</div>
                            </div>
                            <div class="vehicle-figure position-relative">
                                <div class="water-fill" id="waterFill" style="display: block; height: 0%;"></div>
                                <div class="vehicle-icon"><i class="fas fa-truck" aria-hidden="true"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Hidden span to pass driver barangay -->
            <span id="clientBarangay" style="display: none;"><?= htmlspecialchars($driverBarangay) ?></span>

            <!-- Add spacing between map and table -->
            <div class="mt-4">
                <h4 class="text-center mb-3">Vehicle Routes</h4>
                <table class="table align-items-center table-flush">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center text-uppercase text-xs font-weight-bolder">Vehicle List</th>
                            <th class="text-center text-uppercase text-xs font-weight-bolder">Route</th>
                            <th class="text-center text-uppercase text-xs font-weight-bolder">Actions</th>
                            <th class="text-center text-uppercase text-xs font-weight-bolder">Tools</th>
                        </tr>
                    </thead>
                    <tbody>
            <tr>
                <td>
                    <div class="d-flex px-2 py-1">
                        <div>
                            <img src="../assets/img/logo.png" class="avatar avatar-sm me-3 border-radius-lg" alt="route">
                        </div>
                        <div class="d-flex flex-column justify-content-center">
                            <h6 class="mb-0 text-sm"><?= htmlspecialchars($row['vehicle_name']); ?></h6>
                        </div>
                    </div>
                </td>
                <td>
                    <p class="text-xs font-weight-bold mb-0 text-center">
                        Start Point: <?= htmlspecialchars($row['start_point']); ?> → End Point: <?= htmlspecialchars($row['end_point']); ?>
                    </p>
                </td>
                <td class="align-middle">
                    <div class="d-flex align-items-center justify-content-center">
                        <a href="#"
                        class="badge badge-sm bg-gradient-success view-route"
                        data-bs-toggle="tooltip"
                        data-bs-original-title="View Route Details"
                        data-barangay="<?= htmlspecialchars($row['end_point']); ?>">
                            View
                            <span class="material-symbols-rounded opacity-10" style="font-size: 0.9rem;">eye_tracking</span>
                        </a>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center justify-content-center">
                        <a href="#"
                        class="badge badge-sm bg-gradient-info details-route-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#detailsRouteModal"
                        data-vehicle="<?= htmlspecialchars($row['vehicle_name']); ?>"
                        data-plate="<?= htmlspecialchars($row['plate_no']); ?>"
                        data-type="<?= htmlspecialchars($row['vehicle_capacity']); ?>"
                        data-driver="<?= htmlspecialchars($row['first_name']); ?>"
                        data-start="<?= htmlspecialchars($row['start_point']); ?>"
                        data-end="<?= htmlspecialchars($row['end_point']); ?>">
                        Details <span class="material-symbols-rounded opacity-10" style="font-size: 0.9rem;">info</span>
                        </a>
                    </div>
                </td>
            </tr>
        <?php
            endwhile;
        else:
        ?>
            <tr>
                <td colspan="4" class="text-center text-secondary">No routes found.</td>
            </tr>
        <?php endif; ?>

                <!-- Details Route Modal -->
                <div class="modal fade" id="detailsRouteModal" tabindex="-1" aria-labelledby="detailsRouteLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                            <h5 class="modal-title" id="detailsRouteLabel">Vehicle & Route Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <ul class="list-group">
                                    <li class="list-group-item"><strong>Vehicle Name:</strong> <span id="detailsVehicleName"></span></li>
                                    <li class="list-group-item"><strong>Driver Name:</strong> <span id="detailsDriverName"></span></li>
                                    <li class="list-group-item"><strong>Plate Number:</strong> <span id="detailsPlateNumber"></span></li>
                                    <li class="list-group-item"><strong>Vehicle Capacity:</strong> <span id="detailsVehicleType"></span></li>
                                    <li class="list-group-item"><strong>Start Point:</strong> <span id="detailsStartPoint"></span></li>
                                    <li class="list-group-item"><strong>End Point:</strong> <span id="detailsEndPoint"></span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    </div>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <?php include '../includes/footer.php'; ?>
    </main>
    <!-- Tooltip and Scrollbar Init -->
    <script>
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        var win = navigator.platform.indexOf('Win') > -1;
        if (win && document.querySelector('#sidenav-scrollbar')) {
            var options = { damping: '0.5' }
            Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
        }
    </script>

    <style>
    .map-box {
        overflow: hidden;
        position: relative;
        min-height: 400px;
        /* Ensure container is visible immediately */
        opacity: 1;
        visibility: visible;
    }
    #map {
        width: 100% !important;
        height: 100% !important;
        min-height: 400px;
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        /* Prevent layout shift */
        background-color: #e5e5e5;
        /* Show loading state */
        background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMTgiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzMzMzMzMyIgc3Ryb2tlLXdpZHRoPSIyIi8+PC9zdmc+');
        background-repeat: no-repeat;
        background-position: center;
        background-size: 40px;
    }
    .floating-trail-controls {
    position: absolute;
    top: 20px;
    right: 20px;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    gap: 10px;
    }   
    .map-box::after {
        content: '';
        position: absolute;
        right: 0;
        bottom: 0;
        width: 5px;
        height: 5px;
        background: #000;
        cursor: nwse-resize;
        z-index: 999;
    }
    .floating-trail-controls {
        position: absolute;
        top: 20px;
        right: 20px;
        z-index: 1000;
    }
    .btn-floating {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        border: none;
        box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    .btn-floating:hover {
        transform: translateY(-2px);
    }
    /* Vehicle panel water fill */
    .vehicle-panel .vehicle-figure {
        height: 160px;
        border-radius: 12px;
        background: #f8fafc;
        overflow: hidden; /* Keep hidden to clip water fill properly */
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
        background: linear-gradient(180deg, #28a745 0%, #1e7e34 100%); /* Green */
    }
    .vehicle-panel .water-fill-warning {
        background: linear-gradient(180deg, #ffc107 0%, #e0a800 100%); /* Yellow */
    }
    .vehicle-panel .water-fill-normal {
        background: linear-gradient(180deg, #007bff 0%, #0056b3 100%); /* Blue */
    }
    /* Rise-up animation when data first appears */
    .vehicle-panel .water-fill-rise {
        animation: waterRise 1s ease-out;
    }
    @keyframes waterRise {
        0% {
            transform: translateY(20px);
            opacity: 0.5;
        }
        50% {
            transform: translateY(-5px);
        }
        100% {
            transform: translateY(0);
            opacity: 1;
        }
    }
    /* Pulse effect when capacity increases */
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
        font-size: 64px;
        opacity: 0.9;
        pointer-events: none;
        z-index: 2;
    }
    /* Visual indicator when data is available today */
    .vehicle-panel .has-data-today {
        font-weight: 600;
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
</body>
</html>
<!-- Hidden span to pass driver barangay -->
    <script>
        // Global variables
        var map;
        var allBarangays = [];
        var barangayPolygons = {};
        var geojsonLoaded = false;
        var gpsMarker;
        var gpsTrail = []; // Trail for driver's own location
        var trailPolyline = null;
        var trailVisible = false;
        var followingGps = false;
        var driverMarkers = {}; // Store driver location markers
        var previouslyActiveDrivers = new Set(); // Track previously active drivers for notification
        var locationOffNotificationsShown = new Set(); // Track which notifications have been shown
        var currentDriverId = <?php echo (int)$_SESSION['driver_id']; ?>; // Current driver's ID
        var lastTrailLocation = null; // Track last location to avoid duplicates
        
        // Dashboard variables (for forecast and route progress)
        var currentYear = new Date().getFullYear();
        var currentMonth = new Date().getMonth() + 1;
        var selectedWeek = null; // Will be set after first load

        // Global variable to store driver location
        var driverLocation = {
            latitude: null,
            longitude: null,
            barangay: null
        };

        // Detect if device is mobile
        function isMobileDevice() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
                   (window.innerWidth <= 768);
        }

        // Check if HTTPS is available (informational only, don't block)
        function checkHTTPSRequirement() {
            const isLocalhost = window.location.hostname === 'localhost' || 
                               window.location.hostname === '127.0.0.1' ||
                               window.location.hostname === '[::1]';
            const isHTTPS = window.location.protocol === 'https:';
            
            // Always return true to allow geolocation attempt
            // Browser will handle HTTPS requirement and show appropriate errors
            if (!isLocalhost && !isHTTPS && isMobileDevice()) {
                console.warn('Geolocation may have limitations without HTTPS on mobile devices, but attempting anyway...');
            }
            return true; // Always allow attempt
        }

        // Force location access on page load
        function requestDriverLocation() {
            if (!navigator.geolocation) {
                let errorText = 'Your browser does not support geolocation. Please use a modern browser.';
                if (isMobileDevice()) {
                    errorText += ' Please use Chrome, Safari, or Firefox on your mobile device.';
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Location Not Supported',
                    text: errorText,
                    confirmButtonColor: '#3085d6',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                });
                return;
            }

            // Check HTTPS (informational only, don't block)
            // Allow geolocation attempt even without HTTPS - browser will handle it
            checkHTTPSRequirement();

            // Mobile-specific instructions
            const isMobile = isMobileDevice();
            const mobileInstructions = isMobile ? 
                '<p style="font-size: 0.85em; color: #999; margin-top: 10px;"><strong>Mobile:</strong> Ensure GPS is enabled in your device settings.</p>' : 
                '';

            // Show location prompt modal
            Swal.fire({
                icon: 'info',
                title: 'Location Access Required',
                html: `
                    <div style="text-align: center;">
                        <p style="font-size: 1.1em; margin-bottom: 15px;">
                            <strong>Please enable location access</strong>
                        </p>
                        <p style="font-size: 0.9em; color: #666; margin-bottom: 20px;">
                            We need your location to:<br>
                            • Track your vehicle position<br>
                            • Auto-fill barangay in waste uploads<br>
                            • Provide accurate route information
                        </p>
                        <p style="font-size: 0.85em; color: #999;">
                            Click "Allow" when prompted by your browser
                        </p>
                        ${mobileInstructions}
                    </div>
                `,
                showCancelButton: false,
                confirmButtonText: 'Request Location',
                confirmButtonColor: '#3085d6',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    // Mobile-optimized geolocation options
                    const geoOptions = {
                        enableHighAccuracy: true, // Use GPS on mobile for better accuracy
                        timeout: isMobile ? 20000 : 10000, // Longer timeout for mobile (GPS can be slower)
                        maximumAge: isMobile ? 120000 : 0 // Accept cached location on mobile (up to 2 minutes)
                    };
                    
                    // Request location immediately with fallback for mobile
                    function requestLocation() {
                        navigator.geolocation.getCurrentPosition(
                            function(position) {
                            // Validate coordinates
                            if (isNaN(position.coords.latitude) || isNaN(position.coords.longitude) ||
                                position.coords.latitude === 0 || position.coords.longitude === 0) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Invalid Location',
                                    text: 'Received invalid location data. Please try again.',
                                    confirmButtonColor: '#3085d6',
                                    timer: 3000
                                });
                                return;
                            }
                            
                            // Success - store location
                            driverLocation.latitude = position.coords.latitude;
                            driverLocation.longitude = position.coords.longitude;
                            
                            // Store in sessionStorage for use in waste upload
                            sessionStorage.setItem('driverLatitude', driverLocation.latitude);
                            sessionStorage.setItem('driverLongitude', driverLocation.longitude);
                            
                            // Get barangay from location
                            fetch(`../api/get_barangay_by_location.php?latitude=${driverLocation.latitude}&longitude=${driverLocation.longitude}`)
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        driverLocation.barangay = data.barangay;
                                        sessionStorage.setItem('driverBarangay', data.barangay);
                                        
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Location Access Granted',
                                            text: `Location detected: ${data.barangay}`,
                                            confirmButtonColor: '#43A047',
                                            timer: 2000,
                                            timerProgressBar: true
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'warning',
                                            title: 'Location Detected',
                                            text: 'Could not determine barangay, but location is being tracked.',
                                            confirmButtonColor: '#3085d6',
                                            timer: 2000
                                        });
                                    }
                                })
                                .catch(error => {
                                    console.error('Error getting barangay:', error);
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Location Detected',
                                        text: 'Location is being tracked.',
                                        confirmButtonColor: '#3085d6',
                                        timer: 2000
                                    });
                                });
                        },
                        function(error) {
                            // If high accuracy fails on mobile, try with lower accuracy as fallback
                            if (isMobile && error.code === error.TIMEOUT) {
                                console.log('High accuracy timeout, trying with lower accuracy...');
                                navigator.geolocation.getCurrentPosition(
                                    function(position) {
                                        // Success with lower accuracy - store location
                                        driverLocation.latitude = position.coords.latitude;
                                        driverLocation.longitude = position.coords.longitude;
                                        
                                        sessionStorage.setItem('driverLatitude', driverLocation.latitude);
                                        sessionStorage.setItem('driverLongitude', driverLocation.longitude);
                                        
                                        // Get barangay from location
                                        fetch(`../api/get_barangay_by_location.php?latitude=${driverLocation.latitude}&longitude=${driverLocation.longitude}`)
                                            .then(response => response.json())
                                            .then(data => {
                                                if (data.success) {
                                                    driverLocation.barangay = data.barangay;
                                                    sessionStorage.setItem('driverBarangay', data.barangay);
                                                    
                                                    Swal.fire({
                                                        icon: 'success',
                                                        title: 'Location Access Granted',
                                                        text: `Location detected: ${data.barangay}`,
                                                        confirmButtonColor: '#43A047',
                                                        timer: 2000,
                                                        timerProgressBar: true
                                                    });
                                                } else {
                                                    Swal.fire({
                                                        icon: 'warning',
                                                        title: 'Location Detected',
                                                        text: 'Could not determine barangay, but location is being tracked.',
                                                        confirmButtonColor: '#3085d6',
                                                        timer: 2000
                                                    });
                                                }
                                            })
                                            .catch(error => {
                                                console.error('Error getting barangay:', error);
                                                Swal.fire({
                                                    icon: 'warning',
                                                    title: 'Location Detected',
                                                    text: 'Location is being tracked.',
                                                    confirmButtonColor: '#3085d6',
                                                    timer: 2000
                                                });
                                            });
                                    },
                                    function(fallbackError) {
                                        // Fallback also failed, show error
                                        showLocationError(fallbackError);
                                    },
                                    {
                                        enableHighAccuracy: false,
                                        timeout: 15000,
                                        maximumAge: 300000 // Accept up to 5 minutes old location
                                    }
                                );
                            } else {
                                // Show error for other cases
                                showLocationError(error);
                            }
                        },
                        geoOptions
                    );
                }
                
                function showLocationError(error) {
                    // Error - show message based on error type with mobile-specific guidance
                    let errorMessage = 'Unable to get your location. ';
                    let mobileGuidance = '';
                    
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage += 'Location access was denied.';
                            if (isMobile) {
                                mobileGuidance = '<br><br><strong>Mobile Instructions:</strong><br>' +
                                    '1. Go to your device Settings<br>' +
                                    '2. Find Location/GPS settings<br>' +
                                    '3. Enable Location Services<br>' +
                                    '4. Return to browser and allow location access';
                            } else {
                                mobileGuidance = ' Please enable location in your browser settings.';
                            }
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage += 'Location information is unavailable.';
                            if (isMobile) {
                                mobileGuidance = '<br><br>Please ensure:<br>' +
                                    '• GPS is enabled in device settings<br>' +
                                    '• You are in an area with GPS signal<br>' +
                                    '• Location services are turned on';
                            }
                            break;
                        case error.TIMEOUT:
                            errorMessage += 'Location request timed out.';
                            if (isMobile) {
                                mobileGuidance = '<br><br>GPS may be slow to respond. Please:<br>' +
                                    '• Ensure you are outdoors or near a window<br>' +
                                    '• Wait a few seconds and try again<br>' +
                                    '• Check that GPS is enabled';
                            }
                            break;
                        default:
                            errorMessage += 'An unknown error occurred.';
                            break;
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Location Access Required',
                        html: `
                            <div style="text-align: center;">
                                <p style="font-size: 1em; margin-bottom: 15px;">
                                    ${errorMessage}${mobileGuidance}
                                </p>
                                <p style="font-size: 0.9em; color: #666;">
                                    <strong>You must enable location access to use the driver dashboard.</strong><br>
                                    Please refresh the page and allow location when prompted.
                                </p>
                            </div>
                        `,
                        confirmButtonText: 'Refresh Page',
                        confirmButtonColor: '#3085d6',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showCancelButton: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.reload();
                        }
                    });
                }
                
                // Start location request
                requestLocation();
            });
        }

        // Initialize map - optimized for fast loading
        function initializeMap() {
            // Check if Leaflet is loaded
            if (typeof L === 'undefined') {
                // Retry faster
                setTimeout(initializeMap, 50);
                return;
            }
            
            // Check if map container exists and is visible
            const mapContainer = document.getElementById('map');
            if (!mapContainer) {
                setTimeout(initializeMap, 50);
                return;
            }
            
            // Ensure container has dimensions (but don't wait too long)
            if (mapContainer.offsetWidth === 0 || mapContainer.offsetHeight === 0) {
                // Use CSS min-height as fallback, don't wait
                if (mapContainer.style.minHeight) {
                    // Container has CSS height, proceed anyway
                } else {
                    setTimeout(initializeMap, 50);
                    return;
                }
            }
            
            try {
                var bagoBounds = L.latLngBounds(
                    L.latLng(10.4300, 122.7800),
                    L.latLng(10.6500, 123.1000)
                );

                map = L.map('map', {
                    center: [10.5379, 122.8333],
                    zoom: 13,
                    maxBounds: bagoBounds,
                    maxBoundsViscosity: 1.0,
                    minZoom: 12,
                    maxZoom: 18,
                    preferCanvas: false // Use DOM rendering for better compatibility
                });

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                    maxZoom: 19,
                    errorTileUrl: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' // Fallback for failed tiles
                }).addTo(map);

                // Force map to recalculate size immediately after initialization
                // Use requestAnimationFrame for better performance
                requestAnimationFrame(function() {
                    if (map) {
                        map.invalidateSize();
                    }
                });

                // Add Bago City Hall marker (defer popup to avoid blocking)
                const cityHallMarker = L.marker([10.538274, 122.835230]).addTo(map)
                    .bindPopup("<b>Bago City Hall</b>");
                
                // Open popup after a short delay to avoid blocking map render
                setTimeout(function() {
                    cityHallMarker.openPopup();
                }, 200);
                    
                console.log('Map initialized successfully');
                
                // Remove loading background once map tiles start loading
                const mapContainer = document.getElementById('map');
                if (mapContainer) {
                    mapContainer.style.backgroundImage = 'none';
                }
            } catch (error) {
                console.error('Error initializing map:', error);
                // Retry after a short delay
                setTimeout(initializeMap, 500);
            }
        }
        
        // Handle window resize to ensure map displays correctly
        window.addEventListener('resize', function() {
            if (map) {
                setTimeout(function() {
                    map.invalidateSize();
                }, 100);
            }
        });
        
        // Also handle sidebar toggle if it affects map visibility
        document.addEventListener('DOMContentLoaded', function() {
            // Check for sidebar toggle events
            const sidebarToggle = document.querySelector('[data-bs-toggle="sidenav"]');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    setTimeout(function() {
                        if (map) {
                            map.invalidateSize();
                        }
                    }, 300);
                });
            }
        });
        
        window.onload = function () {
            // Initialize map IMMEDIATELY - don't wait for location request
            // This ensures map is visible to user right away
            initializeMap();
            
            // Request location AFTER map is initialized (non-blocking)
            // Use requestIdleCallback to avoid blocking map rendering
            if (window.requestIdleCallback) {
                requestIdleCallback(function() {
                    requestDriverLocation();
                }, { timeout: 1000 });
            } else {
                setTimeout(function() {
                    requestDriverLocation();
                }, 500);
            }

            // Load map data after map is initialized (defer heavy operations)
            function loadMapData() {
                if (!map) {
                    // Retry faster if map not ready
                    setTimeout(loadMapData, 50);
                    return;
                }
                
                // Start driver locations update FIRST (most important)
                updateDriverLocations();
                setInterval(updateDriverLocations, 1000);
                
                // Initialize controls immediately
                initializeTrailControls();
                initializeFollowGps();
                initVehiclePanel();
                
                // Load dashboard summary (non-blocking)
                loadDashboardSummary();
                // Load waste statistics (matching admin dashboard)
                loadWasteStatistics();
                
                // Defer heavy operations (barangay markers, GeoJSON) until after map is visible
                // Use requestIdleCallback for better performance
                function loadHeavyData() {
                    // Fetch barangays and add markers (can be deferred)
                    fetch("../barangay_api/get_barangays.php")
                        .then(response => response.json())
                        .then(data => {
                            allBarangays = data;
                            // Batch marker creation for better performance
                            const markers = [];
                            data.forEach(barangay => {
                                if (barangay.latitude && barangay.longitude && barangay.city === 'Bago City') {
                                    markers.push({
                                        lat: parseFloat(barangay.latitude),
                                        lng: parseFloat(barangay.longitude),
                                        name: barangay.barangay
                                    });
                                }
                            });
                            
                            // Add markers in batches to avoid blocking
                            let index = 0;
                            function addMarkerBatch() {
                                const batchSize = 10;
                                const batch = markers.slice(index, index + batchSize);
                                batch.forEach(m => {
                                    L.marker([m.lat, m.lng])
                                        .addTo(map)
                                        .bindPopup(`<b>${m.name}</b><br>Bago City`);
                                });
                                index += batchSize;
                                if (index < markers.length) {
                                    requestAnimationFrame(addMarkerBatch);
                                } else {
                                    console.log('Barangay markers loaded');
                                }
                            }
                            addMarkerBatch();
                        })
                        .catch(error => console.error("Error fetching barangays:", error));

                    // Load GeoJSON for polygons (used for entry detection only) - defer this
                    fetch("../barangay_api/brgy.geojson")
                        .then(response => response.json())
                        .then(geojson => {
                            geojson.features.forEach(feature => {
                                var name = feature.properties.name;
                                var polygon = L.geoJSON(feature);
                                barangayPolygons[name] = polygon;
                            });
                            geojsonLoaded = true;
                            console.log('GeoJSON polygons loaded');
                        })
                        .catch(error => console.error("Error loading GeoJSON:", error));
                }
                
                // Load heavy data after a short delay or when browser is idle
                if (window.requestIdleCallback) {
                    requestIdleCallback(loadHeavyData, { timeout: 2000 });
                } else {
                    setTimeout(loadHeavyData, 1000);
                }
                
                // Start background updates (non-critical)
                checkDriverOwnLocationStatus();
                setInterval(checkDriverOwnLocationStatus, 3000);
                
                updateDriverLocationForWasteUpload();
                setInterval(updateDriverLocationForWasteUpload, 30000);
                
                // Load bin level after a short delay
                setTimeout(() => {
                    loadBinLevelFromDriverSummary();
                }, 300);
                
                // Refresh data every 30 seconds
                if (!window.driverDashboardRefreshInterval) {
                    window.driverDashboardRefreshInterval = setInterval(() => {
                        console.log('Auto-refreshing driver dashboard data...');
                        loadDashboardSummary();
                        loadWasteStatistics(); // Refresh waste statistics
                    }, 30000);
                }
            }
            
            // Load map data immediately after map initialization
            loadMapData();
        };

        // Update GPS marker
        function updateGpsMarker() {
            fetch('../admin_management/get_latest_gps.php')
                .then(res => res.json())
                .then(data => {
                    // Remove previous GPS marker
                    if (gpsMarker && map.hasLayer(gpsMarker)) {
                        map.removeLayer(gpsMarker);
                    }

                    if (!data.gps_points || !Array.isArray(data.gps_points) || data.gps_points.length === 0) return;

                    var point = data.gps_points[0];
                    if (!point.latitude || !point.longitude) return;

                    const latLng = [point.latitude, point.longitude];

                    // Add GPS marker
                    let icon = L.icon({
                        iconUrl: '../assets/img/gps_icon.png',
                        iconSize: [30, 30],
                        iconAnchor: [15, 30],
                        popupAnchor: [0, -30]
                    });
                    gpsMarker = L.marker(latLng, { icon: icon })
                        .addTo(map)
                        .bindPopup("🚗 Current Vehicle Location");

                    // Add to trail
                    gpsTrail.push(latLng);
                    if (gpsTrail.length > 100) gpsTrail.shift();

                    // Update trail line if visible
                    if (trailVisible) {
                        updateTrailLine();
                    }

                    // Center map on GPS if following
                    if (followingGps) {
                        map.setView(latLng, map.getZoom());
                    }

                    // Track barangay entry/exit
                    trackBarangayEntry(latLng);

                    // Update ETA if vehicle is in driver's barangay
                    const driverBarangay = document.getElementById('clientBarangay').textContent.trim();
                    if (window.lastBrgy === driverBarangay) {
                        updateETA();
                    }
                })
                .catch(err => console.error("Error fetching GPS:", err));
        }
        
        // Update all driver locations on map
        function updateDriverLocations() {
            fetch('../api/get_driver_locations.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.success || !data.drivers) {
                        // No drivers active, check if any were previously active
                        checkForLocationOffNotifications(new Set(), new Map());
                        return;
                    }
                    
                    // Track which drivers are still active
                    const activeDriverIds = new Set();
                    const driverInfoMap = new Map(); // Store driver info for notifications
                    
                    // Update or add markers for each driver
                    data.drivers.forEach(driver => {
                        const driverId = driver.driver_id;
                        activeDriverIds.add(driverId);
                        driverInfoMap.set(driverId, driver);
                        
                        if (!driver.latitude || !driver.longitude) return;
                        
                        const latLng = [driver.latitude, driver.longitude];
                        
                        // Track current driver's location for trail
                        if (driverId === currentDriverId) {
                            // Check if location has changed significantly (at least 10 meters)
                            let shouldAddToTrail = true;
                            if (lastTrailLocation) {
                                const distance = L.latLng(latLng).distanceTo(L.latLng(lastTrailLocation));
                                if (distance < 10) {
                                    // Location hasn't changed much, skip adding to trail
                                    shouldAddToTrail = false;
                                }
                            }
                            
                            if (shouldAddToTrail) {
                                // Add current driver's location to trail
                                gpsTrail.push(latLng);
                                if (gpsTrail.length > 500) gpsTrail.shift(); // Keep last 500 points
                                lastTrailLocation = latLng;
                                
                                // Update trail line if visible
                                if (trailVisible) {
                                    updateTrailLine();
                                }
                            }
                            
                            // Update GPS marker position
                            if (gpsMarker) {
                                gpsMarker.setLatLng(latLng);
                            } else {
                                // Create GPS marker if it doesn't exist
                                let icon = L.icon({
                                    iconUrl: '../assets/img/gps_icon.png',
                                    iconSize: [30, 30],
                                    iconAnchor: [15, 30],
                                    popupAnchor: [0, -30]
                                });
                                gpsMarker = L.marker(latLng, { icon: icon })
                                    .addTo(map)
                                    .bindPopup("🚗 Your Current Location");
                            }
                            
                            // Center map on GPS if following
                            if (followingGps) {
                                map.setView(latLng, map.getZoom());
                            }
                            
                            // Track barangay entry/exit for current driver
                            trackBarangayEntry(latLng);
                        }
                        
                        // Check if location is active or inactive (GPS off)
                        const isActive = driver.is_active !== undefined ? driver.is_active : 1;
                        const statusBadge = isActive 
                            ? '<span class="badge bg-success">GPS Active</span>' 
                            : '<span class="badge bg-warning">GPS Off</span>';
                        const statusText = isActive 
                            ? 'Real-time location' 
                            : 'Last known location (GPS off)';
                        
                        // Create popup content
                        const popupContent = `
                            <div style="text-align: center;">
                                <b>🚗 Driver Location</b><br>
                                <strong>${driver.full_name}</strong><br>
                                ${statusBadge}<br>
                                <small>Vehicle: ${driver.vehicle_name}</small><br>
                                <small>Plate: ${driver.plate_no}</small><br>
                                <small style="color: #666;">${statusText}</small><br>
                                <small style="color: #666;">Updated: ${new Date(driver.timestamp).toLocaleTimeString()}</small>
                            </div>
                        `;
                        
                        // Create custom icon for driver location
                        let driverIcon = L.icon({
                            iconUrl: '../assets/img/gps_icon.png',
                            iconSize: [32, 32],
                            iconAnchor: [16, 32],
                            popupAnchor: [0, -32]
                        });
                        
                        // Use different icon style for inactive locations
                        if (!isActive) {
                            driverIcon = L.icon({
                                iconUrl: '../assets/img/gps_icon.png',
                                iconSize: [32, 32],
                                iconAnchor: [16, 32],
                                popupAnchor: [0, -32],
                                className: 'inactive-location-marker'
                            });
                        }
                        
                        // Add or update marker - if exists, just update position for real-time movement
                        if (driverMarkers[driverId] && map.hasLayer(driverMarkers[driverId])) {
                            // Marker exists, update position smoothly for real-time tracking
                            driverMarkers[driverId].setLatLng(latLng);
                            driverMarkers[driverId].setPopupContent(popupContent);
                            if (!isActive) {
                                driverMarkers[driverId].setOpacity(0.6);
                                driverMarkers[driverId].setIcon(driverIcon);
                            } else {
                                driverMarkers[driverId].setOpacity(1.0);
                                driverMarkers[driverId].setIcon(driverIcon);
                            }
                        } else {
                            // Create new marker and immediately pin it to map
                            driverMarkers[driverId] = L.marker(latLng, {
                                icon: driverIcon,
                                zIndexOffset: 500 // Make drivers appear above other markers
                            })
                            .addTo(map) // Immediately add to map
                            .bindPopup(popupContent);
                            
                            // Add opacity for inactive markers
                            if (!isActive && driverMarkers[driverId]) {
                                driverMarkers[driverId].setOpacity(0.6);
                            }
                            
                            // Ensure marker is visible on map
                            console.log(`Driver ${driver.full_name} location pinned at [${latLng[0]}, ${latLng[1]}]`);
                        }
                    });
                    
            // Check for drivers who turned off location
            checkForLocationOffNotifications(activeDriverIds, driverInfoMap);
            
            // Remove markers for drivers that are no longer active (and not inactive but recent)
            Object.keys(driverMarkers).forEach(driverId => {
                const driverIdInt = parseInt(driverId);
                // Keep marker if driver is in active list OR has inactive but recent location
                const driver = driverInfoMap.get(driverIdInt);
                const shouldKeep = activeDriverIds.has(driverIdInt) || 
                                 (driver && driver.is_active === 0); // Keep inactive markers too
                
                if (!shouldKeep) {
                    if (driverMarkers[driverId] && map.hasLayer(driverMarkers[driverId])) {
                        map.removeLayer(driverMarkers[driverId]);
                    }
                    delete driverMarkers[driverId];
                }
            });
            
            // Update previously active drivers set for next check
            previouslyActiveDrivers = new Set(activeDriverIds);
                })
                .catch(error => {
                    console.error('Error fetching driver locations:', error);
                });
        }
        
        // Continuously update driver location for waste upload auto-fill
        function updateDriverLocationForWasteUpload() {
            if (navigator.geolocation) {
                const isMobile = isMobileDevice();
                const geoOptions = {
                    enableHighAccuracy: true,
                    timeout: isMobile ? 10000 : 5000, // Longer timeout for mobile
                    maximumAge: isMobile ? 60000 : 30000 // Accept cached location (1 min on mobile, 30 sec on desktop)
                };
                
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        // Validate coordinates
                        if (isNaN(position.coords.latitude) || isNaN(position.coords.longitude) ||
                            position.coords.latitude === 0 || position.coords.longitude === 0) {
                            console.warn('Invalid coordinates received');
                            return;
                        }
                        
                        // Update location
                        driverLocation.latitude = position.coords.latitude;
                        driverLocation.longitude = position.coords.longitude;
                        
                        // Store in sessionStorage for use in waste upload
                        sessionStorage.setItem('driverLatitude', driverLocation.latitude);
                        sessionStorage.setItem('driverLongitude', driverLocation.longitude);
                        
                        // Update barangay if needed
                        fetch(`../api/get_barangay_by_location.php?latitude=${driverLocation.latitude}&longitude=${driverLocation.longitude}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success && data.barangay) {
                                    driverLocation.barangay = data.barangay;
                                    sessionStorage.setItem('driverBarangay', data.barangay);
                                }
                            })
                            .catch(error => console.error('Error updating barangay:', error));
                    },
                    function(error) {
                        // Silently fail - location might be temporarily unavailable
                        console.warn('Location update failed:', error.message);
                    },
                    geoOptions
                );
            }
        }

        // Check driver's own location status (for driver dashboard)
        function checkDriverOwnLocationStatus() {
            const driverId = <?php echo (int)$_SESSION['driver_id']; ?>;
            
            fetch('../api/get_driver_locations.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.success || !data.drivers) return;
                    
                    // Find the current driver in the response
                    const currentDriver = data.drivers.find(d => d.driver_id === driverId);
                    
                    if (!currentDriver) return;
                    
                    const isActive = currentDriver.is_active !== undefined ? currentDriver.is_active : 1;
                    
                    // Check if this is the first time checking or if status changed
                    if (!window.driverLocationStatusChecked) {
                        window.driverLocationStatusChecked = true;
                        window.lastDriverLocationStatus = isActive;
                        return;
                    }
                    
                    // If driver was active before but is now inactive, show alert
                    if (window.lastDriverLocationStatus === 1 && isActive === 0 && !window.driverLocationOffAlertShown) {
                        window.driverLocationOffAlertShown = true;
                        
                        // Show SweetAlert to driver
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Location Turned Off',
                                html: `
                                    <div style="text-align: center;">
                                        <p style="font-size: 1.1em; margin-bottom: 10px;">
                                            <strong>Your location was turned off</strong>
                                        </p>
                                        <p style="font-size: 0.9em; color: #666; margin-top: 10px;">
                                            Your location tracking has been disabled.<br>
                                            Please enable location access in your device settings<br>
                                            to continue real-time tracking.<br><br>
                                            <strong>Your last known location will be shown on the map.</strong>
                                        </p>
                                    </div>
                                `,
                                confirmButtonColor: '#ff9800',
                                confirmButtonText: 'I Understand',
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                timer: 12000,
                                timerProgressBar: true
                            }).then(() => {
                                window.driverLocationOffAlertShown = false; // Allow alert to show again if still off
                            });
                        } else {
                            alert('Your location was turned off. Please enable location access to continue tracking.');
                            window.driverLocationOffAlertShown = false;
                        }
                    }
                    
                    // If driver turned location back on, reset alert flag
                    if (isActive === 1 && window.lastDriverLocationStatus === 0) {
                        window.driverLocationOffAlertShown = false;
                        
                        // Show success message when location is turned back on
                        if (typeof Swal !== 'undefined' && !window.driverLocationOnAlertShown) {
                            window.driverLocationOnAlertShown = true;
                            Swal.fire({
                                icon: 'success',
                                title: 'Location Access Enabled',
                                text: 'Your location is now being tracked in real-time.',
                                confirmButtonColor: '#43A047',
                                confirmButtonText: 'OK',
                                timer: 3000,
                                timerProgressBar: true
                            }).then(() => {
                                window.driverLocationOnAlertShown = false;
                            });
                        }
                    }
                    
                    window.lastDriverLocationStatus = isActive;
                })
                .catch(error => {
                    console.error('Error checking driver location status:', error);
                });
        }
        
        // Check for drivers who turned off their location and show notifications
        function checkForLocationOffNotifications(activeDriverIds, driverInfoMap) {
            // Check each previously active driver
            previouslyActiveDrivers.forEach(driverId => {
                // Get current and previous driver info
                const currentDriver = driverInfoMap.get(driverId);
                const previousDriver = window.lastKnownDriverInfo ? window.lastKnownDriverInfo.get(driverId) : null;
                
                // Check if driver was active before but is now inactive
                const wasActive = previousDriver && (previousDriver.is_active === undefined || previousDriver.is_active === 1);
                const isNowInactive = currentDriver && currentDriver.is_active === 0;
                const driverNotInActiveList = !activeDriverIds.has(driverId);
                
                // If driver was previously active but is now inactive or not in active list, show notification
                if (wasActive && (isNowInactive || driverNotInActiveList) && !locationOffNotificationsShown.has(driverId)) {
                    // Get driver info
                    const driverInfo = currentDriver || previousDriver;
                    let driverName = driverInfo ? (driverInfo.full_name || 'Driver') : 'Driver';
                    let vehicleName = driverInfo ? (driverInfo.vehicle_name || 'Vehicle') : 'Vehicle';
                    
                    // Mark notification as shown
                    locationOffNotificationsShown.add(driverId);
                    
                    // Show notification using SweetAlert
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Location Access Turned Off',
                            html: `
                                <div style="text-align: center;">
                                    <p style="font-size: 1.1em; margin-bottom: 10px;">
                                        Vehicle <strong>${vehicleName}</strong>, Driver <strong>${driverName}</strong> has turned off their location.
                                    </p>
                                    <p style="font-size: 0.9em; color: #666; margin-top: 10px;">
                                        Last known location will continue to be shown on the map.<br>
                                        Real-time tracking will resume when location is turned back on.
                                    </p>
                                </div>
                            `,
                            confirmButtonColor: '#3085d6',
                            confirmButtonText: 'OK',
                            timer: 8000,
                            timerProgressBar: true
                        });
                    } else {
                        // Fallback to regular alert
                        alert(`Vehicle ${vehicleName}, Driver ${driverName} has turned off their location.`);
                    }
                }
            });
            
            // Store current driver info for future reference
            if (!window.lastKnownDriverInfo) {
                window.lastKnownDriverInfo = new Map();
            }
            driverInfoMap.forEach((info, driverId) => {
                window.lastKnownDriverInfo.set(driverId, info);
            });
            
            // Clean up notifications for drivers who are active again
            activeDriverIds.forEach(driverId => {
                const driver = driverInfoMap.get(driverId);
                if (driver && driver.is_active === 1) {
                    locationOffNotificationsShown.delete(driverId);
                }
            });
        }

        // Track if vehicle enters/exits a barangay
        function trackBarangayEntry(latLng) {
            let insideAny = false;
            let currentBrgy = null;

            Object.keys(barangayPolygons).forEach(name => {
                var polygon = barangayPolygons[name];
                if (polygon && geojsonLoaded) {
                    polygon.eachLayer(function(layer) {
                        if (layer instanceof L.Polygon) {
                            if (layer.contains && layer.contains(latLng)) {
                                insideAny = true;
                                currentBrgy = name;
                            } else {
                                // Fallback point-in-polygon check
                                var polyLatLngs = layer.getLatLngs()[0];
                                var x = latLng[1], y = latLng[0];
                                var inside = false;
                                for (var i = 0, j = polyLatLngs.length - 1; i < polyLatLngs.length; j = i++) {
                                    var xi = polyLatLngs[i].lng, yi = polyLatLngs[i].lat;
                                    var xj = polyLatLngs[j].lng, yj = polyLatLngs[j].lat;
                                    var intersect = ((yi > y) !== (yj > y)) && (x < (xj - xi) * (y - yi) / (yj - yi + 1e-10) + xi);
                                    if (intersect) inside = !inside;
                                }
                                if (inside) {
                                    insideAny = true;
                                    currentBrgy = name;
                                }
                            }
                        }
                    });
                }
            });
        }

        // Load bin level from driver dashboard summary (uses driver_waste_uploads, not sensor)
        function loadBinLevelFromDriverSummary() {
            // Get driver_id from session
            const driverId = <?php echo isset($_SESSION['driver_id']) ? (int)$_SESSION['driver_id'] : 0; ?>;
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

        // Vehicle info panel
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

        // Update trail line
        function updateTrailLine() {
            if (trailPolyline) {
                map.removeLayer(trailPolyline);
                trailPolyline = null;
            }
            if (gpsTrail.length > 1) {
                trailPolyline = L.polyline(gpsTrail, {
                    color: '#ff6b35',
                    weight: 4,
                    opacity: 0.9,
                    smoothFactor: 1.0
                }).addTo(map);
                
                // Fit map to show entire trail if it's the first time showing
                if (gpsTrail.length > 2 && !window.trailBoundsSet) {
                    const trailBounds = L.latLngBounds(gpsTrail);
                    map.fitBounds(trailBounds, { padding: [50, 50] });
                    window.trailBoundsSet = true;
                }
            }
        }

        // Initialize View/Hide Trail button
        function initializeTrailControls() {
            const trailBtn = document.getElementById('viewTrail');
            if (!trailBtn) return;

            trailBtn.addEventListener('click', function () {
                trailVisible = !trailVisible;

                if (trailVisible) {
                    this.classList.remove('btn-outline-primary');
                    this.classList.add('btn-success');
                    this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                    this.title = 'Hide Trail';
                    
                    // Update trail line immediately
                    if (gpsTrail.length > 1) {
                        updateTrailLine();
                        const trailMessage = gpsTrail.length > 1 
                            ? `Your location trail is now visible (${gpsTrail.length} points tracked).`
                            : 'Your location trail will appear as you move.';
                        Swal.fire({
                            icon: 'success',
                            title: 'Trail Visible',
                            text: trailMessage,
                            confirmButtonColor: '#3085d6',
                            confirmButtonText: 'OK',
                            timer: 2000,
                            timerProgressBar: true
                        });
                    } else {
                        Swal.fire({
                            icon: 'info',
                            title: 'Trail Enabled',
                            text: 'Your location trail will appear as you move. Start moving to see your trail.',
                            confirmButtonColor: '#3085d6',
                            confirmButtonText: 'OK',
                            timer: 3000,
                            timerProgressBar: true
                        });
                    }
                } else {
                    this.classList.remove('btn-success');
                    this.classList.add('btn-outline-primary');
                    this.innerHTML = '<i class="fas fa-route"></i>';
                    this.title = 'View Trail';
                    if (trailPolyline) {
                        map.removeLayer(trailPolyline);
                        trailPolyline = null;
                    }
                    window.trailBoundsSet = false; // Reset bounds flag
                    Swal.fire({
                        icon: 'info',
                        title: 'Trail Hidden',
                        text: 'Your location trail is now hidden.',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK',
                        timer: 2000,
                        timerProgressBar: true
                    });
                }
            });
        }

        // Initialize Follow GPS button
        function initializeFollowGps() {
            const followBtn = document.getElementById('followGps');
            if (!followBtn) return;

            followBtn.addEventListener('click', function () {
                followingGps = !followingGps;

                if (followingGps) {
                    this.classList.remove('btn-outline-primary');
                    this.classList.add('btn-info');
                    this.innerHTML = '<i class="fas fa-bullseye"></i>';
                    this.title = 'Stop Following';
                    if (gpsMarker) {
                        map.setView(gpsMarker.getLatLng(), map.getZoom());
                    }
                    Swal.fire({
                        icon: 'info',
                        title: 'Following GPS',
                        text: 'Map is now following the vehicle.',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    });
                } else {
                    this.classList.remove('btn-info');
                    this.classList.add('btn-outline-primary');
                    this.innerHTML = '<i class="fas fa-location-arrow"></i>';
                    this.title = 'Follow GPS';
                    Swal.fire({
                        icon: 'info',
                        title: 'Stopped Following',
                        text: 'Map will no longer follow the vehicle.',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }

        // Estimate arrival time to driver's barangay
        function updateETA() {
            if (!alertsEnabled || etaAlertShown) return; // Skip if disabled or already shown

            const driverBarangay = document.getElementById('clientBarangay').textContent.trim();
            const barangay = allBarangays.find(b => b.barangay === driverBarangay);
            if (!barangay || !gpsMarker) return;

            const dest = L.latLng(parseFloat(barangay.latitude), parseFloat(barangay.longitude));
            const current = gpsMarker.getLatLng();
            const distance = current.distanceTo(dest) / 1000; // km
            const speed = 30; // km/h
            const time = (distance / speed) * 60; // minutes

            let msg;
            if (time < 60) {
                msg = `Arriving in ${Math.round(time)} minute${Math.round(time) !== 1 ? 's' : ''}.`;
            } else {
                const hrs = Math.floor(time / 60);
                const mins = Math.round(time % 60);
                msg = `Arriving in ${hrs}h ${mins}m.`;
            }

            Swal.fire({
                icon: 'info',
                title: 'Estimated Arrival',
                text: msg,
                timer: 5000,
                showConfirmButton: false
            });

            etaAlertShown = true; // Mark as shown
        }

        // --- Load Dashboard Summary (Same as Admin Dashboard) ---
        // Load waste collection statistics (using SAME APIs as admin dashboard for system-wide data)
        async function loadWasteStatistics() {
            try {
                // Use the SAME APIs as admin dashboard to get system-wide data
                // 1. Load Collected Waste Today from get_dashboard_summary.php (same as admin)
                const summaryRes = await fetch('../api/get_dashboard_summary.php');
                
                if (!summaryRes.ok) {
                    throw new Error(`HTTP error! status: ${summaryRes.status}`);
                }
                
                const summaryData = await summaryRes.json();
                
                if (summaryData.success) {
                    // Collected Waste Today (convert to percentage: multiply by 100) - SAME as admin
                    const todayEl = document.getElementById('collectedWasteToday');
                    if (todayEl) {
                        const todayTons = parseFloat(summaryData.todayTons) || 0;
                        const todayPercent = todayTons * 100; // Convert to percentage
                        todayEl.textContent = todayPercent.toFixed(0) + '% volume';
                    }
                }
                
                // 2. Load Waste Collected This Week, Average Daily Collection, and Collection Efficiency
                // from get_admin_statistics.php (same as admin dashboard)
                const statsRes = await fetch('../api/get_admin_statistics.php');
                
                if (!statsRes.ok) {
                    throw new Error(`HTTP error! status: ${statsRes.status}`);
                }
                
                const statsData = await statsRes.json();
                
                if (statsData.success) {
                    // Waste Collected This Week (convert count to tons: 1 count = 0.001 tons) - SAME as admin
                    const weeklyCount = parseFloat(statsData.weekly_waste_count) || 0;
                    const weeklyTons = (weeklyCount * 0.001).toFixed(2);
                    const weeklyEl = document.getElementById('wasteCollectedThisWeek');
                    if (weeklyEl) {
                        weeklyEl.textContent = weeklyTons + ' estimated tons';
                    }
                    
                    // Average Daily Collection (convert to % waste volume) - SAME as admin
                    const avgDaily = parseFloat(statsData.avg_daily_collection) || 0;
                    const avgTons = avgDaily * 0.001; // Convert count to tons
                    const avgPercentage = (avgTons * 100).toFixed(0); // Convert to percentage
                    const avgDailyEl = document.getElementById('avgDailyCollection');
                    if (avgDailyEl) {
                        avgDailyEl.textContent = avgPercentage + '% waste volume';
                    }
                    
                    // Collection Efficiency - SAME as admin
                    const efficiency = parseFloat(statsData.collection_efficiency) || 0;
                    const efficiencyEl = document.getElementById('collectionEfficiency');
                    if (efficiencyEl) {
                        efficiencyEl.textContent = efficiency.toFixed(0) + '%';
                    }
                } else {
                    throw new Error(statsData.error || 'Unknown error');
                }
            } catch (e) {
                console.error('Error loading waste statistics:', e);
                // Set default values on error
                const todayEl = document.getElementById('collectedWasteToday');
                const weeklyEl = document.getElementById('wasteCollectedThisWeek');
                const avgDailyEl = document.getElementById('avgDailyCollection');
                const efficiencyEl = document.getElementById('collectionEfficiency');
                if (todayEl) todayEl.textContent = '0% volume';
                if (weeklyEl) weeklyEl.textContent = '0.00 estimated tons';
                if (avgDailyEl) avgDailyEl.textContent = '0% waste volume';
                if (efficiencyEl) efficiencyEl.textContent = '0%';
            }
        }

        async function loadDashboardSummary() {
            try {
                console.log('Loading dashboard summary...');
                const res = await fetch('../api/get_dashboard_summary.php');
                
                if (!res.ok) {
                    throw new Error(`HTTP error! status: ${res.status}`);
                }
                
                const data = await res.json();
                console.log('Dashboard summary API response:', data);

                if (data.success) {
                    // Update any cards if needed in the future
                    console.log('Dashboard summary loaded successfully');
                } else {
                    throw new Error(data.error || 'Unknown error');
                }
            } catch (error) {
                console.error('Error loading dashboard summary:', error);
            }
        }

        // View Route - Only show route line, no polygon
        document.addEventListener('DOMContentLoaded', () => {
            const buttons = document.querySelectorAll('.view-route');
            buttons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const barangayName = button.getAttribute('data-barangay');
                    const barangay = allBarangays.find(b => b.barangay === barangayName);
                    if (!barangay || !barangay.latitude || !barangay.longitude) {
                        Swal.fire('Error', `Coordinates not found for ${barangayName}`, 'error');
                        return;
                    }

                    const startPoint = [10.538274, 122.835230];
                    const endPoint = [parseFloat(barangay.latitude), parseFloat(barangay.longitude)];

                    // Remove any existing routing
                    map.eachLayer(layer => {
                        if (layer instanceof L.Routing.Control) {
                            map.removeControl(layer);
                        }
                    });

                    // Add routing (only the line)
                    L.Routing.control({
                        waypoints: [
                            L.latLng(startPoint[0], startPoint[1]),
                            L.latLng(endPoint[0], endPoint[1])
                        ],
                        routeWhileDragging: false,
                        lineOptions: { styles: [{ color: 'blue', weight: 4 }] },
                        createMarker: function () { return null; },
                        show: false,
                        addWaypoints: false,
                        draggableWaypoints: false
                    }).addTo(map);
                });
            });
            
            // Details Route Modal handlers
            const detailsButtons = document.querySelectorAll('.details-route-btn');
            detailsButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('detailsVehicleName').textContent = this.getAttribute('data-vehicle') || '—';
                    document.getElementById('detailsDriverName').textContent = this.getAttribute('data-driver') || '—';
                    document.getElementById('detailsPlateNumber').textContent = this.getAttribute('data-plate') || '—';
                    document.getElementById('detailsVehicleType').textContent = this.getAttribute('data-type') || '—';
                    document.getElementById('detailsStartPoint').textContent = this.getAttribute('data-start') || '—';
                    document.getElementById('detailsEndPoint').textContent = this.getAttribute('data-end') || '—';
                });
            });
        });
    </script>
