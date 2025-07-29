<?php
require_once __DIR__ . '/../config.php';

$title = 'Alerts';
$sub_title = 'System Notifications';

ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Active Alerts</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div>
                            <h5 class="alert-heading">Scale Malfunction - Device #4 (A-12)</h5>
                            <p class="mb-0">Consistent weight deviations detected. Last reading: 0g at 08:45 AM.</p>
                            <hr>
                            <p class="mb-0">Alert triggered: <?= date('M d, H:i') ?></p>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning d-flex align-items-center mt-3" role="alert">
                        <i class="fas fa-temperature-high fa-2x me-3"></i>
                        <div>
                            <h5 class="alert-heading">High Temperature - Barn B</h5>
                            <p class="mb-0">Temperature reading: 32°C (Optimal: 18-26°C). Check ventilation system.</p>
                            <hr>
                            <p class="mb-0">Alert triggered: <?= date('M d, H:i', strtotime('-30 minutes')) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title">Alert History</h5>
                        <div class="d-flex">
                            <select class="form-select form-select-sm me-2" style="width: auto;">
                                <option>Last 24 Hours</option>
                                <option selected>Last 7 Days</option>
                                <option>Last 30 Days</option>
                                <option>All Time</option>
                            </select>
                            <button class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Type</th>
                                    <th>Device/Location</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?= date('M d, H:i', strtotime('-1 day')) ?></td>
                                    <td><span class="badge bg-warning">Temperature</span></td>
                                    <td>Barn C</td>
                                    <td>Low temperature alert: 15°C</td>
                                    <td><span class="badge bg-success">Resolved</span></td>
                                </tr>
                                <tr>
                                    <td><?= date('M d, H:i', strtotime('-2 days')) ?></td>
                                    <td><span class="badge bg-info">Production</span></td>
                                    <td>System-wide</td>
                                    <td>15% drop in morning collection</td>
                                    <td><span class="badge bg-success">Resolved</span></td>
                                </tr>
                                <tr>
                                    <td><?= date('M d, H:i', strtotime('-4 days')) ?></td>
                                    <td><span class="badge bg-danger">Equipment</span></td>
                                    <td>Device #2 (A-5)</td>
                                    <td>Conveyor belt jam detected</td>
                                    <td><span class="badge bg-success">Resolved</span></td>
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
$content = ob_get_clean();
include layouts('employee.main');
?>