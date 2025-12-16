<?php
session_start();
include '../includes/conn.php';
include '../includes/header.php';

if (!isset($_SESSION['client_id'])) {
    header("Location: ../index.php");
    exit();
}

$client_id = $_SESSION['client_id'];

// Mark notification as read if requested
if (isset($_GET['mark_read']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE client_notifications SET is_read = 1 WHERE id = ? AND client_id = ?");
    $stmt->execute([$id, $client_id]);
    header("Location: client_notifications.php");
    exit();
}

// Get client notifications
$stmt = $pdo->prepare("
    SELECT * FROM client_notifications 
    WHERE client_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$client_id]);
$notifications = $stmt->fetchAll();

// Get client's requests
$stmt = $pdo->prepare("
    SELECT * FROM client_requests 
    WHERE client_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$client_id]);
$requests = $stmt->fetchAll();

$page_title = "Client Notifications";

?>
<body class="g-sidenav-show bg-gray-200">
    <?php include '../sidebar/client_sidebar.php'; ?>
    
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include '../includes/navbar.php'; ?>
        
        <div class="container-fluid py-4">
            <h1 class="h3 mb-4 text-gray-800"></h1>
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-lg">
                        <div class="card-header p-0 position-relative mt-n4 mx-4 z-index-2">
                        <div style="background: linear-gradient(60deg, #66c05eff, #49755cff 100%);" class="shadow-dark border-radius-lg pt-4 pb-3">
                                <h5 class="text-white text-center text-uppercase font-weight-bold mb-0">
                                    <i class="fas fa-bell me-2"></i>Notifications & Requests
                                </h5>
                            </div>
                        </div>
                        
                        <div class="card-body px-0 pb-2">
                            <div class="row mx-4 mb-4">
                                <!-- Notifications Section -->
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-bell me-2"></i>Recent Notifications
                                                <?php 
                                                $unread_count = array_filter($notifications, function($n) { return !$n['is_read']; });
                                                if (count($unread_count) > 0): 
                                                ?>
                                                <span class="badge bg-danger ms-2"><?= count($unread_count) ?></span>
                                                <?php endif; ?>
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <?php if (empty($notifications)): ?>
                                                <div class="text-center py-4">
                                                    <i class="fas fa-bell-slash text-muted" style="font-size: 3rem;"></i>
                                                    <p class="text-muted mt-2">No notifications yet</p>
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($notifications as $notification): ?>
                                                <div class="notification-card card mb-3 <?= $notification['is_read'] ? '' : 'unread' ?>">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div class="flex-grow-1">
                                                                <h6 class="card-title mb-1"><?= htmlspecialchars($notification['title']) ?></h6>
                                                                <p class="card-text text-muted small mb-2"><?= htmlspecialchars($notification['message']) ?></p>
                                                                <small class="text-muted">
                                                                    <?= date('M d, Y H:i', strtotime($notification['created_at'])) ?>
                                                                </small>
                                                            </div>
                                                            <div class="btn-group" role="group">
                                                            <button 
                                                                class="btn btn-sm btn-outline-primary view-btn" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#notificationModal"
                                                            data-id="<?= $notification['id'] ?>"
                                                            data-title="<?= htmlspecialchars($notification['title'], ENT_QUOTES) ?>"
                                                            data-message="<?= htmlspecialchars($notification['message'], ENT_QUOTES) ?>"
                                                            data-date="<?= date('M d, Y H:i', strtotime($notification['created_at'])) ?>">
                                                                <i class="fas fa-eye me-1"></i>View
                                                        </button>
                                                                
                                                                <?php if (!$notification['is_read']): ?>
                                                        <a href="client_notifications.php?mark_read=1&id=<?= $notification['id'] ?>" 
                                                                class="btn btn-sm btn-outline-success">
                                                                <i class="fas fa-check"></i> Mark Read
                                                                </a>
                                                                <?php endif; ?>
                                                                
                                                                <button 
                                                                class="btn btn-sm btn-outline-danger delete-notification-btn" 
                                                                data-id="<?= $notification['id'] ?>"
                                                                data-title="<?= htmlspecialchars($notification['title'], ENT_QUOTES) ?>">
                                                                <i class="fas fa-trash"></i> Delete
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Requests Section -->
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-clipboard-list me-2"></i>My Requests
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <?php if (empty($requests)): ?>
                                                <div class="text-center py-4">
                                                    <i class="fas fa-clipboard text-muted" style="font-size: 3rem;"></i>
                                                    <p class="text-muted mt-2">No requests submitted yet</p>
                                                    <a href="client_request.php" class="btn btn-primary">
                                                        <i class="fas fa-plus me-2"></i>Submit Request
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($requests as $request): ?>
                                                <div class="notification-card card mb-3 <?= $request['status'] ?>">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div class="flex-grow-1">
                                                                <h6 class="card-title mb-1"><?= htmlspecialchars($request['request_details']) ?></h6>
                                                                <p class="card-text text-muted small mb-2">
                                                                    <?= htmlspecialchars($request['request_description']) ?>
                                                                </p>
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <small class="text-muted">
                                                                        Preferred: <?= date('M d, Y', strtotime($request['request_date'])) ?>
                                                                    </small>
                                                                    <?php
                                                                    $status_class = '';
                                                                    $status_text = '';
                                                                    switch ($request['status']) {
                                                                        case 'pending':
                                                                            $status_class = 'bg-warning';
                                                                            $status_text = 'Pending';
                                                                            break;
                                                                        case 'approved':
                                                                            $status_class = 'bg-success';
                                                                            $status_text = 'Approved';
                                                                            break;
                                                                        case 'rejected':
                                                                            $status_class = 'bg-danger';
                                                                            $status_text = 'Rejected';
                                                                            break;
                                                                    }
                                                                    ?>
                                                                    <span class="badge <?= $status_class ?> request-status"><?= $status_text ?></span>
                                                                </div>
                                                                <?php if ($request['admin_notes']): ?>
                                                                <div class="mt-2">
                                                                    <small class="text-muted">
                                                                        <strong>Admin Notes:</strong> <?= htmlspecialchars($request['admin_notes']) ?>
                                                                    </small>
                                                                </div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="btn-group" role="group">
                                                                <button 
                                                                class="btn btn-sm btn-outline-primary view-request-btn" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#requestModal"
                                                                data-id="<?= $request['id'] ?>"
                                                                data-details="<?= htmlspecialchars($request['request_details'], ENT_QUOTES) ?>"
                                                                data-description="<?= htmlspecialchars($request['request_description'], ENT_QUOTES) ?>"
                                                                data-date="<?= date('M d, Y', strtotime($request['request_date'])) ?>"
                                                                data-status="<?= $request['status'] ?>"
                                                                data-notes="<?= htmlspecialchars($request['admin_notes'] ?? '', ENT_QUOTES) ?>">
                                                                <i class="fas fa-eye me-1"></i>View
                                                                </button>
                                                                
                                                                <button 
                                                                class="btn btn-sm btn-outline-danger delete-request-btn" 
                                                                data-id="<?= $request['id'] ?>"
                                                                data-details="<?= htmlspecialchars($request['request_details'], ENT_QUOTES) ?>">
                                                                <i class="fas fa-trash"></i> Delete
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php include '../includes/footer.php'; ?>
</main>

    <!-- Notification Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-gradient-success text-white">
        <h5 class="modal-title" id="notificationModalLabel">Notification Details</h5>
        <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <h6 id="modal-title"></h6>
        <p id="modal-message" class="text-muted"></p>
        <small class="text-muted d-block mt-3">Created at: <span id="modal-date"></span></small>
      </div>
    </div>
  </div>
