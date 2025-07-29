// Disable auto discovery to prevent Dropzone from automatically attaching to elements
Dropzone.autoDiscover = false;

$(document).ready(function () {

    // Initialize Dropzone for image uploads only when modal is shown
    let trayImageDropzone = null;
    let dropzoneInitialized = false;

    $('#publishTrayModal').on('shown.bs.modal', function () {
        // Only initialize once or after destroy
        if (dropzoneInitialized && trayImageDropzone) {
            // Just clear files instead of destroying
            trayImageDropzone.removeAllFiles(true);
            return;
        }

        // Initialize new Dropzone instance
        trayImageDropzone = new Dropzone("#trayImageDropzone", {
            url: "api/auto-egg-tray/upload-image.php",
            paramName: "image",
            maxFiles: 1,
            maxFilesize: 5, // MB
            acceptedFiles: "image/*",
            addRemoveLinks: true,
            init: function () {
                this.on("success", function (file, response) {
                    if (response.success) {
                        $('#publishTrayModal').data('image', response.imageUrl);
                    } else {
                        toastr.error(response.message || "Image upload failed");
                        this.removeFile(file);
                    }
                });
                this.on("removedfile", function () {
                    $('#publishTrayModal').data('image', null);
                });
                // Set flag to indicate Dropzone has been initialized
                dropzoneInitialized = true;
            }
        });
    });

    // Handle modal close event
    $('#publishTrayModal').on('hidden.bs.modal', function () {
        if (trayImageDropzone) {
            trayImageDropzone.removeAllFiles(true);
        }
    });

    // Load initial data
    loadDashboardStats();
    loadNotifications();
    loadPendingTrays();
    loadActiveTrays();
    loadSoldTrays();
    loadExpiredTrays();

    setInterval(autoCheckForNewTrays, 60000);

    // Check for new trays button
    $('#checkNewTraysBtn').click(function () {
        checkForNewTrays();
    });

    // Save settings button
    $('#saveSettingsBtn').click(saveTraySettings);

    // Publish tray handler
    $('#confirmPublishBtn').click(function () {
        const trayId = $('#publishTrayId').val();
        const price = $('#trayPrice').val();
        const image = $('#publishTrayModal').data('image');
        publishTray(trayId, price, image);
    });

    // Edit tray button handler
    $(document).on('click', '.edit-tray-btn', function () {
        const trayId = $(this).data('tray-id');
        openEditTrayModal(trayId);
    });

    // Publish tray button handler
    $(document).on('click', '.publish-tray-btn', function () {
        const trayId = $(this).data('tray-id');
        $('#publishTrayId').val(trayId);
        $('#trayPrice').val(''); // Reset price
        $('#publishTrayModal').data('image', null);
        if (trayImageDropzone) {
            trayImageDropzone.removeAllFiles();
        }
    });

    // Mark all notifications as read
    $('#markAllReadBtn').click(function () {
        markAllNotificationsRead();
    });
});

// ======================== CORE FUNCTIONS ========================

async function loadDashboardStats() {
    try {
        const response = await fetch(`api/auto-egg-tray/tray-stats.php?farm_id=${farmId}`);
        const data = await response.json();

        $('#pendingTraysCount').text(data.pending);
        $('#listedTraysCount').text(data.published);
        $('#soldTraysCount').text(data.sold);
        $('#expiringTraysCount').text(data.expiring);

        // Update tab counts
        $('#pendingTabCount').text(data.pending);
        $('#activeTabCount').text(data.published);
        $('#soldTabCount').text(data.sold);
        $('#expiredTabCount').text(data.expired || 0);
    } catch (error) {
        showError('Failed to load dashboard stats');
    }
}

