<?php
require_once __DIR__ . '/../config.php';

$employee_id = intval($_GET['id']);
$owner_id = $_SESSION['id'];
$title = 'Employee Details';
$sub_title = 'View and Manage Employee Information';

ob_start();
$conn = db_connect();

// Fetch employee details
$employee = [];
$employeeStmt = $conn->prepare(
    "SELECT e.*, u.fullname, u.email, u.created_at, t.type_name, f.farm_name
     FROM employees e
     JOIN users u ON e.user_id = u.id
     JOIN employee_types t ON e.type_id = t.type_id
     JOIN farms f ON e.farm_id = f.farm_id
     WHERE e.employee_id = ? AND f.owner_id = ?"
);
$employeeStmt->bind_param('ii', $employee_id, $owner_id);
$employeeStmt->execute();
$employeeResult = $employeeStmt->get_result();
$employee = $employeeResult->fetch_assoc();

if (!$employee) {
    echo "<script>alert('Employee not found'); window.location.href = '" . view('owner.employees') . "';</script>";
    exit;
}

// Fetch assigned tasks
$tasks = [];
$tasksStmt = $conn->prepare(
    "SELECT * FROM tasks 
     WHERE assigned_to = ? 
     ORDER BY due_date ASC, priority DESC"
);
$tasksStmt->bind_param('i', $employee_id);
$tasksStmt->execute();
$tasksResult = $tasksStmt->get_result();
$tasks = $tasksResult->fetch_all(MYSQLI_ASSOC);

// Fetch all tasks for assignment
$allTasks = [];
$allTasksStmt = $conn->prepare(
    "SELECT task_id, title, due_date 
     FROM tasks 
     WHERE farm_id = ? AND (assigned_to IS NULL OR assigned_to = 0)
     ORDER BY due_date ASC"
);
$allTasksStmt->bind_param('i', $employee['farm_id']);
$allTasksStmt->execute();
$allTasksResult = $allTasksStmt->get_result();
$allTasks = $allTasksResult->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<a href="<?= view('owner.employees') ?>" class="btn btn-sm btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Employees</a>

