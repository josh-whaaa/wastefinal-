<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify user is a driver
if (!isset($_SESSION['driver_id']) || $_SESSION['user_role'] !== 'driver') {
    // If not a driver, redirect to login
    header("Location: ../login_page/sign-in.php");
    exit();
}

// Get current page name for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

    <aside
    class="sidenav navbar navbar-vertical navbar-expand-xs border-radius-lg fixed-start ms-2 my-2"
    id="sidenav-main"
    style="background-color: #1c2e4a;"
    >

    <div class="sidenav-header">
        <a class="navbar-brand px-4 py-3 m-0" href="#">
            <img src="../assets/img/logo.png" class="navbar-brand-img" width="30" height="30" alt="main_logo">
            <span class="ms-1 text-sm text-white">BAGO CITY - CEMO</span>
        </a>
</div>
    <hr class="horizontal light mt-0 mb-2">
    <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'driver_map.php') !== false || strpos($_SERVER['REQUEST_URI'], 'driver_dashboard.php') !== false) ? 'active text-white' : 'text-light'; ?>" 
                href="../admin_management/driver_map.php">
                    <i class="material-symbols-rounded opacity-5">map</i>
                    <span class="nav-link-text ms-1">Map Dashboard</span>
                </a>
            </li>

            
            <li class="nav-item mt-3">
                <h6 class="ps-4 ms-2 text-uppercase text-xs text-light font-weight-bolder opacity-5">Waste Management</h6>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'waste_upload.php') ? 'active bg-gradient-dark text-white' : 'text-light'; ?>" href="../admin_management/waste_upload.php">
                    <i class="material-symbols-rounded opacity-5">recycling</i>
                    <span class="nav-link-text ms-1">Waste Uploads</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'drivers_notification.php') ? 'active bg-gradient-dark text-white' : 'text-light'; ?>" href="../admin_management/drivers_notification.php">
                    <i class="material-symbols-rounded opacity-5">notifications</i>
                    <span class="nav-link-text ms-1">Notifications</span>
                </a>
            </li>

            <!-- <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'sign-in.php') ? 'active bg-gradient-dark text-white' : 'text-light'; ?>"
                href="../login_page/sign-in.php">
                    <i class="material-symbols-rounded opacity-5">login</i>
                    <span class="nav-link-text ms-1">Sign In</span>
                </a>
            </li> -->   
            <!-- <li class="nav-item">
              <a class="nav-link text-light" href="javascript:void(0);" onclick="showLogoutToast();">
                <i class="material-symbols-rounded opacity-5">logout</i>
                  <span class="nav-link-text ms-1">Logout</span>
              </a>
            </li> -->

                <!-- Toast Container (Centered at Top) -->
            <div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1050;">
              <div id="logoutToast" class="toast text-bg-light border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                  <strong class="me-auto">Confirm Logout</strong>
                  <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
              <div class="toast-body text-center"> Are you sure you want to log out?
              <div class="d-flex justify-content-center gap-9 mt-4 pt-4 border-top">
                <button type="button" class="btn btn-danger btn-sm" onclick="logoutUser();">Yes, Logout</button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="toast">Cancel</button>
              </div>

        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
function showLogoutToast() {
    let toastElement = document.getElementById('logoutToast');
    let toast = new bootstrap.Toast(toastElement);
    toast.show();
}

function logoutUser() {
    window.location.href = "../login_page/logout.php"; // Redirect to logout script
}

</script>

<!-- Add this in your <head> -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>



        </ul>
    </div>
</aside>

