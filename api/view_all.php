<?php
/**
 * View All Notifications Page
 * Complete notifications management interface
 */

// Initialize session
session_start();

// Check if user is logged in
if (!isset($_SESSION['client_id']) && !isset($_SESSION['admin_id'])) {
    header("Location: ../login_page/login.php");
    exit();
}

require_once '../includes/conn.php';
require_once '../includes/header.php';

// Get user data
$user_data = [
    'first_name' => 'User',
    'role' => 'guest',
    'user_id' => null
];

if (isset($_SESSION['client_id'])) {
    $stmt = $conn->prepare("SELECT first_name FROM client_table WHERE client_id = ?");
    $stmt->bind_param("i", $_SESSION['client_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $user_data['first_name'] = $row['first_name'];
        $user_data['role'] = 'client';
        $user_data['user_id'] = $_SESSION['client_id'];
    }
    $stmt->close();
    
} elseif (isset($_SESSION['admin_id'])) {
    $stmt = $conn->prepare("SELECT first_name, user_role FROM admin_table WHERE admin_id = ?");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $user_data['first_name'] = $row['first_name'];
        $user_data['role'] = $row['user_role'];
        $user_data['user_id'] = $_SESSION['admin_id'];
    }
    $stmt->close();
}

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 15;
$offset = ($page - 1) * $items_per_page;

// Filter settings
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query conditions
$where_conditions = [];
$params = [];
$param_types = '';

if ($user_data['role'] === 'client') {
    $where_conditions[] = "client_id = ?";
    $params[] = $user_data['user_id'];
    $param_types .= 'i';
    $table_name = 'client_notifications';
} else {
    $table_name = 'admin_notifications';
}

if ($filter === 'unread') {
    $where_conditions[] = "is_read = 0";
} elseif ($filter === 'read') {
    $where_conditions[] = "is_read = 1";
}

