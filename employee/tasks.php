<?php
require_once __DIR__ . '/../config.php';

$title = 'Task & Schedule Management';
$sub_title = 'Daily Responsibilities';

// Get employee tasks and schedules
$tasks = get_employee_tasks($user['employee_id']);
$schedules = get_employee_schedules($user['employee_id']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_token($_POST['token'])) {
        $_SESSION['error'] = 'Invalid security token';
        header('Location: ' . view('employee.tasks'));
        exit;
    }

    if (isset($_POST['update_status'])) {
        if (update_task_status($_POST['task_id'], $_POST['status'])) {
            $_SESSION['success'] = 'Task status updated';
        } else {
            $_SESSION['error'] = 'Failed to update task status';
        }
    }

    if (isset($_POST['toggle_item'])) {
        if (toggle_checklist_item($_POST['item_id'])) {
            $_SESSION['success'] = 'Checklist item updated';
        } else {
            $_SESSION['error'] = 'Failed to update checklist';
        }
    }

    header('Location: ' . view('employee.tasks'));
    exit;
}

ob_start();
?>
<div class="row">
    <!-- Task List -->
    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex align-items-center">
                <i class="fas fa-tasks fa-lg mr-2"></i>
                <h3 class="h5 mb-0">My Tasks</h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($tasks)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                        <p class="mb-0">No pending tasks. Great job!</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($tasks as $task): ?>
                            <?php
                            $priority_class = [
                                'low' => 'info',
                                'medium' => 'warning',
                                'high' => 'danger'
                            ][$task['priority']];

                            $status_class = [
                                'pending' => 'secondary',
                                'in_progress' => 'primary',
                                'completed' => 'success',
                                'overdue' => 'danger'
                            ][$task['status']];

                            $due_date = strtotime($task['due_date']);
                            $is_overdue = ($task['status'] !== 'completed' && $due_date < time());
                            ?>

                            <div class="list-group-item <?= $is_overdue ? 'bg-light-warning' : '' ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h5 class="mb-0"><?= special_chars($task['title']) ?></h5>
                                            <span class="badge badge-pill badge-<?= $priority_class ?>">
                                                <?= ucfirst($task['priority']) ?>
                                            </span>
                                        </div>

                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted small">
                                                <i class="fas fa-home mr-1"></i> <?= $task['farm_name'] ?>
                                            </span>
                                            <span class="small <?= $is_overdue ? 'text-danger font-weight-bold' : 'text-muted' ?>">
                                                <i class="fas fa-clock mr-1"></i>
                                                <?= date('M d, Y', $due_date) ?>
                                            </span>
                                        </div>

                                        <?php if ($task['description']): ?>
                                            <p class="mb-2"><?= special_chars($task['description']) ?></p>
                                        <?php endif; ?>

                                        <!-- Progress -->
                                        <?php $checklist = get_task_checklist($task['task_id']); ?>
                                        <?php if (!empty($checklist)): ?>
                                            <?php
                                            $completed = array_filter($checklist, function ($item) {
                                                return $item['is_completed'];
                                            });
                                            $progress = count($completed) / count($checklist) * 100;
                                            ?>
                                            <div class="mt-2 mb-3">
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span>Progress</span>
                                                    <span><?= round($progress) ?>%</span>
                                                </div>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-<?= $status_class ?>"
                                                        role="progressbar"
                                                        style="width: <?= $progress ?>%"
                                                        aria-valuenow="<?= $progress ?>"
                                                        aria-valuemin="0"
                                                        aria-valuemax="100"></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Checklist -->
                                        <?php if (!empty($checklist)): ?>
                                            <div class="mb-3">
                                                <h6 class="mb-2 small font-weight-bold text-uppercase text-muted">Checklist:</h6>
                                                <div class="list-group">
                                                    <?php foreach ($checklist as $item): ?>
                                                        <form method="post" class="list-group-item list-group-item-action p-2 rounded mb-1">
                                                            <div class="d-flex align-items-center">
                                                                <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
                                                                <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                                                                <div class="custom-control custom-checkbox mr-2">
                                                                    <input
                                                                        type="checkbox"
                                                                        class="custom-control-input"
                                                                        id="item_<?= $item['item_id'] ?>"
                                                                        <?= $item['is_completed'] ? 'checked' : '' ?>
                                                                        onchange="this.form.submit()">
                                                                    <label class="custom-control-label" for="item_<?= $item['item_id'] ?>"></label>
                                                                </div>
                                                                <span class="<?= $item['is_completed'] ? 'text-muted text-decoration-line-through' : '' ?>">
                                                                    <?= special_chars($item['description']) ?>
                                                                </span>
                                                                <?php if ($item['is_completed']): ?>
                                                                    <small class="ml-auto text-muted small">
                                                                        <?= date('M j', strtotime($item['completed_at'])) ?>
                                                                    </small>
                                                                <?php endif; ?>
                                                                <button type="submit" name="toggle_item" class="btn btn-sm btn-link ml-auto" style="display:none">Update</button>
                                                            </div>
                                                        </form>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Status Actions -->
                                        <form method="post" class="mt-2">
                                            <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
                                            <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">

                                            <div class="btn-group d-flex">
                                                <?php if ($task['status'] === 'pending'): ?>
                                                    <button type="submit" name="update_status" value="in_progress"
                                                        class="btn btn-sm btn-primary flex-grow-1">
                                                        <i class="fas fa-play mr-1"></i> Start Task
                                                    </button>
                                                <?php endif; ?>

                                                <?php if ($task['status'] === 'in_progress'): ?>
                                                    <button type="submit" name="update_status" value="completed"
                                                        class="btn btn-sm btn-success flex-grow-1">
                                                        <i class="fas fa-check mr-1"></i> Complete
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Schedule Calendar -->
    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex align-items-center">
                <i class="fas fa-calendar-alt fa-lg mr-2"></i>
                <h3 class="h5 mb-0">My Schedule</h3>
            </div>
            <div class="card-body">
                <?php if (empty($schedules)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-calendar-times fa-3x mb-3 text-info"></i>
                        <p class="mb-0">No schedules assigned</p>
                        <small class="d-block mt-2">Check back later for updates</small>
                    </div>
                <?php else: ?>
                    <div id="schedule-calendar" class="mb-4" style="min-height: 400px;"></div>

                    <h5 class="mb-3 d-flex align-items-center">
                        <i class="fas fa-list mr-2"></i>
                        <span>Upcoming Responsibilities</span>
                    </h5>

                    <div class="list-group">
                        <?php foreach ($schedules as $schedule): ?>
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong><?= special_chars($schedule['title']) ?></strong>
                                    <span class="badge badge-info badge-pill">
                                        <?= date('h:i A', strtotime($schedule['start_time'])) ?> -
                                        <?= date('h:i A', strtotime($schedule['end_time'])) ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between small mt-1">
                                    <span class="text-muted">
                                        <i class="fas fa-home mr-1"></i> <?= $schedule['farm_name'] ?>
                                    </span>
                                    <span class="text-muted">
                                        <i class="fas fa-sync-alt mr-1"></i>
                                        <?= ucfirst($schedule['schedule_type']) ?>
                                    </span>
                                </div>
                                <p class="mt-2 mb-0 small"><?= special_chars($schedule['description']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($schedules)): ?>
    <script>
        $(document).ready(function() {
            const calendarEl = document.getElementById('schedule-calendar');
            if (calendarEl) {
                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'timeGridWeek',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    events: [
                        <?php foreach ($schedules as $index => $schedule): ?> {
                                title: '<?= addslashes($schedule['title']) ?>',
                                start: '<?= date('Y-m-d') ?>T<?= $schedule['start_time'] ?>',
                                end: '<?= date('Y-m-d') ?>T<?= $schedule['end_time'] ?>',
                                extendedProps: {
                                    description: '<?= addslashes($schedule['description']) ?>',
                                    farm: '<?= addslashes($schedule['farm_name']) ?>',
                                    type: '<?= $schedule['schedule_type'] ?>'
                                }
                            }
                            <?= $index < count($schedules) - 1 ? ',' : '' ?>
                        <?php endforeach; ?>
                    ],
                    eventContent: function(arg) {
                        return {
                            html: `<div class="fc-event-title">${arg.event.title}</div>
                           <div class="fc-event-time small">${arg.timeText}</div>`
                        };
                    },
                    eventDidMount: function(info) {
                        $(info.el).tooltip({
                            title: `<div class="text-left">
                                <strong>${info.event.title}</strong><br>
                                <small>${info.timeText}</small>
                                <div class="mt-2">${info.event.extendedProps.description}</div>
                                <div class="mt-2 small"><i class="fas fa-home mr-1"></i> ${info.event.extendedProps.farm}</div>
                                <div class="small"><i class="fas fa-sync-alt mr-1"></i> ${info.event.extendedProps.type}</div>
                            </div>`,
                            html: true,
                            placement: 'top',
                            trigger: 'hover',
                            container: 'body'
                        });
                    },
                    dayHeaderFormat: {
                        weekday: 'short'
                    },
                    slotLabelFormat: {
                        hour: 'numeric',
                        minute: '2-digit',
                        omitZeroMinute: false,
                        meridiem: 'short'
                    },
                    allDaySlot: false,
                    nowIndicator: true,
                    navLinks: true,
                    editable: false,
                    selectable: false
                });
                calendar.render();
            }
        });
    </script>

    <style>
        .fc-event {
            background-color: #17a2b8;
            border: none;
            border-radius: 4px;
            padding: 2px 4px;
            cursor: pointer;
            font-size: 0.85em;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .fc-event:hover {
            background-color: #138496;
        }

        .fc-event-title {
            font-weight: 500;
        }

        .fc-event-time {
            opacity: 0.9;
        }

        .fc-toolbar-title {
            font-size: 1.25rem;
            font-weight: 500;
        }

        .fc-button {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #495057;
            font-size: 0.85rem;
        }

        .fc-button:hover {
            background-color: #e9ecef;
        }

        .fc-button-primary {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: white;
        }

        .fc-button-primary:hover {
            background-color: #138496;
            border-color: #117a8b;
        }
    </style>
<?php endif; ?>

<?php
function get_employee_tasks($employee_id)
{
    $mysqli = db_connect();
    $stmt = $mysqli->prepare("
              SELECT t.*, f.farm_name 
              FROM tasks t
              JOIN farms f ON t.farm_id = f.farm_id
              WHERE t.assigned_to = ?
              AND t.status IN ('pending', 'in_progress')
              ORDER BY t.due_date ASC
          ");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_employee_schedules($employee_id)
{
    $mysqli = db_connect();
    $stmt = $mysqli->prepare("
              SELECT s.*, f.farm_name
              FROM schedules s
              JOIN farms f ON s.farm_id = f.farm_id
              WHERE s.assigned_to = ?
              AND (s.is_recurring = 1 OR s.start_time >= CURDATE())
              ORDER BY s.start_time ASC
          ");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_task_checklist($task_id)
{
    $mysqli = db_connect();
    $stmt = $mysqli->prepare("
              SELECT * FROM checklist_items 
              WHERE task_id = ?
              ORDER BY item_id ASC
          ");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function update_task_status($task_id, $status)
{
    $mysqli = db_connect();
    $stmt = $mysqli->prepare("
              UPDATE tasks 
              SET status = ?, 
                  completed_at = IF(? = 'completed', CURRENT_TIMESTAMP, NULL)
              WHERE task_id = ?
          ");
    $stmt->bind_param("ssi", $status, $status, $task_id);
    return $stmt->execute();
}

function toggle_checklist_item($item_id)
{
    $mysqli = db_connect();
    $stmt = $mysqli->prepare("
              UPDATE checklist_items 
              SET is_completed = NOT is_completed,
                  completed_at = IF(NOT is_completed, CURRENT_TIMESTAMP, NULL)
              WHERE item_id = ?
          ");
    $stmt->bind_param("i", $item_id);
    return $stmt->execute();
}

$push_js = [
    'libs/fullcalendar/main.min.js',
];

$push_css = [
    'libs/fullcalendar/main.min.css',
];

$content = ob_get_clean();
include layouts('employee.main');
?>