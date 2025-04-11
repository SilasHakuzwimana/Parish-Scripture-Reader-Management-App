<?php
require_once '../backend/auth.php';
require_once '../backend/db.php';

$auth->requireRole('admin');

$scheduleManager = new ScheduleManager($db);
$userManager = new UserManager($db);

// Get current week's masses
$startDate = date('Y-m-d', strtotime('monday this week'));
$endDate = date('Y-m-d', strtotime('sunday this week'));
$masses = $scheduleManager->getMassesByDateRange($startDate, $endDate);

// Get all readers
$readers = $userManager->getAllReaders();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            height: 100vh;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        .sidebar .nav-link {
            color: #333;
        }
        .sidebar .nav-link.active {
            color: #0d6efd;
            font-weight: bold;
        }
        .calendar-day {
            border: 1px solid #dee2e6;
            min-height: 120px;
        }
        .mass-card {
            border-left: 4px solid #0d6efd;
            margin-bottom: 10px;
        }
        .reader-badge {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="admin_dashboard.php">
                                <i class="bi bi-calendar-week"></i> Schedule
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_readers.php">
                                <i class="bi bi-people"></i> Readers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_reports.php">
                                <i class="bi bi-graph-up"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_settings.php">
                                <i class="bi bi-gear"></i> Settings
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Weekly Schedule</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button class="btn btn-sm btn-outline-secondary">Share</button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">Print</button>
                        </div>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addMassModal">
                            <i class="bi bi-plus"></i> Add Mass
                        </button>
                    </div>
                </div>

                <!-- Week navigation -->
                <div class="row mb-3">
                    <div class="col">
                        <div class="btn-group" role="group">
                            <a href="?week=<?= date('Y-m-d', strtotime($startDate . ' -7 days')) ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-chevron-left"></i> Previous Week
                            </a>
                            <button class="btn btn-outline-secondary" disabled>
                                <?= date('M j', strtotime($startDate)) ?> - <?= date('M j, Y', strtotime($endDate)) ?>
                            </button>
                            <a href="?week=<?= date('Y-m-d', strtotime($startDate . ' +7 days')) ?>" class="btn btn-outline-secondary">
                                Next Week <i class="bi bi-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-auto">
                        <a href="backend/pdfgenerator.php?type=weekly&date=<?= $startDate ?>" class="btn btn-primary">
                            <i class="bi bi-download"></i> Download PDF
                        </a>
                    </div>
                </div>

                <!-- Weekly calendar -->
                <div class="row">
                    <?php
                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    foreach ($days as $day) {
                        $currentDate = date('Y-m-d', strtotime($startDate . ' +' . (array_search($day, $days) . ' days')));
                        $dayMasses = array_filter($masses, function($mass) use ($currentDate) {
                            return $mass['mass_date'] === $currentDate;
                        });
                        ?>
                        <div class="col-md-4 col-lg-3 mb-4">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0"><?= $day ?><br>
                                        <small class="text-muted"><?= date('M j', strtotime($currentDate)) ?></small>
                                    </h5>
                                </div>
                                <div class="card-body p-2">
                                    <?php if (empty($dayMasses)): ?>
                                        <div class="text-center text-muted py-3">No masses scheduled</div>
                                    <?php else: ?>
                                        <?php foreach ($dayMasses as $mass): 
                                            $assignments = $scheduleManager->getAssignmentsForMass($mass['mass_id']);
                                        ?>
                                            <div class="mass-card p-2 mb-2">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <strong><?= date('g:i a', strtotime($mass['mass_time'])) ?></strong>
                                                    <span class="badge bg-info"><?= $mass['mass_type'] ?></span>
                                                </div>
                                                <small class="text-muted d-block mb-2"><?= $mass['location'] ?></small>
                                                
                                                <?php foreach ($assignments as $assignment): ?>
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span><?= $assignment['role'] ?>:</span>
                                                        <span class="badge bg-primary reader-badge" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#assignReaderModal"
                                                            data-mass-id="<?= $mass['mass_id'] ?>"
                                                            data-role="<?= htmlspecialchars($assignment['role']) ?>"
                                                            data-current-reader="<?= $assignment['user_id'] ?>">
                                                            <?= $assignment['name'] ?>
                                                        </span>
                                                    </div>
                                                <?php endforeach; ?>
                                                
                                                <div class="mt-2 d-flex justify-content-end">
                                                    <button class="btn btn-sm btn-outline-secondary me-1"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editMassModal"
                                                        data-mass-id="<?= $mass['mass_id'] ?>"
                                                        data-date="<?= $mass['mass_date'] ?>"
                                                        data-time="<?= $mass['mass_time'] ?>"
                                                        data-type="<?= $mass['mass_type'] ?>"
                                                        data-location="<?= htmlspecialchars($mass['location']) ?>"
                                                        data-notes="<?= htmlspecialchars($mass['notes'] ?? '') ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger"
                                                        onclick="confirmDeleteMass(<?= $mass['mass_id'] ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-sm btn-outline-primary w-100 mt-2"
                                        data-bs-toggle="modal"
                                        data-bs-target="#addMassModal"
                                        data-date="<?= $currentDate ?>">
                                        <i class="bi bi-plus"></i> Add Mass
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <div class="row mt-4">
                    <div class="col">
                        <button class="btn btn-primary" onclick="autoAssignReaders()">
                            <i class="bi bi-magic"></i> Auto-Assign Readers for This Week
                        </button>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Mass Modal -->
    <div class="modal fade" id="addMassModal" tabindex="-1" aria-labelledby="addMassModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="save_mass.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addMassModalLabel">Add New Mass</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="massDate" class="form-label">Date</label>
                            <input type="date" class="form-control" id="massDate" name="mass_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="massTime" class="form-label">Time</label>
                            <input type="time" class="form-control" id="massTime" name="mass_time" required>
                        </div>
                        <div class="mb-3">
                            <label for="massType" class="form-label">Type</label>
                            <select class="form-select" id="massType" name="mass_type" required>
                                <option value="Sunday">Sunday Mass</option>
                                <option value="Weekday">Weekday Mass</option>
                                <option value="Special">Special Mass</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="massLocation" class="form-label">Location</label>
                            <input type="text" class="form-control" id="massLocation" name="location" required>
                        </div>
                        <div class="mb-3">
                            <label for="massNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="massNotes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Mass</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Mass Modal -->
    <div class="modal fade" id="editMassModal" tabindex="-1" aria-labelledby="editMassModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="save_mass.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editMassModalLabel">Edit Mass</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" id="editMassId" name="mass_id">
                        <div class="mb-3">
                            <label for="editMassDate" class="form-label">Date</label>
                            <input type="date" class="form-control" id="editMassDate" name="mass_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="editMassTime" class="form-label">Time</label>
                            <input type="time" class="form-control" id="editMassTime" name="mass_time" required>
                        </div>
                        <div class="mb-3">
                            <label for="editMassType" class="form-label">Type</label>
                            <select class="form-select" id="editMassType" name="mass_type" required>
                                <option value="Sunday">Sunday Mass</option>
                                <option value="Weekday">Weekday Mass</option>
                                <option value="Special">Special Mass</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editMassLocation" class="form-label">Location</label>
                            <input type="text" class="form-control" id="editMassLocation" name="location" required>
                        </div>
                        <div class="mb-3">
                            <label for="editMassNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="editMassNotes" name="notes" rows="3"></textarea>
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

    <!-- Assign Reader Modal -->
    <div class="modal fade" id="assignReaderModal" tabindex="-1" aria-labelledby="assignReaderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="save_assignment.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="assignReaderModalLabel">Assign Reader</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="assignMassId" name="mass_id">
                        <input type="hidden" id="assignRole" name="role">
                        <div class="mb-3">
                            <label for="readerSelect" class="form-label">Select Reader</label>
                            <select class="form-select" id="readerSelect" name="reader_id" required>
                                <option value="">-- Select Reader --</option>
                                <?php foreach ($readers as $reader): ?>
                                    <option value="<?= $reader['user_id'] ?>"><?= htmlspecialchars($reader['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="scriptureReference" class="form-label">Scripture Reference (Optional)</label>
                            <input type="text" class="form-control" id="scriptureReference" name="scripture_reference">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Assign Reader</button>
                    </div>
                </form>
            </div>
        </div>
        <footer class="footer text-center">
            <p>&copy; 2025 - <?php echo date('Y') ?> S<sup>t</sup>Basil. All right resevered!</p>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle modal data
        document.addEventListener('DOMContentLoaded', function() {
            // Add Mass modal date preset
            const addMassModal = document.getElementById('addMassModal');
            if (addMassModal) {
                addMassModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const date = button.getAttribute('data-date');
                    if (date) {
                        document.getElementById('massDate').value = date;
                    }
                });
            }

            // Edit Mass modal data
            const editMassModal = document.getElementById('editMassModal');
            if (editMassModal) {
                editMassModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    document.getElementById('editMassId').value = button.getAttribute('data-mass-id');
                    document.getElementById('editMassDate').value = button.getAttribute('data-date');
                    document.getElementById('editMassTime').value = button.getAttribute('data-time');
                    document.getElementById('editMassType').value = button.getAttribute('data-type');
                    document.getElementById('editMassLocation').value = button.getAttribute('data-location');
                    document.getElementById('editMassNotes').value = button.getAttribute('data-notes');
                });
            }

            // Assign Reader modal data
            const assignReaderModal = document.getElementById('assignReaderModal');
            if (assignReaderModal) {
                assignReaderModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    document.getElementById('assignMassId').value = button.getAttribute('data-mass-id');
                    document.getElementById('assignRole').value = button.getAttribute('data-role');
                    
                    const currentReader = button.getAttribute('data-current-reader');
                    const readerSelect = document.getElementById('readerSelect');
                    if (currentReader) {
                        readerSelect.value = currentReader;
                    } else {
                        readerSelect.value = '';
                    }
                });
            }
        });

        function confirmDeleteMass(massId) {
            if (confirm('Are you sure you want to delete this mass? All assignments will be removed.')) {
                window.location.href = 'delete_mass.php?id=' + massId;
            }
        }

        function autoAssignReaders() {
            if (confirm('This will automatically assign available readers to all masses this week. Continue?')) {
                window.location.href = 'auto_assign.php?week=<?= $startDate ?>';
            }
        }
    </script>
</body>
</html>