<div class="row">
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Employee Profile</h6>
            </div>
            <div class="card-body text-center">
                <div class="mb-3">
                    <div class="img-profile rounded-circle bg-primary d-flex align-items-center justify-content-center" 
                         style="width: 120px; height: 120px; margin: 0 auto; font-size: 48px; color: white">
                        <?= strtoupper(substr($employee['fullname'], 0, 1)) ?>
                    </div>
                </div>
                
                <h4 class="mb-1"><?= special_chars($employee['fullname']) ?></h4>
                <p class="text-muted mb-3"><?= special_chars($employee['type_name']) ?></p>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <h6 class="text-primary mb-0">Farm</h6>
                            <p class="mb-0"><?= special_chars($employee['farm_name']) ?></p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <h6 class="text-primary mb-0">Status</h6>
                            <p class="mb-0">
                                <span class="badge bg-<?= 
                                    $employee['status'] == 'active' ? 'success' : 
                                    ($employee['status'] == 'on_leave' ? 'warning' : 'danger')
                                ?>">
                                    <?= ucfirst(str_replace('_', ' ', $employee['status'])) ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="border rounded p-3 mb-3">
                    <h6 class="text-primary">Contact Information</h6>
                    <p class="mb-1"><i class="fas fa-envelope mr-2"></i> <?= special_chars($employee['email']) ?></p>
                    <p class="mb-0"><i class="fas fa-calendar-alt mr-2"></i> Joined: <?= !empty($employee['hire_date']) ? date('M d, Y', strtotime($employee['hire_date'])) : 'N/A' ?></p>
                </div>
                
                <?php if ($employee['salary']): ?>
                <div class="border rounded p-3">
                    <h6 class="text-primary">Compensation</h6>
                    <p class="mb-0">$<?= number_format($employee['salary'], 2) ?> per month</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Assigned Tasks</h6>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignTaskModal">
                    <i class="fas fa-plus"></i> Assign Task
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Priority</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td><?= special_chars($task['title']) ?></td>
                                <td>
                                    <span class="badge badge-<?= 
                                        $task['priority'] == 'high' ? 'danger' : 
                                        ($task['priority'] == 'medium' ? 'warning' : 'secondary')
                                    ?>">
                                        <?= ucfirst($task['priority']) ?>
                                    </span>
                                </td>
                                <td><?= !empty($task['due_date']) ? date('M d, Y', strtotime($task['due_date'])) : 'N/A' ?></td>
                                <td>
                                    <span class="badge badge-<?= 
                                        $task['status'] == 'completed' ? 'success' : 
                                        ($task['status'] == 'in_progress' ? 'primary' : 
                                        ($task['status'] == 'overdue' ? 'danger' : 'secondary'))
                                    ?>">
                                        <?= ucfirst(str_replace('_', ' ', $task['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-info btn-sm view-task" 
                                            data-bs-toggle="modal" data-bs-target="#taskDetailModal"
                                            data-id="<?= $task['task_id'] ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-primary btn-sm edit-task" 
                                            data-bs-toggle="modal" data-bs-target="#editTaskModal"
                                            data-id="<?= $task['task_id'] ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($tasks)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No tasks assigned</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Task Detail Modal -->
<div class="modal fade" id="taskDetailModal" tabindex="-1" role="dialog" aria-labelledby="taskDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="taskDetailModalLabel">Task Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="taskDetailContent">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Assign Task Modal -->
<div class="modal fade" id="assignTaskModal" tabindex="-1" role="dialog" aria-labelledby="assignTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignTaskModalLabel">Assign Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= view('owner.task-actions') ?>" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="token" value="<?= special_chars($_SESSION['token']) ?>">
                    <input type="hidden" name="action" value="assign">
                    <input type="hidden" name="assigned_to" value="<?= $employee['employee_id'] ?>">
                    <input type="hidden" name="farm_id" value="<?= $employee['farm_id'] ?>">
                    
                    <div class="form-group mb-3">
                        <label for="taskSelect">Select Task</label>
                        <select class="form-control" id="taskSelect" name="task_id" required>
                            <option value="">Choose a task</option>
                            <?php foreach ($allTasks as $task): ?>
                            <option value="<?= $task['task_id'] ?>">
                                <?= special_chars($task['title']) ?> (Due: <?= !empty($task['due_date']) ? date('M d', strtotime($task['due_date'])) : 'N/A' ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="newTaskTitle">Or Create New Task</label>
                        <input type="text" class="form-control" id="newTaskTitle" name="title" placeholder="Task title">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="newTaskDescription">Task Description</label>
                        <textarea class="form-control" id="newTaskDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="taskPriority">Priority</label>
                                <select class="form-control" id="taskPriority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="taskDueDate">Due Date</label>
                                <input type="date" class="form-control" id="taskDueDate" name="due_date" 
                                       min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Task Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1" role="dialog" aria-labelledby="editTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTaskModalLabel">Edit Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= view('owner.task-actions') ?>" method="POST" id="editTaskForm">
                <div class="modal-body">
                    <input type="hidden" name="token" value="<?= special_chars($_SESSION['token']) ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="task_id" id="editTaskId">
                    
                    <div class="form-group mb-3">
                        <label for="editTaskTitle">Task Title</label>
                        <input type="text" class="form-control" id="editTaskTitle" name="title" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="editTaskDescription">Description</label>
                        <textarea class="form-control" id="editTaskDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="editTaskPriority">Priority</label>
                                <select class="form-control" id="editTaskPriority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="editTaskStatus">Status</label>
                                <select class="form-control" id="editTaskStatus" name="status">
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="overdue">Overdue</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editTaskDueDate">Due Date</label>
                                <input type="date" class="form-control" id="editTaskDueDate" name="due_date">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Task detail modal
    $('.view-task').click(function() {
        const taskId = $(this).data('id');
        $.ajax({
            url: '<?= view('owner.task-detail') ?>?id=' + taskId,
            method: 'GET',
            success: function(data) {
                $('#taskDetailContent').html(data);
            }
        });
    });
    
    // Edit task modal
    $('.edit-task').click(function() {
        const taskId = $(this).data('id');
        $.ajax({
            url: '<?= view('owner.task-data') ?>?id=' + taskId,
            method: 'GET',
            dataType: 'json',
            success: function(task) {
                if (task) {
                    $('#editTaskId').val(task.task_id);
                    $('#editTaskTitle').val(task.title);
                    $('#editTaskDescription').val(task.description || '');
                    $('#editTaskPriority').val(task.priority);
                    $('#editTaskStatus').val(task.status);
                    $('#editTaskDueDate').val(task.due_date);
                }
            }
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include layouts('owner.main');
?>