<?php
require_once __DIR__ . '/../config.php';

$title = 'Farms';
$sub_title = 'Management';
ob_start();

$conn = db_connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['token']) || !verify_token($_POST['token'])) {
        die('Invalid CSRF token');
    }
}

if (isset($_POST['add_farm'])) {
    $farm_name = $_POST['farm_name'];
    $owner_id = intval($_POST['owner_id']);
    $location = $_POST['location'];
    
    if (empty($farm_name) || empty($owner_id)) {
        $_SESSION['error'] = 'Farm name and owner are required';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    $stmt = $conn->prepare('INSERT INTO farms (farm_name, owner_id, location) VALUES (?, ?, ?)');
    $stmt->bind_param('sis', $farm_name, $owner_id, $location);
    if (!$stmt->execute()) {
        $_SESSION['error'] = 'Error: ' . $conn->error;
    } else {
        $_SESSION['success'] = 'Farm added successfully';
    }
    header('Location: ' . $_SERVER['PHP_SELF']); 
    exit;
}

if (isset($_POST['edit_farm'])) {
    $farm_id = intval($_POST['farm_id']);
    $farm_name = $_POST['farm_name'];
    $owner_id = intval($_POST['owner_id']);
    $location = $_POST['location'];
    
    if (empty($farm_name) || empty($owner_id)) {
        $_SESSION['error'] = 'Farm name and owner are required';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    $stmt = $conn->prepare('UPDATE farms SET farm_name = ?, owner_id = ?, location = ? WHERE farm_id = ?');
    $stmt->bind_param('sisi', $farm_name, $owner_id, $location, $farm_id);
    if (!$stmt->execute()) {
        $_SESSION['error'] = 'Error updating farm: ' . $conn->error;
    } else {
        $_SESSION['success'] = 'Farm updated successfully';
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['delete_farm'])) {
    $farm_id = intval($_GET['delete_farm']);
    $stmt = $conn->prepare('DELETE FROM farms WHERE farm_id = ?');
    $stmt->bind_param('i', $farm_id);
    if (!$stmt->execute()) {
        $_SESSION['error'] = 'Error deleting farm: ' . $conn->error;
    } else {
        $_SESSION['success'] = 'Farm deleted successfully';
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$sql = 'SELECT f.owner_id, f.farm_id, f.farm_name, u.fullname AS owner_name, f.location, f.created_at 
        FROM farms f 
        JOIN users u ON f.owner_id = u.id 
        ORDER BY f.created_at DESC';
$result = $conn->query($sql);
if (!$result) die('Error: ' . $conn->error);

$users = $conn->query('SELECT id, fullname FROM users');
if (!$users) die('Error: ' . $conn->error);
$usersArray = $users->fetch_all(MYSQLI_ASSOC);
?>
<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<div class="row mb-3">
    <div class="col">
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addFarmModal">
            <i class="fas fa-plus me-1"></i> Add Farm
        </button>
    </div>
</div>
<div class="row">
    <div class="col">
        <table id="farmsTable" class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Owner</th>
                    <th>Location</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($f = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $f['farm_id'] ?></td>
                    <td><?= special_chars($f['farm_name']) ?></td>
                    <td><?= special_chars($f['owner_name']) ?></td>
                    <td><?= special_chars($f['location']) ?></td>
                    <td><?= $f['created_at'] ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary me-1" data-bs-toggle="modal" data-bs-target="#editFarmModal" 
                                data-id="<?= $f['farm_id'] ?>" 
                                data-name="<?= special_chars($f['farm_name']) ?>"
                                data-owner="<?= $f['owner_id'] ?>"
                                data-location="<?= special_chars($f['location']) ?>">
                            <i class="mdi mdi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger delete-farm" 
                                data-id="<?= $f['farm_id'] ?>"
                                data-name="<?= special_chars($f['farm_name']) ?>">
                            <i class="mdi mdi-delete"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Farm Modal -->
<div class="modal fade" id="editFarmModal" tabindex="-1" aria-labelledby="editFarmModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="token" value="<?= special_chars($_SESSION['token']); ?>">
        <input type="hidden" name="farm_id" id="edit_farm_id">
        <div class="modal-header">
          <h5 class="modal-title" id="editFarmModalLabel">Edit Farm</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Farm Name <span class="text-danger">*</span></label>
            <input type="text" name="farm_name" id="edit_farm_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Owner <span class="text-danger">*</span></label>
            <select name="owner_id" id="edit_owner_id" class="form-select" required>
              <option value="">-- Select Owner --</option>
              <?php foreach ($usersArray as $u): ?>
                <option value="<?= $u['id'] ?>"><?= special_chars($u['fullname']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Location</label>
            <input type="text" name="location" id="edit_location" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="edit_farm" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Add Farm Modal -->
<div class="modal fade" id="addFarmModal" tabindex="-1" aria-labelledby="addFarmModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="token" value="<?= special_chars($_SESSION['token']); ?>">
        <div class="modal-header">
          <h5 class="modal-title" id="addFarmModalLabel">Add New Farm</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Farm Name <span class="text-danger">*</span></label>
            <input type="text" name="farm_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Owner <span class="text-danger">*</span></label>
            <select name="owner_id" class="form-select" required>
              <option value="">-- Select Owner --</option>
              <?php foreach ($usersArray as $u): ?>
                <option value="<?= $u['id'] ?>"><?= special_chars($u['fullname']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="add_farm" class="btn btn-primary">Add Farm</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
    $(document).ready(function() {
        $(document).on('click', '.delete-farm', function() {
            var farmId = $(this).data('id');
            var farmName = $(this).data('name');
            
            Swal.fire({
                title: 'Delete Farm',
                text: 'Are you sure you want to delete ' + farmName + '?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '?delete_farm=' + farmId;
                }
            });
        });

        // Handle edit modal data
        $('#editFarmModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var farmId = button.data('id');
            var farmName = button.data('name');
            var ownerId = button.data('owner');
            var location = button.data('location');
            
            var modal = $(this);
            modal.find('#edit_farm_id').val(farmId);
            modal.find('#edit_farm_name').val(farmName);
            modal.find('#edit_owner_id').val(ownerId);
            modal.find('#edit_location').val(location || '');
        });
        
        // Initialize DataTable
        $('#farmsTable').DataTable({
            responsive: true,
            columnDefs: [
                { responsivePriority: 1, targets: 0 },
                { responsivePriority: 2, targets: 1 },
                { responsivePriority: 3, targets: 2 },
                { responsivePriority: 4, targets: 4 },
                { responsivePriority: 5, targets: 5 },
                { responsivePriority: 6, targets: 3 }
            ]
        });
    });
</script>

<?php
$conn->close();

$push_css = [
    'libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css',
    'libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css',
];
$push_js = [
    'libs/sweetalert2/sweetalert2.all.min.js',
    'libs/datatables.net/js/jquery.dataTables.min.js',
    'libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js',
    'libs/datatables.net-responsive/js/dataTables.responsive.min.js',
    'libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js',
];
$content = ob_get_clean();
include layouts('admin.main');