async function loadTrayProgress() {
    try {
        const response = await fetch(`api/auto-egg-tray/tray-progress.php?farm_id=${farmId}`);
        const data = await response.json();

        let html = '';
        if (data.length > 0) {
            data.forEach(size => {
                const progressPercent = (size.egg_count / 30) * 100;
                html += `
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-medium">${size.size} Eggs</span>
                        <span>${size.egg_count}/30 (${Math.round(progressPercent)}%)</span>
                    </div>
                    <div class="progress tray-progress">
                        <div class="progress-bar" role="progressbar" 
                             style="width: ${progressPercent}%" 
                             aria-valuenow="${progressPercent}" 
                             aria-valuemin="0" 
                             aria-valuemax="100"></div>
                    </div>
                </div>`;
            });
        } else {
            html = '<div class="alert alert-info">No eggs detected. Scan some eggs to get started.</div>';
        }

        $('#trayProgressContainer').html(html);
    } catch (error) {
        showError('Failed to load tray progress');
    }
}

async function loadNotifications() {
    try {
        const response = await fetch(`api/auto-egg-tray/notifications.php?farm_id=${farmId}`);
        const data = await response.json();

        let html = '';
        if (data.length > 0) {
            data.forEach(notification => {
                const timeAgo = timeSince(new Date(notification.created_at));
                html += `
                <div class="notification-item ${notification.is_read ? '' : 'notification-unread'} mb-2 p-2">
                    <div class="d-flex justify-content-between">
                        <strong>${notification.title}</strong>
                        <small class="text-muted">${timeAgo}</small>
                    </div>
                    <p class="mb-0">${notification.message}</p>
                </div>`;
            });
        } else {
            html = '<div class="alert alert-info">No notifications</div>';
        }

        $('#notificationsContainer').html(html);
    } catch (error) {
        showError('Failed to load notifications');
    }
}

async function markAllNotificationsRead() {
    try {
        const response = await fetch(`api/auto-egg-tray/notifications.php?farm_id=${farmId}&mark_read=all`);
        if (response.ok) {
            loadNotifications();
            showSuccess('All notifications marked as read');
        }
    } catch (error) {
        showError('Failed to mark notifications as read');
    }
}

async function loadPendingTrays() {
    try {
        const response = await fetch(`api/auto-egg-tray/trays.php?farm_id=${farmId}&status=pending`);
        const data = await response.json();

        let html = '';
        if (data.length > 0) {
            data.forEach(tray => {
                html += `
                <div class="col-md-6 mb-3">
                    <div class="card tray-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title">${tray.size} Eggs</h5>
                                    <p class="card-text">
                                        <span class="badge bg-primary">${tray.egg_count}/30 eggs</span>
                                        <span class="badge bg-info">${tray.stock_count} in stock</span>
                                    </p>
                                </div>
                                <span class="egg-size-badge badge bg-${getSizeColor(tray.size)}">
                                    ${tray.size}
                                </span>
                            </div>
                            
                            <div class="progress mt-2 tray-progress">
                                <div class="progress-bar" 
                                    style="width: ${Math.min(100, (tray.egg_count / 30) * 100)}%"></div>
                            </div>
                            
                            <div class="mt-3 d-flex justify-content-end">
                                <button class="btn btn-sm btn-primary edit-tray-btn me-2" 
                                        data-tray-id="${tray.tray_id}">
                                    <i class="mdi mdi-pencil"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-success publish-tray-btn" 
                                        data-tray-id="${tray.tray_id}"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#publishTrayModal">
                                    <i class="mdi mdi-cloud-upload"></i> Publish
                                </button>
                            </div>
                        </div>
                    </div>
                </div>`;
            });
        } else {
            html = '<div class="alert alert-info mt-3">No pending trays</div>';
        }

        $('#pendingTraysContainer').html(html);
    } catch (error) {
        showError('Failed to load pending trays');
    }
}