</div>

<!-- Request Details Modal -->
<div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-gradient-success text-white">
        <h5 class="modal-title" id="requestModalLabel">Request Details</h5>
        <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <h6 class="text-primary">Request Details</h6>
            <p id="modal-request-details" class="mb-3"></p>
            
            <h6 class="text-primary">Description</h6>
            <p id="modal-request-description" class="text-muted mb-3"></p>
          </div>
          <div class="col-md-6">
            <h6 class="text-primary">Preferred Date</h6>
            <p id="modal-request-date" class="mb-3"></p>
            
            <h6 class="text-primary">Status</h6>
            <span id="modal-request-status" class="badge mb-3"></span>
            
            <h6 class="text-primary">Admin Notes</h6>
            <p id="modal-request-notes" class="text-muted"></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
        <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete this item?</p>
          <strong id="delete-item-title"></strong>
        <p class="text-muted">This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirm-delete-btn">
          <i class="fas fa-trash me-1"></i>Delete
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

    <script src="../assets/js/notification-sound.js"></script>
    <script>
        // Auto-refresh notifications every 30 seconds
        setInterval(function() {
            checkForNewNotifications();
        }, 30000);

        // Check for new notifications and play sound
        async function checkForNewNotifications() {
            try {
                const response = await fetch('../api/check_new_notifications.php');
                const data = await response.json();
                
                console.log('Notification check response:', data); // Debug log
                
                if (data.success && data.hasNewNotifications) {
                    console.log('New notifications found:', data.count); // Debug log
                    
                    // Play notification sound
                    if (window.notificationSound) {
                        window.notificationSound.playNotificationSound('default');
                    }
                    
                    // Update notification count in navbar if exists
                    updateNotificationCount(data.count);
                    
                    // Show visual notification
                    showNotificationToast(data.count);
                }
            } catch (error) {
                console.error('Error checking notifications:', error);
            }
        }

        // Update notification count in navbar
        function updateNotificationCount(count) {
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'inline' : 'none';
            }
        }

        // Show notification toast
        function showNotificationToast(count) {
            if (window.showToast) {
                window.showToast(`You have ${count} new notification${count > 1 ? 's' : ''}`, 'info');
            }
        }

        // Initialize notification checking on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Check for new notifications immediately
            checkForNewNotifications();
            
            // Set up sound toggle button if it doesn't exist
            setupSoundToggle();
            
            // Load saved sound settings
            loadSoundSettings();
            
            // Set up sound settings modal
            setupSoundSettingsModal();
        });

        // Show sound settings modal
        function showSoundSettingsModal() {
            const modal = new bootstrap.Modal(document.getElementById('soundSettingsModal'));
            modal.show();
        }

        // Setup sound settings modal functionality
        function setupSoundSettingsModal() {
            const volumeSlider = document.getElementById('volumeSlider');
            const volumeDisplay = document.getElementById('volumeDisplay');
            const testSoundBtn = document.getElementById('testSoundBtn');
            const saveSettingsBtn = document.getElementById('saveSoundSettings');
            const soundTypeSelect = document.getElementById('soundTypeSelect');
            const enableSoundCheckbox = document.getElementById('enableSound');
            const enableVisualCheckbox = document.getElementById('enableVisual');

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
                }
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('soundSettingsModal'));
                modal.hide();
            });
        }

        // Load saved sound settings
        function loadSoundSettings() {
            const settings = getSoundSettings();
            
            if (settings.soundType) {
                document.getElementById('soundTypeSelect').value = settings.soundType;
            }
            if (settings.volume !== undefined) {
                document.getElementById('volumeSlider').value = settings.volume * 100;
                document.getElementById('volumeDisplay').textContent = Math.round(settings.volume * 100) + '%';
            }
            if (settings.enableSound !== undefined) {
                document.getElementById('enableSound').checked = settings.enableSound;
            }
            if (settings.enableVisual !== undefined) {
                document.getElementById('enableVisual').checked = settings.enableVisual;
            }
        }

        // Save sound settings to localStorage
        function saveSoundSettings(settings) {
            localStorage.setItem('notificationSoundSettings', JSON.stringify(settings));
            
            // Update global notification sound settings
            if (window.notificationSound) {
                window.notificationSound.setSoundType(settings.soundType);
                window.notificationSound.setVolume(settings.volume);
                window.notificationSound.setSoundPreference(settings.enableSound);
            }
        }

        // Get sound settings from localStorage
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

        // Setup sound toggle functionality
        function setupSoundToggle() {
            // Sound settings are now handled in the navbar
            // This function is kept for compatibility but does nothing
        }

        // Handle notification modal and delete functionality
        document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('notificationModal');
    const titleEl = document.getElementById('modal-title');
    const messageEl = document.getElementById('modal-message');
    const dateEl = document.getElementById('modal-date');

            // View button functionality for notifications
    const viewButtons = document.querySelectorAll('.view-btn');
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const title = this.getAttribute('data-title');
            const message = this.getAttribute('data-message');
            const date = this.getAttribute('data-date');
            const id = this.getAttribute('data-id');

            // Set modal content
            titleEl.textContent = title;
            messageEl.textContent = message;
            dateEl.textContent = date;

            // Optional: Mark as read via AJAX
            fetch(`mark_read.php?id=${id}`, {
                method: 'GET'
            }).then(res => res.ok && console.log("Marked as read"));
        });
    });

            // View button functionality for requests
            const viewRequestButtons = document.querySelectorAll('.view-request-btn');
            const requestModal = document.getElementById('requestModal');
            const requestDetailsEl = document.getElementById('modal-request-details');
            const requestDescriptionEl = document.getElementById('modal-request-description');
            const requestDateEl = document.getElementById('modal-request-date');
            const requestStatusEl = document.getElementById('modal-request-status');
            const requestNotesEl = document.getElementById('modal-request-notes');

            viewRequestButtons.forEach(btn => {
                btn.addEventListener('click', function () {
                    const details = this.getAttribute('data-details');
                    const description = this.getAttribute('data-description');
                    const date = this.getAttribute('data-date');
                    const status = this.getAttribute('data-status');
                    const notes = this.getAttribute('data-notes');

                    // Set modal content
                    requestDetailsEl.textContent = details;
                    requestDescriptionEl.textContent = description;
                    requestDateEl.textContent = date;
                    
                    // Set status badge
                    let statusClass = '';
                    let statusText = '';
                    switch (status) {
                        case 'pending':
                            statusClass = 'bg-warning';
                            statusText = 'Pending';
                            break;
                        case 'approved':
                            statusClass = 'bg-success';
                            statusText = 'Approved';
                            break;
                        case 'rejected':
                            statusClass = 'bg-danger';
                            statusText = 'Rejected';
                            break;
                    }
                    requestStatusEl.className = `badge ${statusClass}`;
                    requestStatusEl.textContent = statusText;
                    
                    requestNotesEl.textContent = notes || 'No admin notes available';
                });
            });

            // Delete button functionality for notifications
            const deleteNotificationButtons = document.querySelectorAll('.delete-notification-btn');
            const deleteModal = document.getElementById('deleteModal');
            const deleteTitleEl = document.getElementById('delete-item-title');
            const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
            let itemToDelete = null;
            let deleteType = null;

            deleteNotificationButtons.forEach(btn => {
                btn.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    const title = this.getAttribute('data-title');
                    
                    itemToDelete = id;
                    deleteType = 'notification';
                    deleteTitleEl.textContent = title;
                    
                    // Show delete confirmation modal
                    const modal = new bootstrap.Modal(deleteModal);
                    modal.show();
                });
            });

            // Delete button functionality for requests
            const deleteRequestButtons = document.querySelectorAll('.delete-request-btn');
            deleteRequestButtons.forEach(btn => {
                btn.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    const details = this.getAttribute('data-details');
                    
                    itemToDelete = id;
                    deleteType = 'request';
                    deleteTitleEl.textContent = details;
                    
                    // Show delete confirmation modal
                    const modal = new bootstrap.Modal(deleteModal);
                    modal.show();
                });
            });

            // Confirm delete
            confirmDeleteBtn.addEventListener('click', function () {
                if (itemToDelete && deleteType) {
                    if (deleteType === 'notification') {
                        deleteNotification(itemToDelete);
                    } else if (deleteType === 'request') {
                        deleteRequest(itemToDelete);
                    }
                }
            });

            // Delete notification function
            function deleteNotification(notificationId) {
                // Add loading state to delete button
                const deleteBtn = document.querySelector(`[data-id="${notificationId}"]`);
                if (deleteBtn) {
                    deleteBtn.classList.add('loading');
                    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Deleting...';
                }

                const formData = new FormData();
                formData.append('notification_id', notificationId);

                fetch('delete_notification.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Find and remove the notification card with slide animation
                        const notificationCard = document.querySelector(`[data-id="${notificationId}"]`).closest('.notification-card');
                        if (notificationCard) {
                            // Add slide out animation
                            notificationCard.classList.add('slide-out');
                            
                            setTimeout(() => {
                                notificationCard.remove();
                                
                                // Check if no notifications left
                                const notificationsContainer = document.querySelector('.col-md-6:first-child .card-body');
                                const remainingNotifications = notificationsContainer.querySelectorAll('.notification-card');
                                if (remainingNotifications.length === 0) {
                                    // Show empty state with animation
                                    notificationsContainer.innerHTML = `
                                        <div class="text-center py-4 slide-in">
                                            <i class="fas fa-bell-slash text-muted" style="font-size: 3rem;"></i>
                                            <p class="text-muted mt-2">No notifications yet</p>
                                        </div>
                                    `;
                                }
                            }, 500);
                        }
                        
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(deleteModal);
                        modal.hide();
                        
                        // Show success message
                        showToast('Notification deleted successfully', 'success');
                    } else {
                        // Reset button state on error
                        if (deleteBtn) {
                            deleteBtn.classList.remove('loading');
                            deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                        }
                        showToast(data.message || 'Error deleting notification', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Reset button state on error
                    if (deleteBtn) {
                        deleteBtn.classList.remove('loading');
                        deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                    }
                    showToast('Error deleting notification', 'error');
                });
            }

            // Delete request function
            function deleteRequest(requestId) {
                // Add loading state to delete button
                const deleteBtn = document.querySelector(`[data-id="${requestId}"]`);
                if (deleteBtn) {
                    deleteBtn.classList.add('loading');
                    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Deleting...';
                }

                const formData = new FormData();
                formData.append('request_id', requestId);

                fetch('delete_request.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Find and remove the request card with slide animation
                        const requestCard = document.querySelector(`[data-id="${requestId}"]`).closest('.notification-card');
                        if (requestCard) {
                            // Add slide out animation
                            requestCard.classList.add('slide-out');
                            
                            setTimeout(() => {
                                requestCard.remove();
                                
                                // Check if no requests left
                                const requestsContainer = document.querySelector('.col-md-6:last-child .card-body');
                                const remainingRequests = requestsContainer.querySelectorAll('.notification-card');
                                if (remainingRequests.length === 0) {
                                    // Show empty state with animation
                                    requestsContainer.innerHTML = `
                                        <div class="text-center py-4 slide-in">
                                            <i class="fas fa-clipboard text-muted" style="font-size: 3rem;"></i>
                                            <p class="text-muted mt-2">No requests submitted yet</p>
                                            <a href="client_request.php" class="btn btn-primary">
                                                <i class="fas fa-plus me-2"></i>Submit Request
                                            </a>
                                        </div>
                                    `;
                                }
                            }, 500);
                        }
                        
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(deleteModal);
                        modal.hide();
                        
                        // Show success message
                        showToast('Request deleted successfully', 'success');
                    } else {
                        // Reset button state on error
                        if (deleteBtn) {
                            deleteBtn.classList.remove('loading');
                            deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                        }
                        showToast(data.message || 'Error deleting request', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Reset button state on error
                    if (deleteBtn) {
                        deleteBtn.classList.remove('loading');
                        deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                    }
                    showToast('Error deleting request', 'error');
                });
            }

            // Toast notification function
            function showToast(message, type) {
                const toastContainer = document.getElementById('toast-container') || createToastContainer();
                const toastId = 'toast-' + Date.now();
                const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
                
                const toastHTML = `
                    <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                                ${message}
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                `;
                
                toastContainer.insertAdjacentHTML('beforeend', toastHTML);
                
                const toastElement = document.getElementById(toastId);
                const toast = new bootstrap.Toast(toastElement);
                toast.show();
                
                // Remove toast element after it's hidden
                toastElement.addEventListener('hidden.bs.toast', () => {
                    toastElement.remove();
                });
            }

            // Create toast container if it doesn't exist
            function createToastContainer() {
                const container = document.createElement('div');
                container.id = 'toast-container';
                container.className = 'toast-container position-fixed top-0 end-0 p-3';
                container.style.zIndex = '9999';
                document.body.appendChild(container);
                return container;
            }
});
    </script>
