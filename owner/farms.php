<?php
require_once __DIR__ . '/../config.php';

$title = 'Farm Management';
$sub_title = 'Manage Your Poultry Farms';
ob_start();

$conn = db_connect();
$owner_id = $user['id'];
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Add new farm
        if ($_POST['action'] === 'add' && isset($_POST['farm_name'])) {
            $farm_name = trim($_POST['farm_name']);
            $location = trim($_POST['location'] ?? '');
            $barangay = trim($_POST['barangay'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $province = trim($_POST['province'] ?? '');
            
            if (!empty($farm_name)) {
                $stmt = $conn->prepare("INSERT INTO farms (farm_name, owner_id, location, barangay, city, province) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sissss", $farm_name, $owner_id, $location, $barangay, $city, $province);
                
                if ($stmt->execute()) {
                    $message = "Farm '$farm_name' added successfully!";
                    
                    // If this is the first farm, set it as selected
                    if (!isset($_SESSION['selected_farm_id'])) {
                        $_SESSION['selected_farm_id'] = $conn->insert_id;
                    }
                } else {
                    $error = "Error adding farm: " . $conn->error;
                }
            } else {
                $error = "Farm name is required!";
            }
        }
        
        // Update farm
        else if ($_POST['action'] === 'update' && isset($_POST['farm_id']) && isset($_POST['farm_name'])) {
            $farm_id = intval($_POST['farm_id']);
            $farm_name = trim($_POST['farm_name']);
            $location = trim($_POST['location'] ?? '');
            $barangay = trim($_POST['barangay'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $province = trim($_POST['province'] ?? '');
            
            if (!empty($farm_name)) {
                // Verify farm belongs to this owner
                $check = $conn->prepare("SELECT farm_id FROM farms WHERE farm_id = ? AND owner_id = ?");
                $check->bind_param("ii", $farm_id, $owner_id);
                $check->execute();
                $result = $check->get_result();
                
                if ($result->num_rows > 0) {
                    $stmt = $conn->prepare("UPDATE farms SET farm_name = ?, location = ?, barangay = ?, city = ?, province = ? WHERE farm_id = ?");
                    $stmt->bind_param("sssssi", $farm_name, $location, $barangay, $city, $province, $farm_id);
                    
                    if ($stmt->execute()) {
                        $message = "Farm updated successfully!";
                    } else {
                        $error = "Error updating farm: " . $conn->error;
                    }
                } else {
                    $error = "You don't have permission to update this farm!";
                }
            } else {
                $error = "Farm name is required!";
            }
        }
        
        // Delete farm
        else if ($_POST['action'] === 'delete' && isset($_POST['farm_id'])) {
            $farm_id = intval($_POST['farm_id']);
            
            // Verify farm belongs to this owner
            $check = $conn->prepare("SELECT farm_id FROM farms WHERE farm_id = ? AND owner_id = ?");
            $check->bind_param("ii", $farm_id, $owner_id);
            $check->execute();
            $result = $check->get_result();
            
            if ($result->num_rows > 0) {
                // Check if this is the only farm
                $count = $conn->prepare("SELECT COUNT(*) as count FROM farms WHERE owner_id = ?");
                $count->bind_param("i", $owner_id);
                $count->execute();
                $count_result = $count->get_result()->fetch_assoc();
                
                if ($count_result['count'] <= 1) {
                    $error = "You cannot delete your only farm. Please add another farm first.";
                } else {
                    $stmt = $conn->prepare("DELETE FROM farms WHERE farm_id = ?");
                    $stmt->bind_param("i", $farm_id);
                    
                    if ($stmt->execute()) {
                        $message = "Farm deleted successfully!";
                        
                        // If the deleted farm was selected, select another farm
                        if (isset($_SESSION['selected_farm_id']) && $_SESSION['selected_farm_id'] == $farm_id) {
                            $new_farm = $conn->prepare("SELECT farm_id FROM farms WHERE owner_id = ? LIMIT 1");
                            $new_farm->bind_param("i", $owner_id);
                            $new_farm->execute();
                            $new_farm_result = $new_farm->get_result();
                            
                            if ($new_farm_result->num_rows > 0) {
                                $_SESSION['selected_farm_id'] = $new_farm_result->fetch_assoc()['farm_id'];
                            } else {
                                unset($_SESSION['selected_farm_id']);
                            }
                        }
                    } else {
                        $error = "Error deleting farm: " . $conn->error;
                    }
                }
            } else {
                $error = "You don't have permission to delete this farm!";
            }
        }
    }
}

// Get all farms for this owner
$farms = [];
$farm_result = $conn->prepare("SELECT * FROM farms WHERE owner_id = ? ORDER BY farm_name");
$farm_result->bind_param("i", $owner_id);
$farm_result->execute();
$result = $farm_result->get_result();
if ($result) {
    $farms = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!-- Success/Error Messages -->
<?php if (!empty($message)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= special_chars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= special_chars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="row">
    <!-- Farm List -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Your Farms</h4>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFarmModal">
                    <i class="fe-plus"></i> Add New Farm
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($farms)): ?>
                    <div class="text-center py-5">
                        <i class="fe-home font-24 text-muted mb-2 d-block"></i>
                        <h4>No Farms Found</h4>
                        <p class="text-muted">You haven't added any farms yet. Click the "Add New Farm" button to get started.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-centered table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Farm Name</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($farms as $farm): ?>
                                    <tr>
                                        <td>
                                            <h5 class="font-14 my-1"><?= special_chars($farm['farm_name']) ?></h5>
                                            <span class="text-muted font-13">ID: <?= $farm['farm_id'] ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                            $location_parts = [];
                                            if (!empty($farm['barangay'])) $location_parts[] = $farm['barangay'];
                                            if (!empty($farm['city'])) $location_parts[] = $farm['city'];
                                            if (!empty($farm['province'])) $location_parts[] = $farm['province'];
                                            echo !empty($location_parts) ? special_chars(implode(', ', $location_parts)) : '<span class="text-muted">Not specified</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (isset($_SESSION['selected_farm_id']) && $_SESSION['selected_farm_id'] == $farm['farm_id']): ?>
                                                <span class="badge bg-success">Current</span>
                                            <?php else: ?>
                                                <a href="<?= view('owner.switch-farm') ?>?farm_id=<?= $farm['farm_id'] ?>" class="badge bg-light text-dark">Switch to</a>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-info edit-farm-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editFarmModal"
                                                data-farm-id="<?= $farm['farm_id'] ?>"
                                                data-farm-name="<?= special_chars($farm['farm_name']) ?>"
                                                data-location="<?= special_chars($farm['location'] ?? '') ?>"
                                                data-barangay="<?= special_chars($farm['barangay'] ?? '') ?>"
                                                data-city="<?= special_chars($farm['city'] ?? '') ?>"
                                                data-province="<?= special_chars($farm['province'] ?? '') ?>"
                                            >
                                                <i class="fe-edit"></i>
                                            </button>
                                            <?php if (count($farms) > 1): ?>
                                                <button type="button" class="btn btn-sm btn-danger delete-farm-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteFarmModal"
                                                    data-farm-id="<?= $farm['farm_id'] ?>"
                                                    data-farm-name="<?= special_chars($farm['farm_name']) ?>"
                                                >
                                                    <i class="fe-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Farm Management Tips -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Farm Management Tips</h4>
            </div>
            <div class="card-body">
                <div class="d-flex mb-3">
                    <div class="flex-shrink-0">
                        <i class="fe-info text-info font-24 me-1"></i>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <h5 class="mt-0">Multiple Farms</h5>
                        <p class="text-muted mb-0">You can manage multiple farms from a single account. Use the farm selector in the top navigation to switch between farms.</p>
                    </div>
                </div>
                
                <div class="d-flex mb-3">
                    <div class="flex-shrink-0">
                        <i class="fe-bar-chart-2 text-success font-24 me-1"></i>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <h5 class="mt-0">Farm-Specific Data</h5>
                        <p class="text-muted mb-0">All data including inventory, sales, and analytics are tracked separately for each farm.</p>
                    </div>
                </div>
                
                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <i class="fe-users text-warning font-24 me-1"></i>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <h5 class="mt-0">Employee Assignment</h5>
                        <p class="text-muted mb-0">Employees can be assigned to specific farms. Manage employee assignments from the Employees page.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Farm Modal -->
<div class="modal fade" id="addFarmModal" tabindex="-1" aria-labelledby="addFarmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title" id="addFarmModalLabel">Add New Farm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="farm_name" class="form-label">Farm Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="farm_name" name="farm_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label">Location Details</label>
                        <input type="text" class="form-control" id="location" name="location" placeholder="Street address, landmark, etc.">
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="barangay" class="form-label">Barangay</label>
                            <input type="text" class="form-control" id="barangay" name="barangay">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="city" class="form-label">City/Municipality</label>
                            <input type="text" class="form-control" id="city" name="city">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="province" class="form-label">Province</label>
                            <input type="text" class="form-control" id="province" name="province">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Farm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Farm Modal -->
<div class="modal fade" id="editFarmModal" tabindex="-1" aria-labelledby="editFarmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="farm_id" id="edit_farm_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editFarmModalLabel">Edit Farm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_farm_name" class="form-label">Farm Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_farm_name" name="farm_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_location" class="form-label">Location Details</label>
                        <input type="text" class="form-control" id="edit_location" name="location" placeholder="Street address, landmark, etc.">
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_barangay" class="form-label">Barangay</label>
                            <input type="text" class="form-control" id="edit_barangay" name="barangay">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_city" class="form-label">City/Municipality</label>
                            <input type="text" class="form-control" id="edit_city" name="city">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_province" class="form-label">Province</label>
                            <input type="text" class="form-control" id="edit_province" name="province">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Farm Modal -->
<div class="modal fade" id="deleteFarmModal" tabindex="-1" aria-labelledby="deleteFarmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="farm_id" id="delete_farm_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteFarmModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the farm <strong id="delete_farm_name"></strong>?</p>
                    <div class="alert alert-warning">
                        <i class="fe-alert-triangle me-1"></i>
                        This action cannot be undone. All data associated with this farm will be permanently deleted.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Farm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Initialize modal data for edit
document.addEventListener('DOMContentLoaded', function() {
    const editButtons = document.querySelectorAll('.edit-farm-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const farmId = this.getAttribute('data-farm-id');
            const farmName = this.getAttribute('data-farm-name');
            const location = this.getAttribute('data-location');
            const barangay = this.getAttribute('data-barangay');
            const city = this.getAttribute('data-city');
            const province = this.getAttribute('data-province');
            
            document.getElementById('edit_farm_id').value = farmId;
            document.getElementById('edit_farm_name').value = farmName;
            document.getElementById('edit_location').value = location;
            document.getElementById('edit_barangay').value = barangay;
            document.getElementById('edit_city').value = city;
            document.getElementById('edit_province').value = province;
        });
    });
    
    const deleteButtons = document.querySelectorAll('.delete-farm-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const farmId = this.getAttribute('data-farm-id');
            const farmName = this.getAttribute('data-farm-name');
            
            document.getElementById('delete_farm_id').value = farmId;
            document.getElementById('delete_farm_name').textContent = farmName;
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include layouts('owner.main');
?>