async function loadActiveTrays() {
    try {
        const response = await fetch(`api/auto-egg-tray/trays.php?farm_id=${farmId}&status=published`);
        const data = await response.json();

        let html = '';
        if (data.length > 0) {
            html = '<div class="row">';
            data.forEach(tray => {
                const freshness = getFreshness(tray.created_at);
                html += `
                <div class="col-md-4 mb-3">
                    <div class="card tray-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title">${tray.size} Eggs</h5>
                                    <p class="card-text">
                                        <span class="badge bg-success">Active</span>
                                        <span class="badge bg-${getSizeColor(tray.size)}">${tray.size}</span>
                                    </p>
                                </div>
                                <span class="text-${getFreshnessColor(freshness)}">
                                    <i class="mdi mdi-circle"></i> ${freshness}
                                </span>
                            </div>
                            
                            <div class="my-3">
                                <img src="${tray.image_url || defaultImage}" 
                                     alt="Tray Image" class="img-fluid rounded">
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <h4>₱${tray.price ? parseFloat(tray.price).toFixed(2) : '0.00'}</h4>
                                <button class="btn btn-sm btn-outline-danger remove-tray-btn" data-tray-id="${tray.tray_id}">
                                    <i class="mdi mdi-trash-can"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>`;
            });
            html += '</div>';
        } else {
            html = '<div class="alert alert-info">No active listings</div>';
        }

        $('#activeTraysContainer').html(html);
    } catch (error) {
        showError('Failed to load active trays');
    }
}

async function loadSoldTrays() {
    try {
        const response = await fetch(`api/auto-egg-tray/trays.php?farm_id=${farmId}&status=sold`);
        const data = await response.json();

        let html = '';
        if (data.length > 0) {
            html = '<div class="list-group">';
            data.forEach(tray => {
                html += `
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">${tray.size} Eggs</h5>
                        <small>${timeSince(new Date(tray.sold_at))} ago</small>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <div>
                            <span class="badge bg-info">Sold</span>
                            <span class="badge bg-${getSizeColor(tray.size)}">${tray.size}</span>
                        </div>
                        <h4 class="mb-0">₱${tray.price ? parseFloat(tray.price).toFixed(2) : '0.00'}</h4>
                    </div>
                </div>`;
            });
            html += '</div>';
        } else {
            html = '<div class="alert alert-info">No sold trays</div>';
        }

        $('#soldTraysContainer').html(html);
    } catch (error) {
        showError('Failed to load sold trays');
    }
}

async function loadExpiredTrays() {
    try {
        const response = await fetch(`api/auto-egg-tray/trays.php?farm_id=${farmId}&status=expired`);
        const data = await response.json();

        let html = '';
        if (data.length > 0) {
            html = '<div class="list-group">';
            data.forEach(tray => {
                html += `
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">${tray.size} Eggs</h5>
                        <small>${timeSince(new Date(tray.expired_at))} ago</small>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <div>
                            <span class="badge bg-danger">Expired</span>
                            <span class="badge bg-${getSizeColor(tray.size)}">${tray.size}</span>
                        </div>
                        <button class="btn btn-sm btn-outline-danger remove-tray-btn" data-tray-id="${tray.tray_id}">
                            <i class="mdi mdi-trash-can"></i> Remove
                        </button>
                    </div>
                </div>`;
            });
            html += '</div>';
        } else {
            html = '<div class="alert alert-info">No expired trays</div>';
        }

        $('#expiredTraysContainer').html(html);
    } catch (error) {
        showError('Failed to load expired trays');
    }
}

async function checkForNewTrays() {
    try {
        $('#checkNewTraysBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Checking...');

        const response = await fetch('api/auto-egg-tray/detect-new-trays.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ farm_id: farmId })
        });

        const result = await response.json();
        if (result.success) {
            if (result.new_trays > 0) {
                showSuccess(`Found ${result.new_trays} new trays!`);
            } else {
                showInfo('No new trays found.');
            }
            // Reload all data
            loadDashboardStats();
            loadTrayProgress();
            loadPendingTrays();
            loadActiveTrays();
            loadExpiredTrays();
        } else {
            showError(result.message);
        }
    } catch (error) {
        showError('Failed to check for new trays');
    } finally {
        $('#checkNewTraysBtn').prop('disabled', false).html('<i class="mdi mdi-refresh me-1"></i> Check for New Trays');
    }
}

async function autoCheckForNewTrays() {
    try {
        const response = await fetch('api/auto-egg-tray/detect-new-trays.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ farm_id: farmId })
        });

        const result = await response.json();
        if (result.success) {
            loadDashboardStats();
            loadTrayProgress();
            loadPendingTrays();
            loadActiveTrays();
        }
    } catch (error) {
        showError('Failed to check for new trays');
    }
}

