<?php
require_once __DIR__ . '/../config.php';

$title = 'Feeding & Health';
$sub_title = 'Chicken Health & Feed Monitoring';

ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Daily Feeding Log</h5>
                </div>
                <div class="card-body">
                    <form id="feedingForm">
                        <div class="mb-3">
                            <label class="form-label">Feed Type</label>
                            <select class="form-select" name="feed_type" required>
                                <option value="Starter">Starter Feed</option>
                                <option value="Grower">Grower Feed</option>
                                <option value="Layer">Layer Feed</option>
                                <option value="Supplement">Supplement Mix</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity (kg)</label>
                            <input type="number" class="form-control" name="quantity" step="0.1" min="0.1" value="25.5" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Time of Feeding</label>
                            <input type="time" class="form-control" name="feeding_time" value="<?= date('H:i') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Barn/Section</label>
                            <select class="form-select" name="barn_section" required>
                                <option value="A">Barn A</option>
                                <option value="B">Barn B</option>
                                <option value="C">Barn C</option>
                                <option value="D">Barn D</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Log Feeding</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Health Observations</h5>
                </div>
                <div class="card-body">
                    <form id="healthForm">
                        <div class="mb-3">
                            <label class="form-label">Observation Type</label>
                            <select class="form-select" name="observation_type" required>
                                <option value="Normal">Normal Behavior</option>
                                <option value="Sickness">Signs of Sickness</option>
                                <option value="Injury">Injury</option>
                                <option value="Death">Mortality</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Affected Count</label>
                            <input type="number" class="form-control" name="affected_count" min="0" value="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Barn/Section</label>
                            <select class="form-select" name="barn_section" required>
                                <option value="A">Barn A</option>
                                <option value="B">Barn B</option>
                                <option value="C">Barn C</option>
                                <option value="D">Barn D</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Record Observation</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Recent Health & Feeding Logs</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date/Time</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Barn</th>
                                    <th>Recorded By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?= date('M d, H:i') ?></td>
                                    <td><span class="badge bg-info">Feeding</span></td>
                                    <td>Layer Feed - 25.5kg</td>
                                    <td>Barn B</td>
                                    <td>You</td>
                                </tr>
                                <tr>
                                    <td><?= date('M d, H:i', strtotime('-2 hours')) ?></td>
                                    <td><span class="badge bg-warning">Health</span></td>
                                    <td>2 chickens showing lethargy</td>
                                    <td>Barn A</td>
                                    <td>You</td>
                                </tr>
                                <tr>
                                    <td><?= date('M d, H:i', strtotime('-1 day')) ?></td>
                                    <td><span class="badge bg-info">Feeding</span></td>
                                    <td>Supplement Mix - 3.2kg</td>
                                    <td>Barn C</td>
                                    <td>You</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$push_js = [
    'js/pages/employee/feeding_health.js'
];

$content = ob_get_clean();
include layouts('employee.main');
?>