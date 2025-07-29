<?php
require_once __DIR__ . '/../config.php';

$owner_id = $_SESSION['id'];
$title = 'Employee Management';
$sub_title = 'Manage Farmers and Staff';

ob_start();
$conn = db_connect();

// Get farm_id from session
$farm_id = $_SESSION['selected_farm_id'] ?? null;
if (!$farm_id) {
    // Show error message if no farm is selected
    echo '<div class="alert alert-danger">No farm selected. Please select a farm first.</div>';
    $content = ob_get_clean();
    require_once layouts('owner.main');
    exit;
}

// Fetch all farms owned by the current owner (for the add employee form)
$farms = [];
$farmsStmt = $conn->prepare("SELECT farm_id, farm_name FROM farms WHERE owner_id = ?");
$farmsStmt->bind_param('i', $owner_id);
$farmsStmt->execute();
$farmsResult = $farmsStmt->get_result();
while ($farm = $farmsResult->fetch_assoc()) {
    $farms[$farm['farm_id']] = $farm['farm_name'];
}

// Get current farm name
$current_farm_name = $farms[$farm_id] ?? 'Unknown Farm';

// Fetch employees for the selected farm only
$employees = [];
$employeesStmt = $conn->prepare(
    "SELECT e.*, u.fullname, u.email, t.type_name, f.farm_name
     FROM employees e
     JOIN users u ON e.user_id = u.id
     JOIN employee_types t ON e.type_id = t.type_id
     JOIN farms f ON e.farm_id = f.farm_id
     WHERE e.farm_id = ?
     ORDER BY e.status, u.fullname"
);
$employeesStmt->bind_param('i', $farm_id);
$employeesStmt->execute();
$employeesResult = $employeesStmt->get_result();
$employees = $employeesResult->fetch_all(MYSQLI_ASSOC);

// Fetch employee types for forms
$employeeTypes = [];
$typesStmt = $conn->prepare("SELECT * FROM employee_types");
$typesStmt->execute();
$typesResult = $typesStmt->get_result();
while ($type = $typesResult->fetch_assoc()) {
    $employeeTypes[$type['type_id']] = $type['type_name'];
}

$conn->close();
?>

<?php if (isset($_SESSION['success']) || isset($_SESSION['error'])): ?>
    <div class="alert alert-<?= isset($_SESSION['success']) ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
        <?= $_SESSION['success'] ?? $_SESSION['error'] ?? '' ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php unset($_SESSION['success']); ?>
