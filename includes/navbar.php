<?php
/**
 * Navigation Bar Component
 * Handles user authentication state and displays appropriate navigation elements
 */

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/conn.php';

// Initialize default user data
$user_data = [
    'first_name' => 'User',
    'role' => 'guest'
];

// Function to get unread notifications count
function getUnreadNotificationsCount($conn) {
    $count = 0;
    
    if (isset($_SESSION['client_id'])) {
        $client_id = $_SESSION['client_id'];
        $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM client_notifications 
                              WHERE client_id = ? AND is_read = 0");
        $stmt->bind_param("i", $client_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = $row['count'] ?? 0;
        $stmt->close();
        
    } elseif (isset($_SESSION['admin_id'])) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM admin_notifications 
                              WHERE is_read = 0");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = $row['count'] ?? 0;
        $stmt->close();
    }
    
    return $count;
}

// Fetch authenticated user information
function getUserData($conn) {
    $user_data = ['first_name' => 'User', 'role' => 'guest'];
    
    // Check session role first (most reliable)
    if (isset($_SESSION['user_role'])) {
        $user_data['role'] = $_SESSION['user_role'];
    }
    
    if (isset($_SESSION['client_id']) && $_SESSION['user_role'] === 'client') {
        $stmt = $conn->prepare("SELECT first_name FROM client_table WHERE client_id = ?");
        $stmt->bind_param("i", $_SESSION['client_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $user_data['first_name'] = $row['first_name'];
        }
        $stmt->close();
        
    } elseif (isset($_SESSION['admin_id']) && $_SESSION['user_role'] === 'admin') {
        $stmt = $conn->prepare("SELECT first_name FROM admin_table WHERE admin_id = ?");
        $stmt->bind_param("i", $_SESSION['admin_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $user_data['first_name'] = $row['first_name'];
        }
        $stmt->close();
        
    } elseif (isset($_SESSION['driver_id']) && $_SESSION['user_role'] === 'driver') {
        $stmt = $conn->prepare("SELECT first_name FROM driver_table WHERE driver_id = ?");
        $stmt->bind_param("i", $_SESSION['driver_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $user_data['first_name'] = $row['first_name'];
        }
        $stmt->close();
    }
    
    return $user_data;
}

$user_data = getUserData($conn);
$unread_count = getUnreadNotificationsCount($conn);
$page_title = $page_title ?? 'Dashboard';
?>

<!-- Main Navigation Bar -->
<nav class="navbar navbar-main navbar-expand  px-0 mx-3 shadow-none border-radius-xl" 
     id="navbarBlur" data-scroll="true">
    <div class="container-fluid py-1 px-3">
        
        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
                <li class="breadcrumb-item text-sm">
                    <a class="opacity-5 text-dark" href="#">Pages</a>
                </li>
                <li class="breadcrumb-item text-sm text-dark active" aria-current="page">
                    <?= htmlspecialchars($page_title) ?>
                </li>
            </ol>
        </nav>

        <!-- Right Side Navigation Items -->
        <div class="collapse navbar-collapse justify-content-end">
            <ul class="navbar-nav align-items-center">
                
                <!-- Mobile Sidebar Toggle -->
                <li class="nav-item d-xl-none ps-3">
                    <a href="javascript:;" class="nav-link text-body p-0" id="sidebarToggle">
                        <div class="sidenav-toggler-inner">
                            <i class="fa-solid fa-bars" aria-label="Toggle sidebar"></i>
                        </div>
                    </a>
                </li>

                <!-- Settings Dropdown -->
                <li class="nav-item dropdown px-3">
                    <a href="#" class="nav-link text-body p-0" id="settingsDropdown" 
                        data-bs-toggle="dropdown" aria-expanded="false" aria-label="Settings">
                        <i class="fa-solid fa-gear fa-lg"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="settingsDropdown">
                        <li>
                            <a class="dropdown-item" href="#" onclick="showSoundSettingsModal()">
                                <i class="fa-solid fa-volume-up me-2"></i>Sound Settings
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#">
                                <i class="fa-solid fa-sliders me-2"></i>Preferences
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#">
                                <i class="fa-regular fa-circle-question me-2"></i>Help
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Notifications Dropdown -->
                <li class="nav-item dropdown pe-3">
                    <a href="#" class="nav-link text-body p-0 position-relative" id="notifDropdown" 
                       data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                        <i class="fa-regular fa-bell fa-lg"></i>
                        <?php if ($unread_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= $unread_count ?>
                            <span class="visually-hidden">unread notifications</span>
                        </span>
                        <?php endif; ?>
                    </a>
                    <!-- Enhanced Notifications Dropdown Menu -->
                    <ul class="dropdown-menu dropdown-menu-end notifications-dropdown" aria-labelledby="notifDropdown">
                        <li class="dropdown-header d-flex justify-content-between align-items-center px-3 py-2">
                            <h6 class="mb-0 fw-bold">Notifications</h6>
                            <?php if ($unread_count > 0): ?>
                            <span class="badge bg-primary rounded-pill"><?= $unread_count ?></span>
                            <?php endif; ?>
                        </li>
                        <li><hr class="dropdown-divider my-0"></li>
                        
                        <!-- Notifications Container -->
                        <div class="notifications-container">
                            <?php if ($unread_count > 0): ?>
                                <li class="px-3 py-3">
                                    <div class="text-center">
                                        <div class="spinner-border spinner-border-sm text-primary mb-2" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <div>
                                            <small class="text-muted">Loading notifications...</small>
                                        </div>
                                    </div>
                                </li>
                            <?php else: ?>
                                <li class="px-3 py-4">
                                    <div class="text-center text-muted">
                                        <i class="fa-regular fa-bell-slash fa-2x mb-2 opacity-50"></i>
                                        <div>
                                            <small>No new notifications</small>
                                        </div>
                                    </div>
                                </li>
                            <?php endif; ?>
                        </div>
                        
                        <!-- View All Footer -->
                        <li><hr class="dropdown-divider my-0"></li>
                        <li>
                            <a class="dropdown-item text-center py-2 text-primary fw-medium" href="../api/view_all.php">
                                <i class="fa-solid fa-eye me-1"></i>View All Notifications
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- User Profile Dropdown -->
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle text-body font-weight-bold px-0 d-flex align-items-center gap-2"
                       id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="position-relative">
                            <i class="fa-solid fa-user-circle fa-lg"></i>
                            <span class="position-absolute bottom-0 end-0 bg-success rounded-circle" 
                                  style="width: 8px; height: 8px;" 
                                  aria-label="Online status"></span>
                        </div>
                        <small class="text-black fw-bold">
                            <?= htmlspecialchars($user_data['first_name']) ?> 
                            <span class="text-muted">(<?= ucfirst(htmlspecialchars($user_data['role'])) ?>)</span>
                        </small>
                    </a>
                    
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm rounded">
                        <li>
                            <?php if ($user_data['role'] === 'client'): ?>
                                <a class="dropdown-item" href="../client_management/client_profile.php">
                                    <i class="fa-solid fa-user me-2"></i>Profile
                                </a>
                            <?php else: ?>
                                <a class="dropdown-item" href="../profile_management/admin_profile.php">
                                    <i class="fa-solid fa-user-shield me-2"></i>Profile
                                </a>
                            <?php endif; ?>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="#" onclick="showLogoutConfirmation()">
                                <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Logout Confirmation Modal -->
<div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1050;">
    <div id="logoutConfirmationToast" class="toast text-bg-light border-0 shadow-lg" 
         role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto">Confirm Logout</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body text-center">
            <p class="mb-3">Are you sure you want to log out?</p>
            <div class="d-flex justify-content-center gap-3 pt-3 border-top">
                <button type="button" class="btn btn-danger btn-sm px-3" onclick="confirmLogout()">
                    Logout
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="toast">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sound Settings Modal -->
<div class="modal fade" id="soundSettingsModal" tabindex="-1" aria-labelledby="soundSettingsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="soundSettingsModalLabel">
          <i class="fas fa-volume-up me-2"></i>Notification Sound Settings
        </h5>
        <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-bold">Sound Type</label>
          <select class="form-select" id="soundTypeSelect">
            <option value="default">Default (2-tone beep)</option>
            <option value="success">Success (3-tone ascending)</option>
            <option value="warning">Warning (2-tone descending)</option>
            <option value="error">Error (low tones)</option>
            <option value="request">Request (3-tone pattern)</option>
            <option value="gentle">Gentle (soft chime)</option>
            <option value="alert">Alert (urgent beep)</option>
            <option value="chime">Chime (musical tone)</option>
          </select>
        </div>
        
        <div class="mb-3">
          <label class="form-label fw-bold">Volume</label>
          <input type="range" class="form-range" id="volumeSlider" min="0" max="100" value="50">
          <div class="d-flex justify-content-between">
            <small class="text-muted">0%</small>
            <small class="text-muted" id="volumeDisplay">50%</small>
            <small class="text-muted">100%</small>
          </div>
        </div>
        
        <div class="mb-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="enableSound" checked>
            <label class="form-check-label" for="enableSound">
              Enable notification sounds
            </label>
          </div>
        </div>
        
        <div class="mb-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="enableVisual">
            <label class="form-check-label" for="enableVisual">
              Show visual notifications
            </label>
          </div>
        </div>
        
        <div class="d-grid gap-2">
          <button type="button" class="btn btn-outline-primary" id="testSoundBtn">
            <i class="fas fa-play me-2"></i>Test Sound
          </button>
          <button type="button" class="btn btn-success" id="saveSoundSettings">
            <i class="fas fa-save me-2"></i>Save Settings
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Notification Details Modal -->
<div class="modal fade" id="notificationDetailsModal" tabindex="-1" aria-labelledby="notificationDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="notificationDetailsModalLabel">
          <i class="fas fa-bell me-2"></i>Notification Details
        </h5>
        <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="notification-details-content">
          <div class="notification-header mb-3">
            <div class="d-flex align-items-center gap-3">
              <div class="notification-icon-large">
                <i class="fas fa-bell fa-2x text-primary"></i>
              </div>
              <div class="flex-grow-1">
                <h4 class="notification-title-large mb-1" id="modalNotificationTitle">Loading...</h4>
                <small class="text-muted" id="modalNotificationTime">Loading...</small>
              </div>
              <div class="notification-status" id="modalNotificationStatus">
                <span class="badge bg-primary">New</span>
              </div>
            </div>
          </div>
          
          <div class="notification-body">
            <div class="card">
              <div class="card-body">
                <h6 class="card-title">Message</h6>
                <p class="card-text" id="modalNotificationMessage">Loading...</p>
              </div>
            </div>
          </div>
          
          <div class="notification-actions mt-3">
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-outline-primary btn-sm" id="markAsReadBtn">
                <i class="fas fa-check me-1"></i>Mark as Read
              </button>
              <button type="button" class="btn btn-outline-danger btn-sm" id="deleteNotificationBtn">
                <i class="fas fa-trash me-1"></i>Delete
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="fas fa-times me-1"></i>Close
        </button>
      </div>
    </div>
  </div>
</div>

<script>
/**
 * Navbar JavaScript Functionality
 */
(function() {
    'use strict';
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeSidebarToggle();
        initializeDropdownHandlers();
        addHoverEffects();
        loadNotifications();
        initializeSoundSettings();
    });

    /**
     * Initialize mobile sidebar toggle functionality
     */
    function initializeSidebarToggle() {
        const toggleButton = document.getElementById('sidebarToggle');
        if (!toggleButton) return;

        toggleButton.addEventListener('click', function(e) {
            e.preventDefault();
            const sidebar = document.querySelector('.sidenav, #sidenav-main, .g-sidenav-show');
            
            if (sidebar) {
                sidebar.classList.toggle('g-sidenav-pinned');
                sidebar.classList.toggle('g-sidenav-hidden');
            }
        });
    }

    /**
     * Handle dropdown menu interactions
     */
    function initializeDropdownHandlers() {
        document.addEventListener('click', function(e) {
            // Close dropdowns when clicking outside
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu.show').forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
            }
        });
    }

    /**
     * Add subtle hover effects to navigation items
     */
    function addHoverEffects() {
        document.querySelectorAll('.navbar-nav .nav-link').forEach(item => {
            item.addEventListener('mouseenter', () => {
                item.style.transform = 'translateY(-2px)';
                item.style.transition = 'transform 0.2s ease';
            });
            
            item.addEventListener('mouseleave', () => {
                item.style.transform = '';
            });
        });
    }

    /**
     * Load notifications when dropdown is shown
     */
    function loadNotifications() {
        const notifDropdown = document.getElementById('notifDropdown');
        if (!notifDropdown) return;

        // Get the notifications container
        const notificationsContainer = document.querySelector('.notifications-container');
        if (!notificationsContainer) return;

        // Add event listener for when dropdown is shown
        notifDropdown.addEventListener('shown.bs.dropdown', function() {
            // Show enhanced loading state
            notificationsContainer.innerHTML = `
                <li class="px-3 py-3">
                    <div class="text-center">
                        <div class="spinner-border spinner-border-sm text-primary mb-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div>
                            <small class="text-muted">Loading notifications...</small>
                        </div>
                    </div>
                </li>
            `;

            fetch('../api/get_notification_count.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data.success) {
                        throw new Error('Failed to load notifications');
                    }

                    // Clear container
                    notificationsContainer.innerHTML = '';

                    if (data.notifications && data.notifications.length > 0) {
                        data.notifications.forEach((notif, index) => {
                            const notifItem = document.createElement('li');
                            notifItem.className = 'notification-item';
                            notifItem.innerHTML = `
                                <div class="px-3 py-2 notification-content ${notif.is_read ? '' : 'unread'}" data-notification-id="${notif.id}">
                                    <div class="d-flex gap-3 align-items-start">
                                        <div class="flex-shrink-0 mt-1">
                                            <div class="notification-icon ${notif.is_read ? 'read' : 'unread'}">
                                                <i class="fa-solid fa-bell"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="notification-title mb-1 ${notif.is_read ? '' : 'fw-bold'}">${notif.title}</h6>
                                            <p class="notification-message mb-1 small text-muted">${notif.message}</p>
                                            <small class="notification-time text-muted">${formatNotificationTime(notif.created_at)}</small>
                                        </div>
                                        ${!notif.is_read ? '<div class="flex-shrink-0"><div class="unread-indicator"></div></div>' : ''}
                                    </div>
                                </div>
                            `;
                            
                            // Add click handler
                            const content = notifItem.querySelector('.notification-content');
                            content.addEventListener('click', function() {
                                showNotificationDetailsModal(notif);
                            });
                            
                            notificationsContainer.appendChild(notifItem);
                        });
                    } else {
                        notificationsContainer.innerHTML = `
                            <li class="px-3 py-4">
                                <div class="text-center text-muted">
                                    <i class="fa-regular fa-bell-slash fa-2x mb-2 opacity-50"></i>
                                    <div>
                                        <small>No new notifications</small>
                                    </div>
                                </div>
                            </li>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                    notificationsContainer.innerHTML = `
                        <li class="px-3 py-3">
                            <div class="text-center">
                                <i class="fa-solid fa-triangle-exclamation text-warning mb-2"></i>
                                <div>
                                    <small class="text-danger">Failed to load notifications</small>
                                </div>
                            </div>
                        </li>
                    `;
                });
        });
    }

    /**
     * Format notification time
     */
    function formatNotificationTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInMs = now - date;
        const diffInHours = diffInMs / (1000 * 60 * 60);
        const diffInDays = diffInMs / (1000 * 60 * 60 * 24);

        if (diffInHours < 1) {
            return 'Just now';
        } else if (diffInHours < 24) {
            return `${Math.floor(diffInHours)}h ago`;
        } else if (diffInDays < 7) {
            return `${Math.floor(diffInDays)}d ago`;
        } else {
            return date.toLocaleDateString();
        }
    }

    /**
     * Show notification details modal
     */
    function showNotificationDetailsModal(notification) {
        // Populate modal with notification data
        document.getElementById('modalNotificationTitle').textContent = notification.title;
        document.getElementById('modalNotificationMessage').textContent = notification.message;
        document.getElementById('modalNotificationTime').textContent = formatNotificationTime(notification.created_at);
        
        // Update status badge
        const statusBadge = document.getElementById('modalNotificationStatus');
        if (notification.is_read) {
            statusBadge.innerHTML = '<span class="badge bg-success">Read</span>';
        } else {
            statusBadge.innerHTML = '<span class="badge bg-primary">New</span>';
        }
        
        // Store current notification ID for actions
        window.currentNotificationId = notification.id;
        window.currentNotificationElement = document.querySelector(`[data-notification-id="${notification.id}"]`);
        
        // Setup action buttons
        setupNotificationModalActions();
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('notificationDetailsModal'));
        modal.show();
    }

    /**
     * Setup notification modal action buttons
     */
    function setupNotificationModalActions() {
        const markAsReadBtn = document.getElementById('markAsReadBtn');
        const deleteBtn = document.getElementById('deleteNotificationBtn');
        
        // Mark as read button
        if (markAsReadBtn) {
            markAsReadBtn.onclick = function() {
                if (window.currentNotificationId) {
                    markNotificationAsRead(window.currentNotificationId, window.currentNotificationElement);
                    // Update modal status
                    document.getElementById('modalNotificationStatus').innerHTML = '<span class="badge bg-success">Read</span>';
                    // Hide mark as read button
                    markAsReadBtn.style.display = 'none';
                }
            };
        }
        
        // Delete button
        if (deleteBtn) {
            deleteBtn.onclick = function() {
                if (window.currentNotificationId) {
                    deleteNotification(window.currentNotificationId);
                }
            };
        }
    }

    /**
     * Delete notification
     */
    function deleteNotification(notificationId) {
        // Determine the correct API endpoint based on user type
        const isClient = document.body.classList.contains('client-page') || 
                        window.location.pathname.includes('client_management');
        const apiEndpoint = isClient ? '../client_management/delete_notification.php' : '../api/delete_admin_notification.php';
        
        fetch(apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ notification_id: notificationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove notification from dropdown
                const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
                if (notificationElement) {
                    notificationElement.closest('.notification-item').remove();
                }
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('notificationDetailsModal'));
                modal.hide();
                
                // Update notification count
                updateNotificationBadgeCount();
                
                // Show success message
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Deleted',
                        text: 'Notification has been deleted.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                }
            } else {
                console.error('Error deleting notification:', data.message);
            }
        })
        .catch(error => {
            console.error('Error deleting notification:', error);
        });
    }

    /**
     * Update notification badge count
     */
    function updateNotificationBadgeCount() {
        const badge = document.querySelector('#notifDropdown .badge');
        if (badge) {
            const currentCount = parseInt(badge.textContent);
            if (currentCount > 0) {
                const newCount = currentCount - 1;
                badge.textContent = newCount;
                if (newCount === 0) {
                    badge.remove();
                }
            }
        }
    }

    /**
     * Mark notification as read
     */
    function markNotificationAsRead(notificationId, element) {
        fetch('../api/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: notificationId })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && element) {
                // Update the UI to show notification as read
                const content = element.querySelector('.notification-content');
                if (content) {
                    content.classList.remove('unread');
                    content.classList.add('read');
                }
                
                const icon = element.querySelector('.notification-icon');
                if (icon) {
                    icon.classList.remove('unread');
                    icon.classList.add('read');
                }
                
                const title = element.querySelector('.notification-title');
                if (title) {
                    title.classList.remove('fw-bold');
                }
                
                const indicator = element.querySelector('.unread-indicator');
                if (indicator) {
                    indicator.remove();
                }
                
                // Update badge count
                const badge = document.querySelector('#notifDropdown .badge');
                if (badge) {
                    const currentCount = parseInt(badge.textContent);
                    if (currentCount > 0) {
                        const newCount = currentCount - 1;
                        badge.textContent = newCount;
                        if (newCount === 0) {
                            badge.remove();
                        }
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error marking notification as read:', error);
        });
    }

    /**
     * Show logout confirmation toast
     */
    window.showLogoutConfirmation = function() {
        const toastElement = document.getElementById('logoutConfirmationToast');
        if (toastElement) {
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
        }
    };

    /**
     * Handle user logout
     */
    window.confirmLogout = function() {
        // Show loading state (optional)
        const logoutButton = event.target;
        const originalText = logoutButton.textContent;
        logoutButton.textContent = 'Logging out...';
        logoutButton.disabled = true;

        // Redirect to logout page
        window.location.href = "../login_page/logout.php";
    };

    /**
     * Initialize sound settings functionality
     */
    function initializeSoundSettings() {
        // Load saved settings when modal is shown
        const soundSettingsModal = document.getElementById('soundSettingsModal');
        if (soundSettingsModal) {
            soundSettingsModal.addEventListener('show.bs.modal', function() {
                loadSoundSettings();
            });
        }

        // Setup sound settings modal functionality
        setupSoundSettingsModal();
    }

    /**
     * Show sound settings modal
     */
    window.showSoundSettingsModal = function() {
        const modal = new bootstrap.Modal(document.getElementById('soundSettingsModal'));
        modal.show();
    };

    /**
     * Setup sound settings modal functionality
     */
    function setupSoundSettingsModal() {
        const volumeSlider = document.getElementById('volumeSlider');
        const volumeDisplay = document.getElementById('volumeDisplay');
        const testSoundBtn = document.getElementById('testSoundBtn');
        const saveSettingsBtn = document.getElementById('saveSoundSettings');
        const soundTypeSelect = document.getElementById('soundTypeSelect');
        const enableSoundCheckbox = document.getElementById('enableSound');
        const enableVisualCheckbox = document.getElementById('enableVisual');

        if (!volumeSlider || !volumeDisplay || !testSoundBtn || !saveSettingsBtn || 
            !soundTypeSelect || !enableSoundCheckbox || !enableVisualCheckbox) {
            return; // Elements not found, skip setup
        }

        // Volume slider update
        volumeSlider.addEventListener('input', function() {
            volumeDisplay.textContent = this.value + '%';
        });

        // Test sound button
        testSoundBtn.addEventListener('click', function() {
            const selectedSound = soundTypeSelect.value;
            const volume = volumeSlider.value / 100;
            
            if (window.notificationSound) {
                // Temporarily set volume
                const originalVolume = window.notificationSound.volume || 0.5;
                window.notificationSound.volume = volume;
                window.notificationSound.playNotificationSound(selectedSound);
                // Restore original volume
                setTimeout(() => {
                    window.notificationSound.volume = originalVolume;
                }, 1000);
            }
        });

        // Save settings button
        saveSettingsBtn.addEventListener('click', function() {
            const settings = {
                soundType: soundTypeSelect.value,
                volume: volumeSlider.value / 100,
                enableSound: enableSoundCheckbox.checked,
                enableVisual: enableVisualCheckbox.checked
            };
            
            saveSoundSettings(settings);
            
            // Show success message
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Settings Saved',
                    text: 'Your notification sound settings have been saved.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            } else {
                // Fallback alert if SweetAlert is not available
                alert('Settings saved successfully!');
            }
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('soundSettingsModal'));
            modal.hide();
        });
    }

    /**
     * Load saved sound settings
     */
    function loadSoundSettings() {
        const settings = getSoundSettings();
        
        const soundTypeSelect = document.getElementById('soundTypeSelect');
        const volumeSlider = document.getElementById('volumeSlider');
        const volumeDisplay = document.getElementById('volumeDisplay');
        const enableSoundCheckbox = document.getElementById('enableSound');
        const enableVisualCheckbox = document.getElementById('enableVisual');

        if (soundTypeSelect && settings.soundType) {
            soundTypeSelect.value = settings.soundType;
        }
        if (volumeSlider && settings.volume !== undefined) {
            volumeSlider.value = settings.volume * 100;
        }
        if (volumeDisplay && settings.volume !== undefined) {
            volumeDisplay.textContent = Math.round(settings.volume * 100) + '%';
        }
        if (enableSoundCheckbox && settings.enableSound !== undefined) {
            enableSoundCheckbox.checked = settings.enableSound;
        }
        if (enableVisualCheckbox && settings.enableVisual !== undefined) {
            enableVisualCheckbox.checked = settings.enableVisual;
        }
    }

    /**
     * Save sound settings to localStorage
     */
    function saveSoundSettings(settings) {
        localStorage.setItem('notificationSoundSettings', JSON.stringify(settings));
        
        // Update global notification sound settings
        if (window.notificationSound) {
            window.notificationSound.setSoundType(settings.soundType);
            window.notificationSound.setVolume(settings.volume);
            window.notificationSound.setSoundPreference(settings.enableSound);
        }
    }

    /**
     * Get sound settings from localStorage
     */
    function getSoundSettings() {
        const defaultSettings = {
            soundType: 'default',
            volume: 0.5,
            enableSound: true,
            enableVisual: true
        };
        
        const saved = localStorage.getItem('notificationSoundSettings');
        return saved ? { ...defaultSettings, ...JSON.parse(saved) } : defaultSettings;
    }
})();
</script>

<style>
/* Navbar styles */
.navbar {
    background-color: #ffffff37;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Additional navbar styling */
.navbar-nav .nav-link {
    transition: all 0.2s ease;
}

.navbar-nav .nav-link:hover {
    opacity: 0.8;
}

.dropdown-menu {
    border: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.toast {
    min-width: 350px;
}

.badge {
    font-size: 0.6rem;
}

/* Enhanced Notifications Dropdown Styles */
.notifications-dropdown {
    min-width: 380px !important;
    max-width: 420px;
    border: none;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    padding: 0;
    margin-top: 10px;
}

.notifications-dropdown .dropdown-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
    border-radius: 12px 12px 0 0;
    padding: 12px 16px;
    margin: 0;
}

.notifications-container {
    max-height: 400px;
    overflow-y: auto;
    padding: 0;
}

/* Custom scrollbar for notifications container */
.notifications-container::-webkit-scrollbar {
    width: 6px;
}

.notifications-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.notifications-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.notifications-container::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.notification-item {
    border-bottom: 1px solid #f8f9fa;
    transition: all 0.2s ease;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-content {
    cursor: pointer;
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
    position: relative;
}

.notification-content:hover {
    background-color: #f8f9fa;
}

.notification-content.unread {
    background-color: #f0f8ff;
    border-left-color: #007bff;
}

.notification-content.unread:hover {
    background-color: #e6f3ff;
}

.notification-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.notification-icon.unread {
    background-color: #007bff;
    color: white;
}

.notification-icon.read {
    background-color: #e9ecef;
    color: #6c757d;
}

.notification-title {
    font-size: 0.9rem;
    line-height: 1.3;
    color: #212529;
    margin: 0;
}

.notification-message {
    font-size: 0.8rem;
    line-height: 1.4;
    color: #6c757d;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.notification-time {
    font-size: 0.75rem;
    color: #adb5bd;
}

.unread-indicator {
    width: 8px;
    height: 8px;
    background-color: #007bff;
    border-radius: 50%;
    margin-top: 6px;
}

/* View all notifications link */
.notifications-dropdown .dropdown-item:last-child {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 0 0 12px 12px;
    border-top: 1px solid #dee2e6;
    font-weight: 500;
    transition: all 0.2s ease;
}

.notifications-dropdown .dropdown-item:last-child:hover {
    background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
    transform: none;
}

/* Empty state styling */
.notifications-container .text-center {
    padding: 30px 20px;
}

.notifications-container .fa-bell-slash {
    opacity: 0.3;
}

/* Loading state */
.spinner-border-sm {
    width: 1.5rem;
    height: 1.5rem;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .notifications-dropdown {
        min-width: 300px !important;
        max-width: 350px;
    }
    
    .notification-message {
        -webkit-line-clamp: 1;
    }
}

@media (max-width: 576px) {
    .notifications-dropdown {
        min-width: 280px !important;
        max-width: 320px;
        margin-right: -20px;
    }
}

/* Notification Details Modal Styles */
.notification-details-content {
    padding: 0;
}

.notification-header {
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 1rem;
}

.notification-icon-large {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.notification-title-large {
    font-size: 1.5rem;
    font-weight: 600;
    color: #212529;
    margin: 0;
    line-height: 1.3;
}

.notification-status .badge {
    font-size: 0.8rem;
    padding: 0.5rem 0.75rem;
}

.notification-body .card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.notification-body .card-title {
    color: #495057;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.notification-body .card-text {
    color: #6c757d;
    line-height: 1.6;
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.notification-actions {
    border-top: 1px solid #e9ecef;
    padding-top: 1rem;
}

.notification-actions .btn {
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.notification-actions .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

/* Modal responsiveness */
@media (max-width: 768px) {
    .notification-icon-large {
        width: 50px;
        height: 50px;
    }
    
    .notification-title-large {
        font-size: 1.25rem;
    }
    
    .notification-actions .d-flex {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .notification-actions .btn {
        width: 100%;
    }
}
</style>