<?php
$current_page = basename($_SERVER['PHP_SELF']); // Get current page for active state
?>

<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-radius-lg fixed-start ms-2 bg-white my-2" id="sidenav-main">
    
    <div class="sidenav-header">
        <i class="fas fa-times p-3 cursor-pointer text-dark opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
        <a class="navbar-brand px-4 py-3 m-0" href="#">
            <img src="../assets/img/logo.png" class="navbar-brand-img" width="30" height="30" alt="main_logo">
            <span class="ms-1 text-sm text-dark">BAGO CITY - CEMO</span>
        </a>
    </div>
                <!-- Admin Management Section -->
    <hr class="horizontal dark mt-0 mb-2">
    <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active bg-gradient-dark text-white' : 'text-dark'; ?>" 
                href="../dashboard_management/admin_dashboard.php">
                    <i class="material-symbols-rounded opacity-5">recycling</i>
                    <span class="nav-link-text ms-1"> Waste Dashboard</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active bg-gradient-dark text-white' : 'text-dark'; ?>" 
                href="../admin_management/admin_map.php">
                    <i class="material-symbols-rounded opacity-5">public</i>
                    <span class="nav-link-text ms-1">Map Dashboard</span>
                </a>
            </li>
            <!-- Barangay Management -->
            <li class="nav-item mt-3">
                <h6 class="ps-4 ms-2 text-uppercase text-xs text-dark font-weight-bolder opacity-5">Barangay Management</h6>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == '../admin_management/admin_barangay_list.php') ? 'active bg-gradient-dark text-white' : 'text-dark'; ?>" href="../admin_management/admin_barangay_list.php">
                    <i class="material-symbols-rounded opacity-5">article</i>
                    <span class="nav-link-text ms-1">Barangay List</span>
                </a>
            </li>
            <!-- User Client Management -->
            <li class="nav-item mt-3">
                <h6 class="ps-4 ms-2 text-uppercase text-xs text-dark font-weight-bolder opacity-5">Client Management</h6>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == '../admin_management/admin_user_client.php') ? 'active bg-gradient-dark text-white' : 'text-dark'; ?>" href="../admin_management/admin_user_client.php">
                    <i class="material-symbols-rounded opacity-5">article</i>
                    <span class="nav-link-text ms-1">Client List</span>
                </a>
            </li>
            <!-- User Role Management -->
            <li class="nav-item mt-3">
                <h6 class="ps-4 ms-2 text-uppercase text-xs text-dark font-weight-bolder opacity-5">User Role Management</h6>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == '../admin_management/admin_role_list.php') ? 'active bg-gradient-dark text-white' : 'text-dark'; ?>" href="../admin_management/admin_role_list.php">
                    <i class="material-symbols-rounded opacity-5">article</i>
                    <span class="nav-link-text ms-1">Role List</span>
                </a>
            </li>
             <!-- Vehicle  Management -->
             <li class="nav-item mt-3">
                <h6 class="ps-4 ms-2 text-uppercase text-xs text-dark font-weight-bolder opacity-5">Vehicle Management</h6>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == '../admin_management/vehicle_management.php') ? 'active bg-gradient-dark text-white' : 'text-dark'; ?>" href="../admin_management/vehicle_management.php">
                    <i class="material-symbols-rounded opacity-5">article</i>
                    <span class="nav-link-text ms-1">Vehicle List</span>
                </a>
    
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == '../admin_management/waste_service_sched.php') ? 'active bg-gradient-dark text-white' : 'text-dark'; ?>" href="../admin_management/waste_service_sched.php">
                    <i class="material-symbols-rounded opacity-5">article</i>
                    <span class="nav-link-text ms-1">Waste Service Schedules</span>
                </a>

                <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == '../admin_management/vehicle_assignment.php') ? 'active bg-gradient-dark text-white' : 'text-dark'; ?>" href="../admin_management/vehicle_assignment.php">
                    <i class="material-symbols-rounded opacity-5">article</i>
                    <span class="nav-link-text ms-1">Vehicle Assignment</span>
                </a>
                
            <li class="nav-item mt-3">
                <h6 class="ps-4 ms-2 text-uppercase text-xs text-dark font-weight-bolder opacity-5">Account pages</h6>
            </li>
            <!-- <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'profile.php') ? 'active bg-gradient-dark text-white' : 'text-dark'; ?>" href="profile.php">
                    <i class="material-symbols-rounded opacity-5">person</i>
                    <span class="nav-link-text ms-1">Profile</span>
                </a>
            </li> -->
            <!-- <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'sign-in.php') ? 'active bg-gradient-dark text-white' : 'text-dark'; ?>"
                href="../login_page/sign-in.php">
                    <i class="material-symbols-rounded opacity-5">login</i>
                    <span class="nav-link-text ms-1">Sign In</span>
                </a>
            </li> -->
            <li class="nav-item">
    <a class="nav-link text-dark" href="javascript:void(0);" onclick="showLogoutToast();">
        <i class="material-symbols-rounded opacity-5">logout</i>
        <span class="nav-link-text ms-1">Logout</span>
    </a>
</li>

<!-- Toast Container (Centered at Top) -->
<d iv class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1050;">
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
</d>

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
