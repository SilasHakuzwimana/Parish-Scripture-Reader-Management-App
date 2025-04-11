<?php
require_once '../vendor/autoload.php';
require_once  '../backend/db.php';
require_once  '../includes/functions.php';

use PHPMailer\PHPMailer\PHPMailer;

$errors = [];
$success = false;

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $parish_role = trim($_POST['parish_role'] ?? 'Reader');
    $availability = $_POST['availability'] ?? [];

    try {
        // Validate inputs
        if (empty($name)) {
            throw new Exception('Full name is required');
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address');
        }

        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters');
        }

        if ($password !== $confirm_password) {
            throw new Exception('Passwords do not match');
        }

        if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
            throw new Exception('Please enter a valid phone number');
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Email already registered');
        }

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Start transaction
        $pdo->beginTransaction();

        // Insert user
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, phone, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $passwordHash, $phone, 'reader']); // Default role is 'reader'
        $userId = $pdo->lastInsertId();

        // Insert availability
        $availabilityStmt = $pdo->prepare("INSERT INTO reader_availability (user_id, day_of_week, is_available) VALUES (?, ?, ?)");
        foreach (['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day) {
            $isAvailable = in_array($day, $availability) ? 1 : 0;
            $availabilityStmt->execute([$userId, $day, $isAvailable]);
        }

        // Commit transaction
        $pdo->commit();

        // Send welcome email
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'info.stbasile@gmail.com';
        $mail->Password = '';#App password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom('info.stbasile@gmail.com', 'Parish Scripture Reader System');
        $mail->addAddress($email, $name);
        
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to Our Parish Reader System';
        $mail->Body = "
            <h2>Welcome, $name!</h2>
            <p>Thank you for registering as a scripture reader at our parish.</p>
            <p>You can now log in to the system using your email address and the password you created.</p>
            <p><a href=\"https://" . $_SERVER['HTTP_HOST'] . "/login.php\">Click here to login</a></p>
            <hr>
            <p>If you have any questions, please contact the parish office.</p>
            <p>Blessings,<br>Your Parish Team</p>
        ";
        
        $mail->send();

        $success = true;
        $_POST = []; // Clear form

    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Parish Scripture Reader</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --parish-primary: #2c3e50;
            --parish-secondary: #3498db;
        }
        body { 
            background-color: #f8f9fa;
            /* background-image: url('../assets/images/parish-bg-light.jpg'); */
            background-size: cover;
            background-position: center;
        }
        .registration-card {
            max-width: 600px;
            background: rgba(255, 255, 255, 0.97);
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        .parish-logo {
            text-align: center;
            padding: 20px 0;
        }
        .parish-logo img {
            height: 80px;
        }
        .btn-parish {
            background-color: var(--parish-primary);
            border-color: var(--parish-primary);
        }
        .btn-parish:hover {
            background-color: var(--parish-secondary);
            border-color: var(--parish-secondary);
        }
        .password-strength {
            height: 5px;
            background: #eee;
            margin-top: 5px;
            border-radius: 3px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s;
        }
        .availability-day {
            cursor: pointer;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 8px 12px;
            margin: 5px;
            text-align: center;
        }
        .availability-day.selected {
            background-color: var(--parish-primary);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="registration-card mx-auto p-4">
            <div class="parish-logo">
                <img src="../assets/images/parish-logo.png" alt="Parish Logo" class="mb-3">
                <h2>Reader Registration</h2>
                <p class="text-muted">Join our scripture reading team</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <h4>Registration Successful!</h4>
                    <p>Thank you for registering. A confirmation email has been sent to your address.</p>
                    <a href="login.php" class="btn btn-parish">Proceed to Login</a>
                </div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h5>Please fix these errors:</h5>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" id="registrationForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" 
                                   pattern="[0-9]{10,15}" required>
                            <div class="form-text">10-15 digits, no spaces or dashes</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="parish_role" class="form-label">Preferred Role</label>
                            <select class="form-select" id="parish_role" name="parish_role">
                                <option value="Reader" <?= ($_POST['parish_role'] ?? '') === 'Reader' ? 'selected' : '' ?>>Scripture Reader</option>
                                <option value="Lector" <?= ($_POST['parish_role'] ?? '') === 'Lector' ? 'selected' : '' ?>>Lector</option>
                                <option value="Cantor" <?= ($_POST['parish_role'] ?? '') === 'Cantor' ? 'selected' : '' ?>>Cantor</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="8">
                            <div class="password-strength">
                                <div class="password-strength-bar" id="password-strength-bar"></div>
                            </div>
                            <div class="form-text">Minimum 8 characters with uppercase and number</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Availability for Readings *</label>
                        <div class="d-flex flex-wrap">
                            <?php 
                            $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                            foreach ($days as $day): 
                                $isChecked = in_array($day, $_POST['availability'] ?? []);
                            ?>
                                <div class="availability-day <?= $isChecked ? 'selected' : '' ?>">
                                    <input type="checkbox" id="avail_<?= strtolower($day) ?>" 
                                           name="availability[]" value="<?= $day ?>" 
                                           <?= $isChecked ? 'checked' : '' ?> hidden>
                                    <label for="avail_<?= strtolower($day) ?>" style="cursor:pointer">
                                        <?= substr($day, 0, 3) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-text">Select days you're typically available</div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">terms and conditions</a></label>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-parish btn-lg">Register</button>
                    </div>
                </form>

                <div class="mt-3 text-center">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>Parish Scripture Reader Agreement</h6>
                    <p>By registering as a scripture reader, you agree to:</p>
                    <ul>
                        <li>Commit to scheduled reading assignments when available</li>
                        <li>Arrive at least 15 minutes before Mass when scheduled</li>
                        <li>Notify the parish office if unable to fulfill an assignment</li>
                        <li>Maintain appropriate decorum during services</li>
                        <li>Respect the sacred nature of the readings</li>
                    </ul>
                    <p>The parish may remove reader privileges for repeated no-shows or inappropriate conduct.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-parish" data-bs-dismiss="modal">I Understand</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('password-strength-bar');
            let strength = 0;
            
            // Length
            if (password.length >= 8) strength += 30;
            if (password.length >= 12) strength += 10;
            
            // Complexity
            if (/[A-Z]/.test(password)) strength += 20;
            if (/[0-9]/.test(password)) strength += 20;
            if (/[^A-Za-z0-9]/.test(password)) strength += 20;
            
            strength = Math.min(strength, 100);
            strengthBar.style.width = strength + '%';
            strengthBar.style.backgroundColor = 
                strength < 40 ? '#dc3545' : 
                strength < 70 ? '#ffc107' : '#28a745';
        });

        // Availability day selection
        document.querySelectorAll('.availability-day').forEach(day => {
            day.addEventListener('click', function() {
                const checkbox = this.querySelector('input[type="checkbox"]');
                checkbox.checked = !checkbox.checked;
                this.classList.toggle('selected');
            });
        });

        // Form validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
                document.getElementById('confirm_password').focus();
            }
            
            if (!document.getElementById('terms').checked) {
                e.preventDefault();
                alert('You must agree to the terms and conditions');
            }
        });
    </script>
</body>
</html>