<?php unset($_SESSION['error']); ?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Employee Directory</h6>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
            <i class="fas fa-plus"></i> Add Employee
        </button>
    </div>
    <div class="card-body">
        <table class="table table-bordered" id="employeesTable" width="100%" cellspacing="0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Farm</th>
                    <th>Position</th>
                    <th>Hire Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $employee): ?>
                    <tr>
                        <td><?= special_chars($employee['fullname']) ?></td>
                        <td><?= special_chars($employee['email']) ?></td>
                        <td><?= special_chars($employee['farm_name']) ?></td>
                        <td><?= special_chars($employee['type_name']) ?></td>
                        <td><?= !empty($employee['hire_date']) ? date('M d, Y', strtotime($employee['hire_date'])) : 'N/A' ?></td>
                        <td>
                            <span class="badge bg-<?=
                                                    $employee['status'] == 'active' ? 'success' : ($employee['status'] == 'on_leave' ? 'warning' : 'danger')
                                                    ?>">
                                <?= ucfirst(str_replace('_', ' ', $employee['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= view('owner.employee-detail') ?>?id=<?= $employee['employee_id'] ?>"
                                class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button class="btn btn-primary btn-sm edit-employee"
                                data-id="<?= $employee['employee_id'] ?>"
                                data-bs-toggle="modal" data-bs-target="#editEmployeeModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="<?= view('owner.api.employee.actions') ?>" method="POST" class="d-inline delete-employee">
                                <input type="hidden" name="token" value="<?= special_chars($_SESSION['token']) ?>">
                                <input type="hidden" name="employee_id" value="<?= $employee['employee_id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-danger btn-sm delete-employee">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($employees)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No employees found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" role="dialog" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEmployeeModalLabel">Add New Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= view('owner.api.employee.actions') ?>" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="token" value="<?= special_chars($_SESSION['token']) ?>">
                    <input type="hidden" name="action" value="add">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="addFullname">Full Name</label>
                                <input type="text" class="form-control" id="addFullname" name="fullname" placeholder="Enter Full Name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="addEmail">Email Address</label>
                                <input type="email" class="form-control" id="addEmail" name="email" placeholder="Enter Email Address" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="addPassword">Password</label>
                        <input type="password" class="form-control" id="addPassword" name="password" placeholder="Enter Password" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="addFarm">Farm</label>
                                <select class="form-control" id="addFarm" name="farm_id" required>
                                    <option value="">Select Farm</option>
                                    <?php foreach ($farms as $id => $name): ?>
                                        <option value="<?= $id ?>"><?= special_chars($name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="addPosition">Position</label>
                                <select class="form-control" id="addPosition" name="type_id" required>
                                    <option value="">Select Position</option>
                                    <?php foreach ($employeeTypes as $id => $name): ?>
                                        <option value="<?= $id ?>"><?= special_chars($name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="addHireDate">Hire Date</label>
                        <input type="date" class="form-control" id="addHireDate" name="hire_date"
                            value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group mb-3">
                        <label for="addSalary">Salary (optional)</label>
                        <input type="number" class="form-control" id="addSalary" name="salary"
                            step="0.01" min="0" placeholder="Enter Salary">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Employee Modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1" role="dialog" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editEmployeeModalLabel">Edit Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= view('owner.api.employee.actions') ?>" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="token" value="<?= special_chars($_SESSION['token']) ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="employee_id" id="editEmployeeId">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="editPosition">Position</label>
                                <select class="form-control" id="editPosition" name="type_id" required>
                                    <?php foreach ($employeeTypes as $id => $name): ?>
                                        <option value="<?= $id ?>"><?= special_chars($name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="editFarm">Farm</label>
                                <select class="form-control" id="editFarm" name="farm_id" required>
                                    <?php foreach ($farms as $id => $name): ?>
                                        <option value="<?= $id ?>"><?= special_chars($name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="editStatus">Status</label>
                        <select class="form-control" id="editStatus" name="status" required>
                            <option value="active">Active</option>
                            <option value="on_leave">On Leave</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="editSalary">Salary</label>
                        <input type="number" class="form-control" id="editSalary" name="salary"
                            step="0.01" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle edit buttons
        document.querySelectorAll('.edit-employee').forEach(button => {
            button.addEventListener('click', function() {
                const employeeId = this.getAttribute('data-id');

                // Fetch employee data (in a real app, this would be an AJAX call)
                const employee = <?= json_encode($employees) ?>.find(e => e.employee_id == employeeId);

                if (employee) {
                    document.getElementById('editEmployeeId').value = employee.employee_id;
                    document.getElementById('editPosition').value = employee.type_id;
                    document.getElementById('editFarm').value = employee.farm_id;
                    document.getElementById('editStatus').value = employee.status;
                    document.getElementById('editSalary').value = employee.salary || '';
                }
            });
        });

        // Handle delete buttons
        $('.delete-employee').on('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: 'You will not be able to recover this employee!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = $(this).closest('form');
                    form.submit();
                }
            });
        });

        // Remove mismatched rows (e.g., default no-data row)
        $('#employeesTable tbody tr').filter(function() {
            return $(this).find('td').length !== $('#employeesTable thead th').length;
        }).remove();

        // Initialize DataTable
        $('#employeesTable').DataTable({
            responsive: true,
            columnDefs: [{
                orderable: false,
                targets: [6]
            }],
            language: {
                emptyTable: 'No employees found'
            }
        });
    });
</script>

<?php
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
include layouts('owner.main');
?>