async function publishTray(trayId, price, image) {
    try {
        $('#confirmPublishBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Publishing...');

        const response = await fetch('api/auto-egg-tray/publish-tray.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                tray_id: trayId,
                price: price,
                image_url: image,
                farm_id: farmId
            })
        });

        const result = await response.json();
        if (result.success) {
            showSuccess('Tray published successfully!');

            $('#settingsModal').modal('hide');
            setTimeout(() => {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('overflow', '');
                $('body').css('padding-right', '');
            }, 1000);
            
            // Reload tray data
            loadDashboardStats();
            loadPendingTrays();
            loadActiveTrays();
        } else {
            showError(result.message);
        }
    } catch (error) {
        showError('Failed to publish tray');
    } finally {
        $('#confirmPublishBtn').prop('disabled', false).html('Publish Tray');
    }
}

async function saveTraySettings() {
    try {
        $('#saveSettingsBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        const settings = {};
        $('[id^="price-"]').each(function () {
            // Extract the size from the ID (e.g., "price-Extra-Large" -> "Extra Large")
            const sizeWithHyphens = this.id.replace('price-', '');
            const size = sizeWithHyphens.replace(/-/g, ' ');

            settings[size] = {
                price: parseFloat($(this).val()) || 0,
                auto_publish: $(`#auto-publish-${sizeWithHyphens}`).is(':checked')
            };
        });

        const response = await fetch('api/auto-egg-tray/save-tray-settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                farm_id: farmId,
                settings: settings
            })
        });

        const result = await response.json();
        if (result.success) {
            showSuccess('Settings saved successfully!');

            $('#settingsModal').modal('hide');
            setTimeout(() => {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('overflow', '');
                $('body').css('padding-right', '');
            }, 1000);
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error saving settings:', error);
        showError('Failed to save settings');
    } finally {
        $('#saveSettingsBtn').prop('disabled', false).html('Save Changes');
    }
}

// ======================== HELPER FUNCTIONS ========================

function getSizeColor(size) {
    const colors = {
        'Jumbo': 'danger',
        'Extra Large': 'warning',
        'Large': 'primary',
        'Medium': 'success',
        'Small': 'info',
        'Pullets': 'secondary',
        'Pewee': 'dark'
    };
    return colors[size] || 'primary';
}

function getFreshness(createdDate) {
    const now = new Date();
    const created = new Date(createdDate);
    if (isNaN(created)) return 'Unknown';

    const diffDays = Math.floor((now - created) / (1000 * 60 * 60 * 24));

    if (diffDays <= 7) return 'Fresh';
    if (diffDays <= 14) return 'Medium';
    return 'Expiring Soon';
}

function getFreshnessColor(freshness) {
    switch (freshness) {
        case 'Fresh': return 'success';
        case 'Medium': return 'warning';
        case 'Expiring Soon': return 'danger';
        default: return 'secondary';
    }
}

function showSuccess(message) {
    toastr.success(message, 'Success', {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 5000,
        preventDuplicates: true
    });
}

function showError(message) {
    toastr.error(message, 'Error', {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 5000,
        preventDuplicates: true
    });
}

function timeSince(date) {
    const seconds = Math.floor((new Date() - date) / 1000);
    let interval = seconds / 31536000;

    if (interval > 1) return Math.floor(interval) + " years ago";
    interval = seconds / 2592000;
    if (interval > 1) return Math.floor(interval) + " months ago";
    interval = seconds / 86400;
    if (interval > 1) return Math.floor(interval) + " days ago";
    interval = seconds / 3600;
    if (interval > 1) return Math.floor(interval) + " hours ago";
    interval = seconds / 60;
    if (interval > 1) return Math.floor(interval) + " minutes ago";
    return Math.floor(seconds) + " seconds ago";
}

// Temporary function until we implement edit modal
function openEditTrayModal(trayId) {
    showInfo('Edit functionality coming soon!');
}

function showInfo(message) {
    toastr.info(message, 'Info', {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 5000,
        preventDuplicates: true
    });
}