<?php
require_once '../backend/auth.php';
require_once '../backend/db.php';

$auth->requireRole('reader');

$userId = $_SESSION['user_id'];
$userManager = new UserManager($db);
$scheduleManager = new ScheduleManager($db);

// Get current and upcoming assignments
$currentDate = date('Y-m-d');
$assignments = $scheduleManager->getReaderAssignments($userId, $currentDate);

// Get availability
$availability = $userManager->getUserAvailability($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reader Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .assignment-card {
            border-left: 4px solid #0d6efd;
            margin-bottom: 15px;
        }
        .availability-day {
            cursor: pointer;
        }
        .availability-day.active {
            background-color: #0d6efd;
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Parish Reader System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="reader_dashboard.php">My Assignments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reader_availability.php">My Availability</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col">
                <h2>My Upcoming Assignments</h2>
                <p class="text-muted">Here are your scheduled readings for upcoming masses</p>
            </div>
        </div>

        <?php if (empty($assignments)): ?>
            <div class="alert alert-info">
                You don't have any upcoming assignments. Check back later or update your availability.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($assignments as $assignment): ?>
                    <div class="col-md-6 mb-3">
                        <div class="assignment-card card p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h5><?= date('l, F j', strtotime($assignment['mass_date'])) ?></h5>
                                    <p class="mb-1">
                                        <i class="bi bi-clock"></i> <?= date('g:i a', strtotime($assignment['mass_time'])) ?>
                                        <span class="badge bg-info ms-2"><?= $assignment['mass_type'] ?></span>
                                    </p>
                                    <p class="mb-1"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($assignment['location']) ?></p>
                                </div>
                                <span class="badge bg-primary"><?= $assignment['role'] ?></span>
                            </div>
                            
                            <?php if (!empty($assignment['scripture_reference'])): ?>
                                <div class="alert alert-light mt-2 mb-0">
                                    <strong>Scripture:</strong> <?= htmlspecialchars($assignment['scripture_reference']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-3 d-flex justify-content-between">
                                <div>
                                    <?php if ($assignment['status'] === 'Assigned'): ?>
                                        <span class="badge bg-warning">Awaiting Confirmation</span>
                                    <?php elseif ($assignment['status'] === 'Confirmed'): ?>
                                        <span class="badge bg-success">Confirmed</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= $assignment['status'] ?></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-outline-danger" 
                                        onclick="confirmCancelAssignment(<?= $assignment['assignment_id'] ?>)">
                                        Cancel
                                    </button>
                                    <?php if ($assignment['status'] === 'Assigned'): ?>
                                        <button class="btn btn-sm btn-success ms-1" 
                                            onclick="confirmAssignment(<?= $assignment['assignment_id'] ?>)">
                                            Confirm
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="row mt-5">
            <div class="col">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">My Weekly Availability</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Click on days to toggle your availability</p>
                        
                        <div class="d-flex flex-wrap gap-2">
                            <?php
                            $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                            foreach ($days as $day): 
                                $isAvailable = $availability[$day];
                            ?>
                                <div class="availability-day text-center p-3 rounded border <?= $isAvailable ? 'active' : '' ?>" 
                                    data-day="<?= $day ?>"
                                    onclick="toggleAvailability('<?= $day ?>', this)">
                                    <div class="fw-bold"><?= substr($day, 0, 3) ?></div>
                                    <small><?= $isAvailable ? 'Available' : 'Unavailable' ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-light py-3 mt-4">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy;2025 - <?= date('Y') ?>S<sup>t</sup>Basil. All right reserved!</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleAvailability(day, element) {
            const isActive = element.classList.contains('active');
            
            fetch('update_availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `day=${encodeURIComponent(day)}&available=${!isActive}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    element.classList.toggle('active');
                    const statusText = element.querySelector('small');
                    if (statusText) {
                        statusText.textContent = data.available ? 'Available' : 'Unavailable';
                    }
                } else {
                    alert('Failed to update availability');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update availability');
            });
        }

        function confirmAssignment(assignmentId) {
            if (confirm('Are you sure you can fulfill this assignment?')) {
                window.location.href = 'confirm_assignment.php?id=' + assignmentId;
            }
        }

        function confirmCancelAssignment(assignmentId) {
            if (confirm('Are you sure you want to cancel this assignment? Please contact the coordinator if this is an emergency.')) {
                window.location.href = 'cancel_assignment.php?id=' + assignmentId;
            }
        }
    </script>
</body>
</html>