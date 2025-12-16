<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify user is an admin
if (!isset($_SESSION['admin_id']) || $_SESSION['user_role'] !== 'admin') {
    // If not an admin, redirect to login
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
  <i class="fas fa-times p-3 cursor-pointer text-white opacity-75 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
  <a class="navbar-brand px-4 py-3 m-0" href="#">
    <img src="../assets/img/logo.png" class="navbar-brand-img" width="30" height="30" alt="main_logo">
    <span class="ms-1 text-sm text-white">BAGO CITY - CEMO</span>
  </a>
</div>



                <!-- Admin Management Section -->
    <hr class="horizontal dark mt-0 mb-2">
    <!-- <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main" style="overflow-y: auto; max-height: 100vh;"> -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active bg-gradient-dark text-white' : 'text-light'; ?>" 
                href="../dashboard_management/admin_dashboard.php">
                    <i class="material-symbols-rounded opacity-5">recycling</i>
                    <span class="nav-link-text ms-1"> Waste Dashboard</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active bg-gradient-dark text-white' : 'text-light'; ?>" 
                href="../admin_management/admin_map.php">
                    <i class="material-symbols-rounded opacity-5">public</i>
                    <span class="nav-link-text ms-1">Map Dashboard</span>
                </a>
            </li>
            <!-- Barangay Management -->
            <li class="nav-item mt-3">
                <h6 class="ps-4 ms-2 text-uppercase text-xs text-light font-weight-bolder opacity-5">Barangay Management</h6>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == '../admin_management/admin_barangay_list.php') ? 'active bg-gradient-dark text-white' : 'text-light'; ?>" href="../admin_management/admin_barangay_list.php">
                    <i class="material-symbols-rounded opacity-5">article</i>
                    <span class="nav-link-text ms-1">Barangay List</span>
                </a>
            </li>

            <!-- User Client Management -->
            <li class="nav-item mt-3">
                <h6 class="ps-4 ms-2 text-uppercase text-xs text-light font-weight-bolder opacity-5">Client Management</h6>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == '../admin_management/admin_user_client.php') ? 'active bg-gradient-dark text-white' : 'text-light'; ?>" href="../admin_management/admin_user_client.php">
                    <i class="material-symbols-rounded opacity-5">article</i>
                    <span class="nav-link-text ms-1">Client List</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == '../admin_management/admin_requests.php') ? 'active bg-gradient-dark text-white' : 'text-light'; ?>" href="../admin_management/admin_requests.php">
                <i class="material-symbols-rounded opacity-5">request_page</i>
                <span class="nav-link-text ms-1">Client Requests</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == '../admin_management/threshold_settings.php') ? 'active bg-gradient-dark text-white' : 'text-light'; ?>" href="../admin_management/threshold_settings.php">
                <i class="material-symbols-rounded opacity-5">settings</i>
                <span class="nav-link-text ms-1">Threshold Settings</span>
                </a>
            </li>


            <!-- User Role Management -->
            <li class="nav-item mt-3">
                <h6 class="ps-4 ms-2 text-uppercase text-xs text-light font-weight-bolder opacity-5">User Role Management</h6>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == '../admin_management/admin_role_list.php') ? 'active bg-gradient-dark text-white' : 'text-light'; ?>" href="../admin_management/admin_role_list.php">
                    <i class="material-symbols-rounded opacity-5">article</i>
                    <span class="nav-link-text ms-1">Staff List</span>
                </a>
            </li>
             <!-- Vehicle  Management -->
             <li class="nav-item mt-3">
                <h6 class="ps-4 ms-2 text-uppercase text-xs text-light font-weight-bolder opacity-5">Vehicle Management</h6>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == '../admin_management/vehicle_management.php') ? 'active bg-gradient-dark text-white' : 'text-light'; ?>" href="../admin_management/vehicle_management.php">
                    <i class="material-symbols-rounded opacity-5">article</i>
                    <span class="nav-link-text ms-1">Vehicle List</span>
                </a>



            <li class="nav-item mt-3">
                <h6 class="ps-4 ms-2 text-uppercase text-xs text-light font-weight-bolder opacity-5">Account pages</h6>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'client_profile.php') ? 'active bg-gradient-dark text-white' : 'text-light'; ?>" href="..//profile_management/admin_profile.php">
                    <i class="material-symbols-rounded opacity-5">person</i>
                    <span class="nav-link-text ms-1">Profile</span>
                </a>
            </li>
            <!-- <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'profile.php') ? 'active bg-gradient-dark text-white' : 'text-light'; ?>" href="profile.php">
                    <i class="material-symbols-rounded opacity-5">person</i>
                    <span class="nav-link-text ms-1">Profile</span>
                </a>
            </li> -->
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
<div iv class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1050;">
    <div id="logoutToast" class="toast text-bg-light border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto">Confirm Logout</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body text-center">
            Are you sure you want to log out?
            <div class="d-flex justify-content-center gap-9 mt-4 pt-4 border-top">
                <!-- Link to logout.php -->
                <form action="../login_page/logout.php" method="POST" id="logoutForm">
                    <button type="submit" class="btn btn-danger btn-sm">Yes, Logout</button>
                </form>
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