if (!empty($search)) {
    $where_conditions[] = "(title LIKE ? OR message LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'ss';
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM $table_name $where_clause";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_notifications = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_notifications / $items_per_page);
$count_stmt->close();

// Get notifications for current page
$query = "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;
$param_types .= 'ii';

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get unread count
$unread_query = "SELECT COUNT(*) as unread_count FROM $table_name " . 
                ($user_data['role'] === 'client' ? "WHERE client_id = ? AND is_read = 0" : "WHERE is_read = 0");
$unread_stmt = $conn->prepare($unread_query);
if ($user_data['role'] === 'client') {
    $unread_stmt->bind_param("i", $user_data['user_id']);
}
$unread_stmt->execute();
$unread_count = $unread_stmt->get_result()->fetch_assoc()['unread_count'];
$unread_stmt->close();

$page_title = 'All Notifications';
?>

<body class="g-sidenav-show bg-gray-100">
    <?php include '../sidebar/admin_sidebar.php'; ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include '../includes/navbar.php'; ?>
        
        <div class="container-fluid py-4">
            <div class="notifications-container">
                
                <!-- Page Header -->
                <div class="page-header mb-4">
                    <div class="header-main">
                        <div class="header-title-section">
                            <div class="d-flex align-items-center">
                                <div class="icon-header me-3">
                                    <i class="fa-solid fa-bell"></i>
                                </div>
                                <div>
                                    <h2 class="page-title mb-0">Notifications</h2>
                                    <p class="page-subtitle mb-0">Stay updated with all your latest notifications</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Stats Cards -->
                        <div class="header-stats">
                            <div class="stat-card total">
                                <div class="stat-number"><?= $total_notifications ?></div>
                                <div class="stat-label">Total</div>
                            </div>
                            <div class="stat-card unread">
                                <div class="stat-number"><?= $unread_count ?></div>
                                <div class="stat-label">Unread</div>
                            </div>
                            <div class="stat-card read">
                                <div class="stat-number"><?= $total_notifications - $unread_count ?></div>
                                <div class="stat-label">Read</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Controls Bar -->
                <div class="controls-bar mb-4">
                    <div class="controls-container">
                        <!-- Search Section -->
                        <div class="search-section">
                            <div class="search-wrapper">
                                <i class="fa-solid fa-search search-icon"></i>
                                <input type="text" 
                                       class="form-control search-input" 
                                       name="search" 
                                       placeholder="Search notifications..." 
                                       value="<?= htmlspecialchars($search) ?>"
                                       id="headerSearchInput">
                                <?php if (!empty($search)): ?>
                                    <button type="button" class="clear-search" onclick="clearHeaderSearch()">
                                        <i class="fa-solid fa-times"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Filter Section -->
                        <div class="filter-section">
                            <form method="GET" class="filter-form">
                                <input type="hidden" name="page" value="1">
                                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                                <div class="filter-buttons">
                                    <button type="submit" name="filter" value="all" 
                                            class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">
                                        <i class="fa-solid fa-list"></i>
                                        <span>All</span>
                                    </button>
                                    <button type="submit" name="filter" value="unread" 
                                            class="filter-btn <?= $filter === 'unread' ? 'active' : '' ?>">
                                        <i class="fa-solid fa-envelope"></i>
                                        <span>Unread</span>
                                        <?php if ($unread_count > 0): ?>
                                            <span class="filter-badge"><?= $unread_count ?></span>
                                        <?php endif; ?>
                                    </button>
                                    <button type="submit" name="filter" value="read" 
                                            class="filter-btn <?= $filter === 'read' ? 'active' : '' ?>">
                                        <i class="fa-solid fa-envelope-open"></i>
                                        <span>Read</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Actions Section -->
                        <?php if ($unread_count > 0): ?>
                        <div class="actions-section">
                            <button type="button" class="btn btn-mark-all" onclick="markAllAsRead()">
                                <i class="fa-solid fa-check-double"></i>
                                <span>Mark All Read</span>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>


                <!-- Notifications Content -->
                <div class="notifications-content">
                    <?php if (empty($notifications)): ?>
                        <!-- Empty State -->
                        <div class="empty-state-card">
                            <div class="empty-state-icon">
                                <i class="fa-regular fa-bell-slash"></i>
                            </div>
                            <div class="empty-state-content">
                                <h4 class="empty-state-title">
                                    <?php if (!empty($search)): ?>
                                        No matching notifications found
                                    <?php elseif ($filter === 'unread'): ?>
                                        All caught up!
                                    <?php elseif ($filter === 'read'): ?>
                                        No read notifications
                                    <?php else: ?>
                                        No notifications yet
                                    <?php endif; ?>
                                </h4>
                                <p class="empty-state-description">
                                    <?php if (!empty($search)): ?>
                                        Try adjusting your search terms or clear the search to see all notifications.
                                    <?php elseif ($filter === 'unread'): ?>
                                        You have no unread notifications. Great job staying up to date!
                                    <?php elseif ($filter === 'read'): ?>
                                        No read notifications found in your history.
                                    <?php else: ?>
                                        Your notifications will appear here when you receive them.
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($search) || $filter !== 'all'): ?>
                                    <a href="view_all.php" class="btn btn-primary">
                                        <i class="fa-solid fa-refresh me-1"></i>View All Notifications
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Notifications List -->
                        <div class="notifications-list">
                            <?php foreach ($notifications as $index => $notification): ?>
                                <div class="notification-item <?= $notification['is_read'] ? 'read' : 'unread' ?>" 
                                     data-notification-id="<?= $notification['id'] ?>">
                                    
                                    <!-- Notification Status Indicator -->
                                    <div class="notification-status">
                                        <?php if (!$notification['is_read']): ?>
                                            <div class="status-dot unread-dot"></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Notification Icon -->
                                    <div class="notification-icon">
                                        <i class="fa-solid fa-bell"></i>
                                    </div>
                                    
                                    <!-- Notification Content -->
                                    <div class="notification-body">
                                        <div class="notification-header-row">
                                            <h5 class="notification-title">
                                                <?= htmlspecialchars($notification['title']) ?>
                                            </h5>
                                            <span class="notification-time">
                                                <i class="fa-regular fa-clock me-1"></i>
                                                <?= formatNotificationTime($notification['created_at']) ?>
                                            </span>
                                        </div>
                                        
                                        <p class="notification-message">
                                            <?= htmlspecialchars($notification['message']) ?>
                                        </p>
                                        
                                        <?php if (!$notification['is_read']): ?>
                                            <div class="notification-actions">
                                                <button class="btn-action mark-read" 
                                                        onclick="markAsRead(<?= $notification['id'] ?>, this)"
                                                        title="Mark as read">
                                                    <i class="fa-solid fa-check"></i>
                                                    <span>Mark as read</span>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Slide Page Navigation -->
                        <?php if ($total_pages > 1): ?>
                            <div class="slide-page-section">
                                <div class="slide-page-controls">
                                    <div class="slide-page-info">
                                        <span class="page-counter">Page <?= $page ?> of <?= $total_pages ?></span>
                                        <span class="items-info">
                                            Showing <?= ($page - 1) * $items_per_page + 1 ?>-<?= min($page * $items_per_page, $total_notifications) ?> of <?= $total_notifications ?>
                                        </span>
                                    </div>
                                    
                                    <div class="slide-page-buttons">
                                        <!-- Previous Button -->
                                        <?php if ($page > 1): ?>
                                            <a href="?page=<?= $page - 1 ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>" 
                                               class="slide-btn slide-btn-prev">
                                                <i class="fa-solid fa-chevron-left"></i>
                                                <span>Previous</span>
                                            </a>
                                        <?php else: ?>
                                            <span class="slide-btn slide-btn-prev disabled">
                                                <i class="fa-solid fa-chevron-left"></i>
                                                <span>Previous</span>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <!-- Page Numbers (Simplified) -->
                                        <div class="page-numbers">
                                            <?php 
                                            $start_page = max(1, $page - 1);
                                            $end_page = min($total_pages, $page + 1);
                                            
                                            if ($start_page > 1): ?>
                                                <a href="?page=1&filter=<?= $filter ?>&search=<?= urlencode($search) ?>" 
                                                   class="page-number">1</a>
                                                <?php if ($start_page > 2): ?>
                                                    <span class="page-ellipsis">...</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                                <a href="?page=<?= $i ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>" 
                                                   class="page-number <?= $i === $page ? 'active' : '' ?>">
                                                    <?= $i ?>
                                                </a>
                                            <?php endfor; ?>
                                            
                                            <?php if ($end_page < $total_pages): ?>
                                                <?php if ($end_page < $total_pages - 1): ?>
                                                    <span class="page-ellipsis">...</span>
                                                <?php endif; ?>
                                                <a href="?page=<?= $total_pages ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>" 
                                                   class="page-number"><?= $total_pages ?></a>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Next Button -->
                                        <?php if ($page < $total_pages): ?>
                                            <a href="?page=<?= $page + 1 ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>" 
                                               class="slide-btn slide-btn-next">
                                                <span>Next</span>
                                                <i class="fa-solid fa-chevron-right"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="slide-btn slide-btn-next disabled">
                                                <span>Next</span>
                                                <i class="fa-solid fa-chevron-right"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Include the Footer -->
        <?php include '../includes/footer.php'; ?>
    </main>

    <script>
        /**
         * Enhanced Notification Management Functions
         */
        
        // Clear search function
        function clearSearch() {
            const searchInput = document.querySelector('input[name="search"]');
            searchInput.value = '';
            searchInput.closest('form').submit();
        }
        
        // Clear header search function
        function clearHeaderSearch() {
            const headerSearchInput = document.getElementById('headerSearchInput');
            if (headerSearchInput) {
                headerSearchInput.value = '';
                // Submit the form to clear search
                const form = document.querySelector('.filter-form');
                if (form) {
                    form.submit();
                }
            }
        }
        
        // Mark single notification as read with improved UX
        function markAsRead(notificationId, buttonElement) {
            const notificationItem = buttonElement.closest('.notification-item');
            const originalButton = buttonElement.innerHTML;
            
            // Show loading state
            buttonElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span>Marking...</span>';
            buttonElement.disabled = true;
            buttonElement.classList.add('loading');
            
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
                if (data.success) {
                    // Smooth transition to read state
                    notificationItem.classList.add('marking-read');
                    
                    setTimeout(() => {
                        // Update notification appearance
                        notificationItem.classList.remove('unread', 'marking-read');
                        notificationItem.classList.add('read');
                        
                        // Remove status dot
                        const statusDot = notificationItem.querySelector('.status-dot');
                        if (statusDot) {
                            statusDot.style.opacity = '0';
                            setTimeout(() => statusDot.remove(), 300);
                        }
                        
                        // Remove actions
                        const actions = notificationItem.querySelector('.notification-actions');
                        if (actions) {
                            actions.style.opacity = '0';
                            setTimeout(() => actions.remove(), 300);
                        }
                        
                        // Update icon
                        const icon = notificationItem.querySelector('.notification-icon');
                        icon.classList.add('read-icon');
                        
                        // Show success message
                        showToast('Notification marked as read', 'success');
                        
                        // Update counts
                        updateNotificationCounts();
                        
                    }, 300);
                    
                } else {
                    throw new Error('Failed to mark notification as read');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                buttonElement.innerHTML = originalButton;
                buttonElement.disabled = false;
                buttonElement.classList.remove('loading');
                showToast('Error marking notification as read', 'error');
            });
        }
        
        // Mark all notifications as read with confirmation
        function markAllAsRead() {
            const confirmMessage = 'Are you sure you want to mark all notifications as read?';
            if (!confirm(confirmMessage)) {
                return;
            }
            
            const button = event.target;
            const originalText = button.innerHTML;
            
            // Show loading state
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i><span>Marking All...</span>';
            button.disabled = true;
            button.classList.add('loading');
            
            fetch('../api/mark_all_notifications_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Show success and reload
                    showToast('All notifications marked as read', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    throw new Error('Failed to mark all notifications as read');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                button.innerHTML = originalText;
                button.disabled = false;
                button.classList.remove('loading');
                showToast('Error marking notifications as read', 'error');
            });
        }
        
        // Update notification counts in real-time
        function updateNotificationCounts() {
            const currentUnread = document.querySelectorAll('.notification-item.unread').length;
            const total = document.querySelectorAll('.notification-item').length;
            
            // Update stats
            const unreadStat = document.querySelector('.stat-card.unread .stat-number');
            const readStat = document.querySelector('.stat-card.read .stat-number');
            
            if (unreadStat) unreadStat.textContent = currentUnread;
            if (readStat) readStat.textContent = total - currentUnread;
            
            // Update filter badge
            const unreadBadge = document.querySelector('.filter-btn .badge');
            if (unreadBadge) {
                if (currentUnread > 0) {
                    unreadBadge.textContent = currentUnread;
                } else {
                    unreadBadge.style.display = 'none';
                }
            }
            
            // Hide mark all button if no unread
            const markAllBtn = document.querySelector('.btn-mark-all');
            if (markAllBtn && currentUnread === 0) {
                markAllBtn.style.opacity = '0';
                setTimeout(() => markAllBtn.style.display = 'none', 300);
            }
        }
        
        // Enhanced toast notifications
        function showToast(message, type = 'info') {
            const toastHtml = `
                <div class="toast-notification ${type}" role="alert">
                    <div class="toast-icon">
                        <i class="fa-solid fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
                    </div>
                    <div class="toast-content">
                        <div class="toast-message">${message}</div>
                    </div>
                    <button type="button" class="toast-close" onclick="this.parentElement.remove()">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
            `;
            
            let container = document.getElementById('toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                container.className = 'toast-container';
                document.body.appendChild(container);
            }
            
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = toastHtml;
            const toastElement = tempDiv.firstElementChild;
            container.appendChild(toastElement);
            
            // Auto-remove after 4 seconds
            setTimeout(() => {
                if (toastElement && toastElement.parentElement) {
                    toastElement.classList.add('removing');
                    setTimeout(() => toastElement.remove(), 300);
                }
            }, 4000);
        }
        
        // Auto-submit search with debounce for header search
        let searchTimeout;
        const headerSearchInput = document.getElementById('headerSearchInput');
        if (headerSearchInput) {
            headerSearchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const form = document.querySelector('.filter-form');
                    const currentUrl = new URL(window.location);
                    if (currentUrl.searchParams.get('search') !== this.value.trim()) {
                        // Update the hidden search input in the form
                        const hiddenSearchInput = form.querySelector('input[name="search"]');
                        if (hiddenSearchInput) {
                            hiddenSearchInput.value = this.value.trim();
                        }
                        // Reset to page 1 when searching
                        form.querySelector('input[name="page"]').value = '1';
                        form.submit();
                    }
                }, 500);
            });
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth scrolling for pagination
            document.querySelectorAll('.pagination a').forEach(link => {
                link.addEventListener('click', function(e) {
                    // Smooth scroll to top of notifications
                    const notificationsContent = document.querySelector('.notifications-content');
                    if (notificationsContent) {
                        notificationsContent.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });
            
            // Auto-refresh every 5 minutes for new notifications
            setInterval(() => {
                if (!document.hidden) {
                    window.location.reload();
                }
            }, 300000);
        });
    </script>

    <!-- Enhanced Custom CSS -->
    <style>
        :root {
            --primary-color: #007bff;
            --primary-dark: #0056b3;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-color: #e9ecef;
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 8px 25px rgba(0, 0, 0, 0.15);
            --radius-sm: 6px;
            --radius-md: 12px;
            --radius-lg: 16px;
        }

        /* Base Styles */
        .notifications-container {
            max-width: 1200px;
        }

        /* Page Header */
        .page-header {
            background: white;
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .header-main {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 2rem;
        }

        .header-title-section {
            flex: 1;
            min-width: 300px;
        }

        .header-stats {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        /* Controls Bar */
        .controls-bar {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .controls-container {
            display: flex;
            align-items: center;
            gap: 2rem;
            flex-wrap: wrap;
        }

        /* Search Section */
        .search-section {
            flex: 1;
            min-width: 300px;
        }

        .search-wrapper {
            position: relative;
            max-width: 400px;
        }

        .search-input {
            border: 2px solid var(--border-color);
            border-radius: 25px;
            padding: 0.75rem 1rem 0.75rem 3rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #fafbfc;
            width: 100%;
        }

        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
            background: white;
            outline: none;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        .clear-search {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--secondary-color);
            padding: 0.25rem;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .clear-search:hover {
            background: var(--danger-color);
            color: white;
        }

        /* Filter Section */
        .filter-section {
            flex: 1;
            min-width: 300px;
        }

        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            border: 2px solid var(--border-color);
            background: white;
            color: var(--secondary-color);
            border-radius: 20px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
            cursor: pointer;
            min-width: 100px;
            justify-content: center;
        }

        .filter-btn:hover {
            border-color: var(--primary-color);
            background: rgba(0, 123, 255, 0.05);
            color: var(--primary-color);
            transform: translateY(-1px);
        }

        .filter-btn.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
        }

        .filter-badge {
            background: rgba(255, 255, 255, 0.3);
            color: inherit;
            border-radius: 10px;
            padding: 0.125rem 0.375rem;
            font-size: 0.75rem;
            margin-left: 0.25rem;
        }

        .filter-btn.active .filter-badge {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Actions Section */
        .actions-section {
            flex-shrink: 0;
        }

        .btn-mark-all {
            background: var(--success-color);
            border: 2px solid var(--success-color);
            color: white;
            border-radius: 20px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-mark-all:hover {
            background: #218838;
            border-color: #218838;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .icon-header {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
        }

        .page-subtitle {
            color: var(--secondary-color);
            font-size: 0.95rem;
            margin: 0;
        }

        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: var(--radius-md);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s ease;
            border: 1px solid var(--border-color);
            min-width: 120px;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            opacity: 0.8;
        }

        .stat-card.total .stat-number { color: var(--info-color); }
        .stat-card.unread .stat-number { color: var(--primary-color); }
        .stat-card.read .stat-number { color: var(--success-color); }

        /* Controls Section */
        .controls-section {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        /* Search Wrapper */
        .search-wrapper {
            position: relative;
        }

        .search-input {
            border: 2px solid var(--border-color);
            border-radius: 25px;
            padding: 0.75rem 1rem 0.75rem 3rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #fafbfc;
        }

        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
            background: white;
            outline: none;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        .clear-search {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--secondary-color);
            padding: 0.25rem;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .clear-search:hover {
            background: var(--danger-color);
            color: white;
        }

        /* Filter Group */
        .filter-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            border: 2px solid var(--border-color);
            background: white;
            color: var(--secondary-color);
            border-radius: 20px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            white-space: nowrap;
        }

        .filter-btn:hover {
            border-color: var(--primary-color);
            background: rgba(0, 123, 255, 0.05);
            color: var(--primary-color);
            transform: translateY(-1px);
        }

        .filter-btn.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
        }

        .filter-btn .badge {
            background: rgba(255, 255, 255, 0.3);
            color: inherit;
            border-radius: 10px;
            padding: 0.125rem 0.375rem;
            font-size: 0.75rem;
            margin-left: 0.25rem;
        }

        .filter-btn.active .badge {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Mark All Button */
        .btn-mark-all {
            background: var(--success-color);
            border: 2px solid var(--success-color);
            color: white;
            border-radius: 20px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
        }

        .btn-mark-all:hover {
            background: #218838;
            border-color: #218838;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .btn-mark-all.loading {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        /* Notifications Content */
        .notifications-content {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        /* Empty State */
        .empty-state-card {
            padding: 4rem 2rem;
            text-align: center;
        }

        .empty-state-icon {
            margin-bottom: 2rem;
        }

        .empty-state-icon i {
            font-size: 4rem;
            color: var(--border-color);
        }

        .empty-state-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }

        .empty-state-description {
            color: var(--secondary-color);
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Notifications List */
        .notifications-list {
            padding: 0;
        }

        .notification-item {
            display: flex;
            align-items: flex-start;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background: #fafbfc;
        }

        .notification-item.unread {
            background: linear-gradient(90deg, #f8f9ff 0%, white 100%);
            border-left: 4px solid var(--primary-color);
            margin-left: -1px;
        }

        .notification-item.marking-read {
            opacity: 0.7;
            transform: scale(0.98);
        }

        /* Notification Status */
        .notification-status {
            width: 20px;
            display: flex;
            align-items: flex-start;
            padding-top: 0.25rem;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            transition: opacity 0.3s ease;
        }

        .unread-dot {
            background: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.2);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.2); }
            50% { box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.1); }
            100% { box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.2); }
        }

        /* Notification Icon */
        .notification-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-right: 1rem;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .notification-item.unread .notification-icon {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }

        .notification-item.read .notification-icon {
            background: #e9ecef;
            color: var(--secondary-color);
        }

        .notification-icon.read-icon {
            background: var(--success-color) !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3) !important;
        }

        /* Notification Body */
        .notification-body {
            flex: 1;
            min-width: 0;
        }

        .notification-header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
            gap: 1rem;
        }

        .notification-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
            line-height: 1.4;
        }

        .notification-item.unread .notification-title {
            font-weight: 700;
        }

        .notification-time {
            color: var(--secondary-color);
            font-size: 0.85rem;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .notification-message {
            color: var(--secondary-color);
            line-height: 1.6;
            margin: 0 0 1rem 0;
            font-size: 0.95rem;
        }

        /* Notification Actions */
        .notification-actions {
            display: flex;
            gap: 0.5rem;
            transition: opacity 0.3s ease;
        }

        .btn-action {
            background: rgba(0, 123, 255, 0.1);
            border: 1px solid rgba(0, 123, 255, 0.2);
            color: var(--primary-color);
            border-radius: var(--radius-sm);
            padding: 0.375rem 0.75rem;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.375rem;
            text-decoration: none;
        }

        .btn-action:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
        }

        .btn-action.loading {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        /* Slide Page Section */
        .slide-page-section {
            padding: 2rem;
            background: linear-gradient(135deg, #f8f9ff 0%, #e3f2fd 100%);
            border-top: 1px solid var(--border-color);
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
        }

        .slide-page-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .slide-page-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .page-counter {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 1rem;
        }

        .items-info {
            color: var(--secondary-color);
            font-size: 0.85rem;
        }

        .slide-page-buttons {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .slide-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 500;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            background: white;
            color: var(--secondary-color);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .slide-btn:hover:not(.disabled) {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }

        .slide-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f8f9fa;
            color: var(--border-color);
        }

        .slide-btn-prev {
            flex-direction: row;
        }

        .slide-btn-next {
            flex-direction: row-reverse;
        }

        .page-numbers {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            padding: 0.5rem;
            border-radius: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .page-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--secondary-color);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .page-number:hover {
            background: rgba(0, 123, 255, 0.1);
            color: var(--primary-color);
            border-color: rgba(0, 123, 255, 0.2);
            transform: scale(1.1);
        }

        .page-number.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
            transform: scale(1.1);
        }

        .page-ellipsis {
            color: var(--secondary-color);
            font-weight: 500;
            padding: 0 0.5rem;
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1050;
            max-width: 350px;
        }

        .toast-notification {
            background: white;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            margin-bottom: 0.5rem;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease;
            transition: all 0.3s ease;
        }

        .toast-notification.removing {
            opacity: 0;
            transform: translateX(100%);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .toast-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .toast-notification.success .toast-icon {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }

        .toast-notification.error .toast-icon {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }

        .toast-notification.info .toast-icon {
            background: rgba(23, 162, 184, 0.1);
            color: var(--info-color);
        }

        .toast-content {
            flex: 1;
        }

        .toast-message {
            font-weight: 500;
            color: var(--dark-color);
            font-size: 0.9rem;
        }

        .toast-close {
            background: none;
            border: none;
            color: var(--secondary-color);
            padding: 0.25rem;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .toast-close:hover {
            background: var(--border-color);
            color: var(--dark-color);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .page-header {
                padding: 1.5rem;
            }
            
            .header-main {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 1.5rem;
            }
            
            .header-stats {
                justify-content: center;
            }
            
            .controls-bar {
                padding: 1rem;
            }
            
            .controls-container {
                flex-direction: column;
                gap: 1.5rem;
                align-items: stretch;
            }
            
            .search-section,
            .filter-section {
                min-width: auto;
                width: 100%;
            }
            
            .search-wrapper {
                max-width: 100%;
            }
            
            .filter-buttons {
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 1rem;
            }
            
            .icon-header {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .header-stats {
                flex-direction: column;
                width: 100%;
                gap: 0.75rem;
            }
            
            .stat-card {
                min-width: auto;
                width: 100%;
            }
            
            .controls-container {
                gap: 1rem;
            }
            
            .filter-buttons {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .filter-btn {
                width: 100%;
                min-width: auto;
            }
            
            .notification-item {
                padding: 1rem;
            }
            
            .notification-header-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .notification-time {
                font-size: 0.8rem;
            }
            
            .filter-group {
                flex-direction: column;
            }
            
            .filter-btn {
                justify-content: center;
            }
            
            /* Slide Page Mobile Styles */
            .slide-page-controls {
                flex-direction: column;
                text-align: center;
                gap: 1.5rem;
            }
            
            .slide-page-buttons {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .slide-btn {
                width: 100%;
                justify-content: center;
                padding: 1rem 1.5rem;
            }
            
            .page-numbers {
                justify-content: center;
                flex-wrap: wrap;
                gap: 0.25rem;
            }
            
            .page-number {
                width: 35px;
                height: 35px;
                font-size: 0.85rem;
            }
            
            .toast-container {
                right: 0.5rem;
                left: 0.5rem;
                max-width: none;
            }
        }

        @media (max-width: 576px) {
            .container-fluid {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            
            .page-header {
                padding: 1rem;
            }
            
            /* Header Search Small Mobile Styles */
            .header-search-section .search-input {
                padding: 0.5rem 0.75rem 0.5rem 2.5rem;
                font-size: 0.9rem;
            }
            
            .header-search-section .search-icon {
                left: 0.75rem;
                font-size: 0.8rem;
            }
            
            .header-search-section .clear-search {
                right: 0.5rem;
                width: 20px;
                height: 20px;
            }
            
            .controls-section {
                padding: 0.75rem;
            }
            
            .notification-icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            
            .notification-item {
                padding: 0.75rem;
            }
            
            .slide-page-section {
                padding: 1rem;
            }
            
            .slide-page-info {
                text-align: center;
            }
            
            .page-counter {
                font-size: 0.9rem;
            }
            
            .items-info {
                font-size: 0.8rem;
            }
            
            .slide-btn {
                padding: 0.75rem 1rem;
                font-size: 0.85rem;
            }
            
            .page-number {
                width: 30px;
                height: 30px;
                font-size: 0.8rem;
            }
        }

        /* Loading States */
        .btn.loading {
            position: relative;
            color: transparent !important;
        }

        .btn.loading::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Focus States for Accessibility */
        .filter-btn:focus,
        .btn-action:focus,
        .btn-mark-all:focus,
        .page-link:focus,
        .search-input:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }

        /* High Contrast Mode Support */
        @media (prefers-contrast: high) {
            .notification-item {
                border: 2px solid var(--border-color);
            }
            
            .notification-item.unread {
                border-left: 6px solid var(--primary-color);
            }
        }

        /* Reduced Motion Support */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
            
            .status-dot {
                animation: none;
            }
        }
    </style>
</body>
</html>

<?php
/**
 * Helper function to format notification time
 */
function formatNotificationTime($dateString) {
    $date = new DateTime($dateString);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->days == 0) {
        if ($diff->h == 0) {
            if ($diff->i < 1) {
                return 'Just now';
            } else {
                return $diff->i . 'm ago';
            }
        } else {
            return $diff->h . 'h ago';
        }
    } elseif ($diff->days < 7) {
        return $diff->days . 'd ago';
    } else {
        return $date->format('M j, Y');
    }
}
?>