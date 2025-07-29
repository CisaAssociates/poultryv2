<?php
require_once __DIR__ . '/../config.php';

$title = 'Users';
$sub_title = 'Management';
ob_start();

$conn = db_connect();

// FIX 1: Add session message handling
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">'.$_SESSION['success'].'</div>';
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">'.$_SESSION['error'].'</div>';
    unset($_SESSION['error']);
}

// Handle add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    if (!isset($_POST['token']) || !verify_token($_POST['token'])) {
        $_SESSION['error'] = 'Invalid CSRF token';
        header('Location: ' . $_SERVER['PHP_SELF']); 
        exit;
    }
    
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role_id = intval($_POST['role_id']);
    
    if (empty($fullname) || empty($email) || empty($password) || empty($role_id)) {
        $_SESSION['error'] = 'All fields are required';
        header('Location: ' . $_SERVER['PHP_SELF']); 
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Invalid email format';
        header('Location: ' . $_SERVER['PHP_SELF']); 
        exit;
    }
    
    if (strlen($password) < 8) {
        $_SESSION['error'] = 'Password must be at least 8 characters';
        header('Location: ' . $_SERVER['PHP_SELF']); 
        exit;
    }
    
    $check = $conn->prepare('SELECT id FROM users WHERE email = ?');
    $check->bind_param('s', $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $_SESSION['error'] = 'Email already exists';
        header('Location: ' . $_SERVER['PHP_SELF']); 
        exit;
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO users (fullname, email, password, role_id) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('sssi', $fullname, $email, $hashed_password, $role_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'User added successfully';
    } else {
        $_SESSION['error'] = 'Error: ' . $conn->error;
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']); 
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    if (!isset($_POST['token']) || !verify_token($_POST['token'])) {
        $_SESSION['error'] = 'Invalid CSRF token';
        header('Location: ' . $_SERVER['PHP_SELF']); 
        exit;
    }
    
    $user_id = intval($_POST['user_id']);
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $role_id = intval($_POST['role_id']);
    
    if (empty($fullname) || empty($email) || empty($role_id)) {
        $_SESSION['error'] = 'All fields are required';
        header('Location: ' . $_SERVER['PHP_SELF']); 
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Invalid email format';
        header('Location: ' . $_SERVER['PHP_SELF']); 
        exit;
    }
    
    $check = $conn->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
    $check->bind_param('si', $email, $user_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $_SESSION['error'] = 'Email already exists';
        header('Location: ' . $_SERVER['PHP_SELF']); 
        exit;
    }
    
    $stmt = $conn->prepare('UPDATE users SET fullname = ?, email = ?, role_id = ? WHERE id = ?');
    $stmt->bind_param('ssii', $fullname, $email, $role_id, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'User updated successfully';
    } else {
        $_SESSION['error'] = 'Error: ' . $conn->error;
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']); 
    exit;
}

if (isset($_GET['delete_user'])) {
    if (!isset($_GET['token']) || !verify_token($_GET['token'])) {
        $_SESSION['error'] = 'Invalid CSRF token';
        header('Location: ' . $_SERVER['PHP_SELF']); 
        exit;
    }
    
    $user_id = intval($_GET['delete_user']);
    
    if ($user_id === $_SESSION['user_id']) {
        $_SESSION['error'] = 'You cannot delete your own account';
        header('Location: ' . $_SERVER['PHP_SELF']); 
        exit;
    }
    
    $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'User deleted successfully';
    } else {
        $_SESSION['error'] = 'Error: ' . $conn->error;
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']); 
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    if (!isset($_POST['token']) || !verify_token($_POST['token'])) {
        $_SESSION['error'] = 'Invalid CSRF token';
        header('Location: ' . $_SERVER['PHP_SELF']); 
        exit;
    }
    
    $user_id = intval($_POST['user_id']);
    $password = $_POST['password'];
    
    if (strlen($password) < 8) {
        $_SESSION['error'] = 'Password must be at least 8 characters';
        header('Location: ' . $_SERVER['PHP_SELF']); 
        exit;
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
    $stmt->bind_param('si', $hashed_password, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Password reset successfully';
    } else {
        $_SESSION['error'] = 'Error: ' . $conn->error;
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']); 
    exit;
}

$roles = $conn->query('SELECT role_id, role_name FROM roles');
if (!$roles) die('Error: ' . $conn->error);
$rolesArray = $roles->fetch_all(MYSQLI_ASSOC);

$sql = 'SELECT u.id, u.fullname, u.email, r.role_name, r.role_id, u.created_at 
        FROM users u 
        JOIN roles r ON u.role_id = r.role_id 
        ORDER BY u.created_at DESC';
$result = $conn->query($sql);
if (!$result) die('Error: ' . $conn->error);
?>

<div class="row mb-3">
    <div class="col">
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus me-1"></i> Add User
        </button>
    </div>
</div>
<div class="row">
    <div class="col">
        <table id="usersTable" class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Actions</th> <!-- FIX 6: Add actions column -->
                </tr>
            </thead>
            <tbody>
                <?php while ($u = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= special_chars($u['fullname']) ?></td>
                    <td><?= special_chars($u['email']) ?></td>
                    <td><?= special_chars($u['role_name']) ?></td>
                    <td><?= $u['created_at'] ?></td>
                    <td>
                        <!-- FIX 7: Add action buttons -->
                        <div class="btn-group">
                            <button class="btn btn-sm btn-primary edit-btn" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editUserModal"
                                    data-id="<?= $u['id'] ?>"
                                    data-fullname="<?= special_chars($u['fullname']) ?>"
                                    data-email="<?= special_chars($u['email']) ?>"
                                    data-role-id="<?= $u['role_id'] ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            
                            <button class="btn btn-sm btn-warning reset-btn" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#resetPasswordModal"
                                    data-id="<?= $u['id'] ?>"
                                    data-fullname="<?= special_chars($u['fullname']) ?>">
                                <i class="fas fa-key"></i>
                            </button>
                            
                            <a href="?delete_user=<?= $u['id'] ?>&token=<?= special_chars($_SESSION['token']) ?>" 
                               class="btn btn-sm btn-danger delete-btn"
                               onclick="return confirm('Are you sure you want to delete this user?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="token" value="<?= special_chars($_SESSION['token']) ?>">
        <div class="modal-header">
          <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="fullname" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" required minlength="8">
            <div class="form-text">Password must be at least 8 characters</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Role <span class="text-danger">*</span></label>
            <select name="role_id" class="form-select" required>
              <option value="">-- Select Role --</option>
              <?php foreach ($rolesArray as $r): ?>
                <option value="<?= $r['role_id'] ?>"><?= special_chars($r['role_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="token" value="<?= special_chars($_SESSION['token']) ?>">
        <input type="hidden" name="user_id" id="edit_user_id" value="">
        <div class="modal-header">
          <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="fullname" id="edit_fullname" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" id="edit_email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Role <span class="text-danger">*</span></label>
            <select name="role_id" id="edit_role_id" class="form-select" required>
              <option value="">-- Select Role --</option>
              <?php foreach ($rolesArray as $r): ?>
                <option value="<?= $r['role_id'] ?>"><?= special_chars($r['role_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="edit_user" class="btn btn-primary">Update User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="token" value="<?= special_chars($_SESSION['token']) ?>">
        <input type="hidden" name="user_id" id="reset_user_id" value="">
        <div class="modal-header">
          <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Resetting password for: <strong id="reset_user_name"></strong></p>
          <div class="mb-3">
            <label class="form-label">New Password <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" required minlength="8">
            <div class="form-text">Password must be at least 8 characters</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="reset_password" class="btn btn-warning">Reset Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    // FIX 8: Initialize DataTables with responsive features
    $('#usersTable').DataTable({
        responsive: true,
        columnDefs: [
            { responsivePriority: 1, targets: 1 }, // Name
            { responsivePriority: 2, targets: 2 }, // Email
            { responsivePriority: 3, targets: 3 }, // Role
            { responsivePriority: 4, targets: 5 }, // Actions
            { responsivePriority: 5, targets: 4 }, // Created At
            { responsivePriority: 6, targets: 0 }  // ID
        ]
    });
    
    // FIX 9: Handle edit button clicks
    $('.edit-btn').click(function() {
        const userId = $(this).data('id');
        const fullname = $(this).data('fullname');
        const email = $(this).data('email');
        const roleId = $(this).data('role-id');
        
        $('#edit_user_id').val(userId);
        $('#edit_fullname').val(fullname);
        $('#edit_email').val(email);
        $('#edit_role_id').val(roleId);
    });
    
    // FIX 10: Handle reset password button clicks
    $('.reset-btn').click(function() {
        const userId = $(this).data('id');
        const fullname = $(this).data('fullname');
        
        $('#reset_user_id').val(userId);
        $('#reset_user_name').text(fullname);
    });
    
    // FIX 11: Confirm before deleting
    $('.delete-btn').click(function(e) {
        if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            e.preventDefault();
        }
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
    'libs/datatables.net/js/jquery.dataTables.min.js',
    'libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js',
    'libs/datatables.net-responsive/js/dataTables.responsive.min.js',
    'libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js',
];

$content = ob_get_clean();
include layouts('admin.main');