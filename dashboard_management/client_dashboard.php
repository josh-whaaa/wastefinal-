<?php
session_start();
include '../includes/conn.php';

// Check if the user is logged in as a client
if (!isset($_SESSION['client_id']) || $_SESSION['user_role'] !== 'client') {
    // Redirect to the login page if not logged in as client
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

            // Get client information
            $client_id = $_SESSION['client_id'];
            $stmt = $pdo->prepare("SELECT barangay FROM client_table WHERE client_id = ?");
            $stmt->execute([$client_id]);
            $client = $stmt->fetch();
            if (!$client) {
                die("Client not found.");
            }
            $clientBarangay = $client['barangay'];


            $page_title = "Bago City Map";
            include '../includes/header.php'; // Includes the head section and styles
?>


<body class="g-sidenav-show bg-gray-200">
    <!-- Sidebar -->
    <?php include '../sidebar/client_sidebar.php'; ?>

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
            <h2 class="text-center">Bago City Map</h2>
            
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
                        </div>

                        <div class="mb-3">
                            <div class="text-sm text-secondary mb-1">
                                <i class="fas fa-route"></i> Route
                            </div>
                            <div id="vehicleRoute" class="text-dark">—</div>
                        </div>

                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="text-sm text-secondary">Capacity</div>
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
            <!-- Hidden span to pass client barangay -->
            <span id="clientBarangay" style="display: none;"><?= htmlspecialchars($clientBarangay) ?></span>

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
<!-- Hidden span to pass client barangay -->
<script>
        // Global variables
        var map;
        var allBarangays = [];
        var barangayPolygons = {};
        var geojsonLoaded = false;
        var gpsMarker;
        var gpsTrail = [];
        var trailPolyline = null;
        var trailVisible = false;
        var followingGps = false;

        // Initialize map
        window.onload = function () {
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
                maxZoom: 18
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            // Add Bago City Hall marker
            L.marker([10.538274, 122.835230]).addTo(map)
                .bindPopup("<b>Bago City Hall</b>")
                .openPopup();

            // Fetch barangays and add markers
            fetch("../barangay_api/get_barangays.php")
                .then(response => response.json())
                .then(data => {
                    allBarangays = data;
                    data.forEach(barangay => {
                        if (barangay.latitude && barangay.longitude && barangay.city === 'Bago City') {
                            L.marker([parseFloat(barangay.latitude), parseFloat(barangay.longitude)])
                                .addTo(map)
                                .bindPopup(`<b>${barangay.barangay}</b><br>Bago City`);
                        }
                    });
                })
                .catch(error => console.error("Error fetching barangays:", error));

            // Load GeoJSON for polygons (used for entry detection only)
            fetch("../barangay_api/brgy.geojson")
                .then(response => response.json())
                .then(geojson => {
                    geojson.features.forEach(feature => {
                        var name = feature.properties.name;
                        var polygon = L.geoJSON(feature);
                        barangayPolygons[name] = polygon;
                    });
                    geojsonLoaded = true;
                })
                .catch(error => console.error("Error loading GeoJSON:", error));

            // Start GPS update
            updateGpsMarker();
            setInterval(updateGpsMarker, 1000);

            // Initialize controls
            initializeTrailControls();
            initializeFollowGps();
            initVehiclePanel();
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

                    // Update ETA if vehicle is in client's barangay
                    const clientBarangay = document.getElementById('clientBarangay').textContent.trim();
                    if (window.lastBrgy === clientBarangay) {
                        updateETA();
                    }
                })
                .catch(err => console.error("Error fetching GPS:", err));
        }

        // Track if vehicle enters/exits a barangay (only for client's barangay)
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

            const clientBarangay = document.getElementById('clientBarangay').textContent.trim();

            // Only trigger for client's barangay
            if (insideAny && currentBrgy === clientBarangay) {
                if (window.lastBrgy !== currentBrgy) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Vehicle Arrived!',
                        text: `The vehicle has arrived at your barangay: ${currentBrgy}.`,
                        confirmButtonColor: '#28a745',
                        confirmButtonText: 'OK'
                    });
                    window.lastBrgy = currentBrgy;
                }
            } else if (window.lastBrgy === clientBarangay && (!insideAny || currentBrgy !== clientBarangay)) {
                window.lastBrgy = null;
            }
        }

        // Load bin level from driver dashboard summary (uses driver_waste_uploads, not sensor)
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


        // Update trail line
        function updateTrailLine() {
            if (trailPolyline) {
                map.removeLayer(trailPolyline);
            }
            if (gpsTrail.length > 1) {
                trailPolyline = L.polyline(gpsTrail, {
                    color: '#ff6b35',
                    weight: 3,
                    opacity: 0.8
                }).addTo(map);
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
                    if (gpsTrail.length > 0) updateTrailLine();
                    Swal.fire({
                        icon: 'success',
                        title: 'Trail Visible',
                        text: 'GPS trail is now visible.',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    });
                } else {
                    this.classList.remove('btn-success');
                    this.classList.add('btn-outline-primary');
                    this.innerHTML = '<i class="fas fa-route"></i>';
                    this.title = 'View Trail';
                    if (trailPolyline) {
                        map.removeLayer(trailPolyline);
                        trailPolyline = null;
                    }
                    Swal.fire({
                        icon: 'info',
                        title: 'Trail Hidden',
                        text: 'GPS trail is now hidden.',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
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

        // Estimate arrival time to client's barangay
        function updateETA() {
            if (!alertsEnabled || etaAlertShown) return; // Skip if disabled or already shown

            const clientBarangay = document.getElementById('clientBarangay').textContent.trim();
            const barangay = allBarangays.find(b => b.barangay === clientBarangay);
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
        });
    </script>