</body>
</html>
<style>
        .notification-card {
            transition: all 0.3s ease;
            border-left: 4px solid #dee2e6;
            position: relative;
            overflow: hidden;
        }
        .notification-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .notification-card.slide-out {
            transform: translateX(-100%);
            opacity: 0;
            transition: all 0.5s ease;
        }
        .notification-card.slide-in {
            animation: slideInFromRight 0.5s ease;
        }
        @keyframes slideInFromRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .notification-card.unread {
            border-left-color: #007bff;
            background-color: #f8f9fa;
        }
        .notification-card.approved {
            border-left-color: #28a745;
        }
        .notification-card.rejected {
            border-left-color: #dc3545;
        }
        .request-status {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
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

        /* Button group styling */
        .btn-group .btn {
            border-radius: 0;
        }
        .btn-group .btn:first-child {
            border-top-left-radius: 0.375rem;
            border-bottom-left-radius: 0.375rem;
        }
        .btn-group .btn:last-child {
            border-top-right-radius: 0.375rem;
            border-bottom-right-radius: 0.375rem;
        }

        /* Enhanced button hover effects */
        .btn-outline-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
        }
        .btn-outline-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        }
        .btn-outline-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }

        /* Toast animations */
        .toast {
            animation: slideInFromTop 0.3s ease;
        }
        @keyframes slideInFromTop {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Loading state for delete button */
        .btn-outline-danger.loading {
            pointer-events: none;
            opacity: 0.6;
        }
        .btn-outline-danger.loading::after {
            content: '';
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 2px solid #dc3545;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
            margin-left: 8px;
        }
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Enhanced card animations */
        .card {
